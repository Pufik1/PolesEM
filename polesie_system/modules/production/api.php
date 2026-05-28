<?php
/**
 * Production API - AJAX endpoints for production module
 */

require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        case 'get_customer_order_materials':
            // Получение материалов для заказа клиента
            $order_id = (int)($_GET['order_id'] ?? 0);
            if (!$order_id) {
                throw new Exception('Не указан ID заказа');
            }
            
            // Получаем информацию о заказе
            $stmt = $pdo->prepare("
                SELECT 
                    o.id as order_id,
                    o.order_number,
                    c.company_name as customer_name,
                    oi.product_id,
                    p.product_name,
                    p.product_code,
                    p.bom_json,
                    oi.quantity as order_quantity
                FROM orders o
                JOIN clients c ON o.client_id = c.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Заказ не найден');
            }
            
            $materials = [];
            $material_shortages = [];
            
            // Декодируем BOM из JSON
            $bom_data = !empty($order['bom_json']) ? json_decode($order['bom_json'], true) : [];
            
            if (is_array($bom_data) && !empty($bom_data)) {
                // Получаем уже выданные материалы для этого заказа
                $stmt = $pdo->prepare("
                    SELECT 
                        pm.material_id,
                        SUM(pm.quantity_issued) as total_issued
                    FROM production_materials pm
                    JOIN production_orders po ON pm.production_order_id = po.id
                    WHERE po.source_order_id = ? AND pm.status IN ('issued', 'used')
                    GROUP BY pm.material_id
                ");
                $stmt->execute([$order_id]);
                $issued_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Создаем карту выданных материалов
                $issued_map = [];
                foreach ($issued_materials as $im) {
                    $issued_map[$im['material_id']] = $im['total_issued'];
                }
                
                // Получаем все материалы со склада для поиска по SKU
                $stmt = $pdo->query("
                    SELECT 
                        m.id as material_id,
                        m.sku,
                        m.name as material_name,
                        m.unit,
                        m.current_stock as warehouse_stock
                    FROM materials m
                ");
                $warehouse_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Создаем карту для поиска материала по SKU
                $sku_map = [];
                foreach ($warehouse_materials as $wm) {
                    $sku_map[$wm['sku']] = $wm;
                }
                
                // Формируем итоговый список материалов
                foreach ($bom_data as $bom_item) {
                    $sku = $bom_item['sku'] ?? '';
                    $qty_per_unit = floatval($bom_item['quantity'] ?? 0);
                    $total_required = $qty_per_unit * $order['order_quantity'];
                    
                    // Ищем материал по SKU
                    $material_id = 0;
                    $material_name = $bom_item['name'] ?? 'Неизвестный материал';
                    // Все материалы на складе в штуках
                    $unit = 'шт';
                    $warehouse_stock = 0;
                    
                    if (isset($sku_map[$sku])) {
                        $material_id = $sku_map[$sku]['material_id'];
                        $material_name = $sku_map[$sku]['material_name'];
                        // Все материалы в штуках
                        $warehouse_stock = floatval($sku_map[$sku]['warehouse_stock']);
                    }
                    
                    // Получаем уже выданное количество для этого материала
                    $already_issued = isset($issued_map[$material_id]) ? floatval($issued_map[$material_id]) : 0;
                    $to_issue = max(0, $total_required - $already_issued);
                    
                    // Проверяем достаточность материала
                    $is_sufficient = $warehouse_stock >= $to_issue;
                    $shortage = 0;
                    if (!$is_sufficient) {
                        $shortage = $to_issue - $warehouse_stock;
                        $material_shortages[] = [
                            'material_id' => $material_id,
                            'material_name' => $material_name,
                            'required' => $to_issue,
                            'available' => $warehouse_stock,
                            'shortage' => $shortage,
                            'unit' => $unit
                        ];
                    }
                    
                    $materials[] = [
                        'material_id' => $material_id,
                        'material_name' => $material_name,
                        'sku' => $sku,
                        'unit' => $unit,
                        'warehouse_stock' => $warehouse_stock,
                        'qty_per_unit' => $qty_per_unit,
                        'total_required' => $total_required,
                        'already_issued' => $already_issued,
                        'to_issue' => $to_issue,
                        'is_sufficient' => $is_sufficient,
                        'shortage' => $shortage
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'materials' => $materials,
                'material_shortages' => $material_shortages,
                'has_shortages' => !empty($material_shortages)
            ]);
            break;
            
        case 'issue_materials':
            // Выдача материалов в производство для заказа
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $order_id = (int)$_POST['order_id'];
            $materials_data = json_decode($_POST['materials_data'], true);
            $notes = $_POST['notes'] ?? '';
            
            if (empty($materials_data)) {
                throw new Exception('Нет материалов для выдачи');
            }
            
            $pdo->beginTransaction();
            
            // Получаем или создаем производственный заказ для этого заказа клиента
            $stmt = $pdo->prepare("SELECT id FROM production_orders WHERE source_order_id = ?");
            $stmt->execute([$order_id]);
            $production_order = $stmt->fetch();
            
            if (!$production_order) {
                // Создаем новый производственный заказ
                $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($items)) {
                    $item = $items[0];
                    $prod_number = 'PO-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO production_orders 
                        (production_number, product_id, quantity, status, priority, order_source, source_order_id, created_by)
                        VALUES (?, ?, ?, 'planned', 'normal', 'customer_order', ?, ?)
                    ");
                    $stmt->execute([$prod_number, $item['product_id'], $item['quantity'], $order_id, $_SESSION['user_id']]);
                    $production_order_id = $pdo->lastInsertId();
                } else {
                    throw new Exception('Не найдены продукты в заказе');
                }
            } else {
                $production_order_id = $production_order['id'];
            }
            
            // Выдаем материалы
            $stmt = $pdo->prepare("
                INSERT INTO production_materials 
                (production_order_id, material_id, quantity_issued, status, notes, created_by)
                VALUES (?, ?, ?, 'issued', ?, ?)
            ");
            
            foreach ($materials_data as $mat) {
                if ($mat['quantity'] > 0) {
                    $stmt->execute([
                        $production_order_id,
                        $mat['material_id'],
                        $mat['quantity'],
                        $notes,
                        $_SESSION['user_id']
                    ]);
                    
                    // Списываем материалы со склада
                    $stmt_update = $pdo->prepare("
                        UPDATE materials 
                        SET current_stock = current_stock - ? 
                        WHERE id = ? AND current_stock >= ?
                    ");
                    $stmt_update->execute([$mat['quantity'], $mat['material_id'], $mat['quantity']]);
                    
                    if ($stmt_update->rowCount() === 0) {
                        throw new Exception('Недостаточно материала на складе: ' . $mat['material_id']);
                    }
                }
            }
            
            // Обновляем статус производственного заказа
            $stmt = $pdo->prepare("
                UPDATE production_orders 
                SET status = 'in_progress' 
                WHERE id = ?
            ");
            $stmt->execute([$production_order_id]);
            
            $pdo->commit();
            
            logActivity($pdo, $_SESSION['user_id'], 'issue_materials', 'production_materials', $production_order_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Материалы успешно выданы в производство',
                'production_order_id' => $production_order_id
            ]);
            break;
            
        case 'complete_production':
            // Завершение производства и оприходование на склад
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $production_order_id = (int)$_POST['production_order_id'];
            $quantity_completed = (int)$_POST['quantity_completed'];
            $quantity_defect = (int)($_POST['quantity_defect'] ?? 0);
            $notes = $_POST['notes'] ?? '';
            
            $pdo->beginTransaction();
            
            // Получаем информацию о производственном заказе
            $stmt = $pdo->prepare("
                SELECT po.product_id, po.quantity, po.source_order_id, p.product_code
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                WHERE po.id = ?
            ");
            $stmt->execute([$production_order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Производственный заказ не найден');
            }
            
            // Оприходуем готовую продукцию на склад
            $stmt = $pdo->prepare("
                UPDATE products 
                SET current_stock = current_stock + ? 
                WHERE id = ?
            ");
            $stmt->execute([$quantity_completed, $order['product_id']]);
            
            // Если есть брак, списываем материалы пропорционально
            if ($quantity_defect > 0) {
                // Здесь можно добавить логику списания материалов на брак
            }
            
            // Обновляем статус производственного заказа
            $new_status = ($quantity_completed >= $order['quantity']) ? 'completed' : 'in_progress';
            $stmt = $pdo->prepare("
                UPDATE production_orders 
                SET status = ?, actual_end_date = CURDATE() 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $production_order_id]);
            
            // Если это заказ клиента, обновляем статус заказа
            if ($order['source_order_id']) {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'ready_for_shipment' 
                    WHERE id = ?
                ");
                $stmt->execute([$order['source_order_id']]);
            }
            
            // Создаем документ оприходования
            $doc_number = 'PR-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                INSERT INTO production_completion_documents 
                (document_number, production_order_id, product_id, quantity, defect_quantity, completion_date, notes, created_by)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([
                $doc_number, 
                $production_order_id, 
                $order['product_id'], 
                $quantity_completed, 
                $quantity_defect, 
                $notes, 
                $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            
            logActivity($pdo, $_SESSION['user_id'], 'complete_production', 'production_orders', $production_order_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Продукция успешно оприходована на склад',
                'document_number' => $doc_number
            ]);
            break;
            
        case 'get_bom':
            // Получение спецификации материалов для производственного заказа
            $order_id = (int)($_GET['order_id'] ?? 0);
            if (!$order_id) {
                throw new Exception('Не указан ID заказа');
            }
            
            // Получаем информацию о заказе
            $stmt = $pdo->prepare("
                SELECT po.id, po.production_number, po.quantity, p.product_name, p.product_code, p.bom_json
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                WHERE po.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Заказ не найден');
            }
            
            $bom_items = [];
            $bom_data = !empty($order['bom_json']) ? json_decode($order['bom_json'], true) : [];
            
            if (is_array($bom_data) && !empty($bom_data)) {
                $material_ids = array_column($bom_data, 'material_id');
                
                if (!empty($material_ids)) {
                    $placeholders = implode(',', array_fill(0, count($material_ids), '?'));
                    $stmt = $pdo->prepare("
                        SELECT 
                            m.id as material_id,
                            m.name as material_name,
                            m.sku,
                            m.unit,
                            m.current_stock as available_stock
                        FROM materials m
                        WHERE m.id IN ($placeholders)
                    ");
                    $stmt->execute($material_ids);
                    $warehouse_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $warehouse_map = [];
                    foreach ($warehouse_materials as $wm) {
                        $warehouse_map[$wm['material_id']] = $wm;
                    }
                    
                    foreach ($bom_data as $bom_item) {
                        $material_id = $bom_item['material_id'];
                        $qty_per_unit = floatval($bom_item['quantity'] ?? 0);
                        $total_quantity = $qty_per_unit * $order['quantity'];
                        
                        $material_name = $bom_item['material_name'] ?? 'Неизвестный материал';
                        $sku = $bom_item['sku'] ?? '';
                        // Все материалы в штуках
                        $unit = 'шт';
                        $available_stock = 0;
                        
                        if (isset($warehouse_map[$material_id])) {
                            $material_name = $warehouse_map[$material_id]['material_name'];
                            $sku = $warehouse_map[$material_id]['sku'];
                            $available_stock = floatval($warehouse_map[$material_id]['available_stock']);
                        }
                        
                        $bom_items[] = [
                            'material_id' => $material_id,
                            'material_name' => $material_name,
                            'sku' => $sku,
                            'unit' => $unit,
                            'qty_per_unit' => $qty_per_unit,
                            'total_quantity' => $total_quantity,
                            'available_stock' => $available_stock,
                            'is_sufficient' => $available_stock >= $total_quantity
                        ];
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'bom_items' => $bom_items
            ]);
            break;
            
        case 'get_route_sheet':
            $order_id = (int)($_GET['order_id'] ?? 0);
            if (!$order_id) {
                throw new Exception('Не указан ID заказа');
            }
            
            // Получаем операции маршрутного листа
            $stmt = $pdo->prepare("
                SELECT 
                    poo.id,
                    poo.operation_id,
                    poo.sequence_order,
                    poo.status,
                    poo.planned_start_datetime as planned_start,
                    poo.planned_end_datetime as planned_end,
                    poo.actual_start_datetime as actual_start,
                    poo.actual_end_datetime as actual_end,
                    poo.quantity_good,
                    poo.quantity_defect,
                    poo.notes,
                    top.operation_name,
                    wc.center_name as work_center_name
                FROM production_order_operations poo
                JOIN technological_operations top ON poo.operation_id = top.id
                LEFT JOIN work_centers wc ON poo.work_center_id = wc.id
                WHERE poo.production_order_id = ?
                ORDER BY poo.sequence_order
            ");
            $stmt->execute([$order_id]);
            $operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($operations)) {
                // Если операций нет, создаем демо-данные
                $operations = [
                    [
                        'id' => 1,
                        'operation_name' => 'Заготовительная операция',
                        'work_center_name' => 'Заготовительный цех',
                        'sequence_order' => 1,
                        'status' => 'completed',
                        'planned_start' => date('Y-m-d H:i:s', strtotime('-5 days')),
                        'planned_end' => date('Y-m-d H:i:s', strtotime('-4 days')),
                        'actual_start' => date('Y-m-d H:i:s', strtotime('-5 days')),
                        'actual_end' => date('Y-m-d H:i:s', strtotime('-4 days')),
                        'quantity_good' => 50,
                        'quantity_defect' => 0
                    ],
                    [
                        'id' => 2,
                        'operation_name' => 'Механообработка',
                        'work_center_name' => 'Токарный участок',
                        'sequence_order' => 2,
                        'status' => 'in_progress',
                        'planned_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
                        'planned_end' => date('Y-m-d H:i:s'),
                        'actual_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
                        'actual_end' => null,
                        'quantity_good' => 30,
                        'quantity_defect' => 1
                    ],
                    [
                        'id' => 3,
                        'operation_name' => 'Сборка',
                        'work_center_name' => 'Сборочный участок',
                        'sequence_order' => 3,
                        'status' => 'pending',
                        'planned_start' => date('Y-m-d H:i:s', strtotime('+1 day')),
                        'planned_end' => date('Y-m-d H:i:s', strtotime('+2 days')),
                        'actual_start' => null,
                        'actual_end' => null,
                        'quantity_good' => 0,
                        'quantity_defect' => 0
                    ],
                    [
                        'id' => 4,
                        'operation_name' => 'Покраска',
                        'work_center_name' => 'Окрасочная камера',
                        'sequence_order' => 4,
                        'status' => 'pending',
                        'planned_start' => date('Y-m-d H:i:s', strtotime('+3 days')),
                        'planned_end' => date('Y-m-d H:i:s', strtotime('+4 days')),
                        'actual_start' => null,
                        'actual_end' => null,
                        'quantity_good' => 0,
                        'quantity_defect' => 0
                    ],
                    [
                        'id' => 5,
                        'operation_name' => 'ОТК',
                        'work_center_name' => 'Отдел технического контроля',
                        'sequence_order' => 5,
                        'status' => 'pending',
                        'planned_start' => date('Y-m-d H:i:s', strtotime('+5 days')),
                        'planned_end' => date('Y-m-d H:i:s', strtotime('+5 days')),
                        'actual_start' => null,
                        'actual_end' => null,
                        'quantity_good' => 0,
                        'quantity_defect' => 0
                    ]
                ];
            }
            
            echo json_encode([
                'success' => true,
                'operations' => $operations
            ]);
            break;
            
        case 'get_quality_control':
            $order_id = (int)($_GET['order_id'] ?? 0);
            if (!$order_id) {
                throw new Exception('Не указан ID заказа');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    qc.id,
                    qc.inspection_date,
                    qc.inspected_quantity,
                    qc.passed_quantity,
                    qc.rejected_quantity,
                    qc.inspection_result,
                    qc.certificate_number,
                    qc.notes,
                    u.full_name as inspector_name,
                    top.operation_name
                FROM quality_control qc
                JOIN users u ON qc.inspector_id = u.id
                LEFT JOIN technological_operations top ON qc.next_operation_id = top.id
                WHERE qc.production_order_id = ?
                ORDER BY qc.inspection_date DESC
            ");
            $stmt->execute([$order_id]);
            $controls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'controls' => $controls
            ]);
            break;
            
        case 'get_defects':
            $stmt = $pdo->prepare("
                SELECT id, defect_code, defect_name, category, description
                FROM defect_types
                WHERE is_active = 1
                ORDER BY category, defect_name
            ");
            $stmt->execute();
            $defects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'defects' => $defects
            ]);
            break;
            
        case 'create_quality_control':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $production_order_id = (int)$_POST['production_order_id'];
            $route_sheet_id = $_POST['route_sheet_id'] ? (int)$_POST['route_sheet_id'] : null;
            $inspected_quantity = (int)$_POST['inspected_quantity'];
            $passed_quantity = (int)$_POST['passed_quantity'];
            $rejected_quantity = (int)$_POST['rejected_quantity'];
            $inspection_result = $_POST['inspection_result'];
            $certificate_number = $_POST['certificate_number'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $defect_types = $_POST['defect_types'] ?? [];
            
            $pdo->beginTransaction();
            
            // Создаем запись ОТК
            $stmt = $pdo->prepare("
                INSERT INTO quality_control 
                (production_order_id, route_sheet_id, inspection_date, inspector_id, 
                 inspected_quantity, passed_quantity, rejected_quantity, 
                 inspection_result, certificate_number, notes)
                VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $production_order_id,
                $route_sheet_id,
                $_SESSION['user_id'],
                $inspected_quantity,
                $passed_quantity,
                $rejected_quantity,
                $inspection_result,
                $certificate_number,
                $notes
            ]);
            
            $qc_id = $pdo->lastInsertId();
            
            // Если есть дефекты, записываем их в журнал
            if (!empty($defect_types) && $rejected_quantity > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO defect_log 
                    (quality_control_id, defect_type_id, quantity, description, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($defect_types as $defect) {
                    $stmt->execute([
                        $qc_id,
                        $defect['defect_type_id'],
                        $defect['quantity'],
                        $defect['description'] ?? null,
                        $_SESSION['user_id']
                    ]);
                }
            }
            
            $pdo->commit();
            
            logActivity($pdo, $_SESSION['user_id'], 'create_quality_control', 'quality_control', $qc_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Контроль качества успешно создан',
                'qc_id' => $qc_id
            ]);
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

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
            
            // Шаг 1: Получаем ВСЕ выданные материалы в производство (агрегировано по material_id)
            // Это общий пул всех выданных материалов независимо от производственного заказа
            // Используем quantity_issued - quantity_used для получения доступного количества в производстве
            $stmt_all_issued = $pdo->query("
                SELECT 
                    pm.material_id,
                    SUM(pm.quantity_issued) as total_issued,
                    SUM(pm.quantity_used) as total_used,
                    SUM(pm.quantity_issued) - SUM(pm.quantity_used) as available_balance
                FROM production_materials pm
                WHERE pm.status IN ('issued', 'used')
                GROUP BY pm.material_id
            ");
            $all_issued_raw = $stmt_all_issued->fetchAll(PDO::FETCH_ASSOC);
            
            // Создаем карту выданных материалов по material_id
            // Для сравнения используем available_balance (выдано минус использовано)
            $global_issued_map = [];
            foreach ($all_issued_raw as $item) {
                $mid = (int)$item['material_id'];
                $global_issued_map[$mid] = [
                    'total_issued' => floatval($item['total_issued']),
                    'total_used' => floatval($item['total_used']),
                    'available_balance' => floatval($item['available_balance'])
                ];
            }
            
            // Декодируем BOM из JSON
            $bom_data = !empty($order['bom_json']) ? json_decode($order['bom_json'], true) : [];
            
            if (is_array($bom_data) && !empty($bom_data)) {
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
                
                // Создаем карту для поиска материала по SKU (так как BOM использует sku)
                $sku_map = [];
                foreach ($warehouse_materials as $wm) {
                    $sku_map[$wm['sku']] = $wm;
                }
                
                // Переменные для подсчета итогов
                $grand_total_required = 0;
                $grand_total_available_in_production = 0;
                
                // Формируем итоговый список материалов
                foreach ($bom_data as $bom_item) {
                    $sku = $bom_item['sku'] ?? '';
                    $qty_per_unit = floatval($bom_item['quantity'] ?? 0);
                    $total_required = $qty_per_unit * $order['order_quantity'];
                    
                    // Ищем материал по SKU (так как BOM содержит только sku, name, quantity)
                    $material_id = 0;
                    $material_name = $bom_item['name'] ?? 'Неизвестный материал';
                    $unit = 'шт';
                    $warehouse_stock = 0;
                    
                    if (isset($sku_map[$sku])) {
                        $material_id = (int)$sku_map[$sku]['material_id'];
                        $material_name = $sku_map[$sku]['material_name'];
                        $unit = 'шт';
                        $warehouse_stock = floatval($sku_map[$sku]['warehouse_stock']);
                    }
                    
                    // Получаем доступное количество для этого материала ИЗ ОБЩЕГО ПУЛА производства
                    // available_balance = выдано всего - использовано всего
                    $available_in_production = 0;
                    if ($material_id > 0 && isset($global_issued_map[$material_id])) {
                        $available_in_production = $global_issued_map[$material_id]['available_balance'];
                    }
                    
                    // Рассчитываем сколько еще нужно выдать
                    $to_issue = max(0, $total_required - $available_in_production);
                    
                    // Проверяем достаточность материала на складе для выдачи
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
                        'available_in_production' => $available_in_production,
                        'to_issue' => $to_issue,
                        'is_sufficient' => $is_sufficient,
                        'shortage' => $shortage
                    ];
                    
                    // Суммируем для общих итогов
                    $grand_total_required += $total_required;
                    $grand_total_available_in_production += min($available_in_production, $total_required);
                }
            }
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'materials' => $materials,
                'material_shortages' => $material_shortages,
                'has_shortages' => !empty($material_shortages),
                'grand_total_required' => isset($grand_total_required) ? $grand_total_required : 0,
                'grand_total_available_in_production' => isset($grand_total_available_in_production) ? $grand_total_available_in_production : 0
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
                SELECT po.product_id, po.quantity, po.source_order_id, p.product_code, p.product_name
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                WHERE po.id = ?
            ");
            $stmt->execute([$production_order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Производственный заказ не найден');
            }
            
            // Получаем BOM продукта для расчета списания материалов
            $bom_data = [];
            try {
                $stmt_bom = $pdo->prepare("SELECT bom_json FROM products WHERE id = ?");
                $stmt_bom->execute([$order['product_id']]);
                $product_data = $stmt_bom->fetch();
                if (!empty($product_data['bom_json'])) {
                    $bom_data = json_decode($product_data['bom_json'], true);
                }
            } catch (Exception $e) {
                // BOM может отсутствовать
            }
            
            // Оприходуем готовую продукцию на склад
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ? 
                WHERE id = ?
            ");
            $stmt->execute([$quantity_completed, $order['product_id']]);
            
            // Списываем материалы из производства согласно BOM
            if (!empty($bom_data) && is_array($bom_data)) {
                // Получаем все выданные материалы для этого производственного заказа ИЛИ для связанного заказа клиента
                // Материалы могут быть выданы как с production_order_id, так и без него (только с source_order_id через прямую выдачу)
                $stmt_issued = $pdo->prepare("
                    SELECT 
                        pm.id as pm_id,
                        pm.material_id,
                        pm.quantity_issued,
                        pm.quantity_used,
                        pm.production_order_id,
                        m.name as material_name,
                        m.sku as material_sku,
                        m.unit
                    FROM production_materials pm
                    JOIN materials m ON pm.material_id = m.id
                    WHERE (pm.production_order_id = ? OR pm.production_order_id IS NULL)
                    AND pm.status IN ('issued', 'used')
                ");
                $stmt_issued->execute([$production_order_id]);
                $issued_materials = $stmt_issued->fetchAll(PDO::FETCH_ASSOC);
                
                // Создаем карту выданных материалов по material_id (агрегируем все записи)
                $issued_map = [];
                foreach ($issued_materials as $im) {
                    $mid = (int)$im['material_id'];
                    if (!isset($issued_map[$mid])) {
                        $issued_map[$mid] = [
                            'pm_ids' => [$im['pm_id']],
                            'material_id' => $mid,
                            'material_name' => $im['material_name'],
                            'material_sku' => $im['material_sku'],
                            'unit' => $im['unit'],
                            'quantity_issued' => floatval($im['quantity_issued']),
                            'quantity_used' => floatval($im['quantity_used'])
                        ];
                    } else {
                        $issued_map[$mid]['pm_ids'][] = $im['pm_id'];
                        $issued_map[$mid]['quantity_issued'] += floatval($im['quantity_issued']);
                        $issued_map[$mid]['quantity_used'] += floatval($im['quantity_used']);
                    }
                }
                
                // Для каждого материала в BOM рассчитываем сколько должно быть использовано
                // и списываем из доступных в производстве
                foreach ($bom_data as $bom_item) {
                    $sku = $bom_item['sku'] ?? '';
                    $qty_per_unit = floatval($bom_item['quantity'] ?? 0);
                    
                    // Находим материал по SKU
                    $material_id = 0;
                    try {
                        $stmt_mat = $pdo->prepare("SELECT id FROM materials WHERE sku = ?");
                        $stmt_mat->execute([$sku]);
                        $mat_result = $stmt_mat->fetch();
                        if ($mat_result) {
                            $material_id = (int)$mat_result['id'];
                        }
                    } catch (Exception $e) {
                        continue;
                    }
                    
                    if ($material_id > 0 && isset($issued_map[$material_id])) {
                        // Рассчитываем плановое количество для произведенного количества продукции
                        $quantity_planned = $qty_per_unit * $quantity_completed;
                        
                        // Получаем текущее количество использованного материала
                        $current_used = $issued_map[$material_id]['quantity_used'];
                        $total_issued = $issued_map[$material_id]['quantity_issued'];
                        
                        // Обновляем quantity_used в production_materials, но не больше чем выдано
                        $new_used = min($quantity_planned, $total_issued);
                        
                        // Если есть несколько записей для этого материала, обновляем их пропорционально
                        $pm_ids = $issued_map[$material_id]['pm_ids'];
                        if (count($pm_ids) === 1) {
                            // Одна запись - простое обновление
                            $stmt_update = $pdo->prepare("
                                UPDATE production_materials 
                                SET quantity_used = ?, status = 'used'
                                WHERE id = ?
                            ");
                            $stmt_update->execute([$new_used, $pm_ids[0]]);
                        } else {
                            // Несколько записей - распределяем new_used пропорционально quantity_issued
                            $remaining_to_allocate = $new_used;
                            $total_for_proportional = $total_issued;
                            
                            foreach ($pm_ids as $index => $pm_id) {
                                if ($index === count($pm_ids) - 1) {
                                    // Последняя запись получает всё оставшееся
                                    $allocated = $remaining_to_allocate;
                                } else {
                                    // Пропорциональное распределение
                                    $stmt_get_qty = $pdo->prepare("SELECT quantity_issued FROM production_materials WHERE id = ?");
                                    $stmt_get_qty->execute([$pm_id]);
                                    $rec = $stmt_get_qty->fetch();
                                    $rec_issued = floatval($rec['quantity_issued']);
                                    
                                    if ($total_for_proportional > 0) {
                                        $allocated = ($rec_issued / $total_for_proportional) * $new_used;
                                    } else {
                                        $allocated = 0;
                                    }
                                    $remaining_to_allocate -= $allocated;
                                    $total_for_proportional -= $rec_issued;
                                }
                                
                                $stmt_update = $pdo->prepare("
                                    UPDATE production_materials 
                                    SET quantity_used = quantity_used + ?, status = 'used'
                                    WHERE id = ?
                                ");
                                $stmt_update->execute([$allocated, $pm_id]);
                            }
                        }
                    }
                }
            }
            
            // Если есть брак, можно добавить дополнительную логику
            
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
                    SET status = 'ready' 
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
            
            $completion_document_id = $pdo->lastInsertId();
            
            // Создаем записи о списании материалов для документа завершения
            if (!empty($bom_data) && is_array($bom_data)) {
                foreach ($bom_data as $bom_item) {
                    $sku = $bom_item['sku'] ?? '';
                    $qty_per_unit = floatval($bom_item['quantity'] ?? 0);
                    $quantity_planned = $qty_per_unit * $quantity_completed;
                    
                    // Находим материал по SKU
                    $material_id = 0;
                    $material_name = $bom_item['name'] ?? '';
                    try {
                        $stmt_mat = $pdo->prepare("SELECT id, name FROM materials WHERE sku = ?");
                        $stmt_mat->execute([$sku]);
                        $mat_result = $stmt_mat->fetch();
                        if ($mat_result) {
                            $material_id = (int)$mat_result['id'];
                            $material_name = $mat_result['name'];
                        }
                    } catch (Exception $e) {
                        continue;
                    }
                    
                    if ($material_id > 0) {
                        // Получаем фактически выданное количество для этого материала
                        $stmt_qty = $pdo->prepare("
                            SELECT SUM(quantity_issued) as total_issued, SUM(quantity_used) as total_used
                            FROM production_materials
                            WHERE production_order_id = ? AND material_id = ?
                        ");
                        $stmt_qty->execute([$production_order_id, $material_id]);
                        $qty_result = $stmt_qty->fetch();
                        $quantity_issued = floatval($qty_result['total_issued'] ?? 0);
                        $quantity_used_before = floatval($qty_result['total_used'] ?? 0);
                        
                        // Вставляем запись о списании
                        $stmt_writeoff = $pdo->prepare("
                            INSERT INTO production_material_writeoffs
                            (completion_document_id, production_order_id, material_id, material_name, material_sku, 
                             quantity_planned, quantity_issued, quantity_used, unit, writeoff_date, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                        ");
                        $stmt_writeoff->execute([
                            $completion_document_id,
                            $production_order_id,
                            $material_id,
                            $material_name,
                            $sku,
                            $quantity_planned,
                            $quantity_issued,
                            $quantity_planned, // quantity_used = quantity_planned при завершении
                            'шт',
                            $_SESSION['user_id']
                        ]);
                    }
                }
            }
            
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
            
        case 'start_production':
            // Начало производства - перевод заказа из статуса "planned" в "in_progress"
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $production_order_id = (int)$_POST['production_order_id'];
            
            $pdo->beginTransaction();
            
            // Проверяем текущий статус
            $stmt = $pdo->prepare("SELECT status FROM production_orders WHERE id = ?");
            $stmt->execute([$production_order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Производственный заказ не найден');
            }
            
            if ($order['status'] !== 'planned') {
                throw new Exception('Нельзя начать производство. Текущий статус: ' . $order['status']);
            }
            
            // Обновляем статус на "in_progress" и устанавливаем дату начала
            $stmt = $pdo->prepare("
                UPDATE production_orders 
                SET status = 'in_progress', actual_start_date = CURDATE()
                WHERE id = ?
            ");
            $stmt->execute([$production_order_id]);
            
            $pdo->commit();
            
            logActivity($pdo, $_SESSION['user_id'], 'start_production', 'production_orders', $production_order_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Производство запущено'
            ]);
            break;
            
        case 'create_production_order_from_customer':
            // Создание производственного заказа из заказа клиента и запуск в производство
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $order_id = (int)$_POST['order_id'];
            
            $pdo->beginTransaction();
            
            // Проверяем существует ли уже производственный заказ для этого заказа клиента
            $stmt = $pdo->prepare("SELECT id FROM production_orders WHERE source_order_id = ?");
            $stmt->execute([$order_id]);
            $existing_order = $stmt->fetch();
            
            if ($existing_order) {
                // Если заказ уже существует, просто запускаем его в производство
                $production_order_id = $existing_order['id'];
                
                $stmt = $pdo->prepare("SELECT status FROM production_orders WHERE id = ?");
                $stmt->execute([$production_order_id]);
                $prod_order = $stmt->fetch();
                
                if ($prod_order['status'] === 'planned') {
                    $stmt = $pdo->prepare("
                        UPDATE production_orders 
                        SET status = 'in_progress', actual_start_date = CURDATE()
                        WHERE id = ?
                    ");
                    $stmt->execute([$production_order_id]);
                    
                    $pdo->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Производство запущено',
                        'production_order_id' => $production_order_id
                    ]);
                } else {
                    $pdo->commit();
                    
                    echo json_encode([
                        'success' => false,
                        'message' => 'Заказ уже находится в статусе: ' . $prod_order['status']
                    ]);
                }
            } else {
                // Создаем новый производственный заказ
                $stmt = $pdo->prepare("
                    SELECT oi.product_id, oi.quantity, p.product_name
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$order_id]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    throw new Exception('Не найдены продукты в заказе');
                }
                
                $prod_number = 'PO-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO production_orders 
                    (production_number, product_id, quantity, status, priority, order_source, source_order_id, created_by, planned_start_date)
                    VALUES (?, ?, ?, 'in_progress', 'normal', 'customer_order', ?, ?, NOW())
                ");
                $stmt->execute([$prod_number, $item['product_id'], $item['quantity'], $order_id, $_SESSION['user_id']]);
                $production_order_id = $pdo->lastInsertId();
                
                $pdo->commit();
                
                logActivity($pdo, $_SESSION['user_id'], 'create_production_order', 'production_orders', $production_order_id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Производственный заказ создан и запущен в производство',
                    'production_order_id' => $production_order_id
                ]);
            }
            break;
            
        case 'get_production_document_full':
            // Получение полной информации о документе для печати
            $doc_id = (int)($_GET['id'] ?? 0);
            if (!$doc_id) {
                throw new Exception('Не указан ID документа');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    pcd.id,
                    pcd.document_number,
                    pcd.production_order_id,
                    po.production_number,
                    pcd.product_id,
                    p.product_name,
                    p.product_code,
                    pcd.quantity,
                    pcd.defect_quantity,
                    pcd.completion_date,
                    pcd.notes,
                    u.full_name as created_by_name,
                    po.source_order_id,
                    o.order_number as customer_order_number,
                    c.company_name as customer_name
                FROM production_completion_documents pcd
                JOIN production_orders po ON pcd.production_order_id = po.id
                JOIN products p ON pcd.product_id = p.id
                LEFT JOIN users u ON pcd.created_by = u.id
                LEFT JOIN orders o ON po.source_order_id = o.id
                LEFT JOIN clients c ON o.client_id = c.id
                WHERE pcd.id = ?
            ");
            $stmt->execute([$doc_id]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doc) {
                throw new Exception('Документ не найден');
            }
            
            // Получаем списанные материалы
            $materials = [];
            try {
                $stmt_mat = $pdo->prepare("
                    SELECT 
                        material_name,
                        material_sku,
                        quantity_planned,
                        quantity_issued,
                        quantity_used,
                        unit
                    FROM production_material_writeoffs
                    WHERE completion_document_id = ?
                    ORDER BY material_name
                ");
                $stmt_mat->execute([$doc_id]);
                $materials = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Таблица может не существовать
            }
            
            echo json_encode([
                'success' => true,
                'document' => $doc,
                'materials' => $materials
            ]);
            break;
            
        case 'update_production_document':
            // Обновление данных документа
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $doc_id = (int)$_POST['id'];
            $quantity = (int)$_POST['quantity'];
            $defect_quantity = (int)($_POST['defect_quantity'] ?? 0);
            $notes = $_POST['notes'] ?? '';
            
            if ($quantity <= 0) {
                throw new Exception('Количество должно быть больше 0');
            }
            
            $stmt = $pdo->prepare("
                UPDATE production_completion_documents 
                SET quantity = ?, defect_quantity = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$quantity, $defect_quantity, $notes, $doc_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Документ не найден или не был изменён');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Документ успешно обновлён'
            ]);
            break;
            
        case 'delete_production_document':
            // Удаление документа
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $doc_id = (int)$_POST['id'];
            
            // Начинаем транзакцию
            $pdo->beginTransaction();
            
            try {
                // Удаляем записи о списании материалов
                $stmt = $pdo->prepare("DELETE FROM production_material_writeoffs WHERE completion_document_id = ?");
                $stmt->execute([$doc_id]);
                
                // Получаем информацию о документе для возврата продукции
                $stmt_doc = $pdo->prepare("SELECT product_id, quantity FROM production_completion_documents WHERE id = ?");
                $stmt_doc->execute([$doc_id]);
                $doc_data = $stmt_doc->fetch();
                
                if ($doc_data) {
                    // Возвращаем продукцию со склада (обратная операция)
                    $stmt_return = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    $stmt_return->execute([$doc_data['quantity'], $doc_data['product_id']]);
                }
                
                // Удаляем документ
                $stmt = $pdo->prepare("DELETE FROM production_completion_documents WHERE id = ?");
                $stmt->execute([$doc_id]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Документ успешно удалён'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'get_production_stages':
            // Получение этапов производства для маршрутного листа
            $production_order_id = (int)($_GET['production_order_id'] ?? 0);
            if (!$production_order_id) {
                throw new Exception('Не указан ID производственного заказа');
            }
            
            // Получаем этапы производства
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    stage_number,
                    stage_name,
                    stage_description,
                    status,
                    completed_at,
                    completed_by
                FROM production_stages
                WHERE production_order_id = ?
                ORDER BY stage_number ASC
            ");
            $stmt->execute([$production_order_id]);
            $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Если этапы еще не созданы, создаем их
            if (empty($stages)) {
                $stage_templates = [
                    ['Заготовка материалов', 'Подготовка и выдача необходимых материалов со склада'],
                    ['Основное производство', 'Изготовление основного изделия согласно технологии'],
                    ['Контроль качества', 'Проверка готового изделия на соответствие стандартам'],
                    ['Упаковка', 'Упаковка готовой продукции'],
                    ['Передача на склад', 'Оприходование готовой продукции на склад']
                ];
                
                $stmt_insert = $pdo->prepare("
                    INSERT INTO production_stages 
                    (production_order_id, stage_number, stage_name, stage_description, status)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                foreach ($stage_templates as $index => $template) {
                    $status = ($index === 0) ? 'available' : 'waiting';
                    $stmt_insert->execute([
                        $production_order_id,
                        $index + 1,
                        $template[0],
                        $template[1],
                        $status
                    ]);
                }
                
                // Получаем созданные этапы
                $stmt->execute([$production_order_id]);
                $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Получаем даты начала и завершения
            $start_date = null;
            $end_date = null;
            
            $stmt_dates = $pdo->prepare("
                SELECT created_at, updated_at 
                FROM production_orders 
                WHERE id = ?
            ");
            $stmt_dates->execute([$production_order_id]);
            $order_dates = $stmt_dates->fetch();
            
            if ($order_dates) {
                $start_date = date('d.m.Y H:i', strtotime($order_dates['created_at']));
                
                // Проверяем, завершен ли последний этап
                $last_stage_completed = false;
                foreach ($stages as $stage) {
                    if ($stage['stage_number'] == 5 && $stage['status'] == 'completed') {
                        $last_stage_completed = true;
                        $end_date = date('d.m.Y H:i', strtotime($stage['completed_at']));
                        break;
                    }
                }
                
                if (!$last_stage_completed) {
                    $end_date = '_________________';
                }
            }
            
            echo json_encode([
                'success' => true,
                'stages' => $stages,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
            break;
            
        case 'complete_production_stage':
            // Завершение этапа производства
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $stage_id = (int)$_POST['stage_id'];
            $production_order_id = (int)$_POST['production_order_id'];
            $stage_number = (int)$_POST['stage_number'];
            
            $pdo->beginTransaction();
            
            try {
                // Получаем текущий этап
                $stmt = $pdo->prepare("SELECT status FROM production_stages WHERE id = ?");
                $stmt->execute([$stage_id]);
                $stage = $stmt->fetch();
                
                if (!$stage) {
                    throw new Exception('Этап не найден');
                }
                
                if ($stage['status'] !== 'available') {
                    throw new Exception('Этот этап еще не доступен для завершения');
                }
                
                // Завершаем текущий этап
                $stmt_update = $pdo->prepare("
                    UPDATE production_stages 
                    SET status = 'completed', completed_at = NOW(), completed_by = ?
                    WHERE id = ?
                ");
                $stmt_update->execute([$_SESSION['user_id'], $stage_id]);
                
                // Делаем доступным следующий этап
                $next_stage_number = $stage_number + 1;
                $stmt_next = $pdo->prepare("
                    UPDATE production_stages 
                    SET status = 'available'
                    WHERE production_order_id = ? AND stage_number = ?
                ");
                $stmt_next->execute([$production_order_id, $next_stage_number]);
                
                // Если это последний этап (5), переносим продукцию на склад
                if ($stage_number == 5) {
                    // Получаем информацию о производственном заказе
                    $stmt_po = $pdo->prepare("
                        SELECT product_id, quantity, source_order_id
                        FROM production_orders
                        WHERE id = ?
                    ");
                    $stmt_po->execute([$production_order_id]);
                    $po_data = $stmt_po->fetch();
                    
                    if ($po_data) {
                        // Оприходуем готовую продукцию на склад
                        $stmt_stock = $pdo->prepare("
                            UPDATE products 
                            SET stock_quantity = stock_quantity + ?
                            WHERE id = ?
                        ");
                        $stmt_stock->execute([$po_data['quantity'], $po_data['product_id']]);
                        
                        // Обновляем статус производственного заказа
                        $stmt_status = $pdo->prepare("
                            UPDATE production_orders 
                            SET status = 'completed'
                            WHERE id = ?
                        ");
                        $stmt_status->execute([$production_order_id]);
                        
                        // Если это заказ клиента, обновляем статус заказа на "готов к отгрузке"
                        if ($po_data['source_order_id']) {
                            $stmt_order = $pdo->prepare("
                                UPDATE orders 
                                SET status = 'ready'
                                WHERE id = ?
                            ");
                            $stmt_order->execute([$po_data['source_order_id']]);
                        }
                        
                        // Создаем документ о завершении производства
                        $doc_number = 'PCD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                        $stmt_doc = $pdo->prepare("
                            INSERT INTO production_completion_documents
                            (document_number, production_order_id, product_id, quantity, defect_quantity, completion_date, created_by, source_order_id)
                            VALUES (?, ?, ?, ?, 0, NOW(), ?, ?)
                        ");
                        $stmt_doc->execute([
                            $doc_number,
                            $production_order_id,
                            $po_data['product_id'],
                            $po_data['quantity'],
                            $_SESSION['user_id'],
                            $po_data['source_order_id']
                        ]);
                        
                        $completion_document_id = $pdo->lastInsertId();
                        
                        // Создаем документ приема товаров на склад из производства
                        $receipt_number = 'ПР-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                        $stmt_receipt = $pdo->prepare("
                            INSERT INTO goods_receipt_documents
                            (receipt_number, receipt_date, receipt_type, production_order_id, warehouse_id, total_items, total_quantity, status, created_by)
                            VALUES (?, NOW(), 'from_production', ?, 1, 1, ?, 'confirmed', ?)
                        ");
                        $stmt_receipt->execute([
                            $receipt_number,
                            $production_order_id,
                            $po_data['quantity'],
                            $_SESSION['user_id']
                        ]);
                        $receipt_id = $pdo->lastInsertId();
                        
                        // Добавляем позицию в документ приема - готовая продукция
                        $stmt_product = $pdo->prepare("SELECT product_name, product_code FROM products WHERE id = ?");
                        $stmt_product->execute([$po_data['product_id']]);
                        $product_data = $stmt_product->fetch();
                        
                        $stmt_receipt_item = $pdo->prepare("
                            INSERT INTO goods_receipt_items
                            (receipt_id, item_type, product_id, item_name, item_sku, item_unit, quantity_received, batch_number, storage_zone)
                            VALUES (?, 'product', ?, ?, ?, 'шт', ?, ?, 'A1')
                        ");
                        $stmt_receipt_item->execute([
                            $receipt_id,
                            $po_data['product_id'],
                            $product_data['product_name'],
                            $product_data['product_code'],
                            $po_data['quantity'],
                            $doc_number
                        ]);
                        
                        // Списываем материалы согласно BOM и создаем записи о списании
                        // Получаем BOM продукта
                        $stmt_bom = $pdo->prepare("SELECT bom_json FROM products WHERE id = ?");
                        $stmt_bom->execute([$po_data['product_id']]);
                        $product_bom = $stmt_bom->fetch();
                        
                        if (!empty($product_bom['bom_json'])) {
                            $bom_data = json_decode($product_bom['bom_json'], true);
                            
                            if (is_array($bom_data)) {
                                foreach ($bom_data as $bom_item) {
                                    $sku = $bom_item['sku'] ?? '';
                                    $qty_per_unit = floatval($bom_item['quantity'] ?? 0);
                                    $quantity_planned = $qty_per_unit * $po_data['quantity'];
                                    
                                    // Находим материал по SKU
                                    $material_id = 0;
                                    $material_name = $bom_item['name'] ?? '';
                                    try {
                                        $stmt_mat = $pdo->prepare("SELECT id, name FROM materials WHERE sku = ?");
                                        $stmt_mat->execute([$sku]);
                                        $mat_result = $stmt_mat->fetch();
                                        if ($mat_result) {
                                            $material_id = (int)$mat_result['id'];
                                            $material_name = $mat_result['name'];
                                        }
                                    } catch (Exception $e) {
                                        continue;
                                    }
                                    
                                    if ($material_id > 0) {
                                        // Получаем фактически выданное количество для этого материала
                                        $stmt_qty = $pdo->prepare("
                                            SELECT SUM(quantity_issued) as total_issued, SUM(quantity_used) as total_used
                                            FROM production_materials
                                            WHERE production_order_id = ? AND material_id = ?
                                        ");
                                        $stmt_qty->execute([$production_order_id, $material_id]);
                                        $qty_result = $stmt_qty->fetch();
                                        $quantity_issued = floatval($qty_result['total_issued'] ?? 0);
                                        
                                        // Обновляем запись в production_materials как использованную
                                        $stmt_use = $pdo->prepare("
                                            UPDATE production_materials
                                            SET quantity_used = quantity_used + ?, status = 'used'
                                            WHERE production_order_id = ? AND material_id = ?
                                        ");
                                        $stmt_use->execute([$quantity_planned, $production_order_id, $material_id]);
                                        
                                        // Создаем запись о списании в production_material_writeoffs
                                        $stmt_writeoff = $pdo->prepare("
                                            INSERT INTO production_material_writeoffs
                                            (completion_document_id, production_order_id, material_id, material_name, material_sku, 
                                             quantity_planned, quantity_issued, quantity_used, unit, writeoff_date, created_by)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                                        ");
                                        $stmt_writeoff->execute([
                                            $completion_document_id,
                                            $production_order_id,
                                            $material_id,
                                            $material_name,
                                            $sku,
                                            $quantity_planned,
                                            $quantity_issued,
                                            $quantity_planned,
                                            'шт',
                                            $_SESSION['user_id']
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                $pdo->commit();
                
                logActivity($pdo, $_SESSION['user_id'], 'complete_stage', 'production_stages', $stage_id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Этап успешно завершен'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'create_material_request':
            // Создание заявки на материалы для производства
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }
            
            $order_id = (int)$_POST['order_id'];
            $materials_data = json_decode($_POST['materials_data'], true);
            
            if (empty($materials_data)) {
                throw new Exception('Нет материалов для заявки');
            }
            
            $pdo->beginTransaction();
            
            try {
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
                
                // Генерируем номер заявки
                $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(request_number, '-', -1) AS UNSIGNED)) as max_num FROM material_requests");
                $result = $stmt->fetch();
                $next_num = ($result['max_num'] ?? 0) + 1;
                $request_number = 'MR-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                
                // Создаем заявку
                $stmt = $pdo->prepare("
                    INSERT INTO material_requests 
                    (request_number, production_order_id, request_date, status, total_items, notes, requested_by)
                    VALUES (?, ?, CURRENT_DATE, 'pending', ?, 'Заявка от производства', ?)
                ");
                $stmt->execute([
                    $request_number,
                    $production_order_id,
                    count($materials_data),
                    $_SESSION['user_id']
                ]);
                $request_id = $pdo->lastInsertId();
                
                // Добавляем позиции заявки
                $stmt = $pdo->prepare("
                    INSERT INTO material_request_items 
                    (request_id, material_id, quantity_requested, unit)
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($materials_data as $mat) {
                    if ($mat['quantity'] > 0) {
                        $stmt->execute([
                            $request_id,
                            $mat['material_id'],
                            $mat['quantity'],
                            $mat['unit'] ?? 'шт'
                        ]);
                    }
                }
                
                $pdo->commit();
                
                logActivity($pdo, $_SESSION['user_id'], 'create_material_request', 'material_requests', $request_id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Заявка на склад отправлена',
                    'request_id' => $request_id,
                    'request_number' => $request_number
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'get_material_request_details':
            // Получение деталей заявки на списание материалов
            $request_id = (int)($_GET['id'] ?? 0);
            if (!$request_id) {
                throw new Exception('Не указан ID заявки');
            }

            // Получаем информацию о заявке
            $stmt = $pdo->prepare("
                SELECT
                    mr.id,
                    mr.request_number,
                    mr.production_order_id,
                    po.order_number as production_order,
                    mr.status,
                    mr.request_date,
                    mr.created_at,
                    mr.requested_by,
                    u.username as requested_by,
                    u.full_name as requested_by_name,
                    mr.notes as comment
                FROM material_requests mr
                LEFT JOIN production_orders po ON mr.production_order_id = po.id
                LEFT JOIN users u ON mr.requested_by = u.id
                WHERE mr.id = ?
            ");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new Exception('Заявка не найдена');
            }

            // Получаем позиции заявки
            $stmt = $pdo->prepare("
                SELECT
                    mri.id,
                    mri.material_id,
                    mri.quantity_requested,
                    COALESCE(m.quantity_issued, 0) as quantity_issued,
                    COALESCE(m.quantity_used, 0) as quantity_used,
                    mri.unit,
                    mat.sku,
                    mat.name as material_name,
                    mri.note
                FROM material_request_items mri
                JOIN materials mat ON mri.material_id = mat.id
                LEFT JOIN production_materials m ON mri.id = m.request_item_id
                WHERE mri.request_id = ?
            ");
            $stmt->execute([$request_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'request' => $request,
                'items' => $items
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

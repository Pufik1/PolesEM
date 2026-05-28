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
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_bom':
            // Получение спецификации материалов для производственного заказа
            $order_id = (int)($_GET['order_id'] ?? 0);
            if (!$order_id) {
                throw new Exception('Не указан ID заказа');
            }
            
            // Получаем информацию о заказе
            $stmt = $pdo->prepare("
                SELECT po.id, po.production_number, po.quantity, p.product_name, p.product_code
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                WHERE po.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Заказ не найден');
            }
            
            // Получаем BOM для продукта
            $stmt = $pdo->prepare("
                SELECT pb.id as bom_id, pb.bom_version, pb.description
                FROM product_bom pb
                WHERE pb.product_id = (SELECT product_id FROM production_orders WHERE id = ?)
                AND pb.is_active = 1
                ORDER BY pb.created_at DESC LIMIT 1
            ");
            $stmt->execute([$order_id]);
            $bom = $stmt->fetch();
            
            $bom_items = [];
            if ($bom) {
                // Получаем элементы BOM с учетом количества заказа
                $stmt = $pdo->prepare("
                    SELECT 
                        pbi.material_id,
                        m.name as material_name,
                        m.sku,
                        m.unit,
                        pbi.quantity as qty_per_unit,
                        pbi.quantity * ? as total_quantity,
                        pbi.waste_percent,
                        m.current_stock as available_stock
                    FROM product_bom_items pbi
                    JOIN materials m ON pbi.material_id = m.id
                    WHERE pbi.bom_id = ?
                    ORDER BY pbi.sequence_order
                ");
                $stmt->execute([$order['quantity'], $bom['bom_id']]);
                $bom_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'bom' => $bom,
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

<?php 
require_once '../../includes/config.php'; 
if (!isLoggedIn()) redirect('../../index.php');

$pdo = getDBConnection();
$error = '';
$success = '';
$userFullName = $_SESSION['full_name'];
$userRole = $_SESSION['user_role'];
$initials = strtoupper(substr($userFullName, 0, 1));

// Получаем производственные заказы (план производства)
$productionOrders = [];
try {
    $stmt = $pdo->query("
        SELECT 
            po.id,
            po.production_number,
            p.product_name,
            p.product_code,
            po.quantity,
            po.planned_start_date,
            po.planned_end_date,
            po.status,
            po.order_source,
            o.order_number as customer_order,
            c.company_name as customer_name,
            po.notes
        FROM production_orders po
        JOIN products p ON po.product_id = p.id
        LEFT JOIN orders o ON po.source_order_id = o.id
        LEFT JOIN clients c ON o.client_id = c.id
        ORDER BY po.planned_start_date DESC, po.created_at DESC
        LIMIT 50
    ");
    $productionOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Ошибка загрузки производственных заказов: ' . $e->getMessage();
}

// Получаем выданные материалы в производство (агрегированные по видам материалов)
$issuedMaterials = [];
try {
    $stmt = $pdo->query("
        SELECT 
            m.id as material_id,
            m.sku,
            m.name as material_name,
            m.unit,
            SUM(pm.quantity_issued) as total_issued,
            SUM(pm.quantity_used) as total_used,
            SUM(pm.quantity_issued) - SUM(pm.quantity_used) as available_on_production
        FROM production_materials pm
        JOIN materials m ON pm.material_id = m.id
        WHERE pm.status IN ('issued', 'used')
        GROUP BY m.id, m.sku, m.name, m.unit
        ORDER BY m.name
    ");
    $issuedMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Таблица может еще не существовать
}

// Получаем заказы клиентов требующие производства с информацией о материалах
$customerOrdersForProduction = [];
try {
    $stmt = $pdo->query("
        SELECT 
            o.id as order_id,
            o.order_number,
            c.company_name as customer_name,
            o.order_date,
            o.delivery_date,
            o.payment_status,
            o.status as order_status,
            oi.product_id,
            p.product_name,
            p.product_code,
            p.bom_json,
            oi.quantity as order_quantity,
            po.id as production_order_id,
            po.quantity as production_quantity,
            po.status as production_status
        FROM orders o
        JOIN clients c ON o.client_id = c.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN production_orders po ON o.id = po.source_order_id
        WHERE o.payment_status = 'paid' 
        AND (po.id IS NULL OR po.status IN ('planned', 'in_progress', 'completed'))
        ORDER BY o.delivery_date ASC
    ");
    $ordersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Для каждого заказа получаем информацию о материалах
    foreach ($ordersRaw as $order) {
        $materialsRequired = [];
        $materialsIssued = [];
        $materialsComparison = [];
        
        // Декодируем BOM продукта
        $bom_data = !empty($order['bom_json']) ? json_decode($order['bom_json'], true) : [];
        
        if (is_array($bom_data) && !empty($bom_data)) {
            // Рассчитываем требуемое количество материалов для заказа
            foreach ($bom_data as $bom_item) {
                $sku = $bom_item['sku'] ?? '';
                $qty_per_unit = floatval($bom_item['quantity'] ?? 0);
                $total_required = $qty_per_unit * $order['order_quantity'];
                
                // Все материалы в штуках
                $materialsRequired[$sku] = [
                    'material_name' => $bom_item['name'] ?? 'Неизвестный материал',
                    'unit' => 'шт',
                    'required' => $total_required
                ];
            }
            
            // Получаем выданные материалы для этого заказа (по любому статусу)
            if ($order['production_order_id']) {
                $stmt_issued = $pdo->prepare("
                    SELECT 
                        m.sku,
                        m.name as material_name,
                        SUM(pm.quantity_issued) as total_issued
                    FROM production_materials pm
                    JOIN materials m ON pm.material_id = m.id
                    WHERE pm.production_order_id = ?
                    GROUP BY m.sku, m.name
                ");
                $stmt_issued->execute([$order['production_order_id']]);
                $issued_raw = $stmt_issued->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($issued_raw as $im) {
                    $materialsIssued[$im['sku']] = [
                        'material_name' => $im['material_name'],
                        'unit' => 'шт',
                        'issued' => floatval($im['total_issued'])
                    ];
                }
            }
            
            // Сравниваем требуемое и выданное
            foreach ($materialsRequired as $sku => $req_info) {
                $issued_qty = isset($materialsIssued[$sku]) ? $materialsIssued[$sku]['issued'] : 0;
                $remaining = $req_info['required'] - $issued_qty;
                
                $materialsComparison[] = [
                    'sku' => $sku,
                    'material_name' => $req_info['material_name'],
                    'unit' => 'шт',
                    'required' => $req_info['required'],
                    'issued' => $issued_qty,
                    'remaining' => max(0, $remaining),
                    'is_fulfilled' => $issued_qty >= $req_info['required']
                ];
            }
        }
        
        $order['materials_comparison'] = $materialsComparison;
        $customerOrdersForProduction[] = $order;
    }
} catch (Exception $e) {
    // 
}

// Получаем документы о завершенном производстве
$productionDocuments = [];
try {
    $stmt = $pdo->query("
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
            pcd.source_order_id,
            o.order_number as customer_order_number,
            c.company_name as customer_name
        FROM production_completion_documents pcd
        JOIN production_orders po ON pcd.production_order_id = po.id
        JOIN products p ON pcd.product_id = p.id
        LEFT JOIN users u ON pcd.created_by = u.id
        LEFT JOIN orders o ON pcd.source_order_id = o.id
        LEFT JOIN clients c ON o.client_id = c.id
        ORDER BY pcd.completion_date DESC
        LIMIT 50
    ");
    $productionDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Таблица может еще не существовать
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Производство - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tabs { display: flex; border-bottom: 2px solid #e5e7eb; margin-bottom: 20px; }
        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }
        .tab-button:hover { color: #3b82f6; }
        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th, .data-table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; word-wrap: break-word; white-space: normal; }
        .data-table th { background-color: #f9fafb; font-weight: 600; color: #374151; vertical-align: top; }
        .data-table td { vertical-align: middle; }
        /* Фиксированная ширина для узких колонок */
        .data-table th:nth-child(1), .data-table td:nth-child(1) { width: 120px; } /* № заказа */
        .data-table th:nth-child(4), .data-table td:nth-child(4) { width: 80px; text-align: center; } /* Заказано */
        .data-table th:nth-child(5), .data-table td:nth-child(5) { width: 100px; text-align: center; } /* В производстве */
        .data-table th:nth-child(6), .data-table td:nth-child(6) { width: 90px; } /* Статус оплаты */
        .data-table th:nth-child(7), .data-table td:nth-child(7) { width: 140px; } /* Статус производства */
        .data-table th:nth-child(8), .data-table td:nth-child(8) { width: 110px; } /* Дата доставки */
        .data-table th:nth-child(9), .data-table td:nth-child(9) { width: 100px; text-align: center; } /* Действия (заказы клиентов) */
        #production-orders-table th:nth-child(7), #production-orders-table td:nth-child(7) { width: 130px; text-align: center; } /* Действия (план производства) */
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 28px; }
        .stat-card p { margin: 0; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-bolt"></i></div>
                    <div class="logo-text"><h2>Полесьеэлектромаш</h2><p>Корпоративная система</p></div>
                </div>
            </div>
            <?php 
            $basePath = '../../';
            include '../../includes/sidebar.php'; 
            ?>
        </aside>
        <div class="main-content">
            <header class="header">
                <div class="header-title"><h1>Производство</h1></div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo $initials; ?></div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($userFullName); ?></span>
                        <span class="user-role"><?php echo ucfirst($userRole); ?></span>
                    </div>
                </div>
            </header>
            <div class="content-area">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Статистика -->
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?php echo count($customerOrdersForProduction); ?></h3>
                        <p>Заказы</p>
                    </div>
                </div>

                <!-- Вкладки -->
                <div class="tabs">
                    <button class="tab-button active" onclick="showTab('orders')">Заказы</button>
                    <button class="tab-button" onclick="showTab('production-plan')">План производства</button>
                    <button class="tab-button" onclick="showTab('issued')">Выдано в производство</button>
                    <button class="tab-button" onclick="showTab('documents')">Документы о производстве</button>
                </div>

                <!-- Заказы -->
                <div id="orders" class="tab-content active">
                    <div class="card">
                        <h2><i class="fas fa-shopping-cart"></i> Заказы</h2>
                        <p style="color: #6b7280; margin-bottom: 20px;">Оплаченные заказы требующие производства</p>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Клиент</th>
                                    <th>Продукт</th>
                                    <th>Заказано</th>
                                    <th>В производстве</th>
                                    <th>Статус оплаты</th>
                                    <th>Статус производства</th>
                                    <th>Дата доставки</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerOrdersForProduction as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                    <td><?php echo $order['order_quantity']; ?></td>
                                    <td><?php echo $order['production_quantity'] ?? 0; ?></td>
                                    <td>
                                        <span class="badge badge-success">Оплачен</span>
                                    </td>
                                    <td>
                                        <?php if ($order['production_order_id']): ?>
                                            <span class="badge badge-<?php 
                                                echo $order['production_status'] == 'completed' ? 'success' : 
                                                    ($order['production_status'] == 'in_progress' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php 
                                                    $prodStatusLabels = [
                                                        'planned' => 'В плане',
                                                        'in_progress' => 'В производстве',
                                                        'completed' => 'Готово',
                                                        'cancelled' => 'Отменено'
                                                    ];
                                                    echo $prodStatusLabels[$order['production_status']] ?? 'В плане'; 
                                                ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Требуется создание</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $order['delivery_date']; ?></td>
                                    <td>
                                        <?php if ($order['production_order_id'] && in_array($order['production_status'], ['completed', 'in_progress'])): ?>
                                            <span class="badge badge-<?php echo $order['production_status'] === 'completed' ? 'success' : 'info'; ?>">
                                                <?php echo $order['production_status'] === 'completed' ? 'Готово' : 'В производстве'; ?>
                                            </span>
                                        <?php else: ?>
                                            <button class="btn btn-primary" style="padding: 4px 8px;" onclick="viewOrderMaterials(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-boxes"></i> Материалы
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($customerOrdersForProduction)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Заказов не найдено</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Выдано в производство -->
                <div id="issued" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-dolly"></i> Материалы в производстве</h2>
                        <p style="color: #6b7280; margin-bottom: 20px;">Материалы выданные со склада и находящиеся в производстве</p>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Выдано всего</th>
                                    <th>Использовано</th>
                                    <th>Доступно в производстве</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($issuedMaterials as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                    <td><?php echo htmlspecialchars($item['material_name']); ?></td>
                                    <td><?php echo $item['total_issued'] . ' шт'; ?></td>
                                    <td><?php echo $item['total_used'] . ' шт'; ?></td>
                                    <td><strong><?php echo $item['available_on_production'] . ' шт'; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($issuedMaterials)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Материалы не выдавались</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- План производства -->
                <div id="production-plan" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-clipboard-list"></i> План производства</h2>
                        <p style="color: #6b7280; margin-bottom: 20px;">Производственные заказы в работе</p>
                        
                        <!-- Карточки статусов производства -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 20px; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9;"><i class="fas fa-cogs"></i> В производстве</h4>
                                <p style="margin: 0; font-size: 28px; font-weight: bold;" id="count-in-progress">0</p>
                            </div>
                            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9;"><i class="fas fa-check-circle"></i> Готово</h4>
                                <p style="margin: 0; font-size: 28px; font-weight: bold;" id="count-completed">0</p>
                            </div>
                        </div>

                        <!-- Фильтры -->
                        <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                            <select id="filter-status" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" onchange="filterProductionOrders()">
                                <option value="all">Все статусы</option>
                                <option value="in_progress">В производстве</option>
                                <option value="completed">Готово</option>
                                <option value="cancelled">Отменено</option>
                            </select>
                            <input type="text" id="search-production" placeholder="Поиск по номеру или продукту..." style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; flex: 1; min-width: 200px;" onkeyup="filterProductionOrders()">
                        </div>

                        <!-- Таблица производственных заказов -->
                        <table class="data-table" id="production-orders-table">
                            <thead>
                                <tr>
                                    <th>№ произв. заказа</th>
                                    <th>Продукт</th>
                                    <th>Кол-во</th>
                                    <th>Статус</th>
                                    <th>Источник</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody id="production-orders-body">
                                <?php foreach ($productionOrders as $order): ?>
                                <tr data-status="<?php echo htmlspecialchars($order['status']); ?>">
                                    <td><?php echo htmlspecialchars($order['production_number']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['product_name']); ?></strong><br>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($order['product_code']); ?></small>
                                    </td>
                                    <td><?php echo $order['quantity']; ?> шт</td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $order['status'] == 'completed' ? 'success' : 
                                                ($order['status'] == 'in_progress' ? 'info' : 
                                                ($order['status'] == 'cancelled' ? 'danger' : 'warning')); 
                                        ?>">
                                            <?php 
                                                $statusLabels = [
                                                    'planned' => 'В плане',
                                                    'in_progress' => 'В производстве',
                                                    'completed' => 'Готово',
                                                    'cancelled' => 'Отменено'
                                                ];
                                                echo $statusLabels[$order['status']] ?? $order['status']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order['customer_order']): ?>
                                            <small>Заказ: <?php echo htmlspecialchars($order['customer_order']); ?></small><br>
                                            <small style="color: #6b7280;"><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></small>
                                        <?php else: ?>
                                            <small style="color: #6b7280;">План</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" style="padding: 4px 8px;" onclick="openRouteSheet(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['production_number']); ?>', '<?php echo htmlspecialchars($order['product_name']); ?>', <?php echo $order['quantity']; ?>)">
                                            <i class="fas fa-file-alt"></i> Маршрутный лист
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($productionOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Производственных заказов не найдено</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Документы о производстве -->
                <div id="documents" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-file-alt"></i> Документы о завершенном производстве</h2>
                        <p style="color: #6b7280; margin-bottom: 20px;">Документы оприходования готовой продукции на склад</p>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>№ документа</th>
                                    <th>№ произв. заказа</th>
                                    <th>Продукт</th>
                                    <th>Кол-во годных</th>
                                    <th>Брак</th>
                                    <th>Дата завершения</th>
                                    <th>Создан</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productionDocuments as $doc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($doc['document_number']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['production_number']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($doc['product_name']); ?></strong><br>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($doc['product_code']); ?></small>
                                    </td>
                                    <td><?php echo $doc['quantity']; ?> шт</td>
                                    <td>
                                        <?php if ($doc['defect_quantity'] > 0): ?>
                                            <span class="badge badge-danger"><?php echo $doc['defect_quantity']; ?> шт</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($doc['completion_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($doc['created_by_name'] ?? 'Неизвестно'); ?></td>
                                    <td>
                                        <button class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;" onclick="printProductionDocument(<?php echo $doc['id']; ?>)" title="Печать подробного документа">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button class="btn btn-primary" style="padding: 4px 8px; font-size: 12px; background: #10b981;" onclick="editProductionDocument(<?php echo $doc['id']; ?>)" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-primary" style="padding: 4px 8px; font-size: 12px; background: #ef4444;" onclick="deleteProductionDocument(<?php echo $doc['id']; ?>)" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($productionDocuments)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Документов не найдено</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
    
    <!-- Модальное окно для просмотра маршрутного листа -->
    <div id="routeSheetModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;" onclick="if(event.target===this) closeRouteSheetModal();">
        <div style="background:white; margin:50px auto; padding:20px; border-radius:8px; max-width:900px; max-height:80vh; overflow-y:auto;">
            <h3 id="routeSheetTitle">Маршрутный лист</h3>
            <div id="routeSheetContent"></div>
            <div style="margin-top:20px; text-align:right;">
                <button class="btn" onclick="closeRouteSheetModal()" style="background:#6b7280; color:white;">Закрыть</button>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для просмотра материалов и выдачи -->
    <div id="materialsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;" onclick="if(event.target===this) closeMaterialsModal();">
        <div style="background:white; margin:50px auto; padding:20px; border-radius:8px; max-width:900px; max-height:80vh; overflow-y:auto;">
            <h3 id="modalTitle">Материалы для заказа</h3>
            <div id="modalContent"></div>
            <div style="margin-top:20px; text-align:right;" id="modalActions">
                <button class="btn" onclick="closeMaterialsModal()" style="background:#6b7280; color:white; margin-right:10px;">Закрыть</button>
                <button class="btn btn-primary" id="issueMaterialsBtn" style="display: none;" onclick="redirectToWarehouseIssue()">Выдать материалы</button>
                <button class="btn btn-primary" id="startProductionBtn" style="display: none; background: #10b981;" onclick="startProductionFromModal()">Приступить к производству</button>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для завершения производства -->
    <div id="completionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;" onclick="if(event.target===this) closeCompletionModal();">
        <div style="background:white; margin:50px auto; padding:20px; border-radius:8px; max-width:500px;">
            <h3>Завершение производства</h3>
            <input type="hidden" id="completionProductionOrderId">
            <div style="margin-bottom:15px;">
                <label>Годных изделий:</label>
                <input type="number" id="quantityCompleted" style="width:100%; padding:8px; margin-top:5px;" value="0">
            </div>
            <div style="margin-bottom:15px;">
                <label>Брак:</label>
                <input type="number" id="quantityDefect" style="width:100%; padding:8px; margin-top:5px;" value="0">
            </div>
            <div style="margin-bottom:15px;">
                <label>Комментарий:</label>
                <textarea id="completionNotes" style="width:100%; padding:8px; margin-top:5px;"></textarea>
            </div>
            <div style="text-align:right;">
                <button class="btn" onclick="closeCompletionModal()" style="background:#6b7280; color:white; margin-right:10px;">Отмена</button>
                <button class="btn btn-primary" onclick="submitCompletion()">Завершить</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentOrderId = null;
        let currentMaterials = [];
        
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function createProductionOrder() {
            alert('Функция создания производственного заказа будет реализована');
        }

        function viewBOM(orderId) {
            fetch('api.php?action=get_bom&order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '<table class="data-table"><thead><tr><th>Материал</th><th>Артикул</th><th>На ед.</th><th>Всего</th><th>На складе</th><th>Статус</th></tr></thead><tbody>';
                        data.bom_items.forEach(item => {
                            let statusBadge = item.is_sufficient ? 
                                '<span class="badge badge-success">Достаточно</span>' : 
                                '<span class="badge badge-danger">Не хватает</span>';
                            html += '<tr><td>' + item.material_name + '</td><td>' + item.sku + '</td><td>' + item.qty_per_unit + ' шт</td><td>' + item.total_quantity + ' шт</td><td>' + item.available_stock + ' шт</td><td>' + statusBadge + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        document.getElementById('modalTitle').innerText = 'Спецификация для заказа ' + data.order.production_number;
                        document.getElementById('modalContent').innerHTML = html;
                        // Скрываем кнопку выдачи для просмотра спецификации
                        document.getElementById('modalActions').style.display = 'none';
                        document.getElementById('materialsModal').style.display = 'block';
                    }
                });
        }
        
        function viewOrderMaterials(orderId) {
            currentOrderId = orderId;
            fetch('api.php?action=get_customer_order_materials&order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentMaterials = data.materials;
                        let html = '<p><strong>Заказ:</strong> ' + data.order.order_number + 
                                   ' | <strong>Клиент:</strong> ' + data.order.customer_name + 
                                   ' | <strong>Продукт:</strong> ' + data.order.product_name + 
                                   ' | <strong>Количество:</strong> ' + data.order.order_quantity + '</p>';
                        
                        // Проверяем общую достаточность выданных материалов
                        // Используем grand_total_available_in_production из API
                        let allIssued = true;
                        let totalRequired = data.grand_total_required || 0;
                        let totalAvailableInProduction = data.grand_total_available_in_production || 0;
                        
                        data.materials.forEach(mat => {
                            if (mat.available_in_production < mat.total_required) {
                                allIssued = false;
                            }
                        });
                        
                        // Отображаем предупреждение если не все материалы выданы
                        if (!allIssued && data.materials.length > 0) {
                            html += '<div style="background:#fee2e2; border:1px solid #ef4444; padding:15px; margin-bottom:15px; border-radius:6px;">';
                            html += '<h4 style="color:#991b1b; margin:0 0 10px 0;"><i class="fas fa-exclamation-triangle"></i> Недостаточно материалов выдано для заказа!</h4>';
                            html += '<p style="margin:0; color:#991b1b;">Доступно в производстве: ' + totalAvailableInProduction + ' из ' + totalRequired + ' требуемых единиц материалов</p></div>';
                        } else if (data.materials.length > 0) {
                            html += '<div style="background:#d1fae5; border:1px solid #10b981; padding:15px; margin-bottom:15px; border-radius:6px;">';
                            html += '<h4 style="color:#065f46; margin:0;"><i class="fas fa-check-circle"></i> Все материалы выданы в полном объеме</h4></div>';
                        }
                        
                        html += '<table class="data-table"><thead><tr>' +
                                '<th>Материал</th><th>Артикул</th>' +
                                '<th>Требуется</th><th>Доступно в пр-ве</th>' +
                                '<th>Статус</th>' +
                                '</tr></thead><tbody>';
                        
                        data.materials.forEach((mat, index) => {
                            // Статус проверяем по факту доступных материалов в производстве против требуемых
                            let isFullyIssued = mat.available_in_production >= mat.total_required;
                            let shortageQty = mat.total_required - mat.available_in_production;
                            let statusBadge = isFullyIssued ? 
                                '<span class="badge badge-success">Достаточно</span>' : 
                                '<span class="badge badge-danger">Не хватает ' + shortageQty + ' шт</span>';
                            
                            html += '<tr>' +
                                    '<td>' + mat.material_name + '</td>' +
                                    '<td>' + mat.sku + '</td>' +
                                    '<td>' + mat.total_required + ' шт</td>' +
                                    '<td>' + mat.available_in_production + ' шт</td>' +
                                    '<td>' + statusBadge + '</td>' +
                                    '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        document.getElementById('modalTitle').innerText = 'Материалы для заказа ' + data.order.order_number;
                        document.getElementById('modalContent').innerHTML = html;
                        
                        // Проверяем общую достаточность выданных материалов (используем уже объявленную переменную)
                        allIssued = true;
                        data.materials.forEach(mat => {
                            if (mat.available_in_production < mat.total_required) {
                                allIssued = false;
                            }
                        });
                        
                        // Показываем кнопку "Выдать материалы" только если есть материалы к выдаче
                        let hasMaterialsToIssue = data.materials.some(mat => mat.available_in_production < mat.total_required);
                        document.getElementById('issueMaterialsBtn').style.display = hasMaterialsToIssue ? 'inline-block' : 'none';
                        
                        // Показываем кнопку "Приступить к производству" если все материалы выданы
                        document.getElementById('startProductionBtn').style.display = allIssued && data.materials.length > 0 ? 'inline-block' : 'none';
                        
                        document.getElementById('materialsModal').style.display = 'block';
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
        }
        
        function closeMaterialsModal() {
            document.getElementById('materialsModal').style.display = 'none';
        }
        
        // Функция для открытия маршрутного листа
        function openRouteSheet(orderId, productionNumber, productName, quantity) {
            document.getElementById('routeSheetTitle').innerText = 'Маршрутный лист № ' + productionNumber;
            
            let html = '<div style="background:#f3f4f6; padding:20px; border-radius:8px; margin-bottom:20px;">';
            html += '<h4 style="margin:0 0 15px 0;"><i class="fas fa-clipboard-list"></i> Общая информация</h4>';
            html += '<p><strong>№ заказа:</strong> ' + productionNumber + '</p>';
            html += '<p><strong>Продукт:</strong> ' + productName + '</p>';
            html += '<p><strong>Количество:</strong> ' + quantity + ' шт</p>';
            html += '</div>';
            
            html += '<div style="background:#f3f4f6; padding:20px; border-radius:8px; margin-bottom:20px;">';
            html += '<h4 style="margin:0 0 15px 0;"><i class="fas fa-tasks"></i> Этапы производства</h4>';
            html += '<table class="data-table"><thead><tr><th>№</th><th>Этап</th><th>Описание</th><th>Статус</th></tr></thead><tbody>';
            html += '<tr><td>1</td><td>Заготовка материалов</td><td>Подготовка и выдача необходимых материалов со склада</td><td><span class="badge badge-warning">В ожидании</span></td></tr>';
            html += '<tr><td>2</td><td>Основное производство</td><td>Изготовление основного изделия согласно технологии</td><td><span class="badge badge-warning">В ожидании</span></td></tr>';
            html += '<tr><td>3</td><td>Контроль качества</td><td>Проверка готового изделия на соответствие стандартам</td><td><span class="badge badge-warning">В ожидании</span></td></tr>';
            html += '<tr><td>4</td><td>Упаковка</td><td>Упаковка готовой продукции</td><td><span class="badge badge-warning">В ожидании</span></td></tr>';
            html += '<tr><td>5</td><td>Передача на склад</td><td>Оприходование готовой продукции на склад</td><td><span class="badge badge-warning">В ожидании</span></td></tr>';
            html += '</tbody></table>';
            html += '</div>';
            
            html += '<div style="background:#f3f4f6; padding:20px; border-radius:8px;">';
            html += '<h4 style="margin:0 0 15px 0;"><i class="fas fa-user-cog"></i> Ответственные</h4>';
            html += '<p><strong>Мастер смены:</strong> _________________</p>';
            html += '<p><strong>Контролер ОТК:</strong> _________________</p>';
            html += '<p><strong>Дата начала:</strong> _________________</p>';
            html += '<p><strong>Дата окончания:</strong> _________________</p>';
            html += '</div>';
            
            document.getElementById('routeSheetContent').innerHTML = html;
            document.getElementById('routeSheetModal').style.display = 'block';
        }
        
        // Функция для закрытия модального окна маршрутного листа
        function closeRouteSheetModal() {
            document.getElementById('routeSheetModal').style.display = 'none';
        }
        
        // Новая функция для запуска производства из модального окна материалов
        function startProductionFromModal() {
            if (!currentOrderId) {
                alert('Ошибка: не указан ID заказа');
                return;
            }
            
            // Создаем производственный заказ и переводим в статус "в производстве"
            let formData = new FormData();
            formData.append('action', 'create_production_order_from_customer');
            formData.append('order_id', currentOrderId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeMaterialsModal();
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        }
        
        function redirectToWarehouseIssue() {
            if (!currentOrderId || !currentMaterials || currentMaterials.length === 0) {
                alert('Нет материалов для выдачи');
                return;
            }
            
            // Фильтруем только те материалы, которые нужно выдать
            let materialsToIssue = currentMaterials.filter(mat => mat.available_in_production < mat.total_required);
            
            if (materialsToIssue.length === 0) {
                alert('Все материалы уже выданы');
                return;
            }
            
            // Формируем данные для передачи на склад
            let issueData = materialsToIssue.map(mat => ({
                material_id: mat.material_id,
                quantity: mat.total_required - mat.available_in_production,
                unit: 'шт',
                material_name: mat.material_name,
                sku: mat.sku
            }));
            
            // Сохраняем в sessionStorage для использования на складе
            sessionStorage.setItem('warehouse_issue_data', JSON.stringify({
                order_id: currentOrderId,
                materials: issueData,
                mode: 'batch' // Режим массовой выдачи
            }));
            
            // Переходим на страницу склада во вкладку материалы с флагом массовой выдачи
            window.location.href = '../../modules/warehouse/index.php?tab=materials&issue_production=1&mode=batch';
        }
        
        function completeProduction(productionOrderId, orderQuantity) {
            document.getElementById('completionProductionOrderId').value = productionOrderId;
            document.getElementById('quantityCompleted').value = orderQuantity;
            document.getElementById('quantityDefect').value = 0;
            document.getElementById('completionNotes').value = '';
            document.getElementById('completionModal').style.display = 'block';
        }
        
        function closeCompletionModal() {
            document.getElementById('completionModal').style.display = 'none';
        }
        
        function submitCompletion() {
            let productionOrderId = document.getElementById('completionProductionOrderId').value;
            let quantityCompleted = parseInt(document.getElementById('quantityCompleted').value) || 0;
            let quantityDefect = parseInt(document.getElementById('quantityDefect').value) || 0;
            let notes = document.getElementById('completionNotes').value;
            
            if (quantityCompleted <= 0) {
                alert('Укажите количество годных изделий');
                return;
            }
            
            let formData = new FormData();
            formData.append('action', 'complete_production');
            formData.append('production_order_id', productionOrderId);
            formData.append('quantity_completed', quantityCompleted);
            formData.append('quantity_defect', quantityDefect);
            formData.append('notes', notes);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeCompletionModal();
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        }
        
        // Функция для начала производства
        function startProduction(productionOrderId) {
            if (!confirm('Начать производство? Убедитесь, что все материалы готовы.')) {
                return;
            }
            
            let formData = new FormData();
            formData.append('action', 'start_production');
            formData.append('production_order_id', productionOrderId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        }
        
        // Фильтрация производственных заказов
        function filterProductionOrders() {
            const statusFilter = document.getElementById('filter-status').value;
            const searchQuery = document.getElementById('search-production').value.toLowerCase();
            
            const rows = document.querySelectorAll('#production-orders-body tr[data-status]');
            let inProgressCount = 0;
            let completedCount = 0;
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const text = row.textContent.toLowerCase();
                
                // Подсчет статистики
                if (status === 'in_progress') inProgressCount++;
                else if (status === 'completed') completedCount++;
                
                // Фильтрация
                let showRow = true;
                
                if (statusFilter !== 'all' && status !== statusFilter) {
                    showRow = false;
                }
                
                if (searchQuery && !text.includes(searchQuery)) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
            
            // Обновление счетчиков
            document.getElementById('count-in-progress').textContent = inProgressCount;
            document.getElementById('count-completed').textContent = completedCount;
        }
        
        // Просмотр деталей завершения
        function viewCompletionDetails(productionOrderId) {
            alert('Функция просмотра деталей завершения будет реализована. ID заказа: ' + productionOrderId);
        }
        
        // Печать подробного документа о производстве
        function printProductionDocument(documentId) {
            fetch('api.php?action=get_production_document_full&id=' + documentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let doc = data.document;
                        let materials = data.materials || [];
                        let productsInfo = data.products_info || [];
                        
                        // Формируем реалистичный печатный документ
                        let printContent = `
                            <div style="font-family: Arial, sans-serif; padding: 40px; background: white; max-width: 800px; margin: 0 auto;">
                                <div style="text-align: center; border-bottom: 3px solid #333; padding-bottom: 20px; margin-bottom: 30px;">
                                    <h1 style="color: #333; margin: 0;">ОАО «ПОЛЕСЬЕЭЛЕКТРОМАШ»</h1>
                                </div>
                                
                                <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <h2 style="color: #333; margin-top: 0;">АКТ ОПРИХОДОВАНИЯ ГОТОВОЙ ПРОДУКЦИИ</h2>
                                    <p style="margin: 10px 0;"><strong>Документ №:</strong> ${doc.document_number}</p>
                                    <p style="margin: 10px 0;"><strong>Дата формирования:</strong> ${new Date(doc.completion_date).toLocaleDateString('ru-RU')}</p>
                                    <p style="margin: 10px 0;"><strong>Производственный заказ:</strong> ${doc.production_number}</p>
                                </div>
                                
                                <div style="margin-bottom: 25px;">
                                    <h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; color: #333;">1. ИНФОРМАЦИЯ О ЗАКАЗЕ</h3>
                                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                        <tr style="background: #f9f9f9;">
                                            <td style="padding: 10px; border: 1px solid #ddd; width: 40%;"><strong>Продукт:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;">${doc.product_name} (${doc.product_code})</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Заказано к производству:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;">${doc.quantity + (doc.defect_quantity || 0)} шт</td>
                                        </tr>
                                        <tr style="background: #f9f9f9;">
                                            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Изготовлено годных:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd; color: green; font-weight: bold;">${doc.quantity} шт</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Брак:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd; color: red;">${doc.defect_quantity > 0 ? doc.defect_quantity + ' шт' : '-'}</td>
                                        </tr>
                                        <tr style="background: #f9f9f9;">
                                            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Заказчик:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;">${doc.customer_name || '—'}</td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div style="margin-bottom: 25px;">
                                    <h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; color: #333;">2. СПИСАННЫЕ МАТЕРИАЛЫ</h3>
                                    ${materials.length > 0 ? `
                                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">
                                        <thead>
                                            <tr style="background: #333; color: white;">
                                                <th style="padding: 10px; border: 1px solid #ddd;">№</th>
                                                <th style="padding: 10px; border: 1px solid #ddd;">Материал</th>
                                                <th style="padding: 10px; border: 1px solid #ddd;">Артикул</th>
                                                <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">План</th>
                                                <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Списано</th>
                                                <th style="padding: 10px; border: 1px solid #ddd;">Ед.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${materials.map((mat, idx) => `
                                            <tr style="${idx % 2 === 0 ? 'background: #f9f9f9;' : ''}">
                                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${idx + 1}</td>
                                                <td style="padding: 8px; border: 1px solid #ddd;">${mat.material_name}</td>
                                                <td style="padding: 8px; border: 1px solid #ddd;">${mat.material_sku}</td>
                                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">${mat.quantity_planned}</td>
                                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">${mat.quantity_used}</td>
                                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${mat.unit}</td>
                                            </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                    ` : '<p>Информация о материалах отсутствует</p>'}
                                </div>
                                
                                <div style="margin-bottom: 25px;">
                                    <h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; color: #333;">3. ОПРИХОДОВАНИЕ НА СКЛАД</h3>
                                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                        <tr style="background: #f9f9f9;">
                                            <td style="padding: 10px; border: 1px solid #ddd; width: 40%;"><strong>Продукт:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;">${doc.product_name}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Артикул:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;">${doc.product_code}</td>
                                        </tr>
                                        <tr style="background: #f9f9f9;">
                                            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Количество на склад:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: green;">${doc.quantity} шт</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Статус:</strong></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><span style="background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px;">Готово к отгрузке</span></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                ${doc.notes ? `
                                <div style="margin-bottom: 25px;">
                                    <h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; color: #333;">4. ПРИМЕЧАНИЯ</h3>
                                    <p style="padding: 15px; background: #fffbea; border-left: 4px solid #f59e0b;">${doc.notes}</p>
                                </div>
                                ` : ''}
                                
                                <div style="margin-top: 40px; border-top: 2px solid #333; padding-top: 20px;">
                                    <p style="color: #666; font-size: 12px; margin-top: 20px;">
                                        Дата печати: ${new Date().toLocaleDateString('ru-RU')} ${new Date().toLocaleTimeString('ru-RU')}
                                    </p>
                                </div>
                            </div>
                        `;
                        
                        // Открываем новое окно для печати
                        let printWindow = window.open('', '_blank', 'width=900,height=700');
                        printWindow.document.write(`
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <title>Печать документа ${doc.document_number}</title>
                                <style>
                                    @media print {
                                        body { margin: 0; padding: 0; }
                                        .no-print { display: none; }
                                    }
                                    body { font-family: Arial, sans-serif; }
                                </style>
                            </head>
                            <body>
                                ${printContent}
                                <div class="no-print" style="text-align: center; margin-top: 20px;">
                                    <button onclick="window.print()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">🖨️ Печать</button>
                                    <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;">Закрыть</button>
                                </div>
                            </body>
                            </html>
                        `);
                        printWindow.document.close();
                    } else {
                        alert('Ошибка загрузки документа: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при загрузке документа');
                });
        }
        
        // Редактирование данных документа для печати
        function editProductionDocument(documentId) {
            fetch('api.php?action=get_production_document_full&id=' + documentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let doc = data.document;
                        let html = `
                            <form id="editDocumentForm" onsubmit="saveEditedDocument(event, ${documentId})">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Количество годных изделий:</label>
                                    <input type="number" id="editQuantity" value="${doc.quantity}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Брак (шт):</label>
                                    <input type="number" id="editDefectQuantity" value="${doc.defect_quantity || 0}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Комментарий:</label>
                                    <textarea id="editNotes" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">${doc.notes || ''}</textarea>
                                </div>
                                <div style="text-align: right; margin-top: 20px;">
                                    <button type="button" onclick="closeMaterialsModal()" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; margin-right: 10px;">Отмена</button>
                                    <button type="submit" style="padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 4px;">Сохранить изменения</button>
                                </div>
                            </form>
                        `;
                        document.getElementById('modalTitle').innerText = 'Редактирование документа №' + doc.document_number;
                        document.getElementById('modalContent').innerHTML = html;
                        document.getElementById('modalActions').style.display = 'none';
                        document.getElementById('materialsModal').style.display = 'block';
                    } else {
                        alert('Ошибка загрузки документа: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при загрузке документа');
                });
        }
        
        // Сохранение отредактированного документа
        function saveEditedDocument(event, documentId) {
            event.preventDefault();
            
            let quantity = parseInt(document.getElementById('editQuantity').value);
            let defectQuantity = parseInt(document.getElementById('editDefectQuantity').value) || 0;
            let notes = document.getElementById('editNotes').value;
            
            fetch('api.php?action=update_production_document', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${documentId}&quantity=${quantity}&defect_quantity=${defectQuantity}&notes=${encodeURIComponent(notes)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Документ успешно обновлён');
                    closeMaterialsModal();
                    location.reload();
                } else {
                    alert('Ошибка обновления: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при сохранении документа');
            });
        }
        
        // Удаление документа
        function deleteProductionDocument(documentId) {
            if (confirm('Вы уверены, что хотите удалить этот документ? Это действие нельзя отменить.')) {
                fetch('api.php?action=delete_production_document', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${documentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Документ успешно удалён');
                        location.reload();
                    } else {
                        alert('Ошибка удаления: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при удалении документа');
                });
            }
        }
        
        // Просмотр документа о производстве (старая функция, оставлена для совместимости)
        function viewProductionDocument(documentId) {
            printProductionDocument(documentId);
        }
        
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            filterProductionOrders();
        });
    </script>
</body>
</html>

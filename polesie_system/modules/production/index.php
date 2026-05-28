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
            po.priority,
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
        AND (po.id IS NULL OR po.status IN ('planned', 'in_progress'))
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
                
                $materialsRequired[$sku] = [
                    'material_name' => $bom_item['name'] ?? 'Неизвестный материал',
                    'unit' => $bom_item['unit'] ?? 'шт',
                    'required' => $total_required
                ];
            }
            
            // Получаем выданные материалы для этого заказа
            if ($order['production_order_id']) {
                $stmt_issued = $pdo->prepare("
                    SELECT 
                        m.sku,
                        m.name as material_name,
                        m.unit,
                        SUM(pm.quantity_issued) as total_issued
                    FROM production_materials pm
                    JOIN materials m ON pm.material_id = m.id
                    WHERE pm.production_order_id = ? AND pm.status IN ('issued', 'used')
                    GROUP BY m.sku, m.name, m.unit
                ");
                $stmt_issued->execute([$order['production_order_id']]);
                $issued_raw = $stmt_issued->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($issued_raw as $im) {
                    $materialsIssued[$im['sku']] = [
                        'material_name' => $im['material_name'],
                        'unit' => $im['unit'],
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
                    'unit' => $req_info['unit'],
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
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .data-table th { background-color: #f9fafb; font-weight: 600; color: #374151; }
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
                    <div class="stat-card">
                        <h3><?php echo count($productionOrders); ?></h3>
                        <p>Производственных заказов</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?php echo count($customerOrdersForProduction); ?></h3>
                        <p>Заказов в производстве</p>
                    </div>
                </div>

                <!-- Вкладки -->
                <div class="tabs">
                    <button class="tab-button active" onclick="showTab('plan')">План производства</button>
                    <button class="tab-button" onclick="showTab('issued')">Выдано в производство</button>
                    <button class="tab-button" onclick="showTab('orders')">Заказы клиентов</button>
                </div>

                <!-- План производства -->
                <div id="plan" class="tab-content active">
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2><i class="fas fa-calendar-alt"></i> План производства</h2>
                            <button class="btn btn-primary" onclick="createProductionOrder()">
                                <i class="fas fa-plus"></i> Создать заказ
                            </button>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Продукт</th>
                                    <th>Количество</th>
                                    <th>План начало</th>
                                    <th>План окончание</th>
                                    <th>Статус</th>
                                    <th>Приоритет</th>
                                    <th>Источник</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productionOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['production_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                    <td><?php echo $order['quantity']; ?></td>
                                    <td><?php echo $order['planned_start_date']; ?></td>
                                    <td><?php echo $order['planned_end_date']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $order['status'] == 'completed' ? 'success' : 
                                                ($order['status'] == 'in_progress' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php 
                                                $statusLabels = [
                                                    'planned' => 'Запланировано',
                                                    'in_progress' => 'В работе',
                                                    'completed' => 'Завершено',
                                                    'cancelled' => 'Отменено',
                                                    'on_hold' => 'На паузе'
                                                ];
                                                echo $statusLabels[$order['status']] ?? $order['status']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $order['priority'] == 'urgent' ? 'danger' : 
                                                ($order['priority'] == 'high' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php 
                                                $priorityLabels = [
                                                    'low' => 'Низкий',
                                                    'normal' => 'Обычный',
                                                    'high' => 'Высокий',
                                                    'urgent' => 'Срочно'
                                                ];
                                                echo $priorityLabels[$order['priority']] ?? $order['priority']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['order_source'] == 'customer_order' ? 'Заказ клиента' : 'На склад'; ?></td>
                                    <td>
                                        <button class="btn btn-primary" style="padding: 4px 8px;" onclick="viewBOM(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-list"></i> Спецификация
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($productionOrders)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Производственные заказы не найдены</td>
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

                <!-- Заказы клиентов -->
                <div id="orders" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-shopping-cart"></i> Заказы клиентов в производстве</h2>
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
                                                        'planned' => 'Запланировано',
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
                                        <button class="btn btn-primary" style="padding: 4px 8px;" onclick="viewOrderMaterials(<?php echo $order['order_id']; ?>)">
                                            <i class="fas fa-boxes"></i> Материалы
                                        </button>
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

            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
    
    <!-- Модальное окно для просмотра материалов и выдачи -->
    <div id="materialsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; margin:50px auto; padding:20px; border-radius:8px; max-width:900px; max-height:80vh; overflow-y:auto;">
            <h3 id="modalTitle">Материалы для заказа</h3>
            <div id="modalContent"></div>
            <div style="margin-top:20px; text-align:right;" id="modalActions">
                <button class="btn" onclick="closeMaterialsModal()" style="background:#6b7280; color:white; margin-right:10px;">Закрыть</button>
                <button class="btn btn-primary" onclick="issueMaterialsToProduction()">Выдать материалы</button>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для завершения производства -->
    <div id="completionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
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
                            html += '<tr><td>' + item.material_name + '</td><td>' + item.sku + '</td><td>' + item.qty_per_unit + ' ' + item.unit + '</td><td>' + item.total_quantity + ' ' + item.unit + '</td><td>' + item.available_stock + ' ' + item.unit + '</td><td>' + statusBadge + '</td></tr>';
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
                        let allIssued = true;
                        let totalRequired = 0;
                        let totalIssued = 0;
                        data.materials.forEach(mat => {
                            totalRequired += mat.total_required;
                            totalIssued += mat.already_issued;
                            if (mat.already_issued < mat.total_required) {
                                allIssued = false;
                            }
                        });
                        
                        // Отображаем предупреждение если не все материалы выданы
                        if (!allIssued && data.materials.length > 0) {
                            html += '<div style="background:#fee2e2; border:1px solid #ef4444; padding:15px; margin-bottom:15px; border-radius:6px;">';
                            html += '<h4 style="color:#991b1b; margin:0 0 10px 0;"><i class="fas fa-exclamation-triangle"></i> Недостаточно материалов выдано для заказа!</h4>';
                            html += '<p style="margin:0; color:#991b1b;">Выдано: ' + totalIssued + ' из ' + totalRequired + ' требуемых единиц материалов</p></div>';
                        } else if (data.materials.length > 0) {
                            html += '<div style="background:#d1fae5; border:1px solid #10b981; padding:15px; margin-bottom:15px; border-radius:6px;">';
                            html += '<h4 style="color:#065f46; margin:0;"><i class="fas fa-check-circle"></i> Все материалы выданы в полном объеме</h4></div>';
                        }
                        
                        html += '<table class="data-table"><thead><tr>' +
                                '<th>Материал</th><th>Артикул</th>' +
                                '<th>Требуется</th><th>Выдано</th>' +
                                '<th>Статус</th>' +
                                '</tr></thead><tbody>';
                        
                        data.materials.forEach((mat, index) => {
                            // Статус проверяем по факту выданных материалов против требуемых
                            let isFullyIssued = mat.already_issued >= mat.total_required;
                            let statusBadge = isFullyIssued ? 
                                '<span class="badge badge-success">Достаточно</span>' : 
                                '<span class="badge badge-danger">Не хватает ' + (mat.total_required - mat.already_issued) + ' ' + mat.unit + '</span>';
                            
                            html += '<tr>' +
                                    '<td>' + mat.material_name + '</td>' +
                                    '<td>' + mat.sku + '</td>' +
                                    '<td>' + mat.total_required + ' ' + mat.unit + '</td>' +
                                    '<td>' + mat.already_issued + ' ' + mat.unit + '</td>' +
                                    '<td>' + statusBadge + '</td>' +
                                    '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        document.getElementById('modalTitle').innerText = 'Материалы для заказа ' + data.order.order_number;
                        document.getElementById('modalContent').innerHTML = html;
                        document.getElementById('materialsModal').style.display = 'block';
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
        }
        
        function closeMaterialsModal() {
            document.getElementById('materialsModal').style.display = 'none';
        }
        
        function issueMaterialsToProduction() {
            if (!currentOrderId) return;
            
            let materialsData = [];
            currentMaterials.forEach((mat, index) => {
                let qtyInput = document.getElementById('mat_qty_' + index);
                let qty = parseFloat(qtyInput.value) || 0;
                if (qty > 0) {
                    materialsData.push({
                        material_id: mat.material_id,
                        quantity: qty
                    });
                }
            });
            
            if (materialsData.length === 0) {
                alert('Выберите материалы для выдачи');
                return;
            }
            
            let formData = new FormData();
            formData.append('action', 'issue_materials');
            formData.append('order_id', currentOrderId);
            formData.append('materials_data', JSON.stringify(materialsData));
            
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
    </script>
</body>
</html>

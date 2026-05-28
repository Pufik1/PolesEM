<?php 
require_once '../../includes/config.php'; 
if (!isLoggedIn()) redirect('../../index.php');

$pdo = getDBConnection();
$error = '';
$success = '';
$userFullName = $_SESSION['full_name'];
$userRole = $_SESSION['user_role'];
$initials = strtoupper(substr($userFullName, 0, 1));

// Получаем материалы доступные для производства
$materialsForProduction = [];
try {
    $stmt = $pdo->query("
        SELECT m.id, m.sku, m.name, m.current_stock, m.unit, mc.category_name
        FROM materials m
        LEFT JOIN material_categories mc ON m.category_id = mc.id
        WHERE m.is_active = 1 AND m.current_stock > 0
        ORDER BY mc.category_name, m.name
    ");
    $materialsForProduction = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Ошибка загрузки материалов: ' . $e->getMessage();
}

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

// Получаем выданные материалы в производство
$issuedMaterials = [];
try {
    $stmt = $pdo->query("
        SELECT 
            pm.id,
            pm.production_order_id,
            po.production_number,
            m.name as material_name,
            m.sku,
            pm.quantity_issued,
            pm.quantity_used,
            pm.unit,
            pm.issue_date,
            pm.status,
            u.full_name as issued_by
        FROM production_materials pm
        JOIN materials m ON pm.material_id = m.id
        JOIN production_orders po ON pm.production_order_id = po.id
        LEFT JOIN users u ON pm.created_by = u.id
        ORDER BY pm.issue_date DESC
        LIMIT 50
    ");
    $issuedMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Таблица может еще не существовать
}

// Получаем заказы клиентов требующие производства
$customerOrdersForProduction = [];
try {
    $stmt = $pdo->query("
        SELECT 
            o.id,
            o.order_number,
            c.company_name as customer_name,
            o.order_date,
            o.delivery_date,
            o.payment_status,
            o.status as order_status,
            oi.product_id,
            p.product_name,
            p.product_code,
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
    $customerOrdersForProduction = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo count($materialsForProduction); ?></h3>
                        <p>Материалов на складе</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?php echo count($customerOrdersForProduction); ?></h3>
                        <p>Заказов в производстве</p>
                    </div>
                </div>

                <!-- Вкладки -->
                <div class="tabs">
                    <button class="tab-button active" onclick="showTab('plan')">План производства</button>
                    <button class="tab-button" onclick="showTab('materials')">Материалы</button>
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

                <!-- Материалы для производства -->
                <div id="materials" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-boxes"></i> Материалы на складе</h2>
                        <p style="color: #6b7280; margin-bottom: 20px;">Материалы доступные для выдачи в производство</p>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Категория</th>
                                    <th>Остаток</th>
                                    <th>Ед. изм.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materialsForProduction as $material): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['sku']); ?></td>
                                    <td><?php echo htmlspecialchars($material['name']); ?></td>
                                    <td><?php echo htmlspecialchars($material['category_name'] ?? 'Без категории'); ?></td>
                                    <td><strong><?php echo $material['current_stock']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($material['unit']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($materialsForProduction)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Материалы не найдены</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Выдано в производство -->
                <div id="issued" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-dolly"></i> Выдано в производство</h2>
                        <p style="color: #6b7280; margin-bottom: 20px;">Материалы выданные со склада в производство</p>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>№ производственного заказа</th>
                                    <th>Материал</th>
                                    <th>Выдано</th>
                                    <th>Использовано</th>
                                    <th>Дата выдачи</th>
                                    <th>Статус</th>
                                    <th>Выдал</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($issuedMaterials as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['production_number']); ?></td>
                                    <td><?php echo htmlspecialchars($item['material_name']); ?> (<?php echo htmlspecialchars($item['sku']); ?>)</td>
                                    <td><?php echo $item['quantity_issued'] . ' ' . $item['unit']; ?></td>
                                    <td><?php echo $item['quantity_used'] . ' ' . $item['unit']; ?></td>
                                    <td><?php echo $item['issue_date']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $item['status'] == 'used' ? 'success' : 
                                                ($item['status'] == 'returned' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo $item['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['issued_by'] ?? 'Н/Д'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($issuedMaterials)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Материалы не выдавались</td>
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
                                            <span class="badge badge-info">
                                                <?php echo $order['production_status'] ?? 'В плане'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Требуется создание</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $order['delivery_date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($customerOrdersForProduction)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Заказов не найдено</td>
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
    <script>
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
            // Показываем спецификацию материалов для заказа
            fetch('api.php?action=get_bom&order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Показать модальное окно со спецификацией
                        alert('Спецификация: ' + JSON.stringify(data.bom_items));
                    }
                });
        }
    </script>
</body>
</html>

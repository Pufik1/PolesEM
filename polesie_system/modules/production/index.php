<?php 
require_once '../../includes/config.php'; 
if (!isLoggedIn()) redirect('../../index.php');

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$messageType = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['action']) {
            case 'create_production_order':
                $production_number = 'PO-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $product_id = (int)$_POST['product_id'];
                $quantity = (int)$_POST['quantity'];
                $planned_start_date = $_POST['planned_start_date'];
                $planned_end_date = $_POST['planned_end_date'];
                $priority = $_POST['priority'];
                $order_source = $_POST['order_source'];
                $work_center_id = $_POST['work_center_id'] ?: null;
                $responsible_user_id = $_POST['responsible_user_id'] ?: null;
                $notes = $_POST['notes'];
                
                $stmt = $pdo->prepare("INSERT INTO production_orders 
                    (production_number, product_id, quantity, planned_start_date, planned_end_date, 
                     priority, order_source, work_center_id, responsible_user_id, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$production_number, $product_id, $quantity, $planned_start_date, 
                               $planned_end_date, $priority, $order_source, $work_center_id, 
                               $responsible_user_id, $notes, $_SESSION['user_id']]);
                
                $message = 'План выпуска продукции успешно создан!';
                $messageType = 'success';
                logActivity($pdo, $_SESSION['user_id'], 'create_production_order', 'production_orders', 
                           $pdo->lastInsertId(), null, $_POST);
                break;
                
            case 'update_status':
                $production_order_id = (int)$_POST['production_order_id'];
                $status = $_POST['status'];
                
                $stmt = $pdo->prepare("UPDATE production_orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $production_order_id]);
                
                $message = 'Статус обновлен!';
                $messageType = 'success';
                break;
                
            case 'complete_operation':
                $operation_id = (int)$_POST['operation_id'];
                $quantity_good = (int)$_POST['quantity_good'];
                $quantity_defect = (int)$_POST['quantity_defect'];
                $defect_reason = $_POST['defect_reason'] ?? null;
                
                $stmt = $pdo->prepare("UPDATE production_order_operations 
                    SET quantity_good = ?, quantity_defect = ?, defect_reason = ?, 
                        status = 'completed', completed_at = NOW(), completed_by = ? 
                    WHERE id = ?");
                $stmt->execute([$quantity_good, $quantity_defect, $defect_reason, $_SESSION['user_id'], $operation_id]);
                
                $message = 'Операция завершена!';
                $messageType = 'success';
                break;
        }
    } catch (PDOException $e) {
        $message = 'Ошибка: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Получение данных для отображения
$products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name")->fetchAll();
$work_centers = $pdo->query("SELECT * FROM work_centers WHERE is_active = 1 ORDER BY center_name")->fetchAll();
$users = $pdo->query("SELECT id, full_name FROM users WHERE is_active = 1 AND role_id IN (4,5) ORDER BY full_name")->fetchAll();

// Текущие производственные заказы
$current_orders = $pdo->query("
    SELECT po.*, p.product_name, p.product_code, wc.center_name, u.full_name as responsible_name,
           DATEDIFF(po.planned_end_date, CURDATE()) as days_remaining
    FROM production_orders po
    JOIN products p ON po.product_id = p.id
    LEFT JOIN work_centers wc ON po.work_center_id = wc.id
    LEFT JOIN users u ON po.responsible_user_id = u.id
    WHERE po.status IN ('planned', 'in_progress')
    ORDER BY 
        FIELD(po.priority, 'urgent', 'high', 'normal', 'low'),
        po.planned_start_date
")->fetchAll();

// Статистика производства
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as planned_count,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM production_orders
    WHERE YEAR(created_at) = YEAR(CURDATE())
")->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Производство - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .production-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-secondary);
            border-radius: 8px 8px 0 0;
            transition: var(--transition);
        }
        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .kanban-board {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .kanban-column {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
        .kanban-column h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid;
        }
        .kanban-column.planned h3 { border-color: #3498db; }
        .kanban-column.in_progress h3 { border-color: #f39c12; }
        .kanban-column.completed h3 { border-color: #2ecc71; }
        .kanban-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: var(--transition);
        }
        .kanban-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .priority-urgent { background: #fdedec; color: #e74c3c; }
        .priority-high { background: #fef9e7; color: #f39c12; }
        .priority-normal { background: #ebf5fb; color: #3498db; }
        .priority-low { background: #eafaf1; color: #2ecc71; }
        .progress-bar {
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            transition: width 0.3s ease;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        .info-item label {
            font-size: 12px;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 3px;
        }
        .info-item span {
            font-weight: 600;
            color: var(--text-primary);
        }
        .btn-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 999;
        }
        .btn-float:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .route-sheet {
            margin-top: 20px;
        }
        .route-step {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            position: relative;
        }
        .route-step::before {
            content: attr(data-step);
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .step-status {
            margin-left: auto;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .step-pending { background: #ecf0f1; color: #7f8c8d; }
        .step-in_progress { background: #fef9e7; color: #f39c12; }
        .step-completed { background: #eafaf1; color: #2ecc71; }
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
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                    </div>
                </div>
            </header>
            <div class="content-area">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_orders']; ?></h3>
                            <p>Всего заказов</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['planned_count']; ?></h3>
                            <p>В плане</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['in_progress_count']; ?></h3>
                            <p>В работе</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['completed_count']; ?></h3>
                            <p>Выполнено</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="production-tabs">
                    <button class="tab-btn active" onclick="showTab('dashboard')">
                        <i class="fas fa-chart-line"></i> Оперативка
                    </button>
                    <button class="tab-btn" onclick="showTab('orders')">
                        <i class="fas fa-tasks"></i> Планы выпуска
                    </button>
                    <button class="tab-btn" onclick="showTab('route-sheets')">
                        <i class="fas fa-route"></i> Маршрутные листы
                    </button>
                    <button class="tab-btn" onclick="showTab('quality')">
                        <i class="fas fa-clipboard-check"></i> ОТК
                    </button>
                </div>

                <!-- Dashboard Tab -->
                <div id="dashboard" class="tab-content active">
                    <div class="card">
                        <h2><i class="fas fa-fire"></i> Горящие заказы</h2>
                        <div class="kanban-board">
                            <?php foreach ($current_orders as $order): ?>
                                <?php if ($order['days_remaining'] <= 7): ?>
                                <div class="kanban-card">
                                    <span class="priority-badge priority-<?php echo $order['priority']; ?>">
                                        <?php 
                                        $priorities = ['urgent' => 'Срочно', 'high' => 'Высокий', 'normal' => 'Нормальный', 'low' => 'Низкий'];
                                        echo $priorities[$order['priority']];
                                        ?>
                                    </span>
                                    <h4><?php echo htmlspecialchars($order['product_name']); ?></h4>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <label>№ заказа:</label>
                                            <span><?php echo htmlspecialchars($order['production_number']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <label>Количество:</label>
                                            <span><?php echo $order['quantity']; ?> шт</span>
                                        </div>
                                        <div class="info-item">
                                            <label>До конца:</label>
                                            <span style="color: <?php echo $order['days_remaining'] <= 3 ? '#e74c3c' : '#f39c12'; ?>">
                                                <?php echo $order['days_remaining']; ?> дн.
                                            </span>
                                        </div>
                                        <div class="info-item">
                                            <label>Ответственный:</label>
                                            <span><?php echo $order['responsible_name'] ?: 'Не назначен'; ?></span>
                                        </div>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php 
                                            $total_days = strtotime($order['planned_end_date']) - strtotime($order['planned_start_date']);
                                            $elapsed = time() - strtotime($order['planned_start_date']);
                                            echo min(100, max(0, ($elapsed / $total_days) * 100));
                                        ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <div id="orders" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Планы выпуска продукции</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>№ заказа</th>
                                        <th>Продукция</th>
                                        <th>Кол-во</th>
                                        <th>Приоритет</th>
                                        <th>План начало</th>
                                        <th>План окончание</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_orders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['production_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo $order['quantity']; ?> шт</td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $order['priority'] === 'urgent' ? 'danger' : 
                                                    ($order['priority'] === 'high' ? 'warning' : 'info');
                                            ?>">
                                                <?php echo $priorities[$order['priority']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($order['planned_start_date'])); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($order['planned_end_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $order['status'] === 'in_progress' ? 'warning' : 'primary';
                                            ?>">
                                                <?php 
                                                $statuses = ['planned' => 'В плане', 'in_progress' => 'В работе', 'completed' => 'Завершен'];
                                                echo $statuses[$order['status']];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-icon btn-sm" title="Маршрутный лист" 
                                                        onclick="viewRouteSheet(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                                <?php if ($order['status'] === 'planned'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="production_order_id" value="<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <button type="submit" class="btn btn-icon btn-sm" title="Начать">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Route Sheets Tab -->
                <div id="route-sheets" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-route"></i> Маршрутные листы</h2>
                        <p>Выберите заказ для просмотра маршрутного листа</p>
                        <select id="route-order-select" class="form-control" style="width: 100%; padding: 10px; margin: 15px 0;" onchange="loadRouteSheet(this.value)">
                            <option value="">-- Выберите производственный заказ --</option>
                            <?php foreach ($current_orders as $order): ?>
                            <option value="<?php echo $order['id']; ?>">
                                <?php echo htmlspecialchars($order['production_number'] . ' - ' . $order['product_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="route-sheet-content" class="route-sheet"></div>
                    </div>
                </div>

                <!-- Quality Control Tab -->
                <div id="quality" class="tab-content">
                    <div class="card">
                        <h2><i class="fas fa-clipboard-check"></i> Контроль качества (ОТК)</h2>
                        <p>Выберите заказ для просмотра результатов контроля качества</p>
                        <select id="quality-order-select" class="form-control" style="width: 100%; padding: 10px; margin: 15px 0;" onchange="loadQualityControl(this.value)">
                            <option value="">-- Выберите производственный заказ --</option>
                            <?php foreach ($current_orders as $order): ?>
                            <option value="<?php echo $order['id']; ?>">
                                <?php echo htmlspecialchars($order['production_number'] . ' - ' . $order['product_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="quality-control-content"></div>
                        <button class="btn btn-primary" onclick="openQualityModal()" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Провести контроль качества
                        </button>
                    </div>
                </div>

                <!-- Floating Action Button -->
                <button class="btn-float" onclick="openModal('createOrderModal')" title="Создать план выпуска">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Create Production Order Modal -->
    <div id="createOrderModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Создать план выпуска продукции</h2>
                <button class="modal-close" onclick="closeModal('createOrderModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_production_order">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Продукция *</label>
                            <select name="product_id" required>
                                <option value="">-- Выберите продукцию --</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Количество *</label>
                            <input type="number" name="quantity" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Плановая дата начала *</label>
                            <input type="date" name="planned_start_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Плановая дата окончания *</label>
                            <input type="date" name="planned_end_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Приоритет</label>
                            <select name="priority">
                                <option value="low">Низкий</option>
                                <option value="normal" selected>Нормальный</option>
                                <option value="high">Высокий</option>
                                <option value="urgent">Срочный</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Источник заказа</label>
                            <select name="order_source">
                                <option value="stock_replenishment">Пополнение склада</option>
                                <option value="customer_order">Заказ клиента</option>
                                <option value="forecast">Прогноз</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Рабочий центр</label>
                            <select name="work_center_id">
                                <option value="">-- Не выбрано --</option>
                                <?php foreach ($work_centers as $wc): ?>
                                <option value="<?php echo $wc['id']; ?>"><?php echo htmlspecialchars($wc['center_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ответственный</label>
                            <select name="responsible_user_id">
                                <option value="">-- Не назначен --</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Примечание</label>
                        <textarea name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createOrderModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать план</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function viewRouteSheet(orderId) {
            showTab('route-sheets');
            document.getElementById('route-order-select').value = orderId;
            loadRouteSheet(orderId);
        }

        function loadRouteSheet(orderId) {
            if (!orderId) {
                document.getElementById('route-sheet-content').innerHTML = '';
                return;
            }
            
            // AJAX запрос для получения маршрутного листа
            fetch('api.php?action=get_route_sheet&order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderRouteSheet(data.operations);
                    } else {
                        document.getElementById('route-sheet-content').innerHTML = 
                            '<div class="alert alert-error">Ошибка загрузки: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    // Для демонстрации показываем статические данные при ошибке
                    renderDemoRouteSheet();
                });
        }
        
        function renderRouteSheet(operations) {
            let html = '';
            operations.forEach((op, index) => {
                const statusClass = op.status === 'completed' ? 'step-completed' : 
                                   (op.status === 'in_progress' ? 'step-in_progress' : 'step-pending');
                const statusText = op.status === 'completed' ? 'Выполнено' : 
                                  (op.status === 'in_progress' ? 'В работе' : 'Ожидание');
                
                html += `
                <div class="route-step" data-step="${index + 1}">
                    <div style="flex: 1;">
                        <strong>${op.operation_name}</strong>
                        <p class="text-muted">Рабочий центр: ${op.work_center_name || 'Не назначен'}</p>
                        <div class="info-grid" style="margin-top: 10px;">
                            <div class="info-item">
                                <label>План:</label>
                                <span>${op.planned_start ? new Date(op.planned_start).toLocaleDateString() : '-'} - ${op.planned_end ? new Date(op.planned_end).toLocaleDateString() : '-'}</span>
                            </div>
                            <div class="info-item">
                                <label>Факт:</label>
                                <span>${op.actual_start ? new Date(op.actual_start).toLocaleDateString() : '-'} - ${op.actual_end ? new Date(op.actual_end).toLocaleDateString() : '-'}</span>
                            </div>
                            ${op.status === 'completed' ? `
                            <div class="info-item">
                                <label>Годные:</label>
                                <span style="color: #2ecc71;">${op.quantity_good}</span>
                            </div>
                            <div class="info-item">
                                <label>Брак:</label>
                                <span style="color: ${op.quantity_defect > 0 ? '#e74c3c' : '#2ecc71'};">${op.quantity_defect}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    <span class="step-status ${statusClass}">${statusText}</span>
                </div>`;
            });
            document.getElementById('route-sheet-content').innerHTML = html;
        }
        
        function renderDemoRouteSheet() {
            const html = `
                <div class="route-step" data-step="1">
                    <div>
                        <strong>Заготовительная операция</strong>
                        <p class="text-muted">Рабочий центр: Заготовительный цех</p>
                    </div>
                    <span class="step-status step-completed">Выполнено</span>
                </div>
                <div class="route-step" data-step="2">
                    <div>
                        <strong>Механообработка</strong>
                        <p class="text-muted">Рабочий центр: Токарный участок</p>
                    </div>
                    <span class="step-status step-in_progress">В работе</span>
                </div>
                <div class="route-step" data-step="3">
                    <div>
                        <strong>Сборка</strong>
                        <p class="text-muted">Рабочий центр: Сборочный участок</p>
                    </div>
                    <span class="step-status step-pending">Ожидание</span>
                </div>
                <div class="route-step" data-step="4">
                    <div>
                        <strong>Покраска</strong>
                        <p class="text-muted">Рабочий центр: Окрасочная камера</p>
                    </div>
                    <span class="step-status step-pending">Ожидание</span>
                </div>
                <div class="route-step" data-step="5">
                    <div>
                        <strong>ОТК</strong>
                        <p class="text-muted">Рабочий центр: Отдел технического контроля</p>
                    </div>
                    <span class="step-status step-pending">Ожидание</span>
                </div>
            `;
            document.getElementById('route-sheet-content').innerHTML = html;
        }

        function loadQualityControl(orderId) {
            if (!orderId) {
                document.getElementById('quality-control-content').innerHTML = '';
                return;
            }
            
            fetch('api.php?action=get_quality_control&order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderQualityControl(data.controls);
                    } else {
                        document.getElementById('quality-control-content').innerHTML = 
                            '<div class="alert alert-error">Ошибка: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    renderDemoQualityControl();
                });
        }

        function renderQualityControl(controls) {
            if (controls.length === 0) {
                document.getElementById('quality-control-content').innerHTML = 
                    '<div class="alert alert-info">Контроль качества еще не проводился</div>';
                return;
            }
            
            let html = '<h3 style="margin-top: 20px;">Результаты контроля:</h3>';
            controls.forEach(qc => {
                const resultClass = qc.inspection_result === 'passed' ? 'success' : 
                                   (qc.inspection_result === 'failed' ? 'danger' : 'warning');
                const resultText = qc.inspection_result === 'passed' ? 'Пройден' : 
                                  (qc.inspection_result === 'failed' ? 'Не пройден' : 'Условно');
                
                html += `
                <div class="card" style="margin-bottom: 15px; background: #f8f9fa;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <strong>Акт № ${qc.certificate_number || 'б/н'}</strong>
                        <span class="badge badge-${resultClass}">${resultText}</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Дата проверки:</label>
                            <span>${new Date(qc.inspection_date).toLocaleDateString()}</span>
                        </div>
                        <div class="info-item">
                            <label>Инспектор:</label>
                            <span>${qc.inspector_name}</span>
                        </div>
                        <div class="info-item">
                            <label>Проверено:</label>
                            <span>${qc.inspected_quantity} шт</span>
                        </div>
                        <div class="info-item">
                            <label>Годные:</label>
                            <span style="color: #2ecc71;">${qc.passed_quantity} шт</span>
                        </div>
                        <div class="info-item">
                            <label>Брак:</label>
                            <span style="color: #e74c3c;">${qc.rejected_quantity} шт</span>
                        </div>
                    </div>
                    ${qc.notes ? `<p style="margin-top: 10px;"><strong>Примечание:</strong> ${qc.notes}</p>` : ''}
                </div>`;
            });
            document.getElementById('quality-control-content').innerHTML = html;
        }

        function renderDemoQualityControl() {
            const html = `
                <h3 style="margin-top: 20px;">Результаты контроля:</h3>
                <div class="card" style="margin-bottom: 15px; background: #f8f9fa;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <strong>Акт № ОТК-2024-001</strong>
                        <span class="badge badge-warning">Условно</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Дата проверки:</label>
                            <span>${new Date().toLocaleDateString()}</span>
                        </div>
                        <div class="info-item">
                            <label>Инспектор:</label>
                            <span>Иванов И.И.</span>
                        </div>
                        <div class="info-item">
                            <label>Проверено:</label>
                            <span>50 шт</span>
                        </div>
                        <div class="info-item">
                            <label>Годные:</label>
                            <span style="color: #2ecc71;">48 шт</span>
                        </div>
                        <div class="info-item">
                            <label>Брак:</label>
                            <span style="color: #e74c3c;">2 шт</span>
                        </div>
                    </div>
                    <p style="margin-top: 10px;"><strong>Примечание:</strong> Выявлено 2 дефекта - трещины в корпусе</p>
                </div>
            `;
            document.getElementById('quality-control-content').innerHTML = html;
        }

        function openQualityModal() {
            const orderId = document.getElementById('quality-order-select').value;
            if (!orderId) {
                alert('Сначала выберите производственный заказ');
                return;
            }
            // Здесь будет открытие модального окна для создания записи ОТК
            alert('Форма проведения контроля качества будет открыта для заказа #' + orderId);
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>

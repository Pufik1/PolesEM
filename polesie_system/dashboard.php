<?php
/**
 * Main dashboard for OAO "Polesieelectromash" ERP System
 * Central hub showing key metrics and navigation
 */

require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

$pdo = getDBConnection();
$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

// Get dashboard statistics based on user role
$stats = [];

try {
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $stats['products'] = $stmt->fetch()['count'];
    
    // Total clients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients WHERE is_active = 1");
    $stats['clients'] = $stmt->fetch()['count'];
    
    // Orders this month
    $stmt = $pdo->query("
        SELECT COUNT(*) as count FROM orders 
        WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(order_date) = YEAR(CURRENT_DATE())
    ");
    $stats['orders_month'] = $stmt->fetch()['count'];
    
    // Production orders in progress
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM production_orders WHERE status = 'in_progress'");
    $stats['production_active'] = $stmt->fetch()['count'];
    
    // Low stock products
    $stmt = $pdo->query("
        SELECT COUNT(*) as count FROM products 
        WHERE stock_quantity <= min_stock_level AND is_active = 1
    ");
    $stats['low_stock'] = $stmt->fetch()['count'];
    
    // Recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, c.company_name, u.full_name as manager_name
        FROM orders o
        LEFT JOIN clients c ON o.client_id = c.id
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll();
    
    // Recent activity
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name as user_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Get user info
$userFullName = $_SESSION['full_name'];
$userEmail = $_SESSION['email'];
$initials = strtoupper(substr($userFullName, 0, 1));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="logo-text">
                        <h2>Полесьеэлектромаш</h2>
                        <p>Корпоративная система</p>
                    </div>
                </div>
            </div>
            
            <?php 
            $basePath = '';
            include 'includes/sidebar.php'; 
            ?>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <div class="header-title">
                        <h1>Главная</h1>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo $initials; ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($userFullName); ?></span>
                            <span class="user-role"><?php echo ucfirst($userRole); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['products']; ?></h3>
                            <p>Продукция в каталоге</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['clients']; ?></h3>
                            <p>Активные клиенты</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['orders_month']; ?></h3>
                            <p>Заказов за месяц</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['low_stock']; ?></h3>
                            <p>Товаров с низким запасом</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Последние заказы</h2>
                        <a href="modules/orders/index.php" class="btn btn-sm btn-primary">Все заказы</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Клиент</th>
                                    <th>Менеджер</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Заказов нет</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['company_name'] ?? 'Не указан'); ?></td>
                                            <td><?php echo htmlspecialchars($order['manager_name'] ?? 'Не назначен'); ?></td>
                                            <td><?php echo number_format($order['final_amount'], 2); ?> BYN</td>
                                            <td>
                                                <?php
                                                $statusBadges = [
                                                    'new' => 'badge-info',
                                                    'processing' => 'badge-warning',
                                                    'production' => 'badge-primary',
                                                    'ready' => 'badge-success',
                                                    'shipped' => 'badge-info',
                                                    'completed' => 'badge-success',
                                                    'cancelled' => 'badge-danger'
                                                ];
                                                $statusLabels = [
                                                    'new' => 'Новый',
                                                    'processing' => 'В обработке',
                                                    'production' => 'В производстве',
                                                    'ready' => 'Готов',
                                                    'shipped' => 'Отгружен',
                                                    'completed' => 'Завершен',
                                                    'cancelled' => 'Отменен'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $statusBadges[$order['status']] ?? 'badge-info'; ?>">
                                                    <?php echo $statusLabels[$order['status']] ?? $order['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($order['order_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Activity Log -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Журнал активности</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Пользователь</th>
                                    <th>Действие</th>
                                    <th>Объект</th>
                                    <th>Время</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentActivity)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Записей нет</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($activity['user_name'] ?? 'Система'); ?></td>
                                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                            <td><?php 
                                                // Перевод имен таблиц на русский язык
                                                $tableTranslations = [
                                                    'users' => 'Пользователи',
                                                    'orders' => 'Заказы',
                                                    'products' => 'Продукция',
                                                    'clients' => 'Клиенты',
                                                    'warehouse' => 'Склад',
                                                    'production' => 'Производство',
                                                    'finance' => 'Финансы',
                                                    'hr' => 'Кадры'
                                                ];
                                                $tableName = $activity['table_name'] ?? null;
                                                echo htmlspecialchars($tableTranslations[$tableName] ?? $tableName ?? '-'); 
                                            ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php 
require_once '../../includes/config.php'; 
if (!isLoggedIn()) redirect('../../index.php'); 

$pdo = getDBConnection();
$userRole = $_SESSION['user_role'];

// Получение данных для отчетов
$reportData = [];

try {
    // 1. Общая статистика
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $reportData['total_orders'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $reportData['total_clients'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $reportData['total_products'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM production_orders");
    $reportData['total_production'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT SUM(final_amount) as total FROM orders WHERE status = 'completed'");
    $reportData['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // 2. Выручка за текущий месяц
    $stmt = $pdo->query("
        SELECT SUM(final_amount) as total 
        FROM orders 
        WHERE status = 'completed' 
        AND MONTH(order_date) = MONTH(CURRENT_DATE())
        AND YEAR(order_date) = YEAR(CURRENT_DATE())
    ");
    $reportData['month_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // 3. Продажи по месяцам (за текущий год)
    $stmt = $pdo->query("
        SELECT 
            MONTH(order_date) as month,
            COUNT(*) as order_count,
            SUM(final_amount) as total_amount
        FROM orders 
        WHERE YEAR(order_date) = YEAR(CURRENT_DATE())
        GROUP BY MONTH(order_date)
        ORDER BY month
    ");
    $reportData['sales_by_month'] = $stmt->fetchAll();
    
    // 4. Статусы заказов
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(final_amount) as total_amount
        FROM orders
        GROUP BY status
    ");
    $reportData['order_statuses'] = $stmt->fetchAll();
    
    // 5. Статистика производства по статусам
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as planned_count,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status = 'planned' THEN quantity ELSE 0 END) as planned_quantity,
            SUM(CASE WHEN status = 'in_progress' THEN quantity ELSE 0 END) as in_progress_quantity,
            SUM(CASE WHEN status = 'completed' THEN quantity ELSE 0 END) as completed_quantity
        FROM production_orders
    ");
    $prodStats = $stmt->fetch();
    $reportData['production_planned'] = (int)($prodStats['planned_count'] ?? 0);
    $reportData['production_in_progress'] = (int)($prodStats['in_progress_count'] ?? 0);
    $reportData['production_completed'] = (int)($prodStats['completed_count'] ?? 0);
    $reportData['production_cancelled'] = (int)($prodStats['cancelled_count'] ?? 0);
    $reportData['production_planned_qty'] = (int)($prodStats['planned_quantity'] ?? 0);
    $reportData['production_in_progress_qty'] = (int)($prodStats['in_progress_quantity'] ?? 0);
    $reportData['production_completed_qty'] = (int)($prodStats['completed_quantity'] ?? 0);
    
    // 6. Топ клиентов по сумме заказов
    $stmt = $pdo->query("
        SELECT 
            c.company_name,
            COUNT(o.id) as order_count,
            SUM(o.final_amount) as total_amount
        FROM orders o
        JOIN clients c ON o.client_id = c.id
        WHERE o.status != 'cancelled'
        GROUP BY c.id, c.company_name
        ORDER BY total_amount DESC
        LIMIT 5
    ");
    $reportData['top_clients'] = $stmt->fetchAll();
    
    // 7. Товары с низким запасом
    $stmt = $pdo->query("
        SELECT 
            product_code,
            product_name,
            stock_quantity,
            min_stock_level,
            (min_stock_level - stock_quantity) as shortage
        FROM products
        WHERE stock_quantity <= min_stock_level
        ORDER BY shortage DESC
        LIMIT 5
    ");
    $reportData['low_stock'] = $stmt->fetchAll();
    
    // 8. Материалы с низким запасом
    $stmt = $pdo->query("
        SELECT 
            sku,
            name,
            current_stock,
            min_stock_level,
            unit,
            (min_stock_level - current_stock) as shortage
        FROM materials
        WHERE current_stock <= min_stock_level
        ORDER BY shortage DESC
        LIMIT 5
    ");
    $reportData['low_materials'] = $stmt->fetchAll();
    
    // 9. Recent orders
    $stmt = $pdo->query("
        SELECT 
            o.order_number,
            o.order_date,
            o.final_amount,
            o.status,
            c.company_name as client_name
        FROM orders o
        JOIN clients c ON o.client_id = c.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $reportData['recent_orders'] = $stmt->fetchAll();
    
    // 10. Динамика продаж и производства (последние 6 месяцев)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(order_date, '%Y-%m') as month,
            COUNT(*) as orders_count,
            SUM(final_amount) as revenue
        FROM orders
        WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ORDER BY month
    ");
    $reportData['sales_trend'] = $stmt->fetchAll();
    
    // 11. Выполнение производственных заказов
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(planned_end_date, '%Y-%m') as month,
            COUNT(*) as production_count,
            SUM(quantity) as total_quantity
        FROM production_orders
        WHERE status = 'completed'
        AND planned_end_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(planned_end_date, '%Y-%m')
        ORDER BY month
    ");
    $reportData['production_trend'] = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчеты - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-dashboard {
            display: grid;
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .kpi-card.primary::before { background: var(--primary-color); }
        .kpi-card.success::before { background: var(--success-color); }
        .kpi-card.warning::before { background: var(--warning-color); }
        .kpi-card.info::before { background: var(--info-color); }
        .kpi-card.danger::before { background: var(--danger-color); }
        
        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        
        .kpi-card.primary .kpi-icon { background: rgba(26, 95, 122, 0.1); color: var(--primary-color); }
        .kpi-card.success .kpi-icon { background: rgba(46, 204, 113, 0.1); color: var(--success-color); }
        .kpi-card.warning .kpi-icon { background: rgba(243, 156, 18, 0.1); color: var(--warning-color); }
        .kpi-card.info .kpi-icon { background: rgba(52, 152, 219, 0.1); color: var(--info-color); }
        .kpi-card.danger .kpi-icon { background: rgba(231, 76, 60, 0.1); color: var(--danger-color); }
        
        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }
        
        .kpi-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .chart-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-md);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-title i {
            color: var(--primary-color);
        }
        
        .chart-container {
            position: relative;
            height: 280px;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
        }
        
        .data-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-md);
        }
        
        .data-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--bg-color);
        }
        
        .data-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .data-title i {
            color: var(--primary-color);
        }
        
        .compact-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .compact-table th,
        .compact-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--bg-color);
        }
        
        .compact-table th {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        .compact-table td {
            font-size: 14px;
        }
        
        .compact-table tr:last-child td {
            border-bottom: none;
        }
        
        .compact-table tr:hover td {
            background: var(--bg-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-new { background: #e3f2fd; color: #1976d2; }
        .status-processing { background: #fff3e0; color: #f57c00; }
        .status-production { background: #f3e5f5; color: #7b1fa2; }
        .status-ready { background: #e8f5e9; color: #388e3c; }
        .status-shipped { background: #e0f7fa; color: #00838f; }
        .status-completed { background: #c8e6c9; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        
        .progress-ring {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .low-stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--bg-color);
        }
        
        .low-stock-item:last-child {
            border-bottom: none;
        }
        
        .stock-info {
            flex: 1;
        }
        
        .stock-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .stock-sku {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .stock-level {
            text-align: right;
        }
        
        .stock-current {
            font-size: 16px;
            font-weight: 600;
            color: var(--danger-color);
        }
        
        .stock-min {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .trend-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .trend-up {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .trend-down {
            background: #ffebee;
            color: #c62828;
        }
        
        @media (max-width: 768px) {
            .charts-row,
            .data-grid {
                grid-template-columns: 1fr;
            }
            
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
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
                <div class="header-title"><h1>Отчеты и аналитика</h1></div>
                <div class="user-info">
                    <div class="user-details">
                        <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                </div>
            </header>
            <div class="content-area">
                
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card primary">
                        <div class="kpi-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="kpi-value"><?php echo number_format($reportData['total_orders'] ?? 0); ?></div>
                        <div class="kpi-label">Всего заказов</div>
                    </div>
                    <div class="kpi-card success">
                        <div class="kpi-icon"><i class="fas fa-users"></i></div>
                        <div class="kpi-value"><?php echo number_format($reportData['total_clients'] ?? 0); ?></div>
                        <div class="kpi-label">Активных клиентов</div>
                    </div>
                    <div class="kpi-card warning">
                        <div class="kpi-icon"><i class="fas fa-box"></i></div>
                        <div class="kpi-value"><?php echo number_format($reportData['total_products'] ?? 0); ?></div>
                        <div class="kpi-label">Продукции в каталоге</div>
                    </div>
                    <div class="kpi-card info">
                        <div class="kpi-icon"><i class="fas fa-industry"></i></div>
                        <div class="kpi-value"><?php echo number_format($reportData['total_production'] ?? 0); ?></div>
                        <div class="kpi-label">Производственных заказов</div>
                    </div>
                    <div class="kpi-card danger">
                        <div class="kpi-icon"><i class="fas fa-coins"></i></div>
                        <div class="kpi-value"><?php echo number_format($reportData['month_revenue'] ?? 0, 0); ?></div>
                        <div class="kpi-label">Выручка за месяц (BYN)</div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="charts-row">
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-chart-line"></i>
                                Динамика продаж (за год)
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-chart-pie"></i>
                                Статусы заказов
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Production Stats & Trend Chart -->
                <div class="charts-row">
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-tasks"></i>
                                Производство по статусам
                            </div>
                        </div>
                        <div style="padding: 20px 0;">
                            <div style="display: flex; justify-content: space-around; text-align: center;">
                                <div>
                                    <div style="font-size: 28px; font-weight: 700; color: #3498db;"><?php echo $reportData['production_planned'] ?? 0; ?></div>
                                    <div style="font-size: 13px; color: var(--text-secondary);">В плане</div>
                                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo $reportData['production_planned_qty'] ?? 0; ?> шт.</div>
                                </div>
                                <div>
                                    <div style="font-size: 28px; font-weight: 700; color: #f39c12;"><?php echo $reportData['production_in_progress'] ?? 0; ?></div>
                                    <div style="font-size: 13px; color: var(--text-secondary);">В работе</div>
                                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo $reportData['production_in_progress_qty'] ?? 0; ?> шт.</div>
                                </div>
                                <div>
                                    <div style="font-size: 28px; font-weight: 700; color: #2ecc71;"><?php echo $reportData['production_completed'] ?? 0; ?></div>
                                    <div style="font-size: 13px; color: var(--text-secondary);">Завершено</div>
                                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo $reportData['production_completed_qty'] ?? 0; ?> шт.</div>
                                </div>
                                <div>
                                    <div style="font-size: 28px; font-weight: 700; color: #e74c3c;"><?php echo $reportData['production_cancelled'] ?? 0; ?></div>
                                    <div style="font-size: 13px; color: var(--text-secondary);">Отменено</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-chart-bar"></i>
                                Тренд продаж (6 мес.)
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Data Tables Grid -->
                <div class="data-grid">
                    <!-- Top Clients -->
                    <div class="data-card">
                        <div class="data-header">
                            <div class="data-title">
                                <i class="fas fa-trophy"></i>
                                Топ клиентов
                            </div>
                        </div>
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Клиент</th>
                                    <th>Заказов</th>
                                    <th>Сумма (BYN)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData['top_clients'])): ?>
                                    <tr><td colspan="3" class="text-center">Нет данных</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reportData['top_clients'] as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['company_name']); ?></td>
                                            <td><?php echo $client['order_count']; ?></td>
                                            <td><?php echo number_format($client['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="data-card">
                        <div class="data-header">
                            <div class="data-title">
                                <i class="fas fa-clock"></i>
                                Последние заказы
                            </div>
                        </div>
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Клиент</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData['recent_orders'])): ?>
                                    <tr><td colspan="4" class="text-center">Нет данных</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reportData['recent_orders'] as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                            <td><?php echo number_format($order['final_amount'], 0); ?></td>
                                            <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Low Stock Products -->
                    <div class="data-card">
                        <div class="data-header">
                            <div class="data-title">
                                <i class="fas fa-exclamation-triangle"></i>
                                Товары с низким запасом
                            </div>
                        </div>
                        <?php if (empty($reportData['low_stock'])): ?>
                            <div style="text-align: center; padding: 30px; color: var(--text-secondary);">
                                <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success-color); margin-bottom: 10px;"></i>
                                <p>Все товары в норме</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reportData['low_stock'] as $item): ?>
                                <div class="low-stock-item">
                                    <div class="stock-info">
                                        <div class="stock-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="stock-sku"><?php echo htmlspecialchars($item['product_code']); ?></div>
                                    </div>
                                    <div class="stock-level">
                                        <div class="stock-current"><?php echo $item['stock_quantity']; ?></div>
                                        <div class="stock-min">мин: <?php echo $item['min_stock_level']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Low Stock Materials -->
                    <div class="data-card">
                        <div class="data-header">
                            <div class="data-title">
                                <i class="fas fa-tools"></i>
                                Материалы с низким запасом
                            </div>
                        </div>
                        <?php if (empty($reportData['low_materials'])): ?>
                            <div style="text-align: center; padding: 30px; color: var(--text-secondary);">
                                <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success-color); margin-bottom: 10px;"></i>
                                <p>Все материалы в норме</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reportData['low_materials'] as $material): ?>
                                <div class="low-stock-item">
                                    <div class="stock-info">
                                        <div class="stock-name"><?php echo htmlspecialchars($material['name']); ?></div>
                                        <div class="stock-sku"><?php echo htmlspecialchars($material['sku']); ?></div>
                                    </div>
                                    <div class="stock-level">
                                        <div class="stock-current"><?php echo $material['current_stock']; ?> <?php echo $material['unit']; ?></div>
                                        <div class="stock-min">мин: <?php echo $material['min_stock_level']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script>
        // Инициализация графиков при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
            
            // График продаж по месяцам
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                const salesData = <?php echo json_encode($reportData['sales_by_month'] ?? []); ?>;
                
                new Chart(salesCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: salesData.map(d => monthNames[d.month - 1]),
                        datasets: [{
                            label: 'Количество заказов',
                            data: salesData.map(d => d.order_count),
                            borderColor: '#1a5f7a',
                            backgroundColor: 'rgba(26, 95, 122, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#1a5f7a',
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }, {
                            label: 'Сумма (тыс. BYN)',
                            data: salesData.map(d => (d.total_amount / 1000).toFixed(1)),
                            borderColor: '#27ae60',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            tension: 0.4,
                            pointBackgroundColor: '#27ae60',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                titleFont: { size: 14 },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            if (context.datasetIndex === 1) {
                                                label += context.parsed.y + ' тыс.';
                                            } else {
                                                label += context.parsed.y;
                                            }
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Заказов'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                title: {
                                    display: true,
                                    text: 'Тыс. BYN'
                                }
                            }
                        }
                    }
                });
            }
            
            // График статусов заказов
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                const statusData = <?php echo json_encode($reportData['order_statuses'] ?? []); ?>;
                const statusColors = {
                    'new': '#3498db',
                    'processing': '#f39c12',
                    'production': '#9b59b6',
                    'ready': '#2ecc71',
                    'shipped': '#1abc9c',
                    'completed': '#27ae60',
                    'cancelled': '#e74c3c'
                };
                const statusLabels = {
                    'new': 'Новый',
                    'processing': 'В обработке',
                    'production': 'В производстве',
                    'ready': 'Готов',
                    'shipped': 'Отгружен',
                    'completed': 'Завершен',
                    'cancelled': 'Отменен'
                };
                
                new Chart(statusCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(d => statusLabels[d.status] || d.status),
                        datasets: [{
                            data: statusData.map(d => d.count),
                            backgroundColor: statusData.map(d => statusColors[d.status] || '#95a5a6'),
                            borderWidth: 3,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: { size: 12 }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return label + ': ' + value + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // График тренда продаж
            const trendCtx = document.getElementById('trendChart');
            if (trendCtx) {
                const trendData = <?php echo json_encode($reportData['sales_trend'] ?? []); ?>;
                
                new Chart(trendCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: trendData.map(d => d.month),
                        datasets: [{
                            label: 'Заказы',
                            data: trendData.map(d => d.orders_count),
                            backgroundColor: 'rgba(26, 95, 122, 0.8)',
                            borderColor: '#1a5f7a',
                            borderWidth: 1,
                            borderRadius: 6,
                            yAxisID: 'y'
                        }, {
                            label: 'Выручка (тыс. BYN)',
                            data: trendData.map(d => (d.revenue / 1000).toFixed(1)),
                            type: 'line',
                            borderColor: '#27ae60',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            tension: 0.4,
                            pointBackgroundColor: '#27ae60',
                            pointRadius: 6,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            if (context.datasetIndex === 1) {
                                                label += context.parsed.y + ' тыс.';
                                            } else {
                                                label += context.parsed.y;
                                            }
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Заказов'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                title: {
                                    display: true,
                                    text: 'Тыс. BYN'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>

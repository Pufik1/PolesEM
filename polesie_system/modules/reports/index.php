<?php 
require_once '../../includes/config.php'; 
if (!isLoggedIn()) redirect('../../index.php'); 

$pdo = getDBConnection();
$userRole = $_SESSION['user_role'];

// Получение данных для отчетов
$reportData = [];

try {
    // 1. Продажи по месяцам (за текущий год)
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
    
    // 2. Топ клиентов по сумме заказов
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
        LIMIT 10
    ");
    $reportData['top_clients'] = $stmt->fetchAll();
    
    // 3. Статусы заказов
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(final_amount) as total_amount
        FROM orders
        GROUP BY status
    ");
    $reportData['order_statuses'] = $stmt->fetchAll();
    
    // 4. Продукция в производстве
    $stmt = $pdo->query("
        SELECT 
            p.product_name,
            COUNT(po.id) as production_count,
            SUM(po.quantity) as total_quantity
        FROM production_orders po
        JOIN products p ON po.product_id = p.id
        WHERE po.status IN ('planned', 'in_progress')
        GROUP BY p.id, p.product_name
        ORDER BY total_quantity DESC
        LIMIT 10
    ");
    $reportData['production_products'] = $stmt->fetchAll();
    
    // 5. Складские запасы (товары с низким остатком)
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
    ");
    $reportData['low_stock'] = $stmt->fetchAll();
    
    // 6. Финансы: выручка по месяцам
    $stmt = $pdo->query("
        SELECT 
            MONTH(paid_date) as month,
            COUNT(*) as payment_count,
            SUM(paid_amount) as total_paid
        FROM orders
        WHERE paid_date IS NOT NULL
        AND YEAR(paid_date) = YEAR(CURRENT_DATE())
        GROUP BY MONTH(paid_date)
        ORDER BY month
    ");
    $reportData['revenue_by_month'] = $stmt->fetchAll();
    
    // 7. Сотрудники: выполненные сменные задания
    $stmt = $pdo->query("
        SELECT 
            u.full_name,
            COUNT(st.id) as tasks_completed,
            SUM(st.actual_quantity) as total_produced
        FROM shift_tasks st
        JOIN users u ON st.worker_id = u.id
        WHERE st.status = 'completed'
        AND MONTH(st.shift_date) = MONTH(CURRENT_DATE())
        GROUP BY u.id, u.full_name
        ORDER BY tasks_completed DESC
        LIMIT 10
    ");
    $reportData['employee_performance'] = $stmt->fetchAll();
    
    // 8. Общая статистика
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $reportData['total_orders'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $reportData['total_clients'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $reportData['total_products'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT SUM(final_amount) as total FROM orders WHERE status = 'completed'");
    $reportData['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
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
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow-md);
        }
        
        .report-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }
        
        .report-filters {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .btn-filter {
            padding: 8px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .btn-filter:hover {
            background: var(--primary-dark);
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            margin-top: 15px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--bg-color);
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: var(--text-secondary);
        }
        
        .stat-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-new { background: #3498db; color: white; }
        .badge-processing { background: #f39c12; color: white; }
        .badge-production { background: #9b59b6; color: white; }
        .badge-ready { background: #2ecc71; color: white; }
        .badge-shipped { background: #1abc9c; color: white; }
        .badge-completed { background: #27ae60; color: white; }
        .badge-cancelled { background: #e74c3c; color: white; }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--bg-color);
        }
        
        .data-table th {
            background: var(--bg-color);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .data-table tr:hover {
            background: var(--bg-color);
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
        }
        
        .summary-card h4 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .summary-card .value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-secondary);
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .tab-btn:hover:not(.active) {
            background: var(--bg-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-export {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-export-print {
            background: #3498db;
            color: white;
        }
        
        .btn-export-print:hover {
            background: #2980b9;
        }
        
        .date-range-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-range-inputs input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }
        
        .chart-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        
        .chart-fullscreen {
            background: transparent;
            border: 1px solid var(--border-color);
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        
        .chart-fullscreen:hover {
            background: var(--bg-color);
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
                <div class="header-title"><h1>Отчетность и аналитика</h1></div>
                <div class="user-info">
                    <div class="user-details">
                        <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                </div>
            </header>
            <div class="content-area">
                
                <!-- Фильтры -->
                <div class="report-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Период</label>
                            <select id="periodFilter" onchange="applyFilters()">
                                <option value="today">Сегодня</option>
                                <option value="week">Неделя</option>
                                <option value="month" selected>Месяц</option>
                                <option value="quarter">Квартал</option>
                                <option value="year">Год</option>
                                <option value="custom">Произвольный</option>
                            </select>
                        </div>
                        <div class="filter-group date-range-inputs" id="dateRangeGroup" style="display: none;">
                            <input type="date" id="dateFrom" placeholder="С">
                            <span>—</span>
                            <input type="date" id="dateTo" placeholder="По">
                        </div>
                        <div class="filter-group">
                            <label>Тип отчета</label>
                            <select id="reportType" onchange="applyFilters()">
                                <option value="all">Все отчеты</option>
                                <option value="sales">Продажи</option>
                                <option value="production">Производство</option>
                                <option value="warehouse">Склад</option>
                                <option value="finance">Финансы</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button class="btn-filter" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Применить
                            </button>
                        </div>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button class="btn-filter" style="background: #3498db;" onclick="exportToPrint()">
                                <i class="fas fa-print"></i> Печать
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Сводные карточки -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <h4>Всего заказов</h4>
                        <div class="value"><?php echo number_format($reportData['total_orders'] ?? 0); ?></div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                        <h4>Активные клиенты</h4>
                        <div class="value"><?php echo number_format($reportData['total_clients'] ?? 0); ?></div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #f39c12, #f1c40f);">
                        <h4>Продукции в каталоге</h4>
                        <div class="value"><?php echo number_format($reportData['total_products'] ?? 0); ?></div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <h4>Общая выручка</h4>
                        <div class="value"><?php echo number_format($reportData['total_revenue'] ?? 0, 2); ?> BYN</div>
                    </div>
                </div>
                
                <!-- Вкладки -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('sales')">Продажи</button>
                    <button class="tab-btn" onclick="switchTab('production')">Производство</button>
                    <button class="tab-btn" onclick="switchTab('warehouse')">Склад</button>
                    <button class="tab-btn" onclick="switchTab('finance')">Финансы</button>
                </div>
                
                <!-- Вкладка: Продажи -->
                <div id="sales-tab" class="tab-content active">
                    <div class="reports-grid">
                        <div class="report-card">
                            <h3><i class="fas fa-chart-line"></i> Продажи по месяцам</h3>
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                        <div class="report-card">
                            <h3><i class="fas fa-trophy"></i> Топ клиентов</h3>
                            <div class="table-responsive">
                                <table class="data-table">
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
                        </div>
                    </div>
                    
                    <div class="report-card">
                        <h3><i class="fas fa-chart-pie"></i> Статусы заказов</h3>
                        <div class="reports-grid">
                            <div style="flex: 1;">
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Статус</th>
                                                <th>Количество</th>
                                                <th>Сумма (BYN)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
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
                                            <?php if (empty($reportData['order_statuses'])): ?>
                                                <tr><td colspan="3" class="text-center">Нет данных</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($reportData['order_statuses'] as $status): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge-status badge-<?php echo $status['status']; ?>">
                                                                <?php echo $statusLabels[$status['status']] ?? $status['status']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $status['count']; ?></td>
                                                        <td><?php echo number_format($status['total_amount'] ?? 0, 2); ?></td>
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
                
                <!-- Вкладка: Производство -->
                <div id="production-tab" class="tab-content">
                    <div class="reports-grid">
                        <div class="report-card">
                            <h3><i class="fas fa-industry"></i> Продукция в производстве</h3>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Продукция</th>
                                            <th>Заказов</th>
                                            <th>Общее количество</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reportData['production_products'])): ?>
                                            <tr><td colspan="3" class="text-center">Нет активных производственных заказов</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($reportData['production_products'] as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                    <td><?php echo $product['production_count']; ?></td>
                                                    <td><?php echo $product['total_quantity']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="report-card">
                            <h3><i class="fas fa-tasks"></i> Статистика производства</h3>
                            <div class="stat-row">
                                <span class="stat-label">В плане</span>
                                <span class="stat-value">-</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">В работе</span>
                                <span class="stat-value">-</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Завершено</span>
                                <span class="stat-value">-</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Брак</span>
                                <span class="stat-value">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Вкладка: Склад -->
                <div id="warehouse-tab" class="tab-content">
                    <div class="report-card">
                        <h3><i class="fas fa-exclamation-triangle"></i> Товары с низким запасом</h3>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Код</th>
                                        <th>Наименование</th>
                                        <th>Остаток</th>
                                        <th>Мин. уровень</th>
                                        <th>Недостача</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reportData['low_stock'])): ?>
                                        <tr><td colspan="5" class="text-center">Все товары в норме</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reportData['low_stock'] as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td><?php echo $item['stock_quantity']; ?></td>
                                                <td><?php echo $item['min_stock_level']; ?></td>
                                                <td style="color: var(--danger-color); font-weight: bold;"><?php echo $item['shortage']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Вкладка: Финансы -->
                <div id="finance-tab" class="tab-content">
                    <div class="reports-grid">
                        <div class="report-card">
                            <h3><i class="fas fa-chart-bar"></i> Выручка по месяцам</h3>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                        <div class="report-card">
                            <h3><i class="fas fa-wallet"></i> Финансовые показатели</h3>
                            <div class="stat-row">
                                <span class="stat-label">Общая выручка</span>
                                <span class="stat-value"><?php echo number_format($reportData['total_revenue'] ?? 0, 2); ?> BYN</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Средний чек</span>
                                <span class="stat-value">
                                    <?php 
                                    $avgCheck = $reportData['total_orders'] > 0 ? ($reportData['total_revenue'] ?? 0) / $reportData['total_orders'] : 0;
                                    echo number_format($avgCheck, 2); ?> BYN
                                </span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">За месяц</span>
                                <span class="stat-value">- BYN</span>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script>
        // Глобальные переменные для графиков
        let salesChart, statusChart, revenueChart;
        let currentPeriod = 'month';
        let currentReportType = 'all';
        
        // Переключение вкладок
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        // Применение фильтров
        function applyFilters() {
            const period = document.getElementById('periodFilter').value;
            const reportType = document.getElementById('reportType').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            // Показываем/скрываем поля для произвольного периода
            const dateRangeGroup = document.getElementById('dateRangeGroup');
            if (period === 'custom') {
                dateRangeGroup.style.display = 'flex';
            } else {
                dateRangeGroup.style.display = 'none';
            }
            
            currentPeriod = period;
            currentReportType = reportType;
            
            // Загружаем новые данные с сервера
            loadReportData(period, reportType, dateFrom, dateTo);
        }
        
        // Загрузка данных отчета через AJAX
        function loadReportData(period, type, dateFrom, dateTo) {
            let url = `api.php?period=${period}&type=${type}`;
            
            if (dateFrom && dateTo) {
                url += `&date_from=${dateFrom}&date_to=${dateTo}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        updateCharts(result.data);
                        updateSummaryCards(result.data);
                        updateTables(result.data);
                    } else {
                        console.error('Ошибка загрузки данных:', result.error);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                });
        }
        
        // Обновление сводных карточек
        function updateSummaryCards(data) {
            const cards = document.querySelectorAll('.summary-card .value');
            if (cards[0]) cards[0].textContent = number_format(data.total_orders ?? 0);
            if (cards[1]) cards[1].textContent = number_format(data.total_clients ?? 0);
            if (cards[2]) cards[2].textContent = number_format(data.total_products ?? 0);
            if (cards[3]) cards[3].textContent = number_format(data.total_revenue ?? 0, 2) + ' BYN';
        }
        
        // Обновление таблиц
        function updateTables(data) {
            // Топ клиентов
            if (data.top_clients) {
                const tbody = document.querySelector('#sales-tab .data-table tbody');
                if (tbody) {
                    if (data.top_clients.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Нет данных</td></tr>';
                    } else {
                        tbody.innerHTML = data.top_clients.map(client => 
                            `<tr>
                                <td>${escapeHtml(client.company_name)}</td>
                                <td>${client.order_count}</td>
                                <td>${number_format(client.total_amount, 2)}</td>
                            </tr>`
                        ).join('');
                    }
                }
            }
            
            // Товары с низким запасом
            if (data.low_stock) {
                const tbody = document.querySelector('#warehouse-tab .data-table tbody');
                if (tbody) {
                    if (data.low_stock.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Все товары в норме</td></tr>';
                    } else {
                        tbody.innerHTML = data.low_stock.map(item => 
                            `<tr>
                                <td>${escapeHtml(item.product_code)}</td>
                                <td>${escapeHtml(item.product_name)}</td>
                                <td>${item.stock_quantity}</td>
                                <td>${item.min_stock_level}</td>
                                <td style="color: var(--danger-color); font-weight: bold;">${item.shortage}</td>
                            </tr>`
                        ).join('');
                    }
                }
            }
        }
        
        // Вспомогательные функции
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
        
        function number_format(number, decimals = 0) {
            return Number(number).toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        
        // Экспорт в печать
        function exportToPrint() {
            window.print();
        }
        
        // Обновление графиков с новыми данными
        function updateCharts(newData) {
            const monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
            
            if (salesChart && newData.sales_by_period) {
                const salesByPeriod = newData.sales_by_period;
                salesChart.data.labels = salesByPeriod.map(d => {
                    const period = d.period;
                    return period > 31 ? monthNames[period - 1] : `${period} число`;
                });
                salesChart.data.datasets[0].data = salesByPeriod.map(d => d.order_count);
                salesChart.data.datasets[1].data = salesByPeriod.map(d => (d.total_amount / 1000).toFixed(2));
                salesChart.update();
            }
            if (statusChart && newData.order_statuses) {
                statusChart.data.datasets[0].data = newData.order_statuses.map(d => d.count);
                statusChart.data.labels = newData.order_statuses.map(d => getStatusLabel(d.status));
                statusChart.update();
            }
            if (revenueChart && newData.revenue_by_period) {
                const revenueByPeriod = newData.revenue_by_period;
                revenueChart.data.labels = revenueByPeriod.map(d => {
                    const period = d.period;
                    return period > 31 ? monthNames[period - 1] : `${period} число`;
                });
                revenueChart.data.datasets[0].data = revenueByPeriod.map(d => (d.total_paid / 1000).toFixed(2));
                revenueChart.update();
            }
        }
        
        function getStatusLabel(status) {
            const labels = {
                'new': 'Новый',
                'processing': 'В обработке',
                'production': 'В производстве',
                'ready': 'Готов',
                'shipped': 'Отгружен',
                'completed': 'Завершен',
                'cancelled': 'Отменен'
            };
            return labels[status] || status;
        }
        
        // Инициализация графиков
        document.addEventListener('DOMContentLoaded', function() {
            // График продаж по месяцам
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesData = <?php echo json_encode($reportData['sales_by_month'] ?? []); ?>;
            
            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: salesData.map(d => d.month + ' мес.'),
                    datasets: [{
                        label: 'Заказы',
                        data: salesData.map(d => d.order_count),
                        borderColor: '#1a5f7a',
                        backgroundColor: 'rgba(26, 95, 122, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Сумма (тыс. BYN)',
                        data: salesData.map(d => (d.total_amount / 1000).toFixed(2)),
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Количество заказов'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Сумма (тыс. BYN)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            
            // График статусов заказов
            const statusCtx = document.getElementById('statusChart').getContext('2d');
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
            
            statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusData.map(d => getStatusLabel(d.status)),
                    datasets: [{
                        data: statusData.map(d => d.count),
                        backgroundColor: statusData.map(d => statusColors[d.status] || '#95a5a6')
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // График выручки
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = <?php echo json_encode($reportData['revenue_by_month'] ?? []); ?>;
            
            revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: revenueData.map(d => d.month + ' мес.'),
                    datasets: [{
                        label: 'Выручка (тыс. BYN)',
                        data: revenueData.map(d => (d.total_paid / 1000).toFixed(2)),
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderColor: '#27ae60',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Сумма (тыс. BYN)'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>

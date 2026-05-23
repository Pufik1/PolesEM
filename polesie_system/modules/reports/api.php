<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();

// Получаем параметры фильтров
$period = $_GET['period'] ?? 'month';
$reportType = $_GET['type'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

// Определяем диапазон дат
$whereDateClause = '';
$params = [];

if ($dateFrom && $dateTo) {
    $whereDateClause = "WHERE order_date BETWEEN :date_from AND :date_to";
    $params[':date_from'] = $dateFrom;
    $params[':date_to'] = $dateTo;
} else {
    switch ($period) {
        case 'today':
            $whereDateClause = "WHERE order_date = CURRENT_DATE()";
            break;
        case 'week':
            $whereDateClause = "WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereDateClause = "WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)";
            break;
        case 'quarter':
            $whereDateClause = "WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)";
            break;
        case 'year':
            $whereDateClause = "WHERE YEAR(order_date) = YEAR(CURRENT_DATE())";
            break;
        default:
            $whereDateClause = "WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)";
    }
}

$response = [];

try {
    // Продажи по дням/месяцам (в зависимости от периода)
    if ($reportType === 'all' || $reportType === 'sales') {
        $groupBy = in_array($period, ['year', 'quarter']) ? 'MONTH(order_date)' : 'DATE(order_date)';
        $labelFormat = in_array($period, ['year', 'quarter']) ? 'MONTH(order_date)' : 'DAY(order_date)';
        
        $stmt = $pdo->prepare("
            SELECT 
                {$groupBy} as period,
                COUNT(*) as order_count,
                SUM(final_amount) as total_amount
            FROM orders 
            {$whereDateClause}
            GROUP BY {$groupBy}
            ORDER BY period
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['sales_by_period'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Топ клиентов
    if ($reportType === 'all' || $reportType === 'sales') {
        $stmt = $pdo->prepare("
            SELECT 
                c.company_name,
                COUNT(o.id) as order_count,
                SUM(o.final_amount) as total_amount
            FROM orders o
            JOIN clients c ON o.client_id = c.id
            {$whereDateClause}
            AND o.status != 'cancelled'
            GROUP BY c.id, c.company_name
            ORDER BY total_amount DESC
            LIMIT 10
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['top_clients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Статусы заказов
    if ($reportType === 'all' || $reportType === 'sales') {
        $stmt = $pdo->query("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(final_amount) as total_amount
            FROM orders
            GROUP BY status
        ");
        $response['order_statuses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Выручка по периодам
    if ($reportType === 'all' || $reportType === 'finance') {
        $groupBy = in_array($period, ['year', 'quarter']) ? 'MONTH(paid_date)' : 'DATE(paid_date)';
        
        $stmt = $pdo->prepare("
            SELECT 
                {$groupBy} as period,
                COUNT(*) as payment_count,
                SUM(paid_amount) as total_paid
            FROM orders
            WHERE paid_date IS NOT NULL
            " . str_replace('order_date', 'paid_date', $whereDateClause) . "
            GROUP BY {$groupBy}
            ORDER BY period
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['revenue_by_period'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Товары с низким запасом
    if ($reportType === 'all' || $reportType === 'warehouse') {
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
            LIMIT 20
        ");
        $response['low_stock'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Продукция в производстве
    if ($reportType === 'all' || $reportType === 'production') {
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
        $response['production_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Общая статистика
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders {$whereDateClause}");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $response['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->prepare("SELECT SUM(final_amount) as total FROM orders WHERE status = 'completed' {$whereDateClause}");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $response['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Выручка за текущий месяц
    $stmt = $pdo->query("
        SELECT SUM(final_amount) as total 
        FROM orders 
        WHERE status = 'completed' 
        AND MONTH(order_date) = MONTH(CURRENT_DATE())
        AND YEAR(order_date) = YEAR(CURRENT_DATE())
    ");
    $response['month_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Статистика производства по статусам
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
    $prodStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['production_planned'] = (int)($prodStats['planned_count'] ?? 0);
    $response['production_in_progress'] = (int)($prodStats['in_progress_count'] ?? 0);
    $response['production_completed'] = (int)($prodStats['completed_count'] ?? 0);
    $response['production_cancelled'] = (int)($prodStats['cancelled_count'] ?? 0);
    $response['production_planned_qty'] = (int)($prodStats['planned_quantity'] ?? 0);
    $response['production_in_progress_qty'] = (int)($prodStats['in_progress_quantity'] ?? 0);
    $response['production_completed_qty'] = (int)($prodStats['completed_quantity'] ?? 0);
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $response['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $response['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode(['success' => true, 'data' => $response]);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

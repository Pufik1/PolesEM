<?php
/**
 * Warehouse Documents - Goods Receipt and Shipment Documents
 * For OAO "Polesieelectromash" ERP System
 */

require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

$pdo = getDBConnection();
$error = '';
$success = '';
$userFullName = $_SESSION['full_name'];
$userRole = $_SESSION['user_role'];
$initials = strtoupper(substr($userFullName, 0, 1));

// Get filter parameters
$filterType = $_GET['type'] ?? 'all'; // all, receipt, shipment
$filterStatus = $_GET['status'] ?? 'all';
$filterDateFrom = $_GET['date_from'] ?? date('Y-m-01');
$filterDateTo = $_GET['date_to'] ?? date('Y-m-d');

try {
    // Build query for goods receipt documents
    $receiptQuery = "
        SELECT grd.*, 
               u.full_name as created_by_name,
               p.production_number as production_order_number,
               wc.center_name as warehouse_name
        FROM goods_receipt_documents grd
        LEFT JOIN users u ON grd.created_by = u.id
        LEFT JOIN production_orders p ON grd.production_order_id = p.id
        LEFT JOIN work_centers wc ON grd.warehouse_id = wc.id
        WHERE 1=1
    ";
    $receiptParams = [];
    
    if ($filterType === 'receipt' || $filterType === 'all') {
        if ($filterStatus !== 'all') {
            $receiptQuery .= " AND grd.status = :status";
            $receiptParams[':status'] = $filterStatus;
        }
        if (!empty($filterDateFrom)) {
            $receiptQuery .= " AND grd.receipt_date >= :date_from";
            $receiptParams[':date_from'] = $filterDateFrom;
        }
        if (!empty($filterDateTo)) {
            $receiptQuery .= " AND grd.receipt_date <= :date_to";
            $receiptParams[':date_to'] = $filterDateTo;
        }
        
        $receiptQuery .= " ORDER BY grd.created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($receiptQuery);
        $stmt->execute($receiptParams);
        $receiptDocuments = $stmt->fetchAll();
    } else {
        $receiptDocuments = [];
    }
    
    // Build query for shipment documents
    $shipmentQuery = "
        SELECT sd.*, 
               u.full_name as created_by_name,
               o.order_number as order_number,
               c.company_name as customer_name,
               wc.center_name as warehouse_name
        FROM shipment_documents sd
        LEFT JOIN users u ON sd.created_by = u.id
        LEFT JOIN orders o ON sd.order_id = o.id
        LEFT JOIN clients c ON sd.customer_id = c.id
        LEFT JOIN work_centers wc ON sd.warehouse_from_id = wc.id
        WHERE 1=1
    ";
    $shipmentParams = [];
    
    if ($filterType === 'shipment' || $filterType === 'all') {
        if ($filterStatus !== 'all') {
            $shipmentQuery .= " AND sd.status = :status";
            $shipmentParams[':status'] = $filterStatus;
        }
        if (!empty($filterDateFrom)) {
            $shipmentQuery .= " AND sd.shipment_date >= :date_from";
            $shipmentParams[':date_from'] = $filterDateFrom;
        }
        if (!empty($filterDateTo)) {
            $shipmentQuery .= " AND sd.shipment_date <= :date_to";
            $shipmentParams[':date_to'] = $filterDateTo;
        }
        
        $shipmentQuery .= " ORDER BY sd.created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($shipmentQuery);
        $stmt->execute($shipmentParams);
        $shipmentDocuments = $stmt->fetchAll();
    } else {
        $shipmentDocuments = [];
    }
    
} catch (Exception $e) {
    $error = 'Ошибка: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Документы склада - Полесьеэлектромаш</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-form .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-form label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #374151;
        }
        
        .filter-form .form-control {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .data-table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .text-center {
            text-align: center;
            color: #6b7280;
            padding: 20px !important;
        }
        
        /* Стили для кнопки "глазик" удалены - кнопка больше не используется */
    </style>
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
            $basePath = '../../';
            include '../../includes/sidebar.php'; 
            ?>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <div class="header-title">
                        <h1>Документы склада</h1>
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
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h2 class="card-title">Фильтры</h2>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="filter-form" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                            <div class="form-group" style="flex: 1; min-width: 150px;">
                                <label for="filter_type">Тип документа</label>
                                <select id="filter_type" name="type" class="form-control">
                                    <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>Все документы</option>
                                    <option value="receipt" <?php echo $filterType === 'receipt' ? 'selected' : ''; ?>>Прием на склад</option>
                                    <option value="shipment" <?php echo $filterType === 'shipment' ? 'selected' : ''; ?>>Отгрузка</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="flex: 1; min-width: 150px;">
                                <label for="filter_status">Статус</label>
                                <select id="filter_status" name="status" class="form-control">
                                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Все статусы</option>
                                    <option value="draft" <?php echo $filterStatus === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                                    <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Подтвержден</option>
                                    <option value="posted" <?php echo $filterStatus === 'posted' ? 'selected' : ''; ?>>Проведен</option>
                                    <option value="shipped" <?php echo $filterStatus === 'shipped' ? 'selected' : ''; ?>>Отгружен</option>
                                    <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                                    <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="flex: 1; min-width: 150px;">
                                <label for="filter_date_from">Дата с</label>
                                <input type="date" id="filter_date_from" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                            </div>
                            
                            <div class="form-group" style="flex: 1; min-width: 150px;">
                                <label for="filter_date_to">Дата по</label>
                                <input type="date" id="filter_date_to" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filterDateTo); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Применить
                            </button>
                            
                            <a href="documents.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Сбросить
                            </a>
                        </form>
                    </div>
                </div>
        
        <!-- Goods Receipt Documents -->
        <?php if ($filterType === 'all' || $filterType === 'receipt'): ?>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-download" style="color: #10b981;"></i> Акты приема товаров
                </h2>
                <a href="../warehouse/index.php#income" class="btn btn-sm btn-success" style="float: right;">
                    <i class="fas fa-plus"></i> Создать прием
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>№ документа</th>
                            <th>Дата</th>
                            <th>Тип</th>
                            <th>Позиций</th>
                            <th>Количество</th>
                            <th>Склад</th>
                            <th>Статус</th>
                            <th>Создан</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($receiptDocuments)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Документы не найдены</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($receiptDocuments as $doc): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($doc['receipt_number']); ?></strong></td>
                                <td><?php echo date('d.m.Y', strtotime($doc['receipt_date'])); ?></td>
                                <td>
                                    <?php if ($doc['receipt_type'] === 'from_production'): ?>
                                        <span class="badge badge-info">Из производства</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">От поставщика</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $doc['total_items']; ?></td>
                                <td><?php echo $doc['total_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($doc['warehouse_name'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'draft' => 'badge-secondary',
                                        'confirmed' => 'badge-info',
                                        'posted' => 'badge-success',
                                        'cancelled' => 'badge-danger'
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Черновик',
                                        'confirmed' => 'Подтвержден',
                                        'posted' => 'Проведен',
                                        'cancelled' => 'Отменен'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statusBadges[$doc['status']] ?? 'badge-secondary'; ?>">
                                        <?php echo $statusLabels[$doc['status']] ?? $doc['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($doc['created_by_name']); ?></td>
                                <td>
                                    <a href="print_receipt.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-icon btn-primary" target="_blank" title="Печать">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="edit_receipt.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-icon btn-warning" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Shipment Documents -->
        <?php if ($filterType === 'all' || $filterType === 'shipment'): ?>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-upload" style="color: #3b82f6;"></i> Накладные на отгрузку
                </h2>
                <a href="../warehouse/index.php#shipment" class="btn btn-sm btn-info" style="float: right;">
                    <i class="fas fa-plus"></i> Создать отгрузку
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>№ документа</th>
                            <th>Дата</th>
                            <th>Клиент</th>
                            <th>Позиций</th>
                            <th>Количество</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Создан</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shipmentDocuments)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Документы не найдены</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($shipmentDocuments as $doc): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($doc['shipment_number']); ?></strong></td>
                                <td><?php echo date('d.m.Y', strtotime($doc['shipment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($doc['customer_name'] ?? 'Прямая продажа'); ?></td>
                                <td><?php echo $doc['total_items']; ?></td>
                                <td><?php echo $doc['total_quantity']; ?></td>
                                <td><?php echo number_format($doc['total_cost'], 2); ?> BYN</td>
                                <td>
                                    <?php
                                    $shipmentStatusBadges = [
                                        'draft' => 'badge-secondary',
                                        'confirmed' => 'badge-info',
                                        'shipped' => 'badge-primary',
                                        'delivered' => 'badge-success',
                                        'cancelled' => 'badge-danger'
                                    ];
                                    $shipmentStatusLabels = [
                                        'draft' => 'Черновик',
                                        'confirmed' => 'Подтвержден',
                                        'shipped' => 'Отгружен',
                                        'delivered' => 'Доставлен',
                                        'cancelled' => 'Отменен'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $shipmentStatusBadges[$doc['status']] ?? 'badge-secondary'; ?>">
                                        <?php echo $shipmentStatusLabels[$doc['status']] ?? $doc['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($doc['created_by_name']); ?></td>
                                <td>
                                    <a href="print_shipment.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-icon btn-primary" target="_blank" title="Печать">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="edit_shipment.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-icon btn-warning" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Back to Warehouse button -->
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Вернуться на склад
            </a>
        </div>
        
            </div>
            
            <script src="../../assets/js/main.js"></script>
            <script>
            // Удалены функции viewReceiptDetails и viewShipmentDetails так как кнопка "глазик" удалена
            </script>
            
        </div>
    </div>
</body>
</html>

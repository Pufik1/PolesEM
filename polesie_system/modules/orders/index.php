<?php
/**
 * Orders module for OAO "Polesieelectromash" ERP System
 * Complete order management with documents: Contract, Invoice, Delivery Note, Transport Waybill
 */

require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

// Check permissions
if (!hasRole(['admin', 'director', 'manager', 'accountant'])) {
    redirect('../../dashboard.php');
}

$pdo = getDBConnection();
$error = '';
$success = '';
$editOrder = null;

// Handle order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasRole(['admin', 'manager'])) {
    try {
        if ($_POST['action'] === 'create') {
            $pdo->beginTransaction();
            
            $orderNumber = trim($_POST['order_number']);
            $clientId = (int)$_POST['client_id'];
            $orderDate = $_POST['order_date'];
            $deliveryDate = $_POST['delivery_date'] ?: null;
            $deliveryAddress = trim($_POST['delivery_address']);
            $notes = trim($_POST['notes']);
            $contractNumber = trim($_POST['contract_number']);
            $contractDate = $_POST['contract_date'] ?: null;
            
            // Calculate totals from order items
            $totalAmount = 0;
            $discountAmount = 0;
            $items = $_POST['items'] ?? [];
            
            foreach ($items as $item) {
                $quantity = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $itemDiscount = (float)($item['discount_percent'] ?? 0);
                $itemTotal = $quantity * $unitPrice;
                $itemDiscountAmount = $itemTotal * ($itemDiscount / 100);
                $totalAmount += $itemTotal;
                $discountAmount += $itemDiscountAmount;
            }
            
            $finalAmount = $totalAmount - $discountAmount;
            
            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (order_number, client_id, user_id, order_date, delivery_date, 
                                   status, total_amount, discount_amount, final_amount, delivery_address, notes, 
                                   contract_number, contract_date) 
                                   VALUES (:order_number, :client_id, :user_id, :order_date, :delivery_date, 
                                   'new', :total_amount, :discount_amount, :final_amount, :delivery_address, :notes, 
                                   :contract_number, :contract_date)");
            $stmt->execute([
                ':order_number' => $orderNumber,
                ':client_id' => $clientId,
                ':user_id' => $_SESSION['user_id'],
                ':order_date' => $orderDate,
                ':delivery_date' => $deliveryDate,
                ':total_amount' => $totalAmount,
                ':discount_amount' => $discountAmount,
                ':final_amount' => $finalAmount,
                ':delivery_address' => $deliveryAddress,
                ':notes' => $notes,
                ':contract_number' => $contractNumber,
                ':contract_date' => $contractDate
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Insert order items
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, 
                                        discount_percent, total_price) 
                                       VALUES (:order_id, :product_id, :quantity, :unit_price, :discount_percent, :total_price)");
            
            foreach ($items as $item) {
                $quantity = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $itemDiscount = (float)($item['discount_percent'] ?? 0);
                $itemTotal = $quantity * $unitPrice * (1 - $itemDiscount / 100);
                
                $stmtItem->execute([
                    ':order_id' => $orderId,
                    ':product_id' => (int)$item['product_id'],
                    ':quantity' => $quantity,
                    ':unit_price' => $unitPrice,
                    ':discount_percent' => $itemDiscount,
                    ':total_price' => $itemTotal
                ]);
            }
            
            // Create contract document
            if (!empty($contractNumber)) {
                $stmtDoc = $pdo->prepare("INSERT INTO order_documents (order_id, document_type, document_number, document_date, status) 
                                          VALUES (:order_id, 'contract', :doc_number, :doc_date, 'draft')");
                $stmtDoc->execute([
                    ':order_id' => $orderId,
                    ':doc_number' => $contractNumber,
                    ':doc_date' => $contractDate ?? $orderDate
                ]);
            }
            
            $pdo->commit();
            
            logActivity($pdo, $_SESSION['user_id'], 'order_created', 'orders', $orderId);
            $success = 'Заказ успешно создан';
            
            header('Location: ' . $_SERVER['PHP_SELF'] . '?view=' . $orderId . '&success=' . urlencode($success));
            exit;
            
        } elseif ($_POST['action'] === 'update_status') {
            $orderId = (int)$_POST['order_id'];
            $newStatus = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $orderId]);
            
            logActivity($pdo, $_SESSION['user_id'], 'order_status_updated', 'orders', $orderId);
            $success = 'Статус заказа обновлен';
            
        } elseif ($_POST['action'] === 'update_payment') {
            $orderId = (int)$_POST['order_id'];
            $paymentStatus = $_POST['payment_status'];
            $paidAmount = (float)($_POST['paid_amount'] ?? 0);
            $paidDate = $_POST['paid_date'] ?: null;
            
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = :payment_status, 
                                   updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([':payment_status' => $paymentStatus, ':id' => $orderId]);
            
            // Update invoice if exists
            if ($paidDate) {
                $stmtInv = $pdo->prepare("UPDATE invoices SET paid_amount = :paid_amount, paid_date = :paid_date, 
                                          payment_status = :payment_status WHERE order_id = :order_id");
                $stmtInv->execute([
                    ':paid_amount' => $paidAmount,
                    ':paid_date' => $paidDate,
                    ':payment_status' => $paymentStatus,
                    ':order_id' => $orderId
                ]);
            }
            
            logActivity($pdo, $_SESSION['user_id'], 'order_payment_updated', 'orders', $orderId);
            $success = 'Информация об оплате обновлена';
        }
        
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . urlencode($success));
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Ошибка при сохранении данных заказа: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Get order for viewing
$viewOrder = null;
$orderItems = [];
$orderDocuments = [];
$invoice = null;
$deliveryNote = null;
$transportWaybill = null;

if (isset($_GET['view'])) {
    $orderId = (int)$_GET['view'];
    
    $stmt = $pdo->prepare("SELECT o.*, c.company_name, c.inn as client_inn, c.address as client_address, 
                           c.contact_person, c.phone, c.email, u.full_name as manager_name
                           FROM orders o
                           LEFT JOIN clients c ON o.client_id = c.id
                           LEFT JOIN users u ON o.user_id = u.id
                           WHERE o.id = :id");
    $stmt->execute([':id' => $orderId]);
    $viewOrder = $stmt->fetch();
    
    if ($viewOrder) {
        // Get order items
        $stmtItems = $pdo->prepare("SELECT oi.*, p.product_name, p.product_code 
                                    FROM order_items oi
                                    LEFT JOIN products p ON oi.product_id = p.id
                                    WHERE oi.order_id = :order_id");
        $stmtItems->execute([':order_id' => $orderId]);
        $orderItems = $stmtItems->fetchAll();
        
        // Get documents
        $stmtDocs = $pdo->prepare("SELECT * FROM order_documents WHERE order_id = :order_id ORDER BY document_type, created_at");
        $stmtDocs->execute([':order_id' => $orderId]);
        $orderDocuments = $stmtDocs->fetchAll();
        
        // Get invoice
        $stmtInv = $pdo->prepare("SELECT * FROM invoices WHERE order_id = :order_id");
        $stmtInv->execute([':order_id' => $orderId]);
        $invoice = $stmtInv->fetch();
        
        // Get delivery note
        $stmtDN = $pdo->prepare("SELECT dn.*, u.full_name as creator_name 
                                 FROM delivery_notes dn
                                 LEFT JOIN users u ON dn.created_by = u.id
                                 WHERE dn.order_id = :order_id");
        $stmtDN->execute([':order_id' => $orderId]);
        $deliveryNote = $stmtDN->fetch();
        
        if ($deliveryNote) {
            // Get delivery note items
            $stmtDNI = $pdo->prepare("SELECT dni.*, p.product_name, p.product_code 
                                      FROM delivery_note_items dni
                                      LEFT JOIN products p ON dni.product_id = p.id
                                      WHERE dni.delivery_note_id = :dn_id");
            $stmtDNI->execute([':dn_id' => $deliveryNote['id']]);
            $deliveryNoteItems = $stmtDNI->fetchAll();
        }
        
        // Get transport waybill
        $stmtTW = $pdo->prepare("SELECT tw.*, u.full_name as creator_name 
                                 FROM transport_waybills tw
                                 LEFT JOIN users u ON tw.created_by = u.id
                                 WHERE tw.order_id = :order_id");
        $stmtTW->execute([':order_id' => $orderId]);
        $transportWaybill = $stmtTW->fetch();
    }
}

// Get all orders for list view
$orders = [];
if (!isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT o.*, c.company_name, u.full_name as manager_name
                           FROM orders o
                           LEFT JOIN clients c ON o.client_id = c.id
                           LEFT JOIN users u ON o.user_id = u.id
                           ORDER BY o.created_at DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll();
}

// Get clients for dropdown
$stmtClients = $pdo->query("SELECT id, company_name, client_code FROM clients WHERE is_active = 1 ORDER BY company_name");
$clients = $stmtClients->fetchAll();

// Get products for dropdown
$stmtProducts = $pdo->query("SELECT id, product_code, product_name, base_price, stock_quantity 
                             FROM products WHERE is_active = 1 ORDER BY product_name");
$products = $stmtProducts->fetchAll();

// Status translations
$statusLabels = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'production' => 'В производстве',
    'ready' => 'Готов к отгрузке',
    'shipped' => 'Отгружен',
    'completed' => 'Выполнен',
    'cancelled' => 'Отменен'
];

$paymentStatusLabels = [
    'unpaid' => 'Не оплачен',
    'partial' => 'Частично оплачен',
    'paid' => 'Оплачен',
    'overdue' => 'Просрочен'
];

$statusColors = [
    'new' => 'info',
    'processing' => 'warning',
    'production' => 'warning',
    'ready' => 'success',
    'shipped' => 'primary',
    'completed' => 'success',
    'cancelled' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-detail-section {
            margin-bottom: 30px;
        }
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        .document-card {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .document-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-color);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 13px;
        }
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .totals-box {
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .total-row.final {
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
        }
        .action-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-marker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .timeline-content {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .print-btn {
            background: var(--secondary-color);
            color: white;
        }
        @media print {
            .sidebar, .header, .action-bar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
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
        <div class="main-content">
            <header class="header">
                <div class="header-left">
                    <div class="header-title">
                        <h1><?php echo $viewOrder ? 'Заказ №' . htmlspecialchars($viewOrder['order_number']) : 'Управление заказами'; ?></h1>
                    </div>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                            <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            <div class="content-area">
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($viewOrder): ?>
                    <!-- Order Detail View -->
                    
                    <div class="action-bar no-print">
                        <a href="?<?php echo http_build_query(array_filter($_GET)); ?>" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Печать
                        </a>
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Назад к списку
                        </a>
                        <?php if (hasRole(['admin', 'manager'])): ?>
                        <button class="btn btn-success" onclick="document.getElementById('statusModal').classList.add('active')">
                            <i class="fas fa-edit"></i> Изменить статус
                        </button>
                        <button class="btn btn-primary" onclick="document.getElementById('paymentModal').classList.add('active')">
                            <i class="fas fa-money-bill"></i> Оплата
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Order Info Card -->
                    <div class="card">
                        <div class="detail-header">
                            <h2 class="card-title">
                                <i class="fas fa-file-invoice"></i> Заказ №<?php echo htmlspecialchars($viewOrder['order_number']); ?>
                            </h2>
                            <div class="status-badges">
                                <span class="badge badge-<?php echo $statusColors[$viewOrder['status']] ?? 'info'; ?>">
                                    <?php echo $statusLabels[$viewOrder['status']] ?? $viewOrder['status']; ?>
                                </span>
                                <span class="badge badge-<?php echo $viewOrder['payment_status'] == 'paid' ? 'success' : ($viewOrder['payment_status'] == 'partial' ? 'warning' : 'danger'); ?>">
                                    <?php echo $paymentStatusLabels[$viewOrder['payment_status']] ?? $viewOrder['payment_status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Дата заказа</span>
                                <span class="info-value"><?php echo date('d.m.Y', strtotime($viewOrder['order_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Дата доставки</span>
                                <span class="info-value"><?php echo $viewOrder['delivery_date'] ? date('d.m.Y', strtotime($viewOrder['delivery_date'])) : 'Не указана'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Менеджер</span>
                                <span class="info-value"><?php echo htmlspecialchars($viewOrder['manager_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Контактное лицо</span>
                                <span class="info-value"><?php echo htmlspecialchars($viewOrder['contact_person']); ?></span>
                            </div>
                        </div>
                        
                        <div class="document-card">
                            <div class="document-title"><i class="fas fa-building"></i> Клиент</div>
                            <div class="info-grid" style="margin-top: 15px;">
                                <div class="info-item">
                                    <span class="info-label">Наименование</span>
                                    <span class="info-value"><?php echo htmlspecialchars($viewOrder['company_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">УНП</span>
                                    <span class="info-value"><?php echo htmlspecialchars($viewOrder['client_inn']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Адрес</span>
                                    <span class="info-value"><?php echo htmlspecialchars($viewOrder['client_address']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Телефон</span>
                                    <span class="info-value"><?php echo htmlspecialchars($viewOrder['phone']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?php echo htmlspecialchars($viewOrder['email']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($viewOrder['delivery_address']): ?>
                        <div class="document-card" style="border-left-color: var(--success-color);">
                            <div class="document-title"><i class="fas fa-truck"></i> Адрес доставки</div>
                            <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($viewOrder['delivery_address'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contract Document -->
                    <?php 
                    $contract = null;
                    foreach ($orderDocuments as $doc) {
                        if ($doc['document_type'] == 'contract') {
                            $contract = $doc;
                            break;
                        }
                    }
                    ?>
                    <div class="card order-detail-section">
                        <div class="detail-header">
                            <h2 class="card-title">
                                <i class="fas fa-file-contract"></i> Договор поставки
                            </h2>
                            <?php if ($contract): ?>
                            <span class="badge badge-<?php echo $contract['status'] == 'signed' ? 'success' : 'warning'; ?>">
                                <?php echo $contract['status'] == 'signed' ? 'Подписан' : 'Проект'; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($contract || $viewOrder['contract_number']): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Номер договора</span>
                                <span class="info-value"><?php echo htmlspecialchars($contract['document_number'] ?? $viewOrder['contract_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Дата договора</span>
                                <span class="info-value"><?php echo date('d.m.Y', strtotime($contract['document_date'] ?? $viewOrder['contract_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Статус</span>
                                <span class="info-value"><?php echo $contract['status'] == 'signed' ? 'Подписан' : 'Проект'; ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Договор не оформлен</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="card order-detail-section">
                        <div class="detail-header">
                            <h2 class="card-title">
                                <i class="fas fa-boxes"></i> Товары в заказе
                            </h2>
                        </div>
                        
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th class="text-right">Количество</th>
                                    <th class="text-right">Цена за ед.</th>
                                    <th class="text-right">Скидка %</th>
                                    <th class="text-right">Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalItems = 0;
                                foreach ($orderItems as $index => $item): 
                                    $totalItems++;
                                ?>
                                <tr>
                                    <td><?php echo $totalItems; ?></td>
                                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td class="text-right"><?php echo $item['quantity']; ?> шт</td>
                                    <td class="text-right"><?php echo number_format($item['unit_price'], 2, ',', ' '); ?> BYN</td>
                                    <td class="text-right"><?php echo $item['discount_percent']; ?>%</td>
                                    <td class="text-right"><?php echo number_format($item['total_price'], 2, ',', ' '); ?> BYN</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="totals-section">
                            <div class="totals-box">
                                <div class="total-row">
                                    <span>Сумма:</span>
                                    <span><?php echo number_format($viewOrder['total_amount'], 2, ',', ' '); ?> BYN</span>
                                </div>
                                <div class="total-row">
                                    <span>Скидка:</span>
                                    <span>-<?php echo number_format($viewOrder['discount_amount'], 2, ',', ' '); ?> BYN</span>
                                </div>
                                <div class="total-row final">
                                    <span>Итого:</span>
                                    <span><?php echo number_format($viewOrder['final_amount'], 2, ',', ' '); ?> BYN</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoice Document -->
                    <div class="card order-detail-section">
                        <div class="detail-header">
                            <h2 class="card-title">
                                <i class="fas fa-file-invoice-dollar"></i> Счет-фактура (Инвойс)
                            </h2>
                            <?php if ($invoice): ?>
                            <span class="badge badge-<?php echo $invoice['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo $paymentStatusLabels[$invoice['payment_status']]; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($invoice): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Номер счета</span>
                                <span class="info-value"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Дата выставления</span>
                                <span class="info-value"><?php echo date('d.m.Y', strtotime($invoice['invoice_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Срок оплаты до</span>
                                <span class="info-value"><?php echo $invoice['due_date'] ? date('d.m.Y', strtotime($invoice['due_date'])) : 'Не указан'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Сумма без НДС</span>
                                <span class="info-value"><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> BYN</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">НДС</span>
                                <span class="info-value"><?php echo number_format($invoice['vat_amount'], 2, ',', ' '); ?> BYN</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Всего с НДС</span>
                                <span class="info-value"><?php echo number_format($invoice['total_with_vat'], 2, ',', ' '); ?> BYN</span>
                            </div>
                            <?php if ($invoice['paid_amount'] > 0): ?>
                            <div class="info-item">
                                <span class="info-label">Оплачено</span>
                                <span class="info-value"><?php echo number_format($invoice['paid_amount'], 2, ',', ' '); ?> BYN</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Дата оплаты</span>
                                <span class="info-value"><?php echo $invoice['paid_date'] ? date('d.m.Y', strtotime($invoice['paid_date'])) : '-'; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Счет еще не выставлен</p>
                        <?php if (hasRole(['admin', 'manager', 'accountant'])): ?>
                        <button class="btn btn-primary" onclick="createInvoice(<?php echo $orderId; ?>)">
                            <i class="fas fa-plus"></i> Выставить счет
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Delivery Note -->
                    <div class="card order-detail-section">
                        <div class="detail-header">
                            <h2 class="card-title">
                                <i class="fas fa-dolly"></i> Товарная накладная (ТН)
                            </h2>
                            <?php if ($deliveryNote): ?>
                            <span class="badge badge-success">Оформлена</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($deliveryNote): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Номер ТН</span>
                                <span class="info-value"><?php echo htmlspecialchars($deliveryNote['tn_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Дата</span>
                                <span class="info-value"><?php echo date('d.m.Y', strtotime($deliveryNote['tn_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Склад отправитель</span>
                                <span class="info-value"><?php echo htmlspecialchars($deliveryNote['warehouse_from']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Склад получатель</span>
                                <span class="info-value"><?php echo htmlspecialchars($deliveryNote['warehouse_to']); ?></span>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <h4>Грузоотправитель</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Наименование</span>
                                    <span class="info-value"><?php echo htmlspecialchars($deliveryNote['shipper_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">УНП</span>
                                    <span class="info-value"><?php echo htmlspecialchars($deliveryNote['shipper_inn']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Адрес</span>
                                    <span class="info-value"><?php echo htmlspecialchars($deliveryNote['shipper_address']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <h4>Грузополучатель</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Наименование</span>
                                    <span class="info-value"><?php echo htmlspecialchars($deliveryNote['consignee_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">УНП</span>
                                    <span class="info-value"><?php echo htmlspecialchars($deliveryNote['consignee_inn']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Адрес</span>
                                    <span class="info-value"><?php echo htmlspecialchars($deliveryNote['consignee_address']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <table class="items-table" style="margin-top: 20px;">
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th class="text-right">Кол-во</th>
                                    <th class="text-right">Ед.изм</th>
                                    <th class="text-right">Вес за ед.</th>
                                    <th class="text-right">Общий вес</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalWeight = 0;
                                foreach ($deliveryNoteItems as $index => $item): 
                                    $totalWeight += $item['total_weight'];
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td class="text-right"><?php echo $item['quantity']; ?></td>
                                    <td class="text-right"><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td class="text-right"><?php echo number_format($item['weight_per_unit'], 3, ',', ' '); ?> кг</td>
                                    <td class="text-right"><?php echo number_format($item['total_weight'], 3, ',', ' '); ?> кг</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8f9fa; font-weight: 600;">
                                    <td colspan="6" class="text-right">Общий вес:</td>
                                    <td class="text-right"><?php echo number_format($totalWeight, 3, ',', ' '); ?> кг</td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php else: ?>
                        <p class="text-muted">Товарная накладная еще не оформлена</p>
                        <?php if (hasRole(['admin', 'manager', 'warehouse_keeper'])): ?>
                        <button class="btn btn-primary" onclick="createDeliveryNote(<?php echo $orderId; ?>)">
                            <i class="fas fa-plus"></i> Оформить ТН
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Transport Waybill -->
                    <div class="card order-detail-section">
                        <div class="detail-header">
                            <h2 class="card-title">
                                <i class="fas fa-truck-moving"></i> Товарно-транспортная накладная (ТТН)
                            </h2>
                            <?php if ($transportWaybill): ?>
                            <span class="badge badge-success">Оформлена</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($transportWaybill): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Номер ТТН</span>
                                <span class="info-value"><?php echo htmlspecialchars($transportWaybill['ttn_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Дата</span>
                                <span class="info-value"><?php echo date('d.m.Y', strtotime($transportWaybill['ttn_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Автомобиль</span>
                                <span class="info-value"><?php echo htmlspecialchars($transportWaybill['vehicle_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Водитель</span>
                                <span class="info-value"><?php echo htmlspecialchars($transportWaybill['driver_name']); ?></span>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <h4>Перевозчик</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Наименование</span>
                                    <span class="info-value"><?php echo htmlspecialchars($transportWaybill['carrier_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">УНП</span>
                                    <span class="info-value"><?php echo htmlspecialchars($transportWaybill['carrier_inn']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <h4>Маршрут</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Пункт погрузки</span>
                                    <span class="info-value"><?php echo htmlspecialchars($transportWaybill['loading_point']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Пункт разгрузки</span>
                                    <span class="info-value"><?php echo htmlspecialchars($transportWaybill['unloading_point']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Расстояние</span>
                                    <span class="info-value"><?php echo $transportWaybill['distance_km']; ?> км</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Стоимость перевозки</span>
                                    <span class="info-value"><?php echo number_format($transportWaybill['freight_cost'], 2, ',', ' '); ?> BYN</span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">ТТН еще не оформлена</p>
                        <?php if (hasRole(['admin', 'manager', 'warehouse_keeper'])): ?>
                        <button class="btn btn-primary" onclick="createTransportWaybill(<?php echo $orderId; ?>)">
                            <i class="fas fa-plus"></i> Оформить ТТН
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Notes -->
                    <?php if ($viewOrder['notes']): ?>
                    <div class="card order-detail-section">
                        <div class="detail-header">
                            <h2 class="card-title">
                                <i class="fas fa-sticky-note"></i> Примечания
                            </h2>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($viewOrder['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status Change Modal -->
                    <div class="modal" id="statusModal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2>Изменить статус заказа</h2>
                                <button class="modal-close" onclick="document.getElementById('statusModal').classList.remove('active')">&times;</button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Новый статус</label>
                                        <select name="status" class="form-control" required>
                                            <?php foreach ($statusLabels as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $viewOrder['status'] == $key ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('statusModal').classList.remove('active')">Отмена</button>
                                    <button type="submit" class="btn btn-primary">Сохранить</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Payment Modal -->
                    <div class="modal" id="paymentModal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2>Регистрация оплаты</h2>
                                <button class="modal-close" onclick="document.getElementById('paymentModal').classList.remove('active')">&times;</button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_payment">
                                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Статус оплаты</label>
                                        <select name="payment_status" class="form-control" required>
                                            <option value="unpaid" <?php echo $viewOrder['payment_status'] == 'unpaid' ? 'selected' : ''; ?>>Не оплачен</option>
                                            <option value="partial" <?php echo $viewOrder['payment_status'] == 'partial' ? 'selected' : ''; ?>>Частично оплачен</option>
                                            <option value="paid" <?php echo $viewOrder['payment_status'] == 'paid' ? 'selected' : ''; ?>>Оплачен</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Сумма оплаты</label>
                                        <input type="number" step="0.01" name="paid_amount" class="form-control" value="<?php echo $invoice['paid_amount'] ?? 0; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Дата оплаты</label>
                                        <input type="date" name="paid_date" class="form-control" value="<?php echo $invoice['paid_date'] ?? date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('paymentModal').classList.remove('active')">Отмена</button>
                                    <button type="submit" class="btn btn-success">Сохранить</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Orders List View -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Все заказы</h2>
                            <?php if (hasRole(['admin', 'manager'])): ?>
                            <button class="btn btn-primary" onclick="document.getElementById('createOrderModal').classList.add('active')">
                                <i class="fas fa-plus"></i> Создать заказ
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>№ заказа</th>
                                        <th>Дата</th>
                                        <th>Клиент</th>
                                        <th>Менеджер</th>
                                        <th>Сумма</th>
                                        <th>Статус</th>
                                        <th>Оплата</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td><?php echo date('d.m.Y', strtotime($order['order_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($order['company_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['manager_name']); ?></td>
                                        <td><?php echo number_format($order['final_amount'], 2, ',', ' '); ?> BYN</td>
                                        <td>
                                            <span class="badge badge-<?php echo $statusColors[$order['status']] ?? 'info'; ?>">
                                                <?php echo $statusLabels[$order['status']] ?? $order['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'partial' ? 'warning' : 'danger'); ?>">
                                                <?php echo $paymentStatusLabels[$order['payment_status']] ?? $order['payment_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-icon btn-primary" title="Просмотр">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Create Order Modal -->
                    <div class="modal" id="createOrderModal">
                        <div class="modal-content modal-large">
                            <div class="modal-header">
                                <h2>Создать новый заказ</h2>
                                <button class="modal-close" onclick="document.getElementById('createOrderModal').classList.remove('active')">&times;</button>
                            </div>
                            <form method="POST" id="createOrderForm">
                                <input type="hidden" name="action" value="create">
                                <div class="modal-body">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Номер заказа *</label>
                                            <input type="text" name="order_number" required value="ORD-<?php echo date('Ymd'); ?>-001">
                                        </div>
                                        <div class="form-group">
                                            <label>Дата заказа *</label>
                                            <input type="date" name="order_date" required value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Клиент *</label>
                                            <select name="client_id" id="clientSelect" required onchange="updateClientInfo()">
                                                <option value="">Выберите клиента</option>
                                                <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id']; ?>">
                                                    <?php echo htmlspecialchars($client['company_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Номер договора</label>
                                            <input type="text" name="contract_number" placeholder="Например: ДОГ-2024-001">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Дата договора</label>
                                            <input type="date" name="contract_date" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Дата доставки</label>
                                            <input type="date" name="delivery_date">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Адрес доставки</label>
                                        <textarea name="delivery_address" rows="2" placeholder="Введите адрес доставки"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Товары в заказе</label>
                                        <div id="orderItemsContainer">
                                            <div class="order-item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: end;">
                                                <div>
                                                    <label style="font-size: 12px;">Товар</label>
                                                    <select name="items[0][product_id]" class="product-select" required onchange="updatePrice(this)">
                                                        <option value="">Выберите товар</option>
                                                        <?php foreach ($products as $product): ?>
                                                        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['base_price']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                                                            <?php echo htmlspecialchars($product['product_name']); ?> (<?php echo $product['product_code']; ?>) - Остаток: <?php echo $product['stock_quantity']; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label style="font-size: 12px;">Количество</label>
                                                    <input type="number" name="items[0][quantity]" value="1" min="1" required class="item-quantity" onchange="calculateRowTotal(this)">
                                                </div>
                                                <div>
                                                    <label style="font-size: 12px;">Цена</label>
                                                    <input type="number" step="0.01" name="items[0][unit_price]" value="0" required class="item-price" onchange="calculateRowTotal(this)">
                                                </div>
                                                <div>
                                                    <label style="font-size: 12px;">Скидка %</label>
                                                    <input type="number" step="0.1" name="items[0][discount_percent]" value="0" min="0" max="100" class="item-discount" onchange="calculateRowTotal(this)">
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)" style="margin-bottom: 5px;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="addItemRow()">
                                            <i class="fas fa-plus"></i> Добавить товар
                                        </button>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Примечания</label>
                                        <textarea name="notes" rows="3" placeholder="Дополнительная информация по заказу"></textarea>
                                    </div>
                                    
                                    <div class="totals-box" style="margin-top: 20px;">
                                        <div class="total-row">
                                            <span>Сумма:</span>
                                            <span id="totalAmount">0.00 BYN</span>
                                        </div>
                                        <div class="total-row">
                                            <span>Скидка:</span>
                                            <span id="discountAmount">0.00 BYN</span>
                                        </div>
                                        <div class="total-row final">
                                            <span>Итого:</span>
                                            <span id="finalAmount">0.00 BYN</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createOrderModal').classList.remove('active')">Отмена</button>
                                    <button type="submit" class="btn btn-primary">Создать заказ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        let itemCounter = 1;
        
        function addItemRow() {
            const container = document.getElementById('orderItemsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'order-item-row';
            newRow.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: end;';
            newRow.innerHTML = `
                <div>
                    <label style="font-size: 12px;">Товар</label>
                    <select name="items[${itemCounter}][product_id]" class="product-select" required onchange="updatePrice(this)">
                        <option value="">Выберите товар</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['base_price']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?> (<?php echo $product['product_code']; ?>) - Остаток: <?php echo $product['stock_quantity']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px;">Количество</label>
                    <input type="number" name="items[${itemCounter}][quantity]" value="1" min="1" required class="item-quantity" onchange="calculateRowTotal(this)">
                </div>
                <div>
                    <label style="font-size: 12px;">Цена</label>
                    <input type="number" step="0.01" name="items[${itemCounter}][unit_price]" value="0" required class="item-price" onchange="calculateRowTotal(this)">
                </div>
                <div>
                    <label style="font-size: 12px;">Скидка %</label>
                    <input type="number" step="0.1" name="items[${itemCounter}][discount_percent]" value="0" min="0" max="100" class="item-discount" onchange="calculateRowTotal(this)">
                </div>
                <div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)" style="margin-bottom: 5px;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            itemCounter++;
        }
        
        function removeItemRow(btn) {
            const rows = document.querySelectorAll('.order-item-row');
            if (rows.length > 1) {
                btn.closest('.order-item-row').remove();
                calculateTotals();
            } else {
                alert('Должен быть хотя бы один товар в заказе');
            }
        }
        
        function updatePrice(select) {
            const row = select.closest('.order-item-row');
            const priceInput = row.querySelector('.item-price');
            const option = select.options[select.selectedIndex];
            if (option.dataset.price) {
                priceInput.value = option.dataset.price;
                calculateRowTotal(priceInput);
            }
        }
        
        function calculateRowTotal(input) {
            const row = input.closest('.order-item-row');
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
            
            const subtotal = quantity * price;
            const discountAmount = subtotal * (discount / 100);
            const total = subtotal - discountAmount;
            
            calculateTotals();
        }
        
        function calculateTotals() {
            let totalAmount = 0;
            let discountAmount = 0;
            
            document.querySelectorAll('.order-item-row').forEach(row => {
                const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
                
                const subtotal = quantity * price;
                const itemDiscount = subtotal * (discount / 100);
                
                totalAmount += subtotal;
                discountAmount += itemDiscount;
            });
            
            const finalAmount = totalAmount - discountAmount;
            
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2) + ' BYN';
            document.getElementById('discountAmount').textContent = '-' + discountAmount.toFixed(2) + ' BYN';
            document.getElementById('finalAmount').textContent = finalAmount.toFixed(2) + ' BYN';
        }
        
        function updateClientInfo() {
            // Client info update (no discount logic needed)
        }
        
        function createInvoice(orderId) {
            alert('Функция создания счета будет реализована в следующем обновлении');
            // Здесь можно добавить AJAX запрос для создания счета
        }
        
        function createDeliveryNote(orderId) {
            alert('Функция создания товарной накладной будет реализована в следующем обновлении');
        }
        
        function createTransportWaybill(orderId) {
            alert('Функция создания ТТН будет реализована в следующем обновлении');
        }
        
        // Initialize calculations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const firstRow = document.querySelector('.order-item-row');
            if (firstRow) {
                const firstSelect = firstRow.querySelector('.product-select');
                if (firstSelect.selectedIndex > 0) {
                    updatePrice(firstSelect);
                }
            }
        });
    </script>
</body>
</html>

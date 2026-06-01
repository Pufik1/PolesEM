<?php
/**
 * Finance module for OAO "Polesieelectromash" ERP System
 * Financial management - invoices, payments, financial reports
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
$editInvoice = null;

// Handle invoice addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasRole(['admin', 'director', 'manager', 'accountant'])) {
    try {
        if ($_POST['action'] === 'add') {
            $invoiceNumber = trim($_POST['invoice_number']);
            $orderId = (int)$_POST['order_id'];
            $invoiceDate = $_POST['invoice_date'];
            $dueDate = $_POST['due_date'] ?: null;
            $totalAmount = (float)$_POST['total_amount'];
            $vatAmount = (float)($_POST['vat_amount'] ?? 0);
            $totalWithVat = $totalAmount + $vatAmount;
            $notes = trim($_POST['notes'] ?? '');
            
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, order_id, invoice_date, due_date, total_amount, vat_amount, total_with_vat, notes) 
                                   VALUES (:invoice_number, :order_id, :invoice_date, :due_date, :total_amount, :vat_amount, :total_with_vat, :notes)");
            $stmt->execute([
                ':invoice_number' => $invoiceNumber,
                ':order_id' => $orderId,
                ':invoice_date' => $invoiceDate,
                ':due_date' => $dueDate,
                ':total_amount' => $totalAmount,
                ':vat_amount' => $vatAmount,
                ':total_with_vat' => $totalWithVat,
                ':notes' => $notes
            ]);
            
            $newInvoiceId = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'invoice_created', 'invoices', $newInvoiceId);
            $success = 'Счет успешно создан';
            
        } elseif ($_POST['action'] === 'edit') {
            $invoiceId = (int)$_POST['invoice_id'];
            $invoiceDate = $_POST['invoice_date'];
            $dueDate = $_POST['due_date'] ?: null;
            $totalAmount = (float)$_POST['total_amount'];
            $vatAmount = (float)($_POST['vat_amount'] ?? 0);
            $totalWithVat = $totalAmount + $vatAmount;
            $notes = trim($_POST['notes'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE invoices SET invoice_date = :invoice_date, due_date = :due_date, 
                                   total_amount = :total_amount, vat_amount = :vat_amount, total_with_vat = :total_with_vat, notes = :notes 
                                   WHERE id = :id");
            $stmt->execute([
                ':id' => $invoiceId,
                ':invoice_date' => $invoiceDate,
                ':due_date' => $dueDate,
                ':total_amount' => $totalAmount,
                ':vat_amount' => $vatAmount,
                ':total_with_vat' => $totalWithVat,
                ':notes' => $notes
            ]);
            
            logActivity($pdo, $_SESSION['user_id'], 'invoice_updated', 'invoices', $invoiceId);
            $success = 'Данные счета успешно обновлены';
        } elseif ($_POST['action'] === 'mark_paid' && hasRole(['admin', 'director', 'manager', 'accountant'])) {
            $invoiceId = (int)$_POST['invoice_id'];
            $paidAmount = (float)$_POST['paid_amount'];
            $paidDate = $_POST['paid_date'] ?: date('Y-m-d');
            
            // Determine payment status
            $stmt = $pdo->prepare("SELECT total_with_vat FROM invoices WHERE id = :id");
            $stmt->execute([':id' => $invoiceId]);
            $invoice = $stmt->fetch();
            
            if ($paidAmount >= $invoice['total_with_vat']) {
                $paymentStatus = 'paid';
            } else {
                $paymentStatus = 'partial';
            }
            
            $stmt = $pdo->prepare("UPDATE invoices SET payment_status = :payment_status, paid_amount = :paid_amount, paid_date = :paid_date WHERE id = :id");
            $stmt->execute([
                ':id' => $invoiceId,
                ':payment_status' => $paymentStatus,
                ':paid_amount' => $paidAmount,
                ':paid_date' => $paidDate
            ]);
            
            logActivity($pdo, $_SESSION['user_id'], 'invoice_payment', 'invoices', $invoiceId);
            $success = 'Платеж зарегистрирован';
        }
        
        // Refresh the page to avoid form resubmission
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . urlencode($success));
        exit;
        
    } catch (PDOException $e) {
        $error = 'Ошибка при сохранении данных';
        error_log($e->getMessage());
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle invoice deletion
if (isset($_GET['delete']) && hasRole(['admin', 'director', 'manager'])) {
    try {
        $invoiceId = (int)$_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = :id");
        $stmt->execute([':id' => $invoiceId]);
        logActivity($pdo, $_SESSION['user_id'], 'invoice_deleted', 'invoices', $invoiceId);
        $success = 'Счет успешно удален';
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении счета';
        error_log($e->getMessage());
    }
}

// Handle edit mode - load invoice data
if (isset($_GET['edit']) && hasRole(['admin', 'director', 'manager', 'accountant'])) {
    try {
        $invoiceId = (int)$_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
        $stmt->execute([':id' => $invoiceId]);
        $editInvoice = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Ошибка загрузки данных счета';
        error_log($e->getMessage());
    }
}

// Get all invoices with order and client information
try {
    $stmt = $pdo->query("
        SELECT i.*, o.order_number, c.company_name as client_name, c.client_code
        FROM invoices i
        LEFT JOIN orders o ON i.order_id = o.id
        LEFT JOIN clients c ON o.client_id = c.id
        ORDER BY i.created_at DESC
    ");
    $invoices = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Ошибка загрузки данных';
    $invoices = [];
}

// Get financial statistics
try {
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_invoices,
            SUM(total_with_vat) as total_amount,
            SUM(CASE WHEN payment_status = 'paid' THEN paid_amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN payment_status IN ('unpaid', 'partial') THEN total_with_vat - COALESCE(paid_amount, 0) ELSE 0 END) as receivable_amount
        FROM invoices
    ")->fetch();
} catch (PDOException $e) {
    $stats = ['total_invoices' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'receivable_amount' => 0];
}

// Get orders for dropdown
try {
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, c.company_name 
        FROM orders o
        LEFT JOIN clients c ON o.client_id = c.id
        WHERE o.status NOT IN ('cancelled')
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

$userFullName = $_SESSION['full_name'];
$userRole = $_SESSION['user_role'];
$initials = strtoupper(substr($userFullName, 0, 1));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Финансы - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                        <h1>Управление финансами</h1>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo $initials; ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($userFullName); ?></span>
                            <span class="user-role"><?php echo translateRoleName($userRole); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="content-area">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Financial Statistics Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="card stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Всего счетов</p>
                                <h3 style="font-size: 28px; margin: 0;"><?php echo number_format($stats['total_invoices'], 0, '.', ' '); ?></h3>
                            </div>
                            <i class="fas fa-file-invoice" style="font-size: 40px; opacity: 0.3;"></i>
                        </div>
                    </div>
                    
                    <div class="card stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Общая сумма</p>
                                <h3 style="font-size: 28px; margin: 0;"><?php echo number_format($stats['total_amount'], 2, '.', ' '); ?> BYN</h3>
                            </div>
                            <i class="fas fa-coins" style="font-size: 40px; opacity: 0.3;"></i>
                        </div>
                    </div>
                    
                    <div class="card stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Оплачено</p>
                                <h3 style="font-size: 28px; margin: 0;"><?php echo number_format($stats['paid_amount'], 2, '.', ' '); ?> BYN</h3>
                            </div>
                            <i class="fas fa-check-circle" style="font-size: 40px; opacity: 0.3;"></i>
                        </div>
                    </div>
                    
                    <div class="card stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Дебиторская задолженность</p>
                                <h3 style="font-size: 28px; margin: 0;"><?php echo number_format($stats['receivable_amount'], 2, '.', ' '); ?> BYN</h3>
                            </div>
                            <i class="fas fa-exclamation-triangle" style="font-size: 40px; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Реестр счетов</h2>
                        <?php if (hasRole(['admin', 'director', 'manager', 'accountant'])): ?>
                            <button class="btn btn-primary" onclick="openModal('addInvoiceModal')">
                                <i class="fas fa-plus"></i> Создать счет
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                        <input 
                            type="text" 
                            id="invoiceSearch"
                            placeholder="Поиск по номеру счета или клиенту..." 
                            onkeyup="filterInvoices()"
                            style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; width: 300px;"
                        >
                        <select 
                            id="invoiceStatusFilter"
                            onchange="filterInvoices()"
                            style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;"
                        >
                            <option value="">Все статусы</option>
                            <option value="unpaid">Не оплачен</option>
                            <option value="partial">Частично оплачен</option>
                            <option value="paid">Оплачен</option>
                            <option value="overdue">Просрочен</option>
                        </select>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" id="invoicesTable">
                            <thead>
                                <tr>
                                    <th>№ Счета</th>
                                    <th>Заказ</th>
                                    <th>Клиент</th>
                                    <th>Дата выставления</th>
                                    <th>Сумма (с НДС)</th>
                                    <th>Статус оплаты</th>
                                    <th>Оплачено</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Счетов не найдено</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($invoice['order_number'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['client_name'] ?? 'Не указано'); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($invoice['invoice_date'])); ?></td>
                                            <td><strong><?php echo number_format($invoice['total_with_vat'], 2, '.', ' '); ?> BYN</strong></td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'unpaid' => '#ef4444',
                                                    'partial' => '#f59e0b',
                                                    'paid' => '#10b981',
                                                    'overdue' => '#dc2626'
                                                ];
                                                $statusLabels = [
                                                    'unpaid' => 'Не оплачен',
                                                    'partial' => 'Частично',
                                                    'paid' => 'Оплачен',
                                                    'overdue' => 'Просрочен'
                                                ];
                                                $status = $invoice['payment_status'] ?? 'unpaid';
                                                ?>
                                                <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background-color: <?php echo $statusColors[$status]; ?>; color: white;">
                                                    <?php echo $statusLabels[$status]; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($invoice['paid_amount'] > 0): ?>
                                                    <?php echo number_format($invoice['paid_amount'], 2, '.', ' '); ?> BYN
                                                    <?php if ($invoice['paid_date']): ?>
                                                        <br><small style="color: var(--text-light);"><?php echo date('d.m.Y', strtotime($invoice['paid_date'])); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color: var(--text-light);">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if (hasRole(['admin', 'director', 'manager', 'accountant'])): ?>
                                                        <a href="?edit=<?php echo $invoice['id']; ?>" 
                                                           class="btn btn-sm btn-icon btn-secondary" 
                                                           title="Редактировать">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($invoice['payment_status'] !== 'paid'): ?>
                                                            <button class="btn btn-sm btn-icon btn-success" 
                                                                    onclick="openPaymentModal(<?php echo $invoice['id']; ?>, '<?php echo htmlspecialchars($invoice['invoice_number']); ?>', <?php echo $invoice['total_with_vat']; ?>, <?php echo (float)($invoice['paid_amount'] ?? 0); ?>)"
                                                                    title="Зарегистрировать платеж">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <a href="payment_print.php?id=<?php echo $invoice['id']; ?>" 
                                                       target="_blank"
                                                       class="btn btn-sm btn-icon btn-primary" 
                                                       title="Печать квитанции об оплате">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <?php if (hasRole(['admin', 'director', 'manager'])): ?>
                                                        <a href="?delete=<?php echo $invoice['id']; ?>" 
                                                           class="btn btn-sm btn-icon btn-danger"
                                                           onclick="return confirm('Вы уверены, что хотите удалить счет?')"
                                                           title="Удалить">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
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
    
    <!-- Add Invoice Modal -->
    <div id="addInvoiceModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Создать новый счет</h2>
                <button class="modal-close" onclick="closeModal('addInvoiceModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="invoice_number">Номер счета *</label>
                        <input type="text" id="invoice_number" name="invoice_number" required placeholder="Например: СЧ-2024-001">
                    </div>
                    
                    <div class="form-group">
                        <label for="order_id">Заказ *</label>
                        <select id="order_id" name="order_id" required>
                            <option value="">Выберите заказ</option>
                            <?php foreach ($orders as $order): ?>
                                <option value="<?php echo $order['id']; ?>">
                                    <?php echo htmlspecialchars($order['order_number']); ?> - <?php echo htmlspecialchars($order['company_name'] ?? 'Без клиента'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="invoice_date">Дата выставления *</label>
                            <input type="date" id="invoice_date" name="invoice_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="due_date">Срок оплаты</label>
                            <input type="date" id="due_date" name="due_date">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="total_amount">Сумма без НДС (BYN) *</label>
                            <input type="number" step="0.01" id="total_amount" name="total_amount" required min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="vat_amount">НДС (BYN)</label>
                            <input type="number" step="0.01" id="vat_amount" name="vat_amount" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Комментарий</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Дополнительная информация"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addInvoiceModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать счет</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Invoice Modal -->
    <?php if ($editInvoice): ?>
    <div id="editInvoiceModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Редактировать счет</h2>
                <a href="index.php" class="modal-close">&times;</a>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="invoice_id" value="<?php echo $editInvoice['id']; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_invoice_number">Номер счета</label>
                        <input type="text" id="edit_invoice_number" value="<?php echo htmlspecialchars($editInvoice['invoice_number']); ?>" disabled>
                        <small style="color: var(--text-light);">Номер счета нельзя изменить</small>
                    </div>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="edit_invoice_date">Дата выставления</label>
                            <input type="date" id="edit_invoice_date" name="invoice_date" value="<?php echo htmlspecialchars($editInvoice['invoice_date']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_due_date">Срок оплаты</label>
                            <input type="date" id="edit_due_date" name="due_date" value="<?php echo htmlspecialchars($editInvoice['due_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="edit_total_amount">Сумма без НДС (BYN)</label>
                            <input type="number" step="0.01" id="edit_total_amount" name="total_amount" value="<?php echo htmlspecialchars($editInvoice['total_amount']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_vat_amount">НДС (BYN)</label>
                            <input type="number" step="0.01" id="edit_vat_amount" name="vat_amount" value="<?php echo htmlspecialchars($editInvoice['vat_amount']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_notes">Комментарий</label>
                        <textarea id="edit_notes" name="notes" rows="3"><?php echo htmlspecialchars($editInvoice['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php" class="btn btn-secondary">Отмена</a>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Регистрация платежа</h2>
                <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="invoice_id" id="payment_invoice_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="paid_amount">Сумма платежа (BYN) *</label>
                        <input type="number" step="0.01" id="paid_amount" name="paid_amount" required min="0.01">
                        <small style="color: var(--text-light);" id="max_payment_info"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="paid_date">Дата оплаты</label>
                        <input type="date" id="paid_date" name="paid_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Отмена</button>
                    <button type="submit" class="btn btn-success">Зарегистрировать платеж</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Open modal by ID
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                setTimeout(function() {
                    modal.classList.add('active');
                }, 10);
            }
        }
        
        // Close modal by ID
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                setTimeout(function() {
                    modal.style.display = 'none';
                }, 300);
            }
        }
        
        // Filter invoices by search and status
        function filterInvoices() {
            const searchTerm = document.getElementById('invoiceSearch').value.toLowerCase();
            const statusFilter = document.getElementById('invoiceStatusFilter').value;
            const table = document.getElementById('invoicesTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const statusCell = row.cells[5]; // Status column index
                const status = statusCell ? statusCell.querySelector('span') : null;
                const statusValue = status ? getStatusValue(status.textContent) : '';
                
                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !statusFilter || statusValue === statusFilter;
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
        
        function getStatusValue(statusText) {
            if (statusText.includes('Не оплачен')) return 'unpaid';
            if (statusText.includes('Частично')) return 'partial';
            if (statusText.includes('Оплачен')) return 'paid';
            if (statusText.includes('Просрочен')) return 'overdue';
            return '';
        }
        
        // Open payment modal
        function openPaymentModal(invoiceId, invoiceNumber, totalAmount, paidAmount) {
            document.getElementById('payment_invoice_id').value = invoiceId;
            const remainingAmount = totalAmount - paidAmount;
            document.getElementById('max_payment_info').textContent = 
                'Счет: ' + invoiceNumber + '. Остаток к оплате: ' + remainingAmount.toFixed(2) + ' BYN (из ' + totalAmount.toFixed(2) + ' BYN)';
            document.getElementById('paid_amount').max = remainingAmount;
            document.getElementById('paid_amount').value = remainingAmount > 0 ? remainingAmount.toFixed(2) : '';
            openModal('paymentModal');
        }
        
        // Auto-calculate total with VAT when adding invoice
        document.addEventListener('input', function(e) {
            if (e.target.id === 'total_amount' || e.target.id === 'vat_amount') {
                const total = parseFloat(document.getElementById('total_amount').value) || 0;
                const vat = parseFloat(document.getElementById('vat_amount').value) || 0;
                console.log('Total with VAT: ' + (total + vat).toFixed(2));
            }
        });
        
        // Initialize modals on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal buttons
            document.querySelectorAll('.modal-close').forEach(button => {
                button.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    closeModal(modal.id);
                });
            });
            
            // Close modal when clicking outside
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });
        });
    </script>
</body>
</html>

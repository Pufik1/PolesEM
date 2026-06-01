<?php
/**
 * Payment Receipt Print Template for OAO "Polesieelectromash" ERP System
 * Professional payment confirmation document
 */

require_once __DIR__ . '/../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

$pdo = getDBConnection();
$invoiceId = (int)($_GET['id'] ?? 0);

if ($invoiceId <= 0) {
    die('Неверный ID счета');
}

try {
    // Get invoice data with full information
    $stmt = $pdo->prepare("
        SELECT i.*, 
               o.order_number,
               c.company_name as client_name,
               c.client_code,
               c.address as client_address,
               c.inn as client_inn,
               c.contact_person,
               c.phone as client_phone,
               c.email as client_email
        FROM invoices i
        LEFT JOIN orders o ON i.order_id = o.id
        LEFT JOIN clients c ON o.client_id = c.id
        WHERE i.id = :id
    ");
    $stmt->execute([':id' => $invoiceId]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        die('Счет не найден');
    }
    
    // Calculate payment details
    $totalAmount = (float)$invoice['total_with_vat'];
    $paidAmount = (float)($invoice['paid_amount'] ?? 0);
    $remainingAmount = $totalAmount - $paidAmount;
    
} catch (Exception $e) {
    die('Ошибка: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Квитанция об оплате №<?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
        }
        
        .company-logo {
            flex: 1;
        }
        
        .company-logo h1 {
            font-size: 16pt;
            margin: 0 0 8px 0;
            color: #333;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .company-details {
            font-size: 10pt;
            color: #555;
        }
        
        .company-details p {
            margin: 3px 0;
        }
        
        .document-title {
            text-align: right;
        }
        
        .document-title h2 {
            font-size: 18pt;
            margin: 0 0 8px 0;
            font-weight: bold;
            color: #333;
        }
        
        .document-number {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .document-date {
            font-size: 11pt;
            color: #555;
        }
        
        .main-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .info-table tr {
            border-bottom: 1px dotted #ccc;
        }
        
        .info-table td {
            padding: 8px 0;
            vertical-align: top;
        }
        
        .info-table td:first-child {
            width: 180px;
            font-weight: bold;
            color: #333;
        }
        
        .payment-details-box {
            border: 2px solid #333;
            padding: 20px;
            margin: 25px 0;
            background: #f9f9f9;
        }
        
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .payment-row:last-child {
            border-bottom: none;
        }
        
        .payment-label {
            font-weight: bold;
            color: #555;
        }
        
        .payment-value {
            font-weight: bold;
            color: #333;
        }
        
        .payment-value.total {
            font-size: 14pt;
            color: #2e7d32;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11pt;
            font-weight: bold;
            color: white;
        }
        
        .status-paid {
            background-color: #2e7d32;
        }
        
        .status-partial {
            background-color: #f57c00;
        }
        
        .status-unpaid {
            background-color: #c62828;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        
        .signature-block {
            width: 45%;
        }
        
        .signature-block p {
            margin: 8px 0;
        }
        
        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
        }
        
        .footer-info {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 10pt;
            color: #666;
            text-align: center;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(0, 0, 0, 0.05);
            pointer-events: none;
            z-index: 0;
        }
        
        .actions {
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
        
        .btn {
            padding: 12px 25px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .btn-print {
            background-color: #007bff;
            color: white;
        }
        
        .btn-print:hover {
            background-color: #0056b3;
        }
        
        .btn-close {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-close:hover {
            background-color: #545b62;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .container {
                padding: 0;
                margin: 0;
                max-width: none;
            }
            
            .actions {
                display: none;
            }
            
            .watermark {
                position: absolute;
            }
            
            .payment-details-box {
                background: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Печать документа
        </button>
        <button class="btn btn-close" onclick="window.close()">Закрыть</button>
    </div>
    
    <div class="container">
        <?php if ($invoice['payment_status'] === 'paid'): ?>
        <div class="watermark">ОПЛАЧЕНО</div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="header">
            <div class="company-logo">
                <h1>ОАО «Полесьеэлектромаш»</h1>
                <div class="company-details">
                    <p>УНП: <?php echo APP_INN ?? '123456789'; ?></p>
                    <p>Адрес: <?php echo APP_ADDRESS; ?></p>
                    <p>Тел: <?php echo APP_PHONE ?? '+375 (232) 00-00-00'; ?></p>
                    <p>Email: <?php echo APP_EMAIL ?? 'info@polesie.by'; ?></p>
                    <p>Банк: <?php echo APP_BANK ?? 'ОАО «Беларусбанк»; ?></p>
                    <p>БИК: <?php echo APP_BIK ?? '153001111'; ?></p>
                </div>
            </div>
            
            <div class="document-title">
                <h2>КВИТАНЦИЯ ОБ ОПЛАТЕ</h2>
                <div class="document-number">К счету № <?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
                <div class="document-date">
                    Дата оплаты: <?php echo $invoice['paid_date'] ? date('d.m.Y', strtotime($invoice['paid_date'])) : '—'; ?>
                </div>
            </div>
        </div>
        
        <!-- Client Information -->
        <div class="main-section">
            <div class="section-title">Плательщик</div>
            <table class="info-table">
                <tr>
                    <td>Наименование:</td>
                    <td><?php echo htmlspecialchars($invoice['client_name'] ?? 'Не указано'); ?></td>
                </tr>
                <?php if ($invoice['client_inn']): ?>
                <tr>
                    <td>УНП:</td>
                    <td><?php echo htmlspecialchars($invoice['client_inn']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($invoice['client_address']): ?>
                <tr>
                    <td>Адрес:</td>
                    <td><?php echo htmlspecialchars($invoice['client_address']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($invoice['contact_person'] || $invoice['client_phone'] || $invoice['client_email']): ?>
                <tr>
                    <td>Контактная информация:</td>
                    <td>
                        <?php if ($invoice['contact_person']): ?>
                            <?php echo htmlspecialchars($invoice['contact_person']); ?>
                        <?php endif; ?>
                        <?php if ($invoice['client_phone']): ?>
                            | Тел: <?php echo htmlspecialchars($invoice['client_phone']); ?>
                        <?php endif; ?>
                        <?php if ($invoice['client_email']): ?>
                            | Email: <?php echo htmlspecialchars($invoice['client_email']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Основание платежа:</td>
                    <td>
                        Оплата по счету № <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                        <?php if ($invoice['order_number']): ?>
                            от <?php echo date('d.m.Y', strtotime($invoice['invoice_date'])); ?>
                            (Заказ № <?php echo htmlspecialchars($invoice['order_number']); ?>)
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Payment Details -->
        <div class="main-section">
            <div class="section-title">Детали платежа</div>
            
            <div class="payment-details-box">
                <div class="payment-row">
                    <span class="payment-label">Номер счета:</span>
                    <span class="payment-value"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                </div>
                
                <div class="payment-row">
                    <span class="payment-label">Дата выставления счета:</span>
                    <span class="payment-value"><?php echo date('d.m.Y', strtotime($invoice['invoice_date'])); ?></span>
                </div>
                
                <?php if ($invoice['due_date']): ?>
                <div class="payment-row">
                    <span class="payment-label">Срок оплаты:</span>
                    <span class="payment-value"><?php echo date('d.m.Y', strtotime($invoice['due_date'])); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="payment-row">
                    <span class="payment-label">Статус оплаты:</span>
                    <span class="payment-value">
                        <?php
                        $statusLabels = [
                            'unpaid' => 'Не оплачен',
                            'partial' => 'Частично оплачен',
                            'paid' => 'Оплачен',
                            'overdue' => 'Просрочен'
                        ];
                        $statusClass = [
                            'unpaid' => 'status-unpaid',
                            'partial' => 'status-partial',
                            'paid' => 'status-paid',
                            'overdue' => 'status-unpaid'
                        ];
                        $status = $invoice['payment_status'] ?? 'unpaid';
                        ?>
                        <span class="status-badge <?php echo $statusClass[$status]; ?>">
                            <?php echo $statusLabels[$status]; ?>
                        </span>
                    </span>
                </div>
                
                <div class="payment-row">
                    <span class="payment-label">Общая сумма к оплате:</span>
                    <span class="payment-value"><?php echo number_format($totalAmount, 2, ',', ' '); ?> BYN</span>
                </div>
                
                <div class="payment-row">
                    <span class="payment-label">В том числе НДС:</span>
                    <span class="payment-value"><?php echo number_format($invoice['vat_amount'], 2, ',', ' '); ?> BYN</span>
                </div>
                
                <div class="payment-row">
                    <span class="payment-label">Сумма без НДС:</span>
                    <span class="payment-value"><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> BYN</span>
                </div>
                
                <?php if ($paidAmount > 0): ?>
                <div class="payment-row">
                    <span class="payment-label">Оплачено:</span>
                    <span class="payment-value" style="color: #2e7d32;"><?php echo number_format($paidAmount, 2, ',', ' '); ?> BYN</span>
                </div>
                <?php endif; ?>
                
                <?php if ($remainingAmount > 0): ?>
                <div class="payment-row">
                    <span class="payment-label">Остаток к оплате:</span>
                    <span class="payment-value" style="color: #c62828;"><?php echo number_format($remainingAmount, 2, ',', ' '); ?> BYN</span>
                </div>
                <?php endif; ?>
                
                <div class="payment-row" style="background: #e8f5e9; margin: 10px -20px -20px -20px; padding: 15px 20px; border-top: 2px solid #2e7d32;">
                    <span class="payment-label" style="font-size: 13pt;">ИТОГО ОПЛАЧЕНО:</span>
                    <span class="payment-value total"><?php echo number_format($paidAmount, 2, ',', ' '); ?> BYN</span>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <?php if ($invoice['notes']): ?>
        <div class="main-section">
            <div class="section-title">Примечание</div>
            <p style="font-style: italic; color: #555;"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-block">
                <p><strong>От продавца:</strong></p>
                <p style="margin-top: 30px;">_____________________</p>
                <div class="signature-line">
                    <span>(подпись)</span>
                    <span>(Ф.И.О.)</span>
                </div>
                <p style="margin-top: 15px; font-size: 10pt; color: #666;">
                    М.П.
                </p>
            </div>
            
            <div class="signature-block">
                <p><strong>От покупателя:</strong></p>
                <p style="margin-top: 30px;">_____________________</p>
                <div class="signature-line">
                    <span>(подпись)</span>
                    <span>(Ф.И.О.)</span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-info">
            <p>Данный документ является подтверждением совершения платежа и действителен без печати при наличии подписи уполномоченного лица.</p>
            <p style="margin-top: 10px;">
                Документ сформирован автоматически <?php echo date('d.m.Y H:i:s'); ?>
            </p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Auto-print option (uncomment if needed)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

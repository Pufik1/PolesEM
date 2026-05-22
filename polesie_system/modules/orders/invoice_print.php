<?php
/**
 * Invoice Print Template for OAO "Polesieelectromash" ERP System
 */

// Include configuration
require_once __DIR__ . '/../../includes/config.php';

if (!isset($printInvoice)) {
    die('Direct access not allowed');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Счет-фактура №<?php echo htmlspecialchars($printInvoice['invoice_number']); ?></title>
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
            padding: 20px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-info h1 {
            font-size: 16pt;
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h2 {
            font-size: 18pt;
            margin: 0 0 5px 0;
        }
        
        .invoice-number {
            font-size: 14pt;
            font-weight: bold;
        }
        
        .invoice-date {
            margin-top: 5px;
        }
        
        .parties-section {
            margin-bottom: 30px;
        }
        
        .party-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .party-label {
            width: 120px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .party-value {
            flex: 1;
            border-bottom: 1px dotted #ccc;
            padding-left: 10px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .items-table td.text-right {
            text-align: right;
        }
        
        .items-table td.text-center {
            text-align: center;
        }
        
        .totals-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            padding: 5px 0;
        }
        
        .total-label {
            width: 200px;
            text-align: right;
            padding-right: 20px;
            font-weight: bold;
        }
        
        .total-value {
            width: 150px;
            text-align: right;
            border-bottom: 1px solid #000;
            padding-left: 10px;
        }
        
        .total-final {
            font-size: 14pt;
            font-weight: bold;
        }
        
        .footer-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        
        .signature-block {
            width: 45%;
        }
        
        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: flex;
            justify-content: space-between;
        }
        
        .notes-section {
            margin-top: 30px;
            font-size: 10pt;
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            <i class="fas fa-print"></i> Печать
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Закрыть
        </button>
    </div>
    
    <div class="invoice-header">
        <div class="company-info">
            <h1>ОАО «Полесьеэлектромаш»</h1>
            <div>УНП: <?php echo APP_INN ?? '123456789'; ?></div>
            <div>Адрес: <?php echo APP_ADDRESS ?? 'г. Гомель, ул. Примерная, 1'; ?></div>
            <div>Тел: <?php echo APP_PHONE ?? '+375 (232) 00-00-00'; ?></div>
            <div>Email: <?php echo APP_EMAIL ?? 'info@polesie.by'; ?></div>
        </div>
        
        <div class="invoice-title">
            <h2>СЧЕТ-ФАКТУРА</h2>
            <div class="invoice-number">№ <?php echo htmlspecialchars($printInvoice['invoice_number']); ?></div>
            <div class="invoice-date">от <?php echo date('d.m.Y', strtotime($printInvoice['invoice_date'])); ?></div>
        </div>
    </div>
    
    <div class="parties-section">
        <div class="party-row">
            <div class="party-label">Продавец:</div>
            <div class="party-value">ОАО «Полесьеэлектромаш», УНП <?php echo APP_INN ?? '123456789'; ?></div>
        </div>
        
        <div class="party-row">
            <div class="party-label">Покупатель:</div>
            <div class="party-value">
                <?php echo htmlspecialchars($printInvoice['client_name']); ?>
                <?php if ($printInvoice['client_inn']): ?>
                    (УНП <?php echo htmlspecialchars($printInvoice['client_inn']); ?>)
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($printInvoice['client_address']): ?>
        <div class="party-row">
            <div class="party-label">Адрес:</div>
            <div class="party-value"><?php echo htmlspecialchars($printInvoice['client_address']); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($printInvoice['contact_person'] || $printInvoice['phone'] || $printInvoice['email']): ?>
        <div class="party-row">
            <div class="party-label">Контакты:</div>
            <div class="party-value">
                <?php if ($printInvoice['contact_person']): ?>
                    <?php echo htmlspecialchars($printInvoice['contact_person']); ?>
                <?php endif; ?>
                <?php if ($printInvoice['phone']): ?>
                    | Тел: <?php echo htmlspecialchars($printInvoice['phone']); ?>
                <?php endif; ?>
                <?php if ($printInvoice['email']): ?>
                    | Email: <?php echo htmlspecialchars($printInvoice['email']); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="party-row">
            <div class="party-label">Основание:</div>
            <div class="party-value">Заказ № <?php echo htmlspecialchars($printInvoice['order_number']); ?></div>
        </div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 8%;">Код</th>
                <th style="width: 35%;">Наименование товара</th>
                <th style="width: 10%;" class="text-center">Ед.изм.</th>
                <th style="width: 10%;" class="text-right">Кол-во</th>
                <th style="width: 12%;" class="text-right">Цена за ед.</th>
                <th style="width: 12%;" class="text-right">Сумма</th>
                <th style="width: 8%;" class="text-right">НДС %</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $itemNumber = 0;
            foreach ($printInvoiceItems as $item): 
                $itemNumber++;
            ?>
            <tr>
                <td class="text-center"><?php echo $itemNumber; ?></td>
                <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td class="text-center">шт</td>
                <td class="text-right"><?php echo $item['quantity']; ?></td>
                <td class="text-right"><?php echo number_format($item['unit_price'], 2, ',', ' '); ?></td>
                <td class="text-right"><?php echo number_format($item['total_price'], 2, ',', ' '); ?></td>
                <td class="text-right"><?php echo $item['vat_rate']; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="totals-section">
        <div class="total-row">
            <div class="total-label">Всего без НДС:</div>
            <div class="total-value"><?php echo number_format($printInvoice['total_amount'], 2, ',', ' '); ?> BYN</div>
        </div>
        
        <div class="total-row">
            <div class="total-label">НДС (<?php echo number_format($printInvoice['vat_amount'], 2, ',', ' '); ?> BYN):</div>
            <div class="total-value"><?php echo number_format($printInvoice['vat_amount'], 2, ',', ' '); ?> BYN</div>
        </div>
        
        <div class="total-row total-final">
            <div class="total-label">ВСЕГО К ОПЛАТЕ:</div>
            <div class="total-value"><?php echo number_format($printInvoice['total_with_vat'], 2, ',', ' '); ?> BYN</div>
        </div>
        
        <div class="total-row" style="margin-top: 10px;">
            <div class="total-label">В том числе НДС:</div>
            <div class="total-value"><?php echo number_format($printInvoice['vat_amount'], 2, ',', ' '); ?> BYN</div>
        </div>
    </div>
    
    <?php if ($printInvoice['notes']): ?>
    <div class="notes-section">
        <strong>Примечание:</strong> <?php echo nl2br(htmlspecialchars($printInvoice['notes'])); ?>
    </div>
    <?php endif; ?>
    
    <div class="footer-section">
        <div class="signature-block">
            <div><strong>От продавца:</strong></div>
            <div style="margin-top: 30px;">_____________________</div>
            <div class="signature-line">
                <span>(подпись)</span>
                <span>(Ф.И.О.)</span>
            </div>
        </div>
        
        <div class="signature-block">
            <div><strong>От покупателя:</strong></div>
            <div style="margin-top: 30px;">_____________________</div>
            <div class="signature-line">
                <span>(подпись)</span>
                <span>(Ф.И.О.)</span>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 30px; font-size: 10pt; color: #666; text-align: center;">
        <p>Счет действителен к оплате в течение <?php echo $printInvoice['due_date'] ? round((strtotime($printInvoice['due_date']) - strtotime($printInvoice['invoice_date'])) / 86400) : 5; ?> дней с даты выставления.</p>
    </div>
    
    <script>
        // Auto-print on load if needed
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

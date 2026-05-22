<?php
/**
 * Delivery Note (Товарная Накладная) Print Template for OAO "Polesieelectromash" ERP System
 * Формат: ТН - Товарная накладная (согласно законодательству РБ)
 */

// Include configuration
require_once __DIR__ . '/../../includes/config.php';

if (!isset($printDeliveryNote)) {
    die('Direct access not allowed');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Товарная накладная №<?php echo htmlspecialchars($printDeliveryNote['tn_number']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        
        .header-section {
            border: 2px solid #000;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .doc-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        .doc-number-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #000;
        }
        
        .doc-number {
            font-size: 13pt;
        }
        
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .party-block {
            border: 1px solid #000;
            padding: 10px;
        }
        
        .party-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }
        
        .party-field {
            margin-bottom: 8px;
        }
        
        .field-label {
            font-size: 9pt;
            color: #555;
        }
        
        .field-value {
            font-weight: bold;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        
        .items-table th {
            background: #f5f5f5;
            font-weight: bold;
            text-align: center;
            font-size: 10pt;
        }
        
        .items-table td.text-right {
            text-align: right;
        }
        
        .items-table td.text-center {
            text-align: center;
        }
        
        .totals-row {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .footer-info {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #000;
        }
        
        .signatures-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-block {
            width: 48%;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .signature-lines {
            margin-top: 40px;
        }
        
        .signature-line {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        
        .signature-position {
            margin-bottom: 30px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            min-width: 120px;
            font-weight: bold;
        }
        
        .info-value {
            flex: 1;
            border-bottom: 1px dotted #000;
            padding-left: 10px;
        }
        
        .notes-section {
            margin-top: 20px;
            font-size: 10pt;
            padding: 10px;
            border: 1px solid #ccc;
            background: #fafafa;
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
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-right: 10px;">
            <i class="fas fa-print"></i> Печать
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            Закрыть
        </button>
    </div>
    
    <div class="header-section">
        <div class="doc-title">ТОВАРНАЯ НАКЛАДНАЯ</div>
        
        <div class="doc-number-date">
            <div class="doc-number">№ <?php echo htmlspecialchars($printDeliveryNote['tn_number']); ?></div>
            <div class="doc-date">от «<?php echo date('d', strtotime($printDeliveryNote['tn_date'])); ?>» <?php echo date('F Y', strtotime($printDeliveryNote['tn_date'])); ?> г.</div>
        </div>
        
        <div class="parties-grid">
            <!-- Грузоотправитель -->
            <div class="party-block">
                <div class="party-title">ГРУЗООТПРАВИТЕЛЬ</div>
                <div class="party-field">
                    <div class="field-label">Наименование:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printDeliveryNote['shipper_name']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">УНП:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printDeliveryNote['shipper_inn']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">Адрес:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printDeliveryNote['shipper_address']); ?></div>
                </div>
            </div>
            
            <!-- Грузополучатель -->
            <div class="party-block">
                <div class="party-title">ГРУЗОПОЛУЧАТЕЛЬ</div>
                <div class="party-field">
                    <div class="field-label">Наименование:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printDeliveryNote['consignee_name']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">УНП:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printDeliveryNote['consignee_inn']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">Адрес:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printDeliveryNote['consignee_address']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Основание:</div>
            <div class="info-value">Заказ № <?php echo htmlspecialchars($printDeliveryNote['order_number']); ?> от <?php echo date('d.m.Y', strtotime($printDeliveryNote['created_at'])); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Склад отправитель:</div>
            <div class="info-value"><?php echo htmlspecialchars($printDeliveryNote['warehouse_from']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Склад получатель:</div>
            <div class="info-value"><?php echo htmlspecialchars($printDeliveryNote['warehouse_to']); ?></div>
        </div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 12%;">Артикул</th>
                <th style="width: 35%;">Наименование товара</th>
                <th style="width: 8%;" class="text-center">Ед.изм.</th>
                <th style="width: 10%;" class="text-right">Кол-во</th>
                <th style="width: 12%;" class="text-right">Вес за ед., кг</th>
                <th style="width: 18%;" class="text-right">Общий вес, кг</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $itemNumber = 0;
            $totalWeight = 0;
            foreach ($printDeliveryNoteItems as $item): 
                $itemNumber++;
                $totalWeight += $item['total_weight'];
            ?>
            <tr>
                <td class="text-center"><?php echo $itemNumber; ?></td>
                <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="text-right"><?php echo $item['quantity']; ?></td>
                <td class="text-right"><?php echo number_format($item['weight_per_unit'], 3, ',', ' '); ?></td>
                <td class="text-right"><?php echo number_format($item['total_weight'], 3, ',', ' '); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="6" class="text-right">ИТОГО:</td>
                <td class="text-right"><?php echo number_format($totalWeight, 3, ',', ' '); ?> кг</td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer-info">
        <div class="info-row">
            <div class="info-label">Всего позиций:</div>
            <div class="info-value"><?php echo count($printDeliveryNoteItems); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Общий вес груза:</div>
            <div class="info-value"><?php echo number_format($totalWeight, 3, ',', ' '); ?> кг</div>
        </div>
    </div>
    
    <?php if ($printDeliveryNote['notes']): ?>
    <div class="notes-section">
        <strong>Примечание:</strong> <?php echo nl2br(htmlspecialchars($printDeliveryNote['notes'])); ?>
    </div>
    <?php endif; ?>
    
    <div class="signatures-section">
        <div class="signature-block">
            <div class="signature-title">От грузоотправителя:</div>
            <div class="signature-position">
                Должность: _____________________
            </div>
            <div class="signature-position">
                ФИО: _____________________
            </div>
            <div class="signature-lines">
                <div class="signature-line">
                    <span>(подпись)</span>
                    <span>М.П.</span>
                </div>
            </div>
        </div>
        
        <div class="signature-block">
            <div class="signature-title">От грузополучателя:</div>
            <div class="signature-position">
                Должность: _____________________
            </div>
            <div class="signature-position">
                ФИО: _____________________
            </div>
            <div class="signature-lines">
                <div class="signature-line">
                    <span>(подпись)</span>
                    <span>М.П.</span>
                </div>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 30px; font-size: 9pt; color: #666; text-align: center; border-top: 1px solid #ccc; padding-top: 10px;">
        <p>Товарная накладная составлена в двух экземплярах: один - грузоотправителю, второй - грузополучателю.</p>
        <p>Документ действителен без подписей и печатей сторон.</p>
    </div>
    
    <script>
        // Auto-print on load if needed
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

<?php
/**
 * Transport Waybill (ТТН) Print Template for OAO "Polesieelectromash" ERP System
 * Формат: ТТН - Товарно-транспортная накладная (согласно законодательству РБ)
 */

// Include configuration
require_once __DIR__ . '/../../includes/config.php';

if (!isset($printTransportWaybill)) {
    die('Direct access not allowed');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Товарно-транспортная накладная №<?php echo htmlspecialchars($printTransportWaybill['ttn_number']); ?></title>
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
        
        .transport-info {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #000;
        }
        
        .transport-section {
            margin-bottom: 15px;
        }
        
        .transport-section h4 {
            margin: 0 0 10px 0;
            font-size: 12pt;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            min-width: 150px;
            font-weight: bold;
        }
        
        .info-value {
            flex: 1;
            border-bottom: 1px dotted #000;
            padding-left: 10px;
        }
        
        .signatures-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-block {
            width: 30%;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
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
        <div class="doc-title">ТОВАРНО-ТРАНСПОРТНАЯ НАКЛАДНАЯ</div>
        
        <div class="doc-number-date">
            <div class="doc-number">№ <?php echo htmlspecialchars($printTransportWaybill['ttn_number']); ?></div>
            <div class="doc-date">от «<?php echo date('d', strtotime($printTransportWaybill['ttn_date'])); ?>» <?php 
                $months_ru = [
                    1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
                    5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
                    9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
                ];
                $month_num = (int)date('n', strtotime($printTransportWaybill['ttn_date']));
                echo $months_ru[$month_num] . ' ' . date('Y', strtotime($printTransportWaybill['ttn_date'])); 
            ?> г.</div>
        </div>
        
        <div class="parties-grid">
            <!-- Грузоотправитель -->
            <div class="party-block">
                <div class="party-title">ГРУЗООТПРАВИТЕЛЬ</div>
                <div class="party-field">
                    <div class="field-label">Наименование:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printTransportWaybill['shipper_name'] ?? $printOrder['company_name']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">УНП:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printTransportWaybill['shipper_inn'] ?? $printOrder['client_inn']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">Адрес:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printTransportWaybill['shipper_address'] ?? $printOrder['client_address']); ?></div>
                </div>
            </div>
            
            <!-- Грузополучатель -->
            <div class="party-block">
                <div class="party-title">ГРУЗОПОЛУЧАТЕЛЬ</div>
                <div class="party-field">
                    <div class="field-label">Наименование:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printTransportWaybill['consignee_name'] ?? $printOrder['company_name']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">УНП:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printTransportWaybill['consignee_inn'] ?? $printOrder['client_inn']); ?></div>
                </div>
                <div class="party-field">
                    <div class="field-label">Адрес:</div>
                    <div class="field-value"><?php echo htmlspecialchars($printTransportWaybill['consignee_address'] ?? $printOrder['client_address']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Основание:</div>
            <div class="info-value">Заказ № <?php echo htmlspecialchars($printOrder['order_number']); ?> от <?php echo date('d.m.Y', strtotime($printOrder['created_at'])); ?></div>
        </div>
    </div>
    
    <div class="transport-info">
        <div class="transport-section">
            <h4>Транспортные данные</h4>
            <div class="info-row">
                <div class="info-label">Автомобиль:</div>
                <div class="info-value"><?php echo htmlspecialchars($printTransportWaybill['vehicle_number'] ?? '-'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Водитель:</div>
                <div class="info-value"><?php echo htmlspecialchars($printTransportWaybill['driver_name'] ?? '-'); ?></div>
            </div>
            <?php if ($printTransportWaybill['driver_license']): ?>
            <div class="info-row">
                <div class="info-label">Лицензия водителя:</div>
                <div class="info-value"><?php echo htmlspecialchars($printTransportWaybill['driver_license']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="transport-section" style="margin-top: 15px;">
            <h4>Перевозчик</h4>
            <div class="info-row">
                <div class="info-label">Наименование:</div>
                <div class="info-value"><?php echo htmlspecialchars($printTransportWaybill['carrier_name'] ?? '-'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">УНП:</div>
                <div class="info-value"><?php echo htmlspecialchars($printTransportWaybill['carrier_inn'] ?? '-'); ?></div>
            </div>
        </div>
        
        <div class="transport-section" style="margin-top: 15px;">
            <h4>Маршрут</h4>
            <div class="info-row">
                <div class="info-label">Пункт погрузки:</div>
                <div class="info-value"><?php echo htmlspecialchars($printTransportWaybill['loading_point'] ?? '-'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Пункт разгрузки:</div>
                <div class="info-value"><?php echo htmlspecialchars($printTransportWaybill['unloading_point'] ?? '-'); ?></div>
            </div>
            <?php if ($printTransportWaybill['distance_km']): ?>
            <div class="info-row">
                <div class="info-label">Расстояние:</div>
                <div class="info-value"><?php echo $printTransportWaybill['distance_km']; ?> км</div>
            </div>
            <?php endif; ?>
            <?php if ($printTransportWaybill['freight_cost']): ?>
            <div class="info-row">
                <div class="info-label">Стоимость перевозки:</div>
                <div class="info-value"><?php echo number_format($printTransportWaybill['freight_cost'], 2, ',', ' '); ?> BYN</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($printTransportWaybillItems): ?>
    <table class="items-table" style="margin-top: 20px;">
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
            foreach ($printTransportWaybillItems as $item): 
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
    
    <div class="footer-info" style="margin-top: 20px;">
        <div class="info-row">
            <div class="info-label">Всего позиций:</div>
            <div class="info-value"><?php echo count($printTransportWaybillItems); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Общий вес груза:</div>
            <div class="info-value"><?php echo number_format($totalWeight, 3, ',', ' '); ?> кг</div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($printTransportWaybill['notes']): ?>
    <div class="notes-section">
        <strong>Примечание:</strong> <?php echo nl2br(htmlspecialchars($printTransportWaybill['notes'])); ?>
    </div>
    <?php endif; ?>
    
    <div class="signatures-section">
        <div class="signature-block">
            <div class="signature-title">Грузоотправитель</div>
            <div style="margin-top: 30px;">_____________________</div>
            <div class="signature-line">
                <span>(подпись)</span>
                <span>М.П.</span>
            </div>
        </div>
        
        <div class="signature-block">
            <div class="signature-title">Водитель</div>
            <div style="margin-top: 30px;">_____________________</div>
            <div class="signature-line">
                <span>(подпись)</span>
                <span></span>
            </div>
        </div>
        
        <div class="signature-block">
            <div class="signature-title">Грузополучатель</div>
            <div style="margin-top: 30px;">_____________________</div>
            <div class="signature-line">
                <span>(подпись)</span>
                <span>М.П.</span>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 30px; font-size: 9pt; color: #666; text-align: center; border-top: 1px solid #ccc; padding-top: 10px;">
        <p>Товарно-транспортная накладная составлена в четырех экземплярах: грузоотправителю, грузополучателю, водителю, бухгалтерии.</p>
        <p>Документ действителен без подписей и печатей сторон.</p>
    </div>
    
    <script>
        // Auto-print on load if needed
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

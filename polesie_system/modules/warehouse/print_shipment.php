<?php
/**
 * Print Shipment Document (Invoice)
 * For OAO "Polesieelectromash" ERP System
 */

require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

$pdo = getDBConnection();
$shipmentId = (int)($_GET['id'] ?? 0);

if ($shipmentId <= 0) {
    die('Неверный ID документа');
}

try {
    // Get shipment document data
    $stmt = $pdo->prepare("
        SELECT sd.*, 
               o.order_number as order_number,
               c.company_name as customer_name,
               wc.center_name as warehouse_name
        FROM shipment_documents sd
        LEFT JOIN orders o ON sd.order_id = o.id
        LEFT JOIN clients c ON sd.customer_id = c.id
        LEFT JOIN work_centers wc ON sd.warehouse_from_id = wc.id
        WHERE sd.id = :id
    ");
    $stmt->execute([':id' => $shipmentId]);
    $shipment = $stmt->fetch();
    
    if (!$shipment) {
        die('Документ не найден');
    }
    
    // Get shipment items
    $stmt = $pdo->prepare("
        SELECT * FROM shipment_items 
        WHERE shipment_id = :shipment_id 
        ORDER BY id ASC
    ");
    $stmt->execute([':shipment_id' => $shipmentId]);
    $items = $stmt->fetchAll();
    
} catch (Exception $e) {
    die('Ошибка: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Накладная №<?php echo htmlspecialchars($shipment['shipment_number']); ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            .header { border-bottom: 3px solid #000; }
            .footer { border-top: 2px solid #000; }
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            line-height: 1.6;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 4px 0;
            font-size: 14px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .doc-number {
            text-align: right;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .doc-info {
            margin-bottom: 25px;
        }
        .doc-info table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .doc-info td {
            padding: 6px 4px;
            border-bottom: 1px solid #ddd;
        }
        .doc-info td:first-child {
            font-weight: bold;
            width: 180px;
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 13px;
        }
        .items-table th,
        .items-table td {
            padding: 8px 6px;
            border: 1px solid #000;
            text-align: left;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }
        .items-table td.num {
            text-align: right;
        }
        .items-table td.center {
            text-align: center;
        }
        .totals-section {
            margin-top: 20px;
            text-align: right;
        }
        .totals-table {
            display: inline-block;
            min-width: 300px;
            font-size: 13px;
        }
        .totals-table td {
            padding: 6px;
            border: 1px solid #000;
        }
        .totals-table td:first-child {
            font-weight: bold;
            background-color: #f0f0f0;
            text-align: left;
        }
        .totals-table td.num {
            text-align: right;
        }
        .footer {
            margin-top: 35px;
            border-top: 2px solid #000;
            padding-top: 15px;
            font-size: 13px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-block {
            width: 45%;
            border-top: 1px solid #000;
            padding-top: 10px;
            font-size: 13px;
        }
        .signature-block p {
            margin: 5px 0;
        }
        .btn-print {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-close {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .actions {
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Печать
        </button>
        <button class="btn-close" onclick="window.close()">Закрыть</button>
    </div>
    
    <div class="header">
        <h1>ОАО «Полесьеэлектромаш»</h1>
        <p>ТОВАРНАЯ НАКЛАДНАЯ</p>
        <p><strong>№ <?php echo htmlspecialchars($shipment['shipment_number']); ?></strong> от <?php echo date('d.m.Y', strtotime($shipment['shipment_date'])); ?></p>
    </div>
    
    <div class="doc-info">
        <table>
            <tr>
                <td>Грузоотправитель:</td>
                <td>ОАО «Полесьеэлектромаш»</td>
            </tr>
            <tr>
                <td>Грузополучатель:</td>
                <td><?php echo htmlspecialchars($shipment['customer_name'] ?? 'Прямая продажа'); ?></td>
            </tr>
            <?php if ($shipment['order_number']): ?>
            <tr>
                <td>Заказ:</td>
                <td><?php echo htmlspecialchars($shipment['order_number']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Склад:</td>
                <td><?php echo htmlspecialchars($shipment['warehouse_name'] ?? 'Основной склад'); ?></td>
            </tr>
            <tr>
                <td>Статус:</td>
                <td>
                    <?php
                    $statusLabels = [
                        'draft' => 'Черновик',
                        'confirmed' => 'Подтвержден',
                        'shipped' => 'Отгружен',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменен'
                    ];
                    echo $statusLabels[$shipment['status']] ?? $shipment['status'];
                    ?>
                </td>
            </tr>
            <?php if ($shipment['notes']): ?>
            <tr>
                <td>Примечание:</td>
                <td><?php echo htmlspecialchars($shipment['notes']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <h3>Товары</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50px;">№</th>
                <th>Наименование</th>
                <th>Артикул</th>
                <th style="width: 60px;">Ед.</th>
                <th style="width: 70px;">Кол-во</th>
                <th style="width: 100px;">Цена</th>
                <th style="width: 80px;">НДС %</th>
                <th style="width: 120px;">Сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalAmount = 0;
            $totalVat = 0;
            $totalWithVat = 0;
            foreach ($items as $index => $item): 
                $totalAmount += $item['line_total'];
                $totalVat += $item['vat_amount'];
                $totalWithVat += $item['line_total_with_vat'];
            ?>
            <tr>
                <td class="num"><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td><?php echo htmlspecialchars($item['item_sku']); ?></td>
                <td><?php echo htmlspecialchars($item['item_unit']); ?></td>
                <td class="num"><strong><?php echo number_format($item['quantity_shipped'], 2); ?></strong></td>
                <td class="num"><?php echo number_format($item['unit_price'], 2); ?> BYN</td>
                <td class="num"><?php echo $item['vat_rate']; ?>%</td>
                <td class="num"><?php echo number_format($item['line_total_with_vat'], 2); ?> BYN</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Всего без НДС:</td>
                <td class="num"><?php echo number_format($totalAmount - $totalVat, 2); ?> BYN</td>
            </tr>
            <tr>
                <td>НДС:</td>
                <td class="num"><?php echo number_format($totalVat, 2); ?> BYN</td>
            </tr>
            <tr>
                <td><strong>Всего с НДС:</strong></td>
                <td class="num"><strong><?php echo number_format($totalWithVat, 2); ?> BYN</strong></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p>Всего позиций: <strong><?php echo count($items); ?></strong></p>
        <p>Общее количество: <strong><?php echo $shipment['total_quantity']; ?></strong> шт.</p>
        <p>Общая сумма: <strong><?php echo number_format($shipment['total_cost'], 2); ?> BYN</strong></p>
    </div>
    
    <div class="signatures">
        <div class="signature-block">
            <p>Отгрузил:</p>
            <p>_____________________</p>
            <p>(_____________________)</p>
        </div>
        <div class="signature-block">
            <p>Получил:</p>
            <p>_____________________</p>
            <p>(_____________________)</p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

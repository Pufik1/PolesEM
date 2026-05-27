<?php
/**
 * Print Write-off Document (Акт списания)
 * For OAO "Polesieelectromash" ERP System
 */

require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

$pdo = getDBConnection();
$writeoffId = (int)($_GET['id'] ?? 0);

if ($writeoffId <= 0) {
    die('Неверный ID документа');
}

try {
    // Get write-off document data
    $stmt = $pdo->prepare("
        SELECT wd.*, 
               u.full_name as created_by_name,
               wc.center_name as warehouse_name
        FROM material_writeoff_documents wd
        LEFT JOIN users u ON wd.created_by = u.id
        LEFT JOIN work_centers wc ON wd.warehouse_id = wc.id
        WHERE wd.id = :id
    ");
    $stmt->execute([':id' => $writeoffId]);
    $writeoff = $stmt->fetch();
    
    if (!$writeoff) {
        die('Документ не найден');
    }
    
    // Get write-off items
    $stmt = $pdo->prepare("
        SELECT * FROM material_writeoff_items 
        WHERE writeoff_id = :writeoff_id 
        ORDER BY id ASC
    ");
    $stmt->execute([':writeoff_id' => $writeoffId]);
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
    <title>Акт списания №<?php echo htmlspecialchars($writeoff['document_number']); ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 20px; }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .doc-info {
            margin-bottom: 20px;
        }
        .doc-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-info td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .doc-info td:first-child {
            font-weight: bold;
            width: 200px;
            background-color: #f5f5f5;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .items-table td.num {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            border-top: 2px solid #333;
            padding-top: 15px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .signature-block {
            width: 45%;
            border-top: 1px solid #333;
            padding-top: 10px;
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
        <p>АКТ СПИСАНИЯ ТОВАРНО-МАТЕРИАЛЬНЫХ ЦЕННОСТЕЙ</p>
        <p><strong>№ <?php echo htmlspecialchars($writeoff['document_number']); ?></strong> от <?php echo date('d.m.Y', strtotime($writeoff['document_date'])); ?></p>
    </div>
    
    <div class="doc-info">
        <table>
            <tr>
                <td>Тип списания:</td>
                <td><?php echo $writeoff['writeoff_type'] === 'material' ? 'Материалы' : 'Готовая продукция'; ?></td>
            </tr>
            <tr>
                <td>Склад:</td>
                <td><?php echo htmlspecialchars($writeoff['warehouse_name'] ?? 'Основной склад'); ?></td>
            </tr>
            <tr>
                <td>Статус:</td>
                <td>
                    <?php
                    $statusLabels = [
                        'draft' => 'Черновик',
                        'confirmed' => 'Подтвержден',
                        'posted' => 'Проведен',
                        'cancelled' => 'Отменен'
                    ];
                    echo $statusLabels[$writeoff['status']] ?? $writeoff['status'];
                    ?>
                </td>
            </tr>
            <tr>
                <td>Создан:</td>
                <td><?php echo htmlspecialchars($writeoff['created_by_name']); ?> (<?php echo date('d.m.Y H:i', strtotime($writeoff['created_at'])); ?>)</td>
            </tr>
            <?php if ($writeoff['reason']): ?>
            <tr>
                <td>Причина списания:</td>
                <td><?php echo htmlspecialchars($writeoff['reason']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($writeoff['notes']): ?>
            <tr>
                <td>Примечание:</td>
                <td><?php echo htmlspecialchars($writeoff['notes']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <h3>Списанные товары/материалы</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50px;">№</th>
                <th>Наименование</th>
                <th>Артикул</th>
                <th style="width: 80px;">Ед.</th>
                <th style="width: 80px;">Количество</th>
                <th style="width: 100px;">Цена</th>
                <th style="width: 120px;">Сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalAmount = 0;
            foreach ($items as $index => $item): 
                $totalAmount += $item['line_total'];
            ?>
            <tr>
                <td class="num"><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td><?php echo htmlspecialchars($item['item_sku']); ?></td>
                <td><?php echo htmlspecialchars($item['item_unit']); ?></td>
                <td class="num"><strong><?php echo number_format($item['quantity_written'], 2); ?></strong></td>
                <td class="num"><?php echo number_format($item['unit_cost'], 2); ?> BYN</td>
                <td class="num"><?php echo number_format($item['line_total'], 2); ?> BYN</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align: right;"><strong>Итого:</strong></td>
                <td class="num"><strong><?php echo number_format($totalAmount, 2); ?> BYN</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p>Всего позиций: <strong><?php echo count($items); ?></strong></p>
        <p>Общая стоимость списанного: <strong><?php echo number_format($totalAmount, 2); ?> BYN</strong></p>
    </div>
    
    <div class="signatures">
        <div class="signature-block">
            <p>Составил:</p>
            <p>_____________________</p>
            <p>(_____________________)</p>
        </div>
        <div class="signature-block">
            <p>Утверждаю:</p>
            <p>_____________________</p>
            <p>(_____________________)</p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

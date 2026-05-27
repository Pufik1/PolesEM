<?php
/**
 * Edit Goods Receipt Document
 * For OAO "Polesieelectromash" ERP System
 */

require_once '../../includes/config.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

if (!hasRole(['admin', 'director', 'manager', 'accountant'])) {
    redirect('../../dashboard.php');
}

$pdo = getDBConnection();
$receiptId = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($receiptId <= 0) {
    die('Неверный ID документа');
}

try {
    $stmt = $pdo->prepare("
        SELECT grd.*, 
               u.full_name as created_by_name,
               p.production_number as production_order_number,
               wc.center_name as warehouse_name
        FROM goods_receipt_documents grd
        LEFT JOIN users u ON grd.created_by = u.id
        LEFT JOIN production_orders p ON grd.production_order_id = p.id
        LEFT JOIN work_centers wc ON grd.warehouse_id = wc.id
        WHERE grd.id = :id
    ");
    $stmt->execute([':id' => $receiptId]);
    $receipt = $stmt->fetch();
    
    if (!$receipt) {
        die('Документ не найден');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM goods_receipt_items WHERE receipt_id = :receipt_id ORDER BY id ASC");
    $stmt->execute([':receipt_id' => $receiptId]);
    $items = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, center_name FROM work_centers WHERE center_type = 'warehouse' ORDER BY center_name");
    $warehouses = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Ошибка: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $receiptDate = $_POST['receipt_date'];
        $receiptType = $_POST['receipt_type'];
        $warehouseId = (int)$_POST['warehouse_id'];
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);
        
        $stmt = $pdo->prepare("
            UPDATE goods_receipt_documents 
            SET receipt_date = :receipt_date, receipt_type = :receipt_type,
                warehouse_id = :warehouse_id, status = :status, notes = :notes
            WHERE id = :id
        ");
        $stmt->execute([
            ':receipt_date' => $receiptDate, ':receipt_type' => $receiptType,
            ':warehouse_id' => $warehouseId, ':status' => $status, ':notes' => $notes, ':id' => $receiptId
        ]);
        
        $itemIds = $_POST['item_id'] ?? [];
        $itemNames = $_POST['item_name'] ?? [];
        $itemSkus = $_POST['item_sku'] ?? [];
        $itemUnits = $_POST['item_unit'] ?? [];
        $quantities = $_POST['quantity_received'] ?? [];
        $batchNumbers = $_POST['batch_number'] ?? [];
        $storageZones = $_POST['storage_zone'] ?? [];
        
        $existingItemIds = array_map(function($item) { return $item['id']; }, $items);
        $itemsToDelete = array_diff($existingItemIds, $itemIds);
        
        if (!empty($itemsToDelete)) {
            $placeholders = implode(',', array_fill(0, count($itemsToDelete), '?'));
            $stmt = $pdo->prepare("DELETE FROM goods_receipt_items WHERE id IN ($placeholders)");
            $stmt->execute($itemsToDelete);
        }
        
        foreach ($itemIds as $index => $itemId) {
            if (!empty($itemId) && is_numeric($itemId)) {
                $stmt = $pdo->prepare("
                    UPDATE goods_receipt_items 
                    SET item_name = :item_name, item_sku = :item_sku, item_unit = :item_unit,
                        quantity_received = :quantity_received, batch_number = :batch_number, storage_zone = :storage_zone
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':item_name' => $itemNames[$index], ':item_sku' => $itemSkus[$index],
                    ':item_unit' => $itemUnits[$index], ':quantity_received' => $quantities[$index],
                    ':batch_number' => $batchNumbers[$index], ':storage_zone' => $storageZones[$index], ':id' => $itemId
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO goods_receipt_items 
                    (receipt_id, item_type, product_id, item_name, item_sku, item_unit, quantity_received, batch_number, storage_zone)
                    VALUES (:receipt_id, 'product', NULL, :item_name, :item_sku, :item_unit, :quantity_received, :batch_number, :storage_zone)
                ");
                $stmt->execute([
                    ':receipt_id' => $receiptId, ':item_name' => $itemNames[$index],
                    ':item_sku' => $itemSkus[$index], ':item_unit' => $itemUnits[$index],
                    ':quantity_received' => $quantities[$index], ':batch_number' => $batchNumbers[$index],
                    ':storage_zone' => $storageZones[$index]
                ]);
            }
        }
        
        $totalItems = count($itemIds);
        $totalQuantity = array_sum($quantities);
        
        $stmt = $pdo->prepare("UPDATE goods_receipt_documents SET total_items = :total_items, total_quantity = :total_quantity WHERE id = :id");
        $stmt->execute([':total_items' => $totalItems, ':total_quantity' => $totalQuantity, ':id' => $receiptId]);
        
        $pdo->commit();
        logActivity($pdo, $_SESSION['user_id'], 'Редактирование акта приема', 'goods_receipt_documents', $receiptId);
        $success = 'Документ успешно обновлен';
        
        $stmt = $pdo->prepare("SELECT * FROM goods_receipt_items WHERE receipt_id = :receipt_id ORDER BY id ASC");
        $stmt->execute([':receipt_id' => $receiptId]);
        $items = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("
            SELECT grd.*, u.full_name as created_by_name, p.production_number as production_order_number, wc.center_name as warehouse_name
            FROM goods_receipt_documents grd
            LEFT JOIN users u ON grd.created_by = u.id
            LEFT JOIN production_orders p ON grd.production_order_id = p.id
            LEFT JOIN work_centers wc ON grd.warehouse_id = wc.id
            WHERE grd.id = :id
        ");
        $stmt->execute([':id' => $receiptId]);
        $receipt = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование акта приема №<?php echo htmlspecialchars($receipt['receipt_number']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0 0 10px 0; font-size: 24px; }
        .form-section { background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-section h3 { margin-top: 0; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #374151; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .items-table th { background-color: #f5f5f5; font-weight: bold; }
        .items-table input { width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; }
        .btn-remove { background-color: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .btn-add { background-color: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-bottom: 15px; }
        .actions { margin-top: 20px; display: flex; gap: 10px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Редактирование акта приема товаров</h1>
        <p><strong>№ <?php echo htmlspecialchars($receipt['receipt_number']); ?></strong></p>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-section">
            <h3>Основная информация</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Номер документа</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($receipt['receipt_number']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Дата приема *</label>
                    <input type="date" name="receipt_date" class="form-control" required value="<?php echo htmlspecialchars($receipt['receipt_date']); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Тип приема *</label>
                    <select name="receipt_type" class="form-control" required>
                        <option value="from_production" <?php echo $receipt['receipt_type'] === 'from_production' ? 'selected' : ''; ?>>Из производства</option>
                        <option value="from_supplier" <?php echo $receipt['receipt_type'] === 'from_supplier' ? 'selected' : ''; ?>>От поставщика</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Склад *</label>
                    <select name="warehouse_id" class="form-control" required>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>" <?php echo $receipt['warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($warehouse['center_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Статус *</label>
                    <select name="status" class="form-control" required>
                        <option value="draft" <?php echo $receipt['status'] === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                        <option value="confirmed" <?php echo $receipt['status'] === 'confirmed' ? 'selected' : ''; ?>>Подтвержден</option>
                        <option value="posted" <?php echo $receipt['status'] === 'posted' ? 'selected' : ''; ?>>Проведен</option>
                        <option value="cancelled" <?php echo $receipt['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Примечание</label>
                <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($receipt['notes'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Товары</h3>
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">№</th>
                        <th>Наименование</th>
                        <th>Артикул</th>
                        <th style="width: 80px;">Ед.</th>
                        <th style="width: 100px;">Количество</th>
                        <th>Серия/Партия</th>
                        <th>Зона хранения</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><input type="text" name="item_name[]" value="<?php echo htmlspecialchars($item['item_name']); ?>" required></td>
                        <td><input type="text" name="item_sku[]" value="<?php echo htmlspecialchars($item['item_sku'] ?? ''); ?>"></td>
                        <td><input type="text" name="item_unit[]" value="<?php echo htmlspecialchars($item['item_unit'] ?? 'шт'); ?>"></td>
                        <td><input type="number" step="0.01" name="quantity_received[]" value="<?php echo number_format($item['quantity_received'], 2); ?>" required min="0"></td>
                        <td><input type="text" name="batch_number[]" value="<?php echo htmlspecialchars($item['batch_number'] ?? ''); ?>"></td>
                        <td><input type="text" name="storage_zone[]" value="<?php echo htmlspecialchars($item['storage_zone'] ?? ''); ?>"></td>
                        <td>
                            <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                            <button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="btn-add" onclick="addRow()"><i class="fas fa-plus"></i> Добавить позицию</button>
        </div>
        
        <div class="actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить изменения</button>
            <a href="documents.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Назад к документам</a>
            <a href="print_receipt.php?id=<?php echo $receiptId; ?>" class="btn btn-success" target="_blank"><i class="fas fa-print"></i> Печать</a>
        </div>
    </form>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        let rowCount = <?php echo count($items); ?>;
        function addRow() {
            rowCount++;
            const tbody = document.querySelector('#itemsTable tbody');
            const row = document.createElement('tr');
            row.innerHTML = '<td>' + rowCount + '</td><td><input type="text" name="item_name[]" required></td><td><input type="text" name="item_sku[]"></td><td><input type="text" name="item_unit[]" value="шт"></td><td><input type="number" step="0.01" name="quantity_received[]" value="0" required min="0"></td><td><input type="text" name="batch_number[]"></td><td><input type="text" name="storage_zone[]"></td><td><input type="hidden" name="item_id[]" value=""><button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>';
            tbody.appendChild(row);
        }
        function removeRow(button) { const row = button.closest('tr'); row.remove(); updateRowNumbers(); }
        function updateRowNumbers() {
            const rows = document.querySelectorAll('#itemsTable tbody tr');
            rows.forEach((row, index) => { row.querySelector('td:first-child').textContent = index + 1; });
            rowCount = rows.length;
        }
    </script>
</body>
</html>

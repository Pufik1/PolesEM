<?php
/**
 * Edit Write-off Document (Акт списания)
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
$writeoffId = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($writeoffId <= 0) {
    die('Неверный ID документа');
}

try {
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
    
    $stmt = $pdo->prepare("SELECT * FROM material_writeoff_items WHERE writeoff_id = :writeoff_id ORDER BY id ASC");
    $stmt->execute([':writeoff_id' => $writeoffId]);
    $items = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, center_name FROM work_centers WHERE center_type = 'warehouse' ORDER BY center_name");
    $warehouses = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Ошибка: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $documentDate = $_POST['document_date'];
        $writeoffType = $_POST['writeoff_type'];
        $warehouseId = (int)$_POST['warehouse_id'];
        $status = $_POST['status'];
        $reason = trim($_POST['reason']);
        $notes = trim($_POST['notes']);
        
        $stmt = $pdo->prepare("
            UPDATE material_writeoff_documents 
            SET document_date = :document_date, writeoff_type = :writeoff_type,
                warehouse_id = :warehouse_id, status = :status, reason = :reason, notes = :notes
            WHERE id = :id
        ");
        $stmt->execute([
            ':document_date' => $documentDate, ':writeoff_type' => $writeoffType,
            ':warehouse_id' => $warehouseId, ':status' => $status, ':reason' => $reason, ':notes' => $notes, ':id' => $writeoffId
        ]);
        
        $itemIds = $_POST['item_id'] ?? [];
        $itemTypes = $_POST['item_type'] ?? [];
        $materialIds = $_POST['material_id'] ?? [];
        $productIds = $_POST['product_id'] ?? [];
        $itemNames = $_POST['item_name'] ?? [];
        $itemSkus = $_POST['item_sku'] ?? [];
        $quantities = $_POST['quantity_written'] ?? [];
        $unitCosts = $_POST['unit_cost'] ?? [];
        $lineTotals = $_POST['line_total'] ?? [];
        $batchNumbers = $_POST['batch_number'] ?? [];
        
        $existingItemIds = array_map(function($item) { return $item['id']; }, $items);
        $itemsToDelete = array_diff($existingItemIds, $itemIds);
        
        if (!empty($itemsToDelete)) {
            $placeholders = implode(',', array_fill(0, count($itemsToDelete), '?'));
            $stmt = $pdo->prepare("DELETE FROM material_writeoff_items WHERE id IN ($placeholders)");
            $stmt->execute($itemsToDelete);
        }
        
        foreach ($itemIds as $index => $itemId) {
            $materialId = !empty($materialIds[$index]) ? (int)$materialIds[$index] : null;
            $productId = !empty($productIds[$index]) ? (int)$productIds[$index] : null;
            $unitCost = !empty($unitCosts[$index]) ? (float)$unitCosts[$index] : 0;
            $quantity = !empty($quantities[$index]) ? (float)$quantities[$index] : 0;
            $lineTotal = $unitCost * $quantity;
            
            if (!empty($itemId) && is_numeric($itemId)) {
                $stmt = $pdo->prepare("
                    UPDATE material_writeoff_items 
                    SET item_type = :item_type, material_id = :material_id, product_id = :product_id,
                        item_name = :item_name, item_sku = :item_sku,
                        quantity_written = :quantity_written, unit_cost = :unit_cost, line_total = :line_total,
                        batch_number = :batch_number
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':item_type' => $itemTypes[$index], ':material_id' => $materialId, ':product_id' => $productId,
                    ':item_name' => $itemNames[$index], ':item_sku' => $itemSkus[$index],
                    ':quantity_written' => $quantity,
                    ':unit_cost' => $unitCost, ':line_total' => $lineTotal,
                    ':batch_number' => $batchNumbers[$index], ':id' => $itemId
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO material_writeoff_items 
                    (writeoff_id, item_type, material_id, product_id, item_name, item_sku, 
                     quantity_written, unit_cost, line_total, batch_number)
                    VALUES (:writeoff_id, :item_type, :material_id, :product_id, :item_name, :item_sku, 
                            :quantity_written, :unit_cost, :line_total, :batch_number)
                ");
                $stmt->execute([
                    ':writeoff_id' => $writeoffId, ':item_type' => $itemTypes[$index],
                    ':material_id' => $materialId, ':product_id' => $productId,
                    ':item_name' => $itemNames[$index], ':item_sku' => $itemSkus[$index],
                    ':quantity_written' => $quantity,
                    ':unit_cost' => $unitCost, ':line_total' => $lineTotal,
                    ':batch_number' => $batchNumbers[$index]
                ]);
            }
        }
        
        $totalItems = count($itemIds);
        $totalQuantity = array_sum($quantities);
        $totalCost = array_sum($lineTotals);
        
        $stmt = $pdo->prepare("UPDATE material_writeoff_documents SET total_items = :total_items, total_quantity = :total_quantity, total_cost = :total_cost WHERE id = :id");
        $stmt->execute([':total_items' => $totalItems, ':total_quantity' => $totalQuantity, ':total_cost' => $totalCost, ':id' => $writeoffId]);
        
        $pdo->commit();
        logActivity($pdo, $_SESSION['user_id'], 'Редактирование акта списания', 'material_writeoff_documents', $writeoffId);
        $success = 'Документ успешно обновлен';
        
        $stmt = $pdo->prepare("SELECT * FROM material_writeoff_items WHERE writeoff_id = :writeoff_id ORDER BY id ASC");
        $stmt->execute([':writeoff_id' => $writeoffId]);
        $items = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("
            SELECT wd.*, u.full_name as created_by_name, wc.center_name as warehouse_name
            FROM material_writeoff_documents wd
            LEFT JOIN users u ON wd.created_by = u.id
            LEFT JOIN work_centers wc ON wd.warehouse_id = wc.id
            WHERE wd.id = :id
        ");
        $stmt->execute([':id' => $writeoffId]);
        $writeoff = $stmt->fetch();
        
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
    <title>Редактирование акта списания №<?php echo htmlspecialchars($writeoff['document_number']); ?></title>
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
        <h1>Редактирование акта списания</h1>
        <p><strong>№ <?php echo htmlspecialchars($writeoff['document_number']); ?></strong></p>
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
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($writeoff['document_number']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Дата списания *</label>
                    <input type="date" name="document_date" class="form-control" required value="<?php echo htmlspecialchars($writeoff['document_date']); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Тип списания *</label>
                    <select name="writeoff_type" class="form-control" required>
                        <option value="material" <?php echo $writeoff['writeoff_type'] === 'material' ? 'selected' : ''; ?>>Материалы</option>
                        <option value="product" <?php echo $writeoff['writeoff_type'] === 'product' ? 'selected' : ''; ?>>Готовая продукция</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Склад *</label>
                    <select name="warehouse_id" class="form-control" required>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>" <?php echo $writeoff['warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($warehouse['center_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Статус *</label>
                    <select name="status" class="form-control" required>
                        <option value="draft" <?php echo $writeoff['status'] === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                        <option value="confirmed" <?php echo $writeoff['status'] === 'confirmed' ? 'selected' : ''; ?>>Подтвержден</option>
                        <option value="posted" <?php echo $writeoff['status'] === 'posted' ? 'selected' : ''; ?>>Проведен</option>
                        <option value="cancelled" <?php echo $writeoff['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Причина списания *</label>
                    <textarea name="reason" class="form-control" rows="2" required><?php echo htmlspecialchars($writeoff['reason'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="form-group">
                <label>Примечание</label>
                <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($writeoff['notes'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Списанные товары/материалы</h3>
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">№</th>
                        <th>Наименование</th>
                        <th>Артикул</th>
                        <th style="width: 100px;">Количество</th>
                        <th style="width: 100px;">Цена</th>
                        <th style="width: 100px;">Сумма</th>
                        <th>Серия/Партия</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><input type="text" name="item_name[]" value="<?php echo htmlspecialchars($item['item_name']); ?>" required></td>
                        <td><input type="text" name="item_sku[]" value="<?php echo htmlspecialchars($item['item_sku'] ?? ''); ?>"></td>
                        <td><input type="number" step="0.01" name="quantity_written[]" value="<?php echo number_format($item['quantity_written'], 2); ?>" required min="0"></td>
                        <td><input type="number" step="0.01" name="unit_cost[]" value="<?php echo number_format($item['unit_cost'] ?? 0, 2); ?>" min="0"></td>
                        <td><input type="number" step="0.01" name="line_total[]" value="<?php echo number_format($item['line_total'] ?? 0, 2); ?>" readonly></td>
                        <td><input type="text" name="batch_number[]" value="<?php echo htmlspecialchars($item['batch_number'] ?? ''); ?>"></td>
                        <td>
                            <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="item_type[]" value="<?php echo htmlspecialchars($item['item_type'] ?? 'material'); ?>">
                            <input type="hidden" name="material_id[]" value="<?php echo $item['material_id'] ?? ''; ?>">
                            <input type="hidden" name="product_id[]" value="<?php echo $item['product_id'] ?? ''; ?>">
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
            <a href="print_writeoff.php?id=<?php echo $writeoffId; ?>" class="btn btn-success" target="_blank"><i class="fas fa-print"></i> Печать</a>
        </div>
    </form>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        let rowCount = <?php echo count($items); ?>;
        function addRow() {
            rowCount++;
            const tbody = document.querySelector('#itemsTable tbody');
            const row = document.createElement('tr');
            row.innerHTML = '<td>' + rowCount + '</td><td><input type="text" name="item_name[]" required></td><td><input type="text" name="item_sku[]"></td><td><input type="number" step="0.01" name="quantity_written[]" value="0" required min="0"></td><td><input type="number" step="0.01" name="unit_cost[]" value="0" min="0"></td><td><input type="number" step="0.01" name="line_total[]" value="0" readonly></td><td><input type="text" name="batch_number[]"></td><td><input type="hidden" name="item_id[]" value=""><input type="hidden" name="item_type[]" value="material"><input type="hidden" name="material_id[]" value=""><input type="hidden" name="product_id[]" value=""><button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>';
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

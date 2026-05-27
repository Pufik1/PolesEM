<?php
/**
 * Edit Shipment Document
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
$shipmentId = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($shipmentId <= 0) {
    die('Неверный ID документа');
}

try {
    $stmt = $pdo->prepare("
        SELECT sd.*, 
               u.full_name as created_by_name,
               o.order_number as order_number,
               c.company_name as customer_name,
               wc.center_name as warehouse_name
        FROM shipment_documents sd
        LEFT JOIN users u ON sd.created_by = u.id
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
    
    $stmt = $pdo->prepare("SELECT * FROM shipment_items WHERE shipment_id = :shipment_id ORDER BY id ASC");
    $stmt->execute([':shipment_id' => $shipmentId]);
    $items = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, center_name FROM work_centers WHERE center_type = 'warehouse' ORDER BY center_name");
    $warehouses = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Ошибка: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $shipmentDate = $_POST['shipment_date'];
        $shipmentType = $_POST['shipment_type'];
        $warehouseId = (int)$_POST['warehouse_from_id'];
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);
        
        $stmt = $pdo->prepare("
            UPDATE shipment_documents 
            SET shipment_date = :shipment_date, shipment_type = :shipment_type,
                warehouse_from_id = :warehouse_from_id, status = :status, notes = :notes
            WHERE id = :id
        ");
        $stmt->execute([
            ':shipment_date' => $shipmentDate, ':shipment_type' => $shipmentType,
            ':warehouse_from_id' => $warehouseId, ':status' => $status, ':notes' => $notes, ':id' => $shipmentId
        ]);
        
        $itemIds = $_POST['item_id'] ?? [];
        $itemNames = $_POST['item_name'] ?? [];
        $itemSkus = $_POST['item_sku'] ?? [];
        $itemUnits = $_POST['item_unit'] ?? [];
        $quantities = $_POST['quantity_shipped'] ?? [];
        
        $existingItemIds = array_map(function($item) { return $item['id']; }, $items);
        $itemsToDelete = array_diff($existingItemIds, $itemIds);
        
        if (!empty($itemsToDelete)) {
            $placeholders = implode(',', array_fill(0, count($itemsToDelete), '?'));
            $stmt = $pdo->prepare("DELETE FROM shipment_items WHERE id IN ($placeholders)");
            $stmt->execute($itemsToDelete);
        }
        
        foreach ($itemIds as $index => $itemId) {
            if (!empty($itemId) && is_numeric($itemId)) {
                $stmt = $pdo->prepare("
                    UPDATE shipment_items 
                    SET item_name = :item_name, item_sku = :item_sku, item_unit = :item_unit,
                        quantity_shipped = :quantity_shipped
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':item_name' => $itemNames[$index], ':item_sku' => $itemSkus[$index],
                    ':item_unit' => $itemUnits[$index], ':quantity_shipped' => $quantities[$index], ':id' => $itemId
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO shipment_items 
                    (shipment_id, product_id, item_name, item_sku, item_unit, quantity_ordered, quantity_shipped)
                    VALUES (:shipment_id, NULL, :item_name, :item_sku, :item_unit, :quantity_ordered, :quantity_shipped)
                ");
                $stmt->execute([
                    ':shipment_id' => $shipmentId, ':item_name' => $itemNames[$index],
                    ':item_sku' => $itemSkus[$index], ':item_unit' => $itemUnits[$index],
                    ':quantity_ordered' => $quantities[$index], ':quantity_shipped' => $quantities[$index]
                ]);
            }
        }
        
        $totalItems = count($itemIds);
        $totalQuantity = array_sum($quantities);
        
        $stmt = $pdo->prepare("UPDATE shipment_documents SET total_items = :total_items, total_quantity = :total_quantity WHERE id = :id");
        $stmt->execute([':total_items' => $totalItems, ':total_quantity' => $totalQuantity, ':id' => $shipmentId]);
        
        $pdo->commit();
        logActivity($pdo, $_SESSION['user_id'], 'Редактирование накладной на отгрузку', 'shipment_documents', $shipmentId);
        $success = 'Документ успешно обновлен';
        
        $stmt = $pdo->prepare("SELECT * FROM shipment_items WHERE shipment_id = :shipment_id ORDER BY id ASC");
        $stmt->execute([':shipment_id' => $shipmentId]);
        $items = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("
            SELECT sd.*, u.full_name as created_by_name, o.order_number as order_number, c.company_name as customer_name, wc.center_name as warehouse_name
            FROM shipment_documents sd
            LEFT JOIN users u ON sd.created_by = u.id
            LEFT JOIN orders o ON sd.order_id = o.id
            LEFT JOIN clients c ON sd.customer_id = c.id
            LEFT JOIN work_centers wc ON sd.warehouse_from_id = wc.id
            WHERE sd.id = :id
        ");
        $stmt->execute([':id' => $shipmentId]);
        $shipment = $stmt->fetch();
        
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
    <title>Редактирование накладной на отгрузку №<?php echo htmlspecialchars($shipment['shipment_number']); ?></title>
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
        <h1>Редактирование накладной на отгрузку</h1>
        <p><strong>№ <?php echo htmlspecialchars($shipment['shipment_number']); ?></strong></p>
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
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($shipment['shipment_number']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Дата отгрузки *</label>
                    <input type="date" name="shipment_date" class="form-control" required value="<?php echo htmlspecialchars($shipment['shipment_date']); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Тип отгрузки *</label>
                    <select name="shipment_type" class="form-control" required>
                        <option value="to_customer" <?php echo $shipment['shipment_type'] === 'to_customer' ? 'selected' : ''; ?>>Покупателю</option>
                        <option value="to_warehouse" <?php echo $shipment['shipment_type'] === 'to_warehouse' ? 'selected' : ''; ?>>На склад</option>
                        <option value="return" <?php echo $shipment['shipment_type'] === 'return' ? 'selected' : ''; ?>>Возврат</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Склад отгрузки *</label>
                    <select name="warehouse_from_id" class="form-control" required>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>" <?php echo $shipment['warehouse_from_id'] == $warehouse['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($warehouse['center_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Статус *</label>
                    <select name="status" class="form-control" required>
                        <option value="draft" <?php echo $shipment['status'] === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                        <option value="confirmed" <?php echo $shipment['status'] === 'confirmed' ? 'selected' : ''; ?>>Подтвержден</option>
                        <option value="shipped" <?php echo $shipment['status'] === 'shipped' ? 'selected' : ''; ?>>Отгружен</option>
                        <option value="delivered" <?php echo $shipment['status'] === 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                        <option value="cancelled" <?php echo $shipment['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Примечание</label>
                <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($shipment['notes'] ?? ''); ?></textarea>
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
                        <td><input type="number" step="0.01" name="quantity_shipped[]" value="<?php echo number_format($item['quantity_shipped'], 2); ?>" required min="0"></td>
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
            <a href="print_shipment.php?id=<?php echo $shipmentId; ?>" class="btn btn-success" target="_blank"><i class="fas fa-print"></i> Печать</a>
        </div>
    </form>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        let rowCount = <?php echo count($items); ?>;
        function addRow() {
            rowCount++;
            const tbody = document.querySelector('#itemsTable tbody');
            const row = document.createElement('tr');
            row.innerHTML = '<td>' + rowCount + '</td><td><input type="text" name="item_name[]" required></td><td><input type="text" name="item_sku[]"></td><td><input type="text" name="item_unit[]" value="шт"></td><td><input type="number" step="0.01" name="quantity_shipped[]" value="0" required min="0"></td><td><input type="hidden" name="item_id[]" value=""><button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>';
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

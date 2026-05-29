<?php
/**
 * Warehouse module for OAO "Polesieelectromash" ERP System
 * Warehouse management: inventory, operations (income/outcome/transfer/write-off)
 * Separated sections for Materials and Finished Products with advanced filtering
 */

// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../../logs/php_error.log');

// Обработчик фатальных ошибок
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<pre style='background:#fdd;padding:20px;border:1px solid red;'>";
        echo "<h2 style='color:red;'>Фатальная ошибка PHP!</h2>";
        echo "<strong>Тип ошибки:</strong> " . $error['type'] . "<br>";
        echo "<strong>Сообщение:</strong> " . $error['message'] . "<br>";
        echo "<strong>Файл:</strong> " . $error['file'] . "<br>";
        echo "<strong>Строка:</strong> " . $error['line'] . "<br>";
        echo "</pre>";
        exit;
    }
});

// Дополнительная отладка - выводим все ошибки в реальном времени
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<pre style='background:#ffeb3b;padding:10px;margin:10px 0;border:1px solid orange;'>";
    echo "<strong>Ошибка:</strong> $errstr<br>";
    echo "<strong>Файл:</strong> $errfile<br>";
    echo "<strong>Строка:</strong> $errline<br>";
    echo "</pre>";
    return false; // Чтобы ошибка также попала в стандартный обработчик
});

require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

$pdo = getDBConnection();
$error = '';
$success = '';
$userFullName = $_SESSION['full_name'];
$userRole = $_SESSION['user_role'];
$initials = strtoupper(substr($userFullName, 0, 1));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'income_product') {
            // Приход готовой продукции на склад с созданием документа приема
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $batch_number = trim($_POST['batch_number']);
            $notes = trim($_POST['notes']);
            
            // Генерируем номер акта приема если не указан
            if (empty($document_number)) {
                $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED)) as max_num FROM goods_receipt_documents WHERE receipt_number LIKE 'ПР-%'");
                $result = $stmt->fetch();
                $next_num = ($result['max_num'] ?? 0) + 1;
                $document_number = 'ПР-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
            }
            
            $pdo->beginTransaction();
            
            // Создаем документ приема
            $stmt = $pdo->prepare("INSERT INTO goods_receipt_documents 
                                   (receipt_number, receipt_date, receipt_type, production_order_id, warehouse_id, total_items, total_quantity, total_cost, status, notes, created_by) 
                                   VALUES (:receipt_number, CURRENT_DATE, 'from_production', NULL, 1, 1, :quantity, :total_cost, 'confirmed', :notes, :user_id)");
            $stmt->execute([
                ':receipt_number' => $document_number,
                ':quantity' => $quantity,
                ':total_cost' => 0,
                ':notes' => $notes,
                ':user_id' => $_SESSION['user_id']
            ]);
            $receipt_id = $pdo->lastInsertId();
            
            // Получаем данные о продукте для позиции
            $stmt = $pdo->prepare("SELECT product_name, product_code FROM products WHERE id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $product = $stmt->fetch();
            
            // Добавляем позицию в документ приема
            $stmt = $pdo->prepare("INSERT INTO goods_receipt_items 
                                   (receipt_id, item_type, product_id, item_name, item_sku, item_unit, quantity_received, batch_number, storage_zone) 
                                   VALUES (:receipt_id, 'product', :product_id, :item_name, :item_sku, 'шт', :quantity, :batch_number, 'А1')");
            $stmt->execute([
                ':receipt_id' => $receipt_id,
                ':product_id' => $product_id,
                ':item_name' => $product['product_name'],
                ':item_sku' => $product['product_code'],
                ':quantity' => $quantity,
                ':batch_number' => $batch_number
            ]);
            
            // Добавляем операцию прихода со ссылкой на документ
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, product_id, quantity, warehouse_to, user_id, document_number, batch_number, expiry_date, quality_cert, notes, receipt_id) 
                                   VALUES ('income', :product_id, :quantity, 1, :user_id, :document_number, :batch_number, NULL, NULL, :notes, :receipt_id)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':batch_number' => $batch_number,
                ':notes' => $notes,
                ':receipt_id' => $receipt_id
            ]);
            
            // Обновляем остаток товара
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':product_id' => $product_id
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Приход готовой продукции', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Готовая продукция успешно оприходована. Документ: ' . htmlspecialchars($document_number);
            
        } elseif ($action === 'income_material') {
            // Приход материалов от поставщика - полностью переписанная функция (исправленная версия)
            $material_id = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;
            $document_number = isset($_POST['document_number']) ? trim($_POST['document_number']) : '';
            $batch_number = isset($_POST['batch_number']) ? trim($_POST['batch_number']) : '';
            $quality_cert = isset($_POST['quality_cert']) ? trim($_POST['quality_cert']) : '';
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            // Проверка обязательных полей
            if ($material_id <= 0) {
                $error = 'Ошибка: Не выбран материал';
            } elseif ($quantity <= 0) {
                $error = 'Ошибка: Количество должно быть больше нуля';
            } else {
                // Получаем данные о материале
                $stmt = $pdo->prepare("SELECT name, sku, unit FROM materials WHERE id = :material_id");
                $stmt->execute([':material_id' => $material_id]);
                $materialData = $stmt->fetch();
                
                if (!$materialData) {
                    $error = 'Материал не найден';
                } else {
                    // Генерируем номер акта приема если не указан (для внутреннего использования)
                    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED)) as max_num FROM goods_receipt_documents WHERE receipt_number LIKE 'ПР-М-%'");
                    $result = $stmt->fetch();
                    $next_num = ($result['max_num'] ?? 0) + 1;
                    $internal_receipt_number = 'ПР-М-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                    
                    $pdo->beginTransaction();
                    
                    try {
                        // 1. Создаем документ приема (goods_receipt_documents)
                        $stmt = $pdo->prepare("INSERT INTO goods_receipt_documents 
                                               (receipt_number, receipt_date, receipt_type, warehouse_id, total_items, total_quantity, total_cost, status, notes, created_by) 
                                               VALUES (:receipt_number, CURRENT_DATE, 'from_supplier', 1, 1, :total_quantity, :total_cost, 'confirmed', :notes, :created_by)");
                        $stmt->execute([
                            ':receipt_number' => $internal_receipt_number,
                            ':total_quantity' => $quantity,
                            ':total_cost' => 0,
                            ':notes' => $notes,
                            ':created_by' => $_SESSION['user_id']
                        ]);
                        $receipt_id = $pdo->lastInsertId();
                        
                        // 2. Добавляем позицию в документ приема (goods_receipt_items)
                        // Важно: product_id должен быть NULL для материалов
                        $stmt = $pdo->prepare("INSERT INTO goods_receipt_items 
                                               (receipt_id, item_type, product_id, item_name, item_sku, item_unit, quantity_received, batch_number, storage_zone) 
                                               VALUES (:receipt_id, 'material', NULL, :item_name, :item_sku, :item_unit, :quantity_received, :batch_number, 'А1')");
                        $stmt->execute([
                            ':receipt_id' => $receipt_id,
                            ':item_name' => $materialData['name'],
                            ':item_sku' => $materialData['sku'] ?? '',
                            ':item_unit' => $materialData['unit'],
                            ':quantity_received' => $quantity,
                            ':batch_number' => $batch_number
                        ]);
                        
                        // 3. Добавляем операцию прихода материала (warehouse_operations)
                        // Важно: product_id должен быть явно указан как NULL для материалов
                        $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                               (operation_type, product_id, material_id, quantity, warehouse_to, user_id, document_number, batch_number, quality_cert, expiry_date, notes, receipt_id, operation_date) 
                                               VALUES ('income', NULL, :material_id, :quantity, 1, :user_id, :document_number, :batch_number, :quality_cert, :expiry_date, :notes, :receipt_id, NOW())");
                        $stmt->execute([
                            ':material_id' => $material_id,
                            ':quantity' => $quantity,
                            ':user_id' => $_SESSION['user_id'],
                            ':document_number' => $document_number,
                            ':batch_number' => $batch_number,
                            ':quality_cert' => $quality_cert,
                            ':expiry_date' => $expiry_date,
                            ':notes' => $notes,
                            ':receipt_id' => $receipt_id
                        ]);
                        $operation_id = $pdo->lastInsertId();
                        
                        // 4. Добавляем запись в движения материалов (material_stock_movements)
                        $stmt = $pdo->prepare("INSERT INTO material_stock_movements 
                                               (material_id, operation_type, quantity, warehouse_to, user_id, document_number, batch_number, production_order_id, notes) 
                                               VALUES (:material_id, 'income', :quantity, 1, :user_id, :document_number, :batch_number, NULL, :notes)");
                        $stmt->execute([
                            ':material_id' => $material_id,
                            ':quantity' => $quantity,
                            ':user_id' => $_SESSION['user_id'],
                            ':document_number' => $document_number,
                            ':batch_number' => $batch_number,
                            ':notes' => $notes
                        ]);
                        
                        // 5. Обновляем остаток материала
                        $stmt = $pdo->prepare("UPDATE materials SET current_stock = current_stock + :quantity WHERE id = :material_id");
                        $stmt->execute([
                            ':quantity' => $quantity,
                            ':material_id' => $material_id
                        ]);
                        
                        $pdo->commit();
                        logActivity($pdo, $_SESSION['user_id'], 'Приход материалов', 'warehouse_operations', $operation_id);
                        $success = 'Материалы успешно оприходованы. Документ: ' . htmlspecialchars($internal_receipt_number) . ' (накладная №' . htmlspecialchars($document_number) . ')';
                        
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $error = 'Ошибка при проведении операции: ' . $e->getMessage();
                        error_log("Warehouse income_material error: " . $e->getMessage());
                    }
                }
            }
            
        } elseif ($action === 'ship_product') {
            // Отгрузка готовой продукции клиенту с созданием накладной
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT stock_quantity, product_name, product_code, base_price FROM products WHERE id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                $error = 'Товар не найден';
            } elseif ($product['stock_quantity'] < $quantity) {
                $error = 'Недостаточно товара на складе. Доступно: ' . $product['stock_quantity'];
            } else {
                
                // Генерируем номер накладной если не указан
                if (empty($document_number)) {
                    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(shipment_number, '-', -1) AS UNSIGNED)) as max_num FROM shipment_documents WHERE shipment_number LIKE 'ТН-%'");
                    $result = $stmt->fetch();
                    $next_num = ($result['max_num'] ?? 0) + 1;
                    $document_number = 'ТН-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                }
                
                $pdo->beginTransaction();
                
                // Вычисляем стоимость
                $unit_price = $product['base_price'] ?? 0;
                $total_cost = $unit_price * $quantity;
                $vat_rate = 20;
                $line_total = $unit_price * $quantity;
                $vat_amount = $line_total * ($vat_rate / 100);
                $line_total_with_vat = $line_total + $vat_amount;
                
                // Создаем документ отгрузки (накладную)
                $stmt = $pdo->prepare("INSERT INTO shipment_documents 
                       (shipment_number, shipment_date, shipment_type, customer_name, warehouse_from_id, total_items, total_quantity, total_cost, status, notes, created_by) 
                       VALUES (?, CURRENT_DATE, 'to_customer', 'Прямая продажа', 1, 1, ?, ?, 'shipped', ?, ?)");
                $stmt->execute([
                    $document_number,
                    $quantity,
                    $total_cost,
                    $notes,
                    $_SESSION['user_id']
                ]);
                $shipment_id = $pdo->lastInsertId();
                
                // Добавляем позицию в документ отгрузки
                $stmt = $pdo->prepare("INSERT INTO shipment_items 
                       (shipment_id, product_id, item_name, item_sku, item_unit, quantity_ordered, quantity_shipped, unit_price, vat_rate, line_total, vat_amount, line_total_with_vat) 
                       VALUES (?, ?, ?, ?, 'шт', ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $shipment_id,
                    $product_id,
                    $product['product_name'],
                    $product['product_code'],
                    $quantity,
                    $quantity,
                    $unit_price,
                    $vat_rate,
                    $line_total,
                    $vat_amount,
                    $line_total_with_vat
                ]);
                
                // Добавляем операцию расхода со ссылкой на документ
                $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                       (operation_type, product_id, quantity, warehouse_from, user_id, document_number, notes, shipment_id, operation_date) 
                       VALUES ('outcome', ?, ?, 1, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $product_id,
                    $quantity,
                    $_SESSION['user_id'],
                    $document_number,
                    $notes,
                    $shipment_id
                ]);
                
                // Обновляем остаток товара
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);
                
                $pdo->commit();
                logActivity($pdo, $_SESSION['user_id'], 'Отгрузка готовой продукции', 'warehouse_operations', $pdo->lastInsertId());
                $success = 'Готовая продукция успешно отгружена. Документ: ' . htmlspecialchars($document_number);
            }
            
        } elseif ($action === 'outcome_material_batch') {
            // Массовая выдача материалов в производство с созданием одного документа списания
            $materials_data = isset($_POST['materials']) ? $_POST['materials'] : [];
            $document_number = isset($_POST['document_number']) ? trim($_POST['document_number']) : '';
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            if (empty($materials_data) || !is_array($materials_data)) {
                $error = 'Ошибка: Не выбраны материалы для выдачи';
            } else {
                // Проверяем достаточность количества для всех материалов
                foreach ($materials_data as $mat_data) {
                    $material_id = (int)$mat_data['material_id'];
                    $quantity = (float)$mat_data['quantity'];
                    
                    if ($material_id <= 0 || $quantity <= 0) {
                        continue;
                    }
                    
                    $stmt = $pdo->prepare("SELECT current_stock FROM materials WHERE id = :material_id");
                    $stmt->execute([':material_id' => $material_id]);
                    $material = $stmt->fetch();
                    
                    if (!$material || $material['current_stock'] < $quantity) {
                        $error = 'Недостаточно материала на складе (ID: ' . $material_id . ')';
                        break 2;
                    }
                }
                
                // Генерируем номер акта списания если не указан
                if (empty($document_number)) {
                    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(document_number, '-', -1) AS UNSIGNED)) as max_num FROM material_writeoff_documents WHERE document_number LIKE 'СП-М-%'");
                    $result = $stmt->fetch();
                    $next_num = ($result['max_num'] ?? 0) + 1;
                    $document_number = 'СП-М-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                }
                
                $pdo->beginTransaction();
                
                try {
                    $total_items = 0;
                    $total_quantity = 0;
                    $total_cost = 0;
                    $writeoff_items = [];
                    
                    // Сначала собираем данные для всех позиций
                    foreach ($materials_data as $mat_data) {
                        $material_id = (int)$mat_data['material_id'];
                        $quantity = (float)$mat_data['quantity'];
                        
                        if ($material_id <= 0 || $quantity <= 0) {
                            continue;
                        }
                        
                        // Получаем данные о материале
                        $stmt = $pdo->prepare("SELECT name, sku, unit, price_per_unit FROM materials WHERE id = :material_id");
                        $stmt->execute([':material_id' => $material_id]);
                        $materialData = $stmt->fetch();
                        
                        if (!$materialData) {
                            continue;
                        }
                        
                        // Вычисляем стоимость
                        $unit_cost = $materialData['price_per_unit'] ?? 0;
                        $line_total = $unit_cost * $quantity;
                        
                        $total_items++;
                        $total_quantity += $quantity;
                        $total_cost += $line_total;
                        
                        $writeoff_items[] = [
                            'material_id' => $material_id,
                            'name' => $materialData['name'],
                            'sku' => $materialData['sku'] ?? '',
                            'unit' => $materialData['unit'],
                            'quantity' => $quantity,
                            'unit_cost' => $unit_cost,
                            'line_total' => $line_total
                        ];
                    }
                    
                    if (empty($writeoff_items)) {
                        throw new Exception('Нет valid материалов для выдачи');
                    }
                    
                    // Создаем документ списания
                    $stmt = $pdo->prepare("INSERT INTO material_writeoff_documents 
                                           (document_number, document_date, writeoff_type, warehouse_id, total_items, total_quantity, total_cost, status, reason, created_by) 
                                           VALUES (:document_number, CURRENT_DATE, 'material', 1, :total_items, :total_quantity, :total_cost, 'confirmed', :reason, :user_id)");
                    $stmt->execute([
                        ':document_number' => $document_number,
                        ':total_items' => $total_items,
                        ':total_quantity' => $total_quantity,
                        ':total_cost' => $total_cost,
                        ':reason' => $notes,
                        ':user_id' => $_SESSION['user_id']
                    ]);
                    $writeoff_id = $pdo->lastInsertId();
                    
                    // Добавляем позиции, операции и обновляем остатки
                    foreach ($writeoff_items as $item) {
                        // Добавляем позицию в документ списания
                        $stmt = $pdo->prepare("INSERT INTO material_writeoff_items 
                                               (writeoff_id, item_type, material_id, product_id, item_name, item_sku, item_unit, quantity_written, unit_cost, line_total) 
                                               VALUES (:writeoff_id, 'material', :material_id, NULL, :item_name, :item_sku, :item_unit, :quantity, :unit_cost, :line_total)");
                        $stmt->execute([
                            ':writeoff_id' => $writeoff_id,
                            ':material_id' => $item['material_id'],
                            ':item_name' => $item['name'],
                            ':item_sku' => $item['sku'],
                            ':item_unit' => $item['unit'],
                            ':quantity' => $item['quantity'],
                            ':unit_cost' => $item['unit_cost'],
                            ':line_total' => $item['line_total']
                        ]);
                        
                        // Добавляем операцию расхода материала
                        $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                               (operation_type, material_id, quantity, warehouse_from, user_id, document_number, batch_number, expiry_date, quality_cert, notes, writeoff_id) 
                                               VALUES ('outcome', :material_id, :quantity, 1, :user_id, :document_number, NULL, NULL, NULL, :notes, :writeoff_id)");
                        $stmt->execute([
                            ':material_id' => $item['material_id'],
                            ':quantity' => $item['quantity'],
                            ':user_id' => $_SESSION['user_id'],
                            ':document_number' => $document_number,
                            ':notes' => $notes,
                            ':writeoff_id' => $writeoff_id
                        ]);
                        
                        // Добавляем запись в движения материалов
                        $stmt = $pdo->prepare("INSERT INTO material_stock_movements 
                                               (material_id, operation_type, quantity, warehouse_from, user_id, document_number, notes) 
                                               VALUES (:material_id, 'outcome', :quantity, 1, :user_id, :document_number, :notes)");
                        $stmt->execute([
                            ':material_id' => $item['material_id'],
                            ':quantity' => $item['quantity'],
                            ':user_id' => $_SESSION['user_id'],
                            ':document_number' => $document_number,
                            ':notes' => $notes
                        ]);
                        
                        // Обновляем остаток материала
                        $stmt = $pdo->prepare("UPDATE materials SET current_stock = current_stock - :quantity WHERE id = :material_id");
                        $stmt->execute([
                            ':quantity' => $item['quantity'],
                            ':material_id' => $item['material_id']
                        ]);
                        
                        // Добавляем запись в production_materials
                        $stmt = $pdo->prepare("INSERT INTO production_materials 
                                               (material_id, quantity_issued, unit, warehouse_document_id, created_by, status) 
                                               VALUES (:material_id, :quantity, :unit, :writeoff_id, :user_id, 'issued')");
                        $stmt->execute([
                            ':material_id' => $item['material_id'],
                            ':quantity' => $item['quantity'],
                            ':unit' => $item['unit'],
                            ':writeoff_id' => $writeoff_id,
                            ':user_id' => $_SESSION['user_id']
                        ]);
                    }
                    
                    $pdo->commit();
                    logActivity($pdo, $_SESSION['user_id'], 'Массовая выдача материалов', 'warehouse_operations', $writeoff_id);
                    $success = 'Материалы успешно выданы в производство (' . $total_items . ' поз.). Документ: ' . htmlspecialchars($document_number);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Ошибка при проведении операции: ' . $e->getMessage();
                    error_log("Warehouse outcome_material_batch error: " . $e->getMessage());
                }
            }
            
        } elseif ($action === 'outcome_material') {
            // Расход материалов со склада (в производство) с созданием документа списания
            $material_id = (int)$_POST['material_id'];
            $quantity = (float)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT current_stock FROM materials WHERE id = :material_id");
            $stmt->execute([':material_id' => $material_id]);
            $material = $stmt->fetch();
            
            if ($material['current_stock'] < $quantity) {
                $error = 'Недостаточно материала на складе';
            } else {
            
            // Генерируем номер акта списания если не указан
            if (empty($document_number)) {
                $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(document_number, '-', -1) AS UNSIGNED)) as max_num FROM material_writeoff_documents WHERE document_number LIKE 'СП-М-%'");
                $result = $stmt->fetch();
                $next_num = ($result['max_num'] ?? 0) + 1;
                $document_number = 'СП-М-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
            }
            
            $pdo->beginTransaction();
            
            // Получаем данные о материале для позиции
            $stmt = $pdo->prepare("SELECT name, sku, unit, price_per_unit FROM materials WHERE id = :material_id");
            $stmt->execute([':material_id' => $material_id]);
            $materialData = $stmt->fetch();
            
            // Вычисляем стоимость списания
            $unit_cost = $materialData['price_per_unit'] ?? 0;
            $line_total = $unit_cost * $quantity;
            
            // Создаем документ списания
            $stmt = $pdo->prepare("INSERT INTO material_writeoff_documents 
                                   (document_number, document_date, writeoff_type, warehouse_id, total_items, total_quantity, total_cost, status, reason, created_by) 
                                   VALUES (:document_number, CURRENT_DATE, 'material', 1, 1, :quantity, :total_cost, 'confirmed', :reason, :user_id)");
            $stmt->execute([
                ':document_number' => $document_number,
                ':quantity' => $quantity,
                ':total_cost' => $line_total,
                ':reason' => $notes,
                ':user_id' => $_SESSION['user_id']
            ]);
            $writeoff_id = $pdo->lastInsertId();
            
            // Добавляем позицию в документ списания
            $stmt = $pdo->prepare("INSERT INTO material_writeoff_items 
                                   (writeoff_id, item_type, material_id, product_id, item_name, item_sku, item_unit, quantity_written, unit_cost, line_total) 
                                   VALUES (:writeoff_id, 'material', :material_id, NULL, :item_name, :item_sku, :item_unit, :quantity, :unit_cost, :line_total)");
            $stmt->execute([
                ':writeoff_id' => $writeoff_id,
                ':material_id' => $material_id,
                ':item_name' => $materialData['name'],
                ':item_sku' => $materialData['sku'] ?? '',
                ':item_unit' => $materialData['unit'],
                ':quantity' => $quantity,
                ':unit_cost' => $unit_cost,
                ':line_total' => $line_total
            ]);
            
            // Добавляем операцию расхода материала со ссылкой на документ
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, material_id, quantity, warehouse_from, user_id, document_number, batch_number, expiry_date, quality_cert, notes, writeoff_id) 
                                   VALUES ('outcome', :material_id, :quantity, 1, :user_id, :document_number, NULL, NULL, NULL, :notes, :writeoff_id)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes,
                ':writeoff_id' => $writeoff_id
            ]);
            
            // Добавляем запись в движения материалов
            $stmt = $pdo->prepare("INSERT INTO material_stock_movements 
                                   (material_id, operation_type, quantity, warehouse_from, user_id, document_number, notes) 
                                   VALUES (:material_id, 'outcome', :quantity, 1, :user_id, :document_number, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
            ]);
            
            // Обновляем остаток материала
            $stmt = $pdo->prepare("UPDATE materials SET current_stock = current_stock - :quantity WHERE id = :material_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':material_id' => $material_id
            ]);
            
            // Добавляем запись в production_materials без привязки к заказу
            $stmt = $pdo->prepare("INSERT INTO production_materials 
                                   (material_id, quantity_issued, unit, warehouse_document_id, created_by, status) 
                                   VALUES (:material_id, :quantity, :unit, :writeoff_id, :user_id, 'issued')");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':unit' => $materialData['unit'],
                ':writeoff_id' => $writeoff_id,
                ':user_id' => $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Расход материалов', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Материалы успешно выданы в производство. Документ: ' . htmlspecialchars($document_number);
            }
            
        } elseif ($action === 'transfer_product') {
            // Перемещение готовой продукции между складами
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $warehouse_from = (int)$_POST['warehouse_from'];
            $warehouse_to = (int)$_POST['warehouse_to'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, product_id, quantity, warehouse_from, warehouse_to, user_id, document_number, batch_number, expiry_date, quality_cert, notes) 
                                   VALUES ('transfer', :product_id, :quantity, :warehouse_from, :warehouse_to, :user_id, :document_number, NULL, NULL, NULL, :notes)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':warehouse_from' => $warehouse_from,
                ':warehouse_to' => $warehouse_to,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Перемещение готовой продукции', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Готовая продукция успешно перемещена';
            
        } elseif ($action === 'transfer_material') {
            // Перемещение материалов между складами
            $material_id = (int)$_POST['material_id'];
            $quantity = (float)$_POST['quantity'];
            $warehouse_from = (int)$_POST['warehouse_from'];
            $warehouse_to = (int)$_POST['warehouse_to'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, material_id, quantity, warehouse_from, warehouse_to, user_id, document_number, batch_number, expiry_date, quality_cert, notes) 
                                   VALUES ('transfer', :material_id, :quantity, :warehouse_from, :warehouse_to, :user_id, :document_number, NULL, NULL, NULL, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':warehouse_from' => $warehouse_from,
                ':warehouse_to' => $warehouse_to,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
            ]);
            
            // Добавляем запись в движения материалов
            $stmt = $pdo->prepare("INSERT INTO material_stock_movements 
                                   (material_id, operation_type, quantity, warehouse_from, warehouse_to, user_id, document_number, notes) 
                                   VALUES (:material_id, 'transfer', :quantity, :warehouse_from, :warehouse_to, :user_id, :document_number, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':warehouse_from' => $warehouse_from,
                ':warehouse_to' => $warehouse_to,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Перемещение материалов', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Материалы успешно перемещены';
            
        } elseif ($action === 'write_off_product') {
            // Списание готовой продукции с созданием документа акта списания
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $product = $stmt->fetch();
            
            if ($product['stock_quantity'] < $quantity) {
                $error = 'Недостаточно товара на складе';
            } else {
            
            // Генерируем номер акта списания если не указан
            if (empty($document_number)) {
                $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(document_number, '-', -1) AS UNSIGNED)) as max_num FROM material_writeoff_documents WHERE document_number LIKE 'СП-П-%'");
                $result = $stmt->fetch();
                $next_num = ($result['max_num'] ?? 0) + 1;
                $document_number = 'СП-П-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
            }
            
            $pdo->beginTransaction();
            
            // Получаем данные о продукте для позиции
            $stmt = $pdo->prepare("SELECT product_name, product_code, base_price FROM products WHERE id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $productData = $stmt->fetch();
            
            // Вычисляем стоимость списания
            $unit_cost = $productData['base_price'] ?? 0;
            $line_total = $unit_cost * $quantity;
            
            // Создаем документ списания
            $stmt = $pdo->prepare("INSERT INTO material_writeoff_documents 
                                   (document_number, document_date, writeoff_type, warehouse_id, total_items, total_quantity, total_cost, status, reason, created_by) 
                                   VALUES (:document_number, CURRENT_DATE, 'product', 1, 1, :quantity, :total_cost, 'confirmed', :reason, :user_id)");
            $stmt->execute([
                ':document_number' => $document_number,
                ':quantity' => $quantity,
                ':total_cost' => $line_total,
                ':reason' => $notes,
                ':user_id' => $_SESSION['user_id']
            ]);
            $writeoff_id = $pdo->lastInsertId();
            
            // Добавляем позицию в документ списания
            $stmt = $pdo->prepare("INSERT INTO material_writeoff_items 
                                   (writeoff_id, item_type, material_id, product_id, item_name, item_sku, item_unit, quantity_written, unit_cost, line_total) 
                                   VALUES (:writeoff_id, 'product', NULL, :product_id, :item_name, :item_sku, 'шт', :quantity, :unit_cost, :line_total)");
            $stmt->execute([
                ':writeoff_id' => $writeoff_id,
                ':product_id' => $product_id,
                ':item_name' => $productData['product_name'],
                ':item_sku' => $productData['product_code'],
                ':quantity' => $quantity,
                ':unit_cost' => $unit_cost,
                ':line_total' => $line_total
            ]);
            
            // Добавляем операцию списания со ссылкой на документ
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, product_id, quantity, warehouse_from, user_id, document_number, batch_number, expiry_date, quality_cert, notes, writeoff_id) 
                                   VALUES ('write_off', :product_id, :quantity, 1, :user_id, :document_number, NULL, NULL, NULL, :notes, :writeoff_id)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes,
                ':writeoff_id' => $writeoff_id
            ]);
            
            // Обновляем остаток товара
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':product_id' => $product_id
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Списание готовой продукции', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Готовая продукция успешно списана. Документ: ' . htmlspecialchars($document_number);
            }
        } elseif ($action === 'write_off_material') {
            // Списание материалов с созданием документа акта списания
            $material_id = (int)$_POST['material_id'];
            $quantity = (float)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT current_stock FROM materials WHERE id = :material_id");
            $stmt->execute([':material_id' => $material_id]);
            $material = $stmt->fetch();
            
            if ($material['current_stock'] < $quantity) {
                $error = 'Недостаточно материала на складе';
            } else {
                // Генерируем номер акта списания если не указан
                if (empty($document_number)) {
                    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(document_number, '-', -1) AS UNSIGNED)) as max_num FROM material_writeoff_documents WHERE document_number LIKE 'СП-М-%'");
                    $result = $stmt->fetch();
                    $next_num = ($result['max_num'] ?? 0) + 1;
                    $document_number = 'СП-М-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                }
                
                $pdo->beginTransaction();
                
                // Получаем данные о материале для позиции
                $stmt = $pdo->prepare("SELECT name, sku, unit, price_per_unit FROM materials WHERE id = :material_id");
                $stmt->execute([':material_id' => $material_id]);
                $materialData = $stmt->fetch();
                
                // Вычисляем стоимость списания
                $unit_cost = $materialData['price_per_unit'] ?? 0;
                $line_total = $unit_cost * $quantity;
                
                // Создаем документ списания
                $stmt = $pdo->prepare("INSERT INTO material_writeoff_documents 
                                       (document_number, document_date, writeoff_type, warehouse_id, total_items, total_quantity, total_cost, status, reason, created_by) 
                                       VALUES (:document_number, CURRENT_DATE, 'material', 1, 1, :quantity, :total_cost, 'confirmed', :reason, :user_id)");
                $stmt->execute([
                    ':document_number' => $document_number,
                    ':quantity' => $quantity,
                    ':total_cost' => $line_total,
                    ':reason' => $notes,
                    ':user_id' => $_SESSION['user_id']
                ]);
                $writeoff_id = $pdo->lastInsertId();
                
                // Добавляем позицию в документ списания
                $stmt = $pdo->prepare("INSERT INTO material_writeoff_items 
                                       (writeoff_id, item_type, material_id, product_id, item_name, item_sku, item_unit, quantity_written, unit_cost, line_total) 
                                       VALUES (:writeoff_id, 'material', :material_id, NULL, :item_name, :item_sku, :item_unit, :quantity, :unit_cost, :line_total)");
                $stmt->execute([
                    ':writeoff_id' => $writeoff_id,
                    ':material_id' => $material_id,
                    ':item_name' => $materialData['name'],
                    ':item_sku' => $materialData['sku'] ?? '',
                    ':item_unit' => $materialData['unit'],
                    ':quantity' => $quantity,
                    ':unit_cost' => $unit_cost,
                    ':line_total' => $line_total
                ]);
                
                // Добавляем операцию списания со ссылкой на документ
                $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                       (operation_type, material_id, quantity, warehouse_from, user_id, document_number, batch_number, expiry_date, quality_cert, notes, writeoff_id) 
                                       VALUES ('write_off', :material_id, :quantity, 1, :user_id, :document_number, NULL, NULL, NULL, :notes, :writeoff_id)");
                $stmt->execute([
                    ':material_id' => $material_id,
                    ':quantity' => $quantity,
                    ':user_id' => $_SESSION['user_id'],
                    ':document_number' => $document_number,
                    ':notes' => $notes,
                    ':writeoff_id' => $writeoff_id
                ]);
                
                // Добавляем запись в движения материалов
                $stmt = $pdo->prepare("INSERT INTO material_stock_movements 
                                       (material_id, operation_type, quantity, warehouse_from, user_id, document_number, notes) 
                                       VALUES (:material_id, 'write_off', :quantity, 1, :user_id, :document_number, :notes)");
                $stmt->execute([
                    ':material_id' => $material_id,
                    ':quantity' => $quantity,
                    ':user_id' => $_SESSION['user_id'],
                    ':document_number' => $document_number,
                    ':notes' => $notes
                ]);
                
                // Обновляем остаток материала
                $stmt = $pdo->prepare("UPDATE materials SET current_stock = current_stock - :quantity WHERE id = :material_id");
                $stmt->execute([
                    ':quantity' => $quantity,
                    ':material_id' => $material_id
                ]);
                
                $pdo->commit();
                logActivity($pdo, $_SESSION['user_id'], 'Списание материалов', 'warehouse_operations', $pdo->lastInsertId());
                $success = 'Материалы успешно списаны. Документ: ' . htmlspecialchars($document_number);
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

// Get active tab
$activeTab = $_GET['tab'] ?? 'products';

// Get filter parameters
$filterCategory = $_GET['category'] ?? '';
$filterZone = $_GET['zone'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterLowStock = isset($_GET['low_stock']) ? true : false;

// Если активна вкладка документов, перенаправляем на documents.php
if ($activeTab === 'documents') {
    header('Location: documents.php');
    exit;
}

// Get all products with filters
try {
    // Products query with filters
    $productQuery = "
        SELECT p.*, pc.category_name 
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_active = 1
    ";
    $productParams = [];
    
    if ($filterCategory && $activeTab === 'products' && $filterCategory !== 'all') {
        $productQuery .= " AND p.category_id = :category_id";
        $productParams[':category_id'] = $filterCategory;
    }
    
    if ($filterSearch && $activeTab === 'products') {
        $productQuery .= " AND (p.product_code LIKE :search OR p.product_name LIKE :search)";
        $productParams[':search'] = "%{$filterSearch}%";
    }
    
    if ($filterLowStock && $activeTab === 'products') {
        $productQuery .= " AND p.stock_quantity <= p.min_stock_level";
    }
    
    $productQuery .= " ORDER BY p.product_name";
    
    $stmt = $pdo->prepare($productQuery);
    $stmt->execute($productParams);
    $products = $stmt->fetchAll();
    
    // Materials query with filters
    $materialQuery = "
        SELECT m.*, mc.category_name, mc.storage_zone as zone_code, wz.zone_name
        FROM materials m
        LEFT JOIN material_categories mc ON m.category_id = mc.id
        LEFT JOIN warehouse_zones wz ON mc.storage_zone = wz.zone_code
        WHERE m.is_active = 1
    ";
    $materialParams = [];
    
    if ($filterCategory && $activeTab === 'materials' && $filterCategory !== 'all') {
        $materialQuery .= " AND m.category_id = :category_id";
        $materialParams[':category_id'] = $filterCategory;
    }
    
    if ($filterZone && $activeTab === 'materials') {
        $materialQuery .= " AND mc.storage_zone = :zone";
        $materialParams[':zone'] = $filterZone;
    }
    
    if ($filterSearch && $activeTab === 'materials') {
        $materialQuery .= " AND (m.sku LIKE :search OR m.name LIKE :search)";
        $materialParams[':search'] = "%{$filterSearch}%";
    }
    
    if ($filterLowStock && $activeTab === 'materials') {
        $materialQuery .= " AND m.current_stock <= m.min_stock_level";
    }
    
    $materialQuery .= " ORDER BY m.name";
    
    $stmt = $pdo->prepare($materialQuery);
    $stmt->execute($materialParams);
    $materials = $stmt->fetchAll();
    
    // Get warehouses
    $stmt = $pdo->query("SELECT * FROM work_centers WHERE center_type = 'warehouse' AND is_active = 1 ORDER BY center_name");
    $warehouses = $stmt->fetchAll();
    
    // Get material categories
    $stmt = $pdo->query("SELECT * FROM material_categories ORDER BY category_name");
    $materialCategories = $stmt->fetchAll();
    
    // Get product categories
    $stmt = $pdo->query("SELECT * FROM product_categories ORDER BY category_name");
    $productCategories = $stmt->fetchAll();
    
    // Get warehouse zones
    $stmt = $pdo->query("SELECT * FROM warehouse_zones WHERE is_active = 1 ORDER BY zone_code");
    $warehouseZones = $stmt->fetchAll();
    
    // Get recent warehouse operations (both products and materials)
    $stmt = $pdo->prepare("
        SELECT wo.*, p.product_name, p.product_code, m.name as material_name, m.sku as material_sku, 
               u.full_name as user_name,
               wf.center_name as warehouse_from_name, wt.center_name as warehouse_to_name,
               CASE WHEN wo.product_id IS NOT NULL THEN 'product' ELSE 'material' END as item_type
        FROM warehouse_operations wo
        LEFT JOIN products p ON wo.product_id = p.id
        LEFT JOIN materials m ON wo.material_id = m.id
        LEFT JOIN users u ON wo.user_id = u.id
        LEFT JOIN work_centers wf ON wo.warehouse_from = wf.id
        LEFT JOIN work_centers wt ON wo.warehouse_to = wt.id
        ORDER BY wo.operation_date DESC
        LIMIT 50
    ");
    $stmt->execute();
    $operations = $stmt->fetchAll();
    
    // Get statistics for products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM warehouse_operations WHERE operation_type = 'income' AND product_id IS NOT NULL AND MONTH(operation_date) = MONTH(CURRENT_DATE())");
    $stats['product_income_count'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM warehouse_operations WHERE operation_type = 'outcome' AND product_id IS NOT NULL AND MONTH(operation_date) = MONTH(CURRENT_DATE())");
    $stats['product_outcome_count'] = $stmt->fetch()['count'];
    
    // Get statistics for materials
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM warehouse_operations WHERE operation_type = 'income' AND material_id IS NOT NULL AND MONTH(operation_date) = MONTH(CURRENT_DATE())");
    $stats['material_income_count'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM warehouse_operations WHERE operation_type = 'outcome' AND material_id IS NOT NULL AND MONTH(operation_date) = MONTH(CURRENT_DATE())");
    $stats['material_outcome_count'] = $stmt->fetch()['count'];
    
    // Low stock products
    $stmt = $pdo->query("
        SELECT p.*, pc.category_name 
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.stock_quantity <= p.min_stock_level AND p.is_active = 1
        ORDER BY p.stock_quantity ASC
        LIMIT 10
    ");
    $lowStockProducts = $stmt->fetchAll();
    
    // Low stock materials
    $stmt = $pdo->query("
        SELECT m.*, mc.category_name, wz.zone_name
        FROM materials m
        LEFT JOIN material_categories mc ON m.category_id = mc.id
        LEFT JOIN warehouse_zones wz ON mc.storage_zone = wz.zone_code
        WHERE m.current_stock <= m.min_stock_level AND m.is_active = 1
        ORDER BY m.current_stock ASC
        LIMIT 10
    ");
    $lowStockMaterials = $stmt->fetchAll();
    
    // Production orders for material issue
    $stmt = $pdo->query("SELECT id, production_number, product_id, quantity, status FROM production_orders WHERE status IN ('planned', 'in_progress') ORDER BY created_at DESC LIMIT 20");
    $productionOrders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Ошибка загрузки данных: ' . $e->getMessage();
    $products = [];
    $materials = [];
    $warehouses = [];
    $materialCategories = [];
    $productCategories = [];
    $warehouseZones = [];
    $operations = [];
    $lowStockProducts = [];
    $lowStockMaterials = [];
    $productionOrders = [];
    $stats = [
        'product_income_count' => 0, 'product_outcome_count' => 0,
        'material_income_count' => 0, 'material_outcome_count' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Склад - <?php echo APP_NAME; ?></title>
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
                        <h1>Управление складом</h1>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo $initials; ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($userFullName); ?></span>
                            <span class="user-role"><?php echo ucfirst($userRole); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="content-area">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #10b981;">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $activeTab === 'products' ? $stats['product_income_count'] : $stats['material_income_count']; ?></h3>
                            <p>Приходов за месяц</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #ef4444;">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $activeTab === 'products' ? $stats['product_outcome_count'] : $stats['material_outcome_count']; ?></h3>
                            <p>Расходов за месяц</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #3b82f6;">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($activeTab === 'products' ? $products : $materials); ?></h3>
                            <p><?php echo $activeTab === 'products' ? 'Видов продукции' : 'Видов материалов'; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f59e0b;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($activeTab === 'products' ? $lowStockProducts : $lowStockMaterials); ?></h3>
                            <p><?php echo $activeTab === 'products' ? 'Продукции с низким запасом' : 'Материалов с низким запасом'; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs for Products and Materials -->
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2 class="card-title">Складские запасы</h2>
                        <div class="tab-buttons">
                            <a href="?tab=products&category=<?php echo htmlspecialchars($filterCategory); ?>&search=<?php echo htmlspecialchars($filterSearch); ?>" 
                               class="btn <?php echo $activeTab === 'products' ? 'btn-primary' : 'btn-secondary'; ?>" 
                               style="padding: 8px 16px; margin-right: 10px;">
                                <i class="fas fa-box"></i> Готовая продукция
                            </a>
                            <a href="?tab=materials&category=<?php echo htmlspecialchars($filterCategory); ?>&zone=<?php echo htmlspecialchars($filterZone); ?>&search=<?php echo htmlspecialchars($filterSearch); ?>" 
                               class="btn <?php echo $activeTab === 'materials' ? 'btn-primary' : 'btn-secondary'; ?>" 
                               style="padding: 8px 16px; margin-right: 10px;">
                                <i class="fas fa-cubes"></i> Материалы
                            </a>
                            <a href="documents.php" 
                               class="btn <?php echo $activeTab === 'documents' ? 'btn-primary' : 'btn-secondary'; ?>" 
                               style="padding: 8px 16px;">
                                <i class="fas fa-file-alt"></i> Документы склада
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons for Products Tab -->
                <?php if ($activeTab === 'products'): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title">Операции с готовой продукцией</h2>
                    </div>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button class="btn btn-success" onclick="openModal('incomeProductModal')">
                            <i class="fas fa-plus"></i> Оприходование из производства
                        </button>
                        <button class="btn btn-info" onclick="openModal('outcomeModal')">
                            <i class="fas fa-truck"></i> Отгрузка клиенту
                        </button>
                        <button class="btn btn-warning" onclick="openModal('writeOffProductModal')">
                            <i class="fas fa-trash"></i> Списание (брак/повреждение)
                        </button>
                    </div>
                </div>
                
                <!-- Action Buttons for Materials Tab -->
                <?php elseif ($activeTab === 'materials'): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title">Операции с материалами</h2>
                    </div>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button class="btn btn-success" onclick="openModal('incomeMaterialModal')">
                            <i class="fas fa-plus"></i> Поступление от поставщика
                        </button>
                        <button class="btn btn-primary" onclick="openModal('createProductionRequestModal')">
                            <i class="fas fa-file-alt"></i> Выдача в производство
                        </button>
                        <button class="btn btn-warning" onclick="openModal('writeOffMaterialModal')">
                            <i class="fas fa-trash"></i> Списание (брак/истечение срока)
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Low Stock Alert -->
                <?php if ($activeTab === 'products' && !empty($lowStockProducts)): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title" style="color: #ef4444;">
                            <i class="fas fa-exclamation-triangle"></i> Продукция с низким запасом
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Категория</th>
                                    <th>Остаток</th>
                                    <th>Мин. запас</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Не указана'); ?></td>
                                        <td>
                                            <span class="badge badge-danger"><?php echo $product['stock_quantity']; ?> шт.</span>
                                        </td>
                                        <td><?php echo $product['min_stock_level']; ?> шт.</td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="openModal('incomeModal', <?php echo $product['id']; ?>)">
                                                <i class="fas fa-plus"></i> Пополнить
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Low Stock Materials Alert -->
                <?php if ($activeTab === 'materials' && !empty($lowStockMaterials)): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title" style="color: #ef4444;">
                            <i class="fas fa-exclamation-triangle"></i> Материалы с низким запасом
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Категория</th>
                                    <th>Остаток</th>
                                    <th>Мин. запас</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockMaterials as $material): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($material['sku']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($material['name']); ?></td>
                                        <td><?php echo htmlspecialchars($material['category_name'] ?? 'Не указана'); ?></td>
                                        <td>
                                            <span class="badge badge-danger"><?php echo $material['current_stock']; ?> шт.</span>
                                        </td>
                                        <td><?php echo $material['min_stock_level']; ?> шт.</td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="openModal('incomeModal', <?php echo $material['id']; ?>)">
                                                <i class="fas fa-plus"></i> Пополнить
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Inventory Table -->
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title"><?php echo $activeTab === 'products' ? 'Остатки готовой продукции на складе' : 'Остатки материалов на складе'; ?></h2>
                    </div>
                    
                    <!-- Filters -->
                    <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                        <input 
                            type="text" 
                            id="inventorySearch"
                            placeholder="<?php echo $activeTab === 'products' ? 'Поиск продукции...' : 'Поиск материалов...'; ?>" 
                            onkeyup="filterInventory()"
                            style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; width: 300px;"
                        >
                        
                        <?php if ($activeTab === 'products'): ?>
                            <!-- Product filters -->
                            <select id="categoryFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Все категории</option>
                                <?php foreach ($productCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                        <?php else: ?>
                            <!-- Material filters -->
                            <select id="materialCategoryFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Все категории</option>
                                <?php foreach ($materialCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Категория</th>
                                    <th>Остаток</th>
                                    <th>Мин. запас</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($activeTab === 'products'): ?>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Продукция не найдена</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr data-product-id="<?php echo $product['id']; ?>" 
                                                data-category="<?php echo $product['category_id'] ?? ''; ?>"
                                                data-power="<?php echo $product['power_kw'] ?? ''; ?>"
                                                data-frame="<?php echo $product['frame_size_mm'] ?? ''; ?>"
                                                data-voltage="<?php echo $product['voltage_v'] ?? ''; ?>"
                                                data-protection="<?php echo $product['protection_class'] ?? ''; ?>">
                                                <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Не указана'); ?></td>
                                                <td><?php echo $product['stock_quantity']; ?> шт.</td>
                                                <td><?php echo $product['min_stock_level']; ?> шт.</td>
                                                <td>
                                                    <?php if ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                                                        <span class="badge badge-danger">Низкий запас</span>
                                                    <?php elseif ($product['stock_quantity'] <= $product['min_stock_level'] * 1.5): ?>
                                                        <span class="badge badge-warning">Средний запас</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">В норме</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="showProductDetails(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-eye"></i> Подробнее
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (empty($materials)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Материалы не найдены</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($materials as $material): ?>
                                            <tr data-material-id="<?php echo $material['id']; ?>" 
                                                data-category="<?php echo $material['category_id'] ?? ''; ?>">
                                                <td><strong><?php echo htmlspecialchars($material['sku']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($material['name']); ?></td>
                                                <td><?php echo htmlspecialchars($material['category_name'] ?? 'Не указана'); ?></td>
                                                <td><?php echo $material['current_stock']; ?> шт.</td>
                                                <td><?php echo $material['min_stock_level']; ?> шт.</td>
                                                <td>
                                                    <?php if ($material['current_stock'] <= $material['min_stock_level']): ?>
                                                        <span class="badge badge-danger">Низкий запас</span>
                                                    <?php elseif ($material['current_stock'] <= $material['min_stock_level'] * 1.5): ?>
                                                        <span class="badge badge-warning">Средний запас</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">В норме</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="showMaterialDetails(<?php echo $material['id']; ?>)">
                                                        <i class="fas fa-eye"></i> Подробнее
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Operations -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">История операций</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Тип</th>
                                    <th>Документ</th>
                                    <th>Товар</th>
                                    <th>Количество</th>
                                    <th>Со склада</th>
                                    <th>На склад</th>
                                    <th>Пользователь</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($operations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Операции не найдены</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($operations as $op): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i', strtotime($op['operation_date'])); ?></td>
                                            <td>
                                                <?php
                                                $typeBadges = [
                                                    'income' => 'badge-success',
                                                    'outcome' => 'badge-danger',
                                                    'transfer' => 'badge-primary',
                                                    'write_off' => 'badge-warning'
                                                ];
                                                $typeLabels = [
                                                    'income' => 'Приход',
                                                    'outcome' => 'Расход',
                                                    'transfer' => 'Перемещение',
                                                    'write_off' => 'Списание'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $typeBadges[$op['operation_type']]; ?>">
                                                    <?php echo $typeLabels[$op['operation_type']]; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($op['document_number'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($op['product_code'] . ' - ' . $op['product_name']); ?></td>
                                            <td><strong><?php echo $op['quantity']; ?> шт.</strong></td>
                                            <td><?php echo htmlspecialchars($op['warehouse_from_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($op['warehouse_to_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($op['user_name']); ?></td>
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
    
    <!-- Income Modal -->
    <div id="incomeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Приход товара на склад</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="income">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="income_product_id">Товар *</label>
                        <select id="income_product_id" name="product_id" required onchange="updateProductInfo('income')">
                            <option value="">Выберите товар</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="income_quantity">Количество *</label>
                            <input type="number" id="income_quantity" name="quantity" required min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="income_document_number">Номер документа</label>
                            <input type="text" id="income_document_number" name="document_number" placeholder="Например: ТОРГ-12 №123">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="income_notes">Комментарий</label>
                        <textarea id="income_notes" name="notes" rows="2" placeholder="Дополнительная информация"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('incomeModal')">Отмена</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Оприходовать
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Outcome Modal -->
    <div id="outcomeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Отгрузка готовой продукции клиенту</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="ship_product">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="outcome_product_id">Продукция *</label>
                        <select id="outcome_product_id" name="product_id" required onchange="updateProductInfo('shipment')">
                            <option value="">Выберите продукцию</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="outcome_quantity">Количество *</label>
                            <input type="number" id="outcome_quantity" name="quantity" required min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="outcome_document_number">Накладная №</label>
                            <input type="text" id="outcome_document_number" name="document_number" placeholder="Например: ТН-2024-001">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="outcome_notes">Комментарий</label>
                        <textarea id="outcome_notes" name="notes" rows="2" placeholder="Информация об отгрузке"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('outcomeModal')">Отмена</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-truck"></i> Отгрузить
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transfer Modal -->
    <div id="transferModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Перемещение товара между складами</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="transfer">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="transfer_product_id">Товар *</label>
                        <select id="transfer_product_id" name="product_id" required>
                            <option value="">Выберите товар</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="warehouse_from">Со склада *</label>
                            <select id="warehouse_from" name="warehouse_from" required>
                                <option value="">Выберите склад</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?php echo $wh['id']; ?>"><?php echo htmlspecialchars($wh['center_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="warehouse_to">На склад *</label>
                            <select id="warehouse_to" name="warehouse_to" required>
                                <option value="">Выберите склад</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?php echo $wh['id']; ?>"><?php echo htmlspecialchars($wh['center_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transfer_quantity">Количество *</label>
                            <input type="number" id="transfer_quantity" name="quantity" required min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="transfer_document_number">Номер документа</label>
                            <input type="text" id="transfer_document_number" name="document_number" placeholder="Например: М-15 №123">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="transfer_notes">Комментарий</label>
                        <textarea id="transfer_notes" name="notes" rows="2" placeholder="Дополнительная информация"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('transferModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-exchange-alt"></i> Переместить
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Write Off Modal -->
    <div id="writeOffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Списание товара</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="write_off_product">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="writeoff_product_id">Товар *</label>
                        <select id="writeoff_product_id" name="product_id" required onchange="updateProductInfo('writeoff')">
                            <option value="">Выберите товар</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="writeoff_quantity">Количество *</label>
                            <input type="number" id="writeoff_quantity" name="quantity" required min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="writeoff_document_number">Номер документа</label>
                            <input type="text" id="writeoff_document_number" name="document_number" placeholder="Например: Акт списания №123">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="writeoff_notes">Причина списания *</label>
                        <textarea id="writeoff_notes" name="notes" rows="3" required placeholder="Укажите причину списания (брак, истечение срока годности, повреждение и т.д.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('writeOffModal')">Отмена</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-trash"></i> Списать
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Income Product Modal (from production) -->
    <div id="incomeProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Оприходование готовой продукции из производства</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="income_product">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="income_product_product_id">Продукция *</label>
                        <select id="income_product_product_id" name="product_id" required onchange="updateProductInfo('income_product')">
                            <option value="">Выберите продукцию</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="income_product_quantity">Количество *</label>
                            <input type="number" id="income_product_quantity" name="quantity" required min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="income_product_batch_number">Номер партии</label>
                            <input type="text" id="income_product_batch_number" name="batch_number" placeholder="Например: П-2024-001">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="income_product_document_number">Номер документа</label>
                            <input type="text" id="income_product_document_number" name="document_number" placeholder="Например: МХ-18 №123">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="income_product_notes">Комментарий</label>
                        <textarea id="income_product_notes" name="notes" rows="2" placeholder="Дополнительная информация"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('incomeProductModal')">Отмена</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Оприходовать
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Write Off Product Modal -->
    <div id="writeOffProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Списание готовой продукции</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="write_off_product">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="writeoff_product_product_id">Продукция *</label>
                        <select id="writeoff_product_product_id" name="product_id" required onchange="updateProductInfo('writeoff_product')">
                            <option value="">Выберите продукцию</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="writeoff_product_quantity">Количество *</label>
                            <input type="number" id="writeoff_product_quantity" name="quantity" required min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="writeoff_product_document_number">Номер акта</label>
                            <input type="text" id="writeoff_product_document_number" name="document_number" placeholder="Например: Акт №789">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="writeoff_product_notes">Причина списания *</label>
                        <textarea id="writeoff_product_notes" name="notes" rows="3" required placeholder="Укажите причину списания (брак, повреждение при хранении, истечение срока годности и т.д.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('writeOffProductModal')">Отмена</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-trash"></i> Списать
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Income Material Modal (from supplier) -->
    <div id="incomeMaterialModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Поступление материалов от поставщика</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="" onsubmit="return validateIncomeMaterialForm(this);">
                <input type="hidden" name="action" value="income_material">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="income_material_material_id">Материал *</label>
                        <select id="income_material_material_id" name="material_id" required onchange="updateMaterialInfo('income_material')">
                            <option value="">Выберите материал</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?php echo $material['id']; ?>" 
                                        data-sku="<?php echo htmlspecialchars($material['sku']); ?>"
                                        data-stock="<?php echo $material['current_stock']; ?>"
                                        data-unit="<?php echo htmlspecialchars($material['unit']); ?>">
                                    <?php echo htmlspecialchars($material['sku'] . ' - ' . $material['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Выберите материал из справочника</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="income_material_quantity">Количество *</label>
                            <input type="number" id="income_material_quantity" name="quantity" required min="0.01" step="0.01" value="1">
                            <small class="form-hint">Укажите количество поступающего материала</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="income_material_batch_number">Номер партии</label>
                            <input type="text" id="income_material_batch_number" name="batch_number" placeholder="Например: П-2024-001">
                            <small class="form-hint">Номер партии от производителя</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="income_material_quality_cert">Сертификат качества</label>
                            <input type="text" id="income_material_quality_cert" name="quality_cert" placeholder="Например: №12345 от 15.05.2024">
                            <small class="form-hint">Номер и дата сертификата соответствия качества (указывается при наличии)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="income_material_expiry_date">Срок годности</label>
                            <input type="date" id="income_material_expiry_date" name="expiry_date">
                            <small class="form-hint">Дата окончания срока годности (для материалов с ограниченным сроком хранения)</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="income_material_document_number">Входящая накладная № *</label>
                            <input type="text" id="income_material_document_number" name="document_number" required placeholder="Например: М-15 №123">
                            <small class="form-hint">Номер товарной накладной поставщика (ТОРГ-12, ТН) - обязательное поле</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="income_material_notes">Комментарий</label>
                        <textarea id="income_material_notes" name="notes" rows="2" placeholder="Дополнительная информация о поступлении"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('incomeMaterialModal')">Отмена</button>
                    <button type="submit" class="btn btn-success" id="incomeMaterialSubmitBtn">
                        <i class="fas fa-plus"></i> Оприходовать
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Create Production Request Modal -->
    <div id="createProductionRequestModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>Выдача материалов в производство</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="" onsubmit="return handleProductionIssueSubmit(this);">
                <input type="hidden" name="action" value="outcome_material">
                <input type="hidden" name="is_from_production_order" id="is_from_production_order" value="0">
                <div class="modal-body">
                    <div style="margin-bottom: 20px; padding: 15px; background: #f0f9ff; border-radius: 8px;">
                        <p style="margin: 0 0 10px 0; color: #0369a1;"><strong>Режим выдачи:</strong></p>
                        <label style="display: inline-flex; align-items: center; margin-right: 20px; cursor: pointer;">
                            <input type="radio" name="issue_mode" value="single" checked onchange="toggleIssueMode()" style="margin-right: 8px;">
                            Одиночная выдача
                        </label>
                        <label style="display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="radio" name="issue_mode" value="batch" onchange="toggleIssueMode()" style="margin-right: 8px;">
                            Массовая выдача (несколько материалов)
                        </label>
                    </div>
                    
                    <!-- Single issue mode -->
                    <div id="single_issue_form">
                        <div class="form-group">
                            <label for="req_material_id">Материал *</label>
                            <select id="req_material_id" name="material_id" required onchange="updateMaterialInfo('req')">
                                <option value="">Выберите материал</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?php echo $material['id']; ?>" 
                                            data-sku="<?php echo htmlspecialchars($material['sku']); ?>"
                                            data-stock="<?php echo $material['current_stock']; ?>"
                                            data-unit="<?php echo htmlspecialchars($material['unit']); ?>">
                                        <?php echo htmlspecialchars($material['sku'] . ' - ' . $material['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="req_quantity">Количество к выдаче *</label>
                                <input type="number" id="req_quantity" name="quantity" required min="0.01" step="0.01" value="1" onchange="checkStockAvailability('req')">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="req_document_number">Требование-накладная №</label>
                                <input type="text" id="req_document_number" name="document_number" placeholder="Например: М-11 №456">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="req_notes">Комментарий</label>
                            <textarea id="req_notes" name="notes" rows="2" placeholder="Информация о выдаче"></textarea>
                        </div>
                        
                        <div id="req_stock_info" style="margin-top: 15px; padding: 15px; background: #f0f9ff; border-radius: 8px; display: none;">
                            <p><strong>Доступно на складе:</strong> <span id="req_available_stock">0</span> <span id="req_stock_unit"></span></p>
                            <p id="req_stock_warning" style="color: #ef4444; display: none;"><strong>Внимание:</strong> Недостаточно материала на складе!</p>
                        </div>
                    </div>
                    
                    <!-- Batch issue mode -->
                    <div id="batch_issue_form" style="display: none;">
                        <div class="form-group">
                            <label>Материалы для выдачи</label>
                            <div id="batch_materials_container" style="margin-bottom: 15px;">
                                <div class="batch-material-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-end;">
                                    <div style="flex: 2;">
                                        <select name="materials[0][material_id]" class="batch-material-select" required onchange="updateBatchMaterialInfo(this)">
                                            <option value="">Выберите материал</option>
                                            <?php foreach ($materials as $material): ?>
                                                <option value="<?php echo $material['id']; ?>" 
                                                        data-sku="<?php echo htmlspecialchars($material['sku']); ?>"
                                                        data-stock="<?php echo $material['current_stock']; ?>"
                                                        data-unit="<?php echo htmlspecialchars($material['unit']); ?>">
                                                    <?php echo htmlspecialchars($material['sku'] . ' - ' . $material['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="number" name="materials[0][quantity]" class="batch-quantity-input" required min="0.01" step="0.01" placeholder="Кол-во" onchange="checkBatchStockAvailability(this)">
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeBatchRow(this)" disabled title="Нельзя удалить единственную строку">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addBatchRow()">
                                <i class="fas fa-plus"></i> Добавить материал
                            </button>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="batch_document_number">Требование-накладная №</label>
                                <input type="text" id="batch_document_number" name="document_number" placeholder="Например: М-11 №456">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="batch_notes">Комментарий</label>
                            <textarea id="batch_notes" name="notes" rows="2" placeholder="Информация о выдаче"></textarea>
                        </div>
                        
                        <div id="batch_stock_info" style="margin-top: 15px; padding: 15px; background: #fef3c7; border-radius: 8px; display: none;">
                            <p id="batch_stock_warning" style="color: #b45309; margin: 0;"><strong>Внимание:</strong> Проверьте доступность материалов на складе!</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createProductionRequestModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="req_submit_btn">
                        <i class="fas fa-file-alt"></i> Создать требование
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Write Off Material Modal -->
    <div id="writeOffMaterialModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Списание материалов</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="write_off_material">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="writeoff_material_material_id">Материал *</label>
                        <select id="writeoff_material_material_id" name="material_id" required onchange="updateMaterialInfo('writeoff_material')">
                            <option value="">Выберите материал</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?php echo $material['id']; ?>" 
                                        data-sku="<?php echo htmlspecialchars($material['sku']); ?>"
                                        data-stock="<?php echo $material['current_stock']; ?>"
                                        data-unit="<?php echo htmlspecialchars($material['unit']); ?>">
                                    <?php echo htmlspecialchars($material['sku'] . ' - ' . $material['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="writeoff_material_quantity">Количество *</label>
                            <input type="number" id="writeoff_material_quantity" name="quantity" required min="0.01" step="0.01" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="writeoff_material_document_number">Номер акта</label>
                            <input type="text" id="writeoff_material_document_number" name="document_number" placeholder="Например: Акт №789">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="writeoff_material_notes">Причина списания *</label>
                        <textarea id="writeoff_material_notes" name="notes" rows="3" required placeholder="Укажите причину списания (брак, порча, истечение срока годности, потеря и т.д.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('writeOffMaterialModal')">Отмена</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-trash"></i> Списать
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Product and Material details data
        const productsData = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
        const materialsData = <?php echo json_encode($materials, JSON_UNESCAPED_UNICODE); ?>;
        
        // Filter inventory table with all filters
        function applyFilters() {
            const searchInput = document.getElementById('inventorySearch').value.toUpperCase();
            const activeTab = '<?php echo $activeTab; ?>';
            
            if (activeTab === 'products') {
                filterProductsTable(searchInput);
            } else {
                filterMaterialsTable(searchInput);
            }
        }
        
        function filterInventory() {
            applyFilters();
        }
        
        function filterProductsTable(searchFilter) {
            const categoryFilter = document.getElementById('categoryFilter').value;
            
            const table = document.getElementById('inventoryTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                const productId = row.getAttribute('data-product-id');
                const category = row.getAttribute('data-category');
                
                const tdCode = row.getElementsByTagName('td')[0];
                const tdName = row.getElementsByTagName('td')[1];
                const tdCategory = row.getElementsByTagName('td')[2];
                
                let showRow = true;
                
                // Search filter
                if (searchFilter) {
                    const codeValue = tdCode.textContent || tdCode.innerText;
                    const nameValue = tdName.textContent || tdName.innerText;
                    const categoryValue = tdCategory.textContent || tdCategory.innerText;
                    
                    if (codeValue.toUpperCase().indexOf(searchFilter) === -1 &&
                        nameValue.toUpperCase().indexOf(searchFilter) === -1 &&
                        categoryValue.toUpperCase().indexOf(searchFilter) === -1) {
                        showRow = false;
                    }
                }
                
                // Category filter
                if (showRow && categoryFilter && category !== categoryFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        }
        
        function filterMaterialsTable(searchFilter) {
            const categoryFilter = document.getElementById('materialCategoryFilter').value;
            
            const table = document.getElementById('inventoryTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                const category = row.getAttribute('data-category');
                
                const tdSku = row.getElementsByTagName('td')[0];
                const tdName = row.getElementsByTagName('td')[1];
                const tdCategory = row.getElementsByTagName('td')[2];
                const tdStock = row.getElementsByTagName('td')[3];
                const tdMinStock = row.getElementsByTagName('td')[4];
                
                let showRow = true;
                
                // Search filter
                if (searchFilter) {
                    const skuValue = tdSku.textContent || tdSku.innerText;
                    const nameValue = tdName.textContent || tdName.innerText;
                    const categoryValue = tdCategory.textContent || tdCategory.innerText;
                    
                    if (skuValue.toUpperCase().indexOf(searchFilter) === -1 &&
                        nameValue.toUpperCase().indexOf(searchFilter) === -1 &&
                        categoryValue.toUpperCase().indexOf(searchFilter) === -1) {
                        showRow = false;
                    }
                }
                
                // Category filter
                if (showRow && categoryFilter && category !== categoryFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        }
        
        // Show product details modal
        function showProductDetails(productId) {
            const product = productsData.find(p => p.id == productId);
            if (!product) return;
            
            const specs = product.specifications ? JSON.parse(product.specifications) : {};
            
            let detailsHtml = `
                <div style="padding: 20px;">
                    <h3 style="margin-bottom: 20px; color: var(--primary-color);">${escapeHtml(product.product_code)} - ${escapeHtml(product.product_name)}</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div><strong>Категория:</strong> ${escapeHtml(product.category_name || 'Не указана')}</div>
                        <div><strong>Артикул:</strong> ${escapeHtml(product.product_code)}</div>
                        <div><strong>Остаток на складе:</strong> ${product.stock_quantity} шт.</div>
                        <div><strong>Минимальный запас:</strong> ${product.min_stock_level} шт.</div>
                        <div><strong>Базовая цена:</strong> ${product.base_price} BYN</div>
                        <div><strong>Валюта:</strong> ${escapeHtml(product.currency || 'BYN')}</div>
            `;
            
            if (product.frame_size_mm) {
                detailsHtml += `<div><strong>Габарит рамы:</strong> ${product.frame_size_mm} мм</div>`;
            }
            if (product.power_kw) {
                detailsHtml += `<div><strong>Мощность:</strong> ${product.power_kw} кВт</div>`;
            }
            if (product.rpm) {
                detailsHtml += `<div><strong>Обороты:</strong> ${product.rpm} об/мин</div>`;
            }
            if (product.efficiency_pct) {
                detailsHtml += `<div><strong>КПД:</strong> ${product.efficiency_pct}%</div>`;
            }
            if (product.cos_phi) {
                detailsHtml += `<div><strong>cos φ:</strong> ${product.cos_phi}</div>`;
            }
            if (product.voltage_v) {
                detailsHtml += `<div><strong>Напряжение:</strong> ${escapeHtml(product.voltage_v)} В</div>`;
            }
            if (product.protection_class) {
                detailsHtml += `<div><strong>Класс защиты:</strong> ${escapeHtml(product.protection_class)}</div>`;
            }
            if (product.mounting_type) {
                detailsHtml += `<div><strong>Тип монтажа:</strong> ${escapeHtml(product.mounting_type)}</div>`;
            }
            if (product.weight) {
                detailsHtml += `<div><strong>Вес:</strong> ${product.weight} кг</div>`;
            }
            if (product.description) {
                detailsHtml += `<div style="grid-column: span 2;"><strong>Описание:</strong><br>${escapeHtml(product.description)}</div>`;
            }
            
            detailsHtml += `</div></div>`;
            
            showModal('productDetailsModal', detailsHtml);
        }
        
        // Show material details modal
        function showMaterialDetails(materialId) {
            const material = materialsData.find(m => m.id == materialId);
            if (!material) return;
            
            let detailsHtml = `
                <div style="padding: 20px;">
                    <h3 style="margin-bottom: 20px; color: var(--primary-color);">${escapeHtml(material.sku)} - ${escapeHtml(material.name)}</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div><strong>Категория:</strong> ${escapeHtml(material.category_name || 'Не указана')}</div>
                        <div><strong>Артикул:</strong> ${escapeHtml(material.sku)}</div>
                        <div><strong>Текущий остаток:</strong> ${material.current_stock} шт.</div>
                        <div><strong>Минимальный запас:</strong> ${material.min_stock_level} шт.</div>
                        <div><strong>Цена за единицу:</strong> ${material.price_per_unit} ${escapeHtml(material.currency || 'BYN')}</div>
            `;
            
            if (material.standard) {
                detailsHtml += `<div><strong>Стандарт:</strong> ${escapeHtml(material.standard)}</div>`;
            }
            if (material.grade_spec) {
                detailsHtml += `<div><strong>Марка/спецификация:</strong> ${escapeHtml(material.grade_spec)}</div>`;
            }
            if (material.purpose) {
                detailsHtml += `<div style="grid-column: span 2;"><strong>Назначение:</strong><br>${escapeHtml(material.purpose)}</div>`;
            }
            if (material.weight && material.weight > 0) {
                detailsHtml += `<div><strong>Вес единицы:</strong> ${material.weight} кг</div>`;
            }
            if (material.length && material.length > 0) {
                detailsHtml += `<div><strong>Длина:</strong> ${material.length} м</div>`;
            }
            if (material.width && material.width > 0) {
                detailsHtml += `<div><strong>Ширина:</strong> ${material.width} мм</div>`;
            }
            if (material.thickness && material.thickness > 0) {
                detailsHtml += `<div><strong>Толщина:</strong> ${material.thickness} мм</div>`;
            }
            if (material.diameter && material.diameter > 0) {
                detailsHtml += `<div><strong>Диаметр:</strong> ${material.diameter} мм</div>`;
            }
            if (material.voltage_rating) {
                detailsHtml += `<div><strong>Класс напряжения:</strong> ${escapeHtml(material.voltage_rating)}</div>`;
            }
            if (material.temperature_class) {
                detailsHtml += `<div><strong>Класс нагревостойкости:</strong> ${escapeHtml(material.temperature_class)}</div>`;
            }
            if (material.ip_rating) {
                detailsHtml += `<div><strong>Степень защиты:</strong> ${escapeHtml(material.ip_rating)}</div>`;
            }
            if (material.storage_conditions) {
                detailsHtml += `<div style="grid-column: span 2;"><strong>Условия хранения:</strong><br>${escapeHtml(material.storage_conditions)}</div>`;
            }
            if (material.shelf_life_months) {
                detailsHtml += `<div><strong>Срок годности:</strong> ${material.shelf_life_months} мес.</div>`;
            }
            if (material.supplier) {
                detailsHtml += `<div><strong>Поставщик:</strong> ${escapeHtml(material.supplier)}</div>`;
            }
            if (material.zone_name) {
                detailsHtml += `<div><strong>Зона хранения:</strong> ${escapeHtml(material.zone_name)}</div>`;
            }
            
            detailsHtml += `</div></div>`;
            
            showModal('materialDetailsModal', detailsHtml);
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Show generic modal with content
        function showModal(modalId, content) {
            // Create modal if it doesn't exist
            let modal = document.getElementById(modalId);
            if (!modal) {
                modal = document.createElement('div');
                modal.id = modalId;
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content" style="max-width: 800px;">
                        <div class="modal-header">
                            <h2>Подробная информация</h2>
                            <button class="modal-close" id="${modalId}CloseBtn">&times;</button>
                        </div>
                        <div class="modal-body" id="${modalId}Body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('${modalId}')">Закрыть</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                // Add close button handler
                const closeBtn = document.getElementById(modalId + 'CloseBtn');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        closeModal(modalId);
                    });
                }
                
                // Add click outside to close
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal(modalId);
                    }
                });
            }
            
            document.getElementById(modalId + 'Body').innerHTML = content;
            modal.style.display = 'flex';
        }
        
        // Close modal function
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        // Open modal function for income/outcome/transfer/writeoff modals
        function openModal(modalId, productId = null) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                
                // Auto-select product if passed
                if (productId) {
                    const select = modal.querySelector('select[name="product_id"], select[name="material_id"]');
                    if (select) {
                        select.value = productId;
                    }
                }
                
                // Add close button handler if not already added
                const closeBtn = modal.querySelector('.modal-close');
                if (closeBtn && !closeBtn.dataset.handlerAdded) {
                    closeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        closeModal(modalId);
                    });
                    closeBtn.dataset.handlerAdded = 'true';
                }
                
                // Add click outside to close
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal(modalId);
                    }
                });
            }
        }
        
        // Update product info when selecting product
        function updateProductInfo(modalPrefix) {
            const select = document.getElementById(modalPrefix + '_product_id');
            if (!select) return;
            
            const selectedOption = select.options[select.selectedIndex];
            const stock = selectedOption.getAttribute('data-stock');
            const code = selectedOption.getAttribute('data-code');
            
            if (stock && !modalPrefix.includes('transfer')) {
                const quantityInput = document.getElementById(modalPrefix + '_quantity');
                if (quantityInput && (modalPrefix.includes('outcome') || modalPrefix.includes('writeoff') || modalPrefix === 'shipment')) {
                    quantityInput.max = stock;
                    quantityInput.title = 'Доступно: ' + stock + ' шт.';
                }
            }
        }
        
        // Update material info when selecting material
        function updateMaterialInfo(modalPrefix) {
            const select = document.getElementById(modalPrefix + '_material_id');
            if (!select) return;
            
            const selectedOption = select.options[select.selectedIndex];
            const stock = selectedOption.getAttribute('data-stock');
            const unit = selectedOption.getAttribute('data-unit');
            const sku = selectedOption.getAttribute('data-sku');
            
            if (stock && !modalPrefix.includes('transfer')) {
                const quantityInput = document.getElementById(modalPrefix + '_quantity');
                if (quantityInput && (modalPrefix.includes('outcome') || modalPrefix.includes('writeoff') || modalPrefix === 'issue' || modalPrefix === 'req')) {
                    quantityInput.max = stock;
                    quantityInput.title = 'Доступно: ' + stock + ' шт.';
                }
                
                // Show stock info for production request modal
                if (modalPrefix === 'req') {
                    const stockInfoDiv = document.getElementById('req_stock_info');
                    const availableStockSpan = document.getElementById('req_available_stock');
                    const stockUnitSpan = document.getElementById('req_stock_unit');
                    const stockWarning = document.getElementById('req_stock_warning');
                    
                    if (stockInfoDiv && availableStockSpan && stockUnitSpan) {
                        stockInfoDiv.style.display = 'block';
                        availableStockSpan.textContent = stock;
                        stockUnitSpan.textContent = 'шт.';
                        
                        // Check if quantity exceeds stock
                        checkStockAvailability('req');
                    }
                }
            }
        }
        
        // Check stock availability for production request
        function checkStockAvailability(modalPrefix) {
            if (modalPrefix !== 'req') return;
            
            const select = document.getElementById('req_material_id');
            const quantityInput = document.getElementById('req_quantity');
            const submitBtn = document.getElementById('req_submit_btn');
            
            if (!select || !quantityInput) return;
            
            const selectedOption = select.options[select.selectedIndex];
            const stock = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
            const quantity = parseFloat(quantityInput.value) || 0;
            
            const stockWarning = document.getElementById('req_stock_warning');
            
            if (quantity > stock) {
                if (stockWarning) stockWarning.style.display = 'block';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Недостаточно на складе';
                }
            } else {
                if (stockWarning) stockWarning.style.display = 'none';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-file-alt"></i> Создать требование';
                }
            }
        }
        
        // Auto-select product if passed in URL
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('product_id');
            const issueProduction = urlParams.get('issue_production');
            
            // Обработка запроса на выдачу материалов в производство из модуля производства
            if (issueProduction === '1') {
                const issueDataStr = sessionStorage.getItem('warehouse_issue_data');
                if (issueDataStr) {
                    const issueData = JSON.parse(issueDataStr);
                    
                    // Открываем модальное окно выдачи в производство
                    openModal('createProductionRequestModal');
                    
                    // Устанавливаем первый материал и сохраняем остальные для последующей выдачи
                    if (issueData.materials && issueData.materials.length > 0) {
                        const materialSelect = document.getElementById('req_material_id');
                        if (materialSelect && issueData.materials[0].material_id) {
                            materialSelect.value = issueData.materials[0].material_id;
                            updateMaterialInfo('req');
                            
                            // Устанавливаем количество
                            const quantityInput = document.getElementById('req_quantity');
                            if (quantityInput) {
                                quantityInput.value = issueData.materials[0].quantity;
                            }
                            
                            // Сохраняем оставшиеся материалы для последующей выдачи
                            if (issueData.materials.length > 1) {
                                sessionStorage.setItem('remaining_issue_materials', JSON.stringify({
                                    order_id: issueData.order_id,
                                    materials: issueData.materials.slice(1)
                                }));
                            } else {
                                sessionStorage.removeItem('remaining_issue_materials');
                            }
                        }
                    }
                }
            }
            
            if (productId) {
                const selects = ['income_product_id', 'outcome_product_id', 'writeoff_product_id', 'writeoff_product_product_id'];
                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        select.value = productId;
                        const prefix = selectId.replace('_product_id', '');
                        updateProductInfo(prefix);
                    }
                });
            }
            
            // Initialize filters on page load
            applyFilters();
        });
        
        // Validate income material form before submission
        function validateIncomeMaterialForm(form) {
            const materialId = form.querySelector('[name="material_id"]').value;
            const quantity = form.querySelector('[name="quantity"]').value;
            const documentNumber = document.getElementById('income_material_document_number').value.trim();
            
            if (!materialId || materialId === '') {
                alert('Ошибка: Пожалуйста, выберите материал из справочника');
                return false;
            }
            
            if (!quantity || parseFloat(quantity) <= 0) {
                alert('Ошибка: Количество должно быть больше нуля');
                return false;
            }
            
            if (!documentNumber) {
                alert('Ошибка: Введите номер входящей накладной поставщика (обязательное поле)');
                return false;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('incomeMaterialSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обработка...';
            }
            
            return true;
        }
        
        // Toggle between single and batch issue mode
        function toggleIssueMode() {
            const mode = document.querySelector('input[name="issue_mode"]:checked').value;
            const singleForm = document.getElementById('single_issue_form');
            const batchForm = document.getElementById('batch_issue_form');
            
            if (mode === 'batch') {
                singleForm.style.display = 'none';
                batchForm.style.display = 'block';
                // Remove required from single form fields
                document.getElementById('req_material_id').required = false;
                document.getElementById('req_quantity').required = false;
                // Add required to first batch row
                const firstSelect = document.querySelector('.batch-material-select');
                const firstInput = document.querySelector('.batch-quantity-input');
                if (firstSelect) firstSelect.required = true;
                if (firstInput) firstInput.required = true;
            } else {
                singleForm.style.display = 'block';
                batchForm.style.display = 'none';
                // Add required to single form fields
                document.getElementById('req_material_id').required = true;
                document.getElementById('req_quantity').required = true;
                // Remove required from batch form fields
                document.querySelectorAll('.batch-material-select').forEach(el => el.required = false);
                document.querySelectorAll('.batch-quantity-input').forEach(el => el.required = false);
            }
        }
        
        // Add new batch material row
        function addBatchRow() {
            const container = document.getElementById('batch_materials_container');
            const rowCount = container.getElementsByClassName('batch-material-row').length;
            
            const newRow = document.createElement('div');
            newRow.className = 'batch-material-row';
            newRow.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-end;';
            newRow.innerHTML = `
                <div style="flex: 2;">
                    <select name="materials[${rowCount}][material_id]" class="batch-material-select" required onchange="updateBatchMaterialInfo(this)">
                        <option value="">Выберите материал</option>
                        <?php foreach ($materials as $material): ?>
                            <option value="<?php echo $material['id']; ?>" 
                                    data-sku="<?php echo htmlspecialchars($material['sku']); ?>"
                                    data-stock="<?php echo $material['current_stock']; ?>"
                                    data-unit="<?php echo htmlspecialchars($material['unit']); ?>">
                                <?php echo htmlspecialchars($material['sku'] . ' - ' . $material['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <input type="number" name="materials[${rowCount}][quantity]" class="batch-quantity-input" required min="0.01" step="0.01" placeholder="Кол-во" onchange="checkBatchStockAvailability(this)">
                </div>
                <div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeBatchRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }
        
        // Remove batch material row
        function removeBatchRow(button) {
            const container = document.getElementById('batch_materials_container');
            const rows = container.getElementsByClassName('batch-material-row');
            
            if (rows.length > 1) {
                button.closest('.batch-material-row').remove();
                // Re-index remaining rows
                const selects = container.querySelectorAll('.batch-material-select');
                const inputs = container.querySelectorAll('.batch-quantity-input');
                selects.forEach((select, index) => {
                    select.name = `materials[${index}][material_id]`;
                });
                inputs.forEach((input, index) => {
                    input.name = `materials[${index}][quantity]`;
                });
            } else {
                alert('Нельзя удалить единственную строку');
            }
        }
        
        // Update batch material info
        function updateBatchMaterialInfo(select) {
            const stock = select.options[select.selectedIndex].getAttribute('data-stock');
            const unit = select.options[select.selectedIndex].getAttribute('data-unit');
            const quantityInput = select.closest('.batch-material-row').querySelector('.batch-quantity-input');
            
            if (quantityInput && stock) {
                quantityInput.max = stock;
                quantityInput.title = 'Доступно: ' + stock + ' ' + unit;
            }
            
            checkBatchStockAvailability(select);
        }
        
        // Check batch stock availability
        function checkBatchStockAvailability(element) {
            const container = document.getElementById('batch_materials_container');
            const rows = container.getElementsByClassName('batch-material-row');
            const stockWarning = document.getElementById('batch_stock_info');
            let hasWarning = false;
            
            for (let row of rows) {
                const select = row.querySelector('.batch-material-select');
                const input = row.querySelector('.batch-quantity-input');
                
                if (select && input && select.value && input.value) {
                    const stock = parseFloat(select.options[select.selectedIndex].getAttribute('data-stock')) || 0;
                    const quantity = parseFloat(input.value) || 0;
                    
                    if (quantity > stock) {
                        hasWarning = true;
                        input.style.borderColor = '#ef4444';
                        input.style.backgroundColor = '#fef2f2';
                    } else {
                        input.style.borderColor = '';
                        input.style.backgroundColor = '';
                    }
                }
            }
            
            if (stockWarning) {
                stockWarning.style.display = hasWarning ? 'block' : 'none';
            }
        }
        
        // Handle production issue submit
        function handleProductionIssueSubmit(form) {
            const mode = document.querySelector('input[name="issue_mode"]:checked').value;
            
            if (mode === 'batch') {
                // Switch action to batch outcome
                form.querySelector('[name="action"]').value = 'outcome_material_batch';
                
                // Validate batch materials
                const container = document.getElementById('batch_materials_container');
                const selects = container.querySelectorAll('.batch-material-select');
                const inputs = container.querySelectorAll('.batch-quantity-input');
                let isValid = true;
                let hasData = false;
                
                for (let i = 0; i < selects.length; i++) {
                    if (selects[i].value && inputs[i].value) {
                        hasData = true;
                        const stock = parseFloat(selects[i].options[selects[i].selectedIndex].getAttribute('data-stock')) || 0;
                        const quantity = parseFloat(inputs[i].value) || 0;
                        
                        if (quantity > stock) {
                            alert('Ошибка: Количество для материала "' + selects[i].options[selects[i].selectedIndex].text + '" превышает доступное на складе!');
                            isValid = false;
                            break;
                        }
                    }
                }
                
                if (!hasData) {
                    alert('Ошибка: Добавьте хотя бы один материал для выдачи');
                    return false;
                }
                
                if (!isValid) {
                    return false;
                }
            } else {
                // Single mode - use default action
                form.querySelector('[name="action"]').value = 'outcome_material';
                
                // Check stock for single mode
                const select = document.getElementById('req_material_id');
                const input = document.getElementById('req_quantity');
                const stock = parseFloat(select.options[select.selectedIndex].getAttribute('data-stock')) || 0;
                const quantity = parseFloat(input.value) || 0;
                
                if (quantity > stock) {
                    alert('Ошибка: Количество превышает доступное на складе!');
                    return false;
                }
            }
            
            return true;
        }
    </script>
</body>
</html>

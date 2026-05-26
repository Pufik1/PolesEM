<?php
/**
 * Warehouse module for OAO "Polesieelectromash" ERP System
 * Warehouse management: inventory, operations (income/outcome/transfer/write-off)
 * Separated sections for Materials and Finished Products with advanced filtering
 */

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
            // Приход готовой продукции на склад
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $batch_number = trim($_POST['batch_number']);
            $notes = trim($_POST['notes']);
            
            $pdo->beginTransaction();
            
            // Добавляем операцию прихода
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, product_id, quantity, warehouse_to, user_id, document_number, batch_number, notes) 
                                   VALUES ('income', :product_id, :quantity, 1, :user_id, :document_number, :batch_number, :notes)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':batch_number' => $batch_number,
                ':notes' => $notes
            ]);
            
            // Обновляем остаток товара
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':product_id' => $product_id
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Приход готовой продукции', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Готовая продукция успешно оприходована';
            
        } elseif ($action === 'income_material') {
            // Приход материалов на склад
            $material_id = (int)$_POST['material_id'];
            $quantity = (float)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $batch_number = trim($_POST['batch_number']);
            $quality_cert = trim($_POST['quality_cert']);
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            $notes = trim($_POST['notes']);
            
            $pdo->beginTransaction();
            
            // Добавляем операцию прихода материала
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, material_id, quantity, warehouse_to, user_id, document_number, batch_number, quality_cert, expiry_date, notes) 
                                   VALUES ('income', :material_id, :quantity, 1, :user_id, :document_number, :batch_number, :quality_cert, :expiry_date, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':batch_number' => $batch_number,
                ':quality_cert' => $quality_cert,
                ':expiry_date' => $expiry_date,
                ':notes' => $notes
            ]);
            
            // Добавляем запись в движения материалов
            $stmt = $pdo->prepare("INSERT INTO material_stock_movements 
                                   (material_id, operation_type, quantity, warehouse_to, user_id, document_number, batch_number, notes) 
                                   VALUES (:material_id, 'income', :quantity, 1, :user_id, :document_number, :batch_number, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':batch_number' => $batch_number,
                ':notes' => $notes
            ]);
            
            // Обновляем остаток материала
            $stmt = $pdo->prepare("UPDATE materials SET current_stock = current_stock + :quantity WHERE id = :material_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':material_id' => $material_id
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Приход материалов', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Материалы успешно оприходованы';
            
        } elseif ($action === 'outcome_product') {
            // Расход готовой продукции со склада
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $product = $stmt->fetch();
            
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception('Недостаточно товара на складе');
            }
            
            $pdo->beginTransaction();
            
            // Добавляем операцию расхода
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, product_id, quantity, warehouse_from, user_id, document_number, notes) 
                                   VALUES ('outcome', :product_id, :quantity, 1, :user_id, :document_number, :notes)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
            ]);
            
            // Обновляем остаток товара
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':product_id' => $product_id
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Расход готовой продукции', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Готовая продукция успешно отгружена';
            
        } elseif ($action === 'outcome_material') {
            // Расход материалов со склада (в производство)
            $material_id = (int)$_POST['material_id'];
            $quantity = (float)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $production_order_id = !empty($_POST['production_order_id']) ? (int)$_POST['production_order_id'] : null;
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT current_stock FROM materials WHERE id = :material_id");
            $stmt->execute([':material_id' => $material_id]);
            $material = $stmt->fetch();
            
            if ($material['current_stock'] < $quantity) {
                throw new Exception('Недостаточно материала на складе');
            }
            
            $pdo->beginTransaction();
            
            // Добавляем операцию расхода материала
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, material_id, quantity, warehouse_from, user_id, document_number, notes) 
                                   VALUES ('outcome', :material_id, :quantity, 1, :user_id, :document_number, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
            ]);
            
            // Добавляем запись в движения материалов
            $stmt = $pdo->prepare("INSERT INTO material_stock_movements 
                                   (material_id, operation_type, quantity, warehouse_from, user_id, document_number, production_order_id, notes) 
                                   VALUES (:material_id, 'outcome', :quantity, 1, :user_id, :document_number, :production_order_id, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':production_order_id' => $production_order_id,
                ':notes' => $notes
            ]);
            
            // Обновляем остаток материала
            $stmt = $pdo->prepare("UPDATE materials SET current_stock = current_stock - :quantity WHERE id = :material_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':material_id' => $material_id
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Расход материалов', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Материалы успешно списаны';
            
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
                                   (operation_type, product_id, quantity, warehouse_from, warehouse_to, user_id, document_number, notes) 
                                   VALUES ('transfer', :product_id, :quantity, :warehouse_from, :warehouse_to, :user_id, :document_number, :notes)");
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
                                   (operation_type, material_id, quantity, warehouse_from, warehouse_to, user_id, document_number, notes) 
                                   VALUES ('transfer', :material_id, :quantity, :warehouse_from, :warehouse_to, :user_id, :document_number, :notes)");
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
            // Списание готовой продукции
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $product = $stmt->fetch();
            
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception('Недостаточно товара на складе');
            }
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, product_id, quantity, warehouse_from, user_id, document_number, notes) 
                                   VALUES ('write_off', :product_id, :quantity, 1, :user_id, :document_number, :notes)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
            ]);
            
            // Обновляем остаток товара
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id");
            $stmt->execute([
                ':quantity' => $quantity,
                ':product_id' => $product_id
            ]);
            
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Списание готовой продукции', 'warehouse_operations', $pdo->lastInsertId());
            $success = 'Готовая продукция успешно списана';
            
        } elseif ($action === 'write_off_material') {
            // Списание материалов
            $material_id = (int)$_POST['material_id'];
            $quantity = (float)$_POST['quantity'];
            $document_number = trim($_POST['document_number']);
            $notes = trim($_POST['notes']);
            
            // Проверяем достаточность количества
            $stmt = $pdo->prepare("SELECT current_stock FROM materials WHERE id = :material_id");
            $stmt->execute([':material_id' => $material_id]);
            $material = $stmt->fetch();
            
            if ($material['current_stock'] < $quantity) {
                throw new Exception('Недостаточно материала на складе');
            }
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO warehouse_operations 
                                   (operation_type, material_id, quantity, warehouse_from, user_id, document_number, notes) 
                                   VALUES ('write_off', :material_id, :quantity, 1, :user_id, :document_number, :notes)");
            $stmt->execute([
                ':material_id' => $material_id,
                ':quantity' => $quantity,
                ':user_id' => $_SESSION['user_id'],
                ':document_number' => $document_number,
                ':notes' => $notes
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
            $success = 'Материалы успешно списаны';
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
    
    if ($filterCategory && $activeTab === 'products') {
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
        SELECT m.*, mc.category_name, wz.zone_name
        FROM materials m
        LEFT JOIN material_categories mc ON m.category_id = mc.id
        LEFT JOIN warehouse_zones wz ON mc.storage_zone = wz.zone_code
        WHERE m.is_active = 1
    ";
    $materialParams = [];
    
    if ($filterCategory && $activeTab === 'materials') {
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
                            <p>Товаров с низким запасом</p>
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
                               style="padding: 8px 16px;">
                                <i class="fas fa-cubes"></i> Материалы
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title">Операции со складом</h2>
                    </div>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button class="btn btn-success" onclick="openModal('incomeModal')">
                            <i class="fas fa-plus"></i> Приход товара
                        </button>
                        <button class="btn btn-danger" onclick="openModal('outcomeModal')">
                            <i class="fas fa-minus"></i> Расход товара
                        </button>
                        <button class="btn btn-primary" onclick="openModal('transferModal')">
                            <i class="fas fa-exchange-alt"></i> Перемещение
                        </button>
                        <button class="btn btn-warning" onclick="openModal('writeOffModal')">
                            <i class="fas fa-trash"></i> Списание
                        </button>
                    </div>
                </div>
                
                <!-- Low Stock Alert -->
                <?php if (!empty($lowStockProducts)): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title" style="color: #ef4444;">
                            <i class="fas fa-exclamation-triangle"></i> Товары с низким запасом
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
                
                <!-- Inventory Table -->
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h2 class="card-title">Остатки товаров на складе</h2>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <input 
                            type="text" 
                            id="inventorySearch"
                            placeholder="Поиск товаров..." 
                            onkeyup="filterInventory()"
                            style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; width: 300px;"
                        >
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Категория</th>
                                    <th>Остаток (шт)</th>
                                    <th>Мин. запас</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Товары не найдены</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Не указана'); ?></td>
                                            <td><?php echo $product['stock_quantity']; ?></td>
                                            <td><?php echo $product['min_stock_level']; ?></td>
                                            <td>
                                                <?php if ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                                                    <span class="badge badge-danger">Низкий запас</span>
                                                <?php elseif ($product['stock_quantity'] <= $product['min_stock_level'] * 1.5): ?>
                                                    <span class="badge badge-warning">Средний запас</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">В норме</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
                <h2>Расход товара со склада</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="outcome">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="outcome_product_id">Товар *</label>
                        <select id="outcome_product_id" name="product_id" required onchange="updateProductInfo('outcome')">
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
                            <label for="outcome_quantity">Количество *</label>
                            <input type="number" id="outcome_quantity" name="quantity" required min="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="outcome_document_number">Номер документа</label>
                            <input type="text" id="outcome_document_number" name="document_number" placeholder="Например: ТОРГ-12 №123">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="outcome_notes">Комментарий</label>
                        <textarea id="outcome_notes" name="notes" rows="2" placeholder="Дополнительная информация"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('outcomeModal')">Отмена</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-minus"></i> Списать
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
                <input type="hidden" name="action" value="write_off">
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
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Filter inventory table
        function filterInventory() {
            const input = document.getElementById('inventorySearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('inventoryTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const tdCode = tr[i].getElementsByTagName('td')[0];
                const tdName = tr[i].getElementsByTagName('td')[1];
                const tdCategory = tr[i].getElementsByTagName('td')[2];
                
                if (tdCode || tdName || tdCategory) {
                    const codeValue = tdCode.textContent || tdCode.innerText;
                    const nameValue = tdName.textContent || tdName.innerText;
                    const categoryValue = tdCategory.textContent || tdCategory.innerText;
                    
                    if (codeValue.toUpperCase().indexOf(filter) > -1 ||
                        nameValue.toUpperCase().indexOf(filter) > -1 ||
                        categoryValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
        
        // Update product info when selecting product
        function updateProductInfo(modalPrefix) {
            const select = document.getElementById(modalPrefix + '_product_id');
            const selectedOption = select.options[select.selectedIndex];
            const stock = selectedOption.getAttribute('data-stock');
            const code = selectedOption.getAttribute('data-code');
            
            if (stock && modalPrefix !== 'transfer') {
                const quantityInput = document.getElementById(modalPrefix + '_quantity');
                if (quantityInput && (modalPrefix === 'outcome' || modalPrefix === 'writeoff')) {
                    quantityInput.max = stock;
                    quantityInput.title = 'Доступно: ' + stock + ' шт.';
                }
            }
        }
        
        // Auto-select product if passed in URL
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('product_id');
            
            if (productId) {
                const selects = ['income_product_id', 'outcome_product_id', 'writeoff_product_id'];
                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        select.value = productId;
                        updateProductInfo(selectId.replace('_product_id', ''));
                    }
                });
            }
        });
    </script>
</body>
</html>

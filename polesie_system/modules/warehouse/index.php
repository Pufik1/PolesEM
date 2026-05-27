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
                            <select id="powerFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Любая мощность</option>
                                <option value="0-0.5">до 0.5 кВт</option>
                                <option value="0.5-1">0.5 - 1 кВт</option>
                                <option value="1-3">1 - 3 кВт</option>
                                <option value="3-5">3 - 5 кВт</option>
                                <option value="5-10">5 - 10 кВт</option>
                                <option value="10-20">10 - 20 кВт</option>
                                <option value="20-50">20 - 50 кВт</option>
                                <option value="50-100">50 - 100 кВт</option>
                                <option value="100+">более 100 кВт</option>
                            </select>
                            <select id="frameSizeFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Любой габарит</option>
                                <option value="63">63</option>
                                <option value="71">71</option>
                                <option value="80">80</option>
                                <option value="90">90</option>
                                <option value="100">100</option>
                                <option value="112">112</option>
                                <option value="132">132</option>
                                <option value="160">160</option>
                                <option value="180">180</option>
                                <option value="200">200</option>
                                <option value="225">225</option>
                                <option value="250">250</option>
                                <option value="280">280</option>
                                <option value="315">315</option>
                            </select>
                            <select id="voltageFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Любое напряжение</option>
                                <option value="220">220 В</option>
                                <option value="380">380 В</option>
                                <option value="400">400 В</option>
                                <option value="660">660 В</option>
                            </select>
                            <select id="protectionFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Любая защита</option>
                                <option value="IP23">IP23</option>
                                <option value="IP44">IP44</option>
                                <option value="IP54">IP54</option>
                                <option value="IP55">IP55</option>
                                <option value="IP65">IP65</option>
                            </select>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="lowStockFilter" onchange="applyFilters()" <?php echo $filterLowStock ? 'checked' : ''; ?>>
                                Только низкий запас
                            </label>
                            
                        <?php else: ?>
                            <!-- Material filters -->
                            <select id="materialCategoryFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Все категории</option>
                                <?php foreach ($materialCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="zoneFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Все зоны</option>
                                <?php foreach ($warehouseZones as $zone): ?>
                                    <option value="<?php echo $zone['zone_code']; ?>" <?php echo $filterZone == $zone['zone_code'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($zone['zone_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="unitFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Все единицы</option>
                                <option value="шт">шт</option>
                                <option value="кг">кг</option>
                                <option value="м">м</option>
                                <option value="л">л</option>
                                <option value="т">т</option>
                                <option value="рулон">рулон</option>
                            </select>
                            <select id="tempClassFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Любой класс нагревостойкости</option>
                                <option value="A">A (105°C)</option>
                                <option value="E">E (120°C)</option>
                                <option value="B">B (130°C)</option>
                                <option value="F">F (155°C)</option>
                                <option value="H">H (180°C)</option>
                            </select>
                            <select id="ipRatingFilter" onchange="applyFilters()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                                <option value="">Любая степень защиты</option>
                                <option value="IP44">IP44</option>
                                <option value="IP54">IP54</option>
                                <option value="IP55">IP55</option>
                                <option value="IP65">IP65</option>
                            </select>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="lowStockFilter" onchange="applyFilters()" <?php echo $filterLowStock ? 'checked' : ''; ?>>
                                Только низкий запас
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Категория</th>
                                    <?php if ($activeTab === 'materials'): ?>
                                    <th>Зона</th>
                                    <th>Ед.изм.</th>
                                    <?php endif; ?>
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
                                            <td colspan="8" class="text-center">Материалы не найдены</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($materials as $material): ?>
                                            <tr data-material-id="<?php echo $material['id']; ?>" 
                                                data-category="<?php echo $material['category_id'] ?? ''; ?>"
                                                data-zone="<?php echo $material['storage_zone'] ?? ''; ?>"
                                                data-unit="<?php echo $material['unit'] ?? ''; ?>"
                                                data-temp-class="<?php echo $material['temperature_class'] ?? ''; ?>"
                                                data-ip-rating="<?php echo $material['ip_rating'] ?? ''; ?>">
                                                <td><strong><?php echo htmlspecialchars($material['sku']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($material['name']); ?></td>
                                                <td><?php echo htmlspecialchars($material['category_name'] ?? 'Не указана'); ?></td>
                                                <td><?php echo htmlspecialchars($material['zone_name'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($material['unit']); ?></td>
                                                <td><?php echo $material['current_stock']; ?> <?php echo htmlspecialchars($material['unit']); ?></td>
                                                <td><?php echo $material['min_stock_level']; ?> <?php echo htmlspecialchars($material['unit']); ?></td>
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
            const powerFilter = document.getElementById('powerFilter').value;
            const frameFilter = document.getElementById('frameSizeFilter').value;
            const voltageFilter = document.getElementById('voltageFilter').value;
            const protectionFilter = document.getElementById('protectionFilter').value;
            const lowStockFilter = document.getElementById('lowStockFilter').checked;
            
            const table = document.getElementById('inventoryTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                const productId = row.getAttribute('data-product-id');
                const category = row.getAttribute('data-category');
                const power = parseFloat(row.getAttribute('data-power')) || 0;
                const frame = row.getAttribute('data-frame');
                const voltage = row.getAttribute('data-voltage');
                const protection = row.getAttribute('data-protection');
                
                const tdCode = row.getElementsByTagName('td')[0];
                const tdName = row.getElementsByTagName('td')[1];
                const tdCategory = row.getElementsByTagName('td')[2];
                const tdStock = row.getElementsByTagName('td')[3];
                const tdMinStock = row.getElementsByTagName('td')[4];
                
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
                
                // Power filter
                if (showRow && powerFilter) {
                    const parts = powerFilter.split('-');
                    if (parts.length === 2) {
                        const minPower = parseFloat(parts[0]);
                        const maxPower = parseFloat(parts[1]);
                        if (power < minPower || power > maxPower) {
                            showRow = false;
                        }
                    } else if (powerFilter.endsWith('+')) {
                        const minPower = parseFloat(powerFilter);
                        if (power <= minPower) {
                            showRow = false;
                        }
                    }
                }
                
                // Frame size filter
                if (showRow && frameFilter && frame !== frameFilter) {
                    showRow = false;
                }
                
                // Voltage filter
                if (showRow && voltageFilter && !voltage.includes(voltageFilter)) {
                    showRow = false;
                }
                
                // Protection filter
                if (showRow && protectionFilter && protection !== protectionFilter) {
                    showRow = false;
                }
                
                // Low stock filter
                if (showRow && lowStockFilter) {
                    const stockText = tdStock.textContent || tdStock.innerText;
                    const minStockText = tdMinStock.textContent || tdMinStock.innerText;
                    const stock = parseInt(stockText) || 0;
                    const minStock = parseInt(minStockText) || 0;
                    if (stock > minStock) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        }
        
        function filterMaterialsTable(searchFilter) {
            const categoryFilter = document.getElementById('materialCategoryFilter').value;
            const zoneFilter = document.getElementById('zoneFilter').value;
            const unitFilter = document.getElementById('unitFilter').value;
            const tempClassFilter = document.getElementById('tempClassFilter').value;
            const ipRatingFilter = document.getElementById('ipRatingFilter').value;
            const lowStockFilter = document.getElementById('lowStockFilter').checked;
            
            const table = document.getElementById('inventoryTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                const materialId = row.getAttribute('data-material-id');
                const category = row.getAttribute('data-category');
                const zone = row.getAttribute('data-zone');
                const unit = row.getAttribute('data-unit');
                const tempClass = row.getAttribute('data-temp-class');
                const ipRating = row.getAttribute('data-ip-rating');
                
                const tdSku = row.getElementsByTagName('td')[0];
                const tdName = row.getElementsByTagName('td')[1];
                const tdCategory = row.getElementsByTagName('td')[2];
                const tdStock = row.getElementsByTagName('td')[5];
                const tdMinStock = row.getElementsByTagName('td')[6];
                
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
                
                // Zone filter
                if (showRow && zoneFilter && zone !== zoneFilter) {
                    showRow = false;
                }
                
                // Unit filter
                if (showRow && unitFilter && unit !== unitFilter) {
                    showRow = false;
                }
                
                // Temperature class filter
                if (showRow && tempClassFilter && tempClass !== tempClassFilter) {
                    showRow = false;
                }
                
                // IP rating filter
                if (showRow && ipRatingFilter && ipRating !== ipRatingFilter) {
                    showRow = false;
                }
                
                // Low stock filter
                if (showRow && lowStockFilter) {
                    const stockText = tdStock.textContent || tdStock.innerText;
                    const minStockText = tdMinStock.textContent || tdMinStock.innerText;
                    const stock = parseFloat(stockText) || 0;
                    const minStock = parseFloat(minStockText) || 0;
                    if (stock > minStock) {
                        showRow = false;
                    }
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
                        <div><strong>Текущий остаток:</strong> ${material.current_stock} ${escapeHtml(material.unit)}</div>
                        <div><strong>Минимальный запас:</strong> ${material.min_stock_level} ${escapeHtml(material.unit)}</div>
                        <div><strong>Цена за единицу:</strong> ${material.price_per_unit} ${escapeHtml(material.currency || 'BYN')}</div>
                        <div><strong>Единица измерения:</strong> ${escapeHtml(material.unit)}</div>
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
            if (material.weight) {
                detailsHtml += `<div><strong>Вес единицы:</strong> ${material.weight} кг</div>`;
            }
            if (material.length) {
                detailsHtml += `<div><strong>Длина:</strong> ${material.length} м</div>`;
            }
            if (material.width) {
                detailsHtml += `<div><strong>Ширина:</strong> ${material.width} мм</div>`;
            }
            if (material.thickness) {
                detailsHtml += `<div><strong>Толщина:</strong> ${material.thickness} мм</div>`;
            }
            if (material.diameter) {
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
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body" id="${modalId}Body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('${modalId}')">Закрыть</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                // Add close button handler
                modal.querySelector('.modal-close').addEventListener('click', () => closeModal(modalId));
            }
            
            document.getElementById(modalId + 'Body').innerHTML = content;
            modal.style.display = 'block';
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

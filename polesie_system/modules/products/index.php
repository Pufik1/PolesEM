<?php
/**
 * Products module for OAO "Polesieelectromash" ERP System
 * Product catalog management
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
        if ($action === 'add') {
            $product_code = trim($_POST['product_code']);
            $product_name = trim($_POST['product_name']);
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $description = trim($_POST['description']);
            $base_price = (float)$_POST['base_price'];
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
            $min_stock_level = (int)($_POST['min_stock_level'] ?? 10);
            
            $stmt = $pdo->prepare("INSERT INTO products (product_code, product_name, category_id, description, base_price, stock_quantity, min_stock_level) 
                                   VALUES (:product_code, :product_name, :category_id, :description, :base_price, :stock_quantity, :min_stock_level)");
            $stmt->execute([
                ':product_code' => $product_code,
                ':product_name' => $product_name,
                ':category_id' => $category_id,
                ':description' => $description,
                ':base_price' => $base_price,
                ':stock_quantity' => $stock_quantity,
                ':min_stock_level' => $min_stock_level
            ]);
            
            logActivity($pdo, $_SESSION['user_id'], 'create_product', 'products', $pdo->lastInsertId());
            $success = 'Продукция успешно добавлена';
            
        } elseif ($action === 'edit') {
            $product_id = (int)$_POST['product_id'];
            $product_code = trim($_POST['product_code']);
            $product_name = trim($_POST['product_name']);
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $description = trim($_POST['description']);
            $base_price = (float)$_POST['base_price'];
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
            $min_stock_level = (int)($_POST['min_stock_level'] ?? 10);
            
            $stmt = $pdo->prepare("UPDATE products SET 
                                   product_code = :product_code,
                                   product_name = :product_name,
                                   category_id = :category_id,
                                   description = :description,
                                   base_price = :base_price,
                                   stock_quantity = :stock_quantity,
                                   min_stock_level = :min_stock_level
                                   WHERE id = :id");
            $stmt->execute([
                ':product_code' => $product_code,
                ':product_name' => $product_name,
                ':category_id' => $category_id,
                ':description' => $description,
                ':base_price' => $base_price,
                ':stock_quantity' => $stock_quantity,
                ':min_stock_level' => $min_stock_level,
                ':id' => $product_id
            ]);
            
            logActivity($pdo, $_SESSION['user_id'], 'update_product', 'products', $product_id);
            $success = 'Продукция успешно обновлена';
            
        } elseif ($action === 'delete') {
            $product_id = (int)$_POST['product_id'];
            
            // Сначала удаляем связанные записи во всех таблицах где есть product_id
            // production_orders
            $stmt = $pdo->prepare("DELETE FROM production_orders WHERE product_id = :id");
            $stmt->execute([':id' => $product_id]);
            
            // order_items
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id = :id");
            $stmt->execute([':id' => $product_id]);
            
            // technological_operations
            $stmt = $pdo->prepare("DELETE FROM technological_operations WHERE product_id = :id");
            $stmt->execute([':id' => $product_id]);
            
            // quality_control
            $stmt = $pdo->prepare("DELETE FROM quality_control WHERE product_id = :id");
            $stmt->execute([':id' => $product_id]);
            
            // warehouse_inventory
            $stmt = $pdo->prepare("DELETE FROM warehouse_inventory WHERE product_id = :id");
            $stmt->execute([':id' => $product_id]);
            
            // Теперь удаляем продукт
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $product_id]);
            
            logActivity($pdo, $_SESSION['user_id'], 'delete_product', 'products', $product_id);
            $success = 'Продукция успешно удалена';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

// Get action parameter for modals
$viewAction = $_GET['action'] ?? '';
$viewProductId = $_GET['product_id'] ?? null;
$editProduct = null;
$viewProduct = null;

if ($viewAction === 'edit' && $viewProductId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => (int)$viewProductId]);
        $editProduct = $stmt->fetch();
    } catch (PDOException $e) {
        // Error handling
    }
}

if ($viewAction === 'view' && $viewProductId) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, pc.category_name FROM products p LEFT JOIN product_categories pc ON p.category_id = pc.id WHERE p.id = :id");
        $stmt->execute([':id' => (int)$viewProductId]);
        $viewProduct = $stmt->fetch();
        
        // Если это AJAX запрос, возвращаем JSON
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            if ($viewProduct) {
                // Декодируем specifications из JSON строки в массив
                $specs = json_decode($viewProduct['specifications'] ?? '{}', true);
                $viewProduct['specifications'] = $specs;

                // Загружаем материалы для продукта (BOM - Bill of Materials)
                $bomMaterials = [];
                try {
                    $bomStmt = $pdo->prepare("
                        SELECT 
                            pb.bom_version,
                            m.id as material_id,
                            m.sku,
                            m.name as material_name,
                            m.unit,
                            pbi.quantity,
                            pbi.waste_percent,
                            pbi.sequence_order,
                            pbi.notes,
                            mc.category_name as material_category
                        FROM product_bom pb
                        INNER JOIN product_bom_items pbi ON pb.id = pbi.bom_id
                        INNER JOIN materials m ON pbi.material_id = m.id
                        LEFT JOIN material_categories mc ON m.category_id = mc.id
                        WHERE pb.product_id = :product_id AND pb.is_active = 1
                        ORDER BY pbi.sequence_order
                    ");
                    $bomStmt->execute([':product_id' => (int)$viewProductId]);
                    $bomMaterials = $bomStmt->fetchAll();
                } catch (PDOException $e) {
                    // BOM table might not exist or no data
                    $bomMaterials = [];
                }

                echo json_encode([
                    'success' => true,
                    'product' => $viewProduct,
                    'bom_materials' => $bomMaterials
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Продукт не найден'
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    } catch (PDOException $e) {
        // Error handling
    }
}

// Get all active products with categories
try {
    $stmt = $pdo->query("
        SELECT p.*, pc.category_name 
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $pdo->query("SELECT * FROM product_categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Ошибка загрузки данных';
    $products = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Продукция - <?php echo APP_NAME; ?></title>
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
                        <h1>Каталог продукции</h1>
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
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Продукция предприятия</h2>
                        <button class="btn btn-primary" onclick="openModal('addProductModal')">
                            <i class="fas fa-plus"></i> Добавить продукт
                        </button>
                    </div>
                    
                    <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                        <input 
                            type="text" 
                            id="productSearch"
                            placeholder="Поиск продукции..." 
                            onkeyup="filterProducts()"
                            style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; width: 300px;"
                        >
                        <select id="categoryFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Все категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="powerFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
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
                        <select id="speedFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Любая скорость</option>
                            <option value="750">750 об/мин</option>
                            <option value="1000">1000 об/мин</option>
                            <option value="1500">1500 об/мин</option>
                            <option value="3000">3000 об/мин</option>
                        </select>
                        <select id="frameFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
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
                        <select id="voltageFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Любое напряжение</option>
                            <option value="220">220 В</option>
                            <option value="380">380 В</option>
                            <option value="400">400 В</option>
                            <option value="660">660 В</option>
                            <option value="3000">3000 В</option>
                            <option value="6000">6000 В</option>
                            <option value="10000">10000 В</option>
                        </select>
                        <select id="frequencyFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Любая частота</option>
                            <option value="50">50 Гц</option>
                            <option value="60">60 Гц</option>
                        </select>
                        <select id="cosPhiFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Любой cos φ</option>
                            <option value="0.50-0.60">0.50 - 0.60</option>
                            <option value="0.60-0.70">0.60 - 0.70</option>
                            <option value="0.70-0.75">0.70 - 0.75</option>
                            <option value="0.75-0.80">0.75 - 0.80</option>
                            <option value="0.80-0.85">0.80 - 0.85</option>
                            <option value="0.85-0.90">0.85 - 0.90</option>
                            <option value="0.90+">более 0.90</option>
                        </select>
                        <select id="efficiencyFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Любой КПД</option>
                            <option value="60-70">60% - 70%</option>
                            <option value="70-75">70% - 75%</option>
                            <option value="75-80">75% - 80%</option>
                            <option value="80-85">80% - 85%</option>
                            <option value="85-90">85% - 90%</option>
                            <option value="90+">более 90%</option>
                        </select>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <th>Категория</th>
                                    <th>Цена (BYN)</th>
                                    <th>Остаток</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Продукция не найдена</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <?php 
                                        // Декодируем specifications для data-product атрибута
                                        $specs = json_decode($product['specifications'] ?? '{}', true);
                                        $productWithSpecs = $product;
                                        $productWithSpecs['specifications'] = $specs;
                                        ?>
                                        <tr data-category="<?php echo $product['category_id'] ?? ''; ?>" 
                                            data-power="<?php echo isset($specs['power_kw']) ? $specs['power_kw'] : ''; ?>"
                                            data-speed="<?php echo isset($specs['rpm']) ? $specs['rpm'] : ''; ?>"
                                            data-frame="<?php echo isset($specs['frame_size_mm']) ? $specs['frame_size_mm'] : ''; ?>"
                                            data-voltage="<?php echo isset($specs['voltage_v']) ? $specs['voltage_v'] : ''; ?>"
                                            data-frequency="<?php echo isset($specs['frequency_hz']) ? $specs['frequency_hz'] : ''; ?>"
                                            data-cosphi="<?php echo isset($specs['cos_phi']) ? $specs['cos_phi'] : ''; ?>"
                                            data-efficiency="<?php echo isset($specs['efficiency_pct']) ? $specs['efficiency_pct'] : ''; ?>"
                                            data-product='<?php echo htmlspecialchars(json_encode($productWithSpecs, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>'>
                                            <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Не указана'); ?></td>
                                            <td><?php echo number_format($product['base_price'], 2); ?></td>
                                            <td>
                                                <?php if ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                                                    <span class="badge badge-danger"><?php echo $product['stock_quantity']; ?> шт.</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success"><?php echo $product['stock_quantity']; ?> шт.</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-icon btn-primary" title="Просмотр характеристик" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-secondary" title="Редактировать" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-danger" title="Удалить" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
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
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Добавить продукцию</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_code">Артикул *</label>
                            <input type="text" id="product_code" name="product_code" required placeholder="Например: AIR71A2">
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Категория</label>
                            <select id="category_id" name="category_id">
                                <option value="">Выберите категорию</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_name">Наименование *</label>
                        <input type="text" id="product_name" name="product_name" required placeholder="Полное наименование продукции">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <textarea id="description" name="description" rows="3" placeholder="Технические характеристики, особенности"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="base_price">Базовая цена (BYN) *</label>
                            <input type="number" id="base_price" name="base_price" step="0.01" required min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity">Остаток на складе</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" value="0" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="min_stock_level">Минимальный запас</label>
                            <input type="number" id="min_stock_level" name="min_stock_level" value="10" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Редактировать продукцию</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_product_code">Артикул *</label>
                            <input type="text" id="edit_product_code" name="product_code" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_category_id">Категория</label>
                            <select id="edit_category_id" name="category_id">
                                <option value="">Выберите категорию</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_product_name">Наименование *</label>
                        <input type="text" id="edit_product_name" name="product_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Описание</label>
                        <textarea id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_base_price">Базовая цена (BYN) *</label>
                            <input type="number" id="edit_base_price" name="base_price" step="0.01" required min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_stock_quantity">Остаток на складе</label>
                            <input type="number" id="edit_stock_quantity" name="stock_quantity" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_min_stock_level">Минимальный запас</label>
                            <input type="number" id="edit_min_stock_level" name="min_stock_level" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Product Modal -->
    <div id="viewProductModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Просмотр продукции</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Содержимое загружается динамически через JavaScript -->
                <p class="text-muted">Нажмите на иконку глаза для просмотра характеристик товара</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewProductModal')">Закрыть</button>
            </div>
        </div>
    </div>

    <div id="deleteProductModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Удаление продукции</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" id="delete_product_id">
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить продукцию <strong id="delete_product_name"></strong>?</p>
                    <p class="text-muted">Это действие безвозвратно удалит продукт из базы данных.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteProductModal')">Отмена</button>
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Расшифровка артикулов продукции ОАО "Полесьеэлектромаш"
        function decodeSKU(sku) {
            if (!sku) return 'Артикул не указан';
            
            sku = sku.toUpperCase().trim();
            
            // Префиксы и их значения
            const prefixes = {
                'MAT-AL': 'Алюминий вторичный',
                'MAT-CI': 'Чугун литейный',
                'CI-GR': 'Решетка колосниковая (чугун)',
                'CI-DR': 'Дождеприемник (чугун)',
                'CI-MH': 'Люк манhole (чугун)',
                'CI-FL': 'Плита половая (чугун)',
                'CI-BALLS': 'Цильпебсы, шары мелющие',
                'AIR': 'Электродвигатель асинхронный общего назначения',
                'AIRS': 'Электродвигатель с повышенным скольжением',
                'AIRE': 'Электродвигатель однофазный (220В)',
                'AIRP': 'Электродвигатель для птицеводства (защищенный)',
                '2AIR': 'Электродвигатель двухскоростной',
                'AIRCH': 'Электродвигатель железнодорожный виброустойчивый',
                'AIRV': 'Электродвигатель встроенный (для встройки)',
                'AIRVS': 'Электродвигатель встроенный с повышенным скольжением'
            };
            
            // Габариты
            const frameSizes = {
                '71': 'Габарит 71 мм',
                '80': 'Габарит 80 мм',
                '90': 'Габарит 90 мм',
                '100': 'Габарит 100 мм',
                '112': 'Габарит 112 мм'
            };
            
            // Число полюсов / скорость
            const poles = {
                '2': '2 полюса (3000 об/мин)',
                '4': '4 полюса (1500 об/мин)',
                '6': '6 полюсов (1000 об/мин)',
                '8': '8 полюсов (750 об/мин)'
            };
            
            // Специальные суффиксы
            const suffixes = {
                '_ZH': 'Для насосов (нерж. вал, уплотнение)',
                '_RZ': 'Специсполнение вала/фланца',
                '_S6': '6 полюсов (1000 об/мин)',
                'A': 'Первая длина корпуса',
                'B': 'Вторая длина корпуса',
                'C': 'Третья длина корпуса',
                'D': 'Четвертая длина корпуса',
                'L': 'Длинная версия',
                'M': 'Средняя версия',
                'S': 'Короткая версия'
            };
            
            let decoding = [];
            let matchedPrefix = false;
            
            // Поиск префикса
            for (const prefix in prefixes) {
                if (sku.startsWith(prefix)) {
                    decoding.push(prefixes[prefix]);
                    matchedPrefix = true;
                    break;
                }
            }
            
            if (!matchedPrefix) {
                decoding.push('Тип продукции не определен');
            }
            
            // Для чугунных изделий - расшифровка из products.json
            if (sku.startsWith('CI-GR')) {
                const modelMatch = sku.match(/CI-GR-(RU\d|RD\dK?|57L|001|ANIM)/);
                if (modelMatch) {
                    const model = modelMatch[1];
                    const models = {
                        'RU2': 'Колосниковая решетка РУ-2 (200x300 мм, 3.5 кг, ГОСТ СТБ 726-2006)',
                        'RU3': 'Колосниковая решетка РУ-3 (200x350 мм, 5.5 кг, ГОСТ СТБ 726-2006)',
                        'RU4': 'Колосниковая решетка РУ-4 (400x200 мм, 6.0 кг, ГОСТ СТБ 726-2006)',
                        'RD3': 'Колосниковая решетка РД-3 (170x240 мм, 2.2 кг, ГОСТ СТБ 726-2006)',
                        'RD6K': 'Колосниковая решетка РД-6К (250x380 мм, 5.2 кг, ГОСТ СТБ 726-2006)',
                        '57L': 'Колосниковая решетка 57Л (240x415 мм, 6.5 кг, ГОСТ СТБ 726-2006)',
                        '001': 'Решетка 001 (496x300x34 мм, 14.2 кг)',
                        'ANIM': 'Решетка животноводческая (420x310x60 мм, 20.0 кг)'
                    };
                    decoding.push(models[model] || 'Модель: ' + model);
                }
            } else if (sku.startsWith('CI-DR')) {
                if (sku.includes('ASSY')) {
                    decoding.push('Дождеприемник в сборе (500x1000 мм, 105 кг, ГОСТ СТБ 3634-99)');
                } else {
                    decoding.push('Решетка дождеприемника (400x800x40 мм, ГОСТ СТБ 3634-99)');
                }
            } else if (sku.startsWith('CI-MH')) {
                if (sku.includes('L') && sku.includes('V15')) {
                    decoding.push('Люк легкий типа Л, класс нагрузки В15 (71.9 кг, ГОСТ 3634-99)');
                }
            } else if (sku.startsWith('CI-FL')) {
                decoding.push('Плита половая (300x420x27 мм)');
            } else if (sku.startsWith('CI-BALLS')) {
                decoding.push('Цильпебсы, шары мелющие (⌀20–100 мм, ТУ предприятия)');
            } else if (sku.startsWith('MAT-AL')) {
                if (sku.includes('AB87')) {
                    decoding.push('Марка: АВ87 (ГОСТ 295-98)');
                }
                if (sku.includes('F')) {
                    decoding.push('Форма: гранулированный');
                } else {
                    decoding.push('Форма: чушка');
                }
            } else if (sku.startsWith('MAT-CI')) {
                if (sku.includes('L4')) {
                    decoding.push('Марка чугуна: Л4 (ГОСТ 4832-95)');
                } else if (sku.includes('L5')) {
                    decoding.push('Марка чугуна: Л5 (ГОСТ 4832-95)');
                }
            }

            // Для электродвигателей - полная расшифровка по данным products.json
            if (sku.startsWith('AIR') && !sku.startsWith('AIRV') && !sku.startsWith('AIRVS')) {
                // Базовые электродвигатели общего назначения
                if (!sku.startsWith('AIRS') && !sku.startsWith('AIRE') && !sku.startsWith('AIRP') && !sku.startsWith('2AIR') && !sku.startsWith('AIRCH')) {
                    const motorMatch = sku.match(/AIR(\d{2,3})([A-D]?)([2468])/);
                    if (motorMatch) {
                        const frameSize = motorMatch[1];
                        const lengthCode = motorMatch[2] || '';
                        const polesCode = motorMatch[3];
                        
                        if (frameSizes[frameSize]) {
                            decoding.push(frameSizes[frameSize]);
                        }
                        if (lengthCode && suffixes[lengthCode]) {
                            decoding.push(suffixes[lengthCode]);
                        }
                        if (poles[polesCode]) {
                            decoding.push(poles[polesCode]);
                        }
                    }
                }
            } else if (sku.startsWith('AIRS')) {
                decoding[0] = 'Электродвигатель с повышенным скольжением';
                const motorMatch = sku.match(/AIRS(\d{2,3})([A-D]?)([2468])|AIRS(\d{2,3})([A-D]?)C?_S6/);
                if (motorMatch) {
                    const frameSize = motorMatch[1] || motorMatch[4];
                    const lengthCode = motorMatch[2] || motorMatch[5] || '';
                    const polesCode = motorMatch[3] || '6';
                    
                    if (frameSizes[frameSize]) {
                        decoding.push(frameSizes[frameSize]);
                    }
                    if (lengthCode && suffixes[lengthCode]) {
                        decoding.push(suffixes[lengthCode]);
                    }
                    if (poles[polesCode]) {
                        decoding.push(poles[polesCode]);
                    }
                }
            } else if (sku.startsWith('AIRE')) {
                decoding[0] = 'Электродвигатель однофазный (220В, 50Гц)';
                const motorMatch = sku.match(/AIRE(\d{2,3})([A-D]?)([2468])/);
                if (motorMatch) {
                    const frameSize = motorMatch[1];
                    const lengthCode = motorMatch[2] || '';
                    const polesCode = motorMatch[3];
                    
                    if (frameSizes[frameSize]) {
                        decoding.push(frameSizes[frameSize]);
                    }
                    if (lengthCode && suffixes[lengthCode]) {
                        decoding.push(suffixes[lengthCode]);
                    }
                    if (poles[polesCode]) {
                        decoding.push(poles[polesCode]);
                    }
                }
            } else if (sku.startsWith('AIRP')) {
                decoding[0] = 'Электродвигатель для птицеводства (защищенный IP55, NH₃/H₂S/SO₂/HCl)';
                const motorMatch = sku.match(/AIRP(\d{2,3})([A-C]?)(\d)/);
                if (motorMatch) {
                    const frameSize = motorMatch[1];
                    const lengthCode = motorMatch[2] || '';
                    const polesCode = motorMatch[3];
                    
                    if (frameSizes[frameSize]) {
                        decoding.push(frameSizes[frameSize]);
                    }
                    if (lengthCode && suffixes[lengthCode]) {
                        decoding.push(suffixes[lengthCode]);
                    }
                    if (poles[polesCode]) {
                        decoding.push(poles[polesCode]);
                    }
                }
            } else if (sku.startsWith('2AIR')) {
                decoding[0] = 'Электродвигатель двухскоростной';
                const motorMatch = sku.match(/2AIR(\d{2,3})([A-L]?)(\d)/);
                if (motorMatch) {
                    const frameSize = motorMatch[1];
                    const lengthCode = motorMatch[2] || '';
                    const polesCode = motorMatch[3];
                    
                    if (frameSizes[frameSize]) {
                        decoding.push(frameSizes[frameSize]);
                    }
                    if (lengthCode && suffixes[lengthCode]) {
                        decoding.push(suffixes[lengthCode]);
                    }
                    // Двухскоростные имеют две скорости
                    if (poles[polesCode]) {
                        const secondPole = polesCode == '2' ? '4' : (polesCode == '4' ? '8' : (polesCode == '6' ? '8' : '4'));
                        if (poles[secondPole]) {
                            decoding.push(poles[polesCode] + ' / ' + poles[secondPole]);
                        }
                    }
                }
            } else if (sku.startsWith('AIRCH')) {
                decoding[0] = 'Электродвигатель железнодорожный виброустойчивый';
                const motorMatch = sku.match(/AIRCH(\d{2,3})([A-B]?)(\d)/);
                if (motorMatch) {
                    const frameSize = motorMatch[1];
                    const lengthCode = motorMatch[2] || '';
                    const polesCode = motorMatch[3];
                    
                    if (frameSizes[frameSize]) {
                        decoding.push(frameSizes[frameSize]);
                    }
                    if (lengthCode && suffixes[lengthCode]) {
                        decoding.push(suffixes[lengthCode]);
                    }
                    if (poles[polesCode]) {
                        decoding.push(poles[polesCode]);
                    }
                }
            } else if (sku.startsWith('AIRV') || sku.startsWith('AIRVS')) {
                if (sku.startsWith('AIRVS')) {
                    decoding[0] = 'Электродвигатель встроенный с повышенным скольжением (для встройки)';
                } else {
                    decoding[0] = 'Электродвигатель встроенный (для встройки)';
                }
                const motorMatch = sku.match(/AIRVS?(\d{3})([A-B]?)(\d)/);
                if (motorMatch) {
                    const frameSize = motorMatch[1];
                    const lengthCode = motorMatch[2] || '';
                    const polesCode = motorMatch[3];
                    
                    decoding.push(`Габарит ${frameSize} мм`);
                    if (lengthCode && suffixes[lengthCode]) {
                        decoding.push(suffixes[lengthCode]);
                    }
                    if (poles[polesCode]) {
                        decoding.push(poles[polesCode]);
                    }
                }
            }
            
            return decoding.join('; ');
        }
        
        // Product management functions
        function editProduct(productId) {
            // Fetch product data and populate edit modal
            fetch(`index.php?action=edit&product_id=${productId}`)
                .then(response => response.text())
                .then(html => {
                    // Parse the HTML to extract product data
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // For now, we'll redirect with query params
                    window.location.href = `index.php?action=edit&product_id=${productId}`;
                });
        }
        
        function viewProduct(productId) {
            // Показываем модальное окно с индикатором загрузки
            openModal('viewProductModal');
            document.querySelector('#viewProductModal .modal-body').innerHTML = '<p class="text-muted" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Загрузка информации о продукте...</p>';
            
            // Загружаем данные через AJAX
            fetch(`index.php?action=view&product_id=${productId}&ajax=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderProductModal(data.product, data.bom_materials || []);
                    } else {
                        document.querySelector('#viewProductModal .modal-body').innerHTML = '<p class="text-muted" style="text-align: center; padding: 40px; color: var(--error-color);">Ошибка загрузки данных</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.querySelector('#viewProductModal .modal-body').innerHTML = '<p class="text-muted" style="text-align: center; padding: 40px; color: var(--error-color);">Ошибка загрузки данных</p>';
                });
        }
        
        function renderProductModal(product, bomMaterials = []) {
            const skuDecoding = decodeSKU(product.product_code);
            
            let specsHtml = '';
            // product.specifications теперь уже объект (декодирован на сервере)
            if (product.specifications && typeof product.specifications === 'object' && Object.keys(product.specifications).length > 0) {
                specsHtml = '<table style="width: 100%; border-collapse: collapse;">';
                for (const [key, value] of Object.entries(product.specifications)) {
                    // Красивое отображение ключей характеристик на русском языке
                    let displayKey = key;
                    const keyMap = {
                        'gost': 'ГОСТ',
                        'size_mm': 'Размеры (мм)',
                        'material': 'Материал',
                        'weight_kg': 'Вес (кг)',
                        'power_kw': 'Мощность (кВт)',
                        'rpm': 'Частота вращения (об/мин)',
                        'efficiency_pct': 'КПД (%)',
                        'cos_phi': 'cos φ',
                        'frame_size_mm': 'Габарит (мм)',
                        'voltage_v': 'Напряжение (В)',
                        'frequency_hz': 'Частота (Гц)',
                        'capacitor_mkF': 'Конденсатор (мкФ)',
                        'protection': 'Защита',
                        'environment': 'Среда',
                        'grade': 'Марка',
                        'form': 'Форма',
                        'application': 'Применение',
                        'storage_conditions': 'Условия хранения',
                        'note': 'Примечание',
                        'stator_d_mm': 'Диаметр статора (мм)',
                        'stator_l_mm': 'Длина статора (мм)'
                    };
                    displayKey = keyMap[key] || key;
                    
                    specsHtml += `<tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px; font-weight: bold;">${displayKey}</td>
                        <td style="padding: 8px;">${value}</td>
                    </tr>`;
                }
                specsHtml += '</table>';
            } else {
                specsHtml = '<p class="text-muted">Характеристики не указаны</p>';
            }
            
            // Вес указывается за одну штуку
            const weightInfo = product.weight ? `${product.weight} кг (за 1 шт.)` : 'Н/Д';
            
            // Формирование HTML для материалов (BOM)
            let bomHtml = '';
            if (bomMaterials && bomMaterials.length > 0) {
                bomHtml = '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">' +
                    '<thead>' +
                        '<tr style="background-color: var(--primary-color); color: white;">' +
                            '<th style="padding: 10px; text-align: left;">№</th>' +
                            '<th style="padding: 10px; text-align: left;">Материал</th>' +
                            '<th style="padding: 10px; text-align: left;">Артикул</th>' +
                            '<th style="padding: 10px; text-align: center;">Кол-во (шт)</th>' +
                            '<th style="padding: 10px; text-align: left;">Категория</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>';
                
                bomMaterials.forEach((material, index) => {
                    bomHtml += `<tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px;">${index + 1}</td>
                        <td style="padding: 8px;"><strong>${material.material_name}</strong></td>
                        <td style="padding: 8px; font-family: monospace;">${material.sku}</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; color: var(--primary-color);">${Math.round(material.quantity)}</td>
                        <td style="padding: 8px;">${material.material_category || '-'}</td>
                    </tr>`;
                });
                
                bomHtml += '</tbody></table>';
            } else {
                bomHtml = '<p class="text-muted">Материалы не указаны или спецификация не создана</p>';
            }
            
            const html = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h3>${product.product_name}</h3>
                        <p><strong>Артикул:</strong> ${product.product_code}</p>
                        <p><strong>Расшифровка артикула:</strong> <span style="color: var(--primary-color); font-weight: bold;">${skuDecoding}</span></p>
                        <p><strong>Категория:</strong> ${product.category_name || 'Не указана'}</p>
                        <p><strong>Цена:</strong> ${parseFloat(product.base_price).toFixed(2)} BYN</p>
                        <p><strong>Остаток на складе:</strong> ${product.stock_quantity} шт.</p>
                        <p><strong>Вес:</strong> ${weightInfo}</p>
                    </div>
                    <div>
                        <h4>Характеристики</h4>
                        ${specsHtml}
                    </div>
                </div>
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid var(--border-color);">
                    <h3 style="color: var(--primary-color);"><i class="fas fa-list-ul"></i> Материалы для производства (1 шт. продукта)</h3>
                    ${bomHtml}
                </div>
                <div style="margin-top: 20px;">
                    <h4>Описание</h4>
                    <p>${product.description ? product.description.replace(/\\n/g, '<br>') : 'Описание отсутствует'}</p>
                </div>
            `;
            
            document.querySelector('#viewProductModal .modal-body').innerHTML = html;
        }
        
        function deleteProduct(productId, productName) {
            document.getElementById('delete_product_id').value = productId;
            document.getElementById('delete_product_name').textContent = productName;
            openModal('deleteProductModal');
        }
        
        // Функция для отображения расшифровки артикула в модальном окне (больше не используется, т.к. данные загружаются через AJAX)
        function showSKUDecoding(sku) {
            // Эта функция больше не нужна, так как расшифровка теперь отображается в renderProductModal
            console.log('SKU Decoding:', decodeSKU(sku));
        }
        
        // Populate edit modal if editing
        <?php if ($editProduct): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('edit_product_id').value = '<?php echo $editProduct['id']; ?>';
            document.getElementById('edit_product_code').value = '<?php echo htmlspecialchars($editProduct['product_code']); ?>';
            document.getElementById('edit_product_name').value = '<?php echo htmlspecialchars($editProduct['product_name']); ?>';
            document.getElementById('edit_category_id').value = '<?php echo $editProduct['category_id']; ?>';
            document.getElementById('edit_description').value = '<?php echo htmlspecialchars($editProduct['description']); ?>';
            document.getElementById('edit_base_price').value = '<?php echo $editProduct['base_price']; ?>';
            document.getElementById('edit_stock_quantity').value = '<?php echo $editProduct['stock_quantity']; ?>';
            document.getElementById('edit_min_stock_level').value = '<?php echo $editProduct['min_stock_level']; ?>';
            openModal('editProductModal');
        });
        <?php endif; ?>
        
        // Filter products function
        function filterProducts() {
            const searchInput = document.getElementById('productSearch').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const powerFilter = document.getElementById('powerFilter').value;
            const speedFilter = document.getElementById('speedFilter').value;
            const frameFilter = document.getElementById('frameFilter').value;
            const voltageFilter = document.getElementById('voltageFilter').value;
            const frequencyFilter = document.getElementById('frequencyFilter').value;
            const cosPhiFilter = document.getElementById('cosPhiFilter').value;
            const efficiencyFilter = document.getElementById('efficiencyFilter').value;
            const table = document.getElementById('productsTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                // Пропускаем строки без data-power атрибута (например, строку "Продукция не найдена")
                if (!row.dataset.power && row.cells.length < 6) return;
                
                const productCode = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
                const productName = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
                const category = row.dataset.category || '';
                
                // Используем data-атрибуты для быстрого доступа к характеристикам
                const powerValue = parseFloat(row.dataset.power) || 0;
                const speedValue = parseInt(row.dataset.speed) || 0;
                const frameValue = String(row.dataset.frame || '').trim();
                const voltageValue = String(row.dataset.voltage || '').trim();
                const frequencyValue = String(row.dataset.frequency || '').trim();
                const cosPhiValue = String(row.dataset.cosphi || '').trim();
                const efficiencyValue = String(row.dataset.efficiency || '').trim();
                
                // Check search match
                const matchesSearch = productCode.includes(searchInput) || productName.includes(searchInput);
                
                // Check category match
                const matchesCategory = categoryFilter === '' || category === categoryFilter;
                
                // Check power match - диапазоны значений
                let matchesPower = true;
                if (powerFilter !== '') {
                    if (powerFilter === '0-0.5') matchesPower = powerValue >= 0 && powerValue < 0.5;
                    else if (powerFilter === '0.5-1') matchesPower = powerValue >= 0.5 && powerValue < 1;
                    else if (powerFilter === '1-3') matchesPower = powerValue >= 1 && powerValue < 3;
                    else if (powerFilter === '3-5') matchesPower = powerValue >= 3 && powerValue < 5;
                    else if (powerFilter === '5-10') matchesPower = powerValue >= 5 && powerValue < 10;
                    else if (powerFilter === '10-20') matchesPower = powerValue >= 10 && powerValue < 20;
                    else if (powerFilter === '20-50') matchesPower = powerValue >= 20 && powerValue < 50;
                    else if (powerFilter === '50-100') matchesPower = powerValue >= 50 && powerValue < 100;
                    else if (powerFilter === '100+') matchesPower = powerValue >= 100;
                }
                
                // Check speed match - точное совпадение
                const matchesSpeed = speedFilter === '' || speedValue === parseInt(speedFilter);
                
                // Check frame match - точное совпадение
                const matchesFrame = frameFilter === '' || frameValue === frameFilter;
                
                // Check voltage match - точное совпадение
                const matchesVoltage = voltageFilter === '' || voltageValue === voltageFilter;
                
                // Check frequency match - точное совпадение
                const matchesFrequency = frequencyFilter === '' || frequencyValue === frequencyFilter;
                
                // Check cos phi match - диапазоны значений
                let matchesCosPhi = true;
                if (cosPhiFilter !== '') {
                    const productCosPhi = parseFloat(cosPhiValue);
                    if (cosPhiFilter === '0.50-0.60') matchesCosPhi = productCosPhi >= 0.50 && productCosPhi < 0.60;
                    else if (cosPhiFilter === '0.60-0.70') matchesCosPhi = productCosPhi >= 0.60 && productCosPhi < 0.70;
                    else if (cosPhiFilter === '0.70-0.75') matchesCosPhi = productCosPhi >= 0.70 && productCosPhi < 0.75;
                    else if (cosPhiFilter === '0.75-0.80') matchesCosPhi = productCosPhi >= 0.75 && productCosPhi < 0.80;
                    else if (cosPhiFilter === '0.80-0.85') matchesCosPhi = productCosPhi >= 0.80 && productCosPhi < 0.85;
                    else if (cosPhiFilter === '0.85-0.90') matchesCosPhi = productCosPhi >= 0.85 && productCosPhi < 0.90;
                    else if (cosPhiFilter === '0.90+') matchesCosPhi = productCosPhi >= 0.90;
                }
                
                // Check efficiency match - диапазоны значений
                let matchesEfficiency = true;
                if (efficiencyFilter !== '') {
                    const productEfficiency = parseFloat(efficiencyValue);
                    if (efficiencyFilter === '60-70') matchesEfficiency = productEfficiency >= 60 && productEfficiency < 70;
                    else if (efficiencyFilter === '70-75') matchesEfficiency = productEfficiency >= 70 && productEfficiency < 75;
                    else if (efficiencyFilter === '75-80') matchesEfficiency = productEfficiency >= 75 && productEfficiency < 80;
                    else if (efficiencyFilter === '80-85') matchesEfficiency = productEfficiency >= 80 && productEfficiency < 85;
                    else if (efficiencyFilter === '85-90') matchesEfficiency = productEfficiency >= 85 && productEfficiency < 90;
                    else if (efficiencyFilter === '90+') matchesEfficiency = productEfficiency >= 90;
                }
                
                if (matchesSearch && matchesCategory && matchesPower && matchesSpeed && matchesFrame && 
                    matchesVoltage && matchesFrequency && matchesCosPhi && matchesEfficiency) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

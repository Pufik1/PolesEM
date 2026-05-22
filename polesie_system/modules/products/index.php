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
                            <option value="0-1">до 1 кВт</option>
                            <option value="1-3">1-3 кВт</option>
                            <option value="3-5">3-5 кВт</option>
                            <option value="5-10">5-10 кВт</option>
                            <option value="10+">более 10 кВт</option>
                        </select>
                        <select id="speedFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Любая скорость</option>
                            <option value="3000">3000 об/мин</option>
                            <option value="1500">1500 об/мин</option>
                            <option value="1000">1000 об/мин</option>
                            <option value="750">750 об/мин</option>
                        </select>
                        <select id="frameFilter" onchange="filterProducts()" style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Любой габарит</option>
                            <option value="71">71</option>
                            <option value="80">80</option>
                            <option value="90">90</option>
                            <option value="100">100</option>
                            <option value="112">112</option>
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
                                        <tr data-category="<?php echo $product['category_id'] ?? ''; ?>" 
                                            data-specs='<?php echo htmlspecialchars(json_encode($product['specifications'] ?? '{}'), ENT_QUOTES); ?>'>
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
    
    <!-- Delete Confirmation Modal -->
    <!-- View Product Modal -->
    <div id="viewProductModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Просмотр продукции</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($viewProduct): ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h3><?php echo htmlspecialchars($viewProduct['product_name']); ?></h3>
                        <p><strong>Артикул:</strong> <?php echo htmlspecialchars($viewProduct['product_code']); ?></p>
                        <p><strong>Категория:</strong> <?php echo htmlspecialchars($viewProduct['category_name'] ?? 'Не указана'); ?></p>
                        <p><strong>Цена:</strong> <?php echo number_format($viewProduct['base_price'], 2); ?> BYN</p>
                        <p><strong>Остаток на складе:</strong> <?php echo $viewProduct['stock_quantity']; ?> шт.</p>
                        <p><strong>Вес:</strong> <?php echo $viewProduct['weight']; ?> кг</p>
                    </div>
                    <div>
                        <h4>Характеристики</h4>
                        <?php 
                        $specs = json_decode($viewProduct['specifications'] ?? '[]', true);
                        if (!empty($specs) && is_array($specs)):
                        ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <?php foreach ($specs as $key => $value): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 8px; font-weight: bold;"><?php echo htmlspecialchars($key); ?></td>
                                <td style="padding: 8px;"><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <?php else: ?>
                        <p class="text-muted">Характеристики не указаны</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <h4>Описание</h4>
                    <p><?php echo nl2br(htmlspecialchars($viewProduct['description'] ?? 'Описание отсутствует')); ?></p>
                </div>
                <?php else: ?>
                <p class="text-muted">Данные о продукте загружаются...</p>
                <?php endif; ?>
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
            window.location.href = `index.php?action=view&product_id=${productId}`;
        }
        
        function deleteProduct(productId, productName) {
            document.getElementById('delete_product_id').value = productId;
            document.getElementById('delete_product_name').textContent = productName;
            openModal('deleteProductModal');
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
            const table = document.getElementById('productsTable');
            const rows = table.querySelectorAll('tbody tr[data-category]');
            
            rows.forEach(row => {
                const productCode = row.cells[0].textContent.toLowerCase();
                const productName = row.cells[1].textContent.toLowerCase();
                const category = row.dataset.category;
                const specs = row.dataset.specs ? JSON.parse(row.dataset.specs) : {};
                
                // Extract power, speed, frame from specs
                const powerStr = specs['Мощность'] || specs['мощность'] || '';
                const speedStr = specs['Частота вращения'] || specs['Об/мин'] || specs['частота вращения'] || '';
                const frameStr = specs['Габарит'] || specs['габарит'] || '';
                
                // Parse power value
                let powerValue = 0;
                const powerMatch = powerStr.match(/([\d.]+)\s*кВт/);
                if (powerMatch) {
                    powerValue = parseFloat(powerMatch[1]);
                }
                
                // Parse speed value (take first number if multiple)
                const speedMatch = speedStr.match(/(\d+)/);
                const speedValue = speedMatch ? parseInt(speedMatch[1]) : 0;
                
                // Parse frame value
                const frameValue = frameStr.trim();
                
                // Check search match
                const matchesSearch = productCode.includes(searchInput) || productName.includes(searchInput);
                
                // Check category match
                const matchesCategory = categoryFilter === '' || category === categoryFilter;
                
                // Check power match
                let matchesPower = true;
                if (powerFilter !== '') {
                    if (powerFilter === '0-1') matchesPower = powerValue >= 0 && powerValue <= 1;
                    else if (powerFilter === '1-3') matchesPower = powerValue > 1 && powerValue <= 3;
                    else if (powerFilter === '3-5') matchesPower = powerValue > 3 && powerValue <= 5;
                    else if (powerFilter === '5-10') matchesPower = powerValue > 5 && powerValue <= 10;
                    else if (powerFilter === '10+') matchesPower = powerValue > 10;
                }
                
                // Check speed match
                const matchesSpeed = speedFilter === '' || speedValue === parseInt(speedFilter);
                
                // Check frame match
                const matchesFrame = frameFilter === '' || frameValue === frameFilter;
                
                if (matchesSearch && matchesCategory && matchesPower && matchesSpeed && matchesFrame) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

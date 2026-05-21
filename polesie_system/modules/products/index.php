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

// Get all active products with categories
try {
    $stmt = $pdo->query("
        SELECT p.*, pc.category_name 
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_active = 1 
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

$userFullName = $_SESSION['full_name'];
$userRole = $_SESSION['user_role'];
$initials = strtoupper(substr($userFullName, 0, 1));
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
            
            <nav class="sidebar-menu">
                <div class="menu-category">Основное</div>
                <a href="../../dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Дашборд</span>
                </a>
                
                <div class="menu-category">Операции</div>
                <a href="../clients/index.php" class="menu-item">
                    <i class="fas fa-handshake"></i>
                    <span>Клиенты</span>
                </a>
                <a href="../orders/index.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Заказы</span>
                </a>
                <a href="../products/index.php" class="menu-item active">
                    <i class="fas fa-box"></i>
                    <span>Продукция</span>
                </a>
                
                <div class="menu-category">Система</div>
                <a href="../../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выход</span>
                </a>
            </nav>
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
                    
                    <div style="margin-bottom: 20px; display: flex; gap: 15px;">
                        <input 
                            type="text" 
                            placeholder="Поиск продукции..." 
                            data-table-search="productsTable"
                            style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; width: 300px;"
                        >
                        <select style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px;">
                            <option value="">Все категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
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
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Продукция не найдена</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
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
                                                <?php if ($product['is_active']): ?>
                                                    <span class="badge badge-success">Активен</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Неактивен</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-icon btn-primary" title="Просмотр">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-secondary" title="Редактировать">
                                                        <i class="fas fa-edit"></i>
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
                
                <!-- Product Categories Info -->
                <div class="stats-grid mt-20">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-motorcycle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Электродвигатели</h3>
                            <p>АИР, АИРЕ, 2AIR, АИВР</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-plug"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Электроконфорки</h3>
                            <p>Чугунные бытовые</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-water"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Насосы</h3>
                            <p>Бытовые и ГНОМ</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Литье</h3>
                            <p>Чугунное и цветное</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Добавить продукцию</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="product_code">Артикул *</label>
                        <input type="text" id="product_code" name="product_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_name">Наименование *</label>
                        <input type="text" id="product_name" name="product_name" required>
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
                    
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="base_price">Базовая цена (BYN) *</label>
                        <input type="number" id="base_price" name="base_price" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Остаток на складе</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="min_stock_level">Минимальный запас</label>
                        <input type="number" id="min_stock_level" name="min_stock_level" value="10">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
</body>
</html>

<?php
/**
 * Sidebar menu component for OAO "Polesieelectromash" ERP System
 * Displays navigation menu based on user role
 * 
 * Usage: include this file from any page, setting $basePath relative to root
 */

// Determine base path if not set (for nested directories)
if (!isset($basePath)) {
    $basePath = '../../';
}

// Menu items configuration based on roles
$menuItems = [
    'admin' => [
        'main' => [
            ['icon' => 'fa-tachometer-alt', 'text' => 'Главная', 'url' => 'dashboard.php'],
            ['icon' => 'fa-users', 'text' => 'Пользователи', 'url' => 'modules/users/index.php'],
            ['icon' => 'fa-box', 'text' => 'Продукция', 'url' => 'modules/products/index.php'],
        ],
        'operations' => [
            ['icon' => 'fa-handshake', 'text' => 'Клиенты', 'url' => 'modules/clients/index.php'],
            ['icon' => 'fa-shopping-cart', 'text' => 'Заказы', 'url' => 'modules/orders/index.php'],
            ['icon' => 'fa-industry', 'text' => 'Производство', 'url' => 'modules/production/index.php'],
            ['icon' => 'fa-warehouse', 'text' => 'Склад', 'url' => 'modules/warehouse/index.php'],
            ['icon' => 'fa-file-invoice-dollar', 'text' => 'Финансы', 'url' => 'modules/finance/index.php'],
            ['icon' => 'fa-chart-bar', 'text' => 'Отчеты', 'url' => 'modules/reports/index.php'],
        ],
    ],
    'director' => [
        'main' => [
            ['icon' => 'fa-tachometer-alt', 'text' => 'Главная', 'url' => 'dashboard.php'],
            ['icon' => 'fa-box', 'text' => 'Продукция', 'url' => 'modules/products/index.php'],
        ],
        'operations' => [
            ['icon' => 'fa-handshake', 'text' => 'Клиенты', 'url' => 'modules/clients/index.php'],
            ['icon' => 'fa-shopping-cart', 'text' => 'Заказы', 'url' => 'modules/orders/index.php'],
            ['icon' => 'fa-industry', 'text' => 'Производство', 'url' => 'modules/production/index.php'],
            ['icon' => 'fa-chart-bar', 'text' => 'Отчеты', 'url' => 'modules/reports/index.php'],
        ],
    ],
    'manager' => [
        'main' => [
            ['icon' => 'fa-tachometer-alt', 'text' => 'Главная', 'url' => 'dashboard.php'],
            ['icon' => 'fa-box', 'text' => 'Продукция', 'url' => 'modules/products/index.php'],
        ],
        'operations' => [
            ['icon' => 'fa-handshake', 'text' => 'Клиенты', 'url' => 'modules/clients/index.php'],
            ['icon' => 'fa-shopping-cart', 'text' => 'Заказы', 'url' => 'modules/orders/index.php'],
        ],
    ],
    'production_master' => [
        'main' => [
            ['icon' => 'fa-tachometer-alt', 'text' => 'Главная', 'url' => 'dashboard.php'],
            ['icon' => 'fa-box', 'text' => 'Продукция', 'url' => 'modules/products/index.php'],
        ],
        'operations' => [
            ['icon' => 'fa-industry', 'text' => 'Производство', 'url' => 'modules/production/index.php'],
        ],
    ],
    'warehouse_keeper' => [
        'main' => [
            ['icon' => 'fa-tachometer-alt', 'text' => 'Главная', 'url' => 'dashboard.php'],
            ['icon' => 'fa-box', 'text' => 'Продукция', 'url' => 'modules/products/index.php'],
        ],
        'operations' => [
            ['icon' => 'fa-warehouse', 'text' => 'Склад', 'url' => 'modules/warehouse/index.php'],
        ],
    ],
    'accountant' => [
        'main' => [
            ['icon' => 'fa-tachometer-alt', 'text' => 'Главная', 'url' => 'dashboard.php'],
        ],
        'operations' => [
            ['icon' => 'fa-file-invoice-dollar', 'text' => 'Финансы', 'url' => 'modules/finance/index.php'],
            ['icon' => 'fa-chart-bar', 'text' => 'Отчеты', 'url' => 'modules/reports/index.php'],
        ],
    ],
    'hr_manager' => [
        'main' => [
            ['icon' => 'fa-tachometer-alt', 'text' => 'Главная', 'url' => 'dashboard.php'],
        ],
        'operations' => [
        ],
    ],
];

// Get current user role and determine menu
$userRole = $_SESSION['user_role'] ?? 'manager';
$currentMenu = $menuItems[$userRole] ?? $menuItems['manager'];

// Function to check if URL is current page
function isCurrentPage($url) {
    $currentPath = $_SERVER['PHP_SELF'];
    // Normalize paths for comparison
    $normalizedUrl = ltrim($url, '/');
    $normalizedPath = ltrim($currentPath, '/');
    
    // Check if the URL matches the end of the current path
    return strpos($normalizedPath, $normalizedUrl) !== false || 
           strpos($currentPath, $normalizedUrl) !== false;
}

// Render sidebar menu
?>
<nav class="sidebar-menu">
    <!-- Вкладка Основное -->
    <?php if (!empty($currentMenu['main'])): ?>
        <div class="menu-category">Основное</div>
        <?php foreach ($currentMenu['main'] as $item): ?>
            <a href="<?php echo $basePath . $item['url']; ?>" class="menu-item <?php echo isCurrentPage($item['url']) ? 'active' : ''; ?>">
                <i class="fas <?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['text']; ?></span>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Вкладка Операции -->
    <?php if (!empty($currentMenu['operations'])): ?>
        <div class="menu-category">Операции</div>
        <?php foreach ($currentMenu['operations'] as $item): ?>
            <a href="<?php echo $basePath . $item['url']; ?>" class="menu-item <?php echo isCurrentPage($item['url']) ? 'active' : ''; ?>">
                <i class="fas <?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['text']; ?></span>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Вкладка Система -->
    <div class="menu-category" style="margin-top: 20px;">Система</div>
    <a href="<?php echo $basePath; ?>logout.php" class="menu-item">
        <i class="fas fa-sign-out-alt"></i>
        <span>Выйти</span>
    </a>
</nav>

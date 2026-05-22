<?php
/**
 * Configuration file for OAO "Polesieelectromash" ERP System
 * Database connection and system settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'polesie_electromash');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Default MAMP password
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Полесьеэлектромаш - Корпоративная Система');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost:8888/polesie_system/');
define('APP_COMPANY_NAME', 'ОАО «Полесьеэлектромаш»');
define('APP_INN', '123456789');
define('APP_ADDRESS', 'г. Лунинец, ул. Красная 179');
define('APP_PHONE', '+375 (232) 00-00-00');
define('APP_EMAIL', 'info@polesie.by');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/Minsk');

// Create database connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Ошибка подключения к базе данных: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Helper function to check user role
function hasRole($allowedRoles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    return in_array($_SESSION['user_role'], $allowedRoles);
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Function to log user activity with Russian action names
function logActivity($pdo, $userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    // Перевод действий на русский язык
    $actionTranslations = [
        'Создание пользователя' => 'Создание пользователя',
        'Редактирование пользователя' => 'Редактирование пользователя',
        'Удаление пользователя' => 'Удаление пользователя',
        'user_create' => 'Создание пользователя',
        'user_update' => 'Редактирование пользователя',
        'user_delete' => 'Удаление пользователя',
        'create_order' => 'Создание заказа',
        'update_order' => 'Редактирование заказа',
        'delete_order' => 'Удаление заказа',
        'create_product' => 'Создание продукта',
        'update_product' => 'Редактирование продукта',
        'delete_product' => 'Удаление продукта',
        'create_client' => 'Создание клиента',
        'update_client' => 'Редактирование клиента',
        'delete_client' => 'Удаление клиента',
    ];
    
    $translatedAction = $actionTranslations[$action] ?? $action;
    
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, table_name, record_id, old_values, new_values, ip_address) 
                           VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address)");
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $translatedAction,
        ':table_name' => $tableName,
        ':record_id' => $recordId,
        ':old_values' => $oldValues ? json_encode($oldValues) : null,
        ':new_values' => $newValues ? json_encode($newValues) : null,
        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}

// Function to get user permissions
function getUserPermissions($pdo, $roleId) {
    $stmt = $pdo->prepare("SELECT permissions FROM roles WHERE id = :role_id");
    $stmt->execute([':role_id' => $roleId]);
    $result = $stmt->fetch();
    return $result ? json_decode($result['permissions'], true) : [];
}

?>

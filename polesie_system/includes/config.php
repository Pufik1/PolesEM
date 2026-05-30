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
        // Пользователи
        'user_create' => 'Создание пользователя',
        'user_update' => 'Редактирование пользователя',
        'user_delete' => 'Удаление пользователя',
        'user_login' => 'Вход в систему',
        'user_logout' => 'Выход из системы',
        'Создание пользователя' => 'Создание пользователя',
        'Редактирование пользователя' => 'Редактирование пользователя',
        'Удаление пользователя' => 'Удаление пользователя',
        
        // Заказы
        'create_order' => 'Создание заказа',
        'update_order' => 'Редактирование заказа',
        'delete_order' => 'Удаление заказа',
        'order_created' => 'Создание заказа',
        'order_status_updated' => 'Обновление статуса заказа',
        'order_payment_updated' => 'Обновление оплаты заказа',
        
        // Продукты
        'create_product' => 'Создание продукта',
        'update_product' => 'Редактирование продукта',
        'delete_product' => 'Удаление продукта',
        'Создание продукта' => 'Создание продукта',
        'Редактирование продукта' => 'Редактирование продукта',
        'Удаление продукта' => 'Удаление продукта',
        
        // Клиенты
        'create_client' => 'Создание клиента',
        'update_client' => 'Редактирование клиента',
        'delete_client' => 'Удаление клиента',
        'client_created' => 'Создание клиента',
        'client_updated' => 'Редактирование клиента',
        'client_deleted' => 'Удаление клиента',
        
        // Счета и финансы
        'invoice_created' => 'Создание счета',
        'invoice_updated' => 'Редактирование счета',
        'invoice_payment' => 'Оплата счета',
        'invoice_deleted' => 'Удаление счета',
        
        // Документы
        'delivery_note_created' => 'Создание накладной на доставку',
        'delivery_note_updated' => 'Редактирование накладной на доставку',
        'transport_waybill_created' => 'Создание транспортной накладной',
        'transport_waybill_updated' => 'Редактирование транспортной накладной',
        
        // Склад
        'Приход товара' => 'Приход товара',
        'Расход товара' => 'Расход товара',
        'Перемещение товара' => 'Перемещение товара',
        'Списание товара' => 'Списание товара',
        'Приход готовой продукции' => 'Приход готовой продукции',
        'Отгрузка готовой продукции' => 'Отгрузка готовой продукции',
        'Массовая отгрузка готовой продукции' => 'Массовая отгрузка готовой продукции',
        'Массовая выдача материалов' => 'Массовая выдача материалов',
        'Приход материалов' => 'Приход материалов',
        'Расход материалов' => 'Расход материалов',
        'Перемещение готовой продукции' => 'Перемещение готовой продукции',
        'Перемещение материалов' => 'Перемещение материалов',
        'Списание готовой продукции' => 'Списание готовой продукции',
        'Списание материалов' => 'Списание материалов',
        'Редактирование акта приема' => 'Редактирование акта приема',
        'Редактирование накладной на отгрузку' => 'Редактирование накладной на отгрузку',
        'Редактирование акта списания' => 'Редактирование акта списания',
        'Удаление акта приема' => 'Удаление акта приема',
        'Удаление накладной на отгрузку' => 'Удаление накладной на отгрузку',
        'Удаление акта списания' => 'Удаление акта списания',
        
        // Производство
        'create_production_order' => 'Создание производственного заказа',
        'start_production' => 'Запуск производства',
        'complete_production' => 'Завершение производства',
        'issue_materials' => 'Выдача материалов',
        'create_quality_control' => 'Создание записи контроля качества',
        
        // Документы склада
        'goods_receipt_documents' => 'Документы приема товара',
        'shipment_documents' => 'Документы отгрузки',
        'material_writeoff_documents' => 'Документы списания материалов',
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

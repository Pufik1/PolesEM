<?php
/**
 * API для получения данных пользователя (AJAX)
 * Используется модулем пользователей для редактирования
 */

require_once '../../includes/config.php';

// Проверка авторизации
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка прав доступа
if (!hasRole(['admin', 'director'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Нет доступа']);
    exit;
}

header('Content-Type: application/json');

$userId = $_GET['id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID пользователя']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Не передаем пароль
        unset($user['password']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>

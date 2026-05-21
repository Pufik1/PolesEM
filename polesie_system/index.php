<?php
/**
 * Login page for OAO "Polesieelectromash" ERP System
 * Modern, professional authentication interface
 */

require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.password, u.full_name, u.email, u.role_id, 
                       r.role_name, r.role_description
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.username = :username AND u.is_active = 1
            ");
            
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && $password === $user['password']) { // Plain text as requested
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $user['id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['email'] = $user['email'];
                
                // Log activity
                logActivity($pdo, $user['id'], 'user_login', 'users', $user['id']);
                
                redirect('dashboard.php');
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка подключения к базе данных';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-bolt"></i>
                </div>
                <h1>Полесьеэлектромаш</h1>
                <p>Корпоративная система управления предприятием</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Логин</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Введите ваш логин"
                        value="<?php echo htmlspecialchars($username ?? ''); ?>"
                        required 
                        autofocus
                    >
                    <i class="fas fa-user input-icon"></i>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Пароль</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Введите ваш пароль"
                        required
                    >
                    <i class="fas fa-lock input-icon"></i>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Войти в систему
                </button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <p style="color: var(--text-secondary); font-size: 12px;">
                    © 2026 ОАО «Полесьеэлектромаш»<br>
                    г. Лунинец, ул. Красная 179
                </p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>

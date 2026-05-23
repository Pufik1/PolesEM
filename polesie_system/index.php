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
                WHERE u.username = :username
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
    <style>
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .login-left {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            width: 100%;
        }
        
        .login-box {
            max-width: 420px;
            width: 100%;
        }
        
        @media (max-width: 968px) {
            .login-left {
                padding: 20px;
            }
        }
        
        .login-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            font-weight: bold;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .login-header h1 {
            color: var(--text-primary);
            font-size: 26px;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .login-header p {
            color: var(--text-secondary);
            font-size: 14px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-footer {
            margin-top: 20px;
            text-align: center;
        }
        
        .form-footer p {
            color: var(--text-secondary);
            font-size: 12px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }
        
        .divider span {
            padding: 0 15px;
            color: var(--text-secondary);
            font-size: 12px;
        }
        
        .help-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid var(--border-color);
        }
        
        .help-box p {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 0 0 10px 0;
        }
        
        .help-contacts {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .help-contact {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-primary);
        }
        
        .help-contact i {
            color: var(--primary-color);
        }
        
        @media (max-width: 968px) {
            .login-left {
                padding: 20px;
            }
        }
        
        /* Анимация появления */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-box {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
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
                
                <div class="form-footer">
                    <p>
                        © 2026 ОАО «Полесьеэлектромаш»<br>
                        г. Лунинец, ул. Красная 179
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>

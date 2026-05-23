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
        /* Дополнительные стили для улучшенной страницы входа */
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .login-right {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            color: white;
            position: relative;
        }
        
        .login-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .login-box {
            max-width: 420px;
            width: 100%;
        }
        
        .info-section {
            max-width: 600px;
            z-index: 1;
        }
        
        .info-section h2 {
            font-size: 36px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .info-section p {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.8;
            margin-bottom: 40px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .feature-card h3 {
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .feature-card p {
            font-size: 13px;
            opacity: 0.8;
            margin: 0;
            line-height: 1.5;
        }
        
        .stats-row {
            display: flex;
            gap: 30px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--secondary-color);
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .company-info {
            margin-top: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .company-info h4 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .company-info p {
            font-size: 13px;
            margin: 5px 0;
            opacity: 0.8;
        }
        
        .company-info i {
            width: 20px;
            margin-right: 8px;
        }
        
        .quick-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-link {
            color: white;
            text-decoration: none;
            font-size: 13px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }
        
        .quick-link:hover {
            opacity: 1;
            color: var(--secondary-color);
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
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            
            .login-right {
                order: -1;
                padding: 40px 20px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-row {
                justify-content: center;
            }
            
            .info-section h2 {
                font-size: 28px;
            }
            
            .info-section p {
                font-size: 16px;
            }
        }
        
        /* Анимация появления */
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .feature-card {
            animation: fadeInLeft 0.6s ease forwards;
        }
        
        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Левая часть - форма входа -->
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
                
                <div class="divider">
                    <span>Нужна помощь?</span>
                </div>
                
                <div class="help-box">
                    <p><strong>Техническая поддержка:</strong></p>
                    <div class="help-contacts">
                        <div class="help-contact">
                            <i class="fas fa-phone"></i>
                            <span>+375 (1647) 5-XX-XX</span>
                        </div>
                        <div class="help-contact">
                            <i class="fas fa-envelope"></i>
                            <span>support@polesie.by</span>
                        </div>
                        <div class="help-contact">
                            <i class="fas fa-clock"></i>
                            <span>Пн-Пт: 8:00 - 17:00</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-footer">
                    <p>
                        © 2026 ОАО «Полесьеэлектромаш»<br>
                        г. Лунинец, ул. Красная 179
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Правая часть - информация -->
        <div class="login-right">
            <div class="info-section">
                <h2>Единая система управления предприятием</h2>
                <p>
                    Современная ERP-система для автоматизации всех бизнес-процессов 
                    ОАО «Полесьеэлектромаш». Контроль производства, складских запасов, 
                    финансов и персонала в одном месте.
                </p>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Управление производством</h3>
                        <p>Планирование и контроль производственных процессов в реальном времени</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <h3>Складской учёт</h3>
                        <p>Автоматизация учёта товаров, материалов и готовой продукции</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Финансы и аналитика</h3>
                        <p>Подробная отчётность и анализ финансовых показателей предприятия</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Управление персоналом</h3>
                        <p>Учёт рабочего времени, зарплат и кадровое делопроизводство</p>
                    </div>
                </div>
                
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-number">10+</div>
                        <div class="stat-label">Модулей системы</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Пользователей</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Доступ к данным</div>
                    </div>
                </div>
                
                <div class="company-info">
                    <h4><i class="fas fa-building"></i> ОАО «Полесьеэлектромаш»</h4>
                    <p><i class="fas fa-map-marker-alt"></i> 225630, Брестская область, г. Лунинец, ул. Красная 179</p>
                    <p><i class="fas fa-phone"></i> +375 (1647) 5-XX-XX</p>
                    <p><i class="fas fa-globe"></i> www.polesie.by</p>
                    
                    <div class="quick-links">
                        <a href="#" class="quick-link">
                            <i class="fas fa-book"></i>
                            Документация
                        </a>
                        <a href="#" class="quick-link">
                            <i class="fas fa-question-circle"></i>
                            FAQ
                        </a>
                        <a href="#" class="quick-link">
                            <i class="fas fa-shield-alt"></i>
                            Безопасность
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>

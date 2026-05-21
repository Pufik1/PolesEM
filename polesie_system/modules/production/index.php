<?php require_once '../../includes/config.php'; if (!isLoggedIn()) redirect('../../index.php'); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Производство - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-bolt"></i></div>
                    <div class="logo-text"><h2>Полесьеэлектромаш</h2><p>Корпоративная система</p></div>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="../../dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Дашборд</span></a>
                <a href="../production/index.php" class="menu-item active"><i class="fas fa-industry"></i><span>Производство</span></a>
                <a href="../warehouse/index.php" class="menu-item"><i class="fas fa-warehouse"></i><span>Склад</span></a>
                <a href="../../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Выход</span></a>
            </nav>
        </aside>
        <div class="main-content">
            <header class="header">
                <div class="header-title"><h1>Производственный модуль</h1></div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                    </div>
                </div>
            </header>
            <div class="content-area">
                <div class="card">
                    <h2>Управление производством</h2>
                    <p>Модуль в разработке...</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

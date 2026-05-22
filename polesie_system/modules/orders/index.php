<?php
/**
 * Orders module placeholder
 */
require_once '../../includes/config.php';
if (!isLoggedIn()) redirect('../../index.php');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-bolt"></i></div>
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
        <div class="main-content">
            <header class="header">
                <div class="header-left">
                    <div class="header-title"><h1>Управление заказами</h1></div>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                            <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Заказы</h2>
                        <button class="btn btn-primary"><i class="fas fa-plus"></i> Создать заказ</button>
                    </div>
                    <p>Модуль заказов в разработке...</p>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>
</html>

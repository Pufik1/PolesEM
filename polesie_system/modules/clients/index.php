<?php
/**
 * Clients module for OAO "Polesieelectromash" ERP System
 * Client management - list, add, edit, delete clients
 */

require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

// Check permissions
if (!hasRole(['admin', 'director', 'manager'])) {
    redirect('../../dashboard.php');
}

$pdo = getDBConnection();
$error = '';
$success = '';

// Handle client deletion
if (isset($_GET['delete']) && hasRole(['admin', 'manager'])) {
    try {
        $clientId = (int)$_GET['delete'];
        $stmt = $pdo->prepare("UPDATE clients SET is_active = 0 WHERE id = :id");
        $stmt->execute([':id' => $clientId]);
        logActivity($pdo, $_SESSION['user_id'], 'client_deactivated', 'clients', $clientId);
        $success = 'Клиент успешно деактивирован';
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении клиента';
        error_log($e->getMessage());
    }
}

// Get all active clients
try {
    $stmt = $pdo->query("SELECT * FROM clients WHERE is_active = 1 ORDER BY created_at DESC");
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Ошибка загрузки данных';
    $clients = [];
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
    <title>Клиенты - <?php echo APP_NAME; ?></title>
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
            
            <?php 
            $basePath = '../../';
            include '../../includes/sidebar.php'; 
            ?>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <div class="header-title">
                        <h1>Управление клиентами</h1>
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
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">База клиентов</h2>
                        <?php if (hasRole(['admin', 'manager'])): ?>
                            <button class="btn btn-primary" onclick="openModal('addClientModal')">
                                <i class="fas fa-plus"></i> Добавить клиента
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <input 
                            type="text" 
                            placeholder="Поиск клиента..." 
                            data-table-search="clientsTable"
                            style="padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; width: 300px;"
                        >
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" id="clientsTable">
                            <thead>
                                <tr>
                                    <th>Код</th>
                                    <th>Компания</th>
                                    <th>Контактное лицо</th>
                                    <th>Телефон</th>
                                    <th>Email</th>
                                    <th>Скидка</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clients)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Клиентов не найдено</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($client['client_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($client['company_name'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($client['contact_person'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($client['phone'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($client['email'] ?? 'Не указано'); ?></td>
                                            <td><?php echo $client['discount_percent']; ?>%</td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-icon btn-primary" title="Просмотр">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (hasRole(['admin', 'manager'])): ?>
                                                        <button class="btn btn-sm btn-icon btn-secondary" title="Редактировать">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $client['id']; ?>" 
                                                           class="btn btn-sm btn-icon btn-danger"
                                                           onclick="return confirm('Вы уверены?')"
                                                           title="Деактивировать">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Добавить нового клиента</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="client_code">Код клиента *</label>
                        <input type="text" id="client_code" name="client_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name">Название компании</label>
                        <input type="text" id="company_name" name="company_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_person">Контактное лицо</label>
                        <input type="text" id="contact_person" name="contact_person">
                    </div>
                    
                    <div class="form-group">
                        <label for="inn">УНП/ИНН</label>
                        <input type="text" id="inn" name="inn">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Адрес</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_percent">Скидка (%)</label>
                        <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addClientModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
</body>
</html>

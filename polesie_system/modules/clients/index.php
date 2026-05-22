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
$editClient = null;

// Handle client addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasRole(['admin', 'manager'])) {
    try {
        if ($_POST['action'] === 'add') {
            $clientCode = trim($_POST['client_code']);
            $companyName = trim($_POST['company_name']);
            $contactPerson = trim($_POST['contact_person']);
            $inn = trim($_POST['inn']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            
            $stmt = $pdo->prepare("INSERT INTO clients (client_code, company_name, contact_person, inn, phone, email, address) 
                                   VALUES (:client_code, :company_name, :contact_person, :inn, :phone, :email, :address)");
            $stmt->execute([
                ':client_code' => $clientCode,
                ':company_name' => $companyName,
                ':contact_person' => $contactPerson,
                ':inn' => $inn,
                ':phone' => $phone,
                ':email' => $email,
                ':address' => $address
            ]);
            
            $newClientId = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'client_created', 'clients', $newClientId);
            $success = 'Клиент успешно добавлен';
            
        } elseif ($_POST['action'] === 'edit') {
            $clientId = (int)$_POST['client_id'];
            $companyName = trim($_POST['company_name']);
            $contactPerson = trim($_POST['contact_person']);
            $inn = trim($_POST['inn']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            
            $stmt = $pdo->prepare("UPDATE clients SET company_name = :company_name, contact_person = :contact_person, 
                                   inn = :inn, phone = :phone, email = :email, address = :address 
                                   WHERE id = :id");
            $stmt->execute([
                ':id' => $clientId,
                ':company_name' => $companyName,
                ':contact_person' => $contactPerson,
                ':inn' => $inn,
                ':phone' => $phone,
                ':email' => $email,
                ':address' => $address
            ]);
            
            logActivity($pdo, $_SESSION['user_id'], 'client_updated', 'clients', $clientId);
            $success = 'Данные клиента успешно обновлены';
        }
        
        // Refresh the page to avoid form resubmission
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . urlencode($success));
        exit;
        
    } catch (PDOException $e) {
        $error = 'Ошибка при сохранении данных клиента';
        error_log($e->getMessage());
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle client deletion
if (isset($_GET['delete']) && hasRole(['admin', 'manager'])) {
    try {
        $clientId = (int)$_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->execute([':id' => $clientId]);
        logActivity($pdo, $_SESSION['user_id'], 'client_deleted', 'clients', $clientId);
        $success = 'Клиент успешно удален';
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении клиента';
        error_log($e->getMessage());
    }
}

// Handle edit mode - load client data
if (isset($_GET['edit']) && hasRole(['admin', 'manager'])) {
    try {
        $clientId = (int)$_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
        $stmt->execute([':id' => $clientId]);
        $editClient = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Ошибка загрузки данных клиента';
        error_log($e->getMessage());
    }
}

// Handle view mode - load client data
if (isset($_GET['view'])) {
    try {
        $clientId = (int)$_GET['view'];
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
        $stmt->execute([':id' => $clientId]);
        $viewClient = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Ошибка загрузки данных клиента';
        error_log($e->getMessage());
    }
}

// Get all clients
try {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC");
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
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clients)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Клиентов не найдено</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($client['client_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($client['company_name'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($client['contact_person'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($client['phone'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($client['email'] ?? 'Не указано'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?view=<?php echo $client['id']; ?>" 
                                                       class="btn btn-sm btn-icon btn-primary" 
                                                       title="Просмотр">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (hasRole(['admin', 'manager'])): ?>
                                                        <a href="?edit=<?php echo $client['id']; ?>" 
                                                           class="btn btn-sm btn-icon btn-secondary" 
                                                           title="Редактировать">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?delete=<?php echo $client['id']; ?>" 
                                                           class="btn btn-sm btn-icon btn-danger"
                                                           onclick="return confirm('Вы уверены, что хотите удалить клиента?')"
                                                           title="Удалить">
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
                <input type="hidden" name="action" value="add">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addClientModal')">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Client Modal -->
    <?php if ($editClient): ?>
    <div id="editClientModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Редактировать клиента</h2>
                <a href="index.php" class="modal-close">&times;</a>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="client_id" value="<?php echo $editClient['id']; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_client_code">Код клиента</label>
                        <input type="text" id="edit_client_code" value="<?php echo htmlspecialchars($editClient['client_code']); ?>" disabled>
                        <small style="color: var(--text-light);">Код клиента нельзя изменить</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_company_name">Название компании</label>
                        <input type="text" id="edit_company_name" name="company_name" value="<?php echo htmlspecialchars($editClient['company_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_contact_person">Контактное лицо</label>
                        <input type="text" id="edit_contact_person" name="contact_person" value="<?php echo htmlspecialchars($editClient['contact_person'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_inn">УНП/ИНН</label>
                        <input type="text" id="edit_inn" name="inn" value="<?php echo htmlspecialchars($editClient['inn'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone">Телефон</label>
                        <input type="text" id="edit_phone" name="phone" value="<?php echo htmlspecialchars($editClient['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" value="<?php echo htmlspecialchars($editClient['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_address">Адрес</label>
                        <textarea id="edit_address" name="address" rows="3"><?php echo htmlspecialchars($editClient['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="?view=<?php echo $editClient['id']; ?>" class="btn btn-secondary">Отмена</a>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- View Client Modal -->
    <?php if (isset($viewClient) && $viewClient): ?>
    <div id="viewClientModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Информация о клиенте</h2>
                <a href="index.php" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <p><strong>Код клиента:</strong></p>
                        <p><?php echo htmlspecialchars($viewClient['client_code']); ?></p>
                    </div>
                    <div>
                        <p><strong>Компания:</strong></p>
                        <p><?php echo htmlspecialchars($viewClient['company_name'] ?? 'Не указано'); ?></p>
                    </div>
                    <div>
                        <p><strong>Контактное лицо:</strong></p>
                        <p><?php echo htmlspecialchars($viewClient['contact_person'] ?? 'Не указано'); ?></p>
                    </div>
                    <div>
                        <p><strong>УНП/ИНН:</strong></p>
                        <p><?php echo htmlspecialchars($viewClient['inn'] ?? 'Не указано'); ?></p>
                    </div>
                    <div>
                        <p><strong>Телефон:</strong></p>
                        <p><?php echo htmlspecialchars($viewClient['phone'] ?? 'Не указано'); ?></p>
                    </div>
                    <div>
                        <p><strong>Email:</strong></p>
                        <p><?php echo htmlspecialchars($viewClient['email'] ?? 'Не указано'); ?></p>
                    </div>
                    <div>
                        <p><strong>Дата создания:</strong></p>
                        <p><?php echo date('d.m.Y H:i', strtotime($viewClient['created_at'])); ?></p>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <p><strong>Адрес:</strong></p>
                    <p><?php echo htmlspecialchars($viewClient['address'] ?? 'Не указано'); ?></p>
                </div>
                <?php if (hasRole(['admin', 'manager'])): ?>
                <div style="margin-top: 30px; text-align: right;">
                    <a href="?edit=<?php echo $viewClient['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Редактировать
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Open modal function
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        // Close modal function
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking on X button
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal.id === 'editClientModal' || modal.id === 'viewClientModal') {
                    window.location.href = 'index.php';
                } else {
                    modal.style.display = 'none';
                }
            });
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                if (event.target.id === 'editClientModal' || event.target.id === 'viewClientModal') {
                    window.location.href = 'index.php';
                } else {
                    event.target.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>

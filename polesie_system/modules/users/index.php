<?php
/**
 * Модуль "Пользователи" для OAO "Polesieelectromash" ERP System
 * Управление пользователями системы: просмотр, добавление, редактирование, удаление
 */

require_once '../../includes/config.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('../../index.php');
}

// Проверка прав доступа (только admin и director)
if (!hasRole(['admin', 'director'])) {
    $_SESSION['error_message'] = 'У вас нет доступа к этому модулю';
    redirect('../../dashboard.php');
}

$pdo = getDBConnection();
$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
// Получаем ID пользователя из GET или POST (для обработки форм)
$userId = $_GET['id'] ?? $_POST['id'] ?? null;

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    try {
        if ($postAction === 'create' || $postAction === 'update') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $roleId = (int)($_POST['role_id'] ?? 0);
            $department = trim($_POST['department'] ?? '');
            $position = trim($_POST['position'] ?? '');
            
            // Валидация
            if (empty($username) || empty($fullName) || empty($email) || $roleId === 0) {
                throw new Exception('Заполните все обязательные поля');
            }
            
            if ($postAction === 'create' && empty($password)) {
                throw new Exception('Укажите пароль для нового пользователя');
            }
            
            if ($postAction === 'create') {
                // Проверка уникальности username и email
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
                $checkStmt->execute([':username' => $username, ':email' => $email]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Пользователь с таким логином или email уже существует');
                }
                
                // Создание пользователя
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, full_name, email, phone, role_id, department, position)
                    VALUES (:username, :password, :full_name, :email, :phone, :role_id, :department, :position)
                ");
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $password, // Plain text как в ТЗ
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':role_id' => $roleId,
                    ':department' => $department,
                    ':position' => $position
                ]);
                
                $newUserId = $pdo->lastInsertId();
                logActivity($pdo, $_SESSION['user_id'], 'Создание пользователя', 'users', $newUserId);
                $message = 'Пользователь успешно создан';
                
            } elseif ($postAction === 'update' && $userId) {
                // Проверка уникальности username (исключая текущего пользователя)
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
                $checkStmt->execute([':username' => $username, ':id' => $userId]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Пользователь с таким логином уже существует');
                }
                
                // Проверка уникальности email (исключая текущего пользователя)
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $checkStmt->execute([':email' => $email, ':id' => $userId]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Пользователь с таким email уже существует');
                }
                
                // Обновление пользователя (логин можно изменить)
                $updateData = [
                    ':username' => $username,
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':role_id' => $roleId,
                    ':department' => $department,
                    ':position' => $position,
                    ':id' => $userId
                ];
                
                if (!empty($password)) {
                    $updateData[':password'] = $password;
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = :username, full_name = :full_name, email = :email, phone = :phone, 
                            role_id = :role_id, department = :department, position = :position, password = :password
                        WHERE id = :id
                    ");
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = :username, full_name = :full_name, email = :email, phone = :phone, 
                            role_id = :role_id, department = :department, position = :position
                        WHERE id = :id
                    ");
                }
                
                $stmt->execute($updateData);
                logActivity($pdo, $_SESSION['user_id'], 'Редактирование пользователя', 'users', $userId);
                $message = 'Пользователь успешно обновлен';
            }
            
            $action = 'list';
            
        } elseif ($postAction === 'delete' && $userId) {
            // Полное удаление пользователя из БД
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            logActivity($pdo, $_SESSION['user_id'], 'Удаление пользователя', 'users', $userId);
            $message = 'Пользователь успешно удален';
            $action = 'list';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Получение списка пользователей с фильтром
$searchQuery = $_GET['search'] ?? '';
$filterParams = [];

$sql = "SELECT u.*, r.role_name, r.role_description
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id";

if (!empty($searchQuery)) {
    $sql .= " WHERE (u.username LIKE :search1 OR u.full_name LIKE :search2 OR u.email LIKE :search3)";
    $filterParams['search1'] = '%' . $searchQuery . '%';
    $filterParams['search2'] = '%' . $searchQuery . '%';
    $filterParams['search3'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY u.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    if (!empty($filterParams)) {
        $stmt->execute($filterParams);
    } else {
        $stmt->execute();
    }
    $users = $stmt->fetchAll();
    
    // Получение списка ролей
    $stmt = $pdo->query("SELECT id, role_name, role_description FROM roles ORDER BY id");
    $roles = $stmt->fetchAll();
    
    // Получение данных пользователя для редактирования
    $editUser = null;
    if ($action === 'edit' && $userId) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $editUser = $stmt->fetch();
        if (!$editUser) {
            $error = 'Пользователь не найден';
            $action = 'list';
        }
    }
    
} catch (PDOException $e) {
    $error = 'Ошибка получения данных: ' . $e->getMessage();
    error_log($e->getMessage());
}

// Получение информации о текущем пользователе
$userFullName = $_SESSION['full_name'];
$initials = strtoupper(substr($userFullName, 0, 1));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-row.single {
            grid-template-columns: 1fr;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-icon {
            padding: 6px 10px;
            font-size: 14px;
        }
        .user-email {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-fullname {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
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
            
            <nav class="sidebar-menu">
                <a href="../../dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Главная</span>
                </a>
                <a href="" class="menu-item active">
                    <i class="fas fa-users"></i>
                    <span>Пользователи</span>
                </a>
                <div class="menu-category" style="margin-top: 20px;">Система</div>
                <a href="../../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выход</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <div class="header-title">
                        <h1>Пользователи системы</h1>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo $initials; ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($userFullName); ?></span>
                            <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Users List -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Список пользователей</h2>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <form method="GET" action="" style="display: flex; gap: 10px;">
                                <input type="text" name="search" placeholder="Поиск по логину, ФИО, email..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                       style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Найти
                                </button>
                                <?php if (!empty($searchQuery)): ?>
                                    <a href="?action=list" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Сбросить
                                    </a>
                                <?php endif; ?>
                            </form>
                            <?php if (hasRole(['admin'])): ?>
                                <button class="btn btn-primary" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Добавить пользователя
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Логин</th>
                                    <th>ФИО</th>
                                    <th>Email</th>
                                    <th>Роль</th>
                                    <th>Отдел/Должность</th>
                                    <th style="width: 120px;">Последний вход</th>
                                    <th style="width: 100px;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Пользователи не найдены</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                            <td class="user-fullname"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role_name'] ?? 'Не назначена'); ?></td>
                                            <td>
                                                <div style="font-size: 11px;">
                                                    <div><?php echo htmlspecialchars($user['department'] ?? '-'); ?></div>
                                                    <div style="color: #666;"><?php echo htmlspecialchars($user['position'] ?? ''); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($user['last_login']): ?>
                                                    <div style="font-size: 11px;"><?php echo date('d.m.Y', strtotime($user['last_login'])); ?></div>
                                                    <div style="font-size: 10px; color: #666;"><?php echo date('H:i', strtotime($user['last_login'])); ?></div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if (hasRole(['admin'])): ?>
                                                        <button class="btn btn-sm btn-primary btn-icon" 
                                                                onclick="openEditModal(<?php echo $user['id']; ?>)" 
                                                                title="Редактировать">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <button class="btn btn-sm btn-danger btn-icon" 
                                                                    onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                                    title="Удалить">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
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
    
    <!-- Modal for Create/Edit User -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Добавить пользователя</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="userId" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Логин *</label>
                        <input type="text" id="username" name="username" required>
                        <small style="color: #666; font-size: 11px;" id="usernameHint">Логин можно изменить</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Пароль <span id="passwordRequired">*</span></label>
                        <input type="password" id="password" name="password">
                        <small style="color: #666; font-size: 11px;" id="passwordHint">Оставьте пустым, чтобы не менять пароль</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName"><i class="fas fa-id-card"></i> ФИО *</label>
                        <input type="text" id="fullName" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Телефон</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="roleId"><i class="fas fa-user-tag"></i> Роль *</label>
                        <select id="roleId" name="role_id" required>
                            <option value="">Выберите роль</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department"><i class="fas fa-building"></i> Отдел</label>
                        <input type="text" id="department" name="department">
                    </div>
                    
                    <div class="form-group">
                        <label for="position"><i class="fas fa-briefcase"></i> Должность</label>
                        <input type="text" id="position" name="position">
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" action="">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteUserId">
    </form>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Глобальные функции для работы с модальными окнами
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Добавить пользователя';
            document.getElementById('formAction').value = 'create';
            document.getElementById('userId').value = '';
            document.getElementById('username').value = '';
            document.getElementById('username').disabled = false;
            document.getElementById('usernameHint').textContent = 'Введите уникальный логин';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('passwordHint').style.display = 'none';
            document.getElementById('fullName').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('roleId').value = '';
            document.getElementById('department').value = '';
            document.getElementById('position').value = '';
            document.getElementById('userModal').classList.add('active');
        }
        
        function openEditModal(userId) {
            console.log('Opening edit modal for user ID:', userId);
            // Загружаем данные пользователя через AJAX
            fetch('get_user.php?id=' + userId)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success) {
                        const user = data.user;
                        document.getElementById('modalTitle').textContent = 'Редактировать пользователя';
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('userId').value = user.id;
                        document.getElementById('username').value = user.username;
                        document.getElementById('username').disabled = false;
                        document.getElementById('usernameHint').textContent = 'Логин можно изменить';
                        document.getElementById('password').value = '';
                        document.getElementById('password').required = false;
                        document.getElementById('passwordRequired').style.display = 'none';
                        document.getElementById('passwordHint').style.display = 'block';
                        document.getElementById('fullName').value = user.full_name;
                        document.getElementById('email').value = user.email;
                        document.getElementById('phone').value = user.phone || '';
                        document.getElementById('roleId').value = user.role_id;
                        document.getElementById('department').value = user.department || '';
                        document.getElementById('position').value = user.position || '';
                        document.getElementById('userModal').classList.add('active');
                    } else {
                        alert('Ошибка загрузки данных пользователя: ' + (data.message || ''));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка загрузки данных пользователя');
                });
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
        
        function confirmDelete(userId, username) {
            console.log('Confirm delete for user ID:', userId, 'username:', username);
            if (confirm('Вы уверены, что хотите удалить пользователя "' + username + '"? Это действие нельзя отменить.')) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>

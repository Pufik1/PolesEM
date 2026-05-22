<?php
/**
 * Модуль "Кадры" - Управление персоналом организации
 * Функционал: просмотр, добавление, редактирование, удаление сотрудников, поиск
 */

require_once '../../includes/config.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('../../index.php');
}

// Проверка прав доступа (admin, manager, hr_manager, director)
if (!hasRole(['admin', 'manager', 'hr_manager', 'director'])) {
    $_SESSION['error_message'] = 'У вас нет доступа к этому модулю';
    redirect('../../dashboard.php');
}

$pdo = getDBConnection();
$message = '';
$error = '';

// Определяем действие
$action = $_GET['action'] ?? 'list';
$employeeId = $_GET['id'] ?? $_POST['id'] ?? null;

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    try {
        // Создание или обновление сотрудника
        if ($postAction === 'create' || $postAction === 'update') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $hireDate = $_POST['hire_date'] ?? null;
            $salary = !empty($_POST['salary']) ? (float)$_POST['salary'] : null;
            $status = $_POST['status'] ?? 'active';
            $notes = trim($_POST['notes'] ?? '');
            
            // Валидация обязательных полей
            if (empty($fullName) || empty($position) || empty($department)) {
                throw new Exception('Заполните все обязательные поля (ФИО, должность, отдел)');
            }
            
            if ($postAction === 'create') {
                // Проверка уникальности email (если указан)
                if (!empty($email)) {
                    $checkStmt = $pdo->prepare("SELECT id FROM employees WHERE email = :email");
                    $checkStmt->execute([':email' => $email]);
                    if ($checkStmt->fetch()) {
                        throw new Exception('Сотрудник с таким email уже существует');
                    }
                }
                
                // Вставка нового сотрудника
                $stmt = $pdo->prepare("
                    INSERT INTO employees (full_name, email, phone, position, department, hire_date, salary, status, notes)
                    VALUES (:full_name, :email, :phone, :position, :department, :hire_date, :salary, :status, :notes)
                ");
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':email' => $email ?: null,
                    ':phone' => $phone ?: null,
                    ':position' => $position,
                    ':department' => $department,
                    ':hire_date' => $hireDate ?: null,
                    ':salary' => $salary,
                    ':status' => $status,
                    ':notes' => $notes
                ]);
                
                $newEmployeeId = $pdo->lastInsertId();
                logActivity($pdo, $_SESSION['user_id'], 'Создание сотрудника', 'employees', $newEmployeeId);
                $message = 'Сотрудник успешно создан';
                
            } elseif ($postAction === 'update' && $employeeId) {
                // Проверка уникальности email (исключая текущего)
                if (!empty($email)) {
                    $checkStmt = $pdo->prepare("SELECT id FROM employees WHERE email = :email AND id != :id");
                    $checkStmt->execute([':email' => $email, ':id' => $employeeId]);
                    if ($checkStmt->fetch()) {
                        throw new Exception('Сотрудник с таким email уже существует');
                    }
                }
                
                // Обновление данных
                $stmt = $pdo->prepare("
                    UPDATE employees 
                    SET full_name = :full_name, email = :email, phone = :phone, 
                        position = :position, department = :department, hire_date = :hire_date, 
                        salary = :salary, status = :status, notes = :notes
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':email' => $email ?: null,
                    ':phone' => $phone ?: null,
                    ':position' => $position,
                    ':department' => $department,
                    ':hire_date' => $hireDate ?: null,
                    ':salary' => $salary,
                    ':status' => $status,
                    ':notes' => $notes,
                    ':id' => $employeeId
                ]);
                
                logActivity($pdo, $_SESSION['user_id'], 'Редактирование сотрудника', 'employees', $employeeId);
                $message = 'Сотрудник успешно обновлен';
            }
            
            $action = 'list';
            
        // Удаление сотрудника
        } elseif ($postAction === 'delete' && $employeeId) {
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
            $stmt->execute([':id' => $employeeId]);
            logActivity($pdo, $_SESSION['user_id'], 'Удаление сотрудника', 'employees', $employeeId);
            $message = 'Сотрудник успешно удален';
            $action = 'list';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Поиск сотрудников
$searchQuery = $_GET['search'] ?? '';
$filterDepartment = $_GET['department'] ?? '';
$filterParams = [];

$sql = "SELECT * FROM employees";
$whereConditions = [];

if (!empty($searchQuery)) {
    $whereConditions[] = "(full_name LIKE :search1 OR email LIKE :search2 OR phone LIKE :search3 OR position LIKE :search4)";
    $filterParams['search1'] = '%' . $searchQuery . '%';
    $filterParams['search2'] = '%' . $searchQuery . '%';
    $filterParams['search3'] = '%' . $searchQuery . '%';
    $filterParams['search4'] = '%' . $searchQuery . '%';
}

if (!empty($filterDepartment)) {
    $whereConditions[] = "department = :department";
    $filterParams['department'] = $filterDepartment;
}

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(' AND ', $whereConditions);
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($filterParams);
    $employees = $stmt->fetchAll();
    
    // Получаем список отделов для фильтра
    $stmt = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Получаем данные сотрудника для редактирования
    $editEmployee = null;
    if ($action === 'edit' && $employeeId) {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
        $stmt->execute([':id' => $employeeId]);
        $editEmployee = $stmt->fetch();
        if (!$editEmployee) {
            $error = 'Сотрудник не найден';
            $action = 'list';
        }
    }
    
} catch (PDOException $e) {
    $error = 'Ошибка получения данных: ' . $e->getMessage();
    error_log($e->getMessage());
}

// Данные текущего пользователя
$userFullName = $_SESSION['full_name'];
$initials = strtoupper(substr($userFullName, 0, 1));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кадры - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .employees-header {
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
        .status-vacation {
            background-color: #fff3cd;
            color: #856404;
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
            max-width: 700px;
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
        .employee-email {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .employee-fullname {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stat-card.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .filters-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
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
                        <h1>Отдел кадров</h1>
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
                
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($employees); ?></div>
                        <div class="stat-label">Всего сотрудников</div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-number"><?php echo count(array_filter($employees, fn($e) => $e['status'] === 'active')); ?></div>
                        <div class="stat-label">Активных</div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-number"><?php echo count(array_filter($employees, fn($e) => $e['status'] === 'vacation')); ?></div>
                        <div class="stat-label">В отпуске</div>
                    </div>
                    <div class="stat-card blue">
                        <div class="stat-number"><?php echo count($departments); ?></div>
                        <div class="stat-label">Отделов</div>
                    </div>
                </div>
                
                <!-- Employees List -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Список сотрудников</h2>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <form method="GET" action="" style="display: flex; gap: 10px;">
                                <input type="text" name="search" placeholder="Поиск по ФИО, должности, email..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                       style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
                                <select name="department" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="">Все отделы</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $filterDepartment === $dept ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Найти
                                </button>
                                <?php if (!empty($searchQuery) || !empty($filterDepartment)): ?>
                                    <a href="?action=list" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Сбросить
                                    </a>
                                <?php endif; ?>
                            </form>
                            <?php if (hasRole(['admin', 'manager', 'hr_manager'])): ?>
                                <button class="btn btn-primary" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Добавить сотрудника
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>ФИО</th>
                                    <th>Должность</th>
                                    <th>Отдел</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                    <th>Дата приёма</th>
                                    <th>Статус</th>
                                    <th style="width: 100px;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Сотрудники не найдены</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td><?php echo $employee['id']; ?></td>
                                            <td class="employee-fullname"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                            <td class="employee-email"><?php echo htmlspecialchars($employee['email'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($employee['phone'] ?? '-'); ?></td>
                                            <td><?php echo $employee['hire_date'] ? date('d.m.Y', strtotime($employee['hire_date'])) : '-'; ?></td>
                                            <td>
                                                <?php
                                                $statusClass = 'status-active';
                                                $statusText = 'Активен';
                                                if ($employee['status'] === 'inactive') {
                                                    $statusClass = 'status-inactive';
                                                    $statusText = 'Не активен';
                                                } elseif ($employee['status'] === 'vacation') {
                                                    $statusClass = 'status-vacation';
                                                    $statusText = 'Отпуск';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if (hasRole(['admin', 'manager', 'hr_manager'])): ?>
                                                        <button class="btn btn-icon btn-primary" onclick="openEditModal(<?php echo $employee['id']; ?>)" title="Редактировать">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-icon btn-danger" onclick="confirmDelete(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['full_name']); ?>')" title="Удалить">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
    
    <!-- Modal for Create/Edit Employee -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Добавить сотрудника</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="employeeId">
                
                <div class="form-row">
                    <div>
                        <label for="full_name">ФИО *</label>
                        <input type="text" id="full_name" name="full_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="position">Должность *</label>
                        <input type="text" id="position" name="position" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div>
                        <label for="department">Отдел *</label>
                        <input type="text" id="department" name="department" required list="departmentList" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <datalist id="departmentList">
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label for="hire_date">Дата приёма</label>
                        <input type="date" id="hire_date" name="hire_date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div>
                        <label for="salary">Зарплата</label>
                        <input type="number" id="salary" name="salary" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="status">Статус</label>
                        <select id="status" name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="active">Активен</option>
                            <option value="inactive">Не активен</option>
                            <option value="vacation">Отпуск</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row single">
                    <div>
                        <label for="notes">Примечание</label>
                        <textarea id="notes" name="notes" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Добавить сотрудника';
            document.getElementById('formAction').value = 'create';
            document.getElementById('employeeId').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('position').value = '';
            document.getElementById('department').value = '';
            document.getElementById('hire_date').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('salary').value = '';
            document.getElementById('status').value = 'active';
            document.getElementById('notes').value = '';
            document.getElementById('employeeModal').classList.add('active');
        }
        
        function openEditModal(employeeId) {
            fetch('?action=get_employee&id=' + employeeId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const emp = data.employee;
                        document.getElementById('modalTitle').textContent = 'Редактировать сотрудника';
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('employeeId').value = emp.id;
                        document.getElementById('full_name').value = emp.full_name;
                        document.getElementById('position').value = emp.position;
                        document.getElementById('department').value = emp.department;
                        document.getElementById('hire_date').value = emp.hire_date || '';
                        document.getElementById('email').value = emp.email || '';
                        document.getElementById('phone').value = emp.phone || '';
                        document.getElementById('salary').value = emp.salary || '';
                        document.getElementById('status').value = emp.status;
                        document.getElementById('notes').value = emp.notes || '';
                        document.getElementById('employeeModal').classList.add('active');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function closeModal() {
            document.getElementById('employeeModal').classList.remove('active');
        }
        
        function confirmDelete(employeeId, employeeName) {
            if (confirm('Вы уверены, что хотите удалить сотрудника "' + employeeName + '"?')) {
                document.getElementById('deleteId').value = employeeId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('employeeModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

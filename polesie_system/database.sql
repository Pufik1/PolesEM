-- Database: polesie_electromash
-- Creating database structure for OAO "Polesieelectromash" ERP System

CREATE DATABASE IF NOT EXISTS polesie_electromash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE polesie_electromash;

-- Roles table (7 roles as requested)
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

-- Product categories
CREATE TABLE product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    parent_id INT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    specifications JSON,
    base_price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'BYN',
    weight DECIMAL(10,3) DEFAULT 0 COMMENT 'Вес единицы продукции в кг',
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
);

-- Clients/Customers table
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_code VARCHAR(50) NOT NULL UNIQUE,
    company_name VARCHAR(200),
    contact_person VARCHAR(100),
    inn VARCHAR(20),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(200),
    payment_terms TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    user_id INT NOT NULL,
    order_date DATE NOT NULL,
    delivery_date DATE,
    status ENUM('new', 'processing', 'production', 'ready', 'shipped', 'completed', 'cancelled') DEFAULT 'new',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    final_amount DECIMAL(12,2) NOT NULL,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    paid_amount DECIMAL(12,2) DEFAULT 0,
    paid_date DATE,
    delivery_address TEXT,
    notes TEXT,
    contract_number VARCHAR(50),
    contract_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Order documents table (Договор, Счет-фактура, ТН, ТТН)
CREATE TABLE order_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    document_type ENUM('contract', 'invoice', 'tn', 'ttn') NOT NULL COMMENT 'contract=Договор, invoice=Счет-фактура, tn=Товарная накладная, ttn=Товарно-транспортная накладная',
    document_number VARCHAR(50) NOT NULL,
    document_date DATE NOT NULL,
    file_path VARCHAR(500),
    status ENUM('draft', 'signed', 'archived') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Invoice details (Счет-фактура)
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE,
    total_amount DECIMAL(12,2) NOT NULL,
    vat_amount DECIMAL(12,2) DEFAULT 0,
    total_with_vat DECIMAL(12,2) NOT NULL,
    payment_status ENUM('unpaid', 'partial', 'paid', 'overdue') DEFAULT 'unpaid',
    paid_amount DECIMAL(12,2) DEFAULT 0,
    paid_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Invoice items
CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL,
    vat_rate DECIMAL(5,2) DEFAULT 20,
    vat_amount DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Delivery Note / Товарная накладная
CREATE TABLE delivery_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tn_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INT NOT NULL,
    tn_date DATE NOT NULL,
    warehouse_from VARCHAR(200),
    warehouse_to VARCHAR(200),
    shipper_name VARCHAR(200),
    consignee_name VARCHAR(200),
    shipper_inn VARCHAR(20),
    consignee_inn VARCHAR(20),
    shipper_address TEXT,
    consignee_address TEXT,
    total_items INT DEFAULT 0,
    total_weight DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Transport Waybill / Товарно-транспортная накладная (ТТН)
CREATE TABLE transport_waybills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ttn_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INT NOT NULL,
    delivery_note_id INT,
    ttn_date DATE NOT NULL,
    vehicle_number VARCHAR(20),
    driver_name VARCHAR(100),
    driver_license VARCHAR(20),
    carrier_name VARCHAR(200),
    carrier_inn VARCHAR(20),
    route_from TEXT,
    route_to TEXT,
    distance_km DECIMAL(8,2),
    freight_cost DECIMAL(10,2) DEFAULT 0,
    loading_point VARCHAR(200),
    unloading_point VARCHAR(200),
    shipper_name VARCHAR(200),
    shipper_inn VARCHAR(20),
    shipper_address TEXT,
    consignee_name VARCHAR(200),
    consignee_inn VARCHAR(20),
    consignee_address TEXT,
    loading_time TIME,
    unloading_time TIME,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Delivery note items
CREATE TABLE delivery_note_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_note_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(20) DEFAULT 'шт',
    weight_per_unit DECIMAL(8,3) DEFAULT 0,
    total_weight DECIMAL(10,3) DEFAULT 0,
    price DECIMAL(10,2),
    total_price DECIMAL(12,2),
    FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Work centers / production areas
CREATE TABLE work_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    center_code VARCHAR(50) NOT NULL UNIQUE,
    center_name VARCHAR(200) NOT NULL,
    center_type ENUM('assembly', 'machining', 'casting', 'painting', 'warehouse', 'quality') DEFAULT 'assembly',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Technological operations
CREATE TABLE technological_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_code VARCHAR(50) NOT NULL UNIQUE,
    operation_name VARCHAR(200) NOT NULL,
    product_id INT,
    work_center_id INT,
    sequence_order INT DEFAULT 1,
    standard_time_minutes DECIMAL(10,2) DEFAULT 0 COMMENT 'Нормативное время в минутах',
    description TEXT,
    required_tools TEXT COMMENT 'Необходимые инструменты',
    quality_requirements TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (work_center_id) REFERENCES work_centers(id) ON DELETE SET NULL
);

-- Production orders (План выпуска продукции)
CREATE TABLE production_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_number VARCHAR(50) NOT NULL UNIQUE,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    planned_start_date DATE,
    planned_end_date DATE,
    actual_start_date DATE,
    actual_end_date DATE,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled', 'on_hold') DEFAULT 'planned',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    order_source ENUM('customer_order', 'stock_replenishment', 'forecast') DEFAULT 'stock_replenishment',
    source_order_id INT COMMENT 'ID заказа клиента если это заказное производство',
    responsible_user_id INT,
    work_center_id INT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (work_center_id) REFERENCES work_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Production order operations (маршрутный лист)
CREATE TABLE production_order_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    operation_id INT NOT NULL,
    sequence_order INT DEFAULT 1,
    planned_start_datetime DATETIME,
    planned_end_datetime DATETIME,
    actual_start_datetime DATETIME,
    actual_end_datetime DATETIME,
    status ENUM('pending', 'in_progress', 'completed', 'skipped') DEFAULT 'pending',
    worker_id INT,
    work_center_id INT,
    quantity_good INT DEFAULT 0 COMMENT 'Годные изделия',
    quantity_defect INT DEFAULT 0 COMMENT 'Брак',
    defect_reason TEXT,
    notes TEXT,
    completed_at TIMESTAMP NULL,
    completed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (operation_id) REFERENCES technological_operations(id) ON DELETE RESTRICT,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (work_center_id) REFERENCES work_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Materials consumption for production (Списание материалов)
CREATE TABLE production_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    material_id INT NOT NULL,
    planned_quantity DECIMAL(10,3) NOT NULL,
    actual_quantity DECIMAL(10,3) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'шт',
    warehouse_id INT,
    issued_date DATE,
    issued_by INT,
    status ENUM('planned', 'issued', 'returned', 'written_off') DEFAULT 'planned',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Quality control / ОТК
CREATE TABLE IF NOT EXISTS quality_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    route_sheet_id INT,
    inspection_date DATETIME NOT NULL,
    inspector_id INT NOT NULL,
    inspected_quantity INT NOT NULL,
    passed_quantity INT NOT NULL,
    rejected_quantity INT NOT NULL,
    defect_types JSON COMMENT 'Типы дефектов',
    inspection_result ENUM('passed', 'failed', 'conditional') DEFAULT 'passed',
    certificate_number VARCHAR(50),
    next_operation_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (route_sheet_id) REFERENCES production_order_operations(id) ON DELETE SET NULL,
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (next_operation_id) REFERENCES technological_operations(id) ON DELETE SET NULL
);

-- Shift tasks for workers (Сменные задания)
CREATE TABLE shift_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_number VARCHAR(50) NOT NULL UNIQUE,
    production_order_id INT NOT NULL,
    worker_id INT NOT NULL,
    shift_date DATE NOT NULL,
    shift_type ENUM('day', 'night', 'mixed') DEFAULT 'day',
    operation_id INT,
    planned_quantity INT NOT NULL,
    actual_quantity INT DEFAULT 0,
    status ENUM('assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'assigned',
    started_at DATETIME,
    completed_at DATETIME,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (operation_id) REFERENCES technological_operations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Production completion acts (Акт сдачи-приемки)
CREATE TABLE production_completion_acts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    act_number VARCHAR(50) NOT NULL UNIQUE,
    production_order_id INT NOT NULL,
    act_date DATE NOT NULL,
    total_quantity INT NOT NULL,
    good_quantity INT NOT NULL,
    defect_quantity INT NOT NULL,
    warehouse_received_by INT,
    quality_approved_by INT,
    status ENUM('draft', 'signed', 'archived') DEFAULT 'draft',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_received_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (quality_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Defect types reference
CREATE TABLE IF NOT EXISTS defect_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    defect_code VARCHAR(50) NOT NULL UNIQUE,
    defect_name VARCHAR(200) NOT NULL,
    category ENUM('critical', 'major', 'minor') DEFAULT 'minor',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Defect log - журнал дефектов по результатам ОТК
CREATE TABLE IF NOT EXISTS defect_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quality_control_id INT NOT NULL,
    defect_type_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_control_id) REFERENCES quality_control(id) ON DELETE CASCADE,
    FOREIGN KEY (defect_type_id) REFERENCES defect_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Employees table (extended HR data) - REMOVED
/*
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    hire_date DATE,
    salary DECIMAL(10,2),
    status ENUM('active', 'inactive', 'vacation') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_status (status),
    INDEX idx_full_name (full_name)
);
*/

-- Original employees table structure (for reference - linked to users)
/*
CREATE TABLE employees_old (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    employee_code VARCHAR(50) NOT NULL UNIQUE,
    hire_date DATE NOT NULL,
    termination_date DATE,
    salary DECIMAL(10,2),
    department VARCHAR(100),
    position VARCHAR(100),
    supervisor_id INT,
    employment_status ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES employees(id) ON DELETE SET NULL
);
*/

-- Activity log table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Warehouse operations table (Операции склада: приход, расход, перемещение, списание)
CREATE TABLE warehouse_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_type ENUM('income', 'outcome', 'transfer', 'write_off') NOT NULL COMMENT 'income=Приход, outcome=Расход, transfer=Перемещение, write_off=Списание',
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    warehouse_from INT,
    warehouse_to INT,
    user_id INT NOT NULL,
    document_number VARCHAR(50),
    notes TEXT,
    operation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_from) REFERENCES work_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_to) REFERENCES work_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_operation_type (operation_type),
    INDEX idx_product_id (product_id),
    INDEX idx_operation_date (operation_date)
);

-- Insert default roles
INSERT INTO roles (role_name, role_description, permissions) VALUES
('admin', 'Системный администратор - полный доступ ко всем функциям системы', '{"all": true}'),
('director', 'Директор - просмотр всех отчетов и аналитики, управление сотрудниками', '{"dashboard": true, "reports": true, "analytics": true}'),
('manager', 'Менеджер по продажам - работа с клиентами, заказами, коммерческими предложениями', '{"clients": true, "orders": true, "products": true, "sales": true}'),
('production_master', 'Мастер производства - управление производственными заданиями, контроль выпуска продукции', '{"production": true, "warehouse": true, "quality": true}'),
('warehouse_keeper', 'Кладовщик - учет ТМЦ, приход/расход материалов и готовой продукции', '{"warehouse": true, "inventory": true}'),
('accountant', 'Бухгалтер - финансовая отчетность, счета, акты, накладные', '{"finance": true, "reports": true, "documents": true}'),
('hr_manager', 'HR-менеджер - управление доступом к кадрам', '{}');

-- Insert default admin user (password: admin123)
-- Insert users with different roles for demonstration
INSERT INTO users (username, password, full_name, email, role_id, department, position) VALUES
('admin', 'admin123', 'Администратор Системы', 'admin@polesieelectromash.by', 1, 'IT отдел', 'Системный администратор'),
('petrov', 'petrov123', 'Петров Иван Сергеевич', 'petrov@polesieelectromash.by', 2, 'Производство', 'Начальник производства'),
('sidorov', 'sidorov123', 'Сидоров Петр Александрович', 'sidorov@polesieelectromash.by', 3, 'Плановый отдел', 'Плановик'),
('ivanov', 'ivanov123', 'Иванов Дмитрий Николаевич', 'ivanov@polesieelectromash.by', 5, 'ОТК', 'Инспектор ОТК'),
('smirnova', 'smirnova123', 'Смирнова Елена Владимировна', 'smirnova@polesieelectromash.by', 5, 'ОТК', 'Старший инспектор ОТК'),
('kozlov', 'kozlov123', 'Козлов Андрей Михайлович', 'kozlov@polesieelectromash.by', 4, 'Склад', 'Заведующий складом'),
('novikov', 'novikov123', 'Новиков Сергей Павлович', 'novikov@polesieelectromash.by', 6, 'Производство', 'Мастер участка №1'),
('morozov', 'morozov123', 'Морозов Владимир Иванович', 'morozov@polesieelectromash.by', 7, 'Производство', 'Рабочий-станочник 5 разряда');

-- Insert product categories based on company info
INSERT INTO product_categories (category_name, description) VALUES
('Сырье и материалы', 'Алюминий вторичный, чугун литейный'),
('Чугунное литье', 'Колосниковые решетки, дождеприемники, люки, плиты'),
('Электродвигатели общепромышленные АИР', 'Асинхронные трехфазные двигатели общего назначения'),
('Электродвигатели с повышенным скольжением AIRS', 'Для механизмов с тяжелым пуском'),
('Электродвигатели однофазные AIRE', 'С конденсатором, напряжение 220В'),
('Электродвигатели для птицефабрик AIRP', 'Специсполнение для агрессивных сред'),
('Электродвигатели двухскоростные 2AIR', 'Многоскоростные двигатели'),
('Электродвигатели железнодорожные AIRCH', 'Виброустойчивое исполнение'),
('Электродвигатели встроенные AIRV', 'Для встройки в агрегаты'),
('Электродвигатели специсполнения', 'Спецвалы, фланцы, уплотнения');

-- Сырье и материалы
INSERT INTO products (product_code, product_name, category_id, description, specifications, base_price, weight, stock_quantity) VALUES
('MAT-AL-AB87', 'Алюминий вторичный в чушках АВ87', 1, 'Раскисление стали, производство ферросплавов, алюминотермические процессы', '{"grade": "АВ87", "gost": "ГОСТ 295-98", "form": "чушка", "storage": "Сухое помещение, защита от влаги"}', 85.00, 20.0, 500),
('MAT-AL-AB87F', 'Алюминий вторичный гранулированный АВ87Ф', 1, 'Алюминотермическое восстановление редкоземельных металлов, производство лигатур', '{"grade": "АВ87Ф", "gost": "ГОСТ 295-98", "form": "гранула", "storage": "Герметичная тара, влажность ≤60%"}', 120.00, 15.0, 300),
('MAT-CI-L4', 'Чугун литейный передельный Л4', 1, 'Литьё корпусов, решёток, промышленных изделий', '{"grade": "Л4", "gost": "ГОСТ 4832-95", "storage": "Открытая площадка под навесом"}', 75.00, 20.0, 600),
('MAT-CI-L5', 'Чугун литейный передельный Л5', 1, 'Литьё корпусов, решёток, промышленных изделий', '{"grade": "Л5", "gost": "ГОСТ 4832-95", "storage": "Аналогично Л4"}', 75.00, 20.0, 600);

-- Чугунное литье
INSERT INTO products (product_code, product_name, category_id, description, specifications, base_price, weight, stock_quantity) VALUES
('CI-GR-RU2', 'Колосниковая решетка РУ-2', 2, 'Колосниковая решетка из серого чугуна', '{"gost": "СТБ 726-2006", "size_mm": "200x300", "material": "серый чугун", "weight_kg": 3.5}', 25.00, 3.5, 100),
('CI-GR-RU3', 'Колосниковая решетка РУ-3', 2, 'Колосниковая решетка из серого чугуна', '{"gost": "СТБ 726-2006", "size_mm": "200x350", "material": "серый чугун", "weight_kg": 5.5}', 35.00, 5.5, 80),
('CI-GR-RU4', 'Колосниковая решетка РУ-4', 2, 'Колосниковая решетка из серого чугуна', '{"gost": "СТБ 726-2006", "size_mm": "400x200", "material": "серый чугун", "weight_kg": 6.0}', 40.00, 6.0, 75),
('CI-GR-RD3', 'Колосниковая решетка РД-3', 2, 'Колосниковая решетка из серого чугуна', '{"gost": "СТБ 726-2006", "size_mm": "170x240", "material": "серый чугун", "weight_kg": 2.2}', 20.00, 2.2, 120),
('CI-GR-RD6K', 'Колосниковая решетка РД-6К', 2, 'Колосниковая решетка из серого чугуна', '{"gost": "СТБ 726-2006", "size_mm": "250x380", "material": "серый чугун", "weight_kg": 5.2}', 38.00, 5.2, 90),
('CI-GR-57L', 'Колосниковая решетка 57Л', 2, 'Колосниковая решетка из серого чугуна', '{"gost": "СТБ 726-2006", "size_mm": "240x415", "material": "серый чугун", "weight_kg": 6.5}', 42.00, 6.5, 70),
('CI-GR-001', 'Решетка 001', 2, 'Решетка из серого чугуна', '{"gost": "-", "size_mm": "496x300x34", "material": "серый чугун", "weight_kg": 14.2}', 85.00, 14.2, 50),
('CI-GR-ANIM', 'Решетка животноводческая', 2, 'Решетка для животноводческих помещений', '{"gost": "-", "size_mm": "420x310x60", "material": "серый чугун", "weight_kg": 20.0}', 120.00, 20.0, 40),
('CI-DR-GRILL', 'Решетка дождеприемника', 2, 'Решетка для дождеприемных колодцев', '{"gost": "СТБ 3634-99", "size_mm": "400x800x40", "material": "серый чугун"}', 150.00, 25.0, 30),
('CI-DR-ASSY', 'Дождеприемник в сборе', 2, 'Дождеприемный колодец в сборе', '{"gost": "СТБ 3634-99", "size_mm": "500x1000", "material": "серый чугун", "weight_kg": 105.0}', 450.00, 105.0, 15),
('CI-MH-L-V15', 'Люк легкий типа Л (В15)', 2, 'Канализационный люк легкого типа', '{"gost": "ГОСТ 3634-99", "material": "серый чугун", "weight_kg": 71.9}', 280.00, 71.9, 25),
('CI-FL-PLATE', 'Плита половая', 2, 'Плита для промышленных полов', '{"gost": "-", "size_mm": "300x420x27", "material": "серый чугун"}', 95.00, 18.0, 35),
('CI-BALLS', 'Цильпебсы, шары мелющие', 2, 'Мелющие тела для дробильного оборудования', '{"gost": "ТУ предприятия", "size_mm": "⌀20–100", "material": "серый чугун"}', 65.00, 10.0, 200);

-- Электродвигатели общепромышленные АИР (категория 3)
INSERT INTO products (product_code, product_name, category_id, description, specifications, base_price, weight, stock_quantity) VALUES
('AIR71A2', 'Электродвигатель АИР71А2', 3, 'Асинхронный трехфазный, 0.55 кВт, 3000 об/мин', '{"power_kw": 0.55, "rpm": 3000, "efficiency_pct": 74.0, "cos_phi": 0.78, "frame_size_mm": 71, "voltage_v": 380, "frequency_hz": 50}', 180.00, 10.2, 60),
('AIR71B2', 'Электродвигатель АИР71В2', 3, 'Асинхронный трехфазный, 0.75 кВт, 3000 об/мин', '{"power_kw": 0.75, "rpm": 3000, "efficiency_pct": 75.0, "cos_phi": 0.80, "frame_size_mm": 71, "voltage_v": 380, "frequency_hz": 50}', 195.00, 10.5, 55),
('AIR71A4', 'Электродвигатель АИР71А4', 3, 'Асинхронный трехфазный, 0.37 кВт, 1500 об/мин', '{"power_kw": 0.37, "rpm": 1500, "efficiency_pct": 69.0, "cos_phi": 0.63, "frame_size_mm": 71, "voltage_v": 380, "frequency_hz": 50}', 175.00, 9.7, 65),
('AIR71B4', 'Электродвигатель АИР71В4', 3, 'Асинхронный трехфазный, 0.55 кВт, 1500 об/мин', '{"power_kw": 0.55, "rpm": 1500, "efficiency_pct": 71.0, "cos_phi": 0.71, "frame_size_mm": 71, "voltage_v": 380, "frequency_hz": 50}', 185.00, 10.2, 50),
('AIR71A6', 'Электродвигатель АИР71А6', 3, 'Асинхронный трехфазный, 0.25 кВт, 1000 об/мин', '{"power_kw": 0.25, "rpm": 1000, "efficiency_pct": 64.0, "cos_phi": 0.55, "frame_size_mm": 71, "voltage_v": 380, "frequency_hz": 50}', 170.00, 9.2, 70),
('AIR71B6', 'Электродвигатель АИР71В6', 3, 'Асинхронный трехфазный, 0.55 кВт, 1000 об/мин', '{"power_kw": 0.55, "rpm": 1000, "efficiency_pct": 69.0, "cos_phi": 0.68, "frame_size_mm": 71, "voltage_v": 380, "frequency_hz": 50}', 190.00, 10.8, 45),
('AIR80A2', 'Электродвигатель АИР80А2', 3, 'Асинхронный трехфазный, 1.5 кВт, 3000 об/мин', '{"power_kw": 1.5, "rpm": 3000, "efficiency_pct": 81.3, "cos_phi": 0.84, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 250.00, 13.3, 40),
('AIR80B2', 'Электродвигатель АИР80В2', 3, 'Асинхронный трехфазный, 2.2 кВт, 3000 об/мин', '{"power_kw": 2.2, "rpm": 3000, "efficiency_pct": 83.2, "cos_phi": 0.85, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 290.00, 15.9, 35),
('AIR80A4', 'Электродвигатель АИР80А4', 3, 'Асинхронный трехфазный, 1.1 кВт, 1500 об/мин', '{"power_kw": 1.1, "rpm": 1500, "efficiency_pct": 75.9, "cos_phi": 0.74, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 230.00, 12.8, 45),
('AIR80B4', 'Электродвигатель АИР80В4', 3, 'Асинхронный трехфазный, 1.5 кВт, 1500 об/мин', '{"power_kw": 1.5, "rpm": 1500, "efficiency_pct": 78.1, "cos_phi": 0.76, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 260.00, 14.7, 40),
('AIR80A6', 'Электродвигатель АИР80А6', 3, 'Асинхронный трехфазный, 0.75 кВт, 1000 об/мин', '{"power_kw": 0.75, "rpm": 1000, "efficiency_pct": 71.0, "cos_phi": 0.63, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 220.00, 12.5, 50),
('AIR80B6', 'Электродвигатель АИР80В6', 3, 'Асинхронный трехфазный, 1.1 кВт, 1000 об/мин', '{"power_kw": 1.1, "rpm": 1000, "efficiency_pct": 75.0, "cos_phi": 0.66, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 245.00, 16.2, 40),
('AIR90L2', 'Электродвигатель АИР90L2', 3, 'Асинхронный трехфазный, 3.0 кВт, 3000 об/мин', '{"power_kw": 3.0, "rpm": 3000, "efficiency_pct": 84.5, "cos_phi": 0.85, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 350.00, 20.6, 30),
('AIR90LB2', 'Электродвигатель АИР90LВ2', 3, 'Асинхронный трехфазный, 4.0 кВт, 3000 об/мин', '{"power_kw": 4.0, "rpm": 3000, "efficiency_pct": 86.5, "cos_phi": 0.86, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 420.00, 23.4, 25),
('AIR90L4', 'Электродвигатель АИР90L4', 3, 'Асинхронный трехфазный, 2.2 кВт, 1500 об/мин', '{"power_kw": 2.2, "rpm": 1500, "efficiency_pct": 80.3, "cos_phi": 0.81, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 320.00, 19.7, 35),
('AIR90LB4', 'Электродвигатель АИР90LВ4', 3, 'Асинхронный трехфазный, 3.0 кВт, 1500 об/мин', '{"power_kw": 3.0, "rpm": 1500, "efficiency_pct": 87.0, "cos_phi": 0.82, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 380.00, 24.1, 28),
('AIR90L6', 'Электродвигатель АИР90L6', 3, 'Асинхронный трехфазный, 1.5 кВт, 1000 об/мин', '{"power_kw": 1.5, "rpm": 1000, "efficiency_pct": 76.0, "cos_phi": 0.71, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 310.00, 20.6, 30),
('AIR90LA8', 'Электродвигатель АИР90LА8', 3, 'Асинхронный трехфазный, 0.75 кВт, 750 об/мин', '{"power_kw": 0.75, "rpm": 750, "efficiency_pct": 72.5, "cos_phi": 0.63, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 295.00, 19.5, 25),
('AIR90LB8', 'Электродвигатель АИР90LВ8', 3, 'Асинхронный трехфазный, 1.1 кВт, 750 об/мин', '{"power_kw": 1.1, "rpm": 750, "efficiency_pct": 76.0, "cos_phi": 0.66, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 330.00, 22.3, 22),
('AIR100S2', 'Электродвигатель АИР100S2', 3, 'Асинхронный трехфазный, 4.0 кВт, 3000 об/мин', '{"power_kw": 4.0, "rpm": 3000, "efficiency_pct": 86.5, "cos_phi": 0.86, "frame_size_mm": 100, "voltage_v": 380, "frequency_hz": 50}', 430.00, 23.6, 20),
('AIR100S4', 'Электродвигатель АИР100S4', 3, 'Асинхронный трехфазный, 3.0 кВт, 1500 об/мин', '{"power_kw": 3.0, "rpm": 1500, "efficiency_pct": 81.5, "cos_phi": 0.81, "frame_size_mm": 100, "voltage_v": 380, "frequency_hz": 50}', 400.00, 25.8, 25),
('AIR100L4', 'Электродвигатель АИР100L4', 3, 'Асинхронный трехфазный, 4.0 кВт, 1500 об/мин', '{"power_kw": 4.0, "rpm": 1500, "efficiency_pct": 83.1, "cos_phi": 0.80, "frame_size_mm": 100, "voltage_v": 380, "frequency_hz": 50}', 450.00, 26.1, 22),
('AIR100L6', 'Электродвигатель АИР100L6', 3, 'Асинхронный трехфазный, 2.2 кВт, 1000 об/мин', '{"power_kw": 2.2, "rpm": 1000, "efficiency_pct": 78.0, "cos_phi": 0.74, "frame_size_mm": 100, "voltage_v": 380, "frequency_hz": 50}', 380.00, 25.1, 28),
('AIR100L2', 'Электродвигатель АИР100L2', 3, 'Асинхронный трехфазный, 5.5 кВт, 3000 об/мин', '{"power_kw": 5.5, "rpm": 3000, "efficiency_pct": 87.5, "cos_phi": 0.88, "frame_size_mm": 100, "voltage_v": 380, "frequency_hz": 50}', 520.00, 31.0, 18),
('AIR112M2', 'Электродвигатель АИР112M2', 3, 'Асинхронный трехфазный, 7.5 кВт, 3000 об/мин', '{"power_kw": 7.5, "rpm": 3000, "efficiency_pct": 87.5, "cos_phi": 0.89, "frame_size_mm": 112, "voltage_v": 380, "frequency_hz": 50}', 650.00, 37.0, 15),
('AIR112M4', 'Электродвигатель АИР112M4', 3, 'Асинхронный трехфазный, 5.5 кВт, 1500 об/мин', '{"power_kw": 5.5, "rpm": 1500, "efficiency_pct": 85.0, "cos_phi": 0.86, "frame_size_mm": 112, "voltage_v": 380, "frequency_hz": 50}', 580.00, 38.5, 18),
('AIR112MA6', 'Электродвигатель АИР112MА6', 3, 'Асинхронный трехфазный, 3.0 кВт, 1000 об/мин', '{"power_kw": 3.0, "rpm": 1000, "efficiency_pct": 83.0, "cos_phi": 0.72, "frame_size_mm": 112, "voltage_v": 380, "frequency_hz": 50}', 480.00, 35.9, 20),
('AIR112MB6', 'Электродвигатель АИР112MВ6', 3, 'Асинхронный трехфазный, 4.0 кВт, 1000 об/мин', '{"power_kw": 4.0, "rpm": 1000, "efficiency_pct": 84.5, "cos_phi": 0.75, "frame_size_mm": 112, "voltage_v": 380, "frequency_hz": 50}', 550.00, 41.0, 16),
('AIR112MA8', 'Электродвигатель АИР112MА8', 3, 'Асинхронный трехфазный, 2.2 кВт, 750 об/мин', '{"power_kw": 2.2, "rpm": 750, "efficiency_pct": 77.2, "cos_phi": 0.63, "frame_size_mm": 112, "voltage_v": 380, "frequency_hz": 50}', 450.00, 35.3, 18),
('AIR112MB8', 'Электродвигатель АИР112MВ8', 3, 'Асинхронный трехфазный, 3.0 кВт, 750 об/мин', '{"power_kw": 3.0, "rpm": 750, "efficiency_pct": 76.5, "cos_phi": 0.66, "frame_size_mm": 112, "voltage_v": 380, "frequency_hz": 50}', 520.00, 40.0, 15);

-- Электродвигатели с повышенным скольжением AIRS (категория 4)
INSERT INTO products (product_code, product_name, category_id, description, specifications, base_price, weight, stock_quantity) VALUES
('AIRS80A2', 'Электродвигатель AIRS80A2', 4, 'С повышенным скольжением, 1.5 кВт, 3000 об/мин', '{"power_kw": 1.5, "rpm": 3000, "efficiency_pct": 74.0, "cos_phi": 0.78, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50, "note": "Тяжелый пуск, высокое скольжение"}', 280.00, 13.1, 25),
('AIRS80B2', 'Электродвигатель AIRS80B2', 4, 'С повышенным скольжением, 2.2 кВт, 3000 об/мин', '{"power_kw": 2.2, "rpm": 3000, "efficiency_pct": 73.0, "cos_phi": 0.85, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 320.00, 15.9, 20),
('AIRS80A4', 'Электродвигатель AIRS80A4', 4, 'С повышенным скольжением, 1.1 кВт, 1500 об/мин', '{"power_kw": 1.1, "rpm": 1500, "efficiency_pct": 79.0, "cos_phi": 0.83, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 260.00, 12.8, 28),
('AIRS80B4', 'Электродвигатель AIRS80B4', 4, 'С повышенным скольжением, 1.5 кВт, 1500 об/мин', '{"power_kw": 1.5, "rpm": 1500, "efficiency_pct": 78.0, "cos_phi": 0.86, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 290.00, 14.7, 22),
('AIRS80A6', 'Электродвигатель AIRS80A6', 4, 'С повышенным скольжением, 0.75 кВт, 1000 об/мин', '{"power_kw": 0.75, "rpm": 1000, "efficiency_pct": 74.0, "cos_phi": 0.68, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 250.00, 12.5, 30),
('AIRS80B6', 'Электродвигатель AIRS80B6', 4, 'С повышенным скольжением, 1.1 кВт, 1000 об/мин', '{"power_kw": 1.1, "rpm": 1000, "efficiency_pct": 74.0, "cos_phi": 0.85, "frame_size_mm": 80, "voltage_v": 380, "frequency_hz": 50}', 275.00, 16.2, 25),
('AIRS90L2', 'Электродвигатель AIRS90L2', 4, 'С повышенным скольжением, 3.0 кВт, 3000 об/мин', '{"power_kw": 3.0, "rpm": 3000, "efficiency_pct": 84.6, "cos_phi": 0.88, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 380.00, 21.3, 18),
('AIRS90LB2', 'Электродвигатель AIRS90LB2', 4, 'С повышенным скольжением, 4.0 кВт, 3000 об/мин', '{"power_kw": 4.0, "rpm": 3000, "efficiency_pct": 84.5, "cos_phi": 0.89, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 450.00, 24.5, 15),
('AIRS90L4', 'Электродвигатель AIRS90L4', 4, 'С повышенным скольжением, 2.2 кВт, 1500 об/мин', '{"power_kw": 2.2, "rpm": 1500, "efficiency_pct": 80.3, "cos_phi": 0.81, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 350.00, 20.4, 20),
('AIRS90LB4', 'Электродвигатель AIRS90LB4', 4, 'С повышенным скольжением, 3.0 кВт, 1500 об/мин', '{"power_kw": 3.0, "rpm": 1500, "efficiency_pct": 87.0, "cos_phi": 0.82, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 410.00, 23.8, 16),
('AIRS90L6', 'Электродвигатель AIRS90L6', 4, 'С повышенным скольжением, 1.5 кВт, 1000 об/мин', '{"power_kw": 1.5, "rpm": 1000, "efficiency_pct": 76.0, "cos_phi": 0.71, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 340.00, 20.6, 18),
('AIRS90LA8', 'Электродвигатель AIRS90LA8', 4, 'С повышенным скольжением, 0.75 кВт, 750 об/мин', '{"power_kw": 0.75, "rpm": 750, "efficiency_pct": 72.5, "cos_phi": 0.63, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 320.00, 19.5, 15),
('AIRS90LB8', 'Электродвигатель AIRS90LB8', 4, 'С повышенным скольжением, 1.1 кВт, 750 об/мин', '{"power_kw": 1.1, "rpm": 750, "efficiency_pct": 76.0, "cos_phi": 0.66, "frame_size_mm": 90, "voltage_v": 380, "frequency_hz": 50}', 360.00, 21.5, 14),
('AIRS100S2', 'Электродвигатель AIRS100S2', 4, 'С повышенным скольжением, 4.0 кВт, 3000 об/мин', '{"power_kw": 4.0, "rpm": 3000, "efficiency_pct": 86.5, "cos_phi": 0.86, "frame_size_mm": 100, "voltage_v": 380, "frequency_hz": 50}', 480.00, 25.0, 12),
('AIRS100S4', 'Электродвигатель AIRS100S4', 4, 'С повышенным скольжением, 3.0 кВт, 1500 об/мин', '{"power_kw": 3.0, "rpm": 1500, "efficiency_pct": 81.5, "cos_phi": 0.81, "frame_size_mm": 100, "voltage_v": 380, "frequency_hz": 50}', 440.00, 26.0, 14);

-- Электродвигатели однофазные AIRE (категория 5)
INSERT INTO products (product_code, product_name, category_id, description, specifications, base_price, weight, stock_quantity) VALUES
('AIRE71A2', 'Электродвигатель AIRE71A2', 5, 'Однофазный с конденсатором, 0.37 кВт, 3000 об/мин, 220В', '{"power_kw": 0.37, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 75.0, "cos_phi": 0.90, "capacitor_mkF": 12, "frame_size_mm": 71}', 220.00, 10.2, 35),
('AIRE71B2', 'Электродвигатель AIRE71B2', 5, 'Однофазный с конденсатором, 0.55 кВт, 3000 об/мин, 220В', '{"power_kw": 0.55, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 71.0, "cos_phi": 0.90, "capacitor_mkF": 16, "frame_size_mm": 71}', 240.00, 10.5, 30),
('AIRE71C2', 'Электродвигатель AIRE71C2', 5, 'Однофазный с конденсатором, 0.75 кВт, 3000 об/мин, 220В', '{"power_kw": 0.75, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 70.0, "cos_phi": 0.90, "capacitor_mkF": 18, "frame_size_mm": 71}', 260.00, 10.8, 28),
('AIRE71A4', 'Электродвигатель AIRE71A4', 5, 'Однофазный с конденсатором, 0.25 кВт, 1500 об/мин, 220В', '{"power_kw": 0.25, "rpm": 1500, "voltage_v": 220, "efficiency_pct": 64.0, "cos_phi": 0.90, "capacitor_mkF": 8, "frame_size_mm": 71}', 210.00, 9.7, 40),
('AIRE71B4', 'Электродвигатель AIRE71B4', 5, 'Однофазный с конденсатором, 0.37 кВт, 1500 об/мин, 220В', '{"power_kw": 0.37, "rpm": 1500, "voltage_v": 220, "efficiency_pct": 69.0, "cos_phi": 0.90, "capacitor_mkF": 12, "frame_size_mm": 71}', 230.00, 10.2, 35),
('AIRE71C4', 'Электродвигатель AIRE71C4', 5, 'Однофазный с конденсатором, 0.55 кВт, 1500 об/мин, 220В', '{"power_kw": 0.55, "rpm": 1500, "voltage_v": 220, "efficiency_pct": 65.0, "cos_phi": 0.90, "capacitor_mkF": 16, "frame_size_mm": 71}', 250.00, 10.6, 30),
('AIRE80A2', 'Электродвигатель AIRE80A2', 5, 'Однофазный с конденсатором, 0.75 кВт, 3000 об/мин, 220В', '{"power_kw": 0.75, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 70.0, "cos_phi": 0.90, "capacitor_mkF": 25, "frame_size_mm": 80}', 280.00, 13.3, 25),
('AIRE80B2', 'Электродвигатель AIRE80B2', 5, 'Однофазный с конденсатором, 1.1 кВт, 3000 об/мин, 220В', '{"power_kw": 1.1, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 76.0, "cos_phi": 0.95, "capacitor_mkF": 30, "frame_size_mm": 80}', 320.00, 15.9, 20),
('AIRE80C2', 'Электродвигатель AIRE80C2', 5, 'Однофазный с конденсатором, 1.5 кВт, 3000 об/мин, 220В', '{"power_kw": 1.5, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 75.0, "cos_phi": 0.95, "capacitor_mkF": 40, "frame_size_mm": 80}', 360.00, 16.5, 18),
('AIRE80C2_S6', 'Электродвигатель AIRE80C2/S6', 5, 'Однофазный с конденсатором, 1.1 кВт, 1000 об/мин, 220В', '{"power_kw": 1.1, "rpm": 1000, "voltage_v": 220, "efficiency_pct": 74.0, "cos_phi": 0.95, "capacitor_mkF": 40, "frame_size_mm": 80}', 350.00, 18.0, 15),
('AIRE80D2', 'Электродвигатель AIRE80D2', 5, 'Однофазный с конденсатором, 2.2 кВт, 3000 об/мин, 220В', '{"power_kw": 2.2, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 74.0, "cos_phi": 0.95, "capacitor_mkF": 50, "frame_size_mm": 80}', 400.00, 18.0, 12),
('AIRE80A4', 'Электродвигатель AIRE80A4', 5, 'Однофазный с конденсатором, 0.55 кВт, 1500 об/мин, 220В', '{"power_kw": 0.55, "rpm": 1500, "voltage_v": 220, "efficiency_pct": 64.0, "cos_phi": 0.88, "capacitor_mkF": 20, "frame_size_mm": 80}', 270.00, 12.8, 22),
('AIRE80B4', 'Электродвигатель AIRE80B4', 5, 'Однофазный с конденсатором, 0.75 кВт, 1500 об/мин, 220В', '{"power_kw": 0.75, "rpm": 1500, "voltage_v": 220, "efficiency_pct": 71.0, "cos_phi": 0.95, "capacitor_mkF": 25, "frame_size_mm": 80}', 295.00, 14.7, 20),
('AIRE80C4', 'Электродвигатель AIRE80C4', 5, 'Однофазный с конденсатором, 1.1 кВт, 1500 об/мин, 220В', '{"power_kw": 1.1, "rpm": 1500, "voltage_v": 220, "efficiency_pct": 71.0, "cos_phi": 0.95, "capacitor_mkF": 30, "frame_size_mm": 80}', 330.00, 16.7, 18),
('AIRE90L2', 'Электродвигатель AIRE90L2', 5, 'Однофазный с конденсатором, 1.5 кВт, 3000 об/мин, 220В', '{"power_kw": 1.5, "rpm": 3000, "voltage_v": 220, "efficiency_pct": 76.0, "cos_phi": 0.92, "capacitor_mkF": 40, "frame_size_mm": 90}', 380.00, 20.6, 15);

-- Остальные категории двигателей (AIRP, 2AIR, AIRCH, AIRV, специсполнения) - категория 6-10
INSERT INTO products (product_code, product_name, category_id, description, specifications, base_price, weight, stock_quantity) VALUES
('AIRP80A6', 'Электродвигатель AIRP80A6', 6, 'Для птицефабрик, 0.37 кВт, 1000 об/мин, защита от агрессивных сред', '{"power_kw": 0.37, "rpm": 1000, "efficiency_pct": 71.0, "cos_phi": 0.63, "frame_size_mm": 80, "protection": "IP55", "environment": "NH₃, H₂S, SO₂, HCl"}', 320.00, 12.2, 20),
('AIRP80B6', 'Электродвигатель AIRP80B6', 6, 'Для птицефабрик, 0.75 кВт, 1000 об/мин, защита от агрессивных сред', '{"power_kw": 0.75, "rpm": 1000, "efficiency_pct": 75.0, "cos_phi": 0.74, "frame_size_mm": 80, "protection": "IP55", "environment": "NH₃, H₂S, SO₂, HCl"}', 360.00, 14.0, 18),
('AIRP80C6', 'Электродвигатель AIRP80C6', 6, 'Для птицефабрик, 1.1 кВт, 1000 об/мин, защита от агрессивных сред', '{"power_kw": 1.1, "rpm": 1000, "efficiency_pct": 76.0, "cos_phi": 0.74, "frame_size_mm": 80, "protection": "IP55", "environment": "NH₃, H₂S, SO₂, HCl"}', 400.00, 16.8, 15),
('2AIR80A2', 'Электродвигатель 2AIR80A2', 7, 'Двухскоростной, 1.5/0.37 кВт, 3000/1500 об/мин', '{"power_kw": "1.5/0.37", "rpm": "3000/1500", "efficiency_pct": "81.3/67.5", "cos_phi": "0.89/0.67", "frame_size_mm": 80}', 450.00, 13.1, 12),
('2AIR80B2', 'Электродвигатель 2AIR80B2', 7, 'Двухскоростной, 2.2/0.55 кВт, 3000/1500 об/мин', '{"power_kw": "2.2/0.55", "rpm": "3000/1500", "efficiency_pct": "83.2/71.0", "cos_phi": "0.90/0.71", "frame_size_mm": 80}', 490.00, 15.9, 10),
('2AIR90L2', 'Электродвигатель 2AIR90L2', 7, 'Двухскоростной, 3.0/0.75 кВт, 3000/1500 об/мин', '{"power_kw": "3.0/0.75", "rpm": "3000/1500", "efficiency_pct": "84.6/75.0", "cos_phi": "0.90/0.63", "frame_size_mm": 90}', 550.00, 21.3, 8),
('AIRCH80B4', 'Электродвигатель AIRCH80B4', 8, 'Железнодорожный, виброустойчивый, 0.55 кВт, 1500 об/мин', '{"power_kw": 0.55, "rpm": 1500, "efficiency_pct": 67.5, "cos_phi": 0.78, "frame_size_mm": 80, "note": "Виброустойчивое, для ж/д транспорта"}', 380.00, 13.3, 10),
('AIRCH80B6', 'Электродвигатель AIRCH80B6', 8, 'Железнодорожный, виброустойчивый, 0.30 кВт, 1000 об/мин', '{"power_kw": 0.30, "rpm": 1000, "efficiency_pct": 71.0, "cos_phi": 0.71, "frame_size_mm": 80, "note": "Виброустойчивое, для ж/д транспорта"}', 360.00, 14.0, 12),
('AIRV100A2', 'Электродвигатель встроенный AIRV100A2', 9, 'Для встройки в агрегаты, 1.5 кВт, 3000 об/мин', '{"power_kw": 1.5, "rpm": 3000, "stator_d_mm": 160, "stator_l_mm": 130}', 420.00, 16.4, 15),
('AIRV100A4', 'Электродвигатель встроенный AIRV100A4', 9, 'Для встройки в агрегаты, 1.1 кВт, 1500 об/мин', '{"power_kw": 1.1, "rpm": 1500, "stator_d_mm": 160, "stator_l_mm": 130}', 400.00, 16.2, 18),
('AIRV100B4', 'Электродвигатель встроенный AIRV100B4', 9, 'Для встройки в агрегаты, 2.2 кВт, 1500 об/мин', '{"power_kw": 2.2, "rpm": 1500, "stator_d_mm": 176, "stator_l_mm": 150}', 480.00, 19.2, 12),
('AIR80A2_ZH', 'Электродвигатель AIR80A2 специсполнение', 10, 'Вал из нерж. стали, торцевое уплотнение, для моноблочных насосов, 1.5 кВт', '{"power_kw": 1.5, "rpm": 3000, "note": "Вал из нерж. стали, торцевое уплотнение, для моноблочных насосов"}', 350.00, 13.3, 8),
('AIR90L2_ZH', 'Электродвигатель AIR90L2 специсполнение', 10, 'Исполнение для насосов типа «моноблок», 3.0 кВт', '{"power_kw": 3.0, "rpm": 3000, "note": "Исполнение для насосов типа «моноблок»"}', 450.00, 20.6, 6),
('AIR90L2_RZ', 'Электродвигатель AIR90L2 специсполнение', 10, 'Спецвал, фланец под редуктор, 3.0 кВт', '{"power_kw": 3.0, "rpm": 3000, "note": "Спецвал, фланец под редуктор"}', 480.00, 20.6, 5);

-- Sample clients data
INSERT INTO clients (client_code, company_name, contact_person, inn, phone, email, address) VALUES
('CL001', 'ООО "БелПромСтрой"', 'Иванов Петр Сергеевич', '193456789', '+375 (29) 123-45-67', 'ivanov@belpromstroy.by', 'г. Минск, ул. Промышленная, 15'),
('CL002', 'ЗАО "Могилевский машиностроитель"', 'Козлов Андрей Владимирович', '291234567', '+375 (222) 77-88-99', 'kozlov@mmz.by', 'г. Могилев, пр-т Строителей, 42'),
('CL003', 'ЧУП "ЭнергоТехСервис"', 'Сидоров Николай Петрович', '590123456', '+375 (17) 234-56-78', 'sidorov@energotech.by', 'г. Гомель, ул. Советская, 88'),
('CL004', 'ОДО "БрестАгроКомплект"', 'Федоров Дмитрий Александрович', '290987654', '+375 (162) 45-67-89', 'fedorov@agrokomplekt.by', 'г. Брест, ул. Восточная, 23'),
('CL005', 'ООО "ВитебскЭлектроМонтаж"', 'Павлова Елена Ивановна', '391567890', '+375 (212) 56-78-90', 'pavova@vitem.by', 'г. Витебск, пр-т Фрунзе, 105'),
('CL006', 'Гродненский филиал РУП "Энергосбыт"', 'Новиков Сергей Михайлович', '490234567', '+375 (152) 67-89-01', 'novikov@energosbyt.by', 'г. Гродно, ул. Кирова, 51'),
('CL007', 'ООО "Минский завод металлоизделий"', 'Соколов Максим Андреевич', '193678901', '+375 (17) 345-67-89', 'sokolov@mzmi.by', 'г. Минск, пер. Индустриальный, 7'),
('CL008', 'Частное предприятие "ТехноРесурс"', 'Морозова Ольга Леонидовна', '590345678', '+375 (232) 78-90-12', 'morozova@technoresurs.by', 'г. Гомель, ул. Ильича, 120'),
('CL009', 'ООО "Бобруйский комбинат стройматериалов"', 'Кузнецов Виктор Павлович', '290456789', '+375 (241) 89-01-23', 'kuznetsov@bobr-stroy.by', 'г. Бобруйск, ул. Минская, 33'),
('CL010', 'УП "Мозырский нефтеперерабатывающий завод"', 'Лебедева Татьяна Николаевна', '490567890', '+375 (2351) 90-12-34', 'lebedeva@npz.by', 'г. Мозырь, ул. Молодежная, 1');

-- Sample work centers
INSERT INTO work_centers (center_code, center_name, center_type, description) VALUES
('WC001', 'Заготовительный цех', 'machining', 'Раскрой и заготовка материалов'),
('WC002', 'Токарный участок', 'machining', 'Токарная обработка деталей'),
('WC003', 'Фрезерный участок', 'machining', 'Фрезерная обработка'),
('WC004', 'Сборочный участок №1', 'assembly', 'Сборка электродвигателей малой мощности'),
('WC005', 'Сборочный участок №2', 'assembly', 'Сборка электродвигателей средней мощности'),
('WC006', 'Окрасочная камера', 'painting', 'Покраска и нанесение покрытий'),
('WC007', 'Литейный цех', 'casting', 'Чугунное и цветное литье'),
('WC008', 'Отдел технического контроля', 'quality', 'Входной и выходной контроль качества'),
('WC009', 'Склад готовой продукции', 'warehouse', 'Хранение готовой продукции'),
('WC010', 'Склад материалов', 'warehouse', 'Хранение сырья и материалов');

-- Sample technological operations for electric motor
INSERT INTO technological_operations (operation_code, operation_name, product_id, work_center_id, sequence_order, standard_time_minutes, description) VALUES
('OP001', 'Заготовка статора', 1, 1, 1, 30, 'Раскрой электротехнической стали для статора'),
('OP002', 'Штамповка пластин', 1, 1, 2, 60, 'Штамповка пластин статора'),
('OP003', 'Токарная обработка корпуса', 1, 2, 3, 45, 'Обработка корпуса двигателя'),
('OP004', 'Намотка обмотки статора', 1, 4, 4, 90, 'Намотка медной проволоки на статор'),
('OP005', 'Пропитка обмотки', 1, 4, 5, 120, 'Пропитка обмотки лаком и сушка'),
('OP006', 'Сборка двигателя', 1, 4, 6, 60, 'Final assembly of motor components'),
('OP007', 'Балансировка ротора', 1, 2, 7, 30, 'Динамическая балансировка ротора'),
('OP008', 'Покраска корпуса', 1, 6, 8, 20, 'Нанесение защитного покрытия'),
('OP009', 'Контроль качества', 1, 8, 9, 15, 'Проверка электрических параметров'),
('OP010', 'Упаковка', 1, 9, 10, 10, 'Упаковка готового изделия');

-- Sample defect types
INSERT INTO defect_types (defect_code, defect_name, category, description) VALUES
('DEF001', 'Царапины на корпусе', 'minor', 'Поверхностные дефекты покраски'),
('DEF002', 'Трещина в корпусе', 'critical', 'Сквозная трещина литой детали'),
('DEF003', 'Неправильная намотка', 'major', 'Нарушение технологии намотки обмотки'),
('DEF004', 'Пробой изоляции', 'critical', 'Нарушение изоляции обмотки'),
('DEF005', 'Дисбаланс ротора', 'major', 'Превышение допустимого дисбаланса'),
('DEF006', 'Люфт подшипника', 'major', 'Превышение радиального люфта'),
('DEF007', 'Неправильное подключение', 'major', 'Ошибка схемы подключения обмоток'),
('DEF008', 'Дефект литья', 'minor', 'Раковины, поры в отливке');

-- Sample production order for demonstration
INSERT INTO production_orders (production_number, product_id, quantity, planned_start_date, planned_end_date, priority, order_source, status, notes, created_by) VALUES
('PO-2024-00001', 1, 50, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'high', 'stock_replenishment', 'in_progress', 'Плановое производство для пополнения склада', 1),
('PO-2024-00002', 2, 30, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'normal', 'customer_order', 'planned', 'Заказ клиента ООО БелПромСтрой', 1),
('PO-2024-00003', 5, 100, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'urgent', 'customer_order', 'planned', 'Срочный заказ на электроконфорки', 1);

-- Sample production order operations for first order
INSERT INTO production_order_operations (production_order_id, operation_id, sequence_order, status, planned_start_datetime, planned_end_datetime, quantity_good, quantity_defect) VALUES
(1, 1, 1, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), 50, 0),
(1, 2, 2, 'completed', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), 50, 2),
(1, 3, 3, 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 48, 0),
(1, 4, 4, 'in_progress', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), 40, 0),
(1, 5, 5, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 0, 0),
(1, 6, 6, 'pending', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY), 0, 0),
(1, 7, 7, 'pending', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY), 0, 0),
(1, 8, 8, 'pending', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY), 0, 0),
(1, 9, 9, 'pending', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY), 0, 0),
(1, 10, 10, 'pending', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY), 0, 0);

-- Sample quality control records for demonstration
INSERT INTO quality_control (production_order_id, route_sheet_id, inspection_date, inspector_id, inspected_quantity, passed_quantity, rejected_quantity, inspection_result, certificate_number, notes) VALUES
(1, 2, DATE_SUB(NOW(), INTERVAL 3 DAY), 4, 50, 48, 2, 'conditional', 'ОТК-2024-001', 'Выявлено 2 дефекта - трещины в корпусе'),
(1, 4, NOW(), 5, 40, 40, 0, 'passed', NULL, 'Промежуточный контроль - замечаний нет');

-- Sample defect log entries
INSERT INTO defect_log (quality_control_id, defect_type_id, quantity, description, created_by) VALUES
(1, 2, 2, 'Обнаружены сквозные трещины в литых корпусах при визуальном контроле', 4);

-- Sample employees data for demonstration - REMOVED
/*
INSERT INTO employees (full_name, email, phone, position, department, hire_date, salary, status, notes) VALUES
('Иванов Иван Иванович', 'ivanov@polesie.by', '+375-29-111-11-11', 'Инженер-конструктор', 'Конструкторский отдел', '2020-03-15', 2500.00, 'active', 'Ведущий специалист'),
('Петров Пётр Петрович', 'petrov@polesie.by', '+375-29-222-22-22', 'Менеджер по продажам', 'Отдел продаж', '2019-06-01', 2200.00, 'active', NULL),
('Сидорова Анна Сергеевна', 'sidorova@polesie.by', '+375-29-333-33-33', 'Бухгалтер', 'Бухгалтерия', '2018-01-10', 2000.00, 'active', NULL),
('Козлов Дмитрий Андреевич', 'kozlov@polesie.by', '+375-29-444-44-44', 'Начальник производства', 'Производство', '2017-09-20', 3500.00, 'active', 'Руководитель подразделения'),
('Новикова Елена Владимировна', 'novikova@polesie.by', '+375-29-555-55-55', 'Инспектор по кадрам', 'Отдел кадров', '2021-02-14', 1800.00, 'active', NULL),
('Морозов Сергей Николаевич', 'morozov@polesie.by', '+375-29-666-66-66', 'Электрик', 'Производство', '2022-05-01', 1500.00, 'vacation', 'Ежегодный отпуск'),
('Волкова Мария Игоревна', 'volkova@polesie.by', '+375-29-777-77-77', 'Технолог', 'Технический отдел', '2020-11-30', 2300.00, 'active', NULL),
('Зайцев Александр Павлович', 'zaitsev@polesie.by', '+375-29-888-88-88', 'Водитель', 'Транспортный отдел', '2023-01-15', 1200.00, 'inactive', 'Декретный отпуск');
*/

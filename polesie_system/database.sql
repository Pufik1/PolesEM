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
CREATE TABLE quality_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    operation_id INT,
    inspection_date DATETIME NOT NULL,
    inspector_id INT NOT NULL,
    inspected_quantity INT NOT NULL,
    passed_quantity INT NOT NULL,
    rejected_quantity INT NOT NULL,
    defect_types JSON COMMENT 'Типы дефектов',
    inspection_result ENUM('passed', 'failed', 'conditional') DEFAULT 'passed',
    certificate_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (operation_id) REFERENCES technological_operations(id) ON DELETE SET NULL,
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL
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
CREATE TABLE defect_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    defect_code VARCHAR(50) NOT NULL UNIQUE,
    defect_name VARCHAR(200) NOT NULL,
    category ENUM('critical', 'major', 'minor') DEFAULT 'minor',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Warehouse operations table
CREATE TABLE warehouse_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_type ENUM('income', 'outcome', 'transfer', 'write_off') NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    warehouse_from INT,
    warehouse_to INT,
    user_id INT NOT NULL,
    operation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    document_number VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Employees table (extended HR data)
CREATE TABLE employees (
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

-- Insert default roles
INSERT INTO roles (role_name, role_description, permissions) VALUES
('admin', 'Системный администратор - полный доступ ко всем функциям системы', '{"all": true}'),
('director', 'Директор - просмотр всех отчетов и аналитики, управление сотрудниками', '{"dashboard": true, "reports": true, "hr": true, "analytics": true}'),
('manager', 'Менеджер по продажам - работа с клиентами, заказами, коммерческими предложениями', '{"clients": true, "orders": true, "products": true, "sales": true}'),
('production_master', 'Мастер производства - управление производственными заданиями, контроль выпуска продукции', '{"production": true, "warehouse": true, "quality": true}'),
('warehouse_keeper', 'Кладовщик - учет ТМЦ, приход/расход материалов и готовой продукции', '{"warehouse": true, "inventory": true}'),
('accountant', 'Бухгалтер - финансовая отчетность, счета, акты, накладные', '{"finance": true, "reports": true, "documents": true}'),
('hr_manager', 'HR-менеджер - кадры, отпуска, больничные, табель учета рабочего времени', '{"hr": true, "employees": true, "schedule": true}');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role_id, department, position) VALUES
('admin', 'admin123', 'Администратор Системы', 'admin@polesieelectromash.by', 1, 'IT отдел', 'Системный администратор');

-- Insert product categories based on company info
INSERT INTO product_categories (category_name, description) VALUES
('Электродвигатели асинхронные трехфазные', 'Общепромышленного назначения серии АИР'),
('Электродвигатели однофазные', 'Бытового и промышленного назначения'),
('Электродвигатели специального назначения', 'С повышенным скольжением, многоскоростные, взрывозащищенные'),
('Электроконфорки чугунные', 'Бытового назначения для электроплит'),
('Электронасосы бытовые', 'Центробежные для воды'),
('Электронасосы погружные', 'Для загрязненных вод типа ГНОМ'),
('Чугунное литье', 'Отливки из серого и высокопрочного чугуна'),
('Цветное литье', 'Отливки из алюминиевых сплавов');

-- Sample products
INSERT INTO products (product_code, product_name, category_id, description, base_price, weight, stock_quantity) VALUES
('AIR71A2', 'Электродвигатель АИР71А2', 1, 'Асинхронный трехфазный, 0.75 кВт, 3000 об/мин', 250.00, 12.5, 50),
('AIR80A4', 'Электродвигатель АИР80А4', 1, 'Асинхронный трехфазный, 1.1 кВт, 1500 об/мин', 320.00, 18.3, 45),
('AIR90L6', 'Электродвигатель АИР90L6', 1, 'Асинхронный трехфазный, 2.2 кВт, 1000 об/мин', 450.00, 28.7, 30),
('AIRE80C2', 'Электродвигатель АИРЕ80С2', 2, 'Однофазный с конденсатором, 1.5 кВт', 380.00, 16.2, 25),
('EKCH145', 'Электроконфорка ЭКЧ145', 4, 'Чугунная бытовая, 145 мм', 45.00, 2.8, 100),
('GNOM10-10', 'Насос ГНОМ 10-10', 6, 'Погружной для загрязненных вод, 10 м³/ч, 10 м', 520.00, 35.5, 20),
('CHUGUN_L4', 'Чугун литейный Л4', 7, 'ГОСТ 4832-95, чушки', 85.00, 20.0, 500),
('ALU_AV87', 'Алюминий АВ87', 8, 'Вторичный алюминий, чушки', 120.00, 15.0, 300);

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

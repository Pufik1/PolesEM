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

-- Production orders table
CREATE TABLE production_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_number VARCHAR(50) NOT NULL UNIQUE,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    planned_start_date DATE,
    planned_end_date DATE,
    actual_start_date DATE,
    actual_end_date DATE,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    responsible_user_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL
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

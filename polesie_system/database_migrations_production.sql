-- Migration: Production module tables
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28

USE polesie_electromash;

-- Bill of Materials (BOM) - спецификация материалов для каждого продукта
CREATE TABLE IF NOT EXISTS product_bom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL COMMENT 'ID готового продукта',
    bom_version VARCHAR(20) DEFAULT '1.0' COMMENT 'Версия спецификации',
    description TEXT COMMENT 'Описание спецификации',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_active (is_active)
);

-- Элементы спецификации BOM
CREATE TABLE IF NOT EXISTS product_bom_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bom_id INT NOT NULL COMMENT 'ID спецификации',
    material_id INT NOT NULL COMMENT 'ID материала',
    quantity DECIMAL(10,3) NOT NULL COMMENT 'Необходимое количество материала',
    unit VARCHAR(20) DEFAULT 'шт' COMMENT 'Единица измерения',
    waste_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Процент отходов',
    sequence_order INT DEFAULT 1 COMMENT 'Порядок использования',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bom_id) REFERENCES product_bom(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    INDEX idx_bom (bom_id),
    INDEX idx_material (material_id)
);

-- Материалы в производстве (выданные со склада)
CREATE TABLE IF NOT EXISTS production_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL COMMENT 'ID производственного заказа',
    material_id INT NOT NULL COMMENT 'ID материала',
    quantity_issued DECIMAL(10,3) NOT NULL COMMENT 'Выданное количество',
    quantity_used DECIMAL(10,3) DEFAULT 0 COMMENT 'Использованное количество',
    quantity_returned DECIMAL(10,3) DEFAULT 0 COMMENT 'Возвращенное количество',
    unit VARCHAR(20) DEFAULT 'шт',
    warehouse_document_id INT COMMENT 'ID документа списания со склада',
    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('issued', 'used', 'returned', 'partial') DEFAULT 'issued',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_document_id) REFERENCES material_writeoff_documents(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_production_order (production_order_id),
    INDEX idx_material (material_id),
    INDEX idx_status (status)
);

-- Заявки на материалы для производства
CREATE TABLE IF NOT EXISTS material_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL UNIQUE,
    production_order_id INT NOT NULL COMMENT 'ID производственного заказа',
    request_date DATE NOT NULL,
    required_date DATE,
    status ENUM('draft', 'pending', 'approved', 'issued', 'cancelled') DEFAULT 'draft',
    total_items INT DEFAULT 0,
    notes TEXT,
    requested_by INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_production_order (production_order_id),
    INDEX idx_status (status)
);

-- Элементы заявки на материалы
CREATE TABLE IF NOT EXISTS material_request_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity_requested DECIMAL(10,3) NOT NULL,
    quantity_approved DECIMAL(10,3),
    quantity_issued DECIMAL(10,3),
    unit VARCHAR(20) DEFAULT 'шт',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES material_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    INDEX idx_request (request_id),
    INDEX idx_material (material_id)
);

-- Статусы производства по заказам клиентов
CREATE TABLE IF NOT EXISTS production_order_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    status ENUM('planned', 'materials_requested', 'materials_issued', 'in_production', 'quality_control', 'completed', 'cancelled') DEFAULT 'planned',
    status_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_production_order (production_order_id),
    INDEX idx_status (status)
);

-- Добавим данные для BOM для существующих продуктов
-- Спецификация для АВН 71-2
INSERT INTO product_bom (product_id, bom_version, description, created_by) 
SELECT id, '1.0', 'Стандартная спецификация для АВН 71-2', 1 
FROM products WHERE product_code = 'AVN71-2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order) 
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE 'MAT-STEEL%' THEN 50  -- кг стали
        WHEN m.sku LIKE 'MAT-WIRE%' THEN 2.5   -- кг меди
        WHEN m.sku LIKE 'MAT-INS%' THEN 0.5    -- кг изоляции
        WHEN m.sku LIKE 'MAT-VAR%' THEN 1.5    -- литра лака
        WHEN m.sku LIKE 'MAT-BRG%' THEN 2      -- шт подшипников
        WHEN m.sku LIKE 'MAT-FAST%' THEN 20    -- шт крепежа
        WHEN m.sku LIKE 'MAT-FAN%' THEN 1      -- шт вентилятор
        WHEN m.sku LIKE 'MAT-TERM%' THEN 1     -- шт клеммная коробка
        ELSE 1
    END AS quantity,
    m.unit,
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
AND m.is_active = 1
LIMIT 10;

-- Добавим статусы для существующих производственных заказов
INSERT INTO production_order_status (production_order_id, status, changed_by)
SELECT id, 'planned', 1 FROM production_orders;

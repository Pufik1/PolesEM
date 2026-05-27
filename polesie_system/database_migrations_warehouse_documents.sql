-- Migration: Add warehouse document tables for proper goods receipt and shipment
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-27

USE polesie_electromash;

-- Goods Receipt Document (Акт приема товаров на склад)
-- Used for receiving finished products from production or materials from suppliers
CREATE TABLE IF NOT EXISTS goods_receipt_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Номер акта приема (например: ПР-2024-001)',
    receipt_date DATE NOT NULL COMMENT 'Дата приема',
    receipt_type ENUM('from_production', 'from_supplier') NOT NULL COMMENT 'Тип: из производства / от поставщика',
    
    -- Counterparty information
    supplier_id INT NULL COMMENT 'Поставщик (если от поставщика)',
    production_order_id INT NULL COMMENT 'Заказ-наряд производства (если из производства)',
    counterparty_name VARCHAR(200) COMMENT 'Наименование контрагента',
    counterparty_inn VARCHAR(20) COMMENT 'ИНН контрагента',
    counterparty_address TEXT COMMENT 'Адрес контрагента',
    
    -- Warehouse information
    warehouse_id INT NOT NULL COMMENT 'Склад приема',
    
    -- Document totals
    total_items INT DEFAULT 0 COMMENT 'Количество позиций',
    total_quantity DECIMAL(12,3) DEFAULT 0 COMMENT 'Общее количество',
    total_weight DECIMAL(10,2) DEFAULT 0 COMMENT 'Общий вес кг',
    total_cost DECIMAL(12,2) DEFAULT 0 COMMENT 'Общая стоимость',
    
    -- Status and tracking
    status ENUM('draft', 'confirmed', 'posted', 'cancelled') DEFAULT 'draft' COMMENT 'Статус документа',
    posted_at TIMESTAMP NULL COMMENT 'Дата проведения',
    posted_by INT NULL COMMENT 'Кто провел',
    
    -- References
    source_document_type VARCHAR(50) NULL COMMENT 'Тип исходного документа (накладная, договор)',
    source_document_number VARCHAR(50) NULL COMMENT 'Номер исходного документа',
    source_document_date DATE NULL COMMENT 'Дата исходного документа',
    
    -- Additional info
    vehicle_number VARCHAR(20) NULL COMMENT 'Номер транспортного средства',
    driver_name VARCHAR(100) NULL COMMENT 'ФИО водителя',
    quality_cert_required TINYINT(1) DEFAULT 0 COMMENT 'Требуется сертификат качества',
    quality_cert_number VARCHAR(50) NULL COMMENT 'Номер сертификата качества',
    
    notes TEXT COMMENT 'Комментарий к документу',
    created_by INT NOT NULL COMMENT 'Кто создал',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (supplier_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES work_centers(id) ON DELETE RESTRICT,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_receipt_date (receipt_date),
    INDEX idx_receipt_type (receipt_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Goods Receipt Document Items (Позиции акта приема)
CREATE TABLE IF NOT EXISTS goods_receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL COMMENT 'Ссылка на документ приема',
    
    -- Item identification
    item_type ENUM('product', 'material') NOT NULL COMMENT 'Тип позиции: продукция / материал',
    product_id INT NULL COMMENT 'ID готовой продукции',
    
    -- Item details
    item_name VARCHAR(200) NOT NULL COMMENT 'Наименование (копия на момент создания)',
    item_sku VARCHAR(50) COMMENT 'Артикул/код',
    item_unit VARCHAR(20) DEFAULT 'шт' COMMENT 'Единица измерения',
    
    -- Quantities
    quantity_received DECIMAL(12,3) NOT NULL COMMENT 'Принятое количество',
    quantity_defective DECIMAL(12,3) DEFAULT 0 COMMENT 'Бракованное количество',
    quantity_accepted DECIMAL(12,3) GENERATED ALWAYS AS (quantity_received - quantity_defective) STORED COMMENT 'Принято годных',
    
    -- Pricing (for supplier receipts)
    unit_price DECIMAL(12,2) DEFAULT 0 COMMENT 'Цена за единицу',
    discount_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Скидка %',
    line_total DECIMAL(12,2) DEFAULT 0 COMMENT 'Сумма строки',
    
    -- Batch and quality info
    batch_number VARCHAR(50) NULL COMMENT 'Номер партии',
    expiry_date DATE NULL COMMENT 'Срок годности',
    quality_cert_number VARCHAR(50) NULL COMMENT 'Сертификат качества',
    manufacturer_country VARCHAR(50) NULL COMMENT 'Страна производитель',
    
    -- Storage info
    storage_zone VARCHAR(10) NULL COMMENT 'Зона хранения',
    storage_location VARCHAR(50) NULL COMMENT 'Место хранения (ячейка)',
    
    -- Additional info
    notes TEXT COMMENT 'Комментарий к позиции',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (receipt_id) REFERENCES goods_receipt_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    
    INDEX idx_receipt_id (receipt_id),
    INDEX idx_item_type (item_type),
    INDEX idx_product_id (product_id)
);

-- Shipment Document (Товарная накладная на отгрузку)
-- Used for shipping finished products to customers or materials to other locations
CREATE TABLE IF NOT EXISTS shipment_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Номер накладной (например: ТН-2024-001)',
    shipment_date DATE NOT NULL COMMENT 'Дата отгрузки',
    shipment_type ENUM('to_customer', 'to_warehouse', 'return') NOT NULL COMMENT 'Тип: клиенту / на склад / возврат',
    
    -- Customer information
    customer_id INT NULL COMMENT 'Покупатель',
    customer_name VARCHAR(200) COMMENT 'Наименование покупателя',
    customer_inn VARCHAR(20) COMMENT 'ИНН покупателя',
    customer_address TEXT COMMENT 'Адрес покупателя',
    customer_contact_person VARCHAR(100) COMMENT 'Контактное лицо',
    customer_phone VARCHAR(30) COMMENT 'Телефон',
    customer_email VARCHAR(100) COMMENT 'Email',
    
    -- Order reference
    order_id INT NULL COMMENT 'Заказ покупателя',
    
    -- Warehouse information
    warehouse_from_id INT NOT NULL COMMENT 'Склад отгрузки',
    
    -- Delivery information
    delivery_address TEXT COMMENT 'Адрес доставки',
    delivery_date DATE NULL COMMENT 'Планируемая дата доставки',
    delivery_method ENUM('pickup', 'delivery', 'courier') DEFAULT 'pickup' COMMENT 'Способ доставки',
    
    -- Transport information
    vehicle_number VARCHAR(20) NULL COMMENT 'Номер транспортного средства',
    driver_name VARCHAR(100) NULL COMMENT 'ФИО водителя',
    driver_license VARCHAR(20) NULL COMMENT 'Номер водительского удостоверения',
    carrier_name VARCHAR(200) NULL COMMENT 'Перевозчик',
    
    -- Document totals
    total_items INT DEFAULT 0 COMMENT 'Количество позиций',
    total_quantity DECIMAL(12,3) DEFAULT 0 COMMENT 'Общее количество',
    total_weight DECIMAL(10,2) DEFAULT 0 COMMENT 'Общий вес кг',
    total_volume DECIMAL(10,3) DEFAULT 0 COMMENT 'Общий объем м3',
    total_cost DECIMAL(12,2) DEFAULT 0 COMMENT 'Общая стоимость',
    
    -- Status and tracking
    status ENUM('draft', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'draft' COMMENT 'Статус документа',
    shipped_at TIMESTAMP NULL COMMENT 'Дата отгрузки',
    delivered_at TIMESTAMP NULL COMMENT 'Дата получения',
    
    -- Signatures and acceptance
    shipper_signature VARCHAR(100) NULL COMMENT 'Подпись кладовщика',
    consignee_signature VARCHAR(100) NULL COMMENT 'Подпись получателя',
    received_date DATE NULL COMMENT 'Дата получения груза',
    
    -- Related documents
    ttn_number VARCHAR(50) NULL COMMENT 'Номер ТТН (если есть)',
    invoice_number VARCHAR(50) NULL COMMENT 'Номер счета',
    
    notes TEXT COMMENT 'Комментарий к документу',
    created_by INT NOT NULL COMMENT 'Кто создал',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_from_id) REFERENCES work_centers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_shipment_number (shipment_number),
    INDEX idx_shipment_date (shipment_date),
    INDEX idx_shipment_type (shipment_type),
    INDEX idx_status (status),
    INDEX idx_customer_id (customer_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
);

-- Shipment Document Items (Позиции накладной на отгрузку)
CREATE TABLE IF NOT EXISTS shipment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT NOT NULL COMMENT 'Ссылка на документ отгрузки',
    
    -- Item identification
    product_id INT NOT NULL COMMENT 'ID готовой продукции',
    
    -- Item details (snapshot at creation time)
    item_name VARCHAR(200) NOT NULL COMMENT 'Наименование (копия на момент создания)',
    item_sku VARCHAR(50) COMMENT 'Артикул/код',
    item_unit VARCHAR(20) DEFAULT 'шт' COMMENT 'Единица измерения',
    
    -- Quantities
    quantity_ordered INT NOT NULL COMMENT 'Заказанное количество',
    quantity_shipped INT NOT NULL COMMENT 'Отгруженное количество',
    quantity_reserved INT DEFAULT 0 COMMENT 'Зарезервированное количество',
    
    -- Batch info (for traceability)
    batch_number VARCHAR(50) NULL COMMENT 'Номер партии',
    
    -- Pricing
    unit_price DECIMAL(12,2) NOT NULL COMMENT 'Цена за единицу',
    discount_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Скидка %',
    vat_rate DECIMAL(5,2) DEFAULT 20 COMMENT 'НДС %',
    line_total DECIMAL(12,2) DEFAULT 0 COMMENT 'Сумма строки без НДС',
    vat_amount DECIMAL(12,2) DEFAULT 0 COMMENT 'Сумма НДС',
    line_total_with_vat DECIMAL(12,2) DEFAULT 0 COMMENT 'Сумма строки с НДС',
    
    -- Packaging
    package_count INT DEFAULT 0 COMMENT 'Количество упаковок',
    package_type VARCHAR(50) NULL COMMENT 'Тип упаковки',
    weight_per_unit DECIMAL(8,3) DEFAULT 0 COMMENT 'Вес единицы кг',
    volume_per_unit DECIMAL(8,3) DEFAULT 0 COMMENT 'Объем единицы м3',
    
    -- Additional info
    notes TEXT COMMENT 'Комментарий к позиции',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shipment_id) REFERENCES shipment_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    
    INDEX idx_shipment_id (shipment_id),
    INDEX idx_product_id (product_id)
);

-- Update warehouse_operations to link with documents
ALTER TABLE warehouse_operations 
ADD COLUMN receipt_id INT NULL COMMENT 'Ссылка на акт приема' AFTER document_number,
ADD COLUMN shipment_id INT NULL COMMENT 'Ссылка на накладную отгрузки' AFTER receipt_id;

-- Add indexes for new columns
CREATE INDEX idx_receipt_id ON warehouse_operations(receipt_id);
CREATE INDEX idx_shipment_id ON warehouse_operations(shipment_id);

-- Add foreign key constraints
ALTER TABLE warehouse_operations
ADD CONSTRAINT fk_warehouse_receipt FOREIGN KEY (receipt_id) REFERENCES goods_receipt_documents(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_warehouse_shipment FOREIGN KEY (shipment_id) REFERENCES shipment_documents(id) ON DELETE SET NULL;

-- Insert sample data for demonstration
-- Sample Goods Receipt Document (from production)
INSERT INTO goods_receipt_documents (receipt_number, receipt_date, receipt_type, production_order_id, warehouse_id, total_items, total_quantity, status, created_by) VALUES
('ПР-2024-001', '2024-12-01', 'from_production', 1, 1, 1, 10, 'confirmed', 1);

-- Sample Goods Receipt Item
INSERT INTO goods_receipt_items (receipt_id, item_type, product_id, item_name, item_sku, item_unit, quantity_received, batch_number, storage_zone) VALUES
(1, 'product', 1, 'Двигатель асинхронный трехфазный АИР71А2', 'AIM-71A2-0.75-3000', 'шт', 10, 'П-2024-12-001', 'А1');

-- Sample Shipment Document (to customer)
INSERT INTO shipment_documents (shipment_number, shipment_date, shipment_type, customer_id, customer_name, customer_inn, warehouse_from_id, total_items, total_quantity, total_cost, status, created_by) VALUES
('ТН-2024-001', '2024-12-05', 'to_customer', 1, 'ООО "Промышленные решения"', '193000000', 1, 1, 5, 12500.00, 'shipped', 1);

-- Sample Shipment Item
INSERT INTO shipment_items (shipment_id, product_id, item_name, item_sku, item_unit, quantity_ordered, quantity_shipped, unit_price, vat_rate, line_total, vat_amount, line_total_with_vat) VALUES
(1, 1, 'Двигатель асинхронный трехфазный АИР71А2', 'AIM-71A2-0.75-3000', 'шт', 5, 5, 2500.00, 20, 12500.00, 2500.00, 15000.00);

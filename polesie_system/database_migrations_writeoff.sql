-- Migration: Add warehouse write-off document tables
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-27

USE polesie_electromash;

-- Write-off Document for Materials (Акт списания материалов)
CREATE TABLE IF NOT EXISTS material_writeoff_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Номер акта списания (например: АМ-2024-001)',
    document_date DATE NOT NULL COMMENT 'Дата списания',
    writeoff_type ENUM('material', 'product') NOT NULL COMMENT 'Тип: материал / продукция',
    
    -- Warehouse information
    warehouse_id INT NOT NULL COMMENT 'Склад списания',
    
    -- Document totals
    total_items INT DEFAULT 0 COMMENT 'Количество позиций',
    total_quantity DECIMAL(12,3) DEFAULT 0 COMMENT 'Общее количество',
    total_cost DECIMAL(12,2) DEFAULT 0 COMMENT 'Общая стоимость',
    
    -- Status and tracking
    status ENUM('draft', 'confirmed', 'posted', 'cancelled') DEFAULT 'draft' COMMENT 'Статус документа',
    posted_at TIMESTAMP NULL COMMENT 'Дата проведения',
    posted_by INT NULL COMMENT 'Кто провел',
    
    -- Reason for write-off
    reason TEXT NOT NULL COMMENT 'Причина списания',
    
    notes TEXT COMMENT 'Комментарий к документу',
    created_by INT NOT NULL COMMENT 'Кто создал',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (warehouse_id) REFERENCES work_centers(id) ON DELETE RESTRICT,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_document_number (document_number),
    INDEX idx_document_date (document_date),
    INDEX idx_writeoff_type (writeoff_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Write-off Document Items (Позиции акта списания)
CREATE TABLE IF NOT EXISTS material_writeoff_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    writeoff_id INT NOT NULL COMMENT 'Ссылка на документ списания',
    
    -- Item identification
    item_type ENUM('material', 'product') NOT NULL COMMENT 'Тип позиции: материал / продукция',
    material_id INT NULL DEFAULT NULL COMMENT 'ID материала',
    product_id INT NULL DEFAULT NULL COMMENT 'ID готовой продукции',
    
    -- Item details
    item_name VARCHAR(200) NOT NULL COMMENT 'Наименование (копия на момент создания)',
    item_sku VARCHAR(50) COMMENT 'Артикул/код',
    item_unit VARCHAR(20) DEFAULT 'шт' COMMENT 'Единица измерения',
    
    -- Quantities
    quantity_written DECIMAL(12,3) NOT NULL COMMENT 'Списанное количество',
    
    -- Cost info
    unit_cost DECIMAL(12,2) DEFAULT 0 COMMENT 'Себестоимость за единицу',
    line_total DECIMAL(12,2) DEFAULT 0 COMMENT 'Сумма строки',
    
    -- Batch info
    batch_number VARCHAR(50) NULL COMMENT 'Номер партии',
    
    -- Additional info
    notes TEXT COMMENT 'Комментарий к позиции',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (writeoff_id) REFERENCES material_writeoff_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    
    INDEX idx_writeoff_id (writeoff_id),
    INDEX idx_item_type (item_type),
    INDEX idx_material_id (material_id),
    INDEX idx_product_id (product_id)
);

-- Update warehouse_operations to link with write-off documents
ALTER TABLE warehouse_operations 
ADD COLUMN writeoff_id INT NULL COMMENT 'Ссылка на акт списания' AFTER shipment_id;

-- Add index for new column
CREATE INDEX idx_writeoff_id ON warehouse_operations(writeoff_id);

-- Add foreign key constraint
ALTER TABLE warehouse_operations
ADD CONSTRAINT fk_warehouse_writeoff FOREIGN KEY (writeoff_id) REFERENCES material_writeoff_documents(id) ON DELETE SET NULL;

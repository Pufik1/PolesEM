-- Migration: Production material write-off details
-- For OAO "Polesieelectromash" ERP System
-- Purpose: Track material consumption when production is completed

USE polesie_electromash;

-- Таблица для хранения информации о списании материалов при завершении производства
CREATE TABLE IF NOT EXISTS production_material_writeoffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    completion_document_id INT NOT NULL COMMENT 'ID документа завершения производства',
    production_order_id INT NOT NULL COMMENT 'ID производственного заказа',
    material_id INT NOT NULL COMMENT 'ID материала',
    material_name VARCHAR(255) NOT NULL COMMENT 'Наименование материала (снимок на момент списания)',
    material_sku VARCHAR(100) NOT NULL COMMENT 'Артикул материала (снимок на момент списания)',
    quantity_planned DECIMAL(10,3) NOT NULL COMMENT 'Плановое количество по спецификации',
    quantity_issued DECIMAL(10,3) NOT NULL COMMENT 'Количество выданное в производство',
    quantity_used DECIMAL(10,3) NOT NULL COMMENT 'Фактически использованное количество',
    quantity_returned DECIMAL(10,3) DEFAULT 0 COMMENT 'Количество возвращенное на склад',
    unit VARCHAR(20) DEFAULT 'шт' COMMENT 'Единица измерения',
    writeoff_date DATETIME NOT NULL COMMENT 'Дата списания',
    notes TEXT COMMENT 'Комментарий',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (completion_document_id) REFERENCES production_completion_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_completion_document (completion_document_id),
    INDEX idx_production_order (production_order_id),
    INDEX idx_material (material_id),
    INDEX idx_writeoff_date (writeoff_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Детализация списания материалов при завершении производства';

-- Добавим поля в таблицу production_completion_documents для лучшей детализации
ALTER TABLE production_completion_documents 
ADD COLUMN source_order_id INT DEFAULT NULL COMMENT 'ID заказа клиента (если применимо)' AFTER production_order_id,
ADD COLUMN product_name VARCHAR(255) DEFAULT NULL COMMENT 'Наименование продукта (снимок)' AFTER product_id,
ADD COLUMN product_code VARCHAR(100) DEFAULT NULL COMMENT 'Артикул продукта (снимок)' AFTER product_name,
ADD INDEX idx_source_order (source_order_id);

-- Обновим существующие документы (заполним snapshot данные)
UPDATE production_completion_documents pcd
JOIN products p ON pcd.product_id = p.id
SET pcd.product_name = p.product_name,
    pcd.product_code = p.product_code
WHERE pcd.product_name IS NULL;

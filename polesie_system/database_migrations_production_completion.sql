-- Migration: Production completion documents table
-- For OAO "Polesieelectromash" ERP System

USE polesie_electromash;

-- Таблица документов оприходования готовой продукции
CREATE TABLE IF NOT EXISTS production_completion_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Номер документа',
    production_order_id INT NOT NULL COMMENT 'ID производственного заказа',
    product_id INT NOT NULL COMMENT 'ID готового продукта',
    quantity DECIMAL(10,3) NOT NULL COMMENT 'Количество годной продукции',
    defect_quantity DECIMAL(10,3) DEFAULT 0 COMMENT 'Количество брака',
    completion_date DATETIME NOT NULL COMMENT 'Дата завершения',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_production_order (production_order_id),
    INDEX idx_product (product_id),
    INDEX idx_completion_date (completion_date)
);

-- Добавим поле current_price в таблицу materials если его нет
ALTER TABLE materials ADD COLUMN IF NOT EXISTS current_price DECIMAL(10,2) DEFAULT 0 COMMENT 'Текущая цена за единицу';

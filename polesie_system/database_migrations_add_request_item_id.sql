-- Добавление поля request_item_id в таблицу production_materials
-- Для связи выданных материалов с позициями заявок на списание

ALTER TABLE production_materials 
ADD COLUMN request_item_id INT DEFAULT NULL COMMENT 'ID позиции заявки на материалы' AFTER warehouse_document_id,
ADD CONSTRAINT fk_request_item 
FOREIGN KEY (request_item_id) REFERENCES material_request_items(id) ON DELETE SET NULL,
ADD INDEX idx_request_item (request_item_id);

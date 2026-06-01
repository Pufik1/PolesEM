-- Добавляем статус 'rejected' в таблицу material_requests
ALTER TABLE material_requests 
MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'issued', 'cancelled', 'rejected') DEFAULT 'draft';

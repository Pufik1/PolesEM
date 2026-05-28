-- Migration: Add bom_json column to products table for storing materials list
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28
-- Purpose: Store Bill of Materials (BOM) directly in products table as JSON

USE polesie_electromash;

-- Add bom_json column to products table
ALTER TABLE products 
ADD COLUMN bom_json JSON NULL COMMENT 'Список материалов для создания продукта (BOM)' AFTER specifications;

-- Update BOM for cast iron products (category 1-2)
-- Чугунные изделия используют: чугун литейный, формовочные материалы, кокс, ферросплавы
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'name', 'Чугун литейный Л4/Л5', 'quantity', ROUND(weight * 1.15, 1)),
    JSON_OBJECT('material_id', 2, 'name', 'Песок формовочный', 'quantity', ROUND(weight * 0.5, 1)),
    JSON_OBJECT('material_id', 3, 'name', 'Кокс доменный', 'quantity', ROUND(weight * 0.08, 2)),
    JSON_OBJECT('material_id', 4, 'name', 'Ферросилиций ФС75', 'quantity', ROUND(weight * 0.015, 3))
) WHERE category_id IN (1, 2);

-- Update BOM for electric motors AIR series (category 3)
-- Different BOM based on frame size
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 5, 'name', 'Станина (корпус) чугунная', 'quantity', 1),
    JSON_OBJECT('material_id', 6, 'name', 'Сердечник статора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 7, 'name', 'Провод медный обмоточный ПЭТВ-2', 'quantity', CASE 
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 1 THEN 1.5
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 3 THEN 3.0
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 5 THEN 5.0
        ELSE 8.0 END),
    JSON_OBJECT('material_id', 8, 'name', 'Изоляция пазовая ЭМИ', 'quantity', 24),
    JSON_OBJECT('material_id', 9, 'name', 'Вал ротора стальной', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'name', 'Сердечник ротора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'name', 'Клетка короткозамкнутая алюминиевая', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'name', 'Щит подшипниковый передний', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'name', 'Щит подшипниковый задний', 'quantity', 1),
    JSON_OBJECT('material_id', 14, 'name', 'Подшипник 6204-2RS/6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', 15, 'name', 'Смазка литиевая для подшипников', 'quantity', 0.05),
    JSON_OBJECT('material_id', 16, 'name', 'Крыльчатка вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 17, 'name', 'Кожух вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 18, 'name', 'Корпус клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 19, 'name', 'Крышка клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 20, 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', 21, 'name', 'Ввод кабельный М20', 'quantity', 1),
    JSON_OBJECT('material_id', 22, 'name', 'Болт заземления М6', 'quantity', 2),
    JSON_OBJECT('material_id', 23, 'name', 'Грунтовка ГФ-021', 'quantity', 0.3),
    JSON_OBJECT('material_id', 24, 'name', 'Эмаль ПФ-115', 'quantity', 0.4),
    JSON_OBJECT('material_id', 25, 'name', 'Болт стяжной М8', 'quantity', 4),
    JSON_OBJECT('material_id', 26, 'name', 'Гайка М8', 'quantity', 4),
    JSON_OBJECT('material_id', 27, 'name', 'Шайба 8', 'quantity', 8),
    JSON_OBJECT('material_id', 28, 'name', 'Шпонка призматическая', 'quantity', 1),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка идентификационная', 'quantity', 1)
) WHERE category_id = 3 AND product_code LIKE 'AIR%';

-- Update BOM for high-slip motors AIRS (category 4)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 5, 'name', 'Станина (корпус) чугунная', 'quantity', 1),
    JSON_OBJECT('material_id', 6, 'name', 'Сердечник статора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 7, 'name', 'Провод медный обмоточный ПЭТВ-2', 'quantity', CASE 
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 1 THEN 1.8
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 3 THEN 3.5
        ELSE 6.0 END),
    JSON_OBJECT('material_id', 8, 'name', 'Изоляция пазовая ЭМИ', 'quantity', 24),
    JSON_OBJECT('material_id', 9, 'name', 'Вал ротора стальной (усиленный)', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'name', 'Сердечник ротора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'name', 'Клетка короткозамкнутая (спецсплав)', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'name', 'Щит подшипниковый передний', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'name', 'Щит подшипниковый задний', 'quantity', 1),
    JSON_OBJECT('material_id', 14, 'name', 'Подшипник 6204-2RS/6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', 15, 'name', 'Смазка литиевая для подшипников', 'quantity', 0.05),
    JSON_OBJECT('material_id', 16, 'name', 'Крыльчатка вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 17, 'name', 'Кожух вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 18, 'name', 'Корпус клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 19, 'name', 'Крышка клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 20, 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', 21, 'name', 'Ввод кабельный М20', 'quantity', 1),
    JSON_OBJECT('material_id', 22, 'name', 'Болт заземления М6', 'quantity', 2),
    JSON_OBJECT('material_id', 23, 'name', 'Грунтовка ГФ-021', 'quantity', 0.3),
    JSON_OBJECT('material_id', 24, 'name', 'Эмаль ПФ-115', 'quantity', 0.4),
    JSON_OBJECT('material_id', 25, 'name', 'Болт стяжной М8', 'quantity', 4),
    JSON_OBJECT('material_id', 26, 'name', 'Гайка М8', 'quantity', 4),
    JSON_OBJECT('material_id', 27, 'name', 'Шайба 8', 'quantity', 8),
    JSON_OBJECT('material_id', 28, 'name', 'Шпонка призматическая', 'quantity', 1),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка идентификационная', 'quantity', 1)
) WHERE category_id = 4 AND product_code LIKE 'AIRS%';

-- Update BOM for single-phase motors AIRE (category 5)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 5, 'name', 'Станина (корпус) чугунная', 'quantity', 1),
    JSON_OBJECT('material_id', 6, 'name', 'Сердечник статора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 7, 'name', 'Провод медный обмоточный ПЭТВ-2', 'quantity', CASE 
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 0.5 THEN 1.2
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 1 THEN 2.0
        ELSE 3.5 END),
    JSON_OBJECT('material_id', 8, 'name', 'Изоляция пазовая ЭМИ', 'quantity', 24),
    JSON_OBJECT('material_id', 9, 'name', 'Вал ротора стальной', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'name', 'Сердечник ротора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'name', 'Клетка короткозамкнутая алюминиевая', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'name', 'Щит подшипниковый передний', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'name', 'Щит подшипниковый задний', 'quantity', 1),
    JSON_OBJECT('material_id', 14, 'name', 'Подшипник 6204-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', 15, 'name', 'Смазка литиевая для подшипников', 'quantity', 0.04),
    JSON_OBJECT('material_id', 16, 'name', 'Крыльчатка вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 17, 'name', 'Кожух вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 18, 'name', 'Корпус клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 19, 'name', 'Крышка клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 20, 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', 21, 'name', 'Ввод кабельный М20', 'quantity', 1),
    JSON_OBJECT('material_id', 22, 'name', 'Болт заземления М6', 'quantity', 2),
    JSON_OBJECT('material_id', 30, 'name', 'Конденсатор пусковой МБГО', 'quantity', 1),
    JSON_OBJECT('material_id', 31, 'name', 'Переключатель ПВК', 'quantity', 1),
    JSON_OBJECT('material_id', 23, 'name', 'Грунтовка ГФ-021', 'quantity', 0.3),
    JSON_OBJECT('material_id', 24, 'name', 'Эмаль ПФ-115', 'quantity', 0.4),
    JSON_OBJECT('material_id', 25, 'name', 'Болт стяжной М8', 'quantity', 4),
    JSON_OBJECT('material_id', 26, 'name', 'Гайка М8', 'quantity', 4),
    JSON_OBJECT('material_id', 27, 'name', 'Шайба 8', 'quantity', 8),
    JSON_OBJECT('material_id', 28, 'name', 'Шпонка призматическая', 'quantity', 1),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка идентификационная', 'quantity', 1)
) WHERE category_id = 5 AND product_code LIKE 'AIRE%';

-- Update BOM for poultry farm motors AIRP (category 6)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 5, 'name', 'Станина (корпус) с антикоррозийным покрытием', 'quantity', 1),
    JSON_OBJECT('material_id', 6, 'name', 'Сердечник статора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 7, 'name', 'Провод медный обмоточный ПЭТВ-2', 'quantity', 2.5),
    JSON_OBJECT('material_id', 8, 'name', 'Изоляция пазовая ЭМИ', 'quantity', 24),
    JSON_OBJECT('material_id', 9, 'name', 'Вал ротора нержавеющий', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'name', 'Сердечник ротора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'name', 'Клетка короткозамкнутая алюминиевая', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'name', 'Щит подшипниковый передний', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'name', 'Щит подшипниковый задний', 'quantity', 1),
    JSON_OBJECT('material_id', 32, 'name', 'Подшипник нержавеющий 6204-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', 33, 'name', 'Смазка пищевая для подшипников', 'quantity', 0.05),
    JSON_OBJECT('material_id', 16, 'name', 'Крыльчатка вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 17, 'name', 'Кожух вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 18, 'name', 'Корпус клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 19, 'name', 'Крышка клеммной коробки с уплотнением', 'quantity', 1),
    JSON_OBJECT('material_id', 20, 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', 34, 'name', 'Уплотнение силиконовое', 'quantity', 4),
    JSON_OBJECT('material_id', 21, 'name', 'Ввод кабельный М20', 'quantity', 1),
    JSON_OBJECT('material_id', 22, 'name', 'Болт заземления М6', 'quantity', 2),
    JSON_OBJECT('material_id', 35, 'name', 'Полиуретановое защитное покрытие', 'quantity', 0.5),
    JSON_OBJECT('material_id', 25, 'name', 'Болт стяжной М8 нержавеющий', 'quantity', 4),
    JSON_OBJECT('material_id', 26, 'name', 'Гайка М8 нержавеющая', 'quantity', 4),
    JSON_OBJECT('material_id', 27, 'name', 'Шайба 8 нержавеющая', 'quantity', 8),
    JSON_OBJECT('material_id', 28, 'name', 'Шпонка призматическая', 'quantity', 1),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка идентификационная', 'quantity', 1)
) WHERE category_id = 6 AND product_code LIKE 'AIRP%';

-- Update BOM for two-speed motors 2AIR (category 7)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 5, 'name', 'Станина (корпус) чугунная', 'quantity', 1),
    JSON_OBJECT('material_id', 36, 'name', 'Сердечник статора многоскоростной', 'quantity', 1),
    JSON_OBJECT('material_id', 7, 'name', 'Провод медный обмоточный ПЭТВ-2', 'quantity', 5.0),
    JSON_OBJECT('material_id', 8, 'name', 'Изоляция пазовая ЭМИ', 'quantity', 36),
    JSON_OBJECT('material_id', 9, 'name', 'Вал ротора стальной', 'quantity', 1),
    JSON_OBJECT('material_id', 37, 'name', 'Ротор двухскоростной', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'name', 'Щит подшипниковый передний', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'name', 'Щит подшипниковый задний', 'quantity', 1),
    JSON_OBJECT('material_id', 14, 'name', 'Подшипник 6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', 15, 'name', 'Смазка литиевая для подшипников', 'quantity', 0.06),
    JSON_OBJECT('material_id', 16, 'name', 'Крыльчатка вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 17, 'name', 'Кожух вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 38, 'name', 'Корпус клеммной коробки увеличенный', 'quantity', 1),
    JSON_OBJECT('material_id', 39, 'name', 'Крышка клеммной коробки', 'quantity', 1),
    JSON_OBJECT('material_id', 40, 'name', 'Клеммная колодка 12 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', 41, 'name', 'Переключатель скоростей ППК', 'quantity', 1),
    JSON_OBJECT('material_id', 21, 'name', 'Ввод кабельный М25', 'quantity', 2),
    JSON_OBJECT('material_id', 22, 'name', 'Болт заземления М6', 'quantity', 2),
    JSON_OBJECT('material_id', 23, 'name', 'Грунтовка ГФ-021', 'quantity', 0.4),
    JSON_OBJECT('material_id', 24, 'name', 'Эмаль ПФ-115', 'quantity', 0.5),
    JSON_OBJECT('material_id', 25, 'name', 'Болт стяжной М8', 'quantity', 4),
    JSON_OBJECT('material_id', 26, 'name', 'Гайка М8', 'quantity', 4),
    JSON_OBJECT('material_id', 27, 'name', 'Шайба 8', 'quantity', 8),
    JSON_OBJECT('material_id', 28, 'name', 'Шпонка призматическая', 'quantity', 1),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка идентификационная', 'quantity', 1)
) WHERE category_id = 7 AND product_code LIKE '2AIR%';

-- Update BOM for railway motors AIRCH (category 8)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 42, 'name', 'Станина виброустойчивая', 'quantity', 1),
    JSON_OBJECT('material_id', 6, 'name', 'Сердечник статора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 7, 'name', 'Провод медный обмоточный ПЭТВ-2', 'quantity', 2.0),
    JSON_OBJECT('material_id', 43, 'name', 'Изоляция вибростойкая', 'quantity', 24),
    JSON_OBJECT('material_id', 44, 'name', 'Вал ротора усиленный', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'name', 'Сердечник ротора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'name', 'Клетка короткозамкнутая', 'quantity', 1),
    JSON_OBJECT('material_id', 45, 'name', 'Щит подшипниковый усиленный', 'quantity', 2),
    JSON_OBJECT('material_id', 46, 'name', 'Подшипник виброустойчивый', 'quantity', 2),
    JSON_OBJECT('material_id', 47, 'name', 'Смазка высокотемпературная', 'quantity', 0.08),
    JSON_OBJECT('material_id', 16, 'name', 'Крыльчатка вентилятора', 'quantity', 1),
    JSON_OBJECT('material_id', 48, 'name', 'Кожух вентилятора усиленный', 'quantity', 1),
    JSON_OBJECT('material_id', 49, 'name', 'Корпус клеммной коробки виброзащищенный', 'quantity', 1),
    JSON_OBJECT('material_id', 50, 'name', 'Виброизолятор М8', 'quantity', 4),
    JSON_OBJECT('material_id', 21, 'name', 'Ввод кабельный М20', 'quantity', 1),
    JSON_OBJECT('material_id', 22, 'name', 'Болт заземления М6', 'quantity', 2),
    JSON_OBJECT('material_id', 51, 'name', 'Амортизатор резиновый', 'quantity', 4),
    JSON_OBJECT('material_id', 25, 'name', 'Болт стяжной М8', 'quantity', 6),
    JSON_OBJECT('material_id', 26, 'name', 'Гайка М8 с гровером', 'quantity', 6),
    JSON_OBJECT('material_id', 52, 'name', 'Шайба пружинная 8', 'quantity', 12),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка идентификационная', 'quantity', 1)
) WHERE category_id = 8 AND product_code LIKE 'AIRCH%';

-- Update BOM for built-in motors AIRV/AIRVS (category 9-10)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 53, 'name', 'Станина безлаповая', 'quantity', 1),
    JSON_OBJECT('material_id', 6, 'name', 'Сердечник статора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 7, 'name', 'Провод медный обмоточный ПЭТВ-2', 'quantity', CASE 
        WHEN JSON_EXTRACT(specifications, '$.power_kw') < 1 THEN 1.5
        ELSE 3.0 END),
    JSON_OBJECT('material_id', 8, 'name', 'Изоляция пазовая ЭМИ', 'quantity', 24),
    JSON_OBJECT('material_id', 9, 'name', 'Вал ротора стальной', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'name', 'Сердечник ротора (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'name', 'Клетка короткозамкнутая', 'quantity', 1),
    JSON_OBJECT('material_id', 54, 'name', 'Фланец крепежный', 'quantity', 1),
    JSON_OBJECT('material_id', 14, 'name', 'Подшипник 6204-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', 15, 'name', 'Смазка литиевая', 'quantity', 0.04),
    JSON_OBJECT('material_id', 55, 'name', 'Муфта соединительная', 'quantity', 1),
    JSON_OBJECT('material_id', 56, 'name', 'Комплект крепежа для встройки', 'quantity', 1),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка идентификационная', 'quantity', 1)
) WHERE category_id IN (9, 10);

-- Default BOM for any products without specific BOM
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'name', 'Материал основной', 'quantity', 1),
    JSON_OBJECT('material_id', 25, 'name', 'Крепеж', 'quantity', 4),
    JSON_OBJECT('material_id', 29, 'name', 'Табличка', 'quantity', 1)
) WHERE bom_json IS NULL;

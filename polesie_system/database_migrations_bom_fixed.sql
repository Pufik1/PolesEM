-- ============================================
-- BOM (Bill of Materials) для всех продуктов
-- ОАО «Полесьеэлектромаш»
-- Все названия материалов и артикулы (SKU) взяты ТОЧНО из таблицы materials
-- Количество материалов - только целые числа
-- ============================================

USE polesie_electromash;

-- Удаляем старые таблицы BOM если они существуют
DROP TABLE IF EXISTS product_bom_items;
DROP TABLE IF EXISTS product_bom;

-- Добавляем колонку bom_json в products если её нет
ALTER TABLE products ADD COLUMN IF NOT EXISTS bom_json JSON NULL COMMENT 'Список материалов для создания продукта (BOM)' AFTER specifications;

-- ============================================
-- ЧУГУННЫЕ ИЗДЕЛИЯ (category_id = 2) - 13 продуктов
-- Материалы из таблицы materials (database_migrations_warehouse.sql)
-- ============================================

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 5),
    JSON_OBJECT('sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 24),
    JSON_OBJECT('sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 1)
) WHERE product_code IN ('CI-GR-RU2', 'CI-GR-RU3', 'CI-GR-RU4');

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 6),
    JSON_OBJECT('sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная (ПЭТФ)', 'quantity', 20),
    JSON_OBJECT('sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 1)
) WHERE product_code IN ('CI-GR-RD3', 'CI-GR-RD6K', 'CI-GR-57L');

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 18),
    JSON_OBJECT('sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4)
) WHERE product_code IN ('CI-GR-001', 'CI-GR-ANIM');

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 20),
    JSON_OBJECT('sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 6),
    JSON_OBJECT('sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 1)
) WHERE product_code IN ('CI-DR-GRILL', 'CI-DR-ASSY');

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 24),
    JSON_OBJECT('sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4)
) WHERE product_code = 'CI-MH-L-V15';

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная (ПЭТФ)', 'quantity', 16),
    JSON_OBJECT('sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4)
) WHERE product_code = 'CI-FL-PLATE';

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 3),
    JSON_OBJECT('sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 12),
    JSON_OBJECT('sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4)
) WHERE product_code = 'CI-BALLS';

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ АИР (category_id = 3) - 28 продуктов
-- Используем материалы из materials_air80_insert.sql
-- Все названия и SKU точно как в таблице materials
-- Количество - только целые числа
-- ============================================

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 3),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR71%';

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR80%';

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 5),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 30),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR90%';

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 7),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 36),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR100%';

UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 8),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 42),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR112%';

-- ============================================
-- ОДНОФАЗНЫЕ ДВИГАТЕЛИ AIRE (category_id = 5)
-- ============================================
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 3),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 5;

-- ============================================
-- СПЕЦИАЛЬНЫЕ ДВИГАТЕЛИ (категории 4,6,7,8,9,10)
-- ============================================
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 3),
    JSON_OBJECT('sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    JSON_OBJECT('sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4)
) WHERE category_id IN (4, 6, 7, 8, 9, 10);

-- Проверка результатов
SELECT 
    p.id,
    p.product_code,
    p.product_name,
    JSON_LENGTH(p.bom_json) as materials_count,
    JSON_EXTRACT(p.bom_json, '$[0].sku') as first_material_sku,
    JSON_EXTRACT(p.bom_json, '$[0].name') as first_material_name
FROM products p
WHERE p.bom_json IS NOT NULL
ORDER BY p.category_id, p.product_code;

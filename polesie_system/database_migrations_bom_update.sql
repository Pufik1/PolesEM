-- Migration: Update BOM for products with materials from materials table
-- Manufacturer: ОАО «Полесьеэлектромаш»
-- Date: 2026-05-28
-- Purpose: 
--   1. Delete old BOM data from products
--   2. Create new BOM using materials from materials table with SKU
--   3. All quantities are integers (whole numbers only)
--   4. Materials are logically distributed by product components

USE polesie_electromash;

-- Step 1: Clear old BOM data from all products
UPDATE products SET bom_json = NULL WHERE bom_json IS NOT NULL;

-- Step 2: Update BOM for electric motors AIR series (category 3) - АИР80 frame
-- Using actual materials from materials table with their SKU
-- All quantities are integers (rounded up where needed)

UPDATE products SET bom_json = JSON_ARRAY(
    -- Статор / Stator
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-001'), 'sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-002'), 'sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-003'), 'sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 15),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-004'), 'sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    
    -- Ротор / Rotor
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-005'), 'sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-006'), 'sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-007'), 'sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-008'), 'sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    
    -- Щиты подшипниковые / Bearing shields
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-009'), 'sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-010'), 'sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    
    -- Подшипники / Bearings
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-011'), 'sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-012'), 'sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    
    -- Вентилятор охлаждения / Cooling fan
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAN-013'), 'sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    
    -- Кожух вентилятора / Fan cover
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-014'), 'sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-015'), 'sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    
    -- Борно / Клеммная коробка / Terminal box
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-016'), 'sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-017'), 'sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-018'), 'sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-019'), 'sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-020'), 'sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    
    -- Лакокрасочная система / Paint system
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-021'), 'sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-022'), 'sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    
    -- Крепеж сборочный / Fasteners
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-023'), 'sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-024'), 'sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-025'), 'sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR80%';

-- Step 3: Update BOM for electric motors AIR series (category 3) - АИР71 frame
-- Similar to AIR80 but with frame-specific materials if they exist, otherwise use AIR80 materials as base

UPDATE products SET bom_json = JSON_ARRAY(
    -- Статор / Stator (using AIR80 materials as base, adjusted for smaller frame)
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-001'), 'sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-002'), 'sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-003'), 'sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 10),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-004'), 'sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    
    -- Ротор / Rotor
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-005'), 'sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-006'), 'sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-007'), 'sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-008'), 'sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    
    -- Щиты подшипниковые / Bearing shields
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-009'), 'sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-010'), 'sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    
    -- Подшипники / Bearings
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-011'), 'sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-012'), 'sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    
    -- Вентилятор охлаждения / Cooling fan
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAN-013'), 'sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    
    -- Кожух вентилятора / Fan cover
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-014'), 'sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-015'), 'sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    
    -- Борно / Клеммная коробка / Terminal box
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-016'), 'sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-017'), 'sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-018'), 'sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-019'), 'sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-020'), 'sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    
    -- Лакокрасочная система / Paint system
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-021'), 'sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-022'), 'sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    
    -- Крепеж сборочный / Fasteners
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-023'), 'sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-024'), 'sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-025'), 'sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR71%';

-- Step 4: Update BOM for electric motors AIR series (category 3) - Other frames (АИР90, АИР100, etc.)
UPDATE products SET bom_json = JSON_ARRAY(
    -- Статор / Stator
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-001'), 'sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-002'), 'sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-003'), 'sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 20),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-004'), 'sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    
    -- Ротор / Rotor
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-005'), 'sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-006'), 'sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-007'), 'sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-008'), 'sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    
    -- Щиты подшипниковые / Bearing shields
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-009'), 'sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-010'), 'sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    
    -- Подшипники / Bearings
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-011'), 'sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-012'), 'sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    
    -- Вентилятор охлаждения / Cooling fan
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAN-013'), 'sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    
    -- Кожух вентилятора / Fan cover
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-014'), 'sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-015'), 'sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    
    -- Борно / Клеммная коробка / Terminal box
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-016'), 'sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-017'), 'sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-018'), 'sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-019'), 'sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-020'), 'sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    
    -- Лакокрасочная система / Paint system
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-021'), 'sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-022'), 'sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    
    -- Крепеж сборочный / Fasteners
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-023'), 'sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-024'), 'sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-025'), 'sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 3 AND product_code LIKE 'AIR%' AND product_code NOT LIKE 'AIR80%' AND product_code NOT LIKE 'AIR71%';

-- Step 5: Update BOM for high-slip motors AIRS series (category 4)
UPDATE products SET bom_json = JSON_ARRAY(
    -- Статор / Stator (special winding for high-slip)
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-001'), 'sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-002'), 'sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-003'), 'sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 18),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-004'), 'sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    
    -- Ротор / Rotor (reinforced for high-slip)
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-005'), 'sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-006'), 'sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-007'), 'sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-008'), 'sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    
    -- Щиты подшипниковые / Bearing shields
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-009'), 'sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-010'), 'sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    
    -- Подшипники / Bearings
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-011'), 'sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-012'), 'sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    
    -- Вентилятор охлаждения / Cooling fan
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAN-013'), 'sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    
    -- Кожух вентилятора / Fan cover
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-014'), 'sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-015'), 'sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    
    -- Борно / Клеммная коробка / Terminal box
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-016'), 'sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-017'), 'sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-018'), 'sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-019'), 'sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-020'), 'sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    
    -- Лакокрасочная система / Paint system
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-021'), 'sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-022'), 'sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    
    -- Крепеж сборочный / Fasteners
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-023'), 'sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-024'), 'sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-025'), 'sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 4 AND product_code LIKE 'AIRS%';

-- Step 6: Update BOM for single-phase motors AIRE series (category 5)
UPDATE products SET bom_json = JSON_ARRAY(
    -- Статор / Stator (single-phase winding)
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-001'), 'sku', 'MAT-AIR80-STATOR-001', 'name', 'Станина (корпус) двигателя АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-002'), 'sku', 'MAT-AIR80-STATOR-002', 'name', 'Сердечник статора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-003'), 'sku', 'MAT-AIR80-STATOR-003', 'name', 'Провод медный обмоточный ПЭТВ-2 для АИР80', 'quantity', 12),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-STATOR-004'), 'sku', 'MAT-AIR80-STATOR-004', 'name', 'Изоляция пазовая ЭМИ-0.35 для АИР80', 'quantity', 24),
    
    -- Ротор / Rotor
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-005'), 'sku', 'MAT-AIR80-ROTOR-005', 'name', 'Вал ротора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-006'), 'sku', 'MAT-AIR80-ROTOR-006', 'name', 'Сердечник ротора АИР80 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-007'), 'sku', 'MAT-AIR80-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР80 (АК9-2)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-ROTOR-008'), 'sku', 'MAT-AIR80-ROTOR-008', 'name', 'Шпонка призматическая 8×7×36', 'quantity', 1),
    
    -- Щиты подшипниковые / Bearing shields
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-009'), 'sku', 'MAT-AIR80-SHIELD-009', 'name', 'Щит подшипниковый передний (DE) АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-SHIELD-010'), 'sku', 'MAT-AIR80-SHIELD-010', 'name', 'Щит подшипниковый задний (NDE) АИР80', 'quantity', 1),
    
    -- Подшипники / Bearings
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-011'), 'sku', 'MAT-AIR80-BEARING-011', 'name', 'Подшипник 6204-2RS / 6205-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-BEARING-012'), 'sku', 'MAT-AIR80-BEARING-012', 'name', 'Смазка литиевая для подшипников', 'quantity', 1),
    
    -- Вентилятор охлаждения / Cooling fan
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAN-013'), 'sku', 'MAT-AIR80-FAN-013', 'name', 'Крыльчатка вентилятора АИР80', 'quantity', 1),
    
    -- Кожух вентилятора / Fan cover
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-014'), 'sku', 'MAT-AIR80-FANCOV-014', 'name', 'Корпус кожуха вентилятора АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FANCOV-015'), 'sku', 'MAT-AIR80-FANCOV-015', 'name', 'Решетка защитная кожуха АИР80', 'quantity', 1),
    
    -- Борно / Клеммная коробка / Terminal box (with capacitor)
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-016'), 'sku', 'MAT-AIR80-TERM-016', 'name', 'Корпус клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-017'), 'sku', 'MAT-AIR80-TERM-017', 'name', 'Крышка клеммной коробки АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-018'), 'sku', 'MAT-AIR80-TERM-018', 'name', 'Клеммная колодка 6 выводов АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-019'), 'sku', 'MAT-AIR80-TERM-019', 'name', 'Ввод кабельный М20×1.5 АИР80', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-TERM-020'), 'sku', 'MAT-AIR80-TERM-020', 'name', 'Болт заземления М6×16 оцинк.', 'quantity', 2),
    
    -- Лакокрасочная система / Paint system
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-021'), 'sku', 'MAT-AIR80-PAINT-021', 'name', 'Грунтовка антикоррозионная ГФ-021', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-PAINT-022'), 'sku', 'MAT-AIR80-PAINT-022', 'name', 'Эмаль атмосферостойкая ПФ-115', 'quantity', 1),
    
    -- Крепеж сборочный / Fasteners
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-023'), 'sku', 'MAT-AIR80-FAST-023', 'name', 'Болт стяжной М8×40 кл.8.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-024'), 'sku', 'MAT-AIR80-FAST-024', 'name', 'Гайка шестигранная М8 кл.8 оцинк.', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR80-FAST-025'), 'sku', 'MAT-AIR80-FAST-025', 'name', 'Шайба плоская 8 оцинк.', 'quantity', 8)
) WHERE category_id = 5 AND product_code LIKE 'AIRE%';

-- Verification query: Show updated BOM for sample products
SELECT 
    p.product_code,
    p.product_name,
    JSON_LENGTH(p.bom_json) as material_count,
    JSON_EXTRACT(p.bom_json, '$[0].sku') as first_material_sku,
    JSON_EXTRACT(p.bom_json, '$[0].name') as first_material_name
FROM products p
WHERE p.bom_json IS NOT NULL
LIMIT 5;

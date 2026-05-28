-- Migration: Complete BOM Update for All Products
-- Manufacturer: ОАО «Полесьеэлектромаш»
-- Date: 2026-05-28
-- Purpose:
--   1. Delete old BOM data from all products
--   2. Create unique BOM for EACH product using materials from materials table
--   3. All quantities are integers (whole numbers only)
--   4. Materials are logically distributed with realistic variations per product
--   5. Include SKU (article number) from warehouse for each material

USE polesie_electromash;

-- ============================================================================
-- STEP 1: Clear ALL old BOM data from products
-- ============================================================================
UPDATE products SET bom_json = NULL WHERE bom_json IS NOT NULL;

-- ============================================================================
-- STEP 2: BOM for AIR71 frame motors (small frame, less materials)
-- ============================================================================

-- AIR71A2 (0.55 кВт, 3000 об/мин) - smallest motor
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-001'), 'sku', 'MAT-AIR71-STATOR-001', 'name', 'Станина (корпус) двигателя АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-002'), 'sku', 'MAT-AIR71-STATOR-002', 'name', 'Сердечник статора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WIRE-CU-001'), 'sku', 'MAT-WIRE-CU-001', 'name', 'Провод медный обмоточный ПЭТВ-2 Ø0.8мм', 'quantity', 8),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-INSUL-001'), 'sku', 'MAT-INSUL-001', 'name', 'Изоляция пазовая ЭМИ-0.35', 'quantity', 18),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-003'), 'sku', 'MAT-AIR71-ROTOR-003', 'name', 'Вал ротора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-004'), 'sku', 'MAT-AIR71-ROTOR-004', 'name', 'Сердечник ротора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-005'), 'sku', 'MAT-AIR71-ROTOR-005', 'name', 'Клетка короткозамкнутая АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-SHAFT-KEY-001'), 'sku', 'MAT-SHAFT-KEY-001', 'name', 'Шпонка призматическая 6×6×28', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-F'), 'sku', 'MAT-AIR71-SHIELD-F', 'name', 'Щит подшипниковый передний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-R'), 'sku', 'MAT-AIR71-SHIELD-R', 'name', 'Щит подшипниковый задний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BEARING-6203'), 'sku', 'MAT-BEARING-6203', 'name', 'Подшипник 6203-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-GREASE-LITH'), 'sku', 'MAT-GREASE-LITH', 'name', 'Смазка литиевая Литол-24', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FAN'), 'sku', 'MAT-AIR71-FAN', 'name', 'Крыльчатка вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FANCOV'), 'sku', 'MAT-AIR71-FANCOV', 'name', 'Кожух вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMBOX'), 'sku', 'MAT-AIR71-TERMBOX', 'name', 'Корпус клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMLID'), 'sku', 'MAT-AIR71-TERMLID', 'name', 'Крышка клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-TERMBLOCK-6'), 'sku', 'MAT-TERMBLOCK-6', 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-CABLEGLAND-M16'), 'sku', 'MAT-CABLEGLAND-M16', 'name', 'Ввод кабельный М16×1.5', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-PRIMER'), 'sku', 'MAT-PAINT-PRIMER', 'name', 'Грунтовка ГФ-021 серая', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-ENAMEL'), 'sku', 'MAT-PAINT-ENAMEL', 'name', 'Эмаль ПФ-115 синяя', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BOLT-M6X30'), 'sku', 'MAT-BOLT-M6X30', 'name', 'Болт стяжной М6×30 кл.8.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-NUT-M6'), 'sku', 'MAT-NUT-M6', 'name', 'Гайка шестигранная М6 кл.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WASHER-6'), 'sku', 'MAT-WASHER-6', 'name', 'Шайба плоская 6 оцинк.', 'quantity', 8)
) WHERE product_code = 'AIR71A2';

-- AIR71B2 (0.75 кВт, 3000 об/мин) - slightly more copper
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-001'), 'sku', 'MAT-AIR71-STATOR-001', 'name', 'Станина (корпус) двигателя АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-002'), 'sku', 'MAT-AIR71-STATOR-002', 'name', 'Сердечник статора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WIRE-CU-001'), 'sku', 'MAT-WIRE-CU-001', 'name', 'Провод медный обмоточный ПЭТВ-2 Ø0.8мм', 'quantity', 10),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-INSUL-001'), 'sku', 'MAT-INSUL-001', 'name', 'Изоляция пазовая ЭМИ-0.35', 'quantity', 18),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-003'), 'sku', 'MAT-AIR71-ROTOR-003', 'name', 'Вал ротора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-004'), 'sku', 'MAT-AIR71-ROTOR-004', 'name', 'Сердечник ротора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-005'), 'sku', 'MAT-AIR71-ROTOR-005', 'name', 'Клетка короткозамкнутая АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-SHAFT-KEY-001'), 'sku', 'MAT-SHAFT-KEY-001', 'name', 'Шпонка призматическая 6×6×28', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-F'), 'sku', 'MAT-AIR71-SHIELD-F', 'name', 'Щит подшипниковый передний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-R'), 'sku', 'MAT-AIR71-SHIELD-R', 'name', 'Щит подшипниковый задний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BEARING-6203'), 'sku', 'MAT-BEARING-6203', 'name', 'Подшипник 6203-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-GREASE-LITH'), 'sku', 'MAT-GREASE-LITH', 'name', 'Смазка литиевая Литол-24', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FAN'), 'sku', 'MAT-AIR71-FAN', 'name', 'Крыльчатка вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FANCOV'), 'sku', 'MAT-AIR71-FANCOV', 'name', 'Кожух вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMBOX'), 'sku', 'MAT-AIR71-TERMBOX', 'name', 'Корпус клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMLID'), 'sku', 'MAT-AIR71-TERMLID', 'name', 'Крышка клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-TERMBLOCK-6'), 'sku', 'MAT-TERMBLOCK-6', 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-CABLEGLAND-M16'), 'sku', 'MAT-CABLEGLAND-M16', 'name', 'Ввод кабельный М16×1.5', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-PRIMER'), 'sku', 'MAT-PAINT-PRIMER', 'name', 'Грунтовка ГФ-021 серая', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-ENAMEL'), 'sku', 'MAT-PAINT-ENAMEL', 'name', 'Эмаль ПФ-115 синяя', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BOLT-M6X30'), 'sku', 'MAT-BOLT-M6X30', 'name', 'Болт стяжной М6×30 кл.8.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-NUT-M6'), 'sku', 'MAT-NUT-M6', 'name', 'Гайка шестигранная М6 кл.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WASHER-6'), 'sku', 'MAT-WASHER-6', 'name', 'Шайба плоская 6 оцинк.', 'quantity', 8)
) WHERE product_code = 'AIR71B2';

-- AIR71A4 (0.37 кВт, 1500 об/мин) - 4-pole, different winding
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-001'), 'sku', 'MAT-AIR71-STATOR-001', 'name', 'Станина (корпус) двигателя АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-002'), 'sku', 'MAT-AIR71-STATOR-002', 'name', 'Сердечник статора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WIRE-CU-002'), 'sku', 'MAT-WIRE-CU-002', 'name', 'Провод медный обмоточный ПЭТВ-2 Ø0.7мм', 'quantity', 9),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-INSUL-001'), 'sku', 'MAT-INSUL-001', 'name', 'Изоляция пазовая ЭМИ-0.35', 'quantity', 20),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-003'), 'sku', 'MAT-AIR71-ROTOR-003', 'name', 'Вал ротора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-004'), 'sku', 'MAT-AIR71-ROTOR-004', 'name', 'Сердечник ротора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-006'), 'sku', 'MAT-AIR71-ROTOR-006', 'name', 'Клетка короткозамкнутая АИР71 4-полюсн.', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-SHAFT-KEY-001'), 'sku', 'MAT-SHAFT-KEY-001', 'name', 'Шпонка призматическая 6×6×28', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-F'), 'sku', 'MAT-AIR71-SHIELD-F', 'name', 'Щит подшипниковый передний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-R'), 'sku', 'MAT-AIR71-SHIELD-R', 'name', 'Щит подшипниковый задний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BEARING-6203'), 'sku', 'MAT-BEARING-6203', 'name', 'Подшипник 6203-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-GREASE-LITH'), 'sku', 'MAT-GREASE-LITH', 'name', 'Смазка литиевая Литол-24', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FAN'), 'sku', 'MAT-AIR71-FAN', 'name', 'Крыльчатка вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FANCOV'), 'sku', 'MAT-AIR71-FANCOV', 'name', 'Кожух вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMBOX'), 'sku', 'MAT-AIR71-TERMBOX', 'name', 'Корпус клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMLID'), 'sku', 'MAT-AIR71-TERMLID', 'name', 'Крышка клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-TERMBLOCK-6'), 'sku', 'MAT-TERMBLOCK-6', 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-CABLEGLAND-M16'), 'sku', 'MAT-CABLEGLAND-M16', 'name', 'Ввод кабельный М16×1.5', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-PRIMER'), 'sku', 'MAT-PAINT-PRIMER', 'name', 'Грунтовка ГФ-021 серая', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-ENAMEL'), 'sku', 'MAT-PAINT-ENAMEL', 'name', 'Эмаль ПФ-115 синяя', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BOLT-M6X30'), 'sku', 'MAT-BOLT-M6X30', 'name', 'Болт стяжной М6×30 кл.8.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-NUT-M6'), 'sku', 'MAT-NUT-M6', 'name', 'Гайка шестигранная М6 кл.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WASHER-6'), 'sku', 'MAT-WASHER-6', 'name', 'Шайба плоская 6 оцинк.', 'quantity', 8)
) WHERE product_code = 'AIR71A4';

-- AIR71B4 (0.55 кВт, 1500 об/мин)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-001'), 'sku', 'MAT-AIR71-STATOR-001', 'name', 'Станина (корпус) двигателя АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-002'), 'sku', 'MAT-AIR71-STATOR-002', 'name', 'Сердечник статора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WIRE-CU-002'), 'sku', 'MAT-WIRE-CU-002', 'name', 'Провод медный обмоточный ПЭТВ-2 Ø0.7мм', 'quantity', 11),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-INSUL-001'), 'sku', 'MAT-INSUL-001', 'name', 'Изоляция пазовая ЭМИ-0.35', 'quantity', 20),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-003'), 'sku', 'MAT-AIR71-ROTOR-003', 'name', 'Вал ротора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-004'), 'sku', 'MAT-AIR71-ROTOR-004', 'name', 'Сердечник ротора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-006'), 'sku', 'MAT-AIR71-ROTOR-006', 'name', 'Клетка короткозамкнутая АИР71 4-полюсн.', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-SHAFT-KEY-001'), 'sku', 'MAT-SHAFT-KEY-001', 'name', 'Шпонка призматическая 6×6×28', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-F'), 'sku', 'MAT-AIR71-SHIELD-F', 'name', 'Щит подшипниковый передний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-R'), 'sku', 'MAT-AIR71-SHIELD-R', 'name', 'Щит подшипниковый задний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BEARING-6203'), 'sku', 'MAT-BEARING-6203', 'name', 'Подшипник 6203-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-GREASE-LITH'), 'sku', 'MAT-GREASE-LITH', 'name', 'Смазка литиевая Литол-24', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FAN'), 'sku', 'MAT-AIR71-FAN', 'name', 'Крыльчатка вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FANCOV'), 'sku', 'MAT-AIR71-FANCOV', 'name', 'Кожух вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMBOX'), 'sku', 'MAT-AIR71-TERMBOX', 'name', 'Корпус клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMLID'), 'sku', 'MAT-AIR71-TERMLID', 'name', 'Крышка клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-TERMBLOCK-6'), 'sku', 'MAT-TERMBLOCK-6', 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-CABLEGLAND-M16'), 'sku', 'MAT-CABLEGLAND-M16', 'name', 'Ввод кабельный М16×1.5', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-PRIMER'), 'sku', 'MAT-PAINT-PRIMER', 'name', 'Грунтовка ГФ-021 серая', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-ENAMEL'), 'sku', 'MAT-PAINT-ENAMEL', 'name', 'Эмаль ПФ-115 синяя', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BOLT-M6X30'), 'sku', 'MAT-BOLT-M6X30', 'name', 'Болт стяжной М6×30 кл.8.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-NUT-M6'), 'sku', 'MAT-NUT-M6', 'name', 'Гайка шестигранная М6 кл.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WASHER-6'), 'sku', 'MAT-WASHER-6', 'name', 'Шайба плоская 6 оцинк.', 'quantity', 8)
) WHERE product_code = 'AIR71B4';

-- AIR71A6 (0.25 кВт, 1000 об/мин) - 6-pole
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-001'), 'sku', 'MAT-AIR71-STATOR-001', 'name', 'Станина (корпус) двигателя АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-002'), 'sku', 'MAT-AIR71-STATOR-002', 'name', 'Сердечник статора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WIRE-CU-003'), 'sku', 'MAT-WIRE-CU-003', 'name', 'Провод медный обмоточный ПЭТВ-2 Ø0.6мм', 'quantity', 7),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-INSUL-001'), 'sku', 'MAT-INSUL-001', 'name', 'Изоляция пазовая ЭМИ-0.35', 'quantity', 22),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-003'), 'sku', 'MAT-AIR71-ROTOR-003', 'name', 'Вал ротора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-004'), 'sku', 'MAT-AIR71-ROTOR-004', 'name', 'Сердечник ротора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-007'), 'sku', 'MAT-AIR71-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР71 6-полюсн.', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-SHAFT-KEY-001'), 'sku', 'MAT-SHAFT-KEY-001', 'name', 'Шпонка призматическая 6×6×28', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-F'), 'sku', 'MAT-AIR71-SHIELD-F', 'name', 'Щит подшипниковый передний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-R'), 'sku', 'MAT-AIR71-SHIELD-R', 'name', 'Щит подшипниковый задний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BEARING-6203'), 'sku', 'MAT-BEARING-6203', 'name', 'Подшипник 6203-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-GREASE-LITH'), 'sku', 'MAT-GREASE-LITH', 'name', 'Смазка литиевая Литол-24', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FAN'), 'sku', 'MAT-AIR71-FAN', 'name', 'Крыльчатка вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FANCOV'), 'sku', 'MAT-AIR71-FANCOV', 'name', 'Кожух вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMBOX'), 'sku', 'MAT-AIR71-TERMBOX', 'name', 'Корпус клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMLID'), 'sku', 'MAT-AIR71-TERMLID', 'name', 'Крышка клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-TERMBLOCK-6'), 'sku', 'MAT-TERMBLOCK-6', 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-CABLEGLAND-M16'), 'sku', 'MAT-CABLEGLAND-M16', 'name', 'Ввод кабельный М16×1.5', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-PRIMER'), 'sku', 'MAT-PAINT-PRIMER', 'name', 'Грунтовка ГФ-021 серая', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-ENAMEL'), 'sku', 'MAT-PAINT-ENAMEL', 'name', 'Эмаль ПФ-115 синяя', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BOLT-M6X30'), 'sku', 'MAT-BOLT-M6X30', 'name', 'Болт стяжной М6×30 кл.8.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-NUT-M6'), 'sku', 'MAT-NUT-M6', 'name', 'Гайка шестигранная М6 кл.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WASHER-6'), 'sku', 'MAT-WASHER-6', 'name', 'Шайба плоская 6 оцинк.', 'quantity', 8)
) WHERE product_code = 'AIR71A6';

-- AIR71B6 (0.55 кВт, 1000 об/мин) - 6-pole more power
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-001'), 'sku', 'MAT-AIR71-STATOR-001', 'name', 'Станина (корпус) двигателя АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-STATOR-002'), 'sku', 'MAT-AIR71-STATOR-002', 'name', 'Сердечник статора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WIRE-CU-003'), 'sku', 'MAT-WIRE-CU-003', 'name', 'Провод медный обмоточный ПЭТВ-2 Ø0.6мм', 'quantity', 12),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-INSUL-001'), 'sku', 'MAT-INSUL-001', 'name', 'Изоляция пазовая ЭМИ-0.35', 'quantity', 22),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-003'), 'sku', 'MAT-AIR71-ROTOR-003', 'name', 'Вал ротора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-004'), 'sku', 'MAT-AIR71-ROTOR-004', 'name', 'Сердечник ротора АИР71 (пакет)', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-ROTOR-007'), 'sku', 'MAT-AIR71-ROTOR-007', 'name', 'Клетка короткозамкнутая АИР71 6-полюсн.', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-SHAFT-KEY-001'), 'sku', 'MAT-SHAFT-KEY-001', 'name', 'Шпонка призматическая 6×6×28', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-F'), 'sku', 'MAT-AIR71-SHIELD-F', 'name', 'Щит подшипниковый передний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-SHIELD-R'), 'sku', 'MAT-AIR71-SHIELD-R', 'name', 'Щит подшипниковый задний АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BEARING-6203'), 'sku', 'MAT-BEARING-6203', 'name', 'Подшипник 6203-2RS', 'quantity', 2),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-GREASE-LITH'), 'sku', 'MAT-GREASE-LITH', 'name', 'Смазка литиевая Литол-24', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FAN'), 'sku', 'MAT-AIR71-FAN', 'name', 'Крыльчатка вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-FANCOV'), 'sku', 'MAT-AIR71-FANCOV', 'name', 'Кожух вентилятора АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMBOX'), 'sku', 'MAT-AIR71-TERMBOX', 'name', 'Корпус клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-AIR71-TERMLID'), 'sku', 'MAT-AIR71-TERMLID', 'name', 'Крышка клеммной коробки АИР71', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-TERMBLOCK-6'), 'sku', 'MAT-TERMBLOCK-6', 'name', 'Клеммная колодка 6 выводов', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-CABLEGLAND-M16'), 'sku', 'MAT-CABLEGLAND-M16', 'name', 'Ввод кабельный М16×1.5', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-PRIMER'), 'sku', 'MAT-PAINT-PRIMER', 'name', 'Грунтовка ГФ-021 серая', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-PAINT-ENAMEL'), 'sku', 'MAT-PAINT-ENAMEL', 'name', 'Эмаль ПФ-115 синяя', 'quantity', 1),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-BOLT-M6X30'), 'sku', 'MAT-BOLT-M6X30', 'name', 'Болт стяжной М6×30 кл.8.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-NUT-M6'), 'sku', 'MAT-NUT-M6', 'name', 'Гайка шестигранная М6 кл.8', 'quantity', 4),
    JSON_OBJECT('material_id', (SELECT id FROM materials WHERE sku = 'MAT-WASHER-6'), 'sku', 'MAT-WASHER-6', 'name', 'Шайба плоская 6 оцинк.', 'quantity', 8)
) WHERE product_code = 'AIR71B6';

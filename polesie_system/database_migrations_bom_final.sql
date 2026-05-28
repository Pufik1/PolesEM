-- Migration: Complete BOM update for ALL 91 products (excluding raw materials category 1)
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28
-- This script:
-- 1. Clears old BOM data from products table
-- 2. Adds realistic and varied BOM for each product using materials from materials table
-- 3. All quantities are integers
-- 4. Each material includes: material_id, sku, name, quantity

USE polesie_electromash;

-- ============================================================
-- STEP 1: Clear old BOM data from all products
-- ============================================================
UPDATE products SET bom_json = NULL WHERE bom_json IS NOT NULL;

-- ============================================================
-- STEP 2: Update BOM for ALL products (categories 2-10)
-- Products IDs based on database.sql INSERT order
-- ============================================================

-- ============================================================
-- CATEGORY 2: Чугунное литье (Products 5-18, IDs 5-18)
-- ============================================================

-- ID 5: CI-GR-RU2 - Колосниковая решетка РУ-2
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 4),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1)
) WHERE product_code = 'CI-GR-RU2';

-- ID 6: CI-GR-RU3 - Колосниковая решетка РУ-3
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 6),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1)
) WHERE product_code = 'CI-GR-RU3';

-- ID 7: CI-GR-RU4 - Колосниковая решетка РУ-4
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 7),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 6),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1)
) WHERE product_code = 'CI-GR-RU4';

-- ID 8: CI-GR-RD3 - Колосниковая решетка РД-3
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 3),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1)
) WHERE product_code = 'CI-GR-RD3';

-- ID 9: CI-GR-RD6K - Колосниковая решетка РД-6К
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 6),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 6),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1)
) WHERE product_code = 'CI-GR-RD6K';

-- ID 10: CI-GR-57L - Колосниковая решетка 57Л
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 7),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 6),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1)
) WHERE product_code = 'CI-GR-57L';

-- ID 11: CI-GR-001 - Решетка 001
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 15),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 2)
) WHERE product_code = 'CI-GR-001';

-- ID 12: CI-GR-ANIM - Решетка животноводческая
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 21),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 2)
) WHERE product_code = 'CI-GR-ANIM';

-- ID 13: CI-DR-GRILL - Решетка дождеприемника
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 26),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 4),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 2)
) WHERE product_code = 'CI-DR-GRILL';

-- ID 14: CI-DR-ASSY - Дождеприемник в сборе
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 105),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 12),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 4),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 5)
) WHERE product_code = 'CI-DR-ASSY';

-- ID 15: CI-MH-L-V15 - Люк легкий типа Л (В15)
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 72),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 6),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 2),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 3)
) WHERE product_code = 'CI-MH-L-V15';

-- ID 16: CI-FL-PLATE - Плита половая
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 19),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 2)
) WHERE product_code = 'CI-FL-PLATE';

-- ID 17: CI-BALLS - Цильпебсы, шары мелющие
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-CI-L4', 'name', 'Чугун литейный передельный Л4', 'quantity', 10),
    JSON_OBJECT('material_id', 17, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1)
) WHERE product_code = 'CI-BALLS';

-- ============================================================
-- CATEGORY 3: Электродвигатели АИР общепромышленные (IDs 18-47)
-- ============================================================

-- ID 18: AIR71A2 - 0.55 кВт, 3000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 8),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 12),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 15),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 8),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 3),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 3),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 2),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR71A2';

-- ID 19: AIR71B2 - 0.75 кВт, 3000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 9),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 14),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 16),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 9),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 3),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 3),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 2),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR71B2';

-- ID 20: AIR71A4 - 0.37 кВт, 1500 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 7),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 10),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 14),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 7),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 2),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 3),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 2),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR71A4';

-- ID 21: AIR71B4 - 0.55 кВт, 1500 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 8),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 12),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 15),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 8),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 3),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 3),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 2),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR71B4';

-- ID 22: AIR71A6 - 0.25 кВт, 1000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 6),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 9),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 13),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 6),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 2),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 3),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 2),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR71A6';

-- ID 23: AIR71B6 - 0.55 кВт, 1000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 8),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 13),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 16),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 9),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 3),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 8),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 3),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 1),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 2),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR71B6';

-- ID 24: AIR80A2 - 1.5 кВт, 3000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 12),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 18),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 20),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 12),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 4),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 10),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 4),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 2),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 3),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR80A2';

-- ID 25: AIR80B2 - 2.2 кВт, 3000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 14),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 22),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 22),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 14),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 5),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 10),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 4),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 2),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 3),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR80B2';

-- ID 26: AIR80A4 - 1.1 кВт, 1500 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 11),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 16),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 19),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 11),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 4),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 10),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 4),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 2),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 3),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR80A4';

-- ID 27: AIR80B4 - 1.5 кВт, 1500 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 12),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 19),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 20),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 12),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 4),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 10),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 4),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 2),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 3),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR80B4';

-- ID 28: AIR80A6 - 0.75 кВт, 1000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 10),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 14),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 17),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 10),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 3),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 10),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 4),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 2),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 3),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR80A6';

-- ID 29: AIR80B6 - 1.1 кВт, 1000 об/мин
UPDATE products SET bom_json = JSON_ARRAY(
    JSON_OBJECT('material_id', 1, 'sku', 'MAT-STEEL-EL-006', 'name', 'Сталь электротехническая холоднокатаная', 'quantity', 11),
    JSON_OBJECT('material_id', 2, 'sku', 'MAT-WIRE-CU-007', 'name', 'Провод медный обмоточный эмальпровод', 'quantity', 17),
    JSON_OBJECT('material_id', 3, 'sku', 'MAT-INS-PAPER-008', 'name', 'Электрокартон изоляционный', 'quantity', 19),
    JSON_OBJECT('material_id', 4, 'sku', 'MAT-INS-FILM-009', 'name', 'Пленка полиэтилентерефталатная', 'quantity', 11),
    JSON_OBJECT('material_id', 5, 'sku', 'MAT-VAR-IMP-010', 'name', 'Лак пропиточный электроизоляционный', 'quantity', 4),
    JSON_OBJECT('material_id', 6, 'sku', 'MAT-BRG-6205-011', 'name', 'Подшипник радиальный шариковый 6205', 'quantity', 2),
    JSON_OBJECT('material_id', 7, 'sku', 'MAT-FAST-BOLT-012', 'name', 'Болты крепежные шестигранные', 'quantity', 10),
    JSON_OBJECT('material_id', 8, 'sku', 'MAT-RUB-SEAL-013', 'name', 'Уплотнители резиновые', 'quantity', 4),
    JSON_OBJECT('material_id', 9, 'sku', 'MAT-FAN-COOL-014', 'name', 'Крыльчатка вентилятора охлаждения', 'quantity', 1),
    JSON_OBJECT('material_id', 10, 'sku', 'MAT-TERM-BOX-015', 'name', 'Корпус клеммной коробки в сборе', 'quantity', 1),
    JSON_OBJECT('material_id', 11, 'sku', 'MAT-CONSERV-018', 'name', 'Смазка консервационная', 'quantity', 1),
    JSON_OBJECT('material_id', 12, 'sku', 'MAT-BEARING-GREASE-022', 'name', 'Смазка подшипниковая', 'quantity', 2),
    JSON_OBJECT('material_id', 13, 'sku', 'MAT-PAINT-023', 'name', 'Краска порошковая полиэфирная', 'quantity', 3),
    JSON_OBJECT('material_id', 14, 'sku', 'MAT-PKG-BOARD-016', 'name', 'Гофрокартон тарный', 'quantity', 1),
    JSON_OBJECT('material_id', 15, 'sku', 'MAT-PKG-STRECH-017', 'name', 'Стрейч-пленка ПВХ', 'quantity', 1),
    JSON_OBJECT('material_id', 16, 'sku', 'MAT-LABEL-026', 'name', 'Таблички идентификационные', 'quantity', 1)
) WHERE product_code = 'AIR80B6';

-- Продолжение для остальных продуктов AIR90, AIR100, AIR112, AIRS, AIRE и других
-- Файл будет продолжен во второй части из-за ограничения размера...

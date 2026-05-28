-- Migration: Bill of Materials (BOM) for all products
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28
-- Corrected version with proper material SKU references

USE polesie_electromash;

-- ============================================
-- Очистка старых данных BOM (если есть)
-- ============================================
DELETE FROM product_bom_items;
DELETE FROM product_bom;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ СЕРИИ АИР (AIR)
-- ============================================

-- АИР71А2 (0.55 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР71А2 (0.55 кВт)', 1
FROM products WHERE product_code = 'AIR71A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 15          -- Сталь электротехническая (кг)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 2.5          -- Провод медный (кг)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 0.5        -- Электрокартон (кг)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.3         -- Пленка ПЭТФ (кг)
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 1.5          -- Лак пропиточный (л)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2           -- Подшипники 6205 (шт)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8          -- Болты (шт)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4           -- Уплотнители (шт)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1           -- Крыльчатка вентилятора (шт)
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1           -- Клеммная коробка (шт)
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.8            -- Краска порошковая (кг)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1              -- Табличка идентификационная (шт)
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИР71А2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР71В2 (0.75 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР71В2 (0.75 кВт)', 1
FROM products WHERE product_code = 'AIR71B2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 18          -- Сталь электротехническая (кг)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 3.0          -- Провод медный (кг)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 0.6        -- Электрокартон (кг)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.4         -- Пленка ПЭТФ (кг)
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 1.8          -- Лак пропиточный (л)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2           -- Подшипники 6205 (шт)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8          -- Болты (шт)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4           -- Уплотнители (шт)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1           -- Крыльчатка вентилятора (шт)
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1           -- Клеммная коробка (шт)
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.9            -- Краска порошковая (кг)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1              -- Табличка идентификационная (шт)
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИР71В2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР80А2 (1.5 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР80А2 (1.5 кВт)', 1
FROM products WHERE product_code = 'AIR80A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 25          -- Сталь электротехническая (кг)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 4.2          -- Провод медный (кг)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 0.8        -- Электрокартон (кг)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.5         -- Пленка ПЭТФ (кг)
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2.5          -- Лак пропиточный (л)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2           -- Подшипники 6205 (шт)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8          -- Болты (шт)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4           -- Уплотнители (шт)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1           -- Крыльчатка вентилятора (шт)
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1           -- Клеммная коробка (шт)
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.0            -- Краска порошковая (кг)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1              -- Табличка идентификационная (шт)
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИР80А2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР80В2 (2.2 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР80В2 (2.2 кВт)', 1
FROM products WHERE product_code = 'AIR80B2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 32          -- Сталь электротехническая (кг)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5.5          -- Провод медный (кг)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1.0        -- Электрокартон (кг)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.6         -- Пленка ПЭТФ (кг)
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3.0          -- Лак пропиточный (л)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2           -- Подшипники 6205 (шт)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8          -- Болты (шт)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4           -- Уплотнители (шт)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1           -- Крыльчатка вентилятора (шт)
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1           -- Клеммная коробка (шт)
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.2            -- Краска порошковая (кг)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1              -- Табличка идентификационная (шт)
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИР80В2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР90L2 (3.0 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР90L2 (3.0 кВт)', 1
FROM products WHERE product_code = 'AIR90L2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 42          -- Сталь электротехническая (кг)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7.0          -- Провод медный (кг)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1.2        -- Электрокартон (кг)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.8         -- Пленка ПЭТФ (кг)
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4.0          -- Лак пропиточный (л)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2           -- Подшипники 6205 (шт)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8          -- Болты (шт)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4           -- Уплотнители (шт)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1           -- Крыльчатка вентилятора (шт)
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1           -- Клеммная коробка (шт)
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.5            -- Краска порошковая (кг)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1              -- Табличка идентификационная (шт)
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИР90L2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- ============================================
-- ЧУГУННЫЕ ИЗДЕЛИЯ
-- ============================================

-- Колосниковая решетка РУ-2
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-2', 1
FROM products WHERE product_code = 'CI-GR-RU2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 4.0                -- Чугун литейный Л4 (кг с учетом литников)
        WHEN m.sku = 'MAT-CI-L5' THEN 0                  -- Не используется
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.15           -- Грунтовка/краска (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для РУ-2 (3.5 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Колосниковая решетка РУ-3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-3', 1
FROM products WHERE product_code = 'CI-GR-RU3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6.2                -- Чугун литейный Л4 (кг)
        WHEN m.sku = 'MAT-CI-L5' THEN 0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.2            -- Грунтовка/краска (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для РУ-3 (5.5 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Колосниковая решетка РУ-4
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-4', 1
FROM products WHERE product_code = 'CI-GR-RU4' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6.8                -- Чугун литейный Л4 (кг)
        WHEN m.sku = 'MAT-CI-L5' THEN 0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.22           -- Грунтовка/краска (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для РУ-4 (6.0 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Колосниковая решетка РД-3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РД-3', 1
FROM products WHERE product_code = 'CI-GR-RD3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 2.5                -- Чугун литейный Л4 (кг)
        WHEN m.sku = 'MAT-CI-L5' THEN 0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.1            -- Грунтовка/краска (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для РД-3 (2.2 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Дождеприемник в сборе
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для дождеприемника в сборе', 1
FROM products WHERE product_code = 'CI-DR-ASSY' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 115.0              -- Чугун литейный Л4 (кг)
        WHEN m.sku = 'MAT-CI-L5' THEN 0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.5            -- Грунтовка/краска (кг)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 4          -- Болты для крепления (шт)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для дождеприемника в сборе (105 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023', 'MAT-FAST-BOLT-012')
ORDER BY m.sku;

-- Люк легкий типа Л (В15)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для люка легкого типа Л', 1
FROM products WHERE product_code = 'CI-MH-L-V15' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 78.0               -- Чугун литейный Л4 (кг)
        WHEN m.sku = 'MAT-CI-L5' THEN 0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.8            -- Грунтовка/краска (кг)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 2          -- Болты шарнирные (шт)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для люка Л-В15 (71.9 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023', 'MAT-FAST-BOLT-012')
ORDER BY m.sku;

-- Решетка дождеприемника
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки дождеприемника', 1
FROM products WHERE product_code = 'CI-DR-GRILL' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 28.0               -- Чугун литейный Л4 (кг)
        WHEN m.sku = 'MAT-CI-L5' THEN 0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.5            -- Грунтовка/краска (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для решетки дождеприемника (25 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Плита половая
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для плиты половой', 1
FROM products WHERE product_code = 'CI-FL-PLATE' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 20.0               -- Чугун литейный Л4 (кг)
        WHEN m.sku = 'MAT-CI-L5' THEN 0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.3            -- Грунтовка/краска (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для плиты половой (18 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023')
ORDER BY m.sku;

-- ============================================
-- Дополнительные электродвигатели
-- ============================================

-- АИР100S2 (4.0 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР100S2 (4.0 кВт)', 1
FROM products WHERE product_code = 'AIR100S2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 55          -- Сталь электротехническая (кг)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9.0          -- Провод медный (кг)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1.5        -- Электрокартон (кг)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1.0         -- Пленка ПЭТФ (кг)
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5.0          -- Лак пропиточный (л)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2           -- Подшипники 6205 (шт)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8          -- Болты (шт)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4           -- Уплотнители (шт)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1           -- Крыльчатка вентилятора (шт)
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1           -- Клеммная коробка (шт)
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.8            -- Краска порошковая (кг)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1              -- Табличка идентификационная (шт)
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИР100S2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР112M2 (7.5 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР112M2 (7.5 кВт)', 1
FROM products WHERE product_code = 'AIR112M2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 75          -- Сталь электротехническая (кг)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 12.0         -- Провод медный (кг)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2.0        -- Электрокартон (кг)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1.2         -- Пленка ПЭТФ (кг)
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 7.0          -- Лак пропиточный (л)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2           -- Подшипники 6205 (шт)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8          -- Болты (шт)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4           -- Уплотнители (шт)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1           -- Крыльчатка вентилятора (шт)
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1           -- Клеммная коробка (шт)
        WHEN m.sku = 'MAT-PAINT-023' THEN 2.2            -- Краска порошковая (кг)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1              -- Табличка идентификационная (шт)
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИР112M2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- Вывод информации о созданных спецификациях
SELECT 
    p.product_code,
    p.product_name,
    pb.bom_version,
    COUNT(pbi.id) as materials_count
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
LEFT JOIN product_bom_items pbi ON pb.id = pbi.bom_id
WHERE pb.is_active = 1
GROUP BY p.id, pb.id
ORDER BY p.product_code;

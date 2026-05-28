-- Migration: Bill of Materials (BOM) для всех продуктов
-- Для ОАО "Полесьеэлектромаш" ERP System
-- Дата: 2026-05-28
-- Назначение: Создание спецификаций материалов для производства каждого продукта
-- Использование: Выполнить после database.sql и database_migrations_warehouse.sql

USE polesie_electromash;

-- ============================================
-- Очистка старых данных BOM
-- ============================================
DELETE FROM product_bom_items;
DELETE FROM product_bom;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ СЕРИИ АИР (категория 3)
-- ============================================

-- АИР71А2 (0.55 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР71А2 (0.55 кВт)', 1
FROM products WHERE product_code = 'AIR71A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 15.0
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 2.5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 0.5
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.3
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 1.5
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2.0
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8.0
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4.0
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1.0
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.8
        WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
        ELSE 1.0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name)
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' AND p.product_code = 'AIR71A2'
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР71В2 (0.75 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР71В2 (0.75 кВт)', 1
FROM products WHERE product_code = 'AIR71B2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 18.0
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 3.0
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 0.6
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.4
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 1.8
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2.0
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8.0
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4.0
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1.0
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.9
        WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
        ELSE 1.0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name)
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'AIR71B2'
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР80А2 (1.5 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР80А2 (1.5 кВт)', 1
FROM products WHERE product_code = 'AIR80A2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 25.0
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 4.2
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 0.8
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.5
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2.5
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2.0
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8.0
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4.0
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1.0
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.0
        WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
        ELSE 1.0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name)
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'AIR80A2'
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР80В2 (2.2 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР80В2 (2.2 кВт)', 1
FROM products WHERE product_code = 'AIR80B2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 32.0
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5.5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1.0
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.6
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3.0
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2.0
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8.0
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4.0
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1.0
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
        ELSE 1.0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name)
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'AIR80B2'
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР90L2 (3.0 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР90L2 (3.0 кВт)', 1
FROM products WHERE product_code = 'AIR90L2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 42.0
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7.0
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1.2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 0.8
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4.0
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2.0
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8.0
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4.0
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1.0
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.5
        WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
        ELSE 1.0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name)
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'AIR90L2'
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР100S2 (4.0 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР100S2 (4.0 кВт)', 1
FROM products WHERE product_code = 'AIR100S2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 55.0
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9.0
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1.5
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1.0
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5.0
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2.0
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8.0
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4.0
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1.0
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.8
        WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
        ELSE 1.0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name)
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'AIR100S2'
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- АИР112M2 (7.5 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР112M2 (7.5 кВт)', 1
FROM products WHERE product_code = 'AIR112M2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 75.0
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 12.0
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2.0
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1.2
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 7.0
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2.0
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8.0
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4.0
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1.0
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 2.2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
        ELSE 1.0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name)
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'AIR112M2'
AND m.is_active = 1
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- ============================================
-- ЧУГУННЫЕ ИЗДЕЛИЯ (категория 2)
-- ============================================

-- Колосниковая решетка РУ-2
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-2', 1
FROM products WHERE product_code = 'CI-GR-RU2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 4.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.15
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (3.5 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-GR-RU2'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Колосниковая решетка РУ-3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-3', 1
FROM products WHERE product_code = 'CI-GR-RU3' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6.2
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.2
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (5.5 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-GR-RU3'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Колосниковая решетка РУ-4
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-4', 1
FROM products WHERE product_code = 'CI-GR-RU4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6.8
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.22
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (6.0 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-GR-RU4'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Колосниковая решетка РД-3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РД-3', 1
FROM products WHERE product_code = 'CI-GR-RD3' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 2.5
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.1
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (2.2 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-GR-RD3'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Дождеприемник в сборе
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для дождеприемника в сборе', 1
FROM products WHERE product_code = 'CI-DR-ASSY' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 115.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.5
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 4.0
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (105 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-DR-ASSY'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-FAST-BOLT-012')
ORDER BY m.sku;

-- Люк легкий типа Л (В15)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для люка легкого типа Л', 1
FROM products WHERE product_code = 'CI-MH-L-V15' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 78.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.8
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 2.0
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (71.9 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-MH-L-V15'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-FAST-BOLT-012')
ORDER BY m.sku;

-- Решетка дождеприемника
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки дождеприемника', 1
FROM products WHERE product_code = 'CI-DR-GRILL' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 28.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.5
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (25 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-DR-GRILL'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- Плита половая
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для плиты половой', 1
FROM products WHERE product_code = 'CI-FL-PLATE' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 20.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.3
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    CONCAT('Для ', p.product_name, ' (18 кг готовое изделие)')
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND p.product_code = 'CI-FL-PLATE'
AND m.is_active = 1
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- ============================================
-- Проверка созданных спецификаций
-- ============================================
SELECT 
    p.product_code AS 'Код продукта',
    p.product_name AS 'Наименование',
    pb.bom_version AS 'Версия BOM',
    COUNT(pbi.id) AS 'Кол-во материалов'
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
LEFT JOIN product_bom_items pbi ON pb.id = pbi.bom_id
WHERE pb.is_active = 1
GROUP BY p.id, pb.id
ORDER BY p.product_code;

-- Детализация по каждому продукту
SELECT 
    p.product_code,
    p.product_name,
    m.sku AS 'Материал SKU',
    m.name AS 'Материал Наименование',
    pbi.quantity AS 'Количество',
    pbi.unit AS 'Ед.изм.',
    pbi.notes AS 'Примечание'
FROM product_bom pb
JOIN products p ON pb.product_id = p.id
JOIN product_bom_items pbi ON pb.id = pbi.bom_id
JOIN materials m ON pbi.material_id = m.id
WHERE pb.is_active = 1
ORDER BY p.product_code, pbi.sequence_order;

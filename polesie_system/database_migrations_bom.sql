-- Migration: Bill of Materials (BOM) для ВСЕХ продуктов
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
-- ЭЛЕКТРОДВИГАТЕЛИ АИР (серия 71, 2 полюса)
-- ============================================

-- AIR71A2 (0.55 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR71A2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 15
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 3
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR71B2 (0.75 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR71B2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 18
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 3
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR71A4 (0.37 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR71A4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 14
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 3
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR71B4 (0.55 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR71B4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 15
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 3
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR71A6 (0.25 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR71A6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 13
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 3
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR71B6 (0.55 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR71B6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 16
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 4
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR80A2 (1.5 кВт)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR80A2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 25
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 4
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR80B2 (2.2 кВт)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR80B2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 32
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR80A4 (1.1 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR80A4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 19
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 4
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR80B4 (1.5 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR80B4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 22
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR80A6 (0.75 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR80A6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 18
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 4
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 2
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR80B6 (1.1 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR80B6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 24
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR90L2 (3.0 кВт)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR90L2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 42
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR90LB2 (4.0 кВт)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR90LB2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR90L4 (2.2 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR90L4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 30
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR90LB4 (3.0 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR90LB4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 36
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR90L6 (1.5 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR90L6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 30
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR90LA8 (0.75 кВт, 8 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR90LA8' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 28
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR90LB8 (1.1 кВт, 8 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR90LB8' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 33
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR100S2 (4.0 кВт)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR100S2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 55
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR100S4 (3.0 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR100S4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 38
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR100L4 (4.0 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR100L4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 45
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR100L6 (2.2 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR100L6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 37
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 1
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR100L2 (5.5 кВт)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR100L2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 65
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 11
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 6
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR112M2 (7.5 кВт)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR112M2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 75
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 12
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 7
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR112M4 (5.5 кВт, 4 полюса)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR112M4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 58
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 10
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 6
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR112MA6 (3.0 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR112MA6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 52
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR112MB6 (4.0 кВт, 6 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR112MB6' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 60
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 10
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 6
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR112MA8 (2.2 кВт, 8 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR112MA8' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- AIR112MB8 (3.0 кВт, 8 полюсов)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'AIR112MB8' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 58
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 10
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 6
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
              'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
              'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
ORDER BY m.sku;

-- ============================================
-- ЧУГУННЫЕ ИЗДЕЛИЯ (категория 2)
-- ============================================

-- CI-GR-RU2
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-RU2' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-GR-RU3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-RU3' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-GR-RU4
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-RU4' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 7
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-GR-RD3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-RD3' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 3
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-GR-RD6K
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-RD6K' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-GR-57L
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-57L' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 7
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-GR-001
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-001' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 15
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-GR-ANIM
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-GR-ANIM' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 21
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-DR-GRILL
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-DR-GRILL' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 26
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-DR-ASSY
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-DR-ASSY' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 115
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 4
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-FAST-BOLT-012')
ORDER BY m.sku;

-- CI-MH-L-V15
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-MH-L-V15' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 78
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-FL-PLATE
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-FL-PLATE' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 19
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023')
ORDER BY m.sku;

-- CI-BALLS
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', CONCAT('Спецификация для ', product_name), 1
FROM products WHERE product_code = 'CI-BALLS' LIMIT 1;

SET @seq := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 11
        ELSE 1
    END,
    @seq := @seq + 1
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.sku IN ('MAT-CI-L4')
ORDER BY m.sku;

-- ============================================
-- ОСТАЛЬНЫЕ ЭЛЕКТРОДВИГАТЕЛИ (серии AIRS, AIRE, AIRP, 2AIR, AIRCH, AIRV, спец)
-- ============================================

-- Генерация BOM для всех остальных электродвигателей с одинаковым набором материалов
-- Используем процедуру для автоматизации

DELIMITER $$

CREATE PROCEDURE generate_motor_bom(IN p_product_code VARCHAR(50))
BEGIN
    DECLARE v_product_id INT;
    
    SELECT id INTO v_product_id FROM products WHERE product_code = p_product_code LIMIT 1;
    
    IF v_product_id IS NOT NULL THEN
        INSERT INTO product_bom (product_id, bom_version, description, is_active)
        VALUES (v_product_id, '1.0', CONCAT('Спецификация для ', p_product_code), 1);
        
        SET @seq := 0;
        INSERT INTO product_bom_items (bom_id, material_id, quantity, sequence_order)
        SELECT pb.id, m.id, 1, @seq := @seq + 1
        FROM product_bom pb
        CROSS JOIN materials m
        WHERE pb.product_id = v_product_id AND pb.bom_version = '1.0'
        AND m.sku IN ('MAT-STEEL-EL-006', 'MAT-WIRE-CU-007', 'MAT-INS-PAPER-008', 'MAT-INS-FILM-009', 
                      'MAT-VAR-IMP-010', 'MAT-BRG-6205-011', 'MAT-FAST-BOLT-012', 'MAT-RUB-SEAL-013',
                      'MAT-FAN-COOL-014', 'MAT-TERM-BOX-015', 'MAT-PAINT-023', 'MAT-LABEL-026')
        ORDER BY m.sku;
    END IF;
END$$

DELIMITER ;

-- Вызов процедуры для всех оставшихся двигателей
CALL generate_motor_bom('AIRS80A2');
CALL generate_motor_bom('AIRS80B2');
CALL generate_motor_bom('AIRS80A4');
CALL generate_motor_bom('AIRS80B4');
CALL generate_motor_bom('AIRS80A6');
CALL generate_motor_bom('AIRS80B6');
CALL generate_motor_bom('AIRS90L2');
CALL generate_motor_bom('AIRS90LB2');
CALL generate_motor_bom('AIRS90L4');
CALL generate_motor_bom('AIRS90LB4');
CALL generate_motor_bom('AIRS90L6');
CALL generate_motor_bom('AIRS90LA8');
CALL generate_motor_bom('AIRS90LB8');
CALL generate_motor_bom('AIRS100S2');
CALL generate_motor_bom('AIRS100S4');
CALL generate_motor_bom('AIRE71A2');
CALL generate_motor_bom('AIRE71B2');
CALL generate_motor_bom('AIRE71C2');
CALL generate_motor_bom('AIRE71A4');
CALL generate_motor_bom('AIRE71B4');
CALL generate_motor_bom('AIRE71C4');
CALL generate_motor_bom('AIRE80A2');
CALL generate_motor_bom('AIRE80B2');
CALL generate_motor_bom('AIRE80C2');
CALL generate_motor_bom('AIRE80C2_S6');
CALL generate_motor_bom('AIRE80D2');
CALL generate_motor_bom('AIRE80A4');
CALL generate_motor_bom('AIRE80B4');
CALL generate_motor_bom('AIRE80C4');
CALL generate_motor_bom('AIRE90L2');
CALL generate_motor_bom('AIRP80A6');
CALL generate_motor_bom('AIRP80B6');
CALL generate_motor_bom('AIRP80C6');
CALL generate_motor_bom('2AIR80A2');
CALL generate_motor_bom('2AIR80B2');
CALL generate_motor_bom('2AIR90L2');
CALL generate_motor_bom('AIRCH80B4');
CALL generate_motor_bom('AIRCH80B6');
CALL generate_motor_bom('AIRV100A2');
CALL generate_motor_bom('AIRV100A4');
CALL generate_motor_bom('AIRV100B4');
CALL generate_motor_bom('AIR80A2_ZH');
CALL generate_motor_bom('AIR90L2_ZH');
CALL generate_motor_bom('AIR90L2_RZ');

-- Удаление процедуры после использования
DROP PROCEDURE IF EXISTS generate_motor_bom;

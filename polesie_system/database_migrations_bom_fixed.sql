-- Migration: Add Bill of Materials (BOM) for all products
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28
-- Fixed version: No duplicates, logical material distribution

USE polesie_electromash;

-- Clear existing BOM data if any
DELETE FROM product_bom_items;
DELETE FROM product_bom;

-- ============================================
-- МАТЕРИАЛЫ (4 продукта) - сырье для переплавки/продажи
-- Эти продукты являются сами материалами, поэтому у них нет BOM
-- ============================================

-- MAT-AL-AB87: Алюминий вторичный в чушках АВ87 (сам материал, BOM не нужен)
-- MAT-AL-AB87F: Алюминий вторичный гранулированный АВ87Ф (сам материал, BOM не нужен)
-- MAT-CI-L4: Чугун литейный передельный Л4 (сам материал, BOM не нужен)
-- MAT-CI-L5: Чугун литейный передельный Л5 (сам материал, BOM не нужен)

-- ============================================
-- ЧУГУННОЕ ЛИТЬЕ (13 продуктов)
-- Материалы: чугун MAT-CI-L4 или MAT-CI-L5, краска, этикетка, упаковка
-- ============================================

-- CI-GR-RU2: Колосниковая решетка РУ-2 (вес 3.5 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки РУ-2', 1
FROM products WHERE product_code = 'CI-GR-RU2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 4.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.15
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-GR-RU3: Колосниковая решетка РУ-3 (вес 5.5 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки РУ-3', 1
FROM products WHERE product_code = 'CI-GR-RU3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 6.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-GR-RU4: Колосниковая решетка РУ-4 (вес 6.0 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки РУ-4', 1
FROM products WHERE product_code = 'CI-GR-RU4' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 6.5
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-GR-RD3: Колосниковая решетка РД-3 (вес 2.2 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки РД-3', 1
FROM products WHERE product_code = 'CI-GR-RD3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 2.5
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-GR-RD6K: Колосниковая решетка РД-6К (вес 5.2 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки РД-6К', 1
FROM products WHERE product_code = 'CI-GR-RD6K' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 5.7
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.18
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-GR-57L: Колосниковая решетка 57Л (вес 6.5 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки 57Л', 1
FROM products WHERE product_code = 'CI-GR-57L' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 7.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.22
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-GR-001: Решетка 001 (вес 14.2 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки 001', 1
FROM products WHERE product_code = 'CI-GR-001' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 15.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.35
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-GR-ANIM: Решетка животноводческая (вес 20.0 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки животноводческой', 1
FROM products WHERE product_code = 'CI-GR-ANIM' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 21.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.45
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-DR-GRILL: Решетка дождеприемника
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки дождеприемника', 1
FROM products WHERE product_code = 'CI-DR-GRILL' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 25.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.5
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-DR-ASSY: Дождеприемник в сборе (вес 105 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для дождеприемника в сборе', 1
FROM products WHERE product_code = 'CI-DR-ASSY' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 110.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.5
        WHEN m.sku = 'MAT-LABEL-026' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-STRECH-017');

-- CI-MH-L-V15: Люк легкий типа Л (В15) (вес 71.9 кг)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для люка легкого Л', 1
FROM products WHERE product_code = 'CI-MH-L-V15' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 75.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 1.0
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-STRECH-017');

-- CI-FL-PLATE: Плита половая
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для плиты половой', 1
FROM products WHERE product_code = 'CI-FL-PLATE' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L4' THEN 30.0
        WHEN m.sku = 'MAT-PAINT-023' THEN 0.6
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016');

-- CI-BALLS: Цильпебсы, шары мелющие
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для цильпебсов', 1
FROM products WHERE product_code = 'CI-BALLS' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-CI-L5' THEN 50.0
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.5
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        ELSE 0
    END AS quantity,
    'кг',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L5', 'MAT-CONSERV-018', 'MAT-PKG-STRECH-017');

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ - СЕРИЯ AIR (общепромышленные)
-- Используем все материалы из materials_air80_insert.sql + дополнительные
-- ============================================

-- AIR71A2: Электродвигатель 0.55 кВт, 3000 об/мин
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двигателя AIR71A2', 1
FROM products WHERE product_code = 'AIR71A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 1.2
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 24
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.05
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.3
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.4
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- AIR71B2: Электродвигатель 0.75 кВт, 3000 об/мин
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двигателя AIR71B2', 1
FROM products WHERE product_code = 'AIR71B2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 1.4
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 24
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.05
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.3
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.4
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- AIR80A2: Электродвигатель 1.5 кВт, 3000 об/мин
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двигателя AIR80A2', 1
FROM products WHERE product_code = 'AIR80A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 1.8
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 32
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.06
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.4
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.5
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- AIR80B2: Электродвигатель 2.2 кВт, 3000 об/мин
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двигателя AIR80B2', 1
FROM products WHERE product_code = 'AIR80B2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.2
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 36
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.07
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.45
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.55
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER () AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- ============================================
-- ОСТАЛЬНЫЕ ДВИГАТЕЛИ (упрощенная генерация через шаблон)
-- ============================================

-- Создаем BOM для всех остальных двигателей AIR серий
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя', 1
FROM products
WHERE product_code LIKE 'AIR%' 
  AND product_code NOT IN ('AIR71A2', 'AIR71B2', 'AIR80A2', 'AIR80B2')
  AND product_code NOT LIKE 'AIRS%' 
  AND product_code NOT LIKE 'AIRE%' 
  AND product_code NOT LIKE 'AIRP%' 
  AND product_code NOT LIKE '2AIR%' 
  AND product_code NOT LIKE 'AIRCH%' 
  AND product_code NOT LIKE 'AIRV%';

-- Добавляем материалы для всех двигателей AIR (шаблонная спецификация)
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.0
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 40
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.08
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.5
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.6
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- ============================================
-- ДВИГАТЕЛИ AIRS (повышенное скольжение)
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двигателя AIRS (повышенное скольжение)', 1
FROM products
WHERE product_code LIKE 'AIRS%';

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.0
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 40
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.08
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.5
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.6
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- ============================================
-- ДВИГАТЕЛИ AIRE (однофазные 220В)
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для однофазного двигателя AIRE', 1
FROM products
WHERE product_code LIKE 'AIRE%';

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.2
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 40
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.08
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        -- Конденсатор для однофазных двигателей (используем терминал как замену)
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 2
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.5
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.6
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- ============================================
-- ДВИГАТЕЛИ AIRP (для птицефабрик)
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двигателя AIRP (птицефабрики)', 1
FROM products
WHERE product_code LIKE 'AIRP%';

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.0
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 40
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.1
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.6
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.7
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.15
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- ============================================
-- ДВИГАТЕЛИ 2AIR (двухскоростные)
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двухскоростного двигателя 2AIR', 1
FROM products
WHERE product_code LIKE '2AIR%';

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.5
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 48
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.08
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.5
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.6
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- ============================================
-- ДВИГАТЕЛИ AIRCH (железнодорожные виброустойчивые)
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для двигателя AIRCH (железнодорожные)', 1
FROM products
WHERE product_code LIKE 'AIRCH%';

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.0
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 40
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.1
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.5
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.6
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.15
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

-- ============================================
-- ДВИГАТЕЛИ AIRV (встроенные)
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для встроенного двигателя AIRV', 1
FROM products
WHERE product_code LIKE 'AIRV%';

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.0
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 40
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.08
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.3
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.4
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.1
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-CONSERV-018'
  );

-- ============================================
-- Специальные двигатели (ZH, RZ)
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для специального двигателя', 1
FROM products
WHERE (product_code LIKE '%_ZH' OR product_code LIKE '%_RZ')
  AND product_code NOT LIKE 'AIRS%' 
  AND product_code NOT LIKE 'AIRE%' 
  AND product_code NOT LIKE 'AIRP%' 
  AND product_code NOT LIKE '2AIR%' 
  AND product_code NOT LIKE 'AIRCH%' 
  AND product_code NOT LIKE 'AIRV%';

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id,
    CASE
        WHEN m.sku = 'MAT-AIR80-STATOR-001' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-002' THEN 1
        WHEN m.sku = 'MAT-AIR80-STATOR-003' THEN 2.0
        WHEN m.sku = 'MAT-AIR80-STATOR-004' THEN 40
        WHEN m.sku = 'MAT-AIR80-ROTOR-005' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-006' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-007' THEN 1
        WHEN m.sku = 'MAT-AIR80-ROTOR-008' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-009' THEN 1
        WHEN m.sku = 'MAT-AIR80-SHIELD-010' THEN 1
        WHEN m.sku = 'MAT-AIR80-BEARING-011' THEN 2
        WHEN m.sku = 'MAT-AIR80-BEARING-012' THEN 0.1
        WHEN m.sku = 'MAT-AIR80-FAN-013' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-014' THEN 1
        WHEN m.sku = 'MAT-AIR80-FANCOV-015' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-016' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-017' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-018' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-019' THEN 1
        WHEN m.sku = 'MAT-AIR80-TERM-020' THEN 1
        WHEN m.sku = 'MAT-AIR80-FAST-023' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-024' THEN 4
        WHEN m.sku = 'MAT-AIR80-FAST-025' THEN 8
        WHEN m.sku = 'MAT-AIR80-PAINT-021' THEN 0.5
        WHEN m.sku = 'MAT-AIR80-PAINT-022' THEN 0.6
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 0.15
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (PARTITION BY pb.id ORDER BY m.id) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0'
  AND m.is_active = 1
  AND m.sku IN (
    'MAT-AIR80-STATOR-001','MAT-AIR80-STATOR-002','MAT-AIR80-STATOR-003','MAT-AIR80-STATOR-004',
    'MAT-AIR80-ROTOR-005','MAT-AIR80-ROTOR-006','MAT-AIR80-ROTOR-007','MAT-AIR80-ROTOR-008',
    'MAT-AIR80-SHIELD-009','MAT-AIR80-SHIELD-010',
    'MAT-AIR80-BEARING-011','MAT-AIR80-BEARING-012',
    'MAT-AIR80-FAN-013','MAT-AIR80-FANCOV-014','MAT-AIR80-FANCOV-015',
    'MAT-AIR80-TERM-016','MAT-AIR80-TERM-017','MAT-AIR80-TERM-018','MAT-AIR80-TERM-019','MAT-AIR80-TERM-020',
    'MAT-AIR80-FAST-023','MAT-AIR80-FAST-024','MAT-AIR80-FAST-025',
    'MAT-AIR80-PAINT-021','MAT-AIR80-PAINT-022',
    'MAT-LABEL-026','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018'
  );

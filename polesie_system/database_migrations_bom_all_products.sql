-- Migration: Add Bill of Materials (BOM) for all 91 products
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28
-- Compatible with MySQL 5.7+ (no window functions)

USE polesie_electromash;

-- Clear existing BOM data if any
DELETE FROM product_bom_items;
DELETE FROM product_bom;

-- ============================================
-- МАТЕРИАЛЫ (4 продукта) - сырье для переплавки/продажи
-- ============================================

-- MAT-AL-AB87: Алюминий вторичный в чушках АВ87
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для алюминия вторичного АВ87', 1 
FROM products WHERE product_code = 'MAT-AL-AB87' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-CONSERV-018');

-- MAT-AL-AB87F: Алюминий вторичный гранулированный АВ87Ф
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для алюминия гранулированного АВ87Ф', 1 
FROM products WHERE product_code = 'MAT-AL-AB87F' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-PKG-BOARD-016', 'MAT-PKG-STRECH-017');

-- MAT-CI-L4: Чугун литейный передельный Л4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для чугуна Л4', 1 
FROM products WHERE product_code = 'MAT-CI-L4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku = 'MAT-CONSERV-018';

-- MAT-CI-L5: Чугун литейный передельный Л5
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для чугуна Л5', 1 
FROM products WHERE product_code = 'MAT-CI-L5' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku = 'MAT-CONSERV-018';

-- ============================================
-- ЧУГУННОЕ ЛИТЬЕ (13 продуктов)
-- ============================================

-- CI-GR-RU2: Колосниковая решетка РУ-2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РУ-2', 1 
FROM products WHERE product_code = 'CI-GR-RU2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-GR-RU3: Колосниковая решетка РУ-3
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РУ-3', 1 
FROM products WHERE product_code = 'CI-GR-RU3' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-GR-RU4: Колосниковая решетка РУ-4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РУ-4', 1 
FROM products WHERE product_code = 'CI-GR-RU4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 7
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-GR-RD3: Колосниковая решетка РД-3
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РД-3', 1 
FROM products WHERE product_code = 'CI-GR-RD3' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 3
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-GR-RD6K: Колосниковая решетка РД-6К
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РД-6К', 1 
FROM products WHERE product_code = 'CI-GR-RD6K' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-GR-57L: Колосниковая решетка 57Л
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки 57Л', 1 
FROM products WHERE product_code = 'CI-GR-57L' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 7
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-GR-001: Решетка 001
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки 001', 1 
FROM products WHERE product_code = 'CI-GR-001' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 15
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-GR-ANIM: Решетка животноводческая
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки животноводческой', 1 
FROM products WHERE product_code = 'CI-GR-ANIM' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 21
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-DR-GRILL: Решетка дождеприемника
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки дождеприемника', 1 
FROM products WHERE product_code = 'CI-DR-GRILL' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 26
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-DR-ASSY: Дождеприемник в сборе
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для дождеприемника в сборе', 1 
FROM products WHERE product_code = 'CI-DR-ASSY' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 106
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 4
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-FAST-BOLT-012');

-- CI-MH-L-V15: Люк легкий типа Л (В15)
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для люка легкого типа Л', 1 
FROM products WHERE product_code = 'CI-MH-L-V15' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 73
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-FL-PLATE: Плита половая
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для плиты половой', 1 
FROM products WHERE product_code = 'CI-FL-PLATE' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 19
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-PAINT-023', 'MAT-LABEL-026');

-- CI-BALLS: Цильпебсы, шары мелющие
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для цильпебсов', 1 
FROM products WHERE product_code = 'CI-BALLS' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 11
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-CI-L4', 'MAT-CONSERV-018');

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ ОБЩЕПРОМЫШЛЕННЫЕ АИР (30 продуктов)
-- ============================================

-- AIR71A2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR71A2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 25
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR71B2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR71B2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 28
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR71A4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR71A4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 26
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR71B4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR71B4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 29
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR71A6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR71A6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 27
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR71B6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR71B6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 30
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR80A2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR80A2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 32
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR80B2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR80B2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 35
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR80A4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR80A4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 33
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR80B4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR80B4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 36
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR80A6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR80A6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 34
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
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR80B6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR80B6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 37
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 5
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 1
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 1
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 2
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR90L2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR90L2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 40
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR90LB2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR90LB2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 45
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR90L4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR90L4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 42
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR90LB4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR90LB4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 47
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR90L6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR90L6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 44
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR90LA8
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR90LA8' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 46
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 6
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR90LB8
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR90LB8' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR100S2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR100S2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 52
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR100S4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR100S4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 54
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR100L4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR100L4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 58
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR100L6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR100L6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 60
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR100L2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR100L2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 56
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR112M2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR112M2' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 65
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 10
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 4
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR112M4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR112M4' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 67
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 10
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 4
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR112MA6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR112MA6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 62
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 4
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR112MB6
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR112MB6' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 68
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 10
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 4
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR112MA8
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR112MA8' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 64
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 4
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- AIR112MB8
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code = 'AIR112MB8' LIMIT 1;

SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 70
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 11
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 4
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- ============================================
-- ОСТАЛЬНЫЕ ДВИГАТЕЛИ (AIRS, AIRE, AIRP, 2AIR, AIRCH, AIRV) - 41 продукт
-- Для краткости используем аналогичные BOM с небольшими вариациями
-- ============================================

-- Пример для двигателей AIRS (повышенное скольжение) - 15 продуктов
-- AIRS71A2, AIRS71B2, AIRS80A2, AIRS80B2, AIRS90L2, AIRS90LB2, AIRS100S2, AIRS100L2, AIRS112M2, AIRS112MA6, AIRS112MB6, AIRS112MA8, AIRS112MB8, AIRS132S2, AIRS132M2

-- Создадим BOM для всех оставшихся продуктов через цикл в одном запросе
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя', 1 
FROM products 
WHERE product_code IN (
    -- AIRS series
    'AIRS71A2','AIRS71B2','AIRS80A2','AIRS80B2','AIRS90L2','AIRS90LB2','AIRS100S2','AIRS100L2','AIRS112M2','AIRS112MA6','AIRS112MB6','AIRS112MA8','AIRS112MB8','AIRS132S2','AIRS132M2',
    -- AIRE series (однофазные)
    'AIRE71A2','AIRE71B2','AIRE80A2','AIRE80B2','AIRE90L2','AIRE90LB2','AIRE100S2','AIRE100L2','AIRE112M2','AIRE112MA6','AIRE112MB6','AIRE112MA8','AIRE112MB8','AIRE132S2','AIRE132M2',
    -- AIRP series (птицефабрики)
    'AIRP80A2','AIRP80B2','AIRP90L2',
    -- 2AIR series (двухскоростные)
    '2AIR90L4/2','2AIR90LB4/2','2AIR100L4/2',
    -- AIRCH series (железнодорожные)
    'AIRCH80A2','AIRCH80B2',
    -- AIRV series (встроенные)
    'AIRV90L2','AIRV90LB2','AIRV100L2'
);

-- Добавим материалы для всех оставшихся двигателей (усредненная спецификация)
SET @row_num := 0;
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1
        WHEN m.sku = 'MAT-PAINT-023' THEN 2
        WHEN m.sku = 'MAT-LABEL-026' THEN 1
        WHEN m.sku = 'MAT-CABLE-024' THEN 2
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1
        ELSE 0
    END AS quantity,
    'шт',
    (@row_num := @row_num + 1) AS sequence_order
FROM product_bom pb
CROSS JOIN materials m
CROSS JOIN (SELECT @row_num := 0) r
WHERE pb.bom_version = '1.0' 
  AND m.is_active = 1
  AND m.sku IN ('MAT-STEEL-EL-006','MAT-WIRE-CU-007','MAT-INS-PAPER-008','MAT-INS-FILM-009','MAT-VAR-IMP-010','MAT-BRG-6205-011','MAT-FAST-BOLT-012','MAT-RUB-SEAL-013','MAT-FAN-COOL-014','MAT-TERM-BOX-015','MAT-PAINT-023','MAT-LABEL-026','MAT-CABLE-024','MAT-TERMINAL-025','MAT-PKG-BOARD-016','MAT-PKG-STRECH-017','MAT-CONSERV-018');

-- Migration: Add Bill of Materials (BOM) for all 91 products
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28

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

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 1  -- 1 чушка литейного чугуна для формы
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки для консервации
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- MAT-AL-AB87F: Алюминий вторичный гранулированный АВ87Ф
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для алюминия гранулированного АВ87Ф', 1 
FROM products WHERE product_code = 'MAT-AL-AB87F' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1  -- 1 упаковка гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- MAT-CI-L4: Чугун литейный передельный Л4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для чугуна Л4', 1 
FROM products WHERE product_code = 'MAT-CI-L4' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки для консервации
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- MAT-CI-L5: Чугун литейный передельный Л5
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для чугуна Л5', 1 
FROM products WHERE product_code = 'MAT-CI-L5' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки для консервации
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЧУГУННОЕ ЛИТЬЕ (13 продуктов)
-- ============================================

-- CI-GR-RU2: Колосниковая решетка РУ-2
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РУ-2', 1 
FROM products WHERE product_code = 'CI-GR-RU2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 4  -- 4 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-GR-RU3: Колосниковая решетка РУ-3
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РУ-3', 1 
FROM products WHERE product_code = 'CI-GR-RU3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6  -- 6 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-GR-RU4: Колосниковая решетка РУ-4
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РУ-4', 1 
FROM products WHERE product_code = 'CI-GR-RU4' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 7  -- 7 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-GR-RD3: Колосниковая решетка РД-3
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РД-3', 1 
FROM products WHERE product_code = 'CI-GR-RD3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 3  -- 3 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-GR-RD6K: Колосниковая решетка РД-6К
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки РД-6К', 1 
FROM products WHERE product_code = 'CI-GR-RD6K' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 6  -- 6 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-GR-57L: Колосниковая решетка 57Л
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки 57Л', 1 
FROM products WHERE product_code = 'CI-GR-57L' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 7  -- 7 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-GR-001: Решетка 001
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки 001', 1 
FROM products WHERE product_code = 'CI-GR-001' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 15  -- 15 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-GR-ANIM: Решетка животноводческая
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки животноводческой', 1 
FROM products WHERE product_code = 'CI-GR-ANIM' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 21  -- 21 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-DR-GRILL: Решетка дождеприемника
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для решетки дождеприемника', 1 
FROM products WHERE product_code = 'CI-DR-GRILL' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 26  -- 26 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-DR-ASSY: Дождеприемник в сборе
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для дождеприемника в сборе', 1 
FROM products WHERE product_code = 'CI-DR-ASSY' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 106  -- 106 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 2  -- 2 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 4  -- 4 болта крепежных
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-MH-L-V15: Люк легкий типа Л (В15)
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для люка легкого типа Л', 1 
FROM products WHERE product_code = 'CI-MH-L-V15' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 73  -- 73 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 2  -- 2 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-FL-PLATE: Плита половая
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для плиты половой', 1 
FROM products WHERE product_code = 'CI-FL-PLATE' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 19  -- 19 кг чугуна Л4
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- CI-BALLS: Цильпебсы, шары мелющие
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для цильпебсов', 1 
FROM products WHERE product_code = 'CI-BALLS' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-CI-L4' THEN 11  -- 11 кг чугуна Л4
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ ОБЩЕПРОМЫШЛЕННЫЕ АИР (категория 3) - 24 продукта
-- ============================================

-- Generic BOM for all AIR motors (71-112 frame)
-- Each motor needs: steel sheets, copper wire, insulation, varnish, bearings, fasteners, fan, terminal box, paint, packaging

SET @air_motor_bom_products = 'AIR71A2,AIR71B2,AIR71A4,AIR71B4,AIR71A6,AIR71B6,AIR80A2,AIR80B2,AIR80A4,AIR80B4,AIR80A6,AIR80B6,AIR90L2,AIR90LB2,AIR90L4,AIR90LB4,AIR90L6,AIR90LA8,AIR90LB8,AIR100S2,AIR100S4,AIR100L4,AIR100L6,AIR100L2,AIR112M2,AIR112M4,AIR112MA6,AIR112MB6,AIR112MA8,AIR112MB8';

-- Создадим BOM для каждого двигателя АИР
INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя АИР', 1 
FROM products WHERE product_code IN ('AIR71A2','AIR71B2','AIR71A4','AIR71B4','AIR71A6','AIR71B6','AIR80A2','AIR80B2','AIR80A4','AIR80B4','AIR80A6','AIR80B6','AIR90L2','AIR90LB2','AIR90L4','AIR90LB4','AIR90L6','AIR90LA8','AIR90LB8','AIR100S2','AIR100S4','AIR100L4','AIR100L6','AIR100L2','AIR112M2','AIR112M4','AIR112MA6','AIR112MB6','AIR112MA8','AIR112MB8');

-- Добавим материалы для всех двигателей АИР
INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50  -- 50 кг стали электротехнической
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8  -- 8 кг медного провода (зависит от мощности)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2  -- 2 кг электрокартона
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1  -- 1 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3  -- 3 литра лака пропиточного
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12  -- 12 болтов крепежных
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4  -- 4 уплотнителя резиновых
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1  -- 1 крыльчатка вентилятора
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1  -- 1 корпус клеммной коробки
        WHEN m.sku = 'MAT-PAINT-023' THEN 2  -- 2 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 2  -- 2 метра кабеля
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6  -- 6 клемм винтовых
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3  -- 3 м² гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%АИР%' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ С ПОВЫШЕННЫМ СКОЛЬЖЕНИЕМ AIRS (категория 4) - 15 продуктов
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя AIRS', 1 
FROM products WHERE product_code IN ('AIRS80A2','AIRS80B2','AIRS80A4','AIRS80B4','AIRS80A6','AIRS80B6','AIRS90L2','AIRS90LB2','AIRS90L4','AIRS90LB4','AIRS90L6','AIRS90LA8','AIRS90LB8','AIRS100S2','AIRS100S4');

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 55  -- 55 кг стали (усиленная конструкция)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 10  -- 10 кг медного провода (увеличенное сечение)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2  -- 2 кг электрокартона
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1  -- 1 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4  -- 4 литра лака (усиленная пропитка)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12  -- 12 болтов крепежных
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4  -- 4 уплотнителя резиновых
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1  -- 1 крыльчатка вентилятора
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1  -- 1 корпус клеммной коробки
        WHEN m.sku = 'MAT-PAINT-023' THEN 2  -- 2 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 2  -- 2 метра кабеля
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6  -- 6 клемм винтовых
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3  -- 3 м² гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%AIRS%' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ ОДНОФАЗНЫЕ AIRE (категория 5) - 15 продуктов
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Стандартная спецификация для электродвигателя AIRE', 1 
FROM products WHERE product_code IN ('AIRE71A2','AIRE71B2','AIRE71C2','AIRE71A4','AIRE71B4','AIRE71C4','AIRE80A2','AIRE80B2','AIRE80C2','AIRE80C2_S6','AIRE80D2','AIRE80A4','AIRE80B4','AIRE80C4','AIRE90L2');

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 45  -- 45 кг стали электротехнической
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 7  -- 7 кг медного провода
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2  -- 2 кг электрокартона
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1  -- 1 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3  -- 3 литра лака пропиточного
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 10  -- 10 болтов крепежных
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4  -- 4 уплотнителя резиновых
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1  -- 1 крыльчатка вентилятора
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1  -- 1 корпус клеммной коробки
        WHEN m.sku = 'MAT-PAINT-023' THEN 2  -- 2 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 2  -- 2 метра кабеля
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 4  -- 4 клеммы винтовые
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3  -- 3 м² гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%AIRE%' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ ДЛЯ ПТИЦЕФАБРИК AIRP (категория 6) - 3 продукта
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецспецификация для электродвигателя AIRP (агрессивные среды)', 1 
FROM products WHERE product_code IN ('AIRP80A6','AIRP80B6','AIRP80C6');

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50  -- 50 кг стали электротехнической
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8  -- 8 кг медного провода
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 3  -- 3 кг электрокартона (усиленная изоляция)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2  -- 2 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5  -- 5 литров лака (химстойкое покрытие)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12  -- 12 болтов крепежных (нержавеющие)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 6  -- 6 уплотнителей резиновых (химстойкие)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1  -- 1 крыльчатка вентилятора
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1  -- 1 корпус клеммной коробки
        WHEN m.sku = 'MAT-PAINT-023' THEN 3  -- 3 кг краски порошковой (химстойкой)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 2  -- 2 метра кабеля
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6  -- 6 клемм винтовых
        WHEN m.sku = 'MAT-SILICONE-028' THEN 2  -- 2 тюбика герметика силиконового
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3  -- 3 м² гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%AIRP%' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ ДВУХСКОРОСТНЫЕ 2AIR (категория 7) - 3 продукта
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для двухскоростного электродвигателя 2AIR', 1 
FROM products WHERE product_code IN ('2AIR80A2','2AIR80B2','2AIR90L2');

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 60  -- 60 кг стали (две обмотки)
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 12  -- 12 кг медного провода (две обмотки)
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 3  -- 3 кг электрокартона
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2  -- 2 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 5  -- 5 литров лака
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 14  -- 14 болтов крепежных
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4  -- 4 уплотнителя резиновых
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1  -- 1 крыльчатка вентилятора
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1  -- 1 корпус клеммной коробки (увеличенный)
        WHEN m.sku = 'MAT-PAINT-023' THEN 2  -- 2 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 3  -- 3 метра кабеля
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 9  -- 9 клемм винтовых
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 4  -- 4 м² гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%2AIR%' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ ЖЕЛЕЗНОДОРОЖНЫЕ AIRCH (категория 8) - 2 продукта
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецспецификация для железнодорожного электродвигателя AIRCH', 1 
FROM products WHERE product_code IN ('AIRCH80B4','AIRCH80B6');

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 55  -- 55 кг стали электротехнической
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 9  -- 9 кг медного провода
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 3  -- 3 кг электрокартона (вибростойкая)
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 2  -- 2 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4  -- 4 литра лака (вибростойкая пропитка)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника (вибростойкие)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 14  -- 14 болтов крепежных (усиленные)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 6  -- 6 уплотнителей резиновых (вибростойкие)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1  -- 1 крыльчатка вентилятора
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1  -- 1 корпус клеммной коробки
        WHEN m.sku = 'MAT-PAINT-023' THEN 3  -- 3 кг краски порошковой (износостойкой)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 2  -- 2 метра кабеля
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6  -- 6 клемм винтовых
        WHEN m.sku = 'MAT-RUBBER-FOOT-027' THEN 4  -- 4 опоры резиновые антивибрационные
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3  -- 3 м² гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%AIRCH%' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ ВСТРОЕННЫЕ AIRV (категория 9) - 3 продукта
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецификация для встроенного электродвигателя AIRV', 1 
FROM products WHERE product_code IN ('AIRV100A2','AIRV100A4','AIRV100B4');

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50  -- 50 кг стали электротехнической
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8  -- 8 кг медного провода
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2  -- 2 кг электрокартона
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1  -- 1 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 3  -- 3 литра лака пропиточного
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 8  -- 8 болтов крепежных (для встройки)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 4  -- 4 уплотнителя резиновых
        WHEN m.sku = 'MAT-PAINT-023' THEN 1  -- 1 кг краски порошковой (только торцы)
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 1  -- 1 метр кабеля (короткие выводы)
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6  -- 6 клемм винтовых
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%AIRV%' AND m.is_active = 1
HAVING quantity > 0;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ СПЕЦИСПОЛНЕНИЯ (категория 10) - 3 продукта
-- ============================================

INSERT INTO product_bom (product_id, bom_version, description, is_active) 
SELECT id, '1.0', 'Спецспецификация для электродвигателя специсполнения', 1 
FROM products WHERE product_code IN ('AIR80A2_ZH','AIR90L2_ZH','AIR90L2_RZ');

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku = 'MAT-STEEL-EL-006' THEN 50  -- 50 кг стали электротехнической
        WHEN m.sku = 'MAT-WIRE-CU-007' THEN 8  -- 8 кг медного провода
        WHEN m.sku = 'MAT-INS-PAPER-008' THEN 2  -- 2 кг электрокартона
        WHEN m.sku = 'MAT-INS-FILM-009' THEN 1  -- 1 кг пленки ПЭТФ
        WHEN m.sku = 'MAT-VAR-IMP-010' THEN 4  -- 4 литра лака (влагостойкая пропитка)
        WHEN m.sku = 'MAT-BRG-6205-011' THEN 2  -- 2 подшипника (нержавеющие)
        WHEN m.sku = 'MAT-FAST-BOLT-012' THEN 12  -- 12 болтов крепежных (нержавеющие)
        WHEN m.sku = 'MAT-RUB-SEAL-013' THEN 6  -- 6 уплотнителей резиновых (торцевые)
        WHEN m.sku = 'MAT-FAN-COOL-014' THEN 1  -- 1 крыльчатка вентилятора
        WHEN m.sku = 'MAT-TERM-BOX-015' THEN 1  -- 1 корпус клеммной коробки
        WHEN m.sku = 'MAT-PAINT-023' THEN 2  -- 2 кг краски порошковой
        WHEN m.sku = 'MAT-LABEL-026' THEN 1  -- 1 табличка идентификационная
        WHEN m.sku = 'MAT-CABLE-024' THEN 2  -- 2 метра кабеля
        WHEN m.sku = 'MAT-TERMINAL-025' THEN 6  -- 6 клемм винтовых
        WHEN m.sku = 'MAT-SILICONE-028' THEN 2  -- 2 тюбика герметика силиконового
        WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 3  -- 3 м² гофрокартона
        WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 1  -- 1 рулон стрейч-пленки
        WHEN m.sku = 'MAT-CONSERV-018' THEN 1  -- 1 упаковка смазки консервационной
        ELSE 0
    END AS quantity,
    'шт',
    ROW_NUMBER() OVER (ORDER BY m.sku)
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' AND pb.description LIKE '%специсполнения%' AND m.is_active = 1
HAVING quantity > 0;

-- Update sequence_order to be sequential within each BOM
UPDATE product_bom_items pbi
JOIN (
    SELECT id, ROW_NUMBER() OVER (PARTITION BY bom_id ORDER BY sequence_order, material_id) as new_seq
    FROM product_bom_items
) ranked ON pbi.id = ranked.id
SET pbi.sequence_order = ranked.new_seq;

-- Verify the results
SELECT 
    p.product_code,
    p.product_name,
    COUNT(pbi.id) as material_count
FROM products p
LEFT JOIN product_bom pb ON p.id = pb.product_id AND pb.is_active = 1
LEFT JOIN product_bom_items pbi ON pb.id = pbi.bom_id
GROUP BY p.id, p.product_code, p.product_name
ORDER BY p.product_code;

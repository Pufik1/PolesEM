-- Migration: Add Bill of Materials (BOM) for all products
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-28
-- Realistic material quantities based on engineering specifications

USE polesie_electromash;

-- ============================================
-- ЭЛЕКТРОДВИГАТЕЛИ СЕРИИ АИР (AIR)
-- ============================================

-- АВН 71-2 (0.55 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР71А2 (0.55 кВт)', 1
FROM products WHERE product_code = 'AIR71A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%STATOR-001%' THEN 1
        WHEN m.sku LIKE '%STATOR-002%' THEN 1
        WHEN m.sku LIKE '%STATOR-003%' THEN 2.5
        WHEN m.sku LIKE '%STATOR-004%' THEN 36
        WHEN m.sku LIKE '%ROTOR-005%' THEN 1
        WHEN m.sku LIKE '%ROTOR-006%' THEN 1
        WHEN m.sku LIKE '%ROTOR-007%' THEN 1
        WHEN m.sku LIKE '%ROTOR-008%' THEN 1
        WHEN m.sku LIKE '%SHIELD-009%' THEN 1
        WHEN m.sku LIKE '%SHIELD-010%' THEN 1
        WHEN m.sku LIKE '%BEARING-011%' THEN 2
        WHEN m.sku LIKE '%BEARING-012%' THEN 0.15
        WHEN m.sku LIKE '%FAN-013%' THEN 1
        WHEN m.sku LIKE '%FANCOV-014%' THEN 1
        WHEN m.sku LIKE '%FANCOV-015%' THEN 1
        WHEN m.sku LIKE '%TERM-016%' THEN 1
        WHEN m.sku LIKE '%TERM-017%' THEN 1
        WHEN m.sku LIKE '%TERM-018%' THEN 1
        WHEN m.sku LIKE '%TERM-019%' THEN 2
        WHEN m.sku LIKE '%TERM-020%' THEN 2
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.8
        WHEN m.sku LIKE '%PAINT-022%' THEN 0.6
        WHEN m.sku LIKE '%FAST-023%' THEN 4
        WHEN m.sku LIKE '%FAST-024%' THEN 4
        WHEN m.sku LIKE '%FAST-025%' THEN 8
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АВН 71-2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%STATOR%' OR m.sku LIKE '%ROTOR%' OR m.sku LIKE '%SHIELD%' 
     OR m.sku LIKE '%BEARING%' OR m.sku LIKE '%FAN%' OR m.sku LIKE '%TERM%' 
     OR m.sku LIKE '%PAINT%' OR m.sku LIKE '%FAST%')
ORDER BY m.sku
LIMIT 25;

-- АИС 80А2 (1.5 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР80А2 (1.5 кВт)', 1
FROM products WHERE product_code = 'AIR80A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%STATOR-001%' THEN 1
        WHEN m.sku LIKE '%STATOR-002%' THEN 1
        WHEN m.sku LIKE '%STATOR-003%' THEN 4.2
        WHEN m.sku LIKE '%STATOR-004%' THEN 48
        WHEN m.sku LIKE '%ROTOR-005%' THEN 1
        WHEN m.sku LIKE '%ROTOR-006%' THEN 1
        WHEN m.sku LIKE '%ROTOR-007%' THEN 1
        WHEN m.sku LIKE '%ROTOR-008%' THEN 1
        WHEN m.sku LIKE '%SHIELD-009%' THEN 1
        WHEN m.sku LIKE '%SHIELD-010%' THEN 1
        WHEN m.sku LIKE '%BEARING-011%' THEN 2
        WHEN m.sku LIKE '%BEARING-012%' THEN 0.2
        WHEN m.sku LIKE '%FAN-013%' THEN 1
        WHEN m.sku LIKE '%FANCOV-014%' THEN 1
        WHEN m.sku LIKE '%FANCOV-015%' THEN 1
        WHEN m.sku LIKE '%TERM-016%' THEN 1
        WHEN m.sku LIKE '%TERM-017%' THEN 1
        WHEN m.sku LIKE '%TERM-018%' THEN 1
        WHEN m.sku LIKE '%TERM-019%' THEN 2
        WHEN m.sku LIKE '%TERM-020%' THEN 2
        WHEN m.sku LIKE '%PAINT-021%' THEN 1.0
        WHEN m.sku LIKE '%PAINT-022%' THEN 0.8
        WHEN m.sku LIKE '%FAST-023%' THEN 4
        WHEN m.sku LIKE '%FAST-024%' THEN 4
        WHEN m.sku LIKE '%FAST-025%' THEN 8
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИС 80А2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%STATOR%' OR m.sku LIKE '%ROTOR%' OR m.sku LIKE '%SHIELD%' 
     OR m.sku LIKE '%BEARING%' OR m.sku LIKE '%FAN%' OR m.sku LIKE '%TERM%' 
     OR m.sku LIKE '%PAINT%' OR m.sku LIKE '%FAST%')
ORDER BY m.sku
LIMIT 25;

-- АИС 80В2 (2.2 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР80В2 (2.2 кВт)', 1
FROM products WHERE product_code = 'AIR80B2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%STATOR-001%' THEN 1        -- Станина
        WHEN m.sku LIKE '%STATOR-002%' THEN 1        -- Сердечник статора
        WHEN m.sku LIKE '%STATOR-003%' THEN 5.5      -- Провод медный (кг)
        WHEN m.sku LIKE '%STATOR-004%' THEN 48       -- Изоляция пазовая (шт)
        WHEN m.sku LIKE '%ROTOR-005%' THEN 1         -- Вал ротора
        WHEN m.sku LIKE '%ROTOR-006%' THEN 1         -- Сердечник ротора
        WHEN m.sku LIKE '%ROTOR-007%' THEN 1         -- Клетка короткозамкнутая
        WHEN m.sku LIKE '%ROTOR-008%' THEN 1         -- Шпонка
        WHEN m.sku LIKE '%SHIELD-009%' THEN 1        -- Щит передний
        WHEN m.sku LIKE '%SHIELD-010%' THEN 1        -- Щит задний
        WHEN m.sku LIKE '%BEARING-011%' AND m.name LIKE '%6205%' THEN 2  -- Подшипники 6205
        WHEN m.sku LIKE '%BEARING-012%' THEN 0.25    -- Смазка (кг)
        WHEN m.sku LIKE '%FAN-013%' THEN 1           -- Крыльчатка
        WHEN m.sku LIKE '%FANCOV-014%' THEN 1        -- Корпус кожуха
        WHEN m.sku LIKE '%FANCOV-015%' THEN 1        -- Решетка защитная
        WHEN m.sku LIKE '%TERM-016%' THEN 1          -- Корпус борно
        WHEN m.sku LIKE '%TERM-017%' THEN 1          -- Крышка борно
        WHEN m.sku LIKE '%TERM-018%' THEN 1          -- Клеммная колодка
        WHEN m.sku LIKE '%TERM-019%' THEN 2          -- Вводы кабельные
        WHEN m.sku LIKE '%TERM-020%' THEN 2          -- Болт заземления
        WHEN m.sku LIKE '%PAINT-021%' THEN 1.2       -- Грунтовка (кг)
        WHEN m.sku LIKE '%PAINT-022%' THEN 1.0       -- Эмаль (кг)
        WHEN m.sku LIKE '%FAST-023%' THEN 4          -- Болты стяжные
        WHEN m.sku LIKE '%FAST-024%' THEN 4          -- Гайки
        WHEN m.sku LIKE '%FAST-025%' THEN 8          -- Шайбы
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИС 80В2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku LIKE '%AIR80%'
LIMIT 25;

-- АИС 90L2 (3.0 кВт, 3000 об/мин) - требуется новые материалы для 90 габарита
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИР90L2 (3.0 кВт)', 1
FROM products WHERE product_code = 'AIR90L2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%STATOR-001%' THEN 1        -- Станина (увеличенная)
        WHEN m.sku LIKE '%STATOR-002%' THEN 1        -- Сердечник статора (больше)
        WHEN m.sku LIKE '%STATOR-003%' THEN 7.0      -- Провод медный (кг)
        WHEN m.sku LIKE '%STATOR-004%' THEN 54       -- Изоляция пазовая (шт)
        WHEN m.sku LIKE '%ROTOR-005%' THEN 1         -- Вал ротора (больше)
        WHEN m.sku LIKE '%ROTOR-006%' THEN 1         -- Сердечник ротора (больше)
        WHEN m.sku LIKE '%ROTOR-007%' THEN 1         -- Клетка короткозамкнутая
        WHEN m.sku LIKE '%ROTOR-008%' THEN 1         -- Шпонка
        WHEN m.sku LIKE '%SHIELD-009%' THEN 1        -- Щит передний (Ø200мм)
        WHEN m.sku LIKE '%SHIELD-010%' THEN 1        -- Щит задний (Ø200мм)
        WHEN m.sku LIKE '%BEARING-011%' AND m.name LIKE '%6205%' THEN 2  -- Подшипники 6205
        WHEN m.sku LIKE '%BEARING-012%' THEN 0.3     -- Смазка (кг)
        WHEN m.sku LIKE '%FAN-013%' THEN 1           -- Крыльчатка (Ø250мм)
        WHEN m.sku LIKE '%FANCOV-014%' THEN 1        -- Корпус кожуха
        WHEN m.sku LIKE '%FANCOV-015%' THEN 1        -- Решетка защитная
        WHEN m.sku LIKE '%TERM-016%' THEN 1          -- Корпус борно (больше)
        WHEN m.sku LIKE '%TERM-017%' THEN 1          -- Крышка борно
        WHEN m.sku LIKE '%TERM-018%' THEN 1          -- Клеммная колодка
        WHEN m.sku LIKE '%TERM-019%' THEN 2          -- Вводы кабельные
        WHEN m.sku LIKE '%TERM-020%' THEN 2          -- Болт заземления
        WHEN m.sku LIKE '%PAINT-021%' THEN 1.5       -- Грунтовка (кг)
        WHEN m.sku LIKE '%PAINT-022%' THEN 1.2       -- Эмаль (кг)
        WHEN m.sku LIKE '%FAST-023%' THEN 4          -- Болты стяжные М10
        WHEN m.sku LIKE '%FAST-024%' THEN 4          -- Гайки М10
        WHEN m.sku LIKE '%FAST-025%' THEN 8          -- Шайбы 10
        ELSE 1
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для АИС 90L2'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku LIKE '%AIR80%'
LIMIT 25;

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
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 4.0  -- Чугун литейный (кг с учетом литников)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.15      -- Грунтовка черная (кг)
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
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Колосниковая решетка РУ-3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-3', 1
FROM products WHERE product_code = 'CI-GR-RU3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 6.2  -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.2       -- Грунтовка черная (кг)
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
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Колосниковая решетка РУ-4
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РУ-4', 1
FROM products WHERE product_code = 'CI-GR-RU4' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 6.8  -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.22      -- Грунтовка черная (кг)
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
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Дождеприемник в сборе
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для дождеприемника в сборе', 1
FROM products WHERE product_code = 'CI-DR-ASSY' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 115.0  -- Чугун литейный (кг с учетом корпуса и решетки)
        WHEN m.sku LIKE '%PAINT-021%' THEN 1.5       -- Грунтовка черная (кг)
        WHEN m.sku LIKE '%FAST-023%' THEN 4          -- Болты для крепления решетки
        WHEN m.sku LIKE '%FAST-024%' THEN 4          -- Гайки
        WHEN m.sku LIKE '%FAST-025%' THEN 8          -- Шайбы
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
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%' OR m.sku LIKE '%FAST%')
LIMIT 8;

-- Люк легкий типа Л (В15)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для люка легкого типа Л', 1
FROM products WHERE product_code = 'CI-MH-L-V15' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 78.0   -- Чугун литейный (кг с учетом крышки и корпуса)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.8       -- Грунтовка черная (кг)
        WHEN m.sku LIKE '%FAST-023%' THEN 2          -- Болты шарнирные
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
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%' OR m.sku LIKE '%FAST%')
LIMIT 5;

-- ============================================
-- АЛЮМИНИЕВЫЕ МАТЕРИАЛЫ (ПРОДАЖА)
-- ============================================

-- Алюминий вторичный АВ87 (чушка)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для алюминия АВ87 в чушках', 1
FROM products WHERE product_code = 'MAT-AL-AB87' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-AL-AB87'
LIMIT 1;

-- Алюминий гранулированный АВ87Ф
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для алюминия гранулированного АВ87Ф', 1
FROM products WHERE product_code = 'MAT-AL-AB87F' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-AL-AB87F'
LIMIT 1;

-- Чугун литейный Л4
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для чугуна литейного Л4', 1
FROM products WHERE product_code = 'MAT-CI-L4' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-CI-L4'
LIMIT 1;

-- Чугун литейный Л5
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для чугуна литейного Л5', 1
FROM products WHERE product_code = 'MAT-CI-L5' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-CI-L5'
LIMIT 1;

-- ============================================
-- ДОПОЛНИТЕЛЬНЫЕ МАТЕРИАЛЫ ДЛЯ ПРОЧИХ ПРОДУКТОВ
-- ============================================

-- Решетка РД-3
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки РД-3', 1
FROM products WHERE product_code = 'CI-GR-RD3' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 2.6  -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.1       -- Грунтовка черная (кг)
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
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Решетка 57Л
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для колосниковой решетки 57Л', 1
FROM products WHERE product_code = 'CI-GR-57L' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 7.2  -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.25      -- Грунтовка черная (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для 57Л (6.5 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Решетка 001
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки 001', 1
FROM products WHERE product_code = 'CI-GR-001' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 15.5  -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.4       -- Грунтовка черная (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для 001 (14.2 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Решетка животноводческая ANIM
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки животноводческой', 1
FROM products WHERE product_code = 'CI-GR-ANIM' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 22.0  -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.5       -- Грунтовка черная (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для ANIM (20.0 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Плита половая
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для плиты половой', 1
FROM products WHERE product_code = 'CI-FL-PLATE' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 8.5   -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.2       -- Грунтовка черная (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для плиты половой'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

-- Цильпебсы, шары мелющие
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для цильпебсов', 1
FROM products WHERE product_code = 'CI-BALLS' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 1.05  -- Чугун литейный (кг с учетом отходов)
        ELSE 0
    END AS quantity,
    m.unit,
    1,
    'Для цильпебсов (1 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku LIKE '%MAT-CI%'
LIMIT 2;

-- Решетка дождеприемника
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для решетки дождеприемника', 1
FROM products WHERE product_code = 'CI-DR-GRILL' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%MAT-CI-L4%' OR m.sku LIKE '%MAT-CI-L5%' THEN 35.0  -- Чугун литейный (кг)
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.5       -- Грунтовка черная (кг)
        ELSE 0
    END AS quantity,
    m.unit,
    @seq := @seq + 1,
    'Для решетки дождеприемника'
FROM product_bom pb
CROSS JOIN materials m
JOIN (SELECT @seq := 0) r
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%')
LIMIT 3;

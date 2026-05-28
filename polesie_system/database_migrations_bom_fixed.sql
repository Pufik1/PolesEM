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
SELECT id, '1.0', 'Спецификация для электродвигателя АВН 71-2 (0.55 кВт)', 1
FROM products WHERE product_code = 'AVN71-2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%STATOR-001%' THEN 1        -- Станина
        WHEN m.sku LIKE '%STATOR-002%' THEN 1        -- Сердечник статора
        WHEN m.sku LIKE '%STATOR-003%' THEN 2.5      -- Провод медный (кг)
        WHEN m.sku LIKE '%STATOR-004%' THEN 36       -- Изоляция пазовая (шт)
        WHEN m.sku LIKE '%ROTOR-005%' THEN 1         -- Вал ротора
        WHEN m.sku LIKE '%ROTOR-006%' THEN 1         -- Сердечник ротора
        WHEN m.sku LIKE '%ROTOR-007%' THEN 1         -- Клетка короткозамкнутая
        WHEN m.sku LIKE '%ROTOR-008%' THEN 1         -- Шпонка
        WHEN m.sku LIKE '%SHIELD-009%' THEN 1        -- Щит передний
        WHEN m.sku LIKE '%SHIELD-010%' THEN 1        -- Щит задний
        WHEN m.sku LIKE '%BEARING-011%' AND m.name LIKE '%6204%' THEN 2  -- Подшипники 6204
        WHEN m.sku LIKE '%BEARING-012%' THEN 0.15    -- Смазка (кг)
        WHEN m.sku LIKE '%FAN-013%' THEN 1           -- Крыльчатка
        WHEN m.sku LIKE '%FANCOV-014%' THEN 1        -- Корпус кожуха
        WHEN m.sku LIKE '%FANCOV-015%' THEN 1        -- Решетка защитная
        WHEN m.sku LIKE '%TERM-016%' THEN 1          -- Корпус борно
        WHEN m.sku LIKE '%TERM-017%' THEN 1          -- Крышка борно
        WHEN m.sku LIKE '%TERM-018%' THEN 1          -- Клеммная колодка
        WHEN m.sku LIKE '%TERM-019%' THEN 2          -- Вводы кабельные
        WHEN m.sku LIKE '%TERM-020%' THEN 2          -- Болт заземления
        WHEN m.sku LIKE '%PAINT-021%' THEN 0.8       -- Грунтовка (кг)
        WHEN m.sku LIKE '%PAINT-022%' THEN 0.6       -- Эмаль (кг)
        WHEN m.sku LIKE '%FAST-023%' THEN 4          -- Болты стяжные
        WHEN m.sku LIKE '%FAST-024%' THEN 4          -- Гайки
        WHEN m.sku LIKE '%FAST-025%' THEN 8          -- Шайбы
        ELSE 1
    END AS quantity,
    m.unit,
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для АВН 71-2'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN (
    'MAT-AIR-STATOR-001', 'MAT-AIR-STATOR-002', 'MAT-AIR-STATOR-003',
    'MAT-AIR-STATOR-004', 'MAT-AIR-ROTOR-005', 'MAT-AIR-ROTOR-006',
    'MAT-AIR-ROTOR-007', 'MAT-AIR-ROTOR-008', 'MAT-AIR-SHIELD-009',
    'MAT-AIR-SHIELD-010', 'MAT-AIR-BEARING-011', 'MAT-AIR-BEARING-012',
    'MAT-AIR-FAN-013', 'MAT-AIR-FANCOV-014', 'MAT-AIR-FANCOV-015',
    'MAT-AIR-TERM-016', 'MAT-AIR-TERM-017', 'MAT-AIR-TERM-018',
    'MAT-AIR-TERM-019', 'MAT-AIR-TERM-020', 'MAT-AIR-PAINT-021',
    'MAT-AIR-PAINT-022', 'MAT-AIR-FAST-023', 'MAT-AIR-FAST-024',
    'MAT-AIR-FAST-025'
);

-- АИС 80А2 (1.5 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИС 80А2 (1.5 кВт)', 1
FROM products WHERE product_code = 'AIS80A2' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 
    CASE 
        WHEN m.sku LIKE '%STATOR-001%' THEN 1        -- Станина
        WHEN m.sku LIKE '%STATOR-002%' THEN 1        -- Сердечник статора
        WHEN m.sku LIKE '%STATOR-003%' THEN 4.2      -- Провод медный (кг)
        WHEN m.sku LIKE '%STATOR-004%' THEN 48       -- Изоляция пазовая (шт)
        WHEN m.sku LIKE '%ROTOR-005%' THEN 1         -- Вал ротора
        WHEN m.sku LIKE '%ROTOR-006%' THEN 1         -- Сердечник ротора
        WHEN m.sku LIKE '%ROTOR-007%' THEN 1         -- Клетка короткозамкнутая
        WHEN m.sku LIKE '%ROTOR-008%' THEN 1         -- Шпонка
        WHEN m.sku LIKE '%SHIELD-009%' THEN 1        -- Щит передний
        WHEN m.sku LIKE '%SHIELD-010%' THEN 1        -- Щит задний
        WHEN m.sku LIKE '%BEARING-011%' AND m.name LIKE '%6204%' THEN 2  -- Подшипники 6204
        WHEN m.sku LIKE '%BEARING-012%' THEN 0.2     -- Смазка (кг)
        WHEN m.sku LIKE '%FAN-013%' THEN 1           -- Крыльчатка
        WHEN m.sku LIKE '%FANCOV-014%' THEN 1        -- Корпус кожуха
        WHEN m.sku LIKE '%FANCOV-015%' THEN 1        -- Решетка защитная
        WHEN m.sku LIKE '%TERM-016%' THEN 1          -- Корпус борно
        WHEN m.sku LIKE '%TERM-017%' THEN 1          -- Крышка борно
        WHEN m.sku LIKE '%TERM-018%' THEN 1          -- Клеммная колодка
        WHEN m.sku LIKE '%TERM-019%' THEN 2          -- Вводы кабельные
        WHEN m.sku LIKE '%TERM-020%' THEN 2          -- Болт заземления
        WHEN m.sku LIKE '%PAINT-021%' THEN 1.0       -- Грунтовка (кг)
        WHEN m.sku LIKE '%PAINT-022%' THEN 0.8       -- Эмаль (кг)
        WHEN m.sku LIKE '%FAST-023%' THEN 4          -- Болты стяжные
        WHEN m.sku LIKE '%FAST-024%' THEN 4          -- Гайки
        WHEN m.sku LIKE '%FAST-025%' THEN 8          -- Шайбы
        ELSE 1
    END AS quantity,
    m.unit,
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для АИС 80А2'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN (
    'MAT-AIR-STATOR-001', 'MAT-AIR-STATOR-002', 'MAT-AIR-STATOR-003',
    'MAT-AIR-STATOR-004', 'MAT-AIR-ROTOR-005', 'MAT-AIR-ROTOR-006',
    'MAT-AIR-ROTOR-007', 'MAT-AIR-ROTOR-008', 'MAT-AIR-SHIELD-009',
    'MAT-AIR-SHIELD-010', 'MAT-AIR-BEARING-011', 'MAT-AIR-BEARING-012',
    'MAT-AIR-FAN-013', 'MAT-AIR-FANCOV-014', 'MAT-AIR-FANCOV-015',
    'MAT-AIR-TERM-016', 'MAT-AIR-TERM-017', 'MAT-AIR-TERM-018',
    'MAT-AIR-TERM-019', 'MAT-AIR-TERM-020', 'MAT-AIR-PAINT-021',
    'MAT-AIR-PAINT-022', 'MAT-AIR-FAST-023', 'MAT-AIR-FAST-024',
    'MAT-AIR-FAST-025'
);

-- АИС 80В2 (2.2 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИС 80В2 (2.2 кВт)', 1
FROM products WHERE product_code = 'AIS80B2' LIMIT 1;

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
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для АИС 80В2'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN (
    'MAT-AIR-STATOR-001', 'MAT-AIR-STATOR-002', 'MAT-AIR-STATOR-003',
    'MAT-AIR-STATOR-004', 'MAT-AIR-ROTOR-005', 'MAT-AIR-ROTOR-006',
    'MAT-AIR-ROTOR-007', 'MAT-AIR-ROTOR-008', 'MAT-AIR-SHIELD-009',
    'MAT-AIR-SHIELD-010', 'MAT-AIR-BEARING-011', 'MAT-AIR-BEARING-012',
    'MAT-AIR-FAN-013', 'MAT-AIR-FANCOV-014', 'MAT-AIR-FANCOV-015',
    'MAT-AIR-TERM-016', 'MAT-AIR-TERM-017', 'MAT-AIR-TERM-018',
    'MAT-AIR-TERM-019', 'MAT-AIR-TERM-020', 'MAT-AIR-PAINT-021',
    'MAT-AIR-PAINT-022', 'MAT-AIR-FAST-023', 'MAT-AIR-FAST-024',
    'MAT-AIR-FAST-025'
);

-- АИС 90L2 (3.0 кВт, 3000 об/мин)
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для электродвигателя АИС 90L2 (3.0 кВт)', 1
FROM products WHERE product_code = 'AIS90L2' LIMIT 1;

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
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для АИС 90L2'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku IN (
    'MAT-AIR-STATOR-001', 'MAT-AIR-STATOR-002', 'MAT-AIR-STATOR-003',
    'MAT-AIR-STATOR-004', 'MAT-AIR-ROTOR-005', 'MAT-AIR-ROTOR-006',
    'MAT-AIR-ROTOR-007', 'MAT-AIR-ROTOR-008', 'MAT-AIR-SHIELD-009',
    'MAT-AIR-SHIELD-010', 'MAT-AIR-BEARING-011', 'MAT-AIR-BEARING-012',
    'MAT-AIR-FAN-013', 'MAT-AIR-FANCOV-014', 'MAT-AIR-FANCOV-015',
    'MAT-AIR-TERM-016', 'MAT-AIR-TERM-017', 'MAT-AIR-TERM-018',
    'MAT-AIR-TERM-019', 'MAT-AIR-TERM-020', 'MAT-AIR-PAINT-021',
    'MAT-AIR-PAINT-022', 'MAT-AIR-FAST-023', 'MAT-AIR-FAST-024',
    'MAT-AIR-FAST-025'
);

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
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для РУ-2 (3.5 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%');

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
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для РУ-3 (5.5 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%');

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
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для РУ-4 (6.0 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%');

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
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для дождеприемника в сборе (105 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%' OR m.sku LIKE '%FAST%');

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
    ROW_NUMBER() OVER (ORDER BY m.sku),
    'Для люка Л-В15 (71.9 кг готовое изделие)'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND (m.sku LIKE '%MAT-CI%' OR m.sku LIKE '%PAINT%' OR m.sku LIKE '%FAST%');

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
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-AL-AB87';

-- Алюминий гранулированный АВ87Ф
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для алюминия гранулированного АВ87Ф', 1
FROM products WHERE product_code = 'MAT-AL-AB87F' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-AL-AB87F';

-- Чугун литейный Л4
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для чугуна литейного Л4', 1
FROM products WHERE product_code = 'MAT-CI-L4' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-CI-L4';

-- Чугун литейный Л5
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для чугуна литейного Л5', 1
FROM products WHERE product_code = 'MAT-CI-L5' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-CI-L5';

-- Сталь круглая St3sp
INSERT INTO product_bom (product_id, bom_version, description, is_active)
SELECT id, '1.0', 'Спецификация для стали круглой St3sp', 1
FROM products WHERE product_code = 'MAT-ST-RND-100' LIMIT 1;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order, notes)
SELECT pb.id, m.id, 1 AS quantity, m.unit, 1, 'Чистый материал для продажи'
FROM product_bom pb
CROSS JOIN materials m
WHERE pb.bom_version = '1.0' 
AND m.is_active = 1
AND m.sku = 'MAT-ST-RND-100';

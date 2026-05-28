-- Исправленная миграция BOM без оконных функций (совместимо с MySQL 5.7+)
-- Устранено дублирование, добавлены логичные спецификации для всех продуктов

START TRANSACTION;

-- 1. Очистка старых данных
DELETE FROM product_bom_items;
DELETE FROM product_bom;

-- 2. Создание заголовков BOM для всех активных продуктов
INSERT INTO product_bom (product_id, bom_version, description, is_active, created_at)
SELECT 
    p.id,
    '1.0',
    CONCAT('Спецификация для ', p.name),
    1,
    NOW()
FROM products p
WHERE p.is_active = 1;

-- 3. Вставка компонентов BOM с использованием переменных для нумерации строк
-- Инициализация переменной
SET @row_number = 0;

INSERT INTO product_bom_items (bom_id, material_id, quantity, unit, sequence_order)
SELECT 
    bom_data.bom_id,
    bom_data.material_id,
    bom_data.quantity,
    bom_data.unit,
    (@row_number := @row_number + 1) AS sequence_order
FROM (
    -- Чугунное литье (Серый чугун, Высокопрочный чугун)
    SELECT pb.id AS bom_id, m.id AS material_id,
        CASE 
            WHEN m.sku = 'MAT-CI-L4' THEN 4.0
            WHEN m.sku = 'MAT-CI-L5' THEN 4.0
            WHEN m.sku = 'MAT-PAINT-023' THEN 0.15
            WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
            WHEN m.sku = 'MAT-PKG-BOARD-016' THEN 1.0
            WHEN m.sku = 'MAT-PKG-STRECH-017' THEN 0.5
            ELSE 0.0
        END AS quantity,
        CASE 
            WHEN m.sku IN ('MAT-CI-L4', 'MAT-CI-L5') THEN 'кг'
            WHEN m.sku = 'MAT-PAINT-023' THEN 'кг'
            WHEN m.sku = 'MAT-LABEL-026' THEN 'шт'
            WHEN m.sku IN ('MAT-PKG-BOARD-016', 'MAT-PKG-STRECH-017') THEN 'шт'
            ELSE 'шт'
        END AS unit
    FROM product_bom pb
    JOIN products pr ON pb.product_id = pr.id
    CROSS JOIN materials m
    WHERE pb.bom_version = '1.0'
      AND m.is_active = 1
      AND pr.category LIKE '%Чугун%'
      AND m.sku IN ('MAT-CI-L4', 'MAT-CI-L5', 'MAT-PAINT-023', 'MAT-LABEL-026', 'MAT-PKG-BOARD-016', 'MAT-PKG-STRECH-017')

    UNION ALL

    -- Электродвигатели (AIR, AIRS, AIRE, AIRP, 2AIR, AIRV и др.)
    SELECT pb.id AS bom_id, m.id AS material_id,
        CASE 
            -- Статор и сердечник
            WHEN m.sku = 'MAT-STATOR-CORE-80' THEN 1.0
            WHEN m.sku = 'MAT-WIRE-CU-1.2' THEN 1.8
            WHEN m.sku = 'MAT-WIRE-CU-1.5' THEN 1.5
            WHEN m.sku = 'MAT-SLOT-INS-001' THEN 36.0
            WHEN m.sku = 'MAT-PHASE-INS-002' THEN 3.0
            WHEN m.sku = 'MAT-BAND-GLASS-003' THEN 4.0
            -- Ротор
            WHEN m.sku = 'MAT-ROTOR-CAGE-80' THEN 1.0
            WHEN m.sku = 'MAT-SHAFT-80' THEN 1.0
            WHEN m.sku = 'MAT-ALU-INGOT-AV87' THEN 3.5
            -- Подшипники и валы
            WHEN m.sku = 'MAT-BEAR-6204-ZZ' THEN 2.0
            WHEN m.sku = 'MAT-BEAR-6205-ZZ' THEN 2.0
            WHEN m.sku = 'MAT-SEAL-OIL-25' THEN 2.0
            -- Щиты и корпус
            WHEN m.sku = 'MAT-SHIELD-DE-80' THEN 1.0
            WHEN m.sku = 'MAT-SHIELD-NDE-80' THEN 1.0
            WHEN m.sku = 'MAT-HOUSING-80' THEN 1.0
            WHEN m.sku = 'MAT-FOOT-80' THEN 2.0
            -- Вентиляция
            WHEN m.sku = 'MAT-FAN-80' THEN 1.0
            WHEN m.sku = 'MAT-FAN-COVER-80' THEN 1.0
            -- Борно и клеммы
            WHEN m.sku = 'MAT-BOX-TERMINAL-001' THEN 1.0
            WHEN m.sku = 'MAT-TERM-BLOCK-6A' THEN 3.0
            WHEN m.sku = 'MAT-CABLE-GLAND-M20' THEN 1.0
            -- Крепеж
            WHEN m.sku = 'MAT-BOLT-M8X20' THEN 4.0
            WHEN m.sku = 'MAT-BOLT-M10X30' THEN 4.0
            WHEN m.sku = 'MAT-NUT-M8' THEN 4.0
            WHEN m.sku = 'MAT-NUT-M10' THEN 4.0
            WHEN m.sku = 'MAT-WASH-M8' THEN 8.0
            WHEN m.sku = 'MAT-WASH-M10' THEN 8.0
            -- Изоляция и пропитка
            WHEN m.sku = 'MAT-VARNISH-912' THEN 0.8
            WHEN m.sku = 'MAT-TAPE-GLASS-004' THEN 0.2
            -- Маркировка и упаковка
            WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
            WHEN m.sku = 'MAT-PKG-BOX-80' THEN 1.0
            WHEN m.sku = 'MAT-PKG-PALLET-STD' THEN 0.02
            ELSE 0.0
        END AS quantity,
        CASE 
            WHEN m.sku LIKE 'MAT-WIRE%' OR m.sku LIKE 'MAT-TAPE%' OR m.sku LIKE 'MAT-VARNISH%' THEN 'кг'
            WHEN m.sku LIKE 'MAT-BOLT%' OR m.sku LIKE 'MAT-NUT%' OR m.sku LIKE 'MAT-WASH%' THEN 'шт'
            WHEN m.sku LIKE 'MAT-BEAR%' OR m.sku LIKE 'MAT-SEAL%' THEN 'шт'
            WHEN m.sku LIKE 'MAT-SLOT%' OR m.sku LIKE 'MAT-PHASE%' THEN 'шт'
            WHEN m.sku LIKE 'MAT-PKG%' THEN 'шт'
            WHEN m.sku IN ('MAT-ALU-INGOT-AV87', 'MAT-STATOR-CORE-80', 'MAT-ROTOR-CAGE-80', 'MAT-SHAFT-80') THEN 'кг'
            ELSE 'шт'
        END AS unit
    FROM product_bom pb
    JOIN products pr ON pb.product_id = pr.id
    CROSS JOIN materials m
    WHERE pb.bom_version = '1.0'
      AND m.is_active = 1
      AND (pr.category LIKE '%Двигатель%' OR pr.category LIKE '%Мотор%' OR pr.name LIKE '%AIR%' OR pr.name LIKE '%2AIR%')
      AND m.sku IN (
        'MAT-STATOR-CORE-80', 'MAT-WIRE-CU-1.2', 'MAT-WIRE-CU-1.5', 'MAT-SLOT-INS-001', 'MAT-PHASE-INS-002', 'MAT-BAND-GLASS-003',
        'MAT-ROTOR-CAGE-80', 'MAT-SHAFT-80', 'MAT-ALU-INGOT-AV87',
        'MAT-BEAR-6204-ZZ', 'MAT-BEAR-6205-ZZ', 'MAT-SEAL-OIL-25',
        'MAT-SHIELD-DE-80', 'MAT-SHIELD-NDE-80', 'MAT-HOUSING-80', 'MAT-FOOT-80',
        'MAT-FAN-80', 'MAT-FAN-COVER-80',
        'MAT-BOX-TERMINAL-001', 'MAT-TERM-BLOCK-6A', 'MAT-CABLE-GLAND-M20',
        'MAT-BOLT-M8X20', 'MAT-BOLT-M10X30', 'MAT-NUT-M8', 'MAT-NUT-M10', 'MAT-WASH-M8', 'MAT-WASH-M10',
        'MAT-VARNISH-912', 'MAT-TAPE-GLASS-004',
        'MAT-LABEL-026', 'MAT-PKG-BOX-80', 'MAT-PKG-PALLET-STD'
      )

    UNION ALL

    -- Вентиляторы (если есть отдельная категория)
    SELECT pb.id AS bom_id, m.id AS material_id,
        CASE 
            WHEN m.sku = 'MAT-FAN-BLADE-200' THEN 1.0
            WHEN m.sku = 'MAT-FAN-HUB-200' THEN 1.0
            WHEN m.sku = 'MAT-BOLT-M6X15' THEN 3.0
            WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
            ELSE 0.0
        END AS quantity,
        'шт' AS unit
    FROM product_bom pb
    JOIN products pr ON pb.product_id = pr.id
    CROSS JOIN materials m
    WHERE pb.bom_version = '1.0'
      AND m.is_active = 1
      AND pr.category LIKE '%Вентилятор%'
      AND m.sku IN ('MAT-FAN-BLADE-200', 'MAT-FAN-HUB-200', 'MAT-BOLT-M6X15', 'MAT-LABEL-026')

    UNION ALL

    -- Насосы (если есть)
    SELECT pb.id AS bom_id, m.id AS material_id,
        CASE 
            WHEN m.sku = 'MAT-IMPELLER-CAST-001' THEN 1.0
            WHEN m.sku = 'MAT-MECH-SEAL-25' THEN 1.0
            WHEN m.sku = 'MAT-BEAR-6205-ZZ' THEN 2.0
            WHEN m.sku = 'MAT-HOUSING-PUMP-001' THEN 1.0
            WHEN m.sku = 'MAT-BOLT-M10X40' THEN 4.0
            WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
            ELSE 0.0
        END AS quantity,
        'шт' AS unit
    FROM product_bom pb
    JOIN products pr ON pb.product_id = pr.id
    CROSS JOIN materials m
    WHERE pb.bom_version = '1.0'
      AND m.is_active = 1
      AND pr.category LIKE '%Насос%'
      AND m.sku IN ('MAT-IMPELLER-CAST-001', 'MAT-MECH-SEAL-25', 'MAT-BEAR-6205-ZZ', 'MAT-HOUSING-PUMP-001', 'MAT-BOLT-M10X40', 'MAT-LABEL-026')

    UNION ALL

    -- Редукторы (если есть)
    SELECT pb.id AS bom_id, m.id AS material_id,
        CASE 
            WHEN m.sku = 'MAT-GEAR-STEEL-001' THEN 2.0
            WHEN m.sku = 'MAT-SHAFT-GEAR-001' THEN 2.0
            WHEN m.sku = 'MAT-BEAR-6205-ZZ' THEN 4.0
            WHEN m.sku = 'MAT-OIL-SEAL-30' THEN 2.0
            WHEN m.sku = 'MAT-HOUSING-GEAR-001' THEN 1.0
            WHEN m.sku = 'MAT-OIL-LUBE-5L' THEN 0.005
            WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
            ELSE 0.0
        END AS quantity,
        'шт' AS unit
    FROM product_bom pb
    JOIN products pr ON pb.product_id = pr.id
    CROSS JOIN materials m
    WHERE pb.bom_version = '1.0'
      AND m.is_active = 1
      AND pr.category LIKE '%Редуктор%'
      AND m.sku IN ('MAT-GEAR-STEEL-001', 'MAT-SHAFT-GEAR-001', 'MAT-BEAR-6205-ZZ', 'MAT-OIL-SEAL-30', 'MAT-HOUSING-GEAR-001', 'MAT-OIL-LUBE-5L', 'MAT-LABEL-026')

    UNION ALL

    -- Комплектующие и запчасти (общий список)
    SELECT pb.id AS bom_id, m.id AS material_id,
        CASE 
            WHEN m.sku = 'MAT-LABEL-026' THEN 1.0
            WHEN m.sku = 'MAT-PKG-BOX-SM' THEN 1.0
            ELSE 0.0
        END AS quantity,
        'шт' AS unit
    FROM product_bom pb
    JOIN products pr ON pb.product_id = pr.id
    CROSS JOIN materials m
    WHERE pb.bom_version = '1.0'
      AND m.is_active = 1
      AND pr.category LIKE '%Комплект%'
      AND m.sku IN ('MAT-LABEL-026', 'MAT-PKG-BOX-SM')

) AS bom_data
WHERE bom_data.quantity > 0;

COMMIT;

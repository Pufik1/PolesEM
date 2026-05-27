-- Migration: Add materials table and update warehouse structure
-- For OAO "Polesieelectromash" ERP System
-- Date: 2026-05-26

USE polesie_electromash;

-- Create material categories table
CREATE TABLE IF NOT EXISTS material_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    parent_id INT NULL,
    description TEXT,
    storage_zone VARCHAR(50) COMMENT 'Зона хранения (А, Б, В, Г, Д)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES material_categories(id) ON DELETE SET NULL
);

-- Create materials table for production raw materials
CREATE TABLE IF NOT EXISTS materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE COMMENT 'Артикул материала',
    name VARCHAR(200) NOT NULL COMMENT 'Наименование материала',
    category_id INT,
    standard VARCHAR(100) COMMENT 'ГОСТ/ТУ стандарт',
    grade_spec VARCHAR(200) COMMENT 'Марка/спецификация',
    purpose TEXT COMMENT 'Назначение',
    unit VARCHAR(20) DEFAULT 'шт' COMMENT 'Единица измерения',
    weight DECIMAL(10,3) DEFAULT 0 COMMENT 'Вес единицы в кг',
    length DECIMAL(10,2) DEFAULT 0 COMMENT 'Длина (для проводов, профилей) в м',
    width DECIMAL(10,2) DEFAULT 0 COMMENT 'Ширина в мм',
    thickness DECIMAL(8,3) DEFAULT 0 COMMENT 'Толщина в мм',
    diameter DECIMAL(8,2) DEFAULT 0 COMMENT 'Диаметр в мм',
    voltage_rating VARCHAR(50) COMMENT 'Класс напряжения',
    temperature_class VARCHAR(20) COMMENT 'Класс нагревостойкости',
    ip_rating VARCHAR(10) COMMENT 'Степень защиты IP',
    storage_conditions TEXT COMMENT 'Условия хранения',
    shelf_life_months INT COMMENT 'Срок годности в месяцах',
    min_stock_level DECIMAL(10,3) DEFAULT 10 COMMENT 'Минимальный запас',
    current_stock DECIMAL(10,3) DEFAULT 0 COMMENT 'Текущий остаток',
    supplier VARCHAR(200) COMMENT 'Поставщик',
    price_per_unit DECIMAL(12,2) DEFAULT 0 COMMENT 'Цена за единицу',
    currency VARCHAR(3) DEFAULT 'BYN',
    is_active TINYINT(1) DEFAULT 1,
    source ENUM('explicit_in_catalog', 'standard_industry_bom') DEFAULT 'standard_industry_bom',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES material_categories(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_category (category_id),
    INDEX idx_active (is_active)
);

-- Create warehouse zones reference table
CREATE TABLE IF NOT EXISTS warehouse_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_code VARCHAR(10) NOT NULL UNIQUE COMMENT 'Код зоны (А, Б, В, Г, Д)',
    zone_name VARCHAR(200) NOT NULL COMMENT 'Наименование зоны',
    description TEXT COMMENT 'Описание зоны и условия хранения',
    temperature_min INT COMMENT 'Мин. температура °C',
    temperature_max INT COMMENT 'Макс. температура °C',
    humidity_max INT COMMENT 'Макс. влажность %',
    fire_safety TINYINT(1) DEFAULT 0 COMMENT 'Требования пожарной безопасности',
    ventilation_required TINYINT(1) DEFAULT 0 COMMENT 'Требуется вентиляция',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update warehouse_operations to support materials
ALTER TABLE warehouse_operations 
ADD COLUMN material_id INT NULL AFTER product_id,
ADD COLUMN batch_number VARCHAR(50) NULL COMMENT 'Номер партии' AFTER document_number,
ADD COLUMN expiry_date DATE NULL COMMENT 'Срок годности' AFTER batch_number,
ADD COLUMN quality_cert VARCHAR(50) NULL COMMENT 'Сертификат качества' AFTER expiry_date;

-- Add index for material_id
CREATE INDEX idx_material_id ON warehouse_operations(material_id);

-- Create material stock movements table
CREATE TABLE IF NOT EXISTS material_stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    operation_type ENUM('income', 'outcome', 'transfer', 'write_off', 'production_use') NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit VARCHAR(20) DEFAULT 'шт',
    warehouse_from INT,
    warehouse_to INT,
    user_id INT NOT NULL,
    document_number VARCHAR(50),
    batch_number VARCHAR(50),
    production_order_id INT NULL COMMENT 'Если списано в производство',
    notes TEXT,
    operation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_from) REFERENCES work_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_to) REFERENCES work_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE SET NULL,
    INDEX idx_material_id (material_id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_operation_date (operation_date)
);

-- Insert warehouse zones
INSERT INTO warehouse_zones (zone_code, zone_name, description, temperature_min, temperature_max, humidity_max, fire_safety, ventilation_required) VALUES
('А', 'Зона А - Литейное сырье', 'Чугун, алюминий - напольное хранение, весовые платформы', -10, 40, 80, 0, 0),
('Б', 'Зона Б - Электротехнические материалы', 'Сталь, провода, изоляция - климат-контроль, вертикальные стеллажи', 10, 25, 60, 0, 0),
('В', 'Зона В - Химия и лаки', 'Огнестойкий отсек, вентиляция, поддоны с бортиками', 5, 25, 60, 1, 1),
('Г', 'Зона Г - Комплектующие и крепеж', 'Мелкоячеечные стеллажи, FIFO/FEFO', 10, 25, 50, 0, 0),
('Д', 'Зона Д - Упаковка и консервация', 'Сухое помещение, защита от УФ', 5, 30, 65, 0, 0);

-- Insert material categories
INSERT INTO material_categories (category_name, description, storage_zone) VALUES
('Литейное сырье', 'Чугун, алюминий и другие металлы для литья', 'А'),
('Электротехнические материалы', 'Сталь электротехническая, провода, изоляция', 'Б'),
('Химия и лаки', 'Пропиточные лаки, смазки, консервационные материалы', 'В'),
('Комплектующие', 'Подшипники, крепеж, уплотнители, вентиляторы', 'Г'),
('Крепеж', 'Болты, гайки, шайбы, винты', 'Г'),
('Упаковка', 'Гофрокартон, стрейч-пленка, упаковочные материалы', 'Д');

-- Insert sample materials from JSON with detailed specifications
INSERT INTO materials (sku, name, category_id, standard, grade_spec, purpose, unit, weight, length, width, thickness, diameter, voltage_rating, temperature_class, ip_rating, storage_conditions, shelf_life_months, min_stock_level, current_stock, price_per_unit, source) VALUES
('MAT-STEEL-EL-006', 'Сталь электротехническая холоднокатаная', 2, 'ГОСТ 21427.1-2015', '1211, 2013, 2212 (толщина 0.35-0.50 мм)', 'Пакеты статора и ротора (шихтовка сердечников)', 'т / рулон', 1000, 500, 1000, 0.35, 0, NULL, 'F (155°C)', NULL, 't: 10-25°C, влажность ≤60%, вертикальное хранение рулонов, защита от коррозии', NULL, 3, 10, 185000, 'standard_industry_bom'),
('MAT-WIRE-CU-007', 'Провод медный обмоточный эмальпровод', 2, 'ГОСТ 1839-80, ГОСТ Р МЭК 60317-3-2012', 'ПЭТВ-2, ПЭТК-155, ПЭТ-180 (Ø 0.05-2.50 мм)', 'Обмотки статора двигателей всех серий', 'кг / бухта', 50, 500, 0, 0, 1.25, '660V', 'F (155°C)', NULL, 't: 15-25°C, влажность ≤50%, без УФ, на деревянных поддонах, радиус перегиба ≥10×Ø', NULL, 100, 200, 1250, 'standard_industry_bom'),
('MAT-INS-PAPER-008', 'Электрокартон изоляционный', 2, 'ГОСТ 2824-75', 'ЭВ, ЭВТ (толщина 0.05-0.5 мм)', 'Пазовая изоляция, межслойная прокладка, изоляция выводов', 'кг / рулон', 25, 100, 1000, 0.3, 0, '660V', 'H (180°C)', NULL, 't: 15-25°C, влажность ≤40%, герметичная упаковка, без пыли', NULL, 50, 100, 450, 'standard_industry_bom'),
('MAT-INS-FILM-009', 'Пленка полиэтилентерефталатная (ПЭТФ)', 2, 'ГОСТ 25902-88', 'ЛЭ, ЛА (толщина 0.05-0.2 мм)', 'Комбинированная изоляция пазов, бандажирование лобовых частей', 'кг / рулон', 20, 200, 1000, 0.1, 0, '660V', 'F (155°C)', NULL, 't: 15-25°C, влажность ≤40%, герметичная упаковка', NULL, 30, 60, 380, 'standard_industry_bom'),
('MAT-VAR-IMP-010', 'Лак пропиточный электроизоляционный', 3, 'ГОСТ 9077-82, ГОСТ 25076-82', 'ГФ-95, ПЭ-933, КО-916 (класс нагревостойкости F, 155°C)', 'Пропитка обмоток статора для влагостойкости, механической прочности и теплоотдачи', 'л / канистра', 20, 0, 0, 0, 0, '660V', 'F (155°C)', NULL, 't: 5-25°C, влажность ≤60%, вентиляция, огнестойкий отсек, срок годности 6-12 мес.', 12, 100, 150, 850, 'standard_industry_bom'),
('MAT-BRG-6205-011', 'Подшипник радиальный шариковый', 4, 'ГОСТ 8328-2013, ISO 15:2017', '6205-2RS1, 6206-2RS1, 6308-2RS1 (класс точности P0, P6)', 'Опоры вала ротора (габариты 71-112)', 'шт', 0.5, 0, 0, 0, 25, NULL, NULL, 'IP54', 't: 10-25°C, влажность ≤50%, в заводской упаковке, не вскрывать до монтажа, FIFO', NULL, 200, 500, 185, 'standard_industry_bom'),
('MAT-FAST-BOLT-012', 'Болты крепежные шестигранные', 5, 'ГОСТ 7798-70, ГОСТ Р 52644-2006', 'М6-М12, длина 20-150 мм, класс прочности 8.8, оцинкованные', 'Крепление лап, подшипниковых щитов, клеммных коробок, вентиляционных колпаков', 'шт / кг', 0.1, 50, 0, 0, 8, NULL, NULL, NULL, 't: -5-30°C, влажность ≤65%, защита от контакта с агрессивными средами', NULL, 1000, 5000, 12, 'standard_industry_bom'),
('MAT-RUB-SEAL-013', 'Уплотнители резиновые (кольца, сальники)', 4, 'ГОСТ 9833-73, ГОСТ 14896-84', 'NBR, EPDM, силикон (IP55)', 'Герметизация стыков щитов, вводов кабеля, подшипниковых узлов', 'шт / м', 0.05, 1, 0, 0, 35, NULL, NULL, 'IP55', 't: 5-25°C, влажность ≤50%, без УФ и озона, в герметичных пакетах, срок 24-36 мес.', 36, 500, 1000, 25, 'standard_industry_bom'),
('MAT-FAN-COOL-014', 'Крыльчатка вентилятора охлаждения', 4, 'ТУ предприятия, СТБ ISO 9001', 'Полиамид ПА6 или алюминий АД31', 'Обдув корпуса двигателя (габариты 71-112)', 'шт', 0.3, 0, 0, 0, 112, NULL, 'B (130°C)', NULL, 'Защита лопастей от деформации, сухое помещение', NULL, 100, 250, 95, 'standard_industry_bom'),
('MAT-TERM-BOX-015', 'Корпус клеммной коробки в сборе', 4, 'ГОСТ 31606-2012, ТР ТС 004/2011', 'Чугун/алюминий + полимерная крышка, IP55', 'Подключение силовых кабелей к выводам статора', 'шт', 2.5, 0, 0, 0, 0, '660V', NULL, 'IP55', 'Складские стеллажи, защита от влаги и пыли', NULL, 50, 120, 450, 'standard_industry_bom'),
('MAT-PKG-BOARD-016', 'Гофрокартон тарный', 6, 'ГОСТ 9142-2014', 'Т-23, Т-24 (трех- и пятислойный)', 'Индивидуальная упаковка двигателей габаритов 71-100', 'м² / шт', 0.5, 2, 1.5, 0.005, 0, NULL, NULL, NULL, 'Сухое помещение, влажность ≤65%, на поддонах', NULL, 500, 2000, 85, 'standard_industry_bom'),
('MAT-PKG-STRECH-017', 'Стрейч-пленка ПВХ', 6, 'ТУ 2245-001-xxxx-xxxx', 'толщина 15-20 мкм, ширина 300-500 мм', 'Фиксация изделий на паллетах, защита от пыли и влаги', 'рулон', 5, 500, 0.5, 0.02, 0, NULL, NULL, NULL, 't: 5-30°C, защита от УФ и механических повреждений', NULL, 50, 150, 320, 'standard_industry_bom'),
('MAT-CONSERV-018', 'Смазка консервационная', 3, 'ГОСТ 18382-73', 'ЦИАТИМ-201, К-17, ВНИИ НП-246', 'Защита обработанных поверхностей валов, фланцев и крепежа от коррозии при хранении', 'кг / туба', 1, 0, 0, 0, 0, NULL, NULL, NULL, 't: 5-25°C, герметичная тара, срок годности до 24 мес.', 24, 30, 80, 650, 'standard_industry_bom');

-- Add additional materials based on enterprise products with detailed specifications
INSERT INTO materials (sku, name, category_id, standard, grade_spec, purpose, unit, weight, length, width, thickness, diameter, voltage_rating, temperature_class, ip_rating, storage_conditions, shelf_life_months, min_stock_level, current_stock, price_per_unit, source) VALUES
('MAT-COPPER-BUS-019', 'Шина медная электротехническая', 2, 'ГОСТ 434-78', 'ММ, МТ (сечение 10x1-100x10 мм)', 'Шины заземления, токопроводы в клеммных коробках', 'м', 0.89, 5, 10, 1, 0, '660V', 'F (155°C)', NULL, 'Сухое помещение, защита от окисления, на стеллажах', NULL, 100, 300, 450, 'standard_industry_bom'),
('MAT-SOLDER-020', 'Припой оловянно-свинцовый', 3, 'ГОСТ 21930-76', 'ПОС-40, ПОС-61', 'Пайка выводов обмоток, соединений', 'кг / пруток', 1, 0.3, 0, 0, 3, NULL, NULL, NULL, 'Сухое помещение, защита от влаги', NULL, 20, 50, 1200, 'standard_industry_bom'),
('MAT-FLUX-021', 'Флюс паяльный', 3, 'ГОСТ 9085-78', 'КТК, ЛТИ-120', 'Пайка медных проводников', 'л / банка', 0.5, 0, 0, 0, 0, NULL, NULL, NULL, 't: 5-25°C, герметичная тара, срок годности 12 мес.', 12, 30, 60, 350, 'standard_industry_bom'),
('MAT-BEARING-GREASE-022', 'Смазка подшипниковая', 3, 'ГОСТ 1033-79', 'Литол-24, ЦИАТИМ-221', 'Смазка подшипников качения', 'кг / туба', 0.8, 0, 0, 0, 0, NULL, 'B (130°C)', NULL, 't: 5-25°C, герметичная тара, срок годности 24 мес.', 24, 50, 120, 580, 'standard_industry_bom'),
('MAT-PAINT-023', 'Краска порошковая полиэфирная', 3, 'ГОСТ Р 53840-2010', 'RALS 5005, 7035, 9005', 'Покрытие корпусов двигателей', 'кг', 1, 0, 0, 0, 0, NULL, 'H (180°C)', NULL, 't: 5-30°C, влажность ≤60%, защита от влаги', 18, 100, 250, 920, 'standard_industry_bom'),
('MAT-CABLE-024', 'Кабель силовой гибкий', 2, 'ГОСТ 433-73', 'КГ, КГН (3x1.5-5x2.5 мм²)', 'Подключение двигателей, внутренняя разводка', 'м', 0.15, 100, 0, 0, 8, '660V', 'F (155°C)', 'IP54', 't: 10-30°C, влажность ≤70%, на барабанах', NULL, 200, 500, 185, 'standard_industry_bom'),
('MAT-TERMINAL-025', 'Клеммы винтовые', 4, 'ГОСТ 30012-96', 'ДКС, WAGO (2.5-16 мм²)', 'Соединение проводов в клеммных коробках', 'шт', 0.02, 0, 0, 0, 0, '660V', 'F (155°C)', NULL, 'Сухое помещение, оригинальная упаковка', NULL, 500, 2000, 35, 'standard_industry_bom'),
('MAT-LABEL-026', 'Таблички идентификационные', 4, 'ГОСТ 12971-67', 'Алюминий 0.5мм, лазерная гравировка', 'Маркировка двигателей (шильдики)', 'шт', 0.01, 0, 70, 0.5, 0, NULL, 'H (180°C)', 'IP54', 'Сухое помещение, защита от царапин', NULL, 1000, 5000, 45, 'standard_industry_bom'),
('MAT-RUBBER-FOOT-027', 'Опоры резиновые антивибрационные', 4, 'ТУ предприятия', 'Резина ИР-1127 (M8-M16)', 'Виброизоляция двигателей при установке', 'шт', 0.1, 0, 0, 0, 40, NULL, NULL, NULL, 't: 5-25°C, без УФ, срок хранения 36 мес.', 36, 200, 600, 85, 'standard_industry_bom'),
('MAT-SILICONE-028', 'Герметик силиконовый термостойкий', 3, 'ГОСТ Р 53778-2010', 'RTV-1, красный/серый (310мл)', 'Герметизация стыков, уплотнений', 'шт', 0.35, 0, 0, 0, 0, NULL, 'H (180°C)', NULL, 't: 5-25°C, срок годности 18 мес.', 18, 100, 300, 280, 'standard_industry_bom');

-- Update products table to add more detailed specifications
ALTER TABLE products 
ADD COLUMN frame_size_mm INT NULL COMMENT 'Габарит рамы',
ADD COLUMN power_kw DECIMAL(8,2) NULL COMMENT 'Мощность кВт',
ADD COLUMN rpm INT NULL COMMENT 'Обороты в минуту',
ADD COLUMN efficiency_pct DECIMAL(5,2) NULL COMMENT 'КПД %',
ADD COLUMN cos_phi DECIMAL(4,2) NULL COMMENT 'Коэффициент мощности',
ADD COLUMN voltage_v VARCHAR(50) NULL COMMENT 'Напряжение В',
ADD COLUMN protection_class VARCHAR(10) DEFAULT 'IP55' COMMENT 'Класс защиты',
ADD COLUMN mounting_type VARCHAR(20) DEFAULT 'IM1001' COMMENT 'Тип монтажа';

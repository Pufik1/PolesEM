<?php require_once '../../includes/config.php'; if (!isLoggedIn()) redirect('../../index.php'); 

// Get database connection
$pdo = getDBConnection();

// Handle AJAX requests for data
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_materials':
            // Fetch materials from database or return sample data
            $materials = [
                // Section 1: Raw Materials - Aluminum
                ['category' => 'raw_aluminum', 'section' => 'Сырьё и материалы', 'subsection' => 'Алюминий вторичный', 'article' => 'AL-AB87-ING', 'name' => 'Алюминий вторичный в чушках', 'brand' => 'АВ87', 'gost' => 'ГОСТ 295-98', 'form' => 'Чушка', 'fraction' => '15–25 кг', 'unit' => 'шт', 'weight' => 20, 'storage' => 'Сухое помещение, защита от влаги', 'shelf_life' => 'Неогран.', 'zone' => 'А-01', 'purpose' => 'Раскисление стали, производство ферросплавов'],
                ['category' => 'raw_aluminum', 'section' => 'Сырьё и материалы', 'subsection' => 'Алюминий вторичный', 'article' => 'AL-AB87F-GRN', 'name' => 'Алюминий вторичный гранулированный', 'brand' => 'АВ87Ф', 'gost' => 'ГОСТ 295-98', 'form' => 'Гранула', 'fraction' => '5–30 мм', 'unit' => 'кг', 'weight' => null, 'storage' => 'Герметичная тара, влажность ≤60%', 'shelf_life' => 'Неогран.', 'zone' => 'А-02', 'purpose' => 'Раскисление стали, алюминотермические процессы'],
                // Section 1.2: Cast Iron
                ['category' => 'raw_iron', 'section' => 'Сырьё и материалы', 'subsection' => 'Чугун литейный', 'article' => 'CI-L4-ING', 'name' => 'Чугун литейный передельный', 'brand' => 'Л4', 'gost' => 'ГОСТ 4832-95', 'composition' => 'C: 3.8–4.4%, Si: 1.2–2.0%, Mn: 0.5–0.9%', 'unit' => 'т', 'storage' => 'Сухая площадка, защита от коррозии', 'shelf_life' => 'Неогран.', 'zone' => 'А-03'],
                ['category' => 'raw_iron', 'section' => 'Сырьё и материалы', 'subsection' => 'Чугун литейный', 'article' => 'CI-L5-ING', 'name' => 'Чугун литейный передельный', 'brand' => 'Л5', 'gost' => 'ГОСТ 4832-95', 'composition' => 'C: 3.6–4.2%, Si: 1.0–1.8%, Mn: 0.6–1.0%', 'unit' => 'т', 'storage' => 'Сухая площадка, защита от коррозии', 'shelf_life' => 'Неогран.', 'zone' => 'А-03'],
                // Section 2: Electric Motors - AIR 71
                ['category' => 'motor_71', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 71', 'article' => 'EM-AIR71A2-U2', 'name' => 'АИР71А2 У2', 'power' => 0.55, 'rpm' => 3000, 'voltage' => 380, 'efficiency' => 74.0, 'cos_phi' => 0.78, 'weight' => 10.2, 'dimensions' => '272×160×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-01'],
                ['category' => 'motor_71', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 71', 'article' => 'EM-AIR71B2-U2', 'name' => 'АИР71В2 У2', 'power' => 0.75, 'rpm' => 3000, 'voltage' => 380, 'efficiency' => 75.0, 'cos_phi' => 0.80, 'weight' => 11.0, 'dimensions' => '272×160×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-01'],
                ['category' => 'motor_71', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 71', 'article' => 'EM-AIR71A4-U2', 'name' => 'АИР71А4 У2', 'power' => 0.37, 'rpm' => 1500, 'voltage' => 380, 'efficiency' => 69.0, 'cos_phi' => 0.63, 'weight' => 9.7, 'dimensions' => '272×160×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-01'],
                ['category' => 'motor_71', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 71', 'article' => 'EM-AIR71B4-U2', 'name' => 'АИР71В4 У2', 'power' => 0.55, 'rpm' => 1500, 'voltage' => 380, 'efficiency' => 71.0, 'cos_phi' => 0.71, 'weight' => 10.2, 'dimensions' => '272×160×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-01'],
                ['category' => 'motor_71', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 71', 'article' => 'EM-AIR71A6-U2', 'name' => 'АИР71А6 У2', 'power' => 0.25, 'rpm' => 1000, 'voltage' => 380, 'efficiency' => 64.0, 'cos_phi' => 0.55, 'weight' => 9.2, 'dimensions' => '272×160×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-01'],
                ['category' => 'motor_71', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 71', 'article' => 'EM-AIR71B6-U2', 'name' => 'АИР71В6 У2', 'power' => 0.55, 'rpm' => 1000, 'voltage' => 380, 'efficiency' => 69.0, 'cos_phi' => 0.68, 'weight' => 10.8, 'dimensions' => '272×160×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-01'],
                // Section 2: Electric Motors - AIR 80
                ['category' => 'motor_80', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 80', 'article' => 'EM-AIR80A2-U2', 'name' => 'АИР80А2 У2', 'power' => 1.5, 'rpm' => 3000, 'voltage' => 380, 'efficiency' => 81.3, 'cos_phi' => 0.84, 'weight' => 13.3, 'dimensions' => '316×180×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-02'],
                ['category' => 'motor_80', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 80', 'article' => 'EM-AIR80B2-U2', 'name' => 'АИР80В2 У2', 'power' => 2.2, 'rpm' => 3000, 'voltage' => 380, 'efficiency' => 83.2, 'cos_phi' => 0.85, 'weight' => 15.9, 'dimensions' => '316×180×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-02'],
                ['category' => 'motor_80', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 80', 'article' => 'EM-AIR80A4-U2', 'name' => 'АИР80А4 У2', 'power' => 1.1, 'rpm' => 1500, 'voltage' => 380, 'efficiency' => 75.9, 'cos_phi' => 0.74, 'weight' => 12.8, 'dimensions' => '316×180×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-02'],
                ['category' => 'motor_80', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 80', 'article' => 'EM-AIR80B4-U2', 'name' => 'АИР80В4 У2', 'power' => 1.5, 'rpm' => 1500, 'voltage' => 380, 'efficiency' => 78.1, 'cos_phi' => 0.76, 'weight' => 14.7, 'dimensions' => '316×180×188', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-02'],
                // Section 2: Electric Motors - AIR 90
                ['category' => 'motor_90', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 90', 'article' => 'EM-AIR90L2-U2', 'name' => 'АИР90L2 У2', 'power' => 3.0, 'rpm' => 3000, 'voltage' => 380, 'efficiency' => 84.6, 'cos_phi' => 0.88, 'weight' => 20.6, 'dimensions' => '355×200×220', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-03'],
                ['category' => 'motor_90', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 90', 'article' => 'EM-AIR90LB2-U2', 'name' => 'АИР90LB2 У2', 'power' => 4.0, 'rpm' => 3000, 'voltage' => 380, 'efficiency' => 86.5, 'cos_phi' => 0.89, 'weight' => 23.4, 'dimensions' => '355×200×220', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-03'],
                ['category' => 'motor_90', 'section' => 'Электродвигатели', 'subsection' => 'АИР — габарит 90', 'article' => 'EM-AIR90L4-U2', 'name' => 'АИР90L4 У2', 'power' => 2.2, 'rpm' => 1500, 'voltage' => 380, 'efficiency' => 80.3, 'cos_phi' => 0.81, 'weight' => 19.7, 'dimensions' => '355×200×220', 'packaging' => 'Картон + стрейч', 'zone' => 'Б-03'],
                // Section 2: Special Motors - AIRS
                ['category' => 'motor_special', 'section' => 'Электродвигатели', 'subsection' => 'АИРС — повышенное скольжение', 'article' => 'EM-AIRS80A2-U2', 'name' => 'АИРС80А2 У2', 'power' => 1.5, 'rpm' => 3000, 'weight' => 13.1, 'application' => 'Приводы с пуском под нагрузкой', 'zone' => 'Б-02'],
                ['category' => 'motor_special', 'section' => 'Электродвигатели', 'subsection' => 'АИРС — повышенное скольжение', 'article' => 'EM-AIRS90L2-U2', 'name' => 'АИРС90L2 У2', 'power' => 3.0, 'rpm' => 3000, 'weight' => 21.3, 'application' => 'Насосы, вентиляторы', 'zone' => 'Б-03'],
                // Section 2: Special Motors - AIRE (Single-phase)
                ['category' => 'motor_single', 'section' => 'Электродвигатели', 'subsection' => 'АИРЕ — однофазные 220В', 'article' => 'EM-AIRE71A2-U2', 'name' => 'АИРЕ71А2 У2', 'power' => 0.37, 'rpm' => 3000, 'voltage' => 220, 'weight' => 10.5, 'zone' => 'Б-01'],
                ['category' => 'motor_single', 'section' => 'Электродвигатели', 'subsection' => 'АИРЕ — однофазные 220В', 'article' => 'EM-AIRE71B2-U2', 'name' => 'АИРЕ71В2 У2', 'power' => 0.55, 'rpm' => 3000, 'voltage' => 220, 'weight' => 11.5, 'zone' => 'Б-01'],
                ['category' => 'motor_single', 'section' => 'Электродвигатели', 'subsection' => 'АИРЕ — однофазные 220В', 'article' => 'EM-AIRE80A2-U2', 'name' => 'АИРЕ80А2 У2', 'power' => 0.75, 'rpm' => 3000, 'voltage' => 220, 'weight' => 13.5, 'zone' => 'Б-02'],
                // Section 2: Special Motors - AIRP (Poultry)
                ['category' => 'motor_poultry', 'section' => 'Электродвигатели', 'subsection' => 'АИРП — для птицеводства', 'article' => 'EM-AIRP80A6-U2', 'name' => 'АИРП80А6 У2', 'power' => 0.75, 'rpm' => 1000, 'ip_rating' => 'IP55', 'weight' => 12.2, 'features' => 'Защита от NH₃, H₂S, SO₂', 'zone' => 'Б-02'],
                ['category' => 'motor_poultry', 'section' => 'Электродвигатели', 'subsection' => 'АИРП — для птицеводства', 'article' => 'EM-AIRP80B6-U2', 'name' => 'АИРП80В6 У2', 'power' => 1.1, 'rpm' => 1000, 'ip_rating' => 'IP55', 'weight' => 14.0, 'features' => 'Усиленная изоляция', 'zone' => 'Б-02'],
                // Section 3: Cast Iron Products
                ['category' => 'cast_grating', 'section' => 'Чугунные изделия', 'subsection' => 'Решётки колосниковые', 'article' => 'CI-GR-RU2', 'name' => 'Решётка колосниковая РУ-2', 'gost' => 'СТБ 726-2006', 'dimensions' => '200×300', 'material' => 'Серый чугун', 'weight' => 3.5, 'packaging' => 'Паллета + стрейч', 'zone' => 'В-01'],
                ['category' => 'cast_grating', 'section' => 'Чугунные изделия', 'subsection' => 'Решётки колосниковые', 'article' => 'CI-GR-RU3', 'name' => 'Решётка колосниковая РУ-3', 'gost' => 'СТБ 726-2006', 'dimensions' => '200×350', 'material' => 'Серый чугун', 'weight' => 5.5, 'packaging' => 'Паллета + стрейч', 'zone' => 'В-01'],
                ['category' => 'cast_grating', 'section' => 'Чугунные изделия', 'subsection' => 'Решётки колосниковые', 'article' => 'CI-GR-RU4', 'name' => 'Решётка колосниковая РУ-4', 'gost' => 'СТБ 726-2006', 'dimensions' => '400×200', 'material' => 'Серый чугун', 'weight' => 6.0, 'packaging' => 'Паллета + стрейч', 'zone' => 'В-01'],
                ['category' => 'cast_grating', 'section' => 'Чугунные изделия', 'subsection' => 'Решётки колосниковые', 'article' => 'CI-GR-RD3', 'name' => 'Решётка колосниковая РД-3', 'gost' => 'СТБ 726-2006', 'dimensions' => '170×240', 'material' => 'Серый чугун', 'weight' => 2.2, 'packaging' => 'Короб картонный', 'zone' => 'В-01'],
                ['category' => 'cast_grating', 'section' => 'Чугунные изделия', 'subsection' => 'Решётки колосниковые', 'article' => 'CI-GR-57L', 'name' => 'Решётка колосниковая 57Л', 'gost' => 'СТБ 726-2006', 'dimensions' => '240×415', 'material' => 'Серый чугун', 'weight' => 6.5, 'packaging' => 'Паллета + стрейч', 'zone' => 'В-01'],
                ['category' => 'cast_manhole', 'section' => 'Чугунные изделия', 'subsection' => 'Люки и дождеприёмники', 'article' => 'CI-DR-400', 'name' => 'Решётка дождеприёмника', 'gost' => 'СТБ 3634-99', 'dimensions' => '400×800×40', 'material' => 'Серый чугун', 'weight' => 45, 'packaging' => 'Паллета + стрейч', 'zone' => 'В-03'],
                ['category' => 'cast_manhole', 'section' => 'Чугунные изделия', 'subsection' => 'Люки и дождеприёмники', 'article' => 'CI-DR-ASSY', 'name' => 'Дождеприёмник в сборе', 'gost' => 'СТБ 3634-99', 'dimensions' => '500×1000', 'material' => 'Серый чугун', 'weight' => 105, 'packaging' => 'Паллета + обрешётка', 'zone' => 'В-03'],
                ['category' => 'cast_manhole', 'section' => 'Чугунные изделия', 'subsection' => 'Люки и дождеприёмники', 'article' => 'CI-MH-L-V15', 'name' => 'Люк лёгкий тип Л (В15)', 'gost' => 'ГОСТ 3634-99', 'dimensions' => '-', 'material' => 'Серый чугун', 'weight' => 71.9, 'packaging' => 'Паллета + обрешётка', 'zone' => 'В-04'],
                ['category' => 'cast_other', 'section' => 'Чугунные изделия', 'subsection' => 'Прочие изделия', 'article' => 'CI-BALL-MILL', 'name' => 'Цильпебсы / шары мелющие', 'gost' => 'ТУ предприятия', 'dimensions' => '⌀20–100 мм', 'material' => 'Серый чугун', 'weight' => null, 'packaging' => 'Биг-бег / контейнер', 'zone' => 'В-05'],
                // Section 4: Components and Spare Parts
                ['category' => 'parts_bearing', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Подшипники', 'article' => 'BRG-6205-2RS', 'name' => 'Подшипник 6205-2RS', 'application' => 'Габариты 71–80', 'unit' => 'шт', 'storage' => 'Сухое помещение, антикоррозийная смазка', 'min_stock' => 50, 'zone' => 'Г-01'],
                ['category' => 'parts_bearing', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Подшипники', 'article' => 'BRG-6308-2RS', 'name' => 'Подшипник 6308-2RS', 'application' => 'Габариты 90–100', 'unit' => 'шт', 'storage' => 'Сухое помещение, антикоррозийная смазка', 'min_stock' => 30, 'zone' => 'Г-01'],
                ['category' => 'parts_bearing', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Подшипники', 'article' => 'BRG-6206-2RS', 'name' => 'Подшипник 6206-2RS', 'application' => 'Габариты 112', 'unit' => 'шт', 'storage' => 'Сухое помещение, антикоррозийная смазка', 'min_stock' => 25, 'zone' => 'Г-01'],
                ['category' => 'parts_terminal', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Клеммные коробки', 'article' => 'TERM-BOX-71', 'name' => 'Клеммная коробка в сборе', 'application' => 'Габарит 71', 'unit' => 'шт', 'storage' => 'Защита от пыли, маркировка', 'min_stock' => 20, 'zone' => 'Г-02'],
                ['category' => 'parts_terminal', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Клеммные коробки', 'article' => 'TERM-BOX-80', 'name' => 'Клеммная коробка в сборе', 'application' => 'Габарит 80', 'unit' => 'шт', 'storage' => 'Защита от пыли, маркировка', 'min_stock' => 20, 'zone' => 'Г-02'],
                ['category' => 'parts_terminal', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Клеммные коробки', 'article' => 'TERM-BOX-90', 'name' => 'Клеммная коробка в сборе', 'application' => 'Габарит 90+', 'unit' => 'шт', 'storage' => 'Защита от пыли, маркировка', 'min_stock' => 15, 'zone' => 'Г-02'],
                ['category' => 'parts_fan', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Вентиляторы охлаждения', 'article' => 'FAN-COOL-71', 'name' => 'Вентилятор охлаждения', 'application' => 'Габарит 71', 'unit' => 'шт', 'storage' => 'Защита лопастей, без деформации', 'min_stock' => 15, 'zone' => 'Г-03'],
                ['category' => 'parts_fan', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Вентиляторы охлаждения', 'article' => 'FAN-COOL-80', 'name' => 'Вентилятор охлаждения', 'application' => 'Габарит 80', 'unit' => 'шт', 'storage' => 'Защита лопастей, без деформации', 'min_stock' => 15, 'zone' => 'Г-03'],
                ['category' => 'parts_seal', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Уплотнения', 'article' => 'SEAL-IP55-SET', 'name' => 'Комплект уплотнений IP55', 'application' => 'Все габариты', 'unit' => 'компл.', 'storage' => 'Герметичная упаковка, без УФ', 'min_stock' => 40, 'zone' => 'Г-04'],
                ['category' => 'parts_fastener', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Крепёж', 'article' => 'FAST-BOLT-M8', 'name' => 'Болт М8×25, кл. прочности 8.8', 'application' => 'Крепление лап', 'unit' => 'кг', 'storage' => 'Оцинковка, защита от коррозии', 'min_stock' => 10, 'zone' => 'Г-05'],
                ['category' => 'parts_fastener', 'section' => 'Комплектующие и ЗИП', 'subsection' => 'Крепёж', 'article' => 'FAST-LOCK-M8', 'name' => 'Гайка самоконтрящаяся М8', 'application' => 'Клеммные коробки', 'unit' => 'шт', 'storage' => 'Оцинковка, защита от коррозии', 'min_stock' => 100, 'zone' => 'Г-05'],
            ];
            
            echo json_encode(['success' => true, 'data' => $materials]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Склад - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/warehouse.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-bolt"></i></div>
                    <div class="logo-text"><h2>Полесьеэлектромаш</h2><p>Корпоративная система</p></div>
                </div>
            </div>
            <?php 
            $basePath = '../../';
            include '../../includes/sidebar.php'; 
            ?>
        </aside>
        <div class="main-content">
            <header class="header">
                <div class="header-title"><h1><i class="fas fa-warehouse"></i> Складской учет материалов</h1></div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <span class="user-role"><?php echo $_SESSION['user_role']; ?></span>
                    </div>
                </div>
            </header>
            <div class="content-area warehouse-content">
                <!-- Statistics Cards -->
                <div class="stats-grid warehouse-stats">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="fas fa-boxes"></i></div>
                        <div class="stat-info">
                            <h3 id="total-items">0</h3>
                            <p>Всего позиций</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3 id="in-stock">0</h3>
                            <p>В наличии</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <h3 id="low-stock">0</h3>
                            <p>Мало на складе</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon danger"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-info">
                            <h3 id="total-zones">4</h3>
                            <p>Зон хранения</p>
                        </div>
                    </div>
                </div>

                <!-- Main Warehouse Content -->
                <div class="warehouse-main">
                    <!-- Filters Panel -->
                    <div class="card filters-panel">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-filter"></i> Фильтры поиска</h2>
                            <button class="btn btn-sm btn-secondary" id="toggleFilters"><i class="fas fa-chevron-up"></i></button>
                        </div>
                        <div class="card-body filters-content">
                            <div class="filters-grid">
                                <!-- Search by text -->
                                <div class="filter-group full-width">
                                    <label><i class="fas fa-search"></i> Поиск по тексту</label>
                                    <input type="text" id="searchText" placeholder="Артикул, наименование, марка..." class="filter-input">
                                </div>
                                
                                <!-- Category Filter -->
                                <div class="filter-group">
                                    <label><i class="fas fa-folder"></i> Категория</label>
                                    <select id="filterCategory" class="filter-select">
                                        <option value="">Все категории</option>
                                        <option value="raw_aluminum">Алюминий вторичный</option>
                                        <option value="raw_iron">Чугун литейный</option>
                                        <option value="motor_71">АИР габарит 71</option>
                                        <option value="motor_80">АИР габарит 80</option>
                                        <option value="motor_90">АИР габарит 90</option>
                                        <option value="motor_special">Спец. исполнения</option>
                                        <option value="motor_single">Однофазные 220В</option>
                                        <option value="motor_poultry">Для птицеводства</option>
                                        <option value="cast_grating">Решётки колосниковые</option>
                                        <option value="cast_manhole">Люки и дождеприёмники</option>
                                        <option value="cast_other">Прочие чугунные изделия</option>
                                        <option value="parts_bearing">Подшипники</option>
                                        <option value="parts_terminal">Клеммные коробки</option>
                                        <option value="parts_fan">Вентиляторы</option>
                                        <option value="parts_seal">Уплотнения</option>
                                        <option value="parts_fastener">Крепёж</option>
                                    </select>
                                </div>

                                <!-- Section Filter -->
                                <div class="filter-group">
                                    <label><i class="fas fa-layer-group"></i> Раздел</label>
                                    <select id="filterSection" class="filter-select">
                                        <option value="">Все разделы</option>
                                        <option value="Сырьё и материалы">Сырьё и материалы</option>
                                        <option value="Электродвигатели">Электродвигатели</option>
                                        <option value="Чугунные изделия">Чугунные изделия</option>
                                        <option value="Комплектующие и ЗИП">Комплектующие и ЗИП</option>
                                    </select>
                                </div>

                                <!-- Zone Filter -->
                                <div class="filter-group">
                                    <label><i class="fas fa-map-marker-alt"></i> Зона хранения</label>
                                    <select id="filterZone" class="filter-select">
                                        <option value="">Все зоны</option>
                                        <optgroup label="Зона А — Сырьё">
                                            <option value="А-01">А-01</option>
                                            <option value="А-02">А-02</option>
                                            <option value="А-03">А-03</option>
                                        </optgroup>
                                        <optgroup label="Зона Б — Электродвигатели">
                                            <option value="Б-01">Б-01</option>
                                            <option value="Б-02">Б-02</option>
                                            <option value="Б-03">Б-03</option>
                                            <option value="Б-04">Б-04</option>
                                            <option value="Б-05">Б-05</option>
                                        </optgroup>
                                        <optgroup label="Зона В — Чугунные изделия">
                                            <option value="В-01">В-01</option>
                                            <option value="В-02">В-02</option>
                                            <option value="В-03">В-03</option>
                                            <option value="В-04">В-04</option>
                                            <option value="В-05">В-05</option>
                                        </optgroup>
                                        <optgroup label="Зона Г — Комплектующие">
                                            <option value="Г-01">Г-01</option>
                                            <option value="Г-02">Г-02</option>
                                            <option value="Г-03">Г-03</option>
                                            <option value="Г-04">Г-04</option>
                                            <option value="Г-05">Г-05</option>
                                        </optgroup>
                                    </select>
                                </div>

                                <!-- Power Filter (for motors) -->
                                <div class="filter-group">
                                    <label><i class="fas fa-bolt"></i> Мощность, кВт</label>
                                    <select id="filterPower" class="filter-select">
                                        <option value="">Любая</option>
                                        <option value="0.25">0.25</option>
                                        <option value="0.37">0.37</option>
                                        <option value="0.55">0.55</option>
                                        <option value="0.75">0.75</option>
                                        <option value="1.1">1.1</option>
                                        <option value="1.5">1.5</option>
                                        <option value="2.2">2.2</option>
                                        <option value="3.0">3.0</option>
                                        <option value="4.0">4.0</option>
                                        <option value="5.5">5.5</option>
                                        <option value="7.5">7.5</option>
                                    </select>
                                </div>

                                <!-- RPM Filter (for motors) -->
                                <div class="filter-group">
                                    <label><i class="fas fa-tachometer-alt"></i> Обороты, об/мин</label>
                                    <select id="filterRpm" class="filter-select">
                                        <option value="">Любые</option>
                                        <option value="750">750</option>
                                        <option value="1000">1000</option>
                                        <option value="1500">1500</option>
                                        <option value="3000">3000</option>
                                    </select>
                                </div>

                                <!-- Voltage Filter -->
                                <div class="filter-group">
                                    <label><i class="fas fa-plug"></i> Напряжение, В</label>
                                    <select id="filterVoltage" class="filter-select">
                                        <option value="">Любое</option>
                                        <option value="220">220 В</option>
                                        <option value="380">380 В</option>
                                    </select>
                                </div>

                                <!-- Material Filter (for cast iron) -->
                                <div class="filter-group">
                                    <label><i class="fas fa-cube"></i> Материал</label>
                                    <select id="filterMaterial" class="filter-select">
                                        <option value="">Любой</option>
                                        <option value="Серый чугун">Серый чугун</option>
                                        <option value="АВ87">АВ87</option>
                                        <option value="АВ87Ф">АВ87Ф</option>
                                        <option value="Л4">Л4</option>
                                        <option value="Л5">Л5</option>
                                    </select>
                                </div>

                                <!-- Weight Range -->
                                <div class="filter-group">
                                    <label><i class="fas fa-weight-hanging"></i> Вес, кг</label>
                                    <div class="range-inputs">
                                        <input type="number" id="weightMin" placeholder="От" class="filter-input-small">
                                        <input type="number" id="weightMax" placeholder="До" class="filter-input-small">
                                    </div>
                                </div>

                                <!-- GOST/Standard Filter -->
                                <div class="filter-group">
                                    <label><i class="fas fa-file-contract"></i> ГОСТ/Стандарт</label>
                                    <select id="filterGost" class="filter-select">
                                        <option value="">Любой</option>
                                        <option value="ГОСТ 295-98">ГОСТ 295-98</option>
                                        <option value="ГОСТ 4832-95">ГОСТ 4832-95</option>
                                        <option value="ГОСТ 3634-99">ГОСТ 3634-99</option>
                                        <option value="СТБ 726-2006">СТБ 726-2006</option>
                                        <option value="СТБ 3634-99">СТБ 3634-99</option>
                                    </select>
                                </div>

                                <!-- Storage Conditions -->
                                <div class="filter-group">
                                    <label><i class="fas fa-thermometer-half"></i> Условия хранения</label>
                                    <select id="filterStorage" class="filter-select">
                                        <option value="">Любые</option>
                                        <option value="Сухое помещение">Сухое помещение</option>
                                        <option value="Герметичная тара">Герметичная тара</option>
                                        <option value="Напольное хранение">Напольное хранение</option>
                                        <option value="Стеллажи">Стеллажи</option>
                                    </select>
                                </div>

                                <!-- In Stock Filter -->
                                <div class="filter-group">
                                    <label><i class="fas fa-box-open"></i> Наличие</label>
                                    <select id="filterStock" class="filter-select">
                                        <option value="">Все</option>
                                        <option value="in_stock">В наличии</option>
                                        <option value="low_stock">Мало на складе</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Filter Actions -->
                            <div class="filter-actions">
                                <button class="btn btn-primary" id="applyFilters"><i class="fas fa-search"></i> Применить фильтры</button>
                                <button class="btn btn-secondary" id="resetFilters"><i class="fas fa-undo"></i> Сбросить</button>
                                <button class="btn btn-success" id="exportData"><i class="fas fa-file-export"></i> Экспорт в Excel</button>
                            </div>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="card results-panel">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-list"></i> Материалы на складе</h2>
                            <div class="table-actions">
                                <span class="results-count" id="resultsCount">0 позиций</span>
                                <div class="view-toggle">
                                    <button class="btn-icon-view active" data-view="table"><i class="fas fa-table"></i></button>
                                    <button class="btn-icon-view" data-view="cards"><i class="fas fa-th-large"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Table View -->
                            <div class="table-responsive" id="tableView">
                                <table class="data-table warehouse-table">
                                    <thead>
                                        <tr>
                                            <th>Артикул</th>
                                            <th>Наименование</th>
                                            <th>Категория</th>
                                            <th>Зона</th>
                                            <th>Вес, кг</th>
                                            <th>Ед. изм.</th>
                                            <th>ГОСТ/Стандарт</th>
                                            <th>Условия хранения</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody id="materialsTableBody">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Card View -->
                            <div class="cards-view hidden" id="cardsView">
                                <div class="materials-grid" id="materialsGrid">
                                    <!-- Cards will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Zones Info -->
                    <div class="zones-info-grid">
                        <div class="card zone-card zone-a">
                            <div class="zone-header">
                                <h3><i class="fas fa-warehouse"></i> Зона А — Сырьё</h3>
                                <span class="zone-badge">Металлы</span>
                            </div>
                            <div class="zone-body">
                                <ul>
                                    <li><strong>Температура:</strong> -10…+30 °C</li>
                                    <li><strong>Влажность:</strong> ≤70%</li>
                                    <li><strong>Погрузка:</strong> Вилочный погрузчик, кран-балка</li>
                                    <li><strong>Особенности:</strong> Напольное хранение на поддонах</li>
                                </ul>
                            </div>
                        </div>
                        <div class="card zone-card zone-b">
                            <div class="zone-header">
                                <h3><i class="fas fa-motorcycle"></i> Зона Б — Электродвигатели</h3>
                                <span class="zone-badge">Готовая продукция</span>
                            </div>
                            <div class="zone-body">
                                <ul>
                                    <li><strong>Температура:</strong> +5…+30 °C</li>
                                    <li><strong>Влажность:</strong> ≤80% при +20 °C</li>
                                    <li><strong>Срок хранения:</strong> 24 месяца</li>
                                    <li><strong>Особенности:</strong> Защита от УФ, вертикальное хранение</li>
                                </ul>
                            </div>
                        </div>
                        <div class="card zone-card zone-c">
                            <div class="zone-header">
                                <h3><i class="fas fa-cubes"></i> Зона В — Чугунные изделия</h3>
                                <span class="zone-badge">Готовая продукция</span>
                            </div>
                            <div class="zone-body">
                                <ul>
                                    <li><strong>Температура:</strong> Не нормируется</li>
                                    <li><strong>Влажность:</strong> ≤80%</li>
                                    <li><strong>Особенности:</strong> Деревянные прокладки</li>
                                    <li><strong>Обработка:</strong> Антикоррозийная при хранении</li>
                                </ul>
                            </div>
                        </div>
                        <div class="card zone-card zone-d">
                            <div class="zone-header">
                                <h3><i class="fas fa-tools"></i> Зона Г — Комплектующие</h3>
                                <span class="zone-badge">ЗИП</span>
                            </div>
                            <div class="zone-body">
                                <ul>
                                    <li><strong>Температура:</strong> +10…+25 °C</li>
                                    <li><strong>Влажность:</strong> ≤60%</li>
                                    <li><strong>Хранение:</strong> Мелкоячеечные стеллажи</li>
                                    <li><strong>Принцип:</strong> FIFO</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Material Detail Modal -->
    <div class="modal" id="materialModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Информация о материале</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Закрыть</button>
                <button class="btn btn-primary"><i class="fas fa-print"></i> Печать</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/warehouse.js"></script>
</body>
</html>

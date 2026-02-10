<?php
session_start();
$_SESSION['role'] = 'pentadbir';
$activePage = 'statistik-sistem';

// Sample statistics data
$stats = [
    'total' => [
        'users' => 2450,
        'programs' => 156,
        'categories' => 12,
        'feedback' => 1245
    ],
    'monthly' => [
        'programs' => [45, 52, 48, 65, 75, 60, 55, 70, 65, 80, 85, 90],
        'participants' => [320, 380, 350, 420, 480, 450, 400, 470, 460, 520, 580, 620],
        'feedback' => [85, 92, 78, 110, 125, 105, 95, 120, 115, 135, 150, 165]
    ],
    'topPrograms' => [
        ['name' => 'Workshop Kepimpinan Mahasiswa', 'participants' => 180, 'rating' => 4.8],
        ['name' => 'Seminar Inovasi Digital', 'participants' => 165, 'rating' => 4.7],
        ['name' => 'Program Sukarelawan Komuniti', 'participants' => 142, 'rating' => 4.9],
        ['name' => 'Forum Kerjaya Graduan', 'participants' => 135, 'rating' => 4.6],
        ['name' => 'Bengkel Penulisan Ilmiah', 'participants' => 128, 'rating' => 4.5]
    ],
    'userGrowth' => [
        ['month' => 'Jan', 'pelajar' => 150, 'penganjur' => 8, 'pentadbir' => 2],
        ['month' => 'Feb', 'pelajar' => 180, 'penganjur' => 10, 'pentadbir' => 2],
        ['month' => 'Mar', 'pelajar' => 210, 'penganjur' => 12, 'pentadbir' => 3],
        ['month' => 'Apr', 'pelajar' => 245, 'penganjur' => 14, 'pentadbir' => 3],
        ['month' => 'May', 'pelajar' => 280, 'penganjur' => 16, 'pentadbir' => 4],
        ['month' => 'Jun', 'pelajar' => 320, 'penganjur' => 18, 'pentadbir' => 4]
    ],
    'categoryStats' => [
        ['name' => 'Kepimpinan', 'programs' => 25, 'participation' => 68],
        ['name' => 'Teknologi', 'programs' => 32, 'participation' => 72],
        ['name' => 'Komuniti', 'programs' => 28, 'participation' => 85],
        ['name' => 'Akademik', 'programs' => 22, 'participation' => 65],
        ['name' => 'Sukan', 'programs' => 18, 'participation' => 58],
        ['name' => 'Kerjaya', 'programs' => 15, 'participation' => 75]
    ],
    'systemMetrics' => [
        'avg_rating' => 4.7,
        'attendance_rate' => 82,
        'feedback_rate' => 65,
        'system_uptime' => 99.8,
        'active_sessions' => 124
    ],
    'recentActivity' => [
        ['type' => 'program', 'action' => 'Program baru diterbitkan', 'details' => 'Workshop AI & ML', 'time' => '2 jam lalu'],
        ['type' => 'user', 'action' => 'Pengguna baharu mendaftar', 'details' => 'Ahmad (Pelajar)', 'time' => '4 jam lalu'],
        ['type' => 'feedback', 'action' => 'Maklum balas diterima', 'details' => 'Rating: 5/5', 'time' => '6 jam lalu'],
        ['type' => 'system', 'action' => 'Backup sistem', 'details' => 'Backup harian berjaya', 'time' => '8 jam lalu']
    ]
];

// Calculate growth percentages
$userGrowthPercent = round(($stats['total']['users'] - 2000) / 2000 * 100, 1);
$programGrowthPercent = round(($stats['total']['programs'] - 120) / 120 * 100, 1);
$feedbackGrowthPercent = round(($stats['total']['feedback'] - 1000) / 1000 * 100, 1);

// Time periods for filtering
$timePeriods = ['7d' => '7 Hari', '30d' => '30 Hari', '90d' => '90 Hari', '1y' => '1 Tahun', 'all' => 'Semua'];
$selectedPeriod = $_GET['period'] ?? '30d';
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Sistem | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Statistics Page Styling */
        .stats-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Time Period Filter */
        .period-filter {
            display: flex;
            gap: 8px;
            margin: 24px 0;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 10px 20px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .period-btn:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .period-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Main Stats Cards */
        .main-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .main-stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .main-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .icon-users { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .icon-programs { background: linear-gradient(135deg, #10b981, #34d399); }
        .icon-categories { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .icon-feedback { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        
        .stat-value {
            font-size: 40px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .stat-title {
            font-size: 14px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        
        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .chart-header {
            margin-bottom: 24px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .chart-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Bar Chart */
        .bar-chart-container {
            display: flex;
            align-items: flex-end;
            height: 200px;
            gap: 20px;
            margin-top: 40px;
        }
        
        .bar-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }
        
        .bar-container {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: flex-end;
        }
        
        .bar {
            width: 100%;
            border-radius: 8px 8px 0 0;
            transition: height 0.5s ease;
            position: relative;
        }
        
        .bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .bar-label {
            margin-top: 12px;
            font-size: 12px;
            color: var(--text-secondary);
            text-align: center;
        }
        
        /* Donut Chart */
        .donut-chart-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
            position: relative;
        }
        
        .donut-chart {
            width: 200px;
            height: 200px;
            position: relative;
        }
        
        .donut-segment {
            position: absolute;
            width: 100%;
            height: 100%;
            clip-path: polygon(50% 50%, 50% 0, 100% 0, 100% 100%, 0 100%, 0 0, 50% 0);
            border-radius: 50%;
            transform: rotate(calc(var(--start) * 1deg));
        }
        
        .donut-center {
            position: absolute;
            width: 100px;
            height: 100px;
            background: var(--surface);
            border-radius: 50%;
            top: 50px;
            left: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .donut-total {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .donut-label {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .donut-legend {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-left: 40px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        
        /* Top Programs */
        .programs-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .program-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: var(--background);
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        
        .program-item:hover {
            background: rgba(37, 99, 235, 0.05);
            transform: translateX(4px);
        }
        
        .program-info {
            flex: 1;
        }
        
        .program-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .program-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .program-stats {
            text-align: right;
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #f59e0b;
            font-weight: 600;
        }
        
        .participants {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        /* Recent Activity */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--background);
            border-radius: 12px;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .icon-program { background: #3b82f6; }
        .icon-user { background: #10b981; }
        .icon-feedback { background: #f59e0b; }
        .icon-system { background: #8b5cf6; }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-action {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }
        
        .activity-details {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--text-tertiary);
        }
        
        /* System Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        
        .metric-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border);
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .metric-label {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .metric-progress {
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            margin-top: 12px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 1s ease;
        }
        
        /* Export Button */
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .export-btn:hover {
            background: rgba(37, 99, 235, 0.1);
        }
        
        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .loading {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
       <!-- TOP BAR -->
<header class="topbar"></header>


        <!-- PAGE CONTENT -->
        <section class="content">
            <!-- Header Section -->
            <div class="welcome-section">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h1 class="page-title">Statistik Sistem</h1>
                        <p class="page-subtitle">Analisis dan prestasi keseluruhan sistem UKMInvolve</p>
                    </div>
                    <button class="export-btn" onclick="exportStatistics()">
                        <i class="fas fa-download"></i> Eksport Data
                    </button>
                </div>
            </div>

            <!-- Time Period Filter -->
            <div class="period-filter">
                <?php foreach ($timePeriods as $value => $label): ?>
                    <a href="?period=<?= $value ?>" 
                       class="period-btn <?= $selectedPeriod === $value ? 'active' : '' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Main Statistics -->
            <div class="main-stats-grid">
                <div class="main-stat-card">
                    <div class="stat-icon icon-users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total']['users']) ?></div>
                    <div class="stat-title">Pengguna</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <?= $userGrowthPercent ?>% dari bulan lalu
                    </div>
                </div>
                
                <div class="main-stat-card">
                    <div class="stat-icon icon-programs">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total']['programs']) ?></div>
                    <div class="stat-title">Program</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <?= $programGrowthPercent ?>% dari bulan lalu
                    </div>
                </div>
                
                <div class="main-stat-card">
                    <div class="stat-icon icon-categories">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total']['categories']) ?></div>
                    <div class="stat-title">Kategori</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        +2 dari bulan lalu
                    </div>
                </div>
                
                <div class="main-stat-card">
                    <div class="stat-icon icon-feedback">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total']['feedback']) ?></div>
                    <div class="stat-title">Maklum Balas</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <?= $feedbackGrowthPercent ?>% dari bulan lalu
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <!-- Monthly Growth Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Pertumbuhan Bulanan</h2>
                        <p class="chart-subtitle">Program dan peserta sepanjang tahun 2025</p>
                    </div>
                    
                    <div class="bar-chart-container">
                        <?php
                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
                        $maxValue = max(max($stats['monthly']['programs']), max($stats['monthly']['participants']));
                        
                        for ($i = 0; $i < 12; $i++):
                            $programHeight = ($stats['monthly']['programs'][$i] / $maxValue) * 160;
                            $participantHeight = ($stats['monthly']['participants'][$i] / $maxValue) * 160;
                        ?>
                        <div class="bar-column">
                            <div class="bar-container">
                                <div class="bar" 
                                     style="height: <?= $programHeight ?>px; background: linear-gradient(135deg, var(--primary), #60a5fa); margin-right: 4px;">
                                    <span class="bar-value"><?= $stats['monthly']['programs'][$i] ?></span>
                                </div>
                                <div class="bar" 
                                     style="height: <?= $participantHeight ?>px; background: linear-gradient(135deg, #10b981, #34d399); margin-left: 4px;">
                                    <span class="bar-value"><?= $stats['monthly']['participants'][$i] ?></span>
                                </div>
                            </div>
                            <div class="bar-label"><?= $months[$i] ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div style="display: flex; gap: 20px; justify-content: center; margin-top: 24px;">
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                            <div style="width: 12px; height: 12px; background: var(--primary); border-radius: 2px;"></div>
                            <span>Program</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                            <div style="width: 12px; height: 12px; background: #10b981; border-radius: 2px;"></div>
                            <span>Peserta</span>
                        </div>
                    </div>
                </div>

                <!-- User Distribution Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Pengedaran Pengguna</h2>
                        <p class="chart-subtitle">Peranan pengguna dalam sistem</p>
                    </div>
                    
                    <div style="display: flex; align-items: center;">
                        <div class="donut-chart-container">
                            <div class="donut-chart">
                                <?php
                                $userDistribution = [
                                    ['label' => 'Pelajar', 'value' => 2000, 'color' => '#3b82f6'],
                                    ['label' => 'Penganjur', 'value' => 120, 'color' => '#8b5cf6'],
                                    ['label' => 'Pentadbir', 'value' => 8, 'color' => '#10b981']
                                ];
                                
                                $total = array_sum(array_column($userDistribution, 'value'));
                                $start = 0;
                                
                                foreach ($userDistribution as $index => $segment):
                                    $percentage = ($segment['value'] / $total) * 100;
                                    $end = $start + ($percentage * 3.6); // 360° for 100%
                                ?>
                                <div class="donut-segment" 
                                     style="background: conic-gradient(
                                         <?= $segment['color'] ?> 0deg <?= $end ?>deg, 
                                         transparent <?= $end ?>deg 360deg
                                     );
                                     --start: <?= $start ?>;">
                                </div>
                                <?php 
                                    $start = $end;
                                endforeach; 
                                ?>
                                
                                <div class="donut-center">
                                    <div class="donut-total"><?= number_format($total) ?></div>
                                    <div class="donut-label">Jumlah Pengguna</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="donut-legend">
                            <?php foreach ($userDistribution as $segment): 
                                $percentage = round(($segment['value'] / $total) * 100, 1);
                            ?>
                            <div class="legend-item">
                                <div class="legend-color" style="background: <?= $segment['color'] ?>"></div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;"><?= $segment['label'] ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">
                                        <?= number_format($segment['value']) ?> (<?= $percentage ?>%)
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row: Top Programs and Category Stats -->
            <div class="charts-grid">
                <!-- Top Programs -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Program Teratas</h2>
                        <p class="chart-subtitle">Program paling popular berdasarkan penyertaan</p>
                    </div>
                    
                    <div class="programs-list">
                        <?php foreach ($stats['topPrograms'] as $program): ?>
                        <div class="program-item">
                            <div class="program-info">
                                <div class="program-name"><?= $program['name'] ?></div>
                                <div class="program-meta">
                                    <span><i class="fas fa-users"></i> <?= $program['participants'] ?> peserta</span>
                                    <span><i class="fas fa-chart-bar"></i> <?= $program['rating'] ?>/5 rating</span>
                                </div>
                            </div>
                            <div class="program-stats">
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                    <?= $program['rating'] ?>
                                </div>
                                <div class="participants">
                                    +<?= round(($program['participants'] - 100) / 100 * 100) ?>% dari sasaran
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Category Statistics -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Statistik Kategori</h2>
                        <p class="chart-subtitle">Prestasi mengikut kategori program</p>
                    </div>
                    
                    <div class="programs-list">
                        <?php foreach ($stats['categoryStats'] as $category): ?>
                        <div class="program-item">
                            <div class="program-info">
                                <div class="program-name"><?= $category['name'] ?></div>
                                <div class="program-meta">
                                    <span><i class="fas fa-calendar"></i> <?= $category['programs'] ?> program</span>
                                    <span><i class="fas fa-percentage"></i> <?= $category['participation'] ?>% penyertaan</span>
                                </div>
                            </div>
                            <div class="program-stats">
                                <div class="rating" style="color: var(--primary);">
                                    <i class="fas fa-chart-line"></i>
                                    <?= round($category['participation'] / 10) ?>/10
                                </div>
                                <div class="participants">
                                    <?= round($category['participation'] / 20) ?> program/bulan
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- System Metrics -->
            <div class="chart-card" style="margin-bottom: 32px;">
                <div class="chart-header">
                    <h2 class="chart-title">Metrik Sistem</h2>
                    <p class="chart-subtitle">Prestasi dan kesihatan sistem</p>
                </div>
                
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value"><?= $stats['systemMetrics']['avg_rating'] ?>/5</div>
                        <div class="metric-label">Rating Purata Program</div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: <?= $stats['systemMetrics']['avg_rating'] * 20 ?>%; background: #f59e0b;"></div>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-value"><?= $stats['systemMetrics']['attendance_rate'] ?>%</div>
                        <div class="metric-label">Kadar Kehadiran</div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: <?= $stats['systemMetrics']['attendance_rate'] ?>%; background: #10b981;"></div>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-value"><?= $stats['systemMetrics']['feedback_rate'] ?>%</div>
                        <div class="metric-label">Kadar Maklum Balas</div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: <?= $stats['systemMetrics']['feedback_rate'] ?>%; background: var(--primary);"></div>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-value"><?= $stats['systemMetrics']['system_uptime'] ?>%</div>
                        <div class="metric-label">Uptime Sistem</div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: <?= $stats['systemMetrics']['system_uptime'] ?>%; background: #8b5cf6;"></div>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-value"><?= $stats['systemMetrics']['active_sessions'] ?></div>
                        <div class="metric-label">Sesi Aktif Sekarang</div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: <?= min(100, $stats['systemMetrics']['active_sessions'] / 2) ?>%; background: #f97316;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="chart-card">
                <div class="chart-header">
                    <h2 class="chart-title">Aktiviti Terkini</h2>
                    <p class="chart-subtitle">Aktiviti sistem dalam 24 jam terakhir</p>
                </div>
                
                <div class="activity-list">
                    <?php foreach ($stats['recentActivity'] as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon icon-<?= $activity['type'] ?>">
                            <?php if ($activity['type'] === 'program'): ?>
                                <i class="fas fa-calendar-plus"></i>
                            <?php elseif ($activity['type'] === 'user'): ?>
                                <i class="fas fa-user-plus"></i>
                            <?php elseif ($activity['type'] === 'feedback'): ?>
                                <i class="fas fa-comment-alt"></i>
                            <?php else: ?>
                                <i class="fas fa-server"></i>
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-action"><?= $activity['action'] ?></div>
                            <div class="activity-details"><?= $activity['details'] ?></div>
                        </div>
                        <div class="activity-time"><?= $activity['time'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </section>
    </main>
</div>

<script>
    // Animate charts on load
    document.addEventListener('DOMContentLoaded', function() {
        // Animate bars
        const bars = document.querySelectorAll('.bar');
        bars.forEach(bar => {
            const originalHeight = bar.style.height;
            bar.style.height = '0px';
            
            setTimeout(() => {
                bar.style.transition = 'height 1s ease';
                bar.style.height = originalHeight;
            }, 100);
        });
        
        // Animate progress bars
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
            const originalWidth = bar.style.width;
            bar.style.width = '0px';
            
            setTimeout(() => {
                bar.style.transition = 'width 1.5s ease';
                bar.style.width = originalWidth;
            }, 500);
        });
        
        // Auto refresh statistics every 30 seconds
        setInterval(() => {
            // In real app, this would fetch updated statistics
            console.log('Refreshing statistics...');
            
            // Update active sessions randomly for demo
            const activeSessions = document.querySelector('.metric-card:last-child .metric-value');
            const current = parseInt(activeSessions.textContent);
            const newValue = Math.max(100, Math.min(200, current + Math.floor(Math.random() * 21) - 10));
            activeSessions.textContent = newValue;
            
            // Update progress bar
            const progressBar = document.querySelector('.metric-card:last-child .progress-fill');
            progressBar.style.width = Math.min(100, newValue / 2) + '%';
            
        }, 30000);
    });
    
    // Export statistics
    function exportStatistics() {
        // In real app, generate and download report
        const data = {
            exported_at: new Date().toISOString(),
            period: '<?= $selectedPeriod ?>',
            statistics: <?= json_encode($stats) ?>
        };
        
        // Create download link
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(data, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", `statistik-sistem-<?= date('Y-m-d') ?>.json`);
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
        
        // Show notification
        showNotification('Statistik telah dieksport ke JSON');
    }
    
    // Print report
    function printReport() {
        window.print();
    }
    
    // Show notification
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary
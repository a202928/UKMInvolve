<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'statistik-sistem';
$adminInitial = strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1));

// 1. Get period filter: weekly (default), monthly, semester
$period = $_GET['period'] ?? 'weekly';
if (!in_array($period, ['weekly', 'monthly', 'semester'], true)) {
    $period = 'weekly';
}

// 2. Fetch all necessary data from database
$totalUsers = 0;
$totalPrograms = 0;
$totalRegs = 0;
$totalAtt = 0;

$allPrograms = [];
$allRegs = [];
$allAtts = [];
$allCategories = [];

if (db()->isConfigured()) {
    // Overall Counts
    $totalUsers = users()->countAll();
    
    $allPrograms = programs()->listWithCategory();
    $totalPrograms = count($allPrograms);
    
    $regRes = db()->select('pendaftaran', '?select=id,program_id,tarikh_daftar,status');
    $allRegs = $regRes['ok'] ? $regRes['data'] : [];
    $totalRegs = count($allRegs);
    
    $attRes = db()->select('kehadiran', '?select=id,program_id,status,created_at');
    $allAtts = $attRes['ok'] ? $attRes['data'] : [];
    
    // Total attendance = count of present ('Hadir') logs
    $totalAtt = count(array_filter($allAtts, fn($a) => ($a['status'] ?? '') === 'Hadir'));
    
    $allCategories = categories()->listAll();
}

// 3. Compute starting date/timestamp for the selected period
if ($period === 'weekly') {
    // Monday of this week 00:00:00
    $startTs = strtotime('monday this week 00:00:00');
    $periodLabel = 'Minggu Ini (Weekly)';
} elseif ($period === 'semester') {
    // Current academic semester: Jan-Jun or Jul-Dec
    $currentMonth = (int)date('n');
    if ($currentMonth <= 6) {
        $startTs = strtotime(date('Y-01-01 00:00:00'));
    } else {
        $startTs = strtotime(date('Y-07-01 00:00:00'));
    }
    $periodLabel = 'Semester Ini (Semester)';
} else {
    // Monthly: 1st of this month 00:00:00
    $startTs = strtotime(date('Y-m-01 00:00:00'));
    $periodLabel = 'Bulan Ini (Monthly)';
}

// 4. Filter data for the selected period
$filteredPrograms = array_filter($allPrograms, fn($p) => isset($p['created_at']) && strtotime($p['created_at']) >= $startTs);
$filteredRegs = array_filter($allRegs, fn($r) => isset($r['tarikh_daftar']) && strtotime($r['tarikh_daftar']) >= $startTs);
$filteredAtts = array_filter($allAtts, fn($a) => isset($a['created_at']) && strtotime($a['created_at']) >= $startTs && ($a['status'] ?? '') === 'Hadir');

$periodStats = [
    'programs' => count($filteredPrograms),
    'registrations' => count($filteredRegs),
    'attendance' => count($filteredAtts),
    'active_category' => 'Tiada Data'
];

// 5. Calculate Most Active / Top Performing Category in the period
// Defined as the category with the highest registrations in the period
$categoryRegCounts = [];
foreach ($allCategories as $cat) {
    $categoryRegCounts[$cat['id']] = 0;
}

foreach ($filteredRegs as $r) {
    $pId = $r['program_id'];
    // Find the category for this program
    foreach ($allPrograms as $p) {
        if ($p['id'] == $pId) {
            $catId = $p['kategori_id'] ?? null;
            if ($catId && isset($categoryRegCounts[$catId])) {
                $categoryRegCounts[$catId]++;
            }
            break;
        }
    }
}

if (!empty($categoryRegCounts)) {
    arsort($categoryRegCounts);
    $topCatId = key($categoryRegCounts);
    $topCatCount = current($categoryRegCounts);
    if ($topCatCount > 0) {
        foreach ($allCategories as $cat) {
            if ($cat['id'] == $topCatId) {
                $periodStats['active_category'] = $cat['nama'] . ' (' . $topCatCount . ' pendaftaran)';
                break;
            }
        }
    }
}

// 6. Chart 1: Programs by Category in the period
$chart1Data = [];
foreach ($allCategories as $cat) {
    $catId = $cat['id'];
    $count = count(array_filter($filteredPrograms, fn($p) => ($p['kategori_id'] ?? null) == $catId));
    $chart1Data[] = [
        'label' => $cat['nama'],
        'value' => $count
    ];
}

// 7. Chart 2: Registrations Trend & Chart 3: Attendance Trend
$chartLabels = [];
$trendReg = [];
$trendAtt = [];

if ($period === 'weekly') {
    $chartLabels = ['Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu', 'Ahad'];
    $trendReg = array_fill(0, 7, 0);
    $trendAtt = array_fill(0, 7, 0);
    
    for ($i = 0; $i < 7; $i++) {
        $dayStart = $startTs + ($i * 86400);
        $dayEnd = $dayStart + 86400;
        
        foreach ($filteredRegs as $r) {
            $ts = strtotime($r['tarikh_daftar']);
            if ($ts >= $dayStart && $ts < $dayEnd) {
                $trendReg[$i]++;
            }
        }
        foreach ($filteredAtts as $a) {
            $ts = strtotime($a['created_at']);
            if ($ts >= $dayStart && $ts < $dayEnd) {
                $trendAtt[$i]++;
            }
        }
    }
} elseif ($period === 'monthly') {
    $chartLabels = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'];
    $trendReg = array_fill(0, 4, 0);
    $trendAtt = array_fill(0, 4, 0);
    
    for ($i = 0; $i < 4; $i++) {
        $weekStart = $startTs + ($i * 7 * 86400);
        // Week 4 goes to end of month
        $weekEnd = ($i === 3) ? strtotime('first day of next month 00:00:00', $startTs) : ($weekStart + (7 * 86400));
        
        foreach ($filteredRegs as $r) {
            $ts = strtotime($r['tarikh_daftar']);
            if ($ts >= $weekStart && $ts < $weekEnd) {
                $trendReg[$i]++;
            }
        }
        foreach ($filteredAtts as $a) {
            $ts = strtotime($a['created_at']);
            if ($ts >= $weekStart && $ts < $weekEnd) {
                $trendAtt[$i]++;
            }
        }
    }
} else { // semester
    $semesterMonths = [];
    $currentMonth = (int)date('n');
    if ($currentMonth <= 6) {
        $semesterMonths = [1, 2, 3, 4, 5, 6];
    } else {
        $semesterMonths = [7, 8, 9, 10, 11, 12];
    }
    
    $trendReg = array_fill(0, 6, 0);
    $trendAtt = array_fill(0, 6, 0);
    
    $year = date('Y');
    for ($i = 0; $i < 6; $i++) {
        $m = $semesterMonths[$i];
        $monthName = date('M', mktime(0, 0, 0, $m, 1));
        $chartLabels[] = $monthName;
        
        $monthStart = strtotime("$year-$m-01 00:00:00");
        $monthEnd = strtotime("+1 month", $monthStart);
        
        foreach ($filteredRegs as $r) {
            $ts = strtotime($r['tarikh_daftar']);
            if ($ts >= $monthStart && $ts < $monthEnd) {
                $trendReg[$i]++;
            }
        }
        foreach ($filteredAtts as $a) {
            $ts = strtotime($a['created_at']);
            if ($ts >= $monthStart && $ts < $monthEnd) {
                $trendAtt[$i]++;
            }
        }
    }
}

// Scaling calculations for HTML charts
$maxCatVal = max(1, max(array_column($chart1Data, 'value')));
$maxRegVal = max(1, max($trendReg));
$maxAttVal = max(1, max($trendAtt));

$menu = [
    'dashboard-pentadbir' => ['Dashboard', 'fa-house'],
    'pengurusan-pengguna' => ['Pengguna', 'fa-users-gear'],
    'pengurusan-kategori' => ['Kategori', 'fa-layer-group'],
    'urus_mata_admin' => ['Urus Mata', 'fa-sliders-h'],
    'statistik-sistem' => ['Statistik', 'fa-chart-pie'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Period Metric Blocks */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .metric-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .metric-card h3 {
            font-size: 32px;
            font-weight: 800;
            color: var(--accent-blue);
            margin-bottom: 8px;
            font-family: 'Outfit', sans-serif;
        }
        .metric-card p {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 12px;
        }
        .progress-track {
            height: 6px;
            background: var(--bg-secondary);
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #60a5fa, var(--accent-blue));
            border-radius: 999px;
        }
        
        .top-performing-card {
            background: rgba(139, 92, 246, 0.05);
            border: 1px solid rgba(139, 92, 246, 0.1);
            border-radius: var(--radius-md);
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .top-performing-text h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            font-weight: 800;
        }
        .top-performing-text h3 {
            font-size: 20px;
            color: #7c3aed;
            margin-top: 6px;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
        }
        
        /* Bar Graph Custom layouts */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
            margin-bottom: 40px;
        }
        .chart-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .chart-card h2 {
            font-size: 18px;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 4px;
        }
        .chart-card-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        .chart-wrapper {
            height: 200px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            padding-top: 24px;
            border-bottom: 1px solid var(--border);
        }
        .chart-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            height: 100%;
        }
        .chart-bar {
            border-radius: 6px 6px 0 0;
            position: relative;
            transition: var(--transition);
            min-height: 2px;
        }
        .chart-bar-blue {
            background: linear-gradient(180deg, #60a5fa, var(--accent-blue));
        }
        .chart-bar-blue:hover {
            background: linear-gradient(180deg, #93c5fd, #1d4ed8);
            transform: scaleX(1.05);
        }
        .chart-bar-orange {
            background: linear-gradient(180deg, #f97316, var(--accent));
        }
        .chart-bar-orange:hover {
            background: linear-gradient(180deg, #fb923c, #c2410c);
            transform: scaleX(1.05);
        }
        .chart-bar-green {
            background: linear-gradient(180deg, #34d399, #10b981);
        }
        .chart-bar-green:hover {
            background: linear-gradient(180deg, #6ee7b7, #047857);
            transform: scaleX(1.05);
        }
        .chart-value {
            position: absolute;
            top: -24px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: 800;
            color: var(--text-primary);
        }
        .chart-label {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 8px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <!-- HEADER -->
            <div class="dashboard-header-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div class="dashboard-header-title">
                    <h1>Reporting & System Analytics</h1>
                    <p>Track student engagement, program creations, attendance ratios, and categories activity metrics.</p>
                </div>
                <button class="btn btn-primary" onclick="exportStatistics()" style="background-color: var(--accent-blue); color: var(--white); border: none; font-weight: 700; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                    <i class="fas fa-download"></i> Export CSV Report
                </button>
            </div>

            <!-- OVERALL STATS CARDS -->
            <div class="stats-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 32px;">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Users</h3>
                        <div class="stat-val"><?= number_format($totalUsers) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Events</h3>
                        <div class="stat-val"><?= number_format($totalPrograms) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Registrations</h3>
                        <div class="stat-val"><?= number_format($totalRegs) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-file-signature"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Attendance</h3>
                        <div class="stat-val"><?= number_format($totalAtt) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-purple">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>

            <!-- TAB FILTERS -->
            <div class="tab-nav-wrapper" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div style="font-size: 16px; font-weight: 700; color: var(--text-primary);">
                    Filtering Period: <span style="color: var(--accent-blue); font-family: 'Outfit';"><?= $periodLabel ?></span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="?period=weekly" class="tab-nav-btn <?= $period === 'weekly' ? 'active' : '' ?>" style="display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 20px; font-size: 14px; font-weight: 700; border-radius: 999px; transition: var(--transition);">Weekly</a>
                    <a href="?period=monthly" class="tab-nav-btn <?= $period === 'monthly' ? 'active' : '' ?>" style="display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 20px; font-size: 14px; font-weight: 700; border-radius: 999px; transition: var(--transition);">Monthly</a>
                    <a href="?period=semester" class="tab-nav-btn <?= $period === 'semester' ? 'active' : '' ?>" style="display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 20px; font-size: 14px; font-weight: 700; border-radius: 999px; transition: var(--transition);">Semester</a>
                </div>
            </div>

            <!-- PERIOD STATS -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <h3><?= number_format($periodStats['programs']) ?></h3>
                    <p>Programmes Created</p>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= min(100, $periodStats['programs'] * 10) ?>%"></div></div>
                </div>

                <div class="metric-card">
                    <h3><?= number_format($periodStats['registrations']) ?></h3>
                    <p>New Registrations</p>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= min(100, $periodStats['registrations'] * 5) ?>%"></div></div>
                </div>

                <div class="metric-card">
                    <h3><?= number_format($periodStats['attendance']) ?></h3>
                    <p>Attendance Verified</p>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= min(100, $periodStats['attendance'] * 5) ?>%"></div></div>
                </div>
            </div>

            <!-- TOP PERFORMING CATEGORY CARD -->
            <div class="top-performing-card">
                <div class="top-performing-text">
                    <h4>Most Active Programme Category (Selected Period)</h4>
                    <h3><?= htmlspecialchars($periodStats['active_category']) ?></h3>
                </div>
                <div class="dashboard-stat-icon stat-icon-purple" style="background: rgba(139, 92, 246, 0.1); color: #7c3aed; margin-bottom: 0;">
                    <i class="fas fa-fire"></i>
                </div>
            </div>

            <!-- CHARTS GRID -->
            <div class="charts-grid">
                <!-- Chart 1: Programs by Category -->
                <div class="chart-card">
                    <h2>Programmes by Category</h2>
                    <p class="chart-card-subtitle">Volume of programs created under each co-curricular category.</p>
                    <div class="chart-wrapper">
                        <?php foreach ($chart1Data as $c1):
                            $height = round(($c1['value'] / $maxCatVal) * 130);
                        ?>
                            <div class="chart-item">
                                <div class="chart-bar chart-bar-blue" style="height: <?= $height ?>px; width: 32px; margin: 0 auto;" title="<?= htmlspecialchars($c1['label']) ?>: <?= $c1['value'] ?>">
                                    <span class="chart-value"><?= $c1['value'] ?></span>
                                </div>
                                <div class="chart-label" title="<?= htmlspecialchars($c1['label']) ?>"><?= htmlspecialchars($c1['label']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Chart 2: Registrations Trend -->
                <div class="chart-card">
                    <h2>Registrations Trend</h2>
                    <p class="chart-card-subtitle">Student registration actions distribution over the selected period.</p>
                    <div class="chart-wrapper">
                        <?php for ($i = 0; $i < count($chartLabels); $i++):
                            $label = $chartLabels[$i];
                            $val = $trendReg[$i];
                            $height = round(($val / $maxRegVal) * 130);
                        ?>
                            <div class="chart-item">
                                <div class="chart-bar chart-bar-orange" style="height: <?= $height ?>px; width: 28px; margin: 0 auto;" title="<?= htmlspecialchars($label) ?>: <?= $val ?>">
                                    <span class="chart-value"><?= $val ?></span>
                                </div>
                                <div class="chart-label"><?= htmlspecialchars($label) ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Chart 3: Attendance Trend -->
                <div class="chart-card">
                    <h2>Verified Attendance Trend</h2>
                    <p class="chart-card-subtitle">Count of students who completed attendance check-in.</p>
                    <div class="chart-wrapper">
                        <?php for ($i = 0; $i < count($chartLabels); $i++):
                            $label = $chartLabels[$i];
                            $val = $trendAtt[$i];
                            $height = round(($val / $maxAttVal) * 130);
                        ?>
                            <div class="chart-item">
                                <div class="chart-bar chart-bar-green" style="height: <?= $height ?>px; width: 28px; margin: 0 auto;" title="<?= htmlspecialchars($label) ?>: <?= $val ?>">
                                    <span class="chart-value"><?= $val ?></span>
                                </div>
                                <div class="chart-label"><?= htmlspecialchars($label) ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    function exportStatistics() {
        const period = <?= json_encode($period) ?>;
        const stats = <?= json_encode([
            'totalUsers' => $totalUsers,
            'totalPrograms' => $totalPrograms,
            'totalRegistrations' => $totalRegs,
            'totalAttendance' => $totalAtt,
            'periodPrograms' => $periodStats['programs'],
            'periodRegistrations' => $periodStats['registrations'],
            'periodAttendance' => $periodStats['attendance'],
            'activeCategory' => $periodStats['active_category']
        ]) ?>;
        
        let csv = "Indicator,Value\n";
        for(let k in stats){
            csv += `"${k}","${stats[k]}"\n`;
        }
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `system_analytics_report_${period}.csv`;
        link.click();
    }
    </script>
</body>
</html>
<?php
session_start();
$_SESSION['role'] = 'pentadbir';
$activePage = 'dashboard-pentadbir';

// Sample statistics data
$stats = [
    'total' => [
        'users' => 2450,
        'programs' => 156,
        'categories' => 12,
        'feedback' => 1245
    ],
    'systemMetrics' => [
        'avg_rating' => 4.7,
        'attendance_rate' => 82,
        'feedback_rate' => 65,
        'system_uptime' => 99.8,
        'active_sessions' => 124
    ]
];

// Growth calculation
$userGrowthPercent = round(($stats['total']['users'] - 2000) / 2000 * 100, 1);
$programGrowthPercent = round(($stats['total']['programs'] - 120) / 120 * 100, 1);
$feedbackGrowthPercent = round(($stats['total']['feedback'] - 1000) / 1000 * 100, 1);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pentadbir | UKMInvolve</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.dashboard-container{
    max-width:1400px;
    margin:auto;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:24px;
}

.stat-card{
    background:var(--surface);
    padding:24px;
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    position:relative;
}

.stat-icon{
    position:absolute;
    right:20px;
    top:20px;
    font-size:30px;
    color:white;
    padding:15px;
    border-radius:12px;
}

.icon-users{background:#3b82f6;}
.icon-programs{background:#10b981;}
.icon-categories{background:#8b5cf6;}
.icon-feedback{background:#f59e0b;}

.stat-value{
    font-size:38px;
    font-weight:700;
}

.stat-title{
    font-size:14px;
    color:gray;
}

.stat-trend{
    font-size:14px;
    margin-top:6px;
    color:#10b981;
}

.metrics-grid{
    margin-top:40px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
}

.metric-card{
    background:var(--surface);
    padding:20px;
    border-radius:12px;
    text-align:center;
    box-shadow:var(--shadow);
}

.metric-value{
    font-size:28px;
    font-weight:bold;
}
</style>
</head>

<body>

<div class="app-layout">

<!-- SIDEBAR -->
<?php include 'sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="main-content">

<!-- TOPBAR -->
<header class="topbar"></header>


<section class="content dashboard-container">

<h1>Dashboard Pentadbir</h1>
<p>Ringkasan keseluruhan sistem UKMInvolve</p>

<!-- MAIN STATS -->
<div class="stats-grid">

<div class="stat-card">
<div class="stat-icon icon-users"><i class="fas fa-users"></i></div>
<div class="stat-value"><?= number_format($stats['total']['users']) ?></div>
<div class="stat-title">Jumlah Pengguna</div>
<div class="stat-trend">+<?= $userGrowthPercent ?>%</div>
</div>

<div class="stat-card">
<div class="stat-icon icon-programs"><i class="fas fa-calendar"></i></div>
<div class="stat-value"><?= $stats['total']['programs'] ?></div>
<div class="stat-title">Jumlah Program</div>
<div class="stat-trend">+<?= $programGrowthPercent ?>%</div>
</div>

<div class="stat-card">
<div class="stat-icon icon-categories"><i class="fas fa-tags"></i></div>
<div class="stat-value"><?= $stats['total']['categories'] ?></div>
<div class="stat-title">Kategori Program</div>
</div>

<div class="stat-card">
<div class="stat-icon icon-feedback"><i class="fas fa-comments"></i></div>
<div class="stat-value"><?= $stats['total']['feedback'] ?></div>
<div class="stat-title">Maklum Balas</div>
<div class="stat-trend">+<?= $feedbackGrowthPercent ?>%</div>
</div>

</div>

<!-- SYSTEM METRICS -->
<h2 style="margin-top:40px;">Prestasi Sistem</h2>

<div class="metrics-grid">

<div class="metric-card">
<div class="metric-value"><?= $stats['systemMetrics']['avg_rating'] ?>/5</div>
<div>Rating Program</div>
</div>

<div class="metric-card">
<div class="metric-value"><?= $stats['systemMetrics']['attendance_rate'] ?>%</div>
<div>Kehadiran</div>
</div>

<div class="metric-card">
<div class="metric-value"><?= $stats['systemMetrics']['feedback_rate'] ?>%</div>
<div>Kadar Feedback</div>
</div>

<div class="metric-card">
<div class="metric-value"><?= $stats['systemMetrics']['system_uptime'] ?>%</div>
<div>System Uptime</div>
</div>

<div class="metric-card">
<div class="metric-value"><?= $stats['systemMetrics']['active_sessions'] ?></div>
<div>Sesi Aktif</div>
</div>

</div>

</section>
</main>
</div>

<script>
function exportStatistics(){
    alert("Fungsi export belum disambungkan dengan database lagi.");
}
</script>

</body>
</html>

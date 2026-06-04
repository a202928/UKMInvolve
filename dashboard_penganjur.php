<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'dashboard_penganjur';

$menu = [
    'dashboard_penganjur' => ['Dashboard', 'fa-house'],
    'hebahan-program' => ['Hebahan', 'fa-bullhorn'],
    'urus-program' => ['Urus Program', 'fa-calendar-check'],
    'peserta-kehadiran' => ['Peserta', 'fa-users'],
    'laporan-statistik' => ['Laporan', 'fa-chart-column'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];

$allPrograms = db()->isConfigured() ? programs()->listWithCategory() : [];
$totalParticipants = array_sum(array_column($allPrograms, 'peserta_semasa'));
$activeCount = count(array_filter($allPrograms, fn($p) => programStatusLabel($p['tarikh'] ?? null) === 'Aktif'));
$ratings = array_column($allPrograms, 'rating');
$avgRating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;

$stats = [
    ['title' => 'Total Programmes', 'value' => (string) count($allPrograms), 'icon' => 'fa-calendar-days', 'class' => 'stat-blue'],
    ['title' => 'Total Participants', 'value' => (string) $totalParticipants, 'icon' => 'fa-users', 'class' => 'stat-green'],
    ['title' => 'Active Programmes', 'value' => (string) $activeCount, 'icon' => 'fa-chart-line', 'class' => 'stat-orange'],
    ['title' => 'Average Rating', 'value' => $avgRating . '/5', 'icon' => 'fa-star', 'class' => 'stat-purple']
];

$chartData = [
    ['month' => 'Sep', 'value' => 45],
    ['month' => 'Oct', 'value' => 52],
    ['month' => 'Nov', 'value' => 48],
    ['month' => 'Dec', 'value' => 65],
    ['month' => 'Jan', 'value' => 75],
    ['month' => 'Feb', 'value' => max(1, $totalParticipants)],
];

$maxValue = max(array_column($chartData, 'value'));

$programs = [];
foreach (array_slice($allPrograms, 0, 4) as $row) {
    $programs[] = programs()->toOrganizerDashboardRow($row);
}

$organizerInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Dashboard Penganjur | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;
    --muted:#6b7280;--text:#111827;--green:#10b981;--orange:#f97316;
    --purple:#8b5cf6;
}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button,input{font-family:inherit}

.dashboard-wrapper{height:100vh;display:grid;grid-template-columns:240px 1fr;background:var(--page);overflow:hidden}

/* SIDEBAR */
.sidebar{height:100vh;background:#fff;border-right:1px solid var(--border);padding:28px 20px;display:flex;flex-direction:column;justify-content:space-between}
.sidebar-header{display:flex;align-items:center;gap:12px;margin-bottom:30px}
.sidebar-logo-wrap{width:38px;height:38px;border-radius:14px;background:#eaf4ff;display:flex;align-items:center;justify-content:center}
.sidebar-logo{width:28px;height:28px;object-fit:contain}
.sidebar-title{font-size:19px;font-weight:800}
.sidebar-label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;padding-left:8px}
.sidebar-nav{display:flex;flex-direction:column;gap:8px}
.sidebar-link{padding:11px 12px;border-radius:14px;display:flex;gap:12px;align-items:center;color:#374151;font-weight:500;transition:.25s}
.sidebar-link i{width:18px;text-align:center}
.sidebar-link.active,.sidebar-link:hover{background:#eff6ff;color:#2563eb;font-weight:700}
.logout-link{color:#f97316}
.logout-link:hover{background:#fff7ed;color:#f97316}
.user-profile{display:flex;align-items:center;gap:10px;background:#f8fbff;border:1px solid var(--border);border-radius:16px;padding:12px}
.user-avatar{width:38px;height:38px;border-radius:50%;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.user-profile h4{font-size:14px}
.user-profile p{font-size:12px;color:var(--muted)}

/* MAIN */
.main-section{height:100vh;overflow-y:auto;padding:28px;background:var(--page)}
.main-section::-webkit-scrollbar{width:8px}
.main-section::-webkit-scrollbar-thumb{background:#bfdbfe;border-radius:999px}

.top-search{display:flex;align-items:center;gap:14px;margin-bottom:22px}
.search-box{flex:1;background:white;border:1px solid var(--border);border-radius:999px;padding:14px 18px;display:flex;align-items:center;gap:10px}
.search-box input{border:none;outline:none;background:transparent;width:100%;font-size:14px}
.search-box i{color:#9ca3af}
.icon-btn{width:42px;height:42px;border-radius:50%;border:1px solid var(--border);background:white;color:#374151;cursor:pointer;position:relative}
.notification-dot{position:absolute;top:9px;right:10px;width:8px;height:8px;background:#ef4444;border-radius:50%}

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{color:var(--muted);font-size:14px;margin-top:4px}
.create-btn{background:var(--primary);color:white;padding:12px 18px;border-radius:999px;font-weight:800;display:flex;align-items:center;gap:8px}

.hero-card{
    background:linear-gradient(135deg,#7bb6ff,#5b8def);
    color:white;
    border-radius:26px;
    padding:28px;
    margin-bottom:22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    overflow:hidden;
    position:relative;
    box-shadow:0 18px 38px rgba(91,141,239,.20);
}
.hero-card::before{content:"";position:absolute;right:-55px;top:-60px;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.13)}
.hero-card h2{font-size:30px;margin-bottom:8px;position:relative;z-index:1}
.hero-card p{color:#eef6ff;font-size:14px;position:relative;z-index:1}
.hero-icon{width:86px;height:86px;border-radius:24px;background:rgba(255,255,255,.20);display:flex;align-items:center;justify-content:center;font-size:38px;position:relative;z-index:1}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{border-radius:22px;padding:18px;color:white;box-shadow:0 10px 25px rgba(37,99,235,.10);display:flex;justify-content:space-between;align-items:center}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:rgba(255,255,255,.9)}
.stat-icon{width:48px;height:48px;border-radius:16px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:21px}
.stat-blue{background:linear-gradient(135deg,#60a5fa,#2563eb)}
.stat-green{background:linear-gradient(135deg,#34d399,#059669)}
.stat-orange{background:linear-gradient(135deg,#fbbf24,#f97316)}
.stat-purple{background:linear-gradient(135deg,#a78bfa,#7c3aed)}

.dashboard-grid{display:grid;grid-template-columns:1.4fr .9fr;gap:22px}
.card{background:white;border:1px solid var(--border);border-radius:26px;padding:22px;box-shadow:0 8px 20px rgba(37,99,235,.06);margin-bottom:22px}
.card-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px}
.card-title{font-size:21px;font-weight:800}
.card-subtitle{font-size:13px;color:var(--muted);margin-top:4px}

.chart-wrapper{height:230px;display:flex;align-items:flex-end;justify-content:space-between;gap:14px;padding-top:35px}
.chart-item{flex:1;text-align:center}
.chart-bar{height:100px;border-radius:14px 14px 6px 6px;background:linear-gradient(180deg,#7bb6ff,#2563eb);position:relative;transition:.25s}
.chart-bar:hover{transform:scaleY(1.05)}
.chart-value{position:absolute;top:-25px;left:50%;transform:translateX(-50%);font-size:12px;font-weight:800;color:var(--dark)}
.chart-month{font-size:12px;color:var(--muted);margin-top:8px}

.program-list{display:flex;flex-direction:column;gap:14px}
.program-item{border:1px solid var(--border);border-radius:20px;padding:16px;display:flex;justify-content:space-between;align-items:center;gap:12px;transition:.25s}
.program-item:hover{transform:translateY(-3px);box-shadow:0 12px 25px rgba(37,99,235,.10)}
.program-name{font-size:16px;font-weight:800;margin-bottom:6px}
.program-date{font-size:13px;color:var(--muted)}
.participant-count{font-size:15px;font-weight:800;color:var(--dark);text-align:right;margin-bottom:6px}
.status-badge{padding:6px 11px;border-radius:999px;font-size:12px;font-weight:800}
.status-active{background:#ecfdf5;color:#059669}
.status-upcoming{background:#eff6ff;color:#2563eb}

.quick-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.quick-action{border:1px solid var(--border);border-radius:20px;padding:18px;background:#fff;display:flex;align-items:center;gap:14px;transition:.25s}
.quick-action:hover{transform:translateY(-3px);box-shadow:0 12px 25px rgba(37,99,235,.10);border-color:var(--primary)}
.action-icon{width:46px;height:46px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:19px}
.icon-blue{background:#eff6ff;color:#2563eb}
.icon-green{background:#ecfdf5;color:#059669}
.icon-orange{background:#fff7ed;color:#f97316}
.icon-purple{background:#f5f3ff;color:#7c3aed}
.quick-action span{font-weight:800;font-size:14px}

@media(max-width:1100px){
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .dashboard-grid{grid-template-columns:1fr}
}
@media(max-width:900px){
    body{overflow:auto}
    .dashboard-wrapper{grid-template-columns:1fr;height:auto}
    .sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
    .sidebar-nav{flex-direction:row;overflow-x:auto}
    .sidebar-link{white-space:nowrap}
    .user-profile{display:none}
    .main-section{height:auto;overflow:visible}
}
@media(max-width:600px){
    .stats-grid,.quick-grid{grid-template-columns:1fr}
    .page-header{flex-direction:column;align-items:flex-start;gap:12px}
}
</style>
</head>

<body>
<div class="dashboard-wrapper">

<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <div class="sidebar-logo-wrap">
                <img src="UKM.png" class="sidebar-logo" alt="UKM">
            </div>
            <h3 class="sidebar-title">UKMInvolve</h3>
        </div>

        <p class="sidebar-label">Menu</p>
        <nav class="sidebar-nav">
            <?php foreach ($menu as $page => $item): ?>
                <a href="<?= $page ?>.php"
                   class="sidebar-link <?= ($activePage === $page) ? 'active' : '' ?> <?= ($page === 'logout') ? 'logout-link' : '' ?>">
                    <i class="fas <?= $item[1] ?>"></i>
                    <?= $item[0] ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="user-profile">
        <div class="user-avatar"><?= htmlspecialchars($organizerInitial) ?></div>
        <div>
            <h4><?= htmlspecialchars($_SESSION['nama'] ?? 'Penganjur') ?></h4>
            <p><?= htmlspecialchars($_SESSION['emel'] ?? '') ?></p>
        </div>
    </div>
</aside>

<main class="main-section">

    <div class="top-search">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search programme or participant...">
        </div>

        <button class="icon-btn">
            <i class="fas fa-bell"></i>
            <span class="notification-dot"></span>
        </button>
    </div>

    <div class="page-header">
        <div>
            <h1>Organizer Dashboard</h1>
            <p>Manage programme announcements, participants and performance summary.</p>
        </div>

        <a href="hebahan-program.php" class="create-btn">
            <i class="fas fa-plus"></i> New Programme
        </a>
    </div>

    <div class="hero-card">
        <div>
            <h2>Welcome Back, Organizer</h2>
            <p>Track your programme performance and manage upcoming activities efficiently.</p>
        </div>

        <div class="hero-icon">
            <i class="fas fa-bullhorn"></i>
        </div>
    </div>

    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card <?= $stat['class'] ?>">
                <div>
                    <h2><?= $stat['value'] ?></h2>
                    <p><?= $stat['title'] ?></p>
                </div>
                <div class="stat-icon">
                    <i class="fas <?= $stat['icon'] ?>"></i>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-grid">

        <div>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Student Participation Trend</h2>
                        <p class="card-subtitle">Number of participants by month</p>
                    </div>
                </div>

                <div class="chart-wrapper">
                    <?php foreach ($chartData as $data): ?>
                        <?php $height = ($data['value'] / $maxValue) * 180; ?>
                        <div class="chart-item">
                            <div class="chart-bar" style="height: <?= $height ?>px;">
                                <span class="chart-value"><?= $data['value'] ?></span>
                            </div>
                            <div class="chart-month"><?= $data['month'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Quick Actions</h2>
                        <p class="card-subtitle">Fast access for organiser tasks</p>
                    </div>
                </div>

                <div class="quick-grid">
                    <a href="hebahan-program.php" class="quick-action">
                        <div class="action-icon icon-blue">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <span>Create Announcement</span>
                    </a>

                    <a href="urus-program.php" class="quick-action">
                        <div class="action-icon icon-green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span>Manage Programmes</span>
                    </a>

                    <a href="peserta-kehadiran.php" class="quick-action">
                        <div class="action-icon icon-orange">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <span>Participant Attendance</span>
                    </a>

                    <a href="laporan-statistik.php" class="quick-action">
                        <div class="action-icon icon-purple">
                            <i class="fas fa-chart-column"></i>
                        </div>
                        <span>View Reports</span>
                    </a>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Upcoming Programmes</h2>
                        <p class="card-subtitle">Active and upcoming programmes</p>
                    </div>
                </div>

                <div class="program-list">
                    <?php foreach ($programs as $program): ?>
                        <?php $statusClass = $program['status'] === 'Active' ? 'status-active' : 'status-upcoming'; ?>

                        <div class="program-item">
                            <div>
                                <h3 class="program-name"><?= $program['name'] ?></h3>
                                <p class="program-date">
                                    <i class="fas fa-calendar"></i> <?= $program['date'] ?>
                                </p>
                            </div>

                            <div>
                                <div class="participant-count"><?= $program['participants'] ?></div>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $program['status'] ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top:18px;text-align:center;">
                    <a href="urus-program.php" class="create-btn" style="display:inline-flex;">
                        <i class="fas fa-gear"></i> Manage All
                    </a>
                </div>
            </div>
        </div>

    </div>

</main>
</div>

</body>
</html>
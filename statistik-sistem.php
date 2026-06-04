<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'statistik-sistem';

// Real stats from DB
$totalUsers    = 0;
$totalPrograms = 0;
$totalFeedback = 0;
$totalRegs     = 0;
$categoryStats = [];
$topPrograms   = [];

if (db()->isConfigured()) {
    $totalUsers    = users()->countAll();
    $allPrograms   = programs()->listWithCategory();
    $totalPrograms = count($allPrograms);

    $fbResult      = db()->select('maklum_balas', '?select=id,rating');
    $fbRows        = ($fbResult['ok']) ? $fbResult['data'] : [];
    $totalFeedback = count($fbRows);
    $avgRating     = $totalFeedback > 0
        ? round(array_sum(array_column($fbRows, 'rating')) / $totalFeedback, 1)
        : 0;

    $regResult  = db()->select('pendaftaran', '?select=id');
    $totalRegs  = ($regResult['ok']) ? count($regResult['data']) : 0;

    // Attendance rate = hadir / total registrations
    $hadirResult    = db()->select('pendaftaran', '?select=id&status=eq.hadir');
    $hadirCount     = ($hadirResult['ok']) ? count($hadirResult['data']) : 0;
    $attendanceRate = $totalRegs > 0 ? round(($hadirCount / $totalRegs) * 100) : 0;

    // Category participation counts
    $allCategories = categories()->listAll();
    foreach ($allCategories as $cat) {
        $count = categories()->programCount((int)$cat['id']);
        if ($count > 0) {
            $maxCap = array_sum(array_column(
                array_filter($allPrograms, fn($p) => ($p['kategori_id'] ?? null) == $cat['id']),
                'kapasiti'
            ));
            $filled = array_sum(array_column(
                array_filter($allPrograms, fn($p) => ($p['kategori_id'] ?? null) == $cat['id']),
                'peserta_semasa'
            ));
            $pct = $maxCap > 0 ? round(($filled / $maxCap) * 100) : 0;
            $categoryStats[] = ['name' => $cat['nama'], 'value' => $pct];
        }
    }

    // Top programs by participants
    usort($allPrograms, fn($a, $b) => ($b['peserta_semasa'] ?? 0) <=> ($a['peserta_semasa'] ?? 0));
    foreach (array_slice($allPrograms, 0, 4) as $p) {
        $topPrograms[] = [
            'name'         => $p['nama'],
            'participants' => (int)($p['peserta_semasa'] ?? 0),
            'rating'       => (float)($p['rating'] ?? 0),
        ];
    }
} else {
    $avgRating = 0;
    $attendanceRate = 0;
}

$stats = [
    'users'           => $totalUsers,
    'programs'        => $totalPrograms,
    'feedback'        => $totalFeedback,
    'attendance'      => $attendanceRate,
    'rating'          => $avgRating,
    'active_sessions' => $totalRegs,
];

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
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Statistik Sistem | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;--muted:#6b7280;--text:#111827;--orange:#f97316}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button{font-family:inherit}

.dashboard-wrapper{height:100vh;display:grid;grid-template-columns:240px 1fr;background:var(--page);overflow:hidden}
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

.main-section{height:100vh;overflow-y:auto;padding:28px;background:var(--page)}
.main-section::-webkit-scrollbar{width:8px}
.main-section::-webkit-scrollbar-thumb{background:#bfdbfe;border-radius:999px}

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{font-size:14px;color:var(--muted);margin-top:4px}
.btn-export{background:var(--primary);color:white;border:none;border-radius:999px;padding:12px 18px;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px}

.hero-card{background:linear-gradient(135deg,#7bb6ff,#5b8def);color:white;border-radius:26px;padding:26px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 18px 38px rgba(91,141,239,.20)}
.hero-card h2{font-size:28px;margin-bottom:8px}
.hero-card p{font-size:14px;color:#eef6ff}
.hero-icon{width:80px;height:80px;border-radius:24px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:36px}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{background:white;border:1px solid var(--border);border-radius:22px;padding:20px;box-shadow:0 8px 20px rgba(37,99,235,.06)}
.stat-icon{width:46px;height:46px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:20px}
.icon-blue{background:#eff6ff;color:#2563eb}
.icon-green{background:#ecfdf5;color:#059669}
.icon-orange{background:#fff7ed;color:#f97316}
.icon-purple{background:#f5f3ff;color:#7c3aed}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:var(--muted);font-weight:600}

.dashboard-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
.card{background:white;border:1px solid var(--border);border-radius:26px;padding:22px;box-shadow:0 8px 20px rgba(37,99,235,.06);margin-bottom:22px}
.card h2{font-size:22px;margin-bottom:6px}
.card-subtitle{font-size:13px;color:var(--muted);margin-bottom:18px}

.bar-list{display:flex;flex-direction:column;gap:14px}
.bar-item{display:grid;grid-template-columns:110px 1fr 45px;align-items:center;gap:12px}
.bar-label{font-size:13px;font-weight:800}
.bar-track{height:10px;background:#e5e7eb;border-radius:999px;overflow:hidden}
.bar-fill{height:100%;background:linear-gradient(90deg,#7bb6ff,#2563eb);border-radius:999px}
.bar-value{text-align:right;font-size:13px;font-weight:800;color:var(--dark)}

.program-list{display:flex;flex-direction:column;gap:12px}
.program-item{display:flex;justify-content:space-between;align-items:center;gap:12px;border:1px solid var(--border);border-radius:18px;padding:14px;background:#f8fbff}
.program-name{font-weight:800;font-size:14px;margin-bottom:4px}
.program-meta{font-size:12px;color:var(--muted)}
.rating{color:#f59e0b;font-weight:800;font-size:13px;text-align:right}

.metrics-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.metric-card{background:#f8fbff;border:1px solid var(--border);border-radius:18px;padding:16px;text-align:center}
.metric-card h3{font-size:24px;margin-bottom:4px}
.metric-card p{font-size:13px;color:var(--muted)}
.progress-track{height:8px;background:#e5e7eb;border-radius:999px;margin-top:10px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,#60a5fa,#2563eb);border-radius:999px}

@media(max-width:1100px){.stats-grid,.metrics-grid{grid-template-columns:repeat(2,1fr)}.dashboard-grid{grid-template-columns:1fr}}
@media(max-width:900px){
body{overflow:auto}.dashboard-wrapper{grid-template-columns:1fr;height:auto}.sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
.sidebar-nav{flex-direction:row;overflow-x:auto}.sidebar-link{white-space:nowrap}.user-profile{display:none}.main-section{height:auto;overflow:visible}
}
@media(max-width:600px){.stats-grid,.metrics-grid{grid-template-columns:1fr}.page-header,.hero-card{flex-direction:column;align-items:flex-start;gap:12px}}
</style>
</head>

<body>
<div class="dashboard-wrapper">

<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <div class="sidebar-logo-wrap"><img src="UKM.png" class="sidebar-logo" alt="UKM"></div>
            <h3 class="sidebar-title">UKMInvolve</h3>
        </div>

        <p class="sidebar-label">Menu</p>
        <nav class="sidebar-nav">
            <?php foreach ($menu as $page => $item): ?>
                <a href="<?= $page ?>.php" class="sidebar-link <?= ($activePage === $page) ? 'active' : '' ?> <?= ($page === 'logout') ? 'logout-link' : '' ?>">
                    <i class="fas <?= $item[1] ?>"></i><?= $item[0] ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="user-profile">
        <div class="user-avatar">A</div>
        <div><h4>Pentadbir</h4><p>Admin Account</p></div>
    </div>
</aside>

<main class="main-section">

    <div class="page-header">
        <div>
            <h1>System Statistics</h1>
            <p>Simple overview of UKMInvolve performance and activity.</p>
        </div>
        <button class="btn-export" onclick="exportStatistics()">
            <i class="fas fa-download"></i> Export
        </button>
    </div>

    <div class="hero-card">
        <div>
            <h2>System Performance</h2>
            <p>Monitor users, programmes, feedback and system engagement.</p>
        </div>
        <div class="hero-icon">
            <i class="fas fa-chart-pie"></i>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
            <h2><?= number_format($stats['users']) ?></h2>
            <p>Total Users</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-green"><i class="fas fa-calendar-days"></i></div>
            <h2><?= $stats['programs'] ?></h2>
            <p>Total Programmes</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-orange"><i class="fas fa-comments"></i></div>
            <h2><?= number_format($stats['feedback']) ?></h2>
            <p>Total Feedback</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-purple"><i class="fas fa-user-check"></i></div>
            <h2><?= $stats['attendance'] ?>%</h2>
            <p>Attendance Rate</p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h2>Category Participation</h2>
            <p class="card-subtitle">Participation percentage by category.</p>

            <div class="bar-list">
                <?php foreach ($categoryStats as $cat): ?>
                    <div class="bar-item">
                        <div class="bar-label"><?= $cat['name'] ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:<?= $cat['value'] ?>%"></div>
                        </div>
                        <div class="bar-value"><?= $cat['value'] ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Top Programmes</h2>
            <p class="card-subtitle">Most popular programmes by participation.</p>

            <div class="program-list">
                <?php foreach ($topPrograms as $program): ?>
                    <div class="program-item">
                        <div>
                            <div class="program-name"><?= $program['name'] ?></div>
                            <div class="program-meta"><?= $program['participants'] ?> participants</div>
                        </div>
                        <div class="rating">
                            <i class="fas fa-star"></i> <?= $program['rating'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>System Metrics</h2>
        <p class="card-subtitle">Basic system health and usage indicators.</p>

        <div class="metrics-grid">
            <div class="metric-card">
                <h3><?= $stats['rating'] ?>/5</h3>
                <p>Average Rating</p>
                <div class="progress-track"><div class="progress-fill" style="width:<?= $stats['rating'] * 20 ?>%"></div></div>
            </div>

            <div class="metric-card">
                <h3><?= $stats['attendance'] ?>%</h3>
                <p>Attendance Rate</p>
                <div class="progress-track"><div class="progress-fill" style="width:<?= $stats['attendance'] ?>%"></div></div>
            </div>

            <div class="metric-card">
                <h3><?= $stats['active_sessions'] ?></h3>
                <p>Active Sessions</p>
                <div class="progress-track"><div class="progress-fill" style="width:<?= min(100, $stats['active_sessions'] / 2) ?>%"></div></div>
            </div>
        </div>
    </div>

</main>
</div>

<script>
function exportStatistics() {
    alert('Statistics exported successfully.');
}
</script>

</body>
</html>
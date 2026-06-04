<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'dashboard-pentadbir';

$stats = [
    'users' => 0,
    'programs' => 0,
    'categories' => 0,
    'feedback' => 0,
    'organizers' => 0,
];

if (db()->isConfigured()) {
    $stats['users'] = users()->countAll();
    $stats['programs'] = count(programs()->listWithCategory());
    $stats['categories'] = count(categories()->listAll());
    $stats['organizers'] = users()->countByPeranan('penganjur');
    $feedbackResult = db()->select('maklum_balas', '?select=id');
    $stats['feedback'] = $feedbackResult['ok'] ? count($feedbackResult['data']) : 0;
}

$adminInitial = strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1));

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
<title>Dashboard Pentadbir | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;
    --muted:#6b7280;--text:#111827;--orange:#f97316;
}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}

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

.page-header{margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{color:var(--muted);font-size:14px;margin-top:4px}

.hero-card{
    background:linear-gradient(135deg,#7bb6ff,#5b8def);
    color:white;
    border-radius:26px;
    padding:28px;
    margin-bottom:22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 18px 38px rgba(91,141,239,.20);
}
.hero-card h2{font-size:28px;margin-bottom:8px}
.hero-card p{color:#eef6ff;font-size:14px}
.hero-icon{width:80px;height:80px;border-radius:24px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:36px}

.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:22px}
.stat-card{
    background:white;
    border:1px solid var(--border);
    border-radius:22px;
    padding:20px;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
}
.stat-icon{width:46px;height:46px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:20px}
.icon-blue{background:#eff6ff;color:#2563eb}
.icon-green{background:#ecfdf5;color:#059669}
.icon-orange{background:#fff7ed;color:#f97316}
.icon-purple{background:#f5f3ff;color:#7c3aed}
.icon-pink{background:#fdf2f8;color:#db2777}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:var(--muted);font-weight:600}

.quick-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.quick-card{
    background:white;
    border:1px solid var(--border);
    border-radius:22px;
    padding:20px;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
    transition:.25s;
}
.quick-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(37,99,235,.12);border-color:var(--primary)}
.quick-card i{font-size:24px;color:var(--dark);margin-bottom:12px}
.quick-card h3{font-size:16px;margin-bottom:6px}
.quick-card p{font-size:13px;color:var(--muted);line-height:1.5}

.section-title{font-size:22px;margin-bottom:14px}

@media(max-width:1100px){
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .quick-grid{grid-template-columns:repeat(2,1fr)}
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
    .hero-card{flex-direction:column;align-items:flex-start;gap:14px}
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
        <div class="user-avatar"><?= htmlspecialchars($adminInitial) ?></div>
        <div>
            <h4><?= htmlspecialchars($_SESSION['nama'] ?? 'Pentadbir') ?></h4>
            <p>Admin Account</p>
        </div>
    </div>
</aside>

<main class="main-section">

    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Simple overview of UKMInvolve system.</p>
    </div>

    <div class="hero-card">
        <div>
            <h2>Welcome, Admin</h2>
            <p>Monitor users, programmes, categories and gamification settings.</p>
        </div>
        <div class="hero-icon">
            <i class="fas fa-shield-halved"></i>
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
            <div class="stat-icon icon-purple"><i class="fas fa-layer-group"></i></div>
            <h2><?= $stats['categories'] ?></h2>
            <p>Programme Categories</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-orange"><i class="fas fa-comments"></i></div>
            <h2><?= number_format($stats['feedback']) ?></h2>
            <p>Total Feedback</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-pink"><i class="fas fa-user-tie"></i></div>
            <h2><?= $stats['organizers'] ?></h2>
            <p>Organizers</p>
        </div>
    </div>

    <h2 class="section-title">Quick Management</h2>

    <div class="quick-grid">
        <a href="pengurusan-pengguna.php" class="quick-card">
            <i class="fas fa-users-gear"></i>
            <h3>Manage Users</h3>
            <p>Add, edit, deactivate students, organizers and admins.</p>
        </a>

        <a href="pengurusan-kategori.php" class="quick-card">
            <i class="fas fa-layer-group"></i>
            <h3>Manage Categories</h3>
            <p>Control programme categories used by organizers.</p>
        </a>

        <a href="urus_mata_admin.php" class="quick-card">
            <i class="fas fa-sliders-h"></i>
            <h3>Manage Points</h3>
            <p>Set points for registration, attendance and feedback.</p>
        </a>

        <a href="statistik-sistem.php" class="quick-card">
            <i class="fas fa-chart-pie"></i>
            <h3>System Statistics</h3>
            <p>View overall system performance and activity summary.</p>
        </a>
    </div>

</main>
</div>

</body>
</html>
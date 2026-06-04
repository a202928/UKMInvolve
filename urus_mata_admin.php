<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'urus_mata_admin';

// Fetch real rules from DB, fallback to defaults if empty
$rules = [];
if (db()->isConfigured()) {
    $result = db()->select('mata_peraturan', '?order=id.asc');
    if ($result['ok'] && !empty($result['data'])) {
        foreach ($result['data'] as $row) {
            $rules[] = [
                'id'    => $row['id'],
                'title' => $row['title'],
                'desc'  => $row['description'] ?? '',
                'icon'  => $row['icon'] ?? 'fa-star',
                'value' => (int)($row['value'] ?? 0),
            ];
        }
    }
}

// Default rules if table is empty
if (empty($rules)) {
    $rules = [
        ['id'=>null,'title'=>'Daftar Program','desc'=>'Mata diberi apabila pelajar mendaftar program.','icon'=>'fa-user-plus','value'=>20],
        ['id'=>null,'title'=>'Hadir Program','desc'=>'Mata diberi selepas kehadiran disahkan.','icon'=>'fa-calendar-check','value'=>100],
        ['id'=>null,'title'=>'Beri Maklum Balas','desc'=>'Mata diberi selepas pelajar menghantar maklum balas.','icon'=>'fa-comment-dots','value'=>30],
        ['id'=>null,'title'=>'Lengkapkan Minat','desc'=>'Mata diberi selepas pelajar memilih minat.','icon'=>'fa-heart','value'=>50],
    ];
}

$levels = [
    ['level'=>1,'name'=>'New Explorer','xp'=>0],
    ['level'=>2,'name'=>'Active Starter','xp'=>200],
    ['level'=>3,'name'=>'Campus Explorer','xp'=>500],
    ['level'=>4,'name'=>'Active Achiever','xp'=>800],
    ['level'=>5,'name'=>'UKM Champion','xp'=>1200],
];

$menu = [
    'dashboard-pentadbir' => ['Dashboard', 'fa-house'],
    'pengurusan-pengguna' => ['Pengguna', 'fa-users-gear'],
    'pengurusan-kategori' => ['Kategori', 'fa-layer-group'],
    'urus_mata_admin'     => ['Urus Mata', 'fa-sliders-h'],
    'statistik-sistem'    => ['Statistik', 'fa-chart-pie'],
    'logout'              => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Urus Mata | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;--muted:#6b7280;--text:#111827;--orange:#f97316}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button,input{font-family:inherit}

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

.page-header{margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{font-size:14px;color:var(--muted);margin-top:4px}

.hero-card{
    background:linear-gradient(135deg,#7bb6ff,#5b8def);
    color:white;
    border-radius:26px;
    padding:26px;
    margin-bottom:22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 18px 38px rgba(91,141,239,.20);
}
.hero-card h2{font-size:28px;margin-bottom:8px}
.hero-card p{font-size:14px;color:#eef6ff}
.hero-icon{width:80px;height:80px;border-radius:24px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:36px}

.settings-grid{display:grid;grid-template-columns:1.2fr .9fr;gap:22px}
.card{background:white;border:1px solid var(--border);border-radius:26px;padding:22px;box-shadow:0 8px 20px rgba(37,99,235,.06);margin-bottom:22px}
.card h2{font-size:22px;margin-bottom:6px}
.card p{font-size:13px;color:var(--muted);margin-bottom:18px}

.rule-list,.level-list{display:flex;flex-direction:column;gap:14px}
.rule-item{display:grid;grid-template-columns:48px 1fr 110px;gap:14px;align-items:center;border:1px solid var(--border);background:#f8fbff;border-radius:18px;padding:14px}
.rule-icon{width:48px;height:48px;border-radius:16px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:20px}
.rule-text h3{font-size:15px;margin-bottom:3px}
.rule-text p{font-size:12px;color:var(--muted);margin:0}
.point-input{display:flex;align-items:center;gap:6px}
.point-input input{width:75px;padding:10px;border:1px solid var(--border);border-radius:14px;text-align:center;font-weight:800}
.point-input span{font-size:12px;font-weight:800;color:#f97316}

.level-item{display:grid;grid-template-columns:1fr 100px;gap:12px;align-items:center;border:1px solid var(--border);background:#f8fbff;border-radius:18px;padding:14px}
.level-name{display:flex;align-items:center;gap:12px}
.level-badge{width:42px;height:42px;border-radius:14px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.level-name h3{font-size:15px}
.level-name p{font-size:12px;color:var(--muted);margin:0}
.level-item input{width:100%;padding:10px;border:1px solid var(--border);border-radius:14px;text-align:center;font-weight:800}

.summary-box{background:#eff6ff;color:#2563eb;border:1px solid #dbeafe;border-radius:18px;padding:15px;font-size:14px;font-weight:600;line-height:1.5}
.action-row{display:flex;gap:12px;margin-top:18px;justify-content:flex-end}
.btn-reset,.btn-save{border:none;border-radius:999px;padding:12px 18px;font-weight:800;cursor:pointer}
.btn-reset{background:#f1f5f9;color:#111827}
.btn-save{background:var(--primary);color:white}

@media(max-width:1000px){.settings-grid{grid-template-columns:1fr}}
@media(max-width:900px){
body{overflow:auto}.dashboard-wrapper{grid-template-columns:1fr;height:auto}.sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
.sidebar-nav{flex-direction:row;overflow-x:auto}.sidebar-link{white-space:nowrap}.user-profile{display:none}.main-section{height:auto;overflow:visible}
}
@media(max-width:600px){.hero-card{flex-direction:column;align-items:flex-start;gap:14px}.rule-item,.level-item{grid-template-columns:1fr}.action-row{flex-direction:column}}
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
        <h1>Manage Points</h1>
        <p>Set gamification points and student level requirements.</p>
    </div>

    <div class="hero-card">
        <div>
            <h2>Gamification Settings</h2>
            <p>Control how students earn XP and level up in UKMInvolve.</p>
        </div>
        <div class="hero-icon">
            <i class="fas fa-sliders-h"></i>
        </div>
    </div>

    <div class="settings-grid">

        <div class="card">
            <h2>Activity Points</h2>
            <p>Set XP rewards for student activities.</p>

            <div class="rule-list">
                <?php foreach ($rules as $index => $rule): ?>
                    <div class="rule-item">
                        <div class="rule-icon">
                            <i class="fas <?= $rule['icon'] ?>"></i>
                        </div>

                        <div class="rule-text">
                            <h3><?= $rule['title'] ?></h3>
                            <p><?= $rule['desc'] ?></p>
                        </div>

                        <div class="point-input">
                            <input type="number" value="<?= $rule['value'] ?>" class="pointRule">
                            <span>XP</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Student Levels</h2>
            <p>Set minimum XP needed for each level.</p>

            <div class="level-list">
                <?php foreach ($levels as $level): ?>
                    <div class="level-item">
                        <div class="level-name">
                            <div class="level-badge"><?= $level['level'] ?></div>
                            <div>
                                <h3>Level <?= $level['level'] ?></h3>
                                <p><?= $level['name'] ?></p>
                            </div>
                        </div>

                        <input type="number" value="<?= $level['xp'] ?>" class="levelInput">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <div class="card">
        <h2>Settings Summary</h2>
        <p>Review current gamification settings before saving.</p>

        <div class="summary-box" id="summaryBox">
            Registration gives 20 XP, attendance gives 100 XP, feedback gives 30 XP. Highest level is Level 5 at 1200 XP.
        </div>

        <div class="action-row">
            <button class="btn-reset" onclick="resetSettings()">
                <i class="fas fa-rotate-right"></i> Reset
            </button>

            <button class="btn-save" onclick="saveSettings()">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </div>

</main>
</div>

<script>
function saveSettings() {
    const points = document.querySelectorAll('.pointRule');
    const levels = document.querySelectorAll('.levelInput');

    document.getElementById('summaryBox').innerHTML =
        'Registration gives ' + points[0].value +
        ' XP, attendance gives ' + points[1].value +
        ' XP, feedback gives ' + points[2].value +
        ' XP. Highest level is Level 5 at ' + levels[4].value + ' XP.';

    alert('Point and level settings saved successfully.');
}

function resetSettings() {
    const defaultPoints = [20, 100, 30, 50];
    const defaultLevels = [0, 200, 500, 800, 1200];

    document.querySelectorAll('.pointRule').forEach((input, index) => {
        input.value = defaultPoints[index];
    });

    document.querySelectorAll('.levelInput').forEach((input, index) => {
        input.value = defaultLevels[index];
    });

    document.getElementById('summaryBox').innerHTML =
        'Registration gives 20 XP, attendance gives 100 XP, feedback gives 30 XP. Highest level is Level 5 at 1200 XP.';

    alert('Settings reset to default values.');
}
</script>

</body>
</html>
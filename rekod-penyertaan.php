<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'rekod-penyertaan';

$records = [];
if (db()->isConfigured() && !empty($_SESSION['user_id'])) {
    foreach (registrations()->listByStudent($_SESSION['user_id']) as $row) {
        $records[] = registrations()->toHistoryRow($row);
    }
}

$totalProgram = count($records);
$studentInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
$totalHadir = count(array_filter($records, fn($r) => $r['status'] === 'Hadir'));
$totalFeedback = count(array_filter($records, fn($r) => $r['feedback']));
$totalPoints = array_sum(array_column($records, 'points'));
$attendanceRate = $totalProgram > 0 ? round(($totalHadir / $totalProgram) * 100) : 0;

$menu = [
    'dashboard_pelajar' => ['Home', 'fa-house'],
    'search' => ['Search', 'fa-magnifying-glass'],
    'recommended' => ['For You', 'fa-lightbulb'],
    'rekod-penyertaan' => ['History', 'fa-clock-rotate-left'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>History | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;
    --muted:#6b7280;--text:#111827;--green:#10b981;--red:#ef4444;
    --orange:#f97316;--purple:#8b5cf6;
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

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{color:var(--muted);font-size:14px;margin-top:4px}

.search-row{display:flex;gap:12px;margin-bottom:22px}
.search-box{flex:1;background:white;border:1px solid var(--border);border-radius:999px;padding:14px 18px;display:flex;align-items:center;gap:10px}
.search-box input{border:none;outline:none;background:transparent;width:100%;font-size:14px}
.search-box i{color:#9ca3af}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{border-radius:22px;padding:18px;color:white;box-shadow:0 10px 25px rgba(37,99,235,.10)}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:rgba(255,255,255,.9)}
.stat-blue{background:linear-gradient(135deg,#60a5fa,#2563eb)}
.stat-green{background:linear-gradient(135deg,#34d399,#059669)}
.stat-orange{background:linear-gradient(135deg,#fbbf24,#f97316)}
.stat-purple{background:linear-gradient(135deg,#a78bfa,#7c3aed)}

.filter-section{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.filter-btn{border:1px solid var(--border);background:white;color:#374151;padding:9px 15px;border-radius:999px;font-weight:700;cursor:pointer}
.filter-btn:hover,.filter-btn.active{background:var(--primary);color:white;border-color:var(--primary)}

.history-card{background:white;border:1px solid var(--border);border-radius:26px;padding:22px;box-shadow:0 8px 20px rgba(37,99,235,.06)}
.history-card h2{font-size:22px;margin-bottom:16px}

.record-list{display:flex;flex-direction:column;gap:14px}
.record-card{border:1px solid var(--border);border-radius:20px;padding:18px;background:#fff;transition:.25s}
.record-card:hover{transform:translateY(-3px);box-shadow:0 12px 25px rgba(37,99,235,.10)}
.record-header{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:12px}
.program-title{font-size:18px;font-weight:800;margin-bottom:8px}
.category-tag{display:inline-flex;background:#eff6ff;color:#2563eb;padding:6px 11px;border-radius:999px;font-size:12px;font-weight:800}
.status-badge{padding:7px 13px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
.status-present{background:#ecfdf5;color:#059669}
.status-absent{background:#fef2f2;color:#dc2626}

.record-meta{display:flex;gap:18px;flex-wrap:wrap;color:var(--muted);font-size:13px;margin:12px 0}
.record-meta i{color:var(--dark);margin-right:6px}

.record-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:12px}
.points-pill{background:#eff6ff;color:#2563eb;padding:8px 12px;border-radius:999px;font-size:12px;font-weight:800}
.feedback-btn{border:1px solid var(--primary);background:white;color:var(--primary);padding:9px 14px;border-radius:999px;font-size:13px;font-weight:800}
.feedback-done{background:#ecfdf5;color:#059669;padding:9px 14px;border-radius:999px;font-size:13px;font-weight:800}
.feedback-na{color:var(--muted);font-size:13px}

.empty-state{text-align:center;padding:42px;background:#fff;border:1px solid var(--border);border-radius:22px;color:var(--muted)}
.empty-state i{font-size:42px;color:#bfdbfe;margin-bottom:12px}
.empty-state h3{color:var(--text);margin-bottom:6px}

@media(max-width:900px){
    body{overflow:auto}
    .dashboard-wrapper{grid-template-columns:1fr;height:auto}
    .sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
    .sidebar-nav{flex-direction:row;overflow-x:auto}
    .sidebar-link{white-space:nowrap}
    .user-profile{display:none}
    .main-section{height:auto;overflow:visible}
    .stats-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
    .stats-grid{grid-template-columns:1fr}
    .page-header{flex-direction:column;align-items:flex-start;gap:10px}
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
        <div class="user-avatar"><?= htmlspecialchars($studentInitial) ?></div>
        <div>
            <h4>Pelajar</h4>
            <p>UKM Account</p>
        </div>
    </div>
</aside>

<main class="main-section">

    <div class="page-header">
        <div>
            <h1>History</h1>
            <p>View your participation record, attendance status and feedback history.</p>
        </div>
    </div>

    <div class="search-row">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search participation record...">
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-blue">
            <h2><?= $totalProgram ?></h2>
            <p>Total Events Joined</p>
        </div>

        <div class="stat-card stat-green">
            <h2><?= $totalHadir ?></h2>
            <p>Attendance Recorded</p>
        </div>

        <div class="stat-card stat-orange">
            <h2><?= $attendanceRate ?>%</h2>
            <p>Attendance Rate</p>
        </div>

        <div class="stat-card stat-purple">
            <h2><?= $totalPoints ?></h2>
            <p>Total Points Earned</p>
        </div>
    </div>

    <div class="filter-section">
        <button class="filter-btn active" onclick="filterRecords('all')">All</button>
        <button class="filter-btn" onclick="filterRecords('Hadir')">Present</button>
        <button class="filter-btn" onclick="filterRecords('Tidak Hadir')">Absent</button>
        <button class="filter-btn" onclick="filterRecords('Akademik')">Academic</button>
        <button class="filter-btn" onclick="filterRecords('Komuniti')">Community</button>
        <button class="filter-btn" onclick="filterRecords('Kerjaya')">Career</button>
    </div>

    <div class="history-card">
        <h2>Participation Records</h2>

        <div class="record-list" id="recordsContainer">
            <?php foreach ($records as $record): ?>
                <?php $statusClass = $record['status'] === 'Hadir' ? 'status-present' : 'status-absent'; ?>

                <div class="record-card"
                     data-status="<?= $record['status'] ?>"
                     data-category="<?= $record['kategori'] ?>"
                     data-title="<?= strtolower($record['program']) ?>">

                    <div class="record-header">
                        <div>
                            <h3 class="program-title"><?= $record['program'] ?></h3>
                            <span class="category-tag"><?= $record['kategori'] ?></span>
                        </div>

                        <span class="status-badge <?= $statusClass ?>">
                            <?= $record['status'] === 'Hadir' ? 'Present' : 'Absent' ?>
                        </span>
                    </div>

                    <div class="record-meta">
                        <div><i class="fas fa-calendar"></i><?= $record['tarikh'] ?></div>
                        <div><i class="fas fa-location-dot"></i><?= $record['lokasi'] ?></div>
                    </div>

                    <div class="record-footer">
                        <span class="points-pill">
                            <i class="fas fa-coins"></i> +<?= $record['points'] ?> Points
                        </span>

                        <?php if ($record['status'] === 'Hadir'): ?>
                            <?php if ($record['feedback']): ?>
                                <span class="feedback-done">
                                    <i class="fas fa-check-circle"></i> Feedback Given
                                </span>
                            <?php else: ?>
                                <a href="maklum-balas.php?program_id=<?= $record['id'] ?>" class="feedback-btn">
                                    <i class="fas fa-comment-dots"></i> Give Feedback
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="feedback-na">
                                <i class="fas fa-info-circle"></i> Feedback unavailable
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

</main>
</div>

<script>
function filterRecords(filter) {
    const records = document.querySelectorAll('.record-card');
    const buttons = document.querySelectorAll('.filter-btn');

    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    records.forEach(record => {
        const status = record.dataset.status;
        const category = record.dataset.category;

        if (filter === 'all' || filter === status || filter === category) {
            record.style.display = 'block';
        } else {
            record.style.display = 'none';
        }
    });
}

document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    const records = document.querySelectorAll('.record-card');

    records.forEach(record => {
        const title = record.dataset.title;
        record.style.display = title.includes(keyword) ? 'block' : 'none';
    });
});
</script>

</body>
</html>
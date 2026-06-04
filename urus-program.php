<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'urus-program';

$programs = [];
if (db()->isConfigured()) {
    foreach (programs()->listWithCategory() as $row) {
        $programs[] = programs()->toOrganizerCard($row);
    }
}

$filter = $_GET['filter'] ?? 'all';

$filteredPrograms = array_filter($programs, function($program) use ($filter) {
    if ($filter === 'all') return true;
    if ($filter === 'active') return $program['status'] === 'Aktif';
    if ($filter === 'upcoming') return $program['status'] === 'Akan Datang';
    if ($filter === 'completed') return $program['status'] === 'Selesai';
    return true;
});

$menu = [
    'dashboard_penganjur' => ['Dashboard', 'fa-house'],
    'hebahan-program' => ['Hebahan', 'fa-bullhorn'],
    'urus-program' => ['Urus Program', 'fa-calendar-check'],
    'peserta-kehadiran' => ['Peserta', 'fa-users'],
    'laporan-statistik' => ['Laporan', 'fa-chart-column'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Urus Program | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;
    --muted:#6b7280;--text:#111827;--green:#10b981;--orange:#f97316;
    --red:#ef4444;--purple:#8b5cf6;
}
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

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{font-size:14px;color:var(--muted);margin-top:4px}

.btn-create{
    background:var(--primary);
    color:white;
    padding:12px 18px;
    border-radius:999px;
    font-weight:800;
    display:flex;
    align-items:center;
    gap:8px;
}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{border-radius:22px;padding:18px;color:white;box-shadow:0 10px 25px rgba(37,99,235,.10)}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:rgba(255,255,255,.9)}
.stat-blue{background:linear-gradient(135deg,#60a5fa,#2563eb)}
.stat-green{background:linear-gradient(135deg,#34d399,#059669)}
.stat-orange{background:linear-gradient(135deg,#fbbf24,#f97316)}
.stat-purple{background:linear-gradient(135deg,#a78bfa,#7c3aed)}

.filter-section{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:22px}
.filter-btn{
    background:white;
    border:1px solid var(--border);
    color:#374151;
    padding:9px 15px;
    border-radius:999px;
    font-weight:800;
    cursor:pointer;
}
.filter-btn.active,.filter-btn:hover{background:var(--primary);color:white;border-color:var(--primary)}

.programs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:18px}

.program-card{
    background:white;
    border:1px solid var(--border);
    border-radius:26px;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
    transition:.25s;
}
.program-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(37,99,235,.12)}

.poster-box{height:170px;background:#dbeafe;position:relative;overflow:hidden}
.poster-box img{width:100%;height:100%;object-fit:cover}
.poster-placeholder{height:100%;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:40px}

.status-badge{
    position:absolute;
    top:12px;
    right:12px;
    padding:7px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}
.status-active{background:#ecfdf5;color:#059669}
.status-completed{background:#f3f4f6;color:#6b7280}
.status-upcoming{background:#eff6ff;color:#2563eb}

.program-content{padding:18px}
.program-title{font-size:18px;font-weight:800;margin-bottom:10px;line-height:1.35}
.category-tag{
    display:inline-flex;
    background:#eff6ff;
    color:#2563eb;
    padding:6px 11px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    margin-bottom:12px;
}

.program-info{display:flex;flex-direction:column;gap:8px;margin:12px 0;color:var(--muted);font-size:13px}
.program-info i{color:var(--dark);width:18px}

.participant-section{margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
.participant-top{display:flex;justify-content:space-between;font-size:13px;font-weight:800;margin-bottom:8px}
.progress-bar{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden}
.progress-fill{height:100%;border-radius:999px}
.progress-high{background:#10b981}
.progress-medium{background:#f59e0b}
.progress-low{background:#60a5fa}

.program-footer{display:flex;gap:10px;margin-top:16px}
.btn-edit,.btn-cancel{
    flex:1;
    border-radius:999px;
    padding:10px 12px;
    font-size:13px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:7px;
}
.btn-edit{border:1px solid var(--primary);background:white;color:var(--primary)}
.btn-cancel{border:none;background:#fef2f2;color:#dc2626}
.btn-edit:hover{background:#eff6ff}
.btn-cancel:hover{background:#fee2e2}

.empty-state{
    grid-column:1/-1;
    background:white;
    border:1px solid var(--border);
    border-radius:26px;
    padding:50px;
    text-align:center;
    color:var(--muted);
}
.empty-state i{font-size:46px;color:#bfdbfe;margin-bottom:14px}
.empty-state h3{color:var(--text);margin-bottom:8px}

.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.45);
    z-index:1000;
    align-items:center;
    justify-content:center;
}
.modal-content{
    background:white;
    border-radius:26px;
    padding:26px;
    max-width:520px;
    width:90%;
    box-shadow:0 20px 50px rgba(15,23,42,.20);
}
.modal-content h3{font-size:22px;margin-bottom:8px}
.modal-content p{color:var(--muted);font-size:14px;margin-bottom:18px}
.cancel-textarea{
    width:100%;
    min-height:110px;
    border:1px solid var(--border);
    border-radius:18px;
    padding:14px;
    resize:vertical;
    outline:none;
}
.modal-actions{display:flex;gap:12px;margin-top:18px}
.btn-close,.btn-confirm{
    flex:1;
    border-radius:999px;
    padding:12px;
    font-weight:800;
    cursor:pointer;
}
.btn-close{background:white;border:1px solid var(--border)}
.btn-confirm{background:#ef4444;color:white;border:none}

@media(max-width:1100px){
    .stats-grid{grid-template-columns:repeat(2,1fr)}
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
    .stats-grid,.programs-grid{grid-template-columns:1fr}
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
        <div class="user-avatar">P</div>
        <div>
            <h4>Penganjur</h4>
            <p>UKM Account</p>
        </div>
    </div>
</aside>

<main class="main-section">

    <div class="page-header">
        <div>
            <h1>Manage Programmes</h1>
            <p>Update, monitor or cancel programmes that have been published.</p>
        </div>

        <a href="hebahan-program.php" class="btn-create">
            <i class="fas fa-plus"></i> New Programme
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-blue">
            <h2><?= count($programs) ?></h2>
            <p>Total Programmes</p>
        </div>

        <div class="stat-card stat-green">
            <h2><?= count(array_filter($programs, fn($p) => $p['status'] === 'Aktif')) ?></h2>
            <p>Active Programmes</p>
        </div>

        <div class="stat-card stat-orange">
            <h2><?= count(array_filter($programs, fn($p) => $p['status'] === 'Akan Datang')) ?></h2>
            <p>Upcoming</p>
        </div>

        <div class="stat-card stat-purple">
            <h2><?= count(array_filter($programs, fn($p) => $p['status'] === 'Selesai')) ?></h2>
            <p>Completed</p>
        </div>
    </div>

    <div class="filter-section">
        <button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" onclick="window.location.href='?filter=all'">All</button>
        <button class="filter-btn <?= $filter === 'active' ? 'active' : '' ?>" onclick="window.location.href='?filter=active'">Active</button>
        <button class="filter-btn <?= $filter === 'upcoming' ? 'active' : '' ?>" onclick="window.location.href='?filter=upcoming'">Upcoming</button>
        <button class="filter-btn <?= $filter === 'completed' ? 'active' : '' ?>" onclick="window.location.href='?filter=completed'">Completed</button>
    </div>

    <div class="programs-grid">
        <?php if (count($filteredPrograms) > 0): ?>
            <?php foreach ($filteredPrograms as $program): ?>
                <?php
                [$current, $total] = explode('/', $program['peserta']);
                $percentage = ($current / $total) * 100;
                $progressClass = $percentage >= 80 ? 'progress-high' : ($percentage >= 50 ? 'progress-medium' : 'progress-low');

                $statusClass = $program['status'] === 'Aktif'
                    ? 'status-active'
                    : ($program['status'] === 'Selesai' ? 'status-completed' : 'status-upcoming');
                ?>

                <div class="program-card">
                    <div class="poster-box">
                        <?php if (!empty($program['poster']) && file_exists($program['poster'])): ?>
                            <img src="<?= htmlspecialchars($program['poster']) ?>" alt="<?= htmlspecialchars($program['nama']) ?>">
                        <?php else: ?>
                            <div class="poster-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>

                        <span class="status-badge <?= $statusClass ?>">
                            <?= $program['status'] ?>
                        </span>
                    </div>

                    <div class="program-content">
                        <span class="category-tag"><?= $program['kategori'] ?></span>
                        <h2 class="program-title"><?= htmlspecialchars($program['nama']) ?></h2>

                        <div class="program-info">
                            <div><i class="fas fa-calendar"></i><?= $program['tarikh'] ?> • <?= $program['masa'] ?></div>
                            <div><i class="fas fa-location-dot"></i><?= htmlspecialchars($program['lokasi']) ?></div>
                            <div><i class="fas fa-users"></i><?= $program['peserta'] ?> participants</div>
                        </div>

                        <div class="participant-section">
                            <div class="participant-top">
                                <span>Participation</span>
                                <span><?= round($percentage) ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $progressClass ?>" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>

                        <div class="program-footer">
                            <a href="edit-program.php?id=<?= $program['id'] ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>

                            <?php if ($program['status'] === 'Aktif'): ?>
                                <button class="btn-cancel" onclick="openCancelModal(<?= $program['id'] ?>, '<?= htmlspecialchars($program['nama']) ?>')">
                                    <i class="fas fa-ban"></i> Cancel
                                </button>
                            <?php else: ?>
                                <button class="btn-cancel" disabled style="opacity:.45;cursor:not-allowed;">
                                    <i class="fas fa-ban"></i> Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-xmark"></i>
                <h3>No Programme Found</h3>
                <p>No programme matches this filter.</p>
            </div>
        <?php endif; ?>
    </div>

</main>
</div>

<div id="cancelModal" class="modal">
    <div class="modal-content">
        <h3>Cancel Programme</h3>
        <p id="modalProgramName"></p>

        <textarea id="cancelReason" class="cancel-textarea" placeholder="Write cancellation reason..."></textarea>

        <div class="modal-actions">
            <button class="btn-close" onclick="closeCancelModal()">Close</button>
            <button class="btn-confirm" onclick="confirmCancel()">Confirm Cancel</button>
        </div>
    </div>
</div>

<script>
let currentProgramName = '';

function openCancelModal(id, name) {
    currentProgramName = name;
    document.getElementById('modalProgramName').textContent =
        'Are you sure you want to cancel "' + name + '"?';
    document.getElementById('cancelReason').value = '';
    document.getElementById('cancelModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

function confirmCancel() {
    const reason = document.getElementById('cancelReason').value.trim();

    if (!reason) {
        alert('Please write cancellation reason.');
        return;
    }

    alert('Programme "' + currentProgramName + '" has been cancelled. Reason: ' + reason);
    closeCancelModal();
}

window.onclick = function(event) {
    const modal = document.getElementById('cancelModal');
    if (event.target === modal) {
        closeCancelModal();
    }
}
</script>

</body>
</html>
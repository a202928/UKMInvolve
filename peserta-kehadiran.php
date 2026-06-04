<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'peserta-kehadiran';

$organizerId = $_SESSION['user_id'] ?? null;

// Fetch this organizer's programs from DB
$rawPrograms = db()->isConfigured() ? programs()->listWithCategory() : [];
$rawPrograms = array_filter($rawPrograms, fn($p) => ($p['penganjur_id'] ?? null) === $organizerId);
$rawPrograms = array_values($rawPrograms);

$programList = [];
foreach ($rawPrograms as $p) {
    $programList[] = ['id' => (string)$p['id'], 'name' => $p['nama']];
}

$searchQuery     = $_GET['search'] ?? '';
$selectedProgram = $_GET['program'] ?? ($programList[0]['id'] ?? '');

// Fetch real participants for selected program
$participants = [];
if (db()->isConfigured() && $selectedProgram) {
    $result = db()->select(
        'pendaftaran',
        '?select=id,nama,no_matrik,fakulti,emel,status&program_id=eq.' . (int)$selectedProgram
    );
    if ($result['ok']) {
        foreach ($result['data'] as $row) {
            $participants[] = [
                'id'        => $row['id'],
                'nama'      => $row['nama'],
                'matrik'    => $row['no_matrik'],
                'fakulti'   => $row['fakulti'],
                'emel'      => $row['emel'],
                'kehadiran' => in_array($row['status'] ?? '', ['hadir', 'approved']),
            ];
        }
    }
}

// Apply search filter
$filteredParticipants = array_filter($participants, function($p) use ($searchQuery) {
    if (!$searchQuery) return true;
    return stripos($p['nama'], $searchQuery) !== false || stripos($p['matrik'], $searchQuery) !== false;
});

$attendanceCount  = count(array_filter($participants, fn($p) => $p['kehadiran']));
$totalParticipants = count($participants);
$absentCount      = $totalParticipants - $attendanceCount;
$attendanceRate   = $totalParticipants > 0 ? round(($attendanceCount / $totalParticipants) * 100) : 0;

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
<title>Peserta & Kehadiran | UKMInvolve</title>
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
button,input,select{font-family:inherit}

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
    overflow:hidden;
    position:relative;
    box-shadow:0 18px 38px rgba(91,141,239,.20);
}
.hero-card::before{
    content:"";
    position:absolute;
    right:-60px;
    top:-70px;
    width:230px;
    height:230px;
    border-radius:50%;
    background:rgba(255,255,255,.13);
}
.hero-card h2{font-size:28px;margin-bottom:8px;position:relative;z-index:1}
.hero-card p{font-size:14px;color:#eef6ff;position:relative;z-index:1}
.hero-icon{width:82px;height:82px;border-radius:24px;background:rgba(255,255,255,.20);display:flex;align-items:center;justify-content:center;font-size:36px;position:relative;z-index:1}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{border-radius:22px;padding:18px;color:white;box-shadow:0 10px 25px rgba(37,99,235,.10);display:flex;justify-content:space-between;align-items:center}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:rgba(255,255,255,.9)}
.stat-icon{width:46px;height:46px;border-radius:16px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-blue{background:linear-gradient(135deg,#60a5fa,#2563eb)}
.stat-green{background:linear-gradient(135deg,#34d399,#059669)}
.stat-orange{background:linear-gradient(135deg,#fbbf24,#f97316)}
.stat-purple{background:linear-gradient(135deg,#a78bfa,#7c3aed)}

/* CARD */
.card{
    background:white;
    border:1px solid var(--border);
    border-radius:26px;
    padding:22px;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
    margin-bottom:22px;
}

.selector-row{
    display:grid;
    grid-template-columns:1fr 1fr auto;
    gap:14px;
    align-items:end;
}
.form-group label{font-size:13px;font-weight:800;margin-bottom:8px;display:block}
.form-input,.form-select{
    width:100%;
    padding:13px 15px;
    border:1px solid var(--border);
    border-radius:16px;
    outline:none;
    background:white;
    font-size:14px;
}
.form-input:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(91,141,239,.12)}
.btn-export{
    background:var(--primary);
    color:white;
    border:none;
    padding:13px 18px;
    border-radius:999px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:8px;
}

/* ACTIONS */
.action-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.action-btn{
    border:none;
    border-radius:999px;
    padding:10px 15px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:8px;
}
.action-primary{background:var(--primary);color:white}
.action-green{background:#ecfdf5;color:#059669}
.action-orange{background:#fff7ed;color:#f97316}

/* TABLE */
.table-card{overflow:hidden}
.table-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px}
.table-header h2{font-size:22px}
.table-wrapper{overflow-x:auto}
.participants-table{width:100%;border-collapse:collapse;min-width:850px}
.participants-table th{
    background:#f8fbff;
    color:#374151;
    font-size:13px;
    text-align:left;
    padding:14px;
    border-bottom:1px solid var(--border);
}
.participants-table td{
    padding:15px 14px;
    border-bottom:1px solid var(--border);
    font-size:14px;
}
.participants-table tr:hover td{background:#f8fbff}
.student-cell{display:flex;align-items:center;gap:10px}
.student-avatar{width:38px;height:38px;border-radius:50%;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.student-name{font-weight:800}
.student-email{font-size:12px;color:var(--muted);margin-top:2px}

.faculty-pill{
    background:#eff6ff;
    color:#2563eb;
    padding:7px 11px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    display:inline-block;
}

/* SWITCH */
.switch-container{display:inline-flex;align-items:center;gap:10px}
.switch{position:relative;display:inline-block;width:52px;height:28px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:#cbd5e1;transition:.3s;border-radius:999px}
.slider:before{content:"";position:absolute;height:20px;width:20px;left:4px;bottom:4px;background:white;transition:.3s;border-radius:50%}
input:checked + .slider{background:#10b981}
input:checked + .slider:before{transform:translateX(24px)}
.status-text{font-size:13px;font-weight:800;min-width:80px}
.status-present{color:#059669}
.status-absent{color:#dc2626}

.empty-state{text-align:center;padding:50px;color:var(--muted)}
.empty-state i{font-size:46px;color:#bfdbfe;margin-bottom:14px}
.empty-state h3{color:var(--text);margin-bottom:8px}

.toast{
    position:fixed;
    top:22px;
    right:22px;
    background:#10b981;
    color:white;
    padding:13px 20px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(16,185,129,.25);
    z-index:9999;
    font-weight:800;
    display:none;
}

@media(max-width:1100px){
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .selector-row{grid-template-columns:1fr}
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
    .stats-grid{grid-template-columns:1fr}
    .page-header,.hero-card{flex-direction:column;align-items:flex-start;gap:14px}
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
            <h1>Participants & Attendance</h1>
            <p>Manage participant list and update attendance records.</p>
        </div>
    </div>

    <div class="hero-card">
        <div>
            <h2>Attendance Management</h2>
            <p>Select a programme, search participants and mark attendance in real time.</p>
        </div>
        <div class="hero-icon">
            <i class="fas fa-user-check"></i>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-blue">
            <div>
                <h2 id="totalStat"><?= $totalParticipants ?></h2>
                <p>Total Participants</p>
            </div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>

        <div class="stat-card stat-green">
            <div>
                <h2 id="presentStat"><?= $attendanceCount ?></h2>
                <p>Present</p>
            </div>
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        </div>

        <div class="stat-card stat-orange">
            <div>
                <h2 id="rateStat"><?= $attendanceRate ?>%</h2>
                <p>Attendance Rate</p>
            </div>
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        </div>

        <div class="stat-card stat-purple">
            <div>
                <h2 id="absentStat"><?= $absentCount ?></h2>
                <p>Absent</p>
            </div>
            <div class="stat-icon"><i class="fas fa-user-xmark"></i></div>
        </div>
    </div>

    <div class="card">
        <form method="GET" class="selector-row">
            <div class="form-group">
                <label>Programme</label>
                <select name="program" class="form-select" onchange="this.form.submit()">
                    <?php if (empty($programList)): ?>
                        <option value="">No programmes yet</option>
                    <?php else: ?>
                        <?php foreach ($programList as $program): ?>
                            <option value="<?= $program['id'] ?>" <?= $selectedProgram == $program['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($program['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Search Participant</label>
                <input type="text" name="search" class="form-input" placeholder="Search name or matric number..." value="<?= htmlspecialchars($searchQuery) ?>">
            </div>

            <button type="button" class="btn-export" onclick="exportAttendance()">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </form>
    </div>

    <div class="card table-card">
        <div class="table-header">
            <div>
                <h2>Participant List</h2>
                <p style="font-size:13px;color:var(--muted);margin-top:4px;">
                    <?= count($filteredParticipants) ?> participant(s) displayed.
                </p>
            </div>
        </div>

        <div class="action-row">
            <button class="action-btn action-primary" onclick="markAllPresent()">
                <i class="fas fa-check-circle"></i> Mark All Present
            </button>
            <button class="action-btn action-orange" onclick="resetAttendance()">
                <i class="fas fa-rotate-right"></i> Reset Attendance
            </button>
            <button class="action-btn action-green" onclick="sendReminders()">
                <i class="fas fa-envelope"></i> Send Reminder
            </button>
        </div>

        <?php if (count($filteredParticipants) > 0): ?>
            <div class="table-wrapper">
                <table class="participants-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Matric No.</th>
                            <th>Faculty</th>
                            <th>Email</th>
                            <th style="text-align:center;">Attendance</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($filteredParticipants as $participant): ?>
                            <tr>
                                <td>
                                    <div class="student-cell">
                                        <div class="student-avatar">
                                            <?= strtoupper(substr($participant['nama'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="student-name"><?= htmlspecialchars($participant['nama']) ?></div>
                                            <div class="student-email"><?= htmlspecialchars($participant['emel']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($participant['matrik']) ?></td>
                                <td><span class="faculty-pill"><?= htmlspecialchars($participant['fakulti']) ?></span></td>
                                <td><?= htmlspecialchars($participant['emel']) ?></td>
                                <td style="text-align:center;">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" <?= $participant['kehadiran'] ? 'checked' : '' ?> onchange="toggleAttendance(this)">
                                            <span class="slider"></span>
                                        </label>

                                        <span class="status-text <?= $participant['kehadiran'] ? 'status-present' : 'status-absent' ?>">
                                            <?= $participant['kehadiran'] ? 'Present' : 'Absent' ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No Participant Found</h3>
                <p>Try another keyword or reset the search.</p>
            </div>
        <?php endif; ?>
    </div>

</main>
</div>

<div class="toast" id="toastMessage"></div>

<script>
function showToast(message) {
    const toast = document.getElementById('toastMessage');
    toast.textContent = message;
    toast.style.display = 'block';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 2500);
}

function updateStats() {
    const total = document.querySelectorAll('.switch input').length;
    const present = document.querySelectorAll('.switch input:checked').length;
    const absent = total - present;
    const rate = total > 0 ? Math.round((present / total) * 100) : 0;

    document.getElementById('totalStat').textContent = total;
    document.getElementById('presentStat').textContent = present;
    document.getElementById('absentStat').textContent = absent;
    document.getElementById('rateStat').textContent = rate + '%';
}

function toggleAttendance(checkbox) {
    const statusText = checkbox.closest('.switch-container').querySelector('.status-text');

    if (checkbox.checked) {
        statusText.textContent = 'Present';
        statusText.className = 'status-text status-present';
    } else {
        statusText.textContent = 'Absent';
        statusText.className = 'status-text status-absent';
    }

    updateStats();
    showToast('Attendance updated successfully.');
}

function markAllPresent() {
    document.querySelectorAll('.switch input').forEach(checkbox => {
        checkbox.checked = true;
        const statusText = checkbox.closest('.switch-container').querySelector('.status-text');
        statusText.textContent = 'Present';
        statusText.className = 'status-text status-present';
    });

    updateStats();
    showToast('All participants marked as present.');
}

function resetAttendance() {
    document.querySelectorAll('.switch input').forEach(checkbox => {
        checkbox.checked = false;
        const statusText = checkbox.closest('.switch-container').querySelector('.status-text');
        statusText.textContent = 'Absent';
        statusText.className = 'status-text status-absent';
    });

    updateStats();
    showToast('Attendance has been reset.');
}

function sendReminders() {
    const absent = document.querySelectorAll('.switch input:not(:checked)').length;

    if (absent === 0) {
        alert('No absent participants to remind.');
        return;
    }

    showToast('Reminder sent to ' + absent + ' absent participant(s).');
}

function exportAttendance() {
    const rows = Array.from(document.querySelectorAll('.participants-table tbody tr')).map(row => {
        const name = row.querySelector('.student-name')?.textContent.trim() || '';
        const matric = row.cells[1].textContent.trim();
        const faculty = row.cells[2].textContent.trim();
        const email = row.cells[3].textContent.trim();
        const attendance = row.querySelector('.status-text')?.textContent.trim() || '';

        return `"${name}","${matric}","${faculty}","${email}","${attendance}"`;
    });

    const csv = "Name,Matric No,Faculty,Email,Attendance\n" + rows.join("\n");
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");

    link.href = URL.createObjectURL(blob);
    link.download = "attendance_record.csv";
    link.click();

    showToast('Attendance CSV exported.');
}
</script>

</body>
</html>
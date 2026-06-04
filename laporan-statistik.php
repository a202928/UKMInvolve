<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'laporan-statistik';

// Fetch real programs from DB for this organizer
$rawPrograms = db()->isConfigured() ? programs()->listWithCategory() : [];

// Filter to only this organizer's programs
$organizerId = $_SESSION['user_id'] ?? null;
$rawPrograms = array_filter($rawPrograms, fn($p) => ($p['penganjur_id'] ?? null) === $organizerId);
$rawPrograms = array_values($rawPrograms);

// Fetch real feedback counts
$programs = [];
foreach ($rawPrograms as $row) {
    $fbResult = db()->select('maklum_balas', '?select=id,rating&program_id=eq.' . (int)$row['id']);
    $fbRows    = ($fbResult['ok'] && !empty($fbResult['data'])) ? $fbResult['data'] : [];
    $fbCount   = count($fbRows);
    $avgRating = $fbCount > 0 ? round(array_sum(array_column($fbRows, 'rating')) / $fbCount, 1) : (float)($row['rating'] ?? 0);

    $programs[] = [
        'id'          => $row['id'],
        'name'        => $row['nama'],
        'participants'=> (int)($row['peserta_semasa'] ?? 0),
        'attendance'  => (int)($row['peserta_semasa'] ?? 0), // attendance = confirmed registrations
        'rating'      => $avgRating,
        'feedback'    => $fbCount,
    ];
}

$totalParticipants = array_sum(array_column($programs, 'participants'));
$totalAttendance   = array_sum(array_column($programs, 'attendance'));
$totalFeedback     = array_sum(array_column($programs, 'feedback'));
$attendanceRate    = $totalParticipants > 0 ? round(($totalAttendance / $totalParticipants) * 100) : 0;
$averageRating     = count($programs) > 0 ? round(array_sum(array_column($programs, 'rating')) / count($programs), 1) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? 'attendance';
    $format = $_POST['format'] ?? 'pdf';
    echo "<script>alert('Report generated successfully in " . strtoupper($format) . " format.');</script>";
}

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
<title>Laporan Statistik | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;
    --muted:#6b7280;--text:#111827;--green:#10b981;--orange:#f97316;
    --purple:#8b5cf6;--red:#ef4444;
}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button,select,input{font-family:inherit}

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

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{border-radius:22px;padding:18px;color:white;box-shadow:0 10px 25px rgba(37,99,235,.10);display:flex;justify-content:space-between;align-items:center}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:rgba(255,255,255,.9)}
.stat-icon{width:46px;height:46px;border-radius:16px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-blue{background:linear-gradient(135deg,#60a5fa,#2563eb)}
.stat-green{background:linear-gradient(135deg,#34d399,#059669)}
.stat-orange{background:linear-gradient(135deg,#fbbf24,#f97316)}
.stat-purple{background:linear-gradient(135deg,#a78bfa,#7c3aed)}

.dashboard-grid{display:grid;grid-template-columns:.9fr 1.2fr;gap:22px}
.card{
    background:white;
    border:1px solid var(--border);
    border-radius:26px;
    padding:22px;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
    margin-bottom:22px;
}
.card-title{font-size:22px;font-weight:800;margin-bottom:6px}
.card-subtitle{font-size:13px;color:var(--muted);margin-bottom:18px}

.report-type-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.report-type-card{
    border:1px solid var(--border);
    border-radius:20px;
    padding:18px;
    cursor:pointer;
    transition:.25s;
    text-align:center;
}
.report-type-card:hover,.report-type-card.selected{
    border-color:var(--primary);
    background:#eff6ff;
    color:var(--dark);
}
.report-type-card i{font-size:30px;margin-bottom:10px;color:var(--primary)}
.report-type-card h3{font-size:16px;margin-bottom:5px}
.report-type-card p{font-size:12px;color:var(--muted)}

.form-group{margin-bottom:16px}
.form-group label{font-size:13px;font-weight:800;margin-bottom:8px;display:block}
.form-select{
    width:100%;
    padding:13px 15px;
    border:1px solid var(--border);
    border-radius:16px;
    outline:none;
    font-size:14px;
}
.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(91,141,239,.12)}

.format-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.format-option{
    border:1px solid var(--border);
    border-radius:16px;
    padding:14px;
    text-align:center;
    cursor:pointer;
    transition:.25s;
}
.format-option i{font-size:24px;margin-bottom:7px;color:var(--primary)}
.format-option.selected,.format-option:hover{background:#eff6ff;border-color:var(--primary);color:var(--dark);font-weight:800}

.action-row{display:flex;gap:12px;margin-top:18px}
.btn-preview,.btn-generate{
    flex:1;
    border-radius:999px;
    padding:13px 16px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}
.btn-preview{background:white;border:1px solid var(--primary);color:var(--primary)}
.btn-generate{background:var(--primary);border:none;color:white}
.btn-generate:hover{background:var(--dark)}

.chart-card{min-height:290px}
.chart-wrapper{height:220px;display:flex;align-items:flex-end;justify-content:space-between;gap:16px;padding-top:35px}
.chart-item{flex:1;text-align:center}
.chart-bar{height:100px;border-radius:14px 14px 6px 6px;background:linear-gradient(180deg,#7bb6ff,#2563eb);position:relative}
.chart-value{position:absolute;top:-26px;left:50%;transform:translateX(-50%);font-size:12px;font-weight:800;color:var(--dark)}
.chart-label{font-size:11px;color:var(--muted);margin-top:8px}

.preview-table-wrapper{overflow-x:auto}
.preview-table{width:100%;border-collapse:collapse;min-width:700px}
.preview-table th{
    background:#f8fbff;
    color:#374151;
    font-size:13px;
    text-align:left;
    padding:14px;
    border-bottom:1px solid var(--border);
}
.preview-table td{
    padding:15px 14px;
    border-bottom:1px solid var(--border);
    font-size:14px;
}
.preview-table tr:hover td{background:#f8fbff}
.badge{
    padding:7px 11px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    display:inline-block;
}
.badge-green{background:#ecfdf5;color:#059669}
.badge-orange{background:#fff7ed;color:#f97316}
.rating{color:#f59e0b;font-weight:800}

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
    .stats-grid,.report-type-grid,.format-grid{grid-template-columns:1fr}
    .hero-card{flex-direction:column;align-items:flex-start;gap:14px}
    .action-row{flex-direction:column}
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
        <h1>Reports & Statistics</h1>
        <p>Generate attendance and feedback reports for organised programmes.</p>
    </div>

    <div class="hero-card">
        <div>
            <h2>Programme Performance Summary</h2>
            <p>Monitor attendance, feedback and overall engagement in one place.</p>
        </div>
        <div class="hero-icon">
            <i class="fas fa-chart-column"></i>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-blue">
            <div>
                <h2><?= $totalParticipants ?></h2>
                <p>Total Participants</p>
            </div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>

        <div class="stat-card stat-green">
            <div>
                <h2><?= $attendanceRate ?>%</h2>
                <p>Attendance Rate</p>
            </div>
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
        </div>

        <div class="stat-card stat-orange">
            <div>
                <h2><?= $totalFeedback ?></h2>
                <p>Total Feedback</p>
            </div>
            <div class="stat-icon"><i class="fas fa-comment-dots"></i></div>
        </div>

        <div class="stat-card stat-purple">
            <div>
                <h2><?= $averageRating ?>/5</h2>
                <p>Average Rating</p>
            </div>
            <div class="stat-icon"><i class="fas fa-star"></i></div>
        </div>
    </div>

    <div class="dashboard-grid">

        <div>
            <form method="POST" class="card">
                <h2 class="card-title">Report Configuration</h2>
                <p class="card-subtitle">Choose report type, programme and output format.</p>

                <div class="report-type-grid">
                    <div class="report-type-card selected" onclick="selectReportType(this, 'attendance')">
                        <i class="fas fa-user-check"></i>
                        <h3>Attendance</h3>
                        <p>Analyse participant attendance.</p>
                    </div>

                    <div class="report-type-card" onclick="selectReportType(this, 'feedback')">
                        <i class="fas fa-comment-alt"></i>
                        <h3>Feedback</h3>
                        <p>Analyse participant feedback.</p>
                    </div>
                </div>

                <input type="hidden" name="report_type" id="reportType" value="attendance">

                <div class="form-group">
                    <label>Programme</label>
                    <select name="program_id" class="form-select" id="programSelect">
                        <option value="all">All Programmes</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?= $program['id'] ?>"><?= htmlspecialchars($program['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Output Format</label>
                    <div class="format-grid">
                        <div class="format-option selected" onclick="selectFormat(this, 'pdf')">
                            <i class="fas fa-file-pdf"></i>
                            <div>PDF</div>
                        </div>

                        <div class="format-option" onclick="selectFormat(this, 'excel')">
                            <i class="fas fa-file-excel"></i>
                            <div>Excel</div>
                        </div>

                        <div class="format-option" onclick="selectFormat(this, 'csv')">
                            <i class="fas fa-file-csv"></i>
                            <div>CSV</div>
                        </div>
                    </div>
                    <input type="hidden" name="format" id="reportFormat" value="pdf">
                </div>

                <div class="action-row">
                    <button type="button" class="btn-preview" onclick="generatePreview()">
                        <i class="fas fa-eye"></i> Preview
                    </button>

                    <button type="submit" name="generate_report" class="btn-generate">
                        <i class="fas fa-download"></i> Generate
                    </button>
                </div>
            </form>
        </div>

        <div>
            <div class="card chart-card">
                <h2 class="card-title">Attendance Overview</h2>
                <p class="card-subtitle">Attendance rate by programme.</p>

                <div class="chart-wrapper">
                    <?php foreach ($programs as $program): ?>
                        <?php $rate = round(($program['attendance'] / $program['participants']) * 100); ?>
                        <div class="chart-item">
                            <div class="chart-bar" style="height: <?= $rate * 1.7 ?>px;">
                                <span class="chart-value"><?= $rate ?>%</span>
                            </div>
                            <div class="chart-label">P<?= $program['id'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="card">
        <h2 class="card-title">Report Preview</h2>
        <p class="card-subtitle">Preview changes based on selected report type.</p>

        <div class="preview-table-wrapper" id="reportPreview">
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Programme</th>
                        <th>Participants</th>
                        <th>Attendance</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programs as $program): ?>
                        <?php $rate = round(($program['attendance'] / $program['participants']) * 100); ?>
                        <tr>
                            <td><?= htmlspecialchars($program['name']) ?></td>
                            <td><?= $program['participants'] ?></td>
                            <td>
                                <span class="badge <?= $rate >= 90 ? 'badge-green' : 'badge-orange' ?>">
                                    <?= $program['attendance'] ?> / <?= $program['participants'] ?> (<?= $rate ?>%)
                                </span>
                            </td>
                            <td><span class="rating"><i class="fas fa-star"></i> <?= $program['rating'] ?>/5</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</div>

<script>
const programs = <?= json_encode($programs) ?>;

function selectReportType(card, type) {
    document.getElementById('reportType').value = type;
    document.querySelectorAll('.report-type-card').forEach(item => item.classList.remove('selected'));
    card.classList.add('selected');
    generatePreview();
}

function selectFormat(card, format) {
    document.getElementById('reportFormat').value = format;
    document.querySelectorAll('.format-option').forEach(item => item.classList.remove('selected'));
    card.classList.add('selected');
}

function generatePreview() {
    const reportType = document.getElementById('reportType').value;
    const preview = document.getElementById('reportPreview');

    let html = '';

    if (reportType === 'attendance') {
        html = `
        <table class="preview-table">
            <thead>
                <tr>
                    <th>Programme</th>
                    <th>Participants</th>
                    <th>Attendance</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
        `;

        programs.forEach(program => {
            const rate = Math.round((program.attendance / program.participants) * 100);
            html += `
                <tr>
                    <td>${program.name}</td>
                    <td>${program.participants}</td>
                    <td>${program.attendance}</td>
                    <td><span class="badge ${rate >= 90 ? 'badge-green' : 'badge-orange'}">${rate}%</span></td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
    }

    if (reportType === 'feedback') {
        html = `
        <table class="preview-table">
            <thead>
                <tr>
                    <th>Programme</th>
                    <th>Total Feedback</th>
                    <th>Average Rating</th>
                </tr>
            </thead>
            <tbody>
        `;

        programs.forEach(program => {
            html += `
                <tr>
                    <td>${program.name}</td>
                    <td>${program.feedback}</td>
                    <td><span class="rating"><i class="fas fa-star"></i> ${program.rating}/5</span></td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
    }

    preview.innerHTML = html;
}
</script>

</body>
</html>
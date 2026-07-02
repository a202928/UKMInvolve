<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'laporan-statistik';

// Fetch real programs from DB for this organizer
$organizerId = $_SESSION['user_id'] ?? null;
$rawPrograms = db()->isConfigured() ? programs()->listByOrganizer($organizerId ?? '') : [];

// Fetch real feedback counts
$programs = [];
foreach ($rawPrograms as $row) {
    $isCompleted = isProgramCompleted($row);
    
    // Count active registrations directly from DB
    $regCount = 0;
    if (db()->isConfigured()) {
        $regRes = db()->select('pendaftaran', '?program_id=eq.' . (int)$row['id'] . '&status=neq.Cancelled');
        if ($regRes['ok'] && is_array($regRes['data'])) {
            $regCount = count($regRes['data']);
        }
    }
    
    // Count present attendance directly from DB
    $attCount = 0;
    if (db()->isConfigured()) {
        $attRes = db()->select('kehadiran', '?program_id=eq.' . (int)$row['id'] . '&status=eq.Hadir');
        if ($attRes['ok'] && is_array($attRes['data'])) {
            $attCount = count($attRes['data']);
        }
    }

    if ($isCompleted && $regCount > 0) {
        $fbResult = db()->select('maklum_balas', '?select=id,rating&program_id=eq.' . (int)$row['id']);
        $fbRows    = ($fbResult['ok'] && !empty($fbResult['data'])) ? $fbResult['data'] : [];
        $fbCount   = count($fbRows);
        $avgRating = $fbCount > 0 ? round(array_sum(array_column($fbRows, 'rating')) / $fbCount, 1) : 0.0;
    } else {
        $fbCount   = 0;
        $avgRating = 0.0;
    }

    $programs[] = [
        'id'          => $row['id'],
        'name'        => $row['nama'],
        'participants'=> $regCount,
        'attendance'  => $attCount,
        'rating'      => $avgRating,
        'feedback'    => $fbCount,
        'is_completed'=> $isCompleted,
    ];
}

$totalParticipants = array_sum(array_column($programs, 'participants'));
$totalAttendance   = array_sum(array_column($programs, 'attendance'));
$totalFeedback     = array_sum(array_column($programs, 'feedback'));
$attendanceRate    = $totalParticipants > 0 ? round(($totalAttendance / $totalParticipants) * 100) : 0;

$completedPrograms = array_filter($programs, function($p) {
    return $p['is_completed'];
});
$averageRating     = count($completedPrograms) > 0 ? round(array_sum(array_column($completedPrograms, 'rating')) / count($completedPrograms), 1) : 0;

$selectedProgramId = 0;
if (isset($_GET['program_id'])) {
    if ($_GET['program_id'] === 'all') {
        $selectedProgramId = 0;
    } else {
        $selectedProgramId = (int)$_GET['program_id'];
    }
} else {
    // Initial page load: default to the first completed program if available
    if (!empty($programs)) {
        foreach ($programs as $p) {
            if ($p['is_completed']) {
                $selectedProgramId = $p['id'];
                break;
            }
        }
        if ($selectedProgramId === 0) {
            $selectedProgramId = $programs[0]['id'];
        }
    }
}

$selectedReportType = isset($_GET['report_type']) ? trim($_GET['report_type']) : 'attendance';

// Overview statistics overrides based on selected program
if ($selectedProgramId > 0) {
    $selectedProg = null;
    foreach ($programs as $p) {
        if ((int)$p['id'] === $selectedProgramId) {
            $selectedProg = $p;
            break;
        }
    }
    if ($selectedProg) {
        $totalParticipantsVal = $selectedProg['participants'];
        $totalAttendanceVal   = $selectedProg['attendance'];
        $totalFeedbackVal     = $selectedProg['feedback'];
        $attendanceRateVal    = $totalParticipantsVal > 0 ? round(($totalAttendanceVal / $totalParticipantsVal) * 100) : 0;
        $averageRatingVal     = $selectedProg['rating'];
    } else {
        $totalParticipantsVal = 0;
        $totalAttendanceVal   = 0;
        $totalFeedbackVal     = 0;
        $attendanceRateVal    = 0;
        $averageRatingVal     = 0.0;
    }
} else {
    $totalParticipantsVal = $totalParticipants;
    $totalAttendanceVal   = $totalAttendance;
    $totalFeedbackVal     = $totalFeedback;
    $attendanceRateVal    = $attendanceRate;
    $averageRatingVal     = $averageRating;
}

// Helper function to parse consolidated comments
if (!function_exists('parseFeedbackKomen')) {
    function parseFeedbackKomen($komen) {
        $data = json_decode($komen, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }
        
        $objektif = 3;
        $pengurusan = 3;
        $kepuasan = 3;
        $diteruskan = 'Ya';
        $cadangan = $komen;
        
        if (preg_match('/Objectives Achieved:\s*(.*)/i', $komen, $matches)) {
            $val = trim($matches[1]);
            if (str_contains($val, 'Strongly Agree')) $objektif = 5;
            elseif (str_contains($val, 'Agree')) $objektif = 4;
            elseif (str_contains($val, 'Disagree')) $objektif = 2;
            elseif (str_contains($val, 'Strongly Disagree')) $objektif = 1;
        }
        if (preg_match('/Programme Management:\s*(\d)/i', $komen, $matches)) {
            $pengurusan = (int)$matches[1];
        }
        if (preg_match('/Comments:\s*(.*)/is', $komen, $matches)) {
            $cadangan = trim($matches[1]);
        }
        
        return [
            'objektif_tercapai' => $objektif,
            'pengurusan_keseluruhan' => $pengurusan,
            'kepuasan_keseluruhan' => $kepuasan,
            'perlu_diteruskan' => $diteruskan,
            'cadangan_penambahbaikan' => $cadangan
        ];
    }
}

$totalRegistered = 0;
$totalAttendance = 0;
$totalFeedback = 0;
$feedbackList = [];

// Aggregations for the selected program feedback
$yearsDist = ['Year 1' => 0, 'Year 2' => 0, 'Year 3' => 0, 'Year 4' => 0];
$coursesDist = [];
$objScores = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$mgmtScores = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$satisfyScores = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$continueDist = ['Ya' => 0, 'Tidak' => 0];
$commentsList = [];

if ($selectedProgramId > 0 && db()->isConfigured()) {
    // Registered (Active only)
    $regCheck = db()->select('pendaftaran', '?program_id=eq.' . $selectedProgramId . '&status=neq.Cancelled');
    if ($regCheck['ok'] && is_array($regCheck['data'])) {
        $totalRegistered = count($regCheck['data']);
    }
    
    // Attendance
    $attCheck = db()->select('kehadiran', '?program_id=eq.' . $selectedProgramId . '&status=eq.Hadir');
    if ($attCheck['ok'] && is_array($attCheck['data'])) {
        $totalAttendance = count($attCheck['data']);
    }
    
    // Feedback list
    $fbCheck = db()->select('maklum_balas', '?select=*,users(nama,tahun_pengajian,kursus_pengajian)&program_id=eq.' . $selectedProgramId);
    if (!$fbCheck['ok']) {
        // Fallback in case users.kursus_pengajian is not yet created in Supabase
        $fbCheck = db()->select('maklum_balas', '?select=*,users(nama,tahun_pengajian)&program_id=eq.' . $selectedProgramId);
    }
    if ($fbCheck['ok'] && is_array($fbCheck['data'])) {
        $feedbackList = $fbCheck['data'];
        $totalFeedback = count($feedbackList);
        
        $ratingSum = 0;
        foreach ($feedbackList as $fb) {
            $ratingSum += (int)$fb['rating'];
            
            // Demographics
            $uData = $fb['users'] ?? [];
            $yearVal = isset($uData['tahun_pengajian']) ? (int)$uData['tahun_pengajian'] : 0;
            if ($yearVal >= 1 && $yearVal <= 4) {
                $yearsDist['Year ' . $yearVal]++;
            }
            
            $courseVal = trim($uData['kursus_pengajian'] ?? '');
            if ($courseVal !== '') {
                $courseKey = strtoupper($courseVal);
                $coursesDist[$courseKey] = ($coursesDist[$courseKey] ?? 0) + 1;
            } else {
                $coursesDist['UNSPECIFIED'] = ($coursesDist['UNSPECIFIED'] ?? 0) + 1;
            }
            
            // Qs answers
            $answers = parseFeedbackKomen($fb['komen'] ?? '');
            
            $obj = isset($answers['objektif_tercapai']) ? (int)$answers['objektif_tercapai'] : 3;
            if ($obj >= 1 && $obj <= 5) $objScores[$obj]++;
            
            $mgmt = isset($answers['pengurusan_keseluruhan']) ? (int)$answers['pengurusan_keseluruhan'] : 3;
            if ($mgmt >= 1 && $mgmt <= 5) $mgmtScores[$mgmt]++;
            
            $sat = isset($answers['kepuasan_keseluruhan']) ? (int)$answers['kepuasan_keseluruhan'] : (int)$fb['rating'];
            if ($sat >= 1 && $sat <= 5) $satisfyScores[$sat]++;
            
            $cont = trim($answers['perlu_diteruskan'] ?? 'Ya');
            if ($cont === 'Ya' || $cont === 'Tidak') {
                $continueDist[$cont]++;
            }
            
            $cadangan = trim($answers['cadangan_penambahbaikan'] ?? '');
            if ($cadangan !== '') {
                $commentsList[] = [
                    'student' => $uData['nama'] ?? 'Student',
                    'comment' => $cadangan
                ];
            }
        }
        $avgRating = $totalFeedback > 0 ? round($ratingSum / $totalFeedback, 1) : 0;
    }
}
$responseRate = $totalAttendance > 0 ? round(($totalFeedback / $totalAttendance) * 100, 1) : 0;
$feedbackCompletion = $totalFeedback > 0 ? 100 : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $reportType = $_POST['report_type'] ?? 'attendance';
    $format = $_POST['format'] ?? 'pdf';
    $programId = $_POST['program_id'] ?? 'all';
    
    if ($reportType === 'feedback' && $format === 'pdf') {
        if ($programId === 'all' || (int)$programId === 0) {
            echo "<script>alert('Sila pilih program spesifik untuk menjana Laporan Maklum Balas.');</script>";
        } else {
            echo "<script>window.open('feedback-report.php?program_id=" . (int)$programId . "', '_blank');</script>";
        }
    } elseif ($reportType === 'attendance' && $format === 'pdf') {
        if ($programId === 'all' || (int)$programId === 0) {
            echo "<script>alert('Sila pilih program spesifik untuk menjana Laporan Kehadiran PDF.');</script>";
        } else {
            echo "<script>window.open('attendance-report.php?program_id=" . (int)$programId . "', '_blank');</script>";
        }
    } else {
        echo "<script>alert('Report generated successfully in " . strtoupper($format) . " format.');</script>";
    }
}

$menu = [
    'dashboard_penganjur' => ['Dashboard', 'fa-house'],
    'hebahan-program' => ['Program Announcements', 'fa-bullhorn'],
    'urus-program' => ['Manage Programs', 'fa-calendar-check'],
    'peserta-kehadiran' => ['Participants', 'fa-users'],
    'laporan-statistik' => ['Reports', 'fa-chart-column'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
$organizerInitial = strtoupper(substr($_SESSION['nama'] ?? 'O', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Statistics | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-type-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .report-type-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            background: var(--white);
        }
        .report-type-card:hover {
            border-color: var(--accent-blue);
            transform: translateY(-2px);
        }
        .report-type-card.selected {
            border-color: var(--accent-blue);
            background: #eff6ff;
            color: var(--accent-blue);
            font-weight: 700;
        }
        .report-type-card i {
            font-size: 32px;
            margin-bottom: 12px;
            color: var(--accent-blue);
        }
        .report-type-card h3 {
            font-size: 15px;
            margin-bottom: 6px;
        }
        .report-type-card p {
            font-size: 12px;
            color: var(--text-secondary);
        }
        .format-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 8px;
        }
        .format-option {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--white);
            user-select: none;
        }
        .format-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }
        .format-option:hover {
            border-color: var(--accent-blue);
        }
        .format-option.selected {
            background: #eff6ff;
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            font-weight: 800;
        }
        .format-option.selected i {
            color: var(--accent-blue);
        }
        .chart-wrapper {
            height: 220px;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            gap: 16px;
            padding-top: 35px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 8px;
            overflow-x: auto;
            overflow-y: hidden;
        }
        .chart-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            max-width: 60px;
            min-width: 48px;
        }
        .chart-bar {
            width: 100%;
            border-radius: 6px 6px 0 0;
            background: linear-gradient(180deg, var(--accent-blue) 0%, var(--primary) 100%);
            position: relative;
            transition: height 0.5s ease;
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
            font-weight: 700;
            color: var(--text-secondary);
            margin-top: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            text-align: center;
        }
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .preview-table th {
            text-align: left;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border);
            color: var(--text-primary);
            font-weight: 800;
        }
        .preview-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }
        .preview-table tr:hover td {
            background: var(--bg-secondary);
        }
        .badge-preview {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-green { background-color: #ecfdf5; color: #059669; }
        .badge-orange { background-color: #fff7ed; color: #d97706; }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Reports & Statistics</h1>
                    <p>Analyze attendance records, rating averages, and export performance reports.</p>
                </div>
            </div>

            <!-- OVERVIEW STATS -->
            <div class="stats-cards-grid">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Registered</h3>
                        <div class="stat-val"><?= $totalParticipantsVal ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Attendance Rate</h3>
                        <div class="stat-val"><?= $attendanceRateVal ?>%</div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Feedback Count</h3>
                        <div class="stat-val"><?= $totalFeedbackVal ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Average Rating</h3>
                        <div class="stat-val"><?= $averageRatingVal ?>/5</div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-purple">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>

            <!-- CONFIGURATION AND CHART GRID -->
            <div class="dashboard-grid-2col">
                <!-- REPORT CONFIGURATION -->
                <form method="POST" class="dashboard-card-wrap" style="margin-bottom: 0;">
                    <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Report Generator</h2>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 20px;">Select report type and choose your output format.</p>

                    <div class="report-type-grid">
                        <div class="report-type-card <?= $selectedReportType === 'attendance' ? 'selected' : '' ?>" id="attendanceCard" onclick="selectReportType(this, 'attendance')">
                            <i class="fas fa-user-check"></i>
                            <h3>Attendance Report</h3>
                            <p>Overview of student check-ins.</p>
                        </div>
                        <div class="report-type-card <?= $selectedReportType === 'feedback' ? 'selected' : '' ?>" id="feedbackCard" onclick="selectReportType(this, 'feedback')">
                            <i class="far fa-comment-dots"></i>
                            <h3>Feedback Report</h3>
                            <p>Ratings and textual analysis.</p>
                        </div>
                    </div>
                    <input type="hidden" name="report_type" id="reportType" value="<?= htmlspecialchars($selectedReportType) ?>">

                    <div class="form-group-profile" style="margin-bottom: 20px;">
                        <label for="programSelect">Select Programme</label>
                        <select name="program_id" id="programSelect" class="form-select-profile">
                            <option value="all" <?= ($selectedProgramId === 0) ? 'selected' : '' ?>>All Programmes</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?= $program['id'] ?>" <?= ((int)$program['id'] === $selectedProgramId) ? 'selected' : '' ?>><?= htmlspecialchars($program['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-profile" style="margin-bottom: 24px;">
                        <label>Output Format</label>
                        <div class="format-grid">
                            <div class="format-option selected" id="formatPdf" onclick="selectFormat(this, 'pdf')">
                                <i class="far fa-file-pdf"></i>
                                <div style="font-size: 13px; font-weight: 700;">PDF</div>
                            </div>
                            <div class="format-option" id="formatExcel" onclick="selectFormat(this, 'excel')">
                                <i class="far fa-file-excel"></i>
                                <div style="font-size: 13px; font-weight: 700;">Excel</div>
                            </div>
                            <div class="format-option" id="formatCsv" onclick="selectFormat(this, 'csv')">
                                <i class="fas fa-file-csv"></i>
                                <div style="font-size: 13px; font-weight: 700;">CSV</div>
                            </div>
                        </div>
                        <input type="hidden" name="format" id="reportFormat" value="pdf">
                    </div>

                    <div style="display: flex; gap: 16px;">
                        <button type="button" class="btn btn-outline" style="flex: 1; border-radius: 999px;" onclick="generatePreview()">
                            <i class="fas fa-eye" style="margin-right: 6px;"></i> Refresh Preview
                        </button>
                        <button type="submit" name="generate_report" class="btn btn-primary" style="flex: 1; border-radius: 999px;">
                            <i class="fas fa-download" style="margin-right: 6px;"></i> Download Report
                        </button>
                    </div>
                </form>

                <!-- CHART COMPONENT -->
                <div class="dashboard-card-wrap" style="margin-bottom: 0;">
                    <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Attendance Rates</h2>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 20px;">Percentage of registered students who attended.</p>

                    <div class="chart-wrapper">
                        <?php foreach ($programs as $program): ?>
                            <?php $rate = $program['participants'] > 0 ? round(($program['attendance'] / $program['participants']) * 100) : 0; ?>
                            <div class="chart-item" title="<?= htmlspecialchars($program['name']) ?>">
                                <div class="chart-bar" style="height: <?= $rate * 1.5 ?>px;">
                                    <span class="chart-value"><?= $rate ?>%</span>
                                </div>
                                <div class="chart-label">ID <?= $program['id'] ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($programs)): ?>
                            <div style="align-self: center; text-align: center; width: 100%; color: var(--text-secondary);">No chart data available.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PREVIEW SHEET -->
            <div class="dashboard-card-wrap" style="margin-top: 32px;">
                <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Live Data Preview</h2>
                <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">Quick snapshot matching the configurations above.</p>

                <div style="overflow-x: auto;" id="reportPreview">
                    <?php if ($selectedReportType === 'attendance'): ?>
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>Programme Name</th>
                                    <th>Registered</th>
                                    <th>Attended (Rate %)</th>
                                    <th>Average Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $previewPrograms = $selectedProgramId > 0 
                                    ? array_filter($programs, function($p) use ($selectedProgramId) { return (int)$p['id'] === $selectedProgramId; })
                                    : $programs;
                                ?>
                                <?php foreach ($previewPrograms as $program): ?>
                                    <?php $rate = $program['participants'] > 0 ? round(($program['attendance'] / $program['participants']) * 100) : 0; ?>
                                    <tr>
                                        <td style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($program['name']) ?></td>
                                        <td><?= $program['participants'] ?></td>
                                        <td>
                                            <span class="badge-preview <?= $rate >= 80 ? 'badge-green' : 'badge-orange' ?>">
                                                <?= $program['attendance'] ?> (<?= $rate ?>%)
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($program['is_completed']): ?>
                                                <span style="font-weight: 700; color: #fbbf24;"><i class="fas fa-star" style="margin-right: 4px;"></i><?= $program['rating'] ?>/5</span>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 12px; font-weight: 600;">In Progress</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($previewPrograms)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 40px 0;">No programmes found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php elseif ($selectedReportType === 'feedback'): ?>
                        <?php if ($selectedProgramId === 0): ?>
                            <!-- General Feedbacks List -->
                            <table class="preview-table">
                                <thead>
                                    <tr>
                                        <th>Programme Name</th>
                                        <th>Total Feedback</th>
                                        <th>Average Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programs as $program): ?>
                                        <tr>
                                            <td style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($program['name']) ?></td>
                                            <td><?= $program['feedback'] ?></td>
                                            <td>
                                                <?php if ($program['is_completed']): ?>
                                                    <span style="font-weight: 700; color: #fbbf24;"><i class="fas fa-star" style="margin-right: 4px;"></i><?= $program['rating'] ?>/5</span>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-size: 12px; font-weight: 600;">In Progress</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($programs)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 40px 0;">No completed programmes found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <!-- Specific Feedback Details: Interactive Charts & Stats -->
                            <!-- Stat Cards Grid -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px;">
                                <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                                    <div style="font-size: 24px; font-weight: 800; color: var(--primary);"><?= $totalFeedback ?></div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-top: 4px;">Total Feedback</div>
                                </div>
                                <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                                    <div style="font-size: 24px; font-weight: 800; color: var(--primary);"><?= $responseRate ?>%</div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-top: 4px;">Response Rate</div>
                                </div>
                                <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                                    <div style="font-size: 24px; font-weight: 800; color: var(--primary);"><?= $avgRating ?>/5</div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-top: 4px;">Average Rating</div>
                                </div>
                                <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                                    <div style="font-size: 24px; font-weight: 800; color: var(--primary);"><?= $feedbackCompletion ?>%</div>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-top: 4px;">Completion Rate</div>
                                </div>
                            </div>

                            <!-- Charts Grid -->
                            <h3 style="font-size: 18px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 16px; border-bottom: 2px solid var(--border); padding-bottom: 8px; color: var(--primary);">Demographic & Ratings Analysis</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 30px;">
                                <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; text-align: center; min-height: 320px;">
                                    <h4 style="font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 15px;">Tahun Pengajian</h4>
                                    <div style="position: relative; height: 240px;"><canvas id="chartTahun"></canvas></div>
                                </div>
                                <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; text-align: center; min-height: 320px;">
                                    <h4 style="font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 15px;">Kursus Pengajian</h4>
                                    <div style="position: relative; height: 240px;"><canvas id="chartKursus"></canvas></div>
                                </div>
                                <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; text-align: center; min-height: 320px;">
                                    <h4 style="font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 15px;">Adakah objektif program ini tercapai? (Section A)</h4>
                                    <div style="position: relative; height: 240px;"><canvas id="chartObjektif"></canvas></div>
                                </div>
                                <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; text-align: center; min-height: 320px;">
                                    <h4 style="font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 15px;">Bagaimana anda menilai pengurusan program? (Section B)</h4>
                                    <div style="position: relative; height: 240px;"><canvas id="chartPengurusan"></canvas></div>
                                </div>
                                <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; text-align: center; min-height: 320px;">
                                    <h4 style="font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 15px;">Adakah anda berpuas hati dengan program? (Section C)</h4>
                                    <div style="position: relative; height: 240px;"><canvas id="chartKepuasan"></canvas></div>
                                </div>
                                <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; text-align: center; min-height: 320px;">
                                    <h4 style="font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 15px;">Adakah program perlu diteruskan? (Section D)</h4>
                                    <div style="position: relative; height: 240px;"><canvas id="chartMasaDepan"></canvas></div>
                                </div>
                            </div>

                            <!-- Comments Section -->
                            <h3 style="font-size: 18px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 16px; border-bottom: 2px solid var(--border); padding-bottom: 8px; color: var(--primary);">Cadangan Penambahbaikan (Section E)</h3>
                            <?php if (empty($commentsList)): ?>
                                <p style="font-size: 14px; color: var(--text-secondary); text-align: center; padding: 40px; border: 1px dashed var(--border); border-radius: var(--radius-md);">No written suggestions or comments submitted yet.</p>
                            <?php else: ?>
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">No.</th>
                                            <th>Student Name</th>
                                            <th>Suggestions / Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($commentsList as $idx => $commentItem): ?>
                                            <tr>
                                                <td><?= $idx + 1 ?></td>
                                                <td style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($commentItem['student']) ?></td>
                                                <td><?= nl2br(htmlspecialchars($commentItem['comment'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    const programsData = <?= json_encode($programs) ?>;

    function selectReportType(card, type) {
        document.getElementById('reportType').value = type;
        document.querySelectorAll('.report-type-card').forEach(item => item.classList.remove('selected'));
        card.classList.add('selected');
        
        // Reload page to preserve selected program & report type
        const programId = document.getElementById('programSelect').value;
        window.location.href = `laporan-statistik.php?program_id=${programId}&report_type=${type}`;
    }

    function selectFormat(card, format) {
        document.getElementById('reportFormat').value = format;
        document.querySelectorAll('.format-option').forEach(item => item.classList.remove('selected'));
        card.classList.add('selected');
    }

    function generatePreview() {
        const programId = document.getElementById('programSelect').value;
        const reportType = document.getElementById('reportType').value;
        window.location.href = `laporan-statistik.php?program_id=${programId}&report_type=${reportType}`;
    }

    // Reload page on programme change to refresh the charts/preview in PHP
    document.getElementById('programSelect').addEventListener('change', function() {
        const programId = this.value;
        const reportType = document.getElementById('reportType').value;
        window.location.href = `laporan-statistik.php?program_id=${programId}&report_type=${reportType}`;
    });

    <?php if ($selectedReportType === 'feedback' && $selectedProgramId > 0): ?>
    // Initialize Chart.js interactive charts for specific feedback report view
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    font: { size: 11, family: 'Inter' }
                }
            }
        }
    };

    // Years distribution pie chart
    new Chart(document.getElementById('chartTahun'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($yearsDist)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($yearsDist)) ?>,
                backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981']
            }]
        },
        options: chartOptions
    });

    // Courses distribution pie chart
    new Chart(document.getElementById('chartKursus'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($coursesDist)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($coursesDist)) ?>,
                backgroundColor: ['#2563eb', '#db2777', '#f59e0b', '#10b981', '#7c3aed', '#06b6d4', '#6b7280']
            }]
        },
        options: chartOptions
    });

    // Bar chart configs helper
    const getBarConfig = (title, labelData, countsData) => ({
        type: 'bar',
        data: {
            labels: labelData,
            datasets: [{
                label: 'Responses',
                data: countsData,
                backgroundColor: '#7c3aed',
                borderRadius: 4
            }]
        },
        options: {
            ...chartOptions,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    const labelsRating = ['1', '2', '3', '4', '5'];

    // Section A
    new Chart(
        document.getElementById('chartObjektif'),
        getBarConfig('Section A', labelsRating, <?= json_encode(array_values($objScores)) ?>)
    );

    // Section B
    new Chart(
        document.getElementById('chartPengurusan'),
        getBarConfig('Section B', labelsRating, <?= json_encode(array_values($mgmtScores)) ?>)
    );

    // Section C
    new Chart(
        document.getElementById('chartKepuasan'),
        getBarConfig('Section C', labelsRating, <?= json_encode(array_values($satisfyScores)) ?>)
    );

    // Section D
    new Chart(document.getElementById('chartMasaDepan'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($continueDist)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($continueDist)) ?>,
                backgroundColor: ['#2563eb', '#dc2626']
            }]
        },
        options: chartOptions
    });
    <?php endif; ?>
    </script>
</body>
</html>

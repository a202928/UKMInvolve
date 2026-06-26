<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');

$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$studentId = $_SESSION['user_id'] ?? '';

$program = null;
$organizer = null;
$totalRegistered = 0;
$totalAttendance = 0;
$totalFeedback = 0;
$feedbackList = [];

// Helper function to parse consolidated comments
function parseFeedbackKomen($komen) {
    $data = json_decode($komen, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        return $data;
    }
    
    // Fallback for old format parsing
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

if ($programId > 0 && db()->isConfigured()) {
    // 1. Fetch programme details
    $pRes = db()->select('program', '?id=eq.' . $programId);
    if ($pRes['ok'] && !empty($pRes['data'])) {
        $program = $pRes['data'][0];
        
        // Fetch organizer name
        if (!empty($program['penganjur_id'])) {
            $orgRes = db()->select('users', '?id=eq.' . rawurlencode($program['penganjur_id']));
            if ($orgRes['ok'] && !empty($orgRes['data'])) {
                $organizer = $orgRes['data'][0];
            }
        }
    }
    
    if ($program) {
        // Verify owner
        if ($_SESSION['role'] !== 'pentadbir' && ($program['penganjur_id'] ?? '') !== $studentId) {
            echo "Access Denied: You are not the organizer of this programme.";
            exit();
        }

        // 2. Fetch Total Registered (Active only)
        $regCheck = db()->select('pendaftaran', '?program_id=eq.' . $programId . '&status=neq.Cancelled');
        if ($regCheck['ok'] && is_array($regCheck['data'])) {
            $totalRegistered = count($regCheck['data']);
        }
        
        // 3. Fetch Total Attendance
        $attCheck = db()->select('kehadiran', '?program_id=eq.' . $programId . '&status=eq.Hadir');
        if ($attCheck['ok'] && is_array($attCheck['data'])) {
            $totalAttendance = count($attCheck['data']);
        }
        
        // 4. Fetch Feedback joined with student user details
        $fbCheck = db()->select('maklum_balas', '?select=*,users(nama,tahun_pengajian,kursus_pengajian)&program_id=eq.' . $programId . '&order=created_at.desc');
        if (!$fbCheck['ok']) {
            // Fallback in case users.kursus_pengajian column is not yet created in Supabase
            $fbCheck = db()->select('maklum_balas', '?select=*,users(nama,tahun_pengajian)&program_id=eq.' . $programId . '&order=created_at.desc');
        }
        if ($fbCheck['ok'] && is_array($fbCheck['data'])) {
            $feedbackList = $fbCheck['data'];
            $totalFeedback = count($feedbackList);
        }
    }
}

if (!$program) {
    echo "Programme not found or database not configured.";
    exit();
}

// Compute metrics
$responseRate = $totalAttendance > 0 ? round(($totalFeedback / $totalAttendance) * 100, 1) : 0;
$avgRating = 0;
$feedbackCompletion = $totalFeedback > 0 ? 100 : 0; // Standard enforced completion

// Aggregation holders
$yearsDist = ['Year 1' => 0, 'Year 2' => 0, 'Year 3' => 0, 'Year 4' => 0];
$coursesDist = [];
$objScores = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$mgmtScores = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$satisfyScores = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$continueDist = ['Ya' => 0, 'Tidak' => 0];
$commentsList = [];

$ratingSum = 0;
foreach ($feedbackList as $fb) {
    $ratingSum += (int)$fb['rating'];
    
    // Parse user demographics
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
    
    // Parse questionnaire JSON
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
        $commentsList[] = $cadangan;
    }
}
$avgRating = $totalFeedback > 0 ? round($ratingSum / $totalFeedback, 1) : 0;

// Sort courses descending
arsort($coursesDist);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback Report - <?= htmlspecialchars($program['nama']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #1e3a8a;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --text-main: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --bg-light: #f9fafb;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            background: #ffffff;
            line-height: 1.5;
            padding: 40px;
        }

        .report-header {
            border-bottom: 3px double var(--border);
            padding-bottom: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .report-header-info h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .report-header-info p {
            font-size: 14px;
            color: var(--text-light);
            font-weight: 500;
        }

        .ukm-badge-wrap img {
            height: 60px;
            object-fit: contain;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
        }

        .info-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 6px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-lbl {
            font-weight: 600;
            color: var(--text-light);
        }

        .info-val {
            font-weight: 700;
            color: var(--text-main);
        }

        .stats-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-box {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }

        .stat-box-num {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .stat-box-lbl {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 8px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            page-break-after: avoid;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-box {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            page-break-inside: avoid;
        }

        .chart-box h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 15px;
            text-align: center;
            width: 100%;
        }

        .chart-canvas-wrap {
            position: relative;
            width: 100%;
            height: 240px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        canvas {
            max-width: 100% !important;
            max-height: 100% !important;
        }

        .comments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .comments-table th {
            background: var(--primary);
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 12px;
            text-align: left;
        }

        .comments-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            color: var(--text-main);
            line-height: 1.6;
        }

        .comments-table tr:nth-child(even) td {
            background: var(--bg-light);
        }

        .print-btn-wrap {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
        }

        .btn-print {
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 999px;
            padding: 12px 24px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s ease;
        }

        .btn-print:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        /* Print styling rules */
        @media print {
            body {
                padding: 0;
                color: #000000;
            }
            .print-btn-wrap {
                display: none !important;
            }
            .chart-box {
                border: none !important;
                background: none !important;
                min-height: auto !important;
            }
            .info-card {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
            }
            .stat-box {
                border: 1px solid #000000 !important;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>

    <!-- Print Floating Action Button -->
    <div class="print-btn-wrap">
        <button class="btn-print" onclick="window.print()">
            <svg style="width:16px; height:16px; fill:currentColor" viewBox="0 0 512 512">
                <path d="M128 0C92.7 0 64 28.7 64 64v96h64V64H384V256H128V192H64v128c0 17.7 14.3 32 32 32h320c17.7 0 32-14.3 32-32V192c0-35.3-28.7-64-64-64H384V64c0-35.3-28.7-64-64-64H128zM128 320v128c0 35.3 28.7 64 64 64h128c35.3 0 64-28.7 64-64V320H128z"/>
            </svg>
            Print Report
        </button>
    </div>

    <!-- REPORT HEADER -->
    <header class="report-header">
        <div class="report-header-info">
            <h1>UKMInvolve Feedback Report</h1>
            <p>Generated automatically on <?= date('j M Y, g:i A') ?></p>
        </div>
        <div class="ukm-badge-wrap">
            <img src="UKM.png" alt="UKM Logo">
        </div>
    </header>

    <!-- PROGRAM INFORMATION -->
    <div class="info-grid">
        <div class="info-card">
            <h3>Programme Information</h3>
            <div class="info-row">
                <span class="info-lbl">Programme Name</span>
                <span class="info-val"><?= htmlspecialchars($program['nama']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Date & Venue</span>
                <span class="info-val"><?= date('j M Y', strtotime($program['tarikh'])) ?> &bull; <?= htmlspecialchars($program['lokasi']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Category</span>
                <span class="info-val"><?= htmlspecialchars($program['kategori']['nama'] ?? 'General') ?></span>
            </div>
        </div>
        <div class="info-card">
            <h3>Organizer Details</h3>
            <div class="info-row">
                <span class="info-lbl">Organizer Name</span>
                <span class="info-val"><?= htmlspecialchars($organizer['nama'] ?? 'UKM Organization') ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Contact Email</span>
                <span class="info-val"><?= htmlspecialchars($organizer['emel'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Contact Phone</span>
                <span class="info-val"><?= htmlspecialchars($organizer['no_telefon'] ?? '-') ?></span>
            </div>
        </div>
    </div>

    <!-- STATS SUMMARY -->
    <div class="stats-summary-grid">
        <div class="stat-box">
            <div class="stat-box-num"><?= $totalRegistered ?></div>
            <div class="stat-box-lbl">Total Registered</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-num"><?= $totalAttendance ?></div>
            <div class="stat-box-lbl">Total Attendance</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-num"><?= $totalFeedback ?></div>
            <div class="stat-box-lbl">Feedback Responses</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-num"><?= $responseRate ?>%</div>
            <div class="stat-box-lbl">Response Rate</div>
        </div>
    </div>

    <!-- DEMOGRAPHIC DISTRIBUTION SECTION -->
    <h2 class="section-title">Demographic Distribution</h2>
    <div class="charts-container">
        <div class="chart-box">
            <h4>Tahun Pengajian</h4>
            <div class="chart-canvas-wrap">
                <canvas id="chartTahun"></canvas>
            </div>
        </div>
        <div class="chart-box">
            <h4>Kursus Pengajian</h4>
            <div class="chart-canvas-wrap">
                <canvas id="chartKursus"></canvas>
            </div>
        </div>
    </div>

    <!-- QUESTIONNAIRE RESULTS SECTION -->
    <div class="page-break"></div>
    <h2 class="section-title">Feedback Ratings Distribution</h2>
    <div class="charts-container">
        <div class="chart-box">
            <h4>Adakah objektif program ini tercapai? (Section A)</h4>
            <div class="chart-canvas-wrap">
                <canvas id="chartObjektif"></canvas>
            </div>
        </div>
        <div class="chart-box">
            <h4>Bagaimana anda menilai pengurusan program secara keseluruhan? (Section B)</h4>
            <div class="chart-canvas-wrap">
                <canvas id="chartPengurusan"></canvas>
            </div>
        </div>
    </div>

    <div class="charts-container" style="margin-top: 30px;">
        <div class="chart-box">
            <h4>Adakah anda berpuas hati dengan pengalaman keseluruhan program ini? (Section C)</h4>
            <div class="chart-canvas-wrap">
                <canvas id="chartKepuasan"></canvas>
            </div>
        </div>
        <div class="chart-box">
            <h4>Adakah program seperti ini perlu diteruskan pada masa akan datang? (Section D)</h4>
            <div class="chart-canvas-wrap">
                <canvas id="chartMasaDepan"></canvas>
            </div>
        </div>
    </div>

    <!-- WRITTEN COMMENTS & SUGGESTIONS SECTION -->
    <div class="page-break"></div>
    <h2 class="section-title">Cadangan Penambahbaikan (Section E)</h2>
    <?php if (empty($commentsList)): ?>
        <p style="font-size: 14px; color: var(--text-light); text-align: center; padding: 40px; border: 1px dashed var(--border); border-radius: 8px;">No written suggestions or comments submitted yet.</p>
    <?php else: ?>
        <table class="comments-table">
            <thead>
                <tr>
                    <th style="width: 80px;">No.</th>
                    <th>Cadangan Penambahbaikan (Submitted Comments)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commentsList as $idx => $comment): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= nl2br(htmlspecialchars($comment)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
    // Disable Chart.js animation for instant print rendering
    const chartOptions = {
        animation: false,
        animations: false,
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

    // Bar chart configs
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

    // Automatically trigger window.print() once page loads
    window.addEventListener('load', () => {
        setTimeout(() => {
            window.print();
        }, 800); // Small timeout to ensure Chart.js paints the elements
    });
    </script>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');

$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$studentId = $_SESSION['user_id'] ?? '';

$program = null;
$organizer = null;
$participants = [];
$totalRegistered = 0;
$totalAttended = 0;

if ($programId > 0) {
    // Check ownership
    $progRes = programs()->findById($programId);
    if ($progRes && $progRes['penganjur_id'] === $studentId) {
        $program = $progRes;
        $organizer = users()->findById($studentId);

        // Get registrations
        $regsRes = db()->select('pendaftaran', '?select=id,nama,no_matrik,fakulti,emel,status,pelajar_id,tarikh_daftar,jenis_pendaftaran&program_id=eq.' . $programId);
        
        // Get attendance
        $attRes = db()->select('kehadiran', '?select=pelajar_id,status&program_id=eq.' . $programId);
        $attendanceMap = [];
        if ($attRes['ok'] && is_array($attRes['data'])) {
            foreach ($attRes['data'] as $att) {
                $attendanceMap[$att['pelajar_id']] = $att;
            }
        }

        if ($regsRes['ok'] && is_array($regsRes['data'])) {
            foreach ($regsRes['data'] as $reg) {
                if (($reg['status'] ?? '') !== 'Cancelled') {
                    $pId = $reg['pelajar_id'];
                    $attStatus = $attendanceMap[$pId]['status'] ?? 'Tidak Hadir';
                    
                    $participants[] = [
                        'nama' => $reg['nama'] ?? 'Unknown',
                        'matrik' => $reg['no_matrik'] ?? '-',
                        'fakulti' => $reg['fakulti'] ?? '-',
                        'kolej' => '-', // pendaftaran doesn't have kolej, so default to -
                        'att_status' => $attStatus
                    ];
                    
                    $totalRegistered++;
                    if ($attStatus === 'Hadir') {
                        $totalAttended++;
                    }
                }
            }
        }
        
        // Sort by name
        usort($participants, function($a, $b) {
            return strcasecmp($a['nama'], $b['nama']);
        });
    } else {
        die("You do not have permission to view this report.");
    }
} else {
    die("Invalid program ID.");
}

$attendanceRate = $totalRegistered > 0 ? round(($totalAttended / $totalRegistered) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - <?= htmlspecialchars($program['nama'] ?? 'Program') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a8a;
            --primary-light: #eff6ff;
            --text-main: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
        }

        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-size: 12px;
            line-height: 1.5;
        }

        .report-container {
            max-width: 100%;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header-logo {
            width: 80px;
            height: auto;
            margin-right: 20px;
        }

        .header-text h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-text h2 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
            margin: 0 0 4px 0;
        }

        .header-text p {
            margin: 0;
            color: var(--text-light);
            font-size: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            background: var(--primary-light);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #bfdbfe;
        }

        .info-col {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-row {
            display: flex;
        }

        .info-lbl {
            font-weight: 700;
            width: 120px;
            color: var(--primary);
        }

        .info-val {
            flex: 1;
            font-weight: 500;
        }

        .stats-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid var(--border);
            padding: 10px 12px;
            text-align: left;
        }

        th {
            background: var(--primary);
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        tr:nth-child(even) td {
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .status-hadir {
            color: #16a34a;
            font-weight: 700;
        }
        
        .status-tidak-hadir {
            color: #dc2626;
            font-weight: 700;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 10px;
            color: var(--text-light);
        }

        /* Print Specific Adjustments */
        @media print {
            body {
                background: none;
            }
            .no-print {
                display: none !important;
            }
            .info-grid {
                background: #eff6ff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .stat-box {
                break-inside: avoid;
            }
            th {
                background: #1e3a8a !important;
                color: #ffffff !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="report-container">
    
    <!-- Controls (Hidden in Print) -->
    <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
        <div>
            <h3 style="margin:0 0 5px 0; color: #0f172a; font-family:'Outfit';">Print Preview</h3>
            <p style="margin:0; font-size: 12px; color: #64748b;">The print dialog should open automatically. Use the buttons below if needed.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.close()" style="padding: 8px 16px; background: white; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600;">Close</button>
            <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Print / Save PDF</button>
        </div>
    </div>

    <!-- Header -->
    <div class="header">
        <img src="UKM.png" alt="UKM Logo" class="header-logo">
        <div class="header-text">
            <h1>ATTENDANCE REPORT</h1>
            <h2><?= htmlspecialchars($program['nama'] ?? 'Program Title') ?></h2>
            <p>Generated on <?= date('d F Y, H:i') ?></p>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-col">
            <div class="info-row">
                <div class="info-lbl">Organizer</div>
                <div class="info-val"><?= htmlspecialchars($organizer['nama'] ?? 'N/A') ?></div>
            </div>
            <div class="info-row">
                <div class="info-lbl">Date</div>
                <div class="info-val"><?= isset($program['tarikh']) ? date('d F Y', strtotime($program['tarikh'])) : 'N/A' ?></div>
            </div>
        </div>
        <div class="info-col">
            <div class="info-row">
                <div class="info-lbl">Venue</div>
                <div class="info-val"><?= htmlspecialchars($program['lokasi'] ?? 'N/A') ?></div>
            </div>
            <div class="info-row">
                <div class="info-lbl">Category</div>
                <div class="info-val"><?= htmlspecialchars(is_array($program['kategori']) ? ($program['kategori']['nama'] ?? 'N/A') : ($program['kategori'] ?? 'N/A')) ?></div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="stats-summary-grid">
        <div class="stat-box">
            <div class="stat-box-num"><?= number_format($totalRegistered) ?></div>
            <div class="stat-box-lbl">Total Registered</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-num"><?= number_format($totalAttended) ?></div>
            <div class="stat-box-lbl">Total Attended</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-num"><?= $attendanceRate ?>%</div>
            <div class="stat-box-lbl">Attendance Rate</div>
        </div>
    </div>

    <!-- Participants List -->
    <div class="section-title">Participants List</div>
    <?php if (empty($participants)): ?>
        <p style="text-align: center; color: var(--text-light); padding: 20px; border: 1px dashed var(--border); border-radius: 8px;">No participants found for this program.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">No.</th>
                    <th>Name</th>
                    <th style="width: 100px;">Matric No.</th>
                    <th>Faculty</th>
                    <th style="width: 100px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $i => $p): ?>
                    <tr>
                        <td style="text-align: center;"><?= $i + 1 ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['matrik']) ?></td>
                        <td><?= htmlspecialchars($p['fakulti']) ?></td>
                        <td class="<?= $p['att_status'] === 'Hadir' ? 'status-hadir' : 'status-tidak-hadir' ?>">
                            <?= $p['att_status'] === 'Hadir' ? 'Attended' : 'Absent' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        <p>This is a computer-generated document from UKMInvolve. No signature is required.</p>
        <p>Universiti Kebangsaan Malaysia (UKM) &copy; <?= date('Y') ?></p>
    </div>

</div>

</body>
</html>

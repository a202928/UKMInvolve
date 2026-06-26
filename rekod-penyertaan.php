<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'rekod-penyertaan';

$studentId = $_SESSION['user_id'] ?? '';
$records = [];
$upcomingRecords = [];
$pastRecords = [];

if (db()->isConfigured() && $studentId) {
    // Fetch registrations
    $registrationsList = registrations()->listByStudent($studentId);
    
    // Fetch attendance records
    $attendanceList = db()->select('kehadiran', '?pelajar_id=eq.' . rawurlencode($studentId));
    $attendanceMap = [];
    if ($attendanceList['ok']) {
        foreach ($attendanceList['data'] as $att) {
            $attendanceMap[$att['program_id']] = $att['status'];
        }
    }

    // Fetch feedback records
    $feedbackList = db()->select('maklum_balas', '?pelajar_id=eq.' . rawurlencode($studentId));
    $feedbackMap = [];
    if ($feedbackList['ok'] && is_array($feedbackList['data'])) {
        foreach ($feedbackList['data'] as $fb) {
            $feedbackMap[$fb['program_id']] = true;
        }
    }

    // Fetch rekod_mata records for the student
    $rekodMataList = db()->select('rekod_mata', '?student_id=eq.' . rawurlencode($studentId));
    $pointsMap = [];
    if ($rekodMataList['ok'] && is_array($rekodMataList['data'])) {
        foreach ($rekodMataList['data'] as $rm) {
            $pId = $rm['program_id'];
            if ($pId !== null) {
                $pointsMap[$pId] = ($pointsMap[$pId] ?? 0) + (int)$rm['points'];
            }
        }
    }
    
    $today = date('Y-m-d');
    
    foreach ($registrationsList as $row) {
        $historyRow = registrations()->toHistoryRow($row);
        $historyRow['program_id'] = $row['program_id'];
        $hasFeedback = isset($feedbackMap[$row['program_id']]);
        $historyRow['feedback'] = $hasFeedback;
        
        // Map attendance status
        $progId = $row['program_id'];
        $regStatus = $row['status'] ?? 'Registered';
        
        if ($regStatus === 'Cancelled' || ($row['program']['status'] ?? '') === 'Cancelled') {
            $historyRow['status_display'] = 'Cancelled';
            $historyRow['points'] = 0;
        } else {
            $attendanceStatus = $attendanceMap[$progId] ?? null;
            if ($attendanceStatus === 'Hadir') {
                $historyRow['status_display'] = 'Attended';
            } elseif ($attendanceStatus === 'Tidak Hadir') {
                $historyRow['status_display'] = 'Absent';
            } else {
                $historyRow['status_display'] = 'Registered';
            }
            $historyRow['points'] = $pointsMap[$progId] ?? 0;
        }
        
        // Role (jenis_pendaftaran)
        $historyRow['role'] = ($row['jenis_pendaftaran'] ?? 'Peserta') === 'Peserta' ? 'Participant' : ($row['jenis_pendaftaran'] ?? 'Participant');
        $historyRow['is_completed'] = isProgramCompleted($row['program'] ?? []);
        
        $records[] = $historyRow;
        
        // Split by completion
        if (!$historyRow['is_completed']) {
            $upcomingRecords[] = $historyRow;
        } else {
            $pastRecords[] = $historyRow;
        }
    }
}

$totalProgram = count($records);
$studentInitial = strtoupper(substr($_SESSION['nama'] ?? 'S', 0, 1));
$totalHadir = count(array_filter($records, fn($r) => $r['status_display'] === 'Attended'));
$totalPoints = array_sum(array_column($records, 'points'));
$completedCount = count($pastRecords);
$attendanceRate = $completedCount > 0 ? round(($totalHadir / $completedCount) * 100) : 0;

$menu = [
    'dashboard_pelajar' => ['Home', 'fa-house'],
    'search' => ['Search', 'fa-magnifying-glass'],
    'recommended' => ['For You', 'fa-lightbulb'],
    'rekod-penyertaan' => ['History', 'fa-clock-rotate-left'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>My Event History</h1>
                    <p>View your participation records, attendance status, and submit feedback.</p>
                </div>
            </div>

            <!-- NOTIFICATIONS -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-banner alert-banner-error" style="margin-bottom: 24px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-circle-exclamation" style="font-size:18px; color:#ef4444;"></i>
                        <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-banner alert-banner-success" style="margin-bottom: 24px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-circle-check" style="font-size:18px; color:#10b981;"></i>
                        <span><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SEARCH BAR -->
            <div style="margin-bottom: 32px; max-width: 500px;">
                <div style="position: relative; display: flex; align-items: center;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; color: var(--text-secondary);"></i>
                    <input type="text" id="searchInput" placeholder="Search events by title..." style="width: 100%; padding: 12px 16px 12px 44px; border: 1px solid var(--border); border-radius: 999px; outline: none; font-size: 14px; transition: var(--transition);">
                </div>
            </div>



            <!-- TAB FILTERS -->
            <div class="tab-nav-wrapper" style="margin-bottom: 32px;">
                <button class="tab-nav-btn active" onclick="filterRecords(event, 'all')">All Records</button>
                <button class="tab-nav-btn" onclick="filterRecords(event, 'Registered')">Registered</button>
                <button class="tab-nav-btn" onclick="filterRecords(event, 'Attended')">Attended</button>
                <button class="tab-nav-btn" onclick="filterRecords(event, 'Absent')">Absent</button>
                <button class="tab-nav-btn" onclick="filterRecords(event, 'Cancelled')">Cancelled</button>
            </div>

            <!-- RECORDS SECTION -->
            <div class="dashboard-card-wrap">
                <h2 id="upcomingHeader" style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">My Registered Programmes (Upcoming & Ongoing)</h2>
                <p id="upcomingSubheader" style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">These are the upcoming or ongoing programmes you are registered for.</p>

                <div class="record-list" id="upcomingContainer" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 40px;">
                    <?php if (count($upcomingRecords) > 0): ?>
                        <?php foreach ($upcomingRecords as $record): 
                            $badgeBg = match($record['status_display']) {
                                'Attended' => '#10b981',
                                'Absent' => '#ef4444',
                                'Cancelled' => '#64748b',
                                default => '#2563eb'
                            };
                        ?>
                            <div class="record-card"
                                 data-status="<?= $record['status_display'] ?>"
                                 data-category="<?= $record['kategori'] ?>"
                                 data-title="<?= strtolower($record['program']) ?>"
                                 style="border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; background: var(--white); transition: var(--transition);">

                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                    <div>
                                        <h3 style="font-size: 18px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;"><?= htmlspecialchars($record['program']) ?></h3>
                                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
                                            <span style="background: var(--bg-secondary); color: var(--accent-blue); padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;"><?= htmlspecialchars($record['kategori']) ?></span>
                                            <span style="background: var(--border); color: var(--text-secondary); padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;"><?= htmlspecialchars($record['role']) ?></span>
                                        </div>
                                        
                                        <div style="display: flex; gap: 16px; flex-wrap: wrap; color: var(--text-muted); font-size: 13px;">
                                            <span><i class="far fa-calendar-alt" style="margin-right: 6px; color: var(--accent-blue);"></i><?= htmlspecialchars($record['tarikh']) ?></span>
                                            <span><i class="fas fa-map-marker-alt" style="margin-right: 6px; color: var(--accent-blue);"></i><?= htmlspecialchars($record['lokasi']) ?></span>
                                        </div>
                                    </div>

                                    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                                        <span class="event-badge" style="position: static; background-color: <?= $badgeBg ?>; font-size: 12px; font-weight: 800;"><?= htmlspecialchars($record['status_display']) ?></span>
                                    </div>
                                </div>
                                <?php if ($record['status_display'] === 'Registered'): ?>
                                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--border); display: flex; justify-content: flex-end; align-items: center; gap: 12px;">
                                        <a href="program-hub.php?id=<?= (int)$record['program_id'] ?>" class="btn btn-outline btn-sm" style="border-radius:999px; color: var(--accent); border-color: var(--accent);">
                                            <i class="fas fa-comments"></i> Hub
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; border: 1px dashed var(--border); border-radius: var(--radius-md); background: var(--bg-main);">
                            <i class="fas fa-calendar-plus" style="font-size: 32px; color: var(--text-muted); margin-bottom: 12px;"></i>
                            <h3 style="font-size:16px; font-weight:700;">No upcoming programmes</h3>
                            <p style="font-size:13px; color:var(--text-secondary); margin-top: 4px;">Browse campus events and sign up to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <h2 id="pastHeader" style="border-top: 1px solid var(--border); padding-top: 32px; margin-top: 32px; font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Participation History (Past)</h2>
                <p id="pastSubheader" style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">These are the programmes that have already concluded.</p>

                <div class="record-list" id="pastContainer" style="display: flex; flex-direction: column; gap: 16px;">
                    <?php if (count($pastRecords) > 0): ?>
                        <?php foreach ($pastRecords as $record): 
                            $badgeBg = match($record['status_display']) {
                                'Attended' => '#10b981',
                                'Absent' => '#ef4444',
                                'Cancelled' => '#64748b',
                                default => '#2563eb'
                            };
                        ?>
                            <div class="record-card"
                                 data-status="<?= $record['status_display'] ?>"
                                 data-category="<?= $record['kategori'] ?>"
                                 data-title="<?= strtolower($record['program']) ?>"
                                 style="border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; background: var(--white); transition: var(--transition);">

                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                    <div>
                                        <h3 style="font-size: 18px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;"><?= htmlspecialchars($record['program']) ?></h3>
                                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
                                            <span style="background: var(--bg-secondary); color: var(--accent-blue); padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;"><?= htmlspecialchars($record['kategori']) ?></span>
                                            <span style="background: var(--border); color: var(--text-secondary); padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;"><?= htmlspecialchars($record['role']) ?></span>
                                        </div>
                                        
                                        <div style="display: flex; gap: 16px; flex-wrap: wrap; color: var(--text-muted); font-size: 13px;">
                                            <span><i class="far fa-calendar-alt" style="margin-right: 6px; color: var(--accent-blue);"></i><?= htmlspecialchars($record['tarikh']) ?></span>
                                            <span><i class="fas fa-map-marker-alt" style="margin-right: 6px; color: var(--accent-blue);"></i><?= htmlspecialchars($record['lokasi']) ?></span>
                                        </div>
                                    </div>

                                    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                                        <span class="event-badge" style="position: static; background-color: <?= $badgeBg ?>; font-size: 12px; font-weight: 800;"><?= htmlspecialchars($record['status_display']) ?></span>
                                        <?php if ($record['status_display'] === 'Attended'): ?>
                                            <span style="font-size: 13px; font-weight: 800; color: var(--accent-blue); background: #eff6ff; padding: 6px 12px; border-radius: 999px;">
                                                <i class="fas fa-coins" style="margin-right: 4px;"></i> +<?= $record['points'] ?> Points
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($record['status_display'] === 'Attended'): ?>
                                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--border); display: flex; justify-content: flex-end; align-items: center; gap: 12px;">
                                        <a href="program-hub.php?id=<?= (int)$record['program_id'] ?>" class="btn btn-outline btn-sm" style="border-radius:999px; color: var(--accent); border-color: var(--accent);">
                                            <i class="fas fa-comments"></i> Hub
                                        </a>
                                        <?php if ($record['feedback']): ?>
                                            <span style="color:#10b981; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                                                <i class="fas fa-check-circle"></i> Attendance & Feedback Completed
                                            </span>
                                        <?php else: ?>
                                            <a href="maklum-balas.php?id=<?= (int)$record['program_id'] ?>" class="btn btn-outline btn-sm" style="border-radius:999px;">
                                                <i class="fas fa-comment-dots"></i> Give Feedback
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; border: 1px dashed var(--border); border-radius: var(--radius-md); background: var(--bg-main);">
                            <i class="fas fa-history" style="font-size: 32px; color: var(--text-muted); margin-bottom: 12px;"></i>
                            <h3 style="font-size:16px; font-weight:700;">No history records</h3>
                            <p style="font-size:13px; color:var(--text-secondary); margin-top: 4px;">Completed programme records will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    function filterRecords(evt, filter) {
        const records = document.querySelectorAll('.record-card');
        const buttons = document.querySelectorAll('.tab-nav-btn');

        buttons.forEach(btn => btn.classList.remove('active'));
        evt.currentTarget.classList.add('active');

        // Toggle headers dynamically based on filter selection
        const upcomingHeader = document.getElementById('upcomingHeader');
        const upcomingSubheader = document.getElementById('upcomingSubheader');
        const pastHeader = document.getElementById('pastHeader');
        const pastSubheader = document.getElementById('pastSubheader');
        const upcomingContainer = document.getElementById('upcomingContainer');

        if (filter === 'all') {
            if (upcomingHeader) upcomingHeader.style.display = 'block';
            if (upcomingSubheader) upcomingSubheader.style.display = 'block';
            if (pastHeader) pastHeader.style.display = 'block';
            if (pastSubheader) pastSubheader.style.display = 'block';
            if (upcomingContainer) upcomingContainer.style.marginBottom = '40px';
        } else {
            if (upcomingHeader) upcomingHeader.style.display = 'none';
            if (upcomingSubheader) upcomingSubheader.style.display = 'none';
            if (pastHeader) pastHeader.style.display = 'none';
            if (pastSubheader) pastSubheader.style.display = 'none';
            if (upcomingContainer) upcomingContainer.style.marginBottom = '0';
        }

        records.forEach(record => {
            const status = record.dataset.status;
            if (filter === 'all' || filter === status) {
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
            if (title.includes(keyword)) {
                record.style.display = 'block';
            } else {
                record.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>
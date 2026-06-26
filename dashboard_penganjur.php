<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'dashboard_penganjur';

$allPrograms = db()->isConfigured() ? programs()->listByOrganizer($_SESSION['user_id'] ?? '') : [];

$activeCount = count(array_filter($allPrograms, function($p) {
    $status = getProgramStatusTimeBased($p);
    return $status === 'Upcoming' || $status === 'Ongoing';
}));

$cancelledCount = count(array_filter($allPrograms, fn($p) => ($p['status'] ?? null) === 'Cancelled'));
$totalRegistrations = 0;
$totalAttendance = 0;
$programsWithRealCounts = [];

if (db()->isConfigured() && !empty($allPrograms)) {
    foreach ($allPrograms as $p) {
        $pId = $p['id'];
        
        // Count active registrations
        $regCount = 0;
        $regRes = db()->select('pendaftaran', "?program_id=eq.$pId&status=neq.Cancelled");
        if ($regRes['ok'] && is_array($regRes['data'])) {
            $regCount = count($regRes['data']);
        }
        $totalRegistrations += $regCount;
        
        // Count present attendance
        $attCount = 0;
        $attRes = db()->select('kehadiran', "?program_id=eq.$pId&status=eq.Hadir");
        if ($attRes['ok'] && is_array($attRes['data'])) {
            $attCount = count($attRes['data']);
        }
        $totalAttendance += $attCount;
        
        // Overwrite the outdated column value
        $p['peserta_semasa'] = $regCount;
        $programsWithRealCounts[] = $p;
    }
} else {
    $programsWithRealCounts = $allPrograms;
}

$allPrograms = $programsWithRealCounts;

// Stats cards mapping
$stats = [
    ['title' => 'Total Events', 'value' => (string) count($allPrograms), 'icon' => 'fa-calendar-days', 'class' => 'stat-icon-blue'],
    ['title' => 'Total Registrations', 'value' => (string) $totalRegistrations, 'icon' => 'fa-ticket', 'class' => 'stat-icon-green'],
    ['title' => 'Total Attendance', 'value' => (string) $totalAttendance, 'icon' => 'fa-user-check', 'class' => 'stat-icon-orange'],
    ['title' => 'Active Programmes', 'value' => (string) $activeCount, 'icon' => 'fa-chart-line', 'class' => 'stat-icon-purple']
];

// Compile chart data for the last 6 months
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $monthTime = strtotime("-$i months");
    $monthKey = date('Y-m', $monthTime);
    $monthName = date('M', $monthTime);
    
    $monthlyParticipants = 0;
    foreach ($allPrograms as $p) {
        if (isset($p['tarikh']) && str_starts_with($p['tarikh'], $monthKey)) {
            $monthlyParticipants += (int) ($p['peserta_semasa'] ?? 0);
        }
    }
    
    $chartData[] = [
        'month' => $monthName,
        'value' => $monthlyParticipants
    ];
}

$maxValue = max(1, max(array_column($chartData, 'value')));

// Fetch saved counts in bulk to prevent N+1 queries
$savedCounts = [];
if (db()->isConfigured() && !empty($allPrograms)) {
    $seRes = db()->select('saved_events', '?select=program_id');
    if ($seRes['ok'] && is_array($seRes['data'])) {
        foreach ($seRes['data'] as $se) {
            $pid = $se['program_id'];
            $savedCounts[$pid] = ($savedCounts[$pid] ?? 0) + 1;
        }
    }
}

// Slice top 4 recent programs for dashboard summary
$programs = [];
foreach (array_slice($allPrograms, 0, 4) as $row) {
    $programs[] = [
        'id' => $row['id'],
        'name' => $row['nama'],
        'date' => formatProgramDates($row['start_date'] ?? null, $row['end_date'] ?? null, $row['tarikh'] ?? null),
        'participants' => ($row['peserta_semasa'] ?? 0) . '/' . ($row['kapasiti'] ?? 0),
        'status' => getProgramStatusTimeBased($row),
        'category' => $row['kategori']['nama'] ?? 'General',
        'saved_count' => $savedCounts[$row['id']] ?? 0,
        'shares_count' => (int)($row['shares_count'] ?? 0),
    ];
}

$organizerInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard | UKMInvolve</title>
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
                    <h1>Organizer Dashboard</h1>
                    <p>Monitor your events, student registrations, and performance summaries.</p>
                </div>
                <a href="hebahan-program.php" class="btn btn-primary" style="border-radius: 999px;">
                    <i class="fas fa-plus"></i> New Programme
                </a>
            </div>

            <!-- STATS CARDS GRID -->
            <div class="stats-cards-grid">
                <?php foreach ($stats as $stat): ?>
                    <div class="dashboard-stat-card">
                        <div class="dashboard-stat-info">
                            <h3><?= htmlspecialchars($stat['title']) ?></h3>
                            <div class="stat-val"><?= htmlspecialchars($stat['value']) ?></div>
                        </div>
                        <div class="dashboard-stat-icon <?= $stat['class'] ?>">
                            <i class="fas <?= $stat['icon'] ?>"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- PERSONAL RANK WIDGET -->
            <?php 
            $orgId = $_SESSION['user_id'] ?? '';
            $orgRankData = LeaderboardService::getPersonalOrganizerRankWidget($orgId);
            ?>
            <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px; background: linear-gradient(135deg, #065f46 0%, #10b981 100%); color: white; border: none; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; background: rgba(255,255,255,0.2); color: white; padding: 4px 10px; border-radius: 999px;">Your Performance</span>
                        <h2 style="font-size: 28px; font-weight: 900; margin: 8px 0 4px 0; font-family:'Outfit';">
                            #<?= $orgRankData['rank'] > 0 ? $orgRankData['rank'] : 'N/A' ?> <span style="font-size: 16px; font-weight: 500; opacity: 0.85;">out of <?= number_format($orgRankData['total']) ?> Organizers</span>
                        </h2>
                        <?php if ($orgRankData['rank'] > 3 || $orgRankData['rank'] === 0): ?>
                            <p style="font-size: 13px; color: #d1fae5; margin: 0; font-weight: 700;">
                                💡 Only 1 completed event with a 4.8+ average rating is needed to enter the Top 3 this month!
                            </p>
                        <?php else: ?>
                            <p style="font-size: 13px; color: #fef08a; margin: 0; font-weight: 700;">
                                🏆 You are currently one of the Top 3 Organizers this month! Outstanding work!
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: right; font-size: 13px; font-weight: 700; color: #d1fae5; background: rgba(255,255,255,0.1); padding: 12px 18px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">
                        <div style="margin-bottom: 4px;">Completed This Month: <span style="color: white; font-size: 15px; font-weight: 900;"><?= $orgRankData['completed_events_this_month'] ?></span></div>
                        <div>Highly Rated (4.8+): <span style="color: white; font-size: 15px; font-weight: 900;"><?= $orgRankData['high_rated_events'] ?></span></div>
                    </div>
                </div>
            </div>

            <!-- MONTHLY LEADERBOARD WIDGETS -->
            <?php include_once __DIR__ . '/components/leaderboard-widgets.php'; ?>

            <!-- MAIN GRID -->
            <div class="dashboard-grid-2col">
                <!-- LEFT COLUMN: TRENDS & EVENTS -->
                <div>
                    <!-- Student Participation Trend -->
                    <div class="dashboard-card-wrap">
                        <div class="dashboard-card-header">
                            <h2>Participation Trend</h2>
                            <p style="font-size: 12px; color: var(--text-secondary); margin: 0;">Monthly registration count</p>
                        </div>
                        <div class="chart-flex-wrapper">
                            <?php foreach ($chartData as $data): 
                                $height = ($data['value'] / $maxValue) * 180;
                            ?>
                                <div class="chart-flex-col">
                                    <div class="chart-flex-bar" style="height: <?= max(4, $height) ?>px;">
                                        <span class="chart-flex-value"><?= $data['value'] ?></span>
                                    </div>
                                    <div class="chart-flex-label"><?= $data['month'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Programmes List -->
                    <div class="dashboard-card-wrap">
                        <div class="dashboard-card-header">
                            <h2>Recent Programmes</h2>
                            <a href="urus-program.php" style="font-size:13px; font-weight:700; color:var(--accent-blue);">Manage All</a>
                        </div>
                        
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <?php if (empty($programs)): ?>
                                <p style="text-align:center; color:var(--text-secondary); padding: 20px 0;">No events published yet.</p>
                            <?php else: ?>
                                <?php foreach ($programs as $prog): 
                                    $statusStyle = '';
                                    if ($prog['status'] === 'Cancelled') {
                                        $statusStyle = 'background:#fef2f2; color:#ef4444; border:1px solid #fecaca;';
                                    } elseif ($prog['status'] === 'Ongoing') {
                                        $statusStyle = 'background:#fffbeb; color:#d97706; border:1px solid #fde68a;';
                                    } elseif ($prog['status'] === 'Completed') {
                                        $statusStyle = 'background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb;';
                                    } else {
                                        $statusStyle = 'background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;';
                                    }
                                ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; border:1px solid var(--border); padding:16px; border-radius:var(--radius-sm); transition:var(--transition); box-shadow:var(--shadow-sm);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                        <div>
                                            <span style="font-size: 10px; font-weight: 800; background: #f1f5f9; color: var(--text-secondary); padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 6px; text-transform: uppercase;">
                                                <?= htmlspecialchars($prog['category']) ?>
                                            </span>
                                            <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px; font-family:'Outfit';"><?= htmlspecialchars($prog['name']) ?></h4>
                                            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 6px;">
                                                <i class="far fa-calendar-alt" style="color: var(--accent-blue);"></i> <?= htmlspecialchars($prog['date']) ?>
                                            </div>
                                            <div style="display: flex; gap: 12px; font-size: 11px; color: var(--text-secondary); font-weight: 700; margin-top: 4px;">
                                                <span>👥 <?= explode('/', $prog['participants'])[0] ?> Regs</span>
                                                <span>❤️ <?= $prog['saved_count'] ?> Saved</span>
                                                <span>🔗 <?= $prog['shares_count'] ?> Shared</span>
                                            </div>
                                        </div>
                                        <div style="text-align: right; display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                                            <span style="font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 999px; <?= $statusStyle ?>">
                                                <?= $prog['status'] ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: QUICK ACTIONS -->
                <div>
                    <!-- Quick Actions Widget -->
                    <div class="dashboard-card-wrap" style="padding: 24px;">
                        <h2 style="font-size: 18px; margin-bottom: 20px;">Quick Actions</h2>
                        <div style="display:grid; grid-template-columns:1fr; gap:12px;">
                            <a href="hebahan-program.php" style="display:flex; align-items:center; gap:12px; padding:14px; border:1px solid var(--border); border-radius:var(--radius-sm); transition:var(--transition);" onmouseover="this.style.borderColor='var(--accent-blue)'; this.style.background='var(--bg-main)';" onmouseout="this.style.borderColor='var(--border)'; this.style.background='none';">
                                <span style="width:40px; height:40px; border-radius:50%; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:16px;"><i class="fas fa-bullhorn"></i></span>
                                <span style="font-weight:700; font-size:14px;">Create Announcement</span>
                            </a>
                            <a href="urus-program.php" style="display:flex; align-items:center; gap:12px; padding:14px; border:1px solid var(--border); border-radius:var(--radius-sm); transition:var(--transition);" onmouseover="this.style.borderColor='var(--accent-blue)'; this.style.background='var(--bg-main)';" onmouseout="this.style.borderColor='var(--border)'; this.style.background='none';">
                                <span style="width:40px; height:40px; border-radius:50%; background:#ecfdf5; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:16px;"><i class="fas fa-calendar-check"></i></span>
                                <span style="font-weight:700; font-size:14px;">Manage Programmes</span>
                            </a>
                            <a href="peserta-kehadiran.php" style="display:flex; align-items:center; gap:12px; padding:14px; border:1px solid var(--border); border-radius:var(--radius-sm); transition:var(--transition);" onmouseover="this.style.borderColor='var(--accent-blue)'; this.style.background='var(--bg-main)';" onmouseout="this.style.borderColor='var(--border)'; this.style.background='none';">
                                <span style="width:40px; height:40px; border-radius:50%; background:#fff7ed; color:#f97316; display:flex; align-items:center; justify-content:center; font-size:16px;"><i class="fas fa-users"></i></span>
                                <span style="font-weight:700; font-size:14px;">Participant Attendance</span>
                            </a>
                            <a href="laporan-statistik.php" style="display:flex; align-items:center; gap:12px; padding:14px; border:1px solid var(--border); border-radius:var(--radius-sm); transition:var(--transition);" onmouseover="this.style.borderColor='var(--accent-blue)'; this.style.background='var(--bg-main)';" onmouseout="this.style.borderColor='var(--border)'; this.style.background='none';">
                                <span style="width:40px; height:40px; border-radius:50%; background:#f5f3ff; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:16px;"><i class="fas fa-chart-column"></i></span>
                                <span style="font-weight:700; font-size:14px;">View Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>
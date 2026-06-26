<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');

$studentId = $_SESSION['user_id'] ?? '';

// Handle delete crew application
if (isset($_POST['action']) && $_POST['action'] === 'delete_crew_app') {
    $appId = (int)($_POST['app_id'] ?? 0);
    if ($appId > 0 && $studentId) {
        $checkRes = db()->select('crew_applications', '?id=eq.' . $appId . '&pelajar_id=eq.' . rawurlencode($studentId));
        if ($checkRes['ok'] && !empty($checkRes['data'])) {
            $delRes = db()->delete('crew_applications', '?id=eq.' . $appId);
            if ($delRes['ok']) {
                $_SESSION['success_message'] = "Application deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to delete application.";
            }
        }
    }
    header("Location: dashboard_pelajar.php?tab=tab-my-crew");
    exit();
}

// Handle claim voucher reward
if (isset($_POST['action']) && $_POST['action'] === 'claim_voucher') {
    $voucherId = (int)($_POST['voucher_id'] ?? 0);
    if ($voucherId > 0 && $studentId) {
        $prog = ProgressionService::getStudentProgression($studentId);
        if ($prog['level'] < 4) {
            $_SESSION['error_message'] = "Only Level 4 (UKM Elite) students can claim rewards.";
        } else {
            $vRes = db()->select('voucher_rewards', '?id=eq.' . $voucherId);
            if ($vRes['ok'] && !empty($vRes['data'][0])) {
                $voucher = $vRes['data'][0];
                if ($voucher['is_claimed']) {
                    $_SESSION['error_message'] = "This voucher has already been claimed.";
                } else {
                    $cost = (int)$voucher['points_cost'];
                    if ($prog['points'] < $cost) {
                        $_SESSION['error_message'] = "Insufficient points to claim this voucher.";
                    } else {
                        // Mark as claimed and deduct points
                        $claimRes = db()->update('voucher_rewards', '?id=eq.' . $voucherId, [
                            'is_claimed' => true,
                            'claimed_by' => $studentId,
                            'claimed_at' => date('c')
                        ]);
                        if ($claimRes['ok']) {
                            // Insert log for deduction
                            db()->insert('rekod_mata', [
                                'student_id' => $studentId,
                                'activity_type' => 'Claimed Voucher: ' . $voucher['title'],
                                'points' => -$cost,
                                'program_id' => null
                            ]);
                            users()->updateStudentPointsAndBadges($studentId);
                            $_SESSION['success_message'] = "Voucher claimed! Code: " . htmlspecialchars($voucher['code']);
                        } else {
                            $_SESSION['error_message'] = "Failed to claim voucher.";
                        }
                    }
                }
            } else {
                $_SESSION['error_message'] = "Voucher not found.";
            }
        }
    }
    header("Location: dashboard_pelajar.php?tab=tab-points");
    exit();
}

$studentName = $_SESSION['nama'] ?? 'Student';
$studentInitial = strtoupper(substr($studentName, 0, 1));

// 1. Reload live student points, level, and streak
$currentPoints = 0;
$level = 1;
$levelName = 'Participant';
$progressPercent = 0;
$streak = 0;
$attendedEvents = 0;
$completedCrew = 0;
$progDetails = [];

if (db()->isConfigured() && $studentId) {
    users()->updateStudentPointsAndBadges($studentId);
    $userData = users()->findById($studentId);
    if ($userData) {
        $currentPoints = (int)($userData['mata'] ?? 0);
        $_SESSION['mata'] = $currentPoints;
        
        $progDetails = ProgressionService::getStudentProgression($studentId, $userData);
        $level = $progDetails['level'];
        $levelName = $progDetails['level_name'];
        $streak = $progDetails['streak'];
        $attendedEvents = $progDetails['attended_programs'];
        $completedCrew = $progDetails['crew_experience'];
        
        $nextLevel = $progDetails['next_level'];
        $nextLevelPoints = $nextLevel ? (int)$nextLevel['xp'] : $currentPoints;
        $currentLevelXP = (int)$progDetails['levels_list'][$level - 1]['xp'];
        
        $range = $nextLevelPoints - $currentLevelXP;
        $earnedInRange = $currentPoints - $currentLevelXP;
        if ($range > 0) {
            $progressPercent = min(100, max(0, ($earnedInRange / $range) * 100));
        } else {
            $progressPercent = 100;
        }
    }
}

// Real participation & attendance stats from DB
$joinedEvents = 0;
$leaderboard = [];
$earnedBadges = [];
$regs = [];

if (db()->isConfigured() && $studentId) {
    // List registrations and exclude cancelled ones
    $regsResult = registrations()->listByStudent($studentId);
    $regs = array_filter($regsResult, fn($r) => ($r['status'] ?? '') !== 'Cancelled');
    $joinedEvents = count($regs);

    // Top 5 users for leaderboard
    $topUsers = db()->select('users', '?select=id,nama,mata,level,avatar_url,streak&peranan=eq.pelajar&order=mata.desc&limit=5');
    if (!$topUsers['ok']) {
        $topUsers = db()->select('users', '?select=id,nama,mata,avatar_url&peranan=eq.pelajar&order=mata.desc&limit=5');
    }
    if ($topUsers['ok']) {
        $leaderboard = $topUsers['data'];
    }
    
    // Fetch earned badges dynamically
    $earnedBadges = users()->getEarnedBadges($studentId);

    // Fetch voucher rewards
    $vouchersList = [];
    $vRes = db()->select('voucher_rewards', '?order=points_cost.asc');
    if ($vRes['ok'] && !empty($vRes['data'])) {
        $vouchersList = $vRes['data'];
    }

    // Fetch Activity Log
    $activityLog = [];
    $logResult = db()->select('rekod_mata', '?student_id=eq.' . rawurlencode($studentId) . '&order=created_at.desc&limit=10');
    if ($logResult['ok'] && !empty($logResult['data'])) {
        foreach ($logResult['data'] as $logRow) {
            $progId = $logRow['program_id'] ?? null;
            $progName = 'General Activity';
            if ($progId) {
                $pData = programs()->findById((int)$progId);
                if ($pData) {
                    $progName = $pData['nama'];
                }
            }
            $activityLog[] = [
                'date' => $logRow['created_at'],
                'event' => $progName,
                'points' => (int)($logRow['points'] ?? 0),
                'desc' => $logRow['activity_type'] ?? 'Points earned'
            ];
        }
    }
}

// Fetch Network Activity Feed (LinkedIn Style)
$networkFeed = [];
if (db()->isConfigured()) {
    // Recent programs published by organizers (solely for organizer event updates)
    $progRes = db()->select('program', '?select=id,nama,created_at,users!penganjur_id(id,nama,avatar_url)&penganjur_id=not.is.null&status=eq.Aktif&order=created_at.desc&limit=15');
    if ($progRes['ok'] && !empty($progRes['data'])) {
        foreach ($progRes['data'] as $row) {
            $org = $row['users'] ?? [];
            if (empty($org)) continue;
            $networkFeed[] = [
                'type' => 'organizer',
                'user_id' => $org['id'],
                'user_name' => $org['nama'],
                'user_avatar' => $org['avatar_url'] ?? '',
                'action' => 'published a new event: <a href="event-details.php?id=' . $row['id'] . '" style="color:var(--accent-blue);font-weight:700;text-decoration:none;">' . htmlspecialchars($row['nama']) . '</a>',
                'time' => $row['created_at'],
                'timestamp' => strtotime($row['created_at'])
            ];
        }
    }
    
    // Sort feed descending by timestamp
    usort($networkFeed, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
}

$currentBadgeName = !empty($earnedBadges) ? end($earnedBadges)['nama'] : 'Bronze';

// Retrieve saved events
$savedEventsList = [];
if (db()->isConfigured() && $studentId) {
    $savedIds = savedEvents()->getSavedPrograms($studentId);
    if (!empty($savedIds)) {
        foreach ($savedIds as $savedId) {
            $savedRow = programs()->findById((int)$savedId);
            if ($savedRow && ($savedRow['penganjur_id'] ?? null) !== null) {
                $savedEventsList[] = programs()->toStudentSearchRow($savedRow);
            }
        }
    }
}

$myInterests = [];
$followedOrganizers = [];
$featuredRecommend = null;
$events = [];

if (db()->isConfigured()) {
    $myInterests = interests()->getStudentInterestSlugs($studentId);

    // Fetch active programs (hide dummy programs where penganjur_id IS NULL)
    $activeRows = array_filter(programs()->listActiveWithCategory(), fn($row) => ($row['penganjur_id'] ?? null) !== null);
    // Calculate history frequency for smart recommendations
    $historyFreq = [];
    foreach ($regs as $reg) {
        $slug = $reg['program']['kategori']['slug'] ?? null;
        if ($slug) {
            $historyFreq[$slug] = ($historyFreq[$slug] ?? 0) + 1;
        }
    }

    // Smart Match Score Algorithm for recommendations (Centralized)
    $scoredEvents = [];
    
    foreach ($activeRows as $row) {
        $matchResult = programs()->calculateMatchScore($row, $userData ?? [], $myInterests, $followedOrganizers, $historyFreq);
        
        if ($matchResult['score'] > 0) {
            $row['match_score'] = $matchResult['score'];
            $row['tags'] = $matchResult['tags'];
            $scoredEvents[] = $row;
        }
        
        $events[] = programs()->toDashboardEvent($row);
    }
    
    usort($scoredEvents, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
    
    if (!empty($scoredEvents)) {
        $featuredRecommend = $scoredEvents[0];
    } elseif (!empty($activeRows)) {
        $featuredRecommend = $activeRows[0];
    }
    
    // Sort the raw events array by date descending for general fallback if needed
    usort($events, fn($a, $b) => strtotime($b['dateISO']) <=> strtotime($a['dateISO']));
}

// Map student registrations
$myRegisteredEvents = [];
foreach ($regs as $reg) {
    if (isset($reg['program'])) {
        $myRegisteredEvents[] = [
            'id' => $reg['program_id'],
            'title' => $reg['program']['nama'] ?? '',
            'date' => formatProgramDates($reg['program']['start_date'] ?? null, $reg['program']['end_date'] ?? null, $reg['program']['tarikh'] ?? null),
            'location' => $reg['program']['lokasi'] ?? '',
            'status' => $reg['status'] ?? 'Registered',
            'points' => (int)($reg['program']['mata'] ?? 100),
            'image' => !empty($reg['program']['poster_url']) ? $reg['program']['poster_url'] : ($reg['program']['gambar'] ?? ''),
            'category' => $reg['program']['kategori']['nama'] ?? 'Umum',
        ];
    }
}

// Fetch and map student crew applications
$myCrewApplications = [];
if (db()->isConfigured() && $studentId) {
    $crewRes = db()->select('crew_applications', '?select=*,program(*,kategori(*),users!penganjur_id(nama)),program_crew_positions(*)&pelajar_id=eq.' . rawurlencode($studentId));
    if ($crewRes['ok'] && !empty($crewRes['data'])) {
        foreach ($crewRes['data'] as $app) {
            if (isset($app['program'])) {
                $myCrewApplications[] = [
                    'id' => $app['program_id'],
                    'app_id' => $app['id'],
                    'title' => $app['program']['nama'] ?? '',
                    'date' => formatProgramDates($app['program']['start_date'] ?? null, $app['program']['end_date'] ?? null, $app['program']['tarikh'] ?? null),
                    'location' => $app['program']['lokasi'] ?? '',
                    'status' => $app['status'] ?? 'Pending',
                    'points' => (int)($app['program_crew_positions']['mata_ganjaran'] ?? 100),
                    'image' => !empty($app['program']['poster_url']) ? $app['program']['poster_url'] : ($app['program']['gambar'] ?? ''),
                    'category' => $app['program']['kategori']['nama'] ?? 'Umum',
                    'position_name' => $app['program_crew_positions']['nama_jawatan'] ?? 'Crew',
                    'reviewed_at' => $app['reviewed_at'] ?? null,
                    'organizer' => $app['program']['users']['nama'] ?? 'Penganjur',
                    'penganjur_id' => $app['program']['penganjur_id'] ?? null
                ];
            }
        }
    }
}

$acceptedCrewCount = count(array_filter($myCrewApplications, fn($a) => $a['status'] === 'Accepted'));

// ==========================================
// NEW CALENDAR & COUNTDOWN LOGIC
// ==========================================
$allMyEvents = [];
$todayDate = date('Y-m-d');
$todayTime = date('H:i:s');

// Helper to extract raw date and time from event array safely
function getRawEventDateTime($prog, $isCrew = false, $posName = null) {
    if (!is_array($prog) || empty($prog)) return null;
    $startDate = $prog['start_date'] ?? $prog['tarikh'] ?? null;
    $endDate = $prog['end_date'] ?? $startDate;
    $startTime = $prog['start_time'] ?? $prog['masa'] ?? '00:00:00';
    $endTime = $prog['end_time'] ?? $startTime;
    
    return [
        'id' => $prog['id'] ?? null,
        'title' => $prog['nama'] ?? 'Unknown Event',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'location' => $prog['lokasi'] ?? '',
        'poster' => !empty($prog['poster_url']) ? $prog['poster_url'] : ($prog['gambar'] ?? ''),
        'is_crew' => $isCrew,
        'role_name' => $isCrew ? $posName : 'Participant',
        'organizer' => $prog['users']['nama'] ?? 'Unknown Organizer',
        'penganjur_id' => $prog['penganjur_id'] ?? null,
        'original_status' => $prog['status'] ?? 'Aktif'
    ];
}

// Map participant registrations
foreach ($regs as $reg) {
    if (!empty($reg['program'])) {
        $reg['program']['id'] = $reg['program_id'];
        $rawEvent = getRawEventDateTime($reg['program']);
        if ($rawEvent) {
            if (($reg['status'] ?? '') === 'Completed') {
                $rawEvent['derived_status'] = 'Completed';
            }
            $allMyEvents[$reg['program_id']] = $rawEvent;
        }
    }
}

// Map crew applications (override participant role if they are accepted crew)
if (db()->isConfigured() && !empty($crewRes['data'])) {
    foreach ($crewRes['data'] as $app) {
        if (($app['status'] ?? '') === 'Accepted' || ($app['status'] ?? '') === 'Completed') {
            if (!empty($app['program']) && isset($app['program']['id'])) {
                $rawEvent = getRawEventDateTime($app['program'], true, $app['program_crew_positions']['nama_jawatan'] ?? 'Crew');
                if ($rawEvent) {
                    if (($app['status'] ?? '') === 'Completed') {
                        $rawEvent['derived_status'] = 'Completed';
                    }
                    $allMyEvents[$app['program_id']] = $rawEvent;
                }
            }
        }
    }
}

// Calculate Statuses & Prepare for Frontend
$calendarEvents = [];
$upcomingEventsQueue = [];
$statsCalc = [
    'registered' => count($myRegisteredEvents),
    'completed' => 0,
    'upcoming' => 0,
    'crew' => 0
];

$reminders = [];

// 1. Process My Registered/Crew Events (for stats, countdown, and reminders)
foreach ($allMyEvents as $id => $ev) {
    if (empty($ev['start_date'])) continue;
    
    $status = 'Upcoming';
    if (($ev['derived_status'] ?? '') === 'Completed' || $ev['end_date'] < $todayDate) {
        $status = 'Completed';
    } elseif (($ev['original_status'] ?? '') === 'Batal') {
        $status = 'Cancelled';
    } elseif ($ev['start_date'] <= $todayDate && $ev['end_date'] >= $todayDate) {
        $status = 'Ongoing';
    }
    
    $ev['computed_status'] = $status;
    
    if ($status === 'Completed') $statsCalc['completed']++;
    if ($status === 'Upcoming' || $status === 'Ongoing') $statsCalc['upcoming']++;
    if (!empty($ev['is_crew'])) $statsCalc['crew']++;
    
    if ($status === 'Upcoming' || $status === 'Ongoing') {
        $startDateTimeStr = $ev['start_date'] . ' ' . $ev['start_time'];
        $ev['timestamp'] = strtotime($startDateTimeStr);
        $upcomingEventsQueue[] = $ev;
        
        $daysDiff = floor(($ev['timestamp'] - time()) / (60 * 60 * 24));
        if ($daysDiff == 0 && $ev['start_date'] === $todayDate) {
            $reminders[] = ['type' => 'urgent', 'icon' => 'fa-bell', 'message' => '<strong>' . htmlspecialchars($ev['title']) . '</strong> is happening today!'];
        } elseif ($daysDiff == 1) {
            $reminders[] = ['type' => 'warning', 'icon' => 'fa-clock', 'message' => '<strong>' . htmlspecialchars($ev['title']) . '</strong> starts tomorrow.'];
        } elseif ($daysDiff > 1 && $daysDiff <= 3) {
            $reminders[] = ['type' => 'info', 'icon' => 'fa-calendar-alt', 'message' => '<strong>' . htmlspecialchars($ev['title']) . '</strong> starts in ' . $daysDiff . ' days.'];
        }
    }
}

// 2. Prepare ALL Active Events for the Calendar (Discovery Calendar)
foreach ($activeRows as $row) {
    $rawEvent = getRawEventDateTime($row);
    if (!$rawEvent || empty($rawEvent['start_date'])) continue;
    
    $progId = $rawEvent['id'];
    
    // Check if this event is already in My Events (Registered/Crew)
    if (isset($allMyEvents[$progId])) {
        // Use my existing event which has 'is_crew' or 'Participant' role info
        $myEv = $allMyEvents[$progId];
        $calendarEvents[] = [
            'id' => $myEv['id'],
            'title' => $myEv['title'],
            'start_date' => $myEv['start_date'],
            'end_date' => $myEv['end_date'],
            'start_time' => $myEv['start_time'],
            'end_time' => $myEv['end_time'],
            'location' => $myEv['location'],
            'poster' => $myEv['poster'],
            'organizer' => $myEv['organizer'],
            'indicator_type' => !empty($myEv['is_crew']) ? 'crew' : 'registered',
            'role_name' => $myEv['role_name']
        ];
    } else {
        // Available Event
        $calendarEvents[] = [
            'id' => $rawEvent['id'],
            'title' => $rawEvent['title'],
            'start_date' => $rawEvent['start_date'],
            'end_date' => $rawEvent['end_date'],
            'start_time' => $rawEvent['start_time'],
            'end_time' => $rawEvent['end_time'],
            'location' => $rawEvent['location'],
            'poster' => $rawEvent['poster'],
            'organizer' => $rawEvent['organizer'],
            'indicator_type' => 'available',
            'role_name' => 'Available Event'
        ];
    }
}

// Sort upcoming events by closest date
usort($upcomingEventsQueue, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
$nearestEvent = $upcomingEventsQueue[0] ?? null;

$calendarEventsJson = json_encode($calendarEvents);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | UKMInvolve</title>
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
                    <h1>Welcome back, <?= htmlspecialchars($studentName) ?>!</h1>
                    <p>Track your registered programmes, points, and badges.</p>
                </div>
            </div>

            <!-- SESSION ALERTS -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-banner alert-banner-success" style="margin-bottom: 24px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px; color: #065f46; font-weight: 700;">
                    <i class="fas fa-circle-check" style="font-size:18px; color:#10b981;"></i>
                    <span><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-banner alert-banner-error" style="margin-bottom: 24px; background: #fef2f2; border: 1px solid #fecaca; padding: 12px 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px; color: #991b1b; font-weight: 700;">
                    <i class="fas fa-circle-exclamation" style="font-size:18px; color:#ef4444;"></i>
                    <span><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></span>
                </div>
            <?php endif; ?>

            <!-- OVERVIEW STATS -->
            <div class="stats-cards-grid">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Registered Events</h3>
                        <div class="stat-val"><?= $statsCalc['registered'] ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-ticket-simple"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Upcoming</h3>
                        <div class="stat-val"><?= $statsCalc['upcoming'] ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Completed</h3>
                        <div class="stat-val"><?= $statsCalc['completed'] ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Crew Roles</h3>
                        <div class="stat-val"><?= $statsCalc['crew'] ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-purple">
                        <i class="fas fa-users-gear"></i>
                    </div>
                </div>
            </div>

            <!-- TAB SYSTEM -->
            <div class="tab-nav-wrapper">
                <button class="tab-nav-btn active" onclick="switchTab(event, 'tab-overview')">Overview</button>
                <button class="tab-nav-btn" onclick="switchTab(event, 'tab-my-events')">History (<?= count($myRegisteredEvents) ?>)</button>
                <button class="tab-nav-btn" onclick="switchTab(event, 'tab-my-crew')" style="position: relative;">
                    My Crew Apps (<?= count($myCrewApplications) ?>)
                    <?php if ($acceptedCrewCount > 0): ?>
                        <span style="position:absolute; top:-4px; right:-8px; background:#10b981; color:white; font-size:10px; font-weight:800; padding:2px 6px; border-radius:999px;"><?= $acceptedCrewCount ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-nav-btn" onclick="switchTab(event, 'tab-recommended')">Recommended & Saved</button>
                <button class="tab-nav-btn" onclick="switchTab(event, 'tab-points')">My Points & Leaderboard</button>
            </div>

            <!-- TAB CONTENT: OVERVIEW -->
            <div id="tab-overview" class="tab-content-active">
                
                <!-- PERSONAL RANK WIDGET -->
                <?php 
                $personalRank = LeaderboardService::getPersonalRankWidget($studentId);
                ?>
                <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; border: none; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; background: rgba(255,255,255,0.2); color: white; padding: 4px 10px; border-radius: 999px;">Your Placement</span>
                            <h2 style="font-size: 28px; font-weight: 900; margin: 8px 0 4px 0; font-family:'Outfit';">
                                #<?= $personalRank['rank'] > 0 ? $personalRank['rank'] : 'N/A' ?> <span style="font-size: 16px; font-weight: 500; opacity: 0.85;">out of <?= number_format($personalRank['total']) ?> Students</span>
                            </h2>
                            <?php if ($personalRank['needed'] > 0): ?>
                                <p style="font-size: 13px; color: #dbeafe; margin: 0; font-weight: 700;">
                                    🔥 <?= $personalRank['needed'] ?> more point<?= $personalRank['needed'] == 1 ? '' : 's' ?> needed to reach the Top 10!
                                </p>
                            <?php else: ?>
                                <p style="font-size: 13px; color: #a7f3d0; margin: 0; font-weight: 700;">
                                    🎉 You are in the Top 10! Excellent job!
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div style="flex: 1; max-width: 300px; min-width: 200px;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #dbeafe;">
                                <span>Progress to Top 10</span>
                                <span><?= $personalRank['progress'] ?>%</span>
                            </div>
                            <div style="background: rgba(255,255,255,0.15); height: 12px; border-radius: 999px; overflow: hidden; border: 1px solid rgba(255,255,255,0.25);">
                                <div style="width: <?= $personalRank['progress'] ?>%; height: 100%; background: linear-gradient(90deg, #34d399 0%, #059669 100%);"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MONTHLY LEADERBOARD WIDGETS -->
                <?php include_once __DIR__ . '/components/leaderboard-widgets.php'; ?>

                <div class="dashboard-main-split">
                    
                    <!-- LEFT COLUMN: ALERTS, REMINDERS, CALENDAR/COUNTDOWN, HERO, UPCOMING -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        
                        <?php 
                        $acceptedApps = array_filter($myCrewApplications, fn($a) => ($a['status'] ?? '') === 'Accepted');
                        if (!empty($acceptedApps)): 
                        ?>
                            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 16px; border-radius: var(--radius-md);">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                    <div style="display: flex; gap: 16px;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div>
                                            <h3 style="color: #065f46; font-size: 16px; font-weight: 800; margin-bottom: 8px;">Application Update!</h3>
                                            <?php foreach ($acceptedApps as $aApp): ?>
                                                <p style="color: #047857; font-size: 14px; margin: 0 0 8px 0;">
                                                    Congratulations! You have been accepted as <strong><?= htmlspecialchars($aApp['position_name']) ?></strong> for <strong><?= htmlspecialchars($aApp['title']) ?></strong>.
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <button onclick="switchTab(event, 'tab-my-crew')" class="btn btn-sm" style="background: #10b981; color: white; border-radius: 999px; font-weight: 800; border: none; white-space: nowrap;">[ View Details ]</button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- NOTIFICATIONS / REMINDERS -->
                        <?php if (!empty($reminders)): ?>
                            <div>
                                <h3 style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 12px; letter-spacing: 0.5px;">Reminders</h3>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ($reminders as $rem): 
                                        $bg = '#eff6ff'; $color = '#2563eb'; $border = '#bfdbfe';
                                        if ($rem['type'] === 'urgent') { $bg = '#fef2f2'; $color = '#dc2626'; $border = '#fecaca'; }
                                        elseif ($rem['type'] === 'warning') { $bg = '#fff7ed'; $color = '#ea580c'; $border = '#fed7aa'; }
                                    ?>
                                        <div style="background: <?= $bg ?>; border: 1px solid <?= $border ?>; border-left: 4px solid <?= $color ?>; padding: 12px 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 12px;">
                                             <i class="fas <?= $rem['icon'] ?>" style="color: <?= $color ?>; font-size: 16px;"></i>
                                             <span style="font-size: 14px; color: var(--text-primary);"><?= $rem['message'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- SIDE-BY-SIDE RECOMMENDED & CALENDAR WIDGETS -->
                        <div class="calendar-countdown-container">
                            <!-- Left: Recommended Event Hero -->
                            <div class="dashboard-card-wrap" style="padding: 0; display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--shadow-md); min-height: 100%; overflow: hidden; margin-bottom: 0; border: none; background: var(--white);">
                                <?php if ($featuredRecommend): ?>
                                    <!-- Event Poster Header -->
                                    <div style="height: 160px; position: relative; overflow: hidden; flex-shrink: 0;">
                                        <img src="<?= htmlspecialchars(getImagePath(!empty($featuredRecommend['poster_url']) ? $featuredRecommend['poster_url'] : ($featuredRecommend['gambar'] ?? ''))) ?>" alt="Featured" style="width: 100%; height: 100%; object-fit: cover;">
                                        <div style="position: absolute; top: 12px; left: 12px; z-index: 10;">
                                            <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; background: var(--accent); color: var(--white); padding: 4px 10px; border-radius: 4px; box-shadow: var(--shadow-sm); display: inline-block;">Recommended For You</span>
                                        </div>
                                        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 60px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); pointer-events: none;"></div>
                                    </div>
                                    
                                    <!-- Content Body -->
                                    <div style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; gap: 12px;">
                                        <div>
                                            <h2 style="font-size: 16px; margin-bottom: 6px; font-family: 'Outfit'; font-weight: 800; line-height: 1.3; color: var(--text-primary); height: 42px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars($featuredRecommend['nama'] ?? '') ?></h2>
                                            <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.6; margin: 0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 8; -webkit-box-orient: vertical;"><?= htmlspecialchars($featuredRecommend['penerangan'] ?? '') ?></p>
                                        </div>
                                        
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: auto; border-top: 1px solid var(--border); padding-top: 12px;">
                                            <span style="font-weight: 800; font-size: 12px; color: var(--accent);">+<?= (int)($featuredRecommend['mata'] ?? 100) ?> Pts</span>
                                            <a href="event-details.php?id=<?= $featuredRecommend['id'] ?>" class="btn btn-primary btn-sm" style="border-radius: 999px; padding: 6px 16px; font-size: 11px; font-weight: 800;">View Details</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Fallback interest setup card -->
                                    <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: var(--white); padding: 32px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; min-height: 300px; position: relative;">
                                        <div style="z-index: 10;">
                                            <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; background: var(--accent-blue); color: var(--white); padding: 4px 10px; border-radius: 4px; display: inline-block; margin-bottom: 16px;">Personalize Your Feed</span>
                                            <h2 style="color: var(--white); font-size: 20px; margin-bottom: 12px; font-family: 'Outfit'; font-weight: 800; line-height: 1.3;">Find Your Interest</h2>
                                            <p style="color: var(--text-muted); font-size: 13px; line-height: 1.5;">Complete your co-curricular preferences to get tailored workshop and volunteer suggestions.</p>
                                        </div>
                                        <div style="z-index: 10; margin-top: 24px;">
                                            <a href="recommended.php" class="btn btn-primary" style="border-radius: 999px; padding: 8px 20px; font-size: 12px; font-weight: 800;">Select Interests</a>
                                        </div>
                                        <div style="position: absolute; right: -50px; bottom: -50px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(37,99,235,0.15) 0%, transparent 70%); z-index: 1;"></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Right: Calendar with small countdown inside -->
                            <!-- CALENDAR WIDGET -->
                            <div class="dashboard-card-wrap" style="padding: 20px; margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                        <h2 style="font-size: 16px; font-weight: 800; font-family: 'Outfit';"><i class="fas fa-calendar-alt" style="color: var(--accent-blue); margin-right: 8px;"></i> Event Calendar</h2>
                                    </div>

                                    <!-- Small Countdown Widget INSIDE Calendar Card -->
                                    <?php if ($nearestEvent): ?>
                                        <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Next: <?= htmlspecialchars($nearestEvent['title']) ?></div>
                                                <div style="font-size: 10px; color: #3b82f6; font-weight: 700;">
                                                    Role: <?= $nearestEvent['is_crew'] ? htmlspecialchars($nearestEvent['role_name']) : 'Participant' ?>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 4px; font-weight: 800; font-size: 10px; color: #1e3a8a; background: white; padding: 4px 6px; border-radius: 4px; border: 1px solid #bfdbfe; white-space: nowrap;" id="nearestCountdown" data-target="<?= $nearestEvent['timestamp'] ?>">
                                                <span id="cdDays">0</span>d : <span id="cdHrs">0</span>h : <span id="cdMins">0</span>m
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                        <button class="btn btn-sm btn-outline" style="border-radius: 999px; padding: 4px 12px;" id="prevMonthBtn"><i class="fas fa-chevron-left"></i></button>
                                        <span style="font-weight: 800; font-size: 14px; text-align: center;" id="calendarMonthYear">September 2026</span>
                                        <button class="btn btn-sm btn-outline" style="border-radius: 999px; padding: 4px 12px;" id="nextMonthBtn"><i class="fas fa-chevron-right"></i></button>
                                    </div>
                                    
                                    <!-- Compact Legend -->
                                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; font-size: 11px; font-weight: 700; color: var(--text-secondary);">
                                        <div style="display: flex; align-items: center; gap: 4px;"><div style="color: #3b82f6; font-size: 14px; line-height: 1;">&bull;</div> Registered</div>
                                        <div style="display: flex; align-items: center; gap: 4px;"><div style="color: #8b5cf6; font-size: 11px; line-height: 1;">&#9733;</div> Crew</div>
                                        <div style="display: flex; align-items: center; gap: 4px;"><div style="color: #f59e0b; font-size: 14px; line-height: 1;">&bull;</div> Available</div>
                                    </div>

                                    <!-- Calendar Grid -->
                                    <div class="calendar-container">
                                        <div class="calendar-header-row" style="font-size: 10px; padding: 6px 0;">
                                            <div>S</div><div>M</div><div>T</div><div>W</div><div>T</div><div>F</div><div>S</div>
                                        </div>
                                        <div class="calendar-days-grid compact" id="calendarDaysGrid">
                                            <!-- JS will populate days here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- UPCOMING QUEUE INSIDE CALENDAR CARD -->
                                <?php if (count($upcomingEventsQueue) > 1): ?>
                                    <div style="margin-top: 16px; border-top: 1px solid var(--border); padding-top: 12px;">
                                        <h3 style="font-size: 12px; font-weight: 800; margin-bottom: 8px; color: var(--text-secondary);">Upcoming Queue</h3>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <?php 
                                            $queueCount = 1;
                                            foreach ($upcomingEventsQueue as $idx => $qEv): 
                                                if ($idx === 0) continue; // Skip nearest
                                                if ($queueCount > 2) break; // Max 2 items in queue to keep it compact
                                                
                                                $qDays = floor(($qEv['timestamp'] - time()) / (60 * 60 * 24));
                                                $qStr = $qDays > 0 ? $qDays . 'd' : 'Today';
                                            ?>
                                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; gap: 8px;">
                                                    <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                                        <div style="width: 16px; height: 16px; background: var(--bg-secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; color: var(--text-secondary); flex-shrink: 0;"><?= $queueCount ?></div>
                                                        <span style="font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($qEv['title']) ?></span>
                                                    </div>
                                                    <span style="font-weight: 800; color: var(--accent-blue); flex-shrink: 0;"><?= $qStr ?></span>
                                                </div>
                                            <?php 
                                                $queueCount++;
                                            endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- UPCOMING EVENTS PREVIEW -->
                        <div class="dashboard-card-wrap" style="margin-bottom: 0;">
                            <div class="dashboard-card-header">
                                <h2>Upcoming Active Events</h2>
                                <a href="events.php" style="font-weight: 700; color: var(--accent-blue); font-size: 14px;">Browse All <i class="fas fa-arrow-right"></i></a>
                            </div>
                            
                            <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                                <?php if (empty($events)): ?>
                                    <p style="grid-column: 1/-1; text-align: center; color: var(--text-secondary); padding: 30px 0;">No active events available yet.</p>
                                <?php else: ?>
                                    <?php foreach (array_slice($events, 0, 4) as $ev): ?>
                                        <div class="event-card">
                                            <div class="event-img-wrap" style="height: 100px;">
                                                <img src="<?= htmlspecialchars(getImagePath($ev['image'])) ?>" alt="Event" class="event-img">
                                            </div>
                                            <div class="event-card-body" style="padding: 12px;">
                                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px; gap: 4px;">
                                                    <span class="event-category" style="font-size: 10px; margin-bottom: 0;"><?= htmlspecialchars($ev['category']) ?></span>
                                                    <?php if (!empty($ev['penganjur_id'])): ?>
                                                        <a href="organizer-profile.php?id=<?= urlencode($ev['penganjur_id']) ?>" class="event-organizer" style="font-size: 10px; color: var(--accent-blue); text-decoration: none; font-weight: 700; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="View Organizer Profile"><?= htmlspecialchars($ev['organizer'] ?? '') ?></a>
                                                    <?php elseif (!empty($ev['organizer'])): ?>
                                                        <span class="event-organizer" style="font-size: 10px; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($ev['organizer']) ?>"><?= htmlspecialchars($ev['organizer']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <h3 style="font-size: 13px; margin-bottom: 8px; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars($ev['title']) ?></h3>
                                                <div style="font-size: 10px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    <div><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($ev['date']) ?></div>
                                                </div>
                                                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border); padding-top:6px;">
                                                    <span style="font-weight:800; font-size:11px; color:var(--accent);">+<?= $ev['points'] ?> Pts</span>
                                                    <a href="event-details.php?id=<?= $ev['id'] ?>" class="btn btn-primary btn-sm" style="border-radius: 999px; padding: 4px 10px; font-size: 10px;">View</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: POINTS, LEADERBOARD, FEED -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        
                        <!-- POINT LEVEL XP CARD -->
                        <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 0;">
                            <h2 style="font-size: 18px; margin-bottom: 16px;">Points Progress</h2>
                            <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 10px;">Level <?= $level ?> • <strong><?= htmlspecialchars($levelName) ?></strong></p>
                            <div style="background: var(--bg-secondary); height: 10px; border-radius: 999px; overflow: hidden; margin-bottom: 10px;">
                                <div style="width: <?= $progressPercent ?>%; height: 100%; background: linear-gradient(90deg, #60a5fa 0%, #2563eb 100%);"></div>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted); font-weight: 700;"><?= $currentPoints ?> / <?= $nextLevelPoints ?> XP to next level</span>
                        </div>

                        <!-- MINI LEADERBOARD -->
                        <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 0;">
                            <h2 style="font-size: 18px; margin-bottom: 16px;">Top Students</h2>
                            <div style="display:flex; flex-direction:column; gap:12px;">
                                <?php foreach (array_slice($leaderboard, 0, 3) as $index => $u): 
                                    $isMe = $u['id'] === $studentId;
                                ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span style="font-weight: 800; font-size:12px; width:20px; text-align:center; color:var(--text-secondary);"><?= $index + 1 ?></span>
                                            <div style="flex-shrink: 0; display: inline-flex;">
                                                <?= ProgressionService::renderAvatarHTML($u, 'sm') ?>
                                            </div>
                                            <span style="font-size:13px; font-weight: <?= $isMe ? '800; color:var(--accent-blue);' : '500;' ?>"><?= htmlspecialchars($u['nama'] ?? '') ?></span>
                                        </div>
                                        <span style="font-size:13px; font-weight:800;"><?= $u['mata'] ?? 0 ?> Pts</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- LINKEDIN-STYLE ACTIVITY FEED -->
                        <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 0;">
                            <h2 style="font-size: 18px; margin-bottom: 16px;"><i class="fas fa-rss" style="color: #f97316; margin-right: 6px;"></i> Campus Feed</h2>
                            
                            <?php if (empty($networkFeed)): ?>
                                <p style="font-size: 13px; color: var(--text-secondary); text-align: center; padding: 10px 0;">No recent campus activity.</p>
                            <?php else: ?>
                                <div style="display:flex; flex-direction:column; gap:16px;">
                                    <?php foreach ($networkFeed as $feed): ?>
                                        <div style="display:flex; gap:12px; align-items:flex-start; border-bottom:1px solid var(--border); padding-bottom:12px;">
                                            <?php 
                                                $profileLink = ($feed['type'] ?? '') === 'student' ? 'public-profile.php?id='.($feed['user_id'] ?? '') : 'organizer-profile.php?id='.($feed['user_id'] ?? '');
                                                $initial = strtoupper(substr($feed['user_name'] ?? 'U', 0, 1));
                                            ?>
                                            <a href="<?= $profileLink ?>" style="text-decoration:none; display:block;">
                                                <?php if (!empty($feed['user_avatar'])): ?>
                                                    <img src="<?= htmlspecialchars($feed['user_avatar']) ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border: 1px solid var(--border);">
                                                <?php else: ?>
                                                    <div style="width:40px; height:40px; border-radius:50%; background:var(--bg-secondary); color:var(--text-muted); font-weight:800; display:flex; align-items:center; justify-content:center; border: 1px solid var(--border);"><?= $initial ?></div>
                                                <?php endif; ?>
                                            </a>
                                            <div style="flex:1;">
                                                <div style="font-size:13px; line-height:1.4;">
                                                    <a href="<?= $profileLink ?>" style="font-weight:800; color:var(--text-primary); text-decoration:none;"><?= htmlspecialchars($feed['user_name'] ?? '') ?></a>
                                                    <span style="color:var(--text-secondary);"><?= $feed['action'] ?? '' ?></span>
                                                </div>
                                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                                                    <i class="far fa-clock" style="margin-right:2px;"></i> 
                                                    <?php 
                                                        $diff = time() - ($feed['timestamp'] ?? time());
                                                        if ($diff < 3600) echo floor($diff/60) . 'm ago';
                                                        elseif ($diff < 86400) echo floor($diff/3600) . 'h ago';
                                                        else echo floor($diff/86400) . 'd ago';
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: MY EVENTS -->
            <div id="tab-my-events" style="display: none;">
                <div class="dashboard-card-wrap" style="min-height: 300px;">
                    <div class="dashboard-card-header">
                        <h2>My Registered Events</h2>
                    </div>

                    <?php if (empty($myRegisteredEvents)): ?>
                        <div style="text-align: center; padding: 60px 0;">
                            <i class="fas fa-calendar-xmark" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                            <h3>No registered programmes yet</h3>
                            <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 14px;">Explore events happening on campus and start earning points.</p>
                            <a href="events.php" class="btn btn-primary" style="border-radius: 999px;">Explore Events</a>
                        </div>
                    <?php else: ?>
                        <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                            <?php foreach ($myRegisteredEvents as $myEv): 
                                $statusClass = '';
                                if ($myEv['status'] === 'Attended' || $myEv['status'] === 'Hadir') {
                                    $statusClass = 'background:#d1fae5; color:#065f46; border: 1px solid #a7f3d0;';
                                    $statusLabel = 'Attended';
                                } else {
                                    $statusClass = 'background:#eff6ff; color:#1e40af; border: 1px solid #bfdbfe;';
                                    $statusLabel = 'Registered';
                                }
                            ?>
                                <div class="event-card">
                                    <div class="event-img-wrap" style="height: 140px;">
                                        <?php if (!empty($myEv['image'])): ?>
                                            <img src="<?= htmlspecialchars(getImagePath($myEv['image'])) ?>" alt="Event" class="event-img">
                                        <?php else: ?>
                                        <div style="width:100%; height:100%; background:var(--bg-secondary); display:flex; align-items:center; justify-content:center; color:var(--text-muted);"><i class="fas fa-image" style="font-size:32px;"></i></div>
                                        <?php endif; ?>
                                        <span style="position: absolute; top: 12px; right: 12px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </div>
                                    <div class="event-card-body" style="padding: 16px;">
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px; gap: 4px;">
                                            <span class="event-category" style="font-size: 11px; margin-bottom: 0;"><?= htmlspecialchars($myEv['category']) ?></span>
                                            <?php if (!empty($myEv['penganjur_id'])): ?>
                                                <a href="organizer-profile.php?id=<?= urlencode($myEv['penganjur_id']) ?>" class="event-organizer" style="font-size: 11px; color: var(--accent-blue); text-decoration: none; font-weight: 700; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="View Organizer Profile"><?= htmlspecialchars($myEv['organizer'] ?? '') ?></a>
                                            <?php elseif (!empty($myEv['organizer'])): ?>
                                                <span class="event-organizer" style="font-size: 11px; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($myEv['organizer']) ?>"><?= htmlspecialchars($myEv['organizer']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 style="font-size: 16px; margin-bottom: 6px; height: 42px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars($myEv['title']) ?></h3>
                                        <div style="font-size: 12px; font-weight: 800; color: #3b82f6; margin-bottom: 8px;"><i class="fas fa-user"></i> Role: Participant</div>
                                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px; display:flex; flex-direction:column; gap:4px;">
                                            <div><i class="far fa-calendar-alt" style="width:16px;"></i> <?= htmlspecialchars($myEv['date']) ?></div>
                                            <div><i class="fas fa-map-marker-alt" style="width:16px;"></i> <?= htmlspecialchars($myEv['location']) ?></div>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border); padding-top:10px;">
                                            <span style="font-weight:800; font-size:13px; color:var(--accent);">+<?= $myEv['points'] ?> Pts</span>
                                            <div style="display:flex; gap: 8px;">
                                                <a href="program-hub.php?id=<?= $myEv['id'] ?>" class="btn btn-outline btn-sm" style="border-radius: 999px; color: var(--accent); border-color: var(--accent);">Hub</a>
                                                <a href="event-details.php?id=<?= $myEv['id'] ?>" class="btn btn-primary btn-sm" style="border-radius: 999px;">Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB CONTENT: MY CREW APPS -->
            <div id="tab-my-crew" style="display: none;">
                <div class="dashboard-card-wrap" style="min-height: 300px;">
                    <div class="dashboard-card-header">
                        <h2>My Crew Applications</h2>
                    </div>

                    <?php if (empty($myCrewApplications)): ?>
                        <div style="text-align: center; padding: 60px 0;">
                            <i class="fas fa-users-cog" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                            <h3>No crew applications yet</h3>
                            <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 14px;">Apply to be a committee member for an event to build your portfolio.</p>
                            <a href="events.php" class="btn btn-primary" style="border-radius: 999px;">Explore Events</a>
                        </div>
                    <?php else: ?>
                        <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                            <?php foreach ($myCrewApplications as $myApp): 
                                $statusClass = '';
                                if ($myApp['status'] === 'Accepted') {
                                    $statusClass = 'background:#d1fae5; color:#065f46; border: 1px solid #a7f3d0;';
                                } elseif ($myApp['status'] === 'Completed') {
                                    $statusClass = 'background:#dbeafe; color:#1e40af; border: 1px solid #bfdbfe;';
                                } elseif ($myApp['status'] === 'Rejected') {
                                    $statusClass = 'background:#fee2e2; color:#991b1b; border: 1px solid #fecaca;';
                                } else {
                                    $statusClass = 'background:#fef3c7; color:#92400e; border: 1px solid #fde68a;';
                                }
                            ?>
                                <div class="event-card">
                                    <div class="event-img-wrap" style="height: 140px;">
                                        <?php if (!empty($myApp['image'])): ?>
                                            <img src="<?= htmlspecialchars(getImagePath($myApp['image'])) ?>" alt="Event" class="event-img">
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; background:var(--bg-secondary); display:flex; align-items:center; justify-content:center; color:var(--text-muted);"><i class="fas fa-image" style="font-size:32px;"></i></div>
                                        <?php endif; ?>
                                        <span style="position: absolute; top: 12px; right: 12px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; <?= $statusClass ?>"><?= htmlspecialchars($myApp['status']) ?></span>
                                    </div>
                                    <div class="event-card-body" style="padding: 16px;">
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px; gap: 4px;">
                                            <span class="event-category" style="font-size: 11px; margin-bottom: 0;"><?= htmlspecialchars($myApp['category']) ?></span>
                                            <?php if (!empty($myApp['penganjur_id'])): ?>
                                                <a href="organizer-profile.php?id=<?= urlencode($myApp['penganjur_id']) ?>" class="event-organizer" style="font-size: 11px; color: var(--accent-blue); text-decoration: none; font-weight: 700; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="View Organizer Profile"><?= htmlspecialchars($myApp['organizer'] ?? '') ?></a>
                                            <?php elseif (!empty($myApp['organizer'])): ?>
                                                <span class="event-organizer" style="font-size: 11px; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($myApp['organizer']) ?>"><?= htmlspecialchars($myApp['organizer']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 style="font-size: 16px; margin-bottom: 8px; height: 22px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><?= htmlspecialchars($myApp['title']) ?></h3>
                                        <div style="font-weight: 800; font-size: 14px; color: var(--accent-blue); margin-bottom: 12px;">Position: <?= htmlspecialchars($myApp['position_name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px; display:flex; flex-direction:column; gap:4px;">
                                            <div><i class="far fa-calendar-alt" style="width:16px;"></i> Event Date: <?= htmlspecialchars($myApp['date']) ?></div>
                                            <?php if ($myApp['reviewed_at']): ?>
                                                <div><i class="fas fa-gavel" style="width:16px;"></i> Decision Date: <?= date('j M Y', strtotime($myApp['reviewed_at'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border); padding-top:10px;">
                                            <span style="font-weight:800; font-size:13px; color:var(--accent);">+<?= $myApp['points'] ?> Pts</span>
                                            <div style="display:flex; gap: 8px; align-items: center;">
                                                <?php if ($myApp['status'] === 'Accepted'): ?>
                                                    <a href="program-hub.php?id=<?= $myApp['id'] ?>" class="btn btn-outline btn-sm" style="border-radius: 999px; color: var(--accent); border-color: var(--accent);">Hub</a>
                                                <?php endif; ?>
                                                <?php if ($myApp['status'] === 'Rejected'): ?>
                                                    <form method="POST" action="dashboard_pelajar.php" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this rejected crew application?');">
                                                        <input type="hidden" name="action" value="delete_crew_app">
                                                        <input type="hidden" name="app_id" value="<?= htmlspecialchars($myApp['app_id']) ?>">
                                                        <button type="submit" class="btn btn-sm" style="border-radius: 999px; background: #ef4444; color: white; border: none; font-weight: 800; padding: 6px 14px; cursor: pointer;">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="event-details.php?id=<?= $myApp['id'] ?>" class="btn btn-primary btn-sm" style="border-radius: 999px;">Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB CONTENT: RECOMMENDED & SAVED -->
            <div id="tab-recommended" style="display: none;">
                <div class="dashboard-grid-2col">
                    <!-- Left: Saved/Bookmarked Events -->
                    <div class="dashboard-card-wrap">
                        <div class="dashboard-card-header">
                            <h2>My Saved Events</h2>
                        </div>
                        
                        <?php if (empty($savedEventsList)): ?>
                            <div style="text-align:center; padding: 40px 0;">
                                <i class="far fa-heart" style="font-size: 36px; color: var(--text-muted); margin-bottom: 12px;"></i>
                                <p style="color:var(--text-secondary); font-size:14px;">No bookmarked events yet. Save events by clicking the heart button on event pages.</p>
                            </div>
                        <?php else: ?>
                            <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
                                <?php foreach ($savedEventsList as $sav): ?>
                                    <div class="event-card">
                                        <div class="event-img-wrap" style="height: 120px;">
                                            <img src="<?= htmlspecialchars(getImagePath($sav['image'])) ?>" alt="Event" class="event-img">
                                        </div>
                                        <div class="event-card-body" style="padding: 14px;">
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px; gap: 4px;">
                                                <span class="event-category" style="font-size: 11px; margin-bottom: 0;"><?= htmlspecialchars($sav['category']) ?></span>
                                                <?php if (!empty($sav['penganjur_id'])): ?>
                                                    <a href="organizer-profile.php?id=<?= urlencode($sav['penganjur_id']) ?>" class="event-organizer" style="font-size: 11px; color: var(--accent-blue); text-decoration: none; font-weight: 700; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="View Organizer Profile"><?= htmlspecialchars($sav['organizer'] ?? '') ?></a>
                                                <?php elseif (!empty($sav['organizer'])): ?>
                                                    <span class="event-organizer" style="font-size: 11px; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($sav['organizer']) ?>"><?= htmlspecialchars($sav['organizer']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <h3 style="font-size: 15px; margin-bottom: 10px; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars($sav['title']) ?></h3>
                                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border); padding-top:8px;">
                                                <span style="font-weight:800; font-size:12px; color:var(--accent);">+<?= $sav['points'] ?> Pts</span>
                                                <a href="event-details.php?id=<?= $sav['id'] ?>" class="btn btn-primary btn-sm" style="border-radius:999px; font-size:11px; padding: 4px 12px;">Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right: Interests Recommended Feed -->
                    <div class="dashboard-card-wrap">
                        <div class="dashboard-card-header">
                            <h2>Recommended Based On Your Interests</h2>
                            <a href="recommended.php" style="font-size:13px; font-weight:700; color:var(--accent-blue);">Edit preferences</a>
                        </div>
                        
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <?php 
                            if (!empty($scoredEvents)):
                                // Show top 5 recommended events in the feed
                                $feedEvents = array_slice($scoredEvents, 0, 5);
                                foreach ($feedEvents as $evRow):
                            ?>
                                        <div style="display:flex; gap:12px; align-items:flex-start; border-bottom:1px solid var(--border); padding-bottom:12px; position:relative;">
                                            <img src="<?= htmlspecialchars(getImagePath(!empty($evRow['poster_url']) ? $evRow['poster_url'] : ($evRow['gambar'] ?? 'program1.jpg'))) ?>" alt="Img" style="width:60px; height:60px; object-fit:cover; border-radius:var(--radius-sm); margin-top: 4px;">
                                            <div style="flex:1;">
                                                <h4 style="font-size:13px; font-weight:800; line-height: 1.3; font-family:'Outfit'; margin-bottom: 4px;">
                                                    <a href="event-details.php?id=<?= $evRow['id'] ?>" style="color:var(--text-primary); text-decoration:none;"><?= htmlspecialchars($evRow['nama']) ?></a>
                                                </h4>
                                                
                                                <div style="font-size:11px; color:var(--text-secondary); display:flex; gap:8px; align-items:center; margin-bottom: 6px;">
                                                    <i class="far fa-calendar-alt"></i> <?= formatProgramDates($evRow['start_date'] ?? null, $evRow['end_date'] ?? null, $evRow['tarikh'] ?? null) ?>
                                                </div>
                                                
                                                <?php if (!empty($evRow['tags'])): ?>
                                                    <div style="display:flex; flex-direction:column; gap:4px;">
                                                        <?php foreach ($evRow['tags'] as $tag): ?>
                                                            <div style="font-size:10px; font-weight:700; color:<?= htmlspecialchars($tag['color']) ?>; display:flex; align-items:center; gap: 4px;">
                                                                <i class="fas <?= htmlspecialchars($tag['icon']) ?>"></i> <?= htmlspecialchars($tag['text']) ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <a href="event-details.php?id=<?= $evRow['id'] ?>" style="font-size:12px; font-weight:800; color:var(--accent-blue); white-space:nowrap; margin-top: 4px;">View <i class="fas fa-angle-right"></i></a>
                                        </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <p style="color:var(--text-secondary); font-size:13px; text-align:center; padding: 20px 0;">No tailored suggestions matching your interests are currently scheduled.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: POINTS & LEADERBOARD -->
            <div id="tab-points" style="display: none;">
                <div class="dashboard-grid-2col">
                    <!-- Left: Achievements & Roadmap -->
                    <div>
                        <!-- Level Progress Card -->
                        <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border: none; border-radius: var(--radius-md); box-shadow: var(--shadow-md); position: relative; overflow: hidden;">
                            <div style="position: absolute; right: -20px; top: -20px; width: 140px; height: 140px; background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%); pointer-events: none;"></div>
                            
                            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                                <div style="flex-shrink: 0; background: white; padding: 4px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                                    <?= ProgressionService::renderAvatarHTML(users()->findById($studentId), 'xl') ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; background: #6366f1; color: white; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.5px;">Level <?= $level ?></span>
                                        <span style="font-size: 13px; font-weight: 800; color: #cbd5e1;"><?= htmlspecialchars($levelName) ?> Badge</span>
                                    </div>
                                    <h2 style="font-size: 24px; font-family: 'Outfit'; font-weight: 900; margin: 6px 0 4px 0; color: white;"><?= htmlspecialchars($studentName) ?></h2>
                                    <div style="font-size: 13px; color: #a5b4fc; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-fire" style="color: #f97316;"></i> Streak: <span style="color: white; font-size: 14px; font-weight: 900;"><?= $streak ?></span> active month<?= $streak == 1 ? '' : 's' ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 16px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 8px; color: #94a3b8;">
                                    <span>XP Points Progress</span>
                                    <span><?= $currentPoints ?> Pts Total</span>
                                </div>
                                <div style="background: rgba(255,255,255,0.1); height: 10px; border-radius: 999px; overflow: hidden; margin-bottom: 8px;">
                                    <div style="width: <?= $progressPercent ?>%; height: 100%; background: linear-gradient(90deg, #6366f1 0%, #a855f7 100%);"></div>
                                </div>
                                <?php if ($progDetails['next_level']): ?>
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #94a3b8; font-weight: 700;">
                                        <span>Current Level</span>
                                        <span><?= $nextLevelPoints - $currentPoints ?> Pts to Level <?= $level + 1 ?> (<?= htmlspecialchars($progDetails['next_level']['name']) ?>)</span>
                                    </div>
                                <?php else: ?>
                                    <div style="font-size: 11px; color: #f59e0b; font-weight: 700; text-align: right;">
                                        <i class="fas fa-crown"></i> Maximum level achieved!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- System Notice: How It Works -->
                        <div class="dashboard-card-wrap" style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; margin-bottom: 24px; position: relative;">
                            <h3 style="font-size: 15px; font-weight: 800; color: #1e40af; margin-bottom: 12px; font-family: 'Outfit'; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-circle-info" style="font-size: 18px; color: #3b82f6;"></i> Level & Streak Guide
                            </h3>
                            <div style="font-size: 13px; color: #1e3a8a; line-height: 1.6; display: flex; flex-direction: column; gap: 8px;">
                                <p style="margin: 0;">Welcome to the UKMInvolve progression system! Points you earn represent your engagement and unlock platform privileges.</p>
                                <p style="margin: 0;">🔥 <strong>Monthly Streaks</strong>: Perform at least one point-earning activity (register/attend program, complete crew duty, or submit feedback) each calendar month. If you miss a month, your streak resets to 0.</p>
                                <p style="margin: 0;">🏆 <strong>Level Privileges</strong>:
                                    <br>&bull; <strong>Level 1 (Participant)</strong>: Default. Register for events, earn points. No crew roles.
                                    <br>&bull; <strong>Level 2 (Crew Member)</strong>: Unlocks crew applications.
                                    <br>&bull; <strong>Level 3 (MT)</strong>: Unlocks Majlis Tertinggi (MT) leadership crew applications.
                                    <br>&bull; <strong>Level 4 (UKM Elite)</strong>: Unlocks Diamond avatar frame, voucher redemption, and official e-Certificate.
                                </p>
                            </div>
                        </div>

                        <!-- Level / Badge Roadmap -->
                        <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px;">
                            <h2 style="font-size: 18px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 16px;">Level & Badge Roadmap</h2>
                            
                            <div style="display: flex; flex-direction: column; gap: 20px;">
                                <?php 
                                $badgeConfigs = [
                                    1 => ['icon' => 'fa-medal', 'color' => '#b45309', 'bg' => 'rgba(180, 83, 9, 0.1)', 'priv' => 'Participate in programs, submit feedback'],
                                    2 => ['icon' => 'fa-shield-halved', 'color' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.1)', 'priv' => 'Apply for crew/committee positions'],
                                    3 => ['icon' => 'fa-crown', 'color' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.1)', 'priv' => 'Apply for MT leadership positions (Pengarah, etc.)'],
                                    4 => ['icon' => 'fa-gem', 'color' => '#0284c7', 'bg' => 'rgba(2, 132, 199, 0.1)', 'priv' => 'Claim rewards, download e-Cert, Diamond frame']
                                ];
                                
                                foreach ($progDetails['levels_list'] as $lvlIndex => $lvl): 
                                    $lNum = (int)$lvl['level'];
                                    $lName = $lvl['name'];
                                    $lXP = (int)$lvl['xp'];
                                    $lProg = (int)($lvl['req_programs'] ?? 0);
                                    $lCrew = (int)($lvl['req_crew'] ?? 0);
                                    $lStreak = (int)($lvl['req_streak'] ?? 0);
                                    
                                    $unlocked = $level >= $lNum;
                                    $cfg = $badgeConfigs[$lNum] ?? ['icon' => 'fa-medal', 'color' => '#cbd5e1', 'bg' => '#f1f5f9', 'priv' => 'No benefits'];
                                    
                                    $borderColor = $unlocked ? $cfg['color'] : 'var(--border)';
                                    $opacity = $unlocked ? '1' : '0.6';
                                ?>
                                    <div style="border: 2px solid <?= $borderColor ?>; border-radius: var(--radius-md); padding: 16px; opacity: <?= $opacity ?>; background: <?= $unlocked ? 'var(--white)' : '#f8fafc' ?>; display: flex; gap: 16px; position: relative;">
                                        <!-- Badge icon -->
                                        <div style="width: 50px; height: 50px; border-radius: 12px; background: <?= $cfg['bg'] ?>; color: <?= $cfg['color'] ?>; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                                            <i class="fas <?= $cfg['icon'] ?>"></i>
                                        </div>
                                        
                                        <div style="flex: 1;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                                <div>
                                                    <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 4px 0; font-family:'Outfit'; color: var(--text-primary);">
                                                        Level <?= $lNum ?>: <?= htmlspecialchars($lName) ?>
                                                    </h3>
                                                    <span style="font-size: 11px; font-weight: 700; color: #64748b;">Privilege: <?= $cfg['priv'] ?></span>
                                                </div>
                                                <div>
                                                    <?php if ($unlocked): ?>
                                                        <span style="font-size: 11px; font-weight: 800; color: #10b981; background: #d1fae5; padding: 4px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-circle-check"></i> Unlocked</span>
                                                    <?php else: ?>
                                                        <span style="font-size: 11px; font-weight: 800; color: #64748b; background: #e2e8f0; padding: 4px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-lock"></i> Locked</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Requirements Checklist -->
                                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border); display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px;">
                                                <!-- Points requirement -->
                                                <div style="font-size: 12px; font-weight: 700; color: <?= $currentPoints >= $lXP ? '#10b981' : '#64748b' ?>;">
                                                    <i class="fas <?= $currentPoints >= $lXP ? 'fa-check-circle' : 'fa-circle' ?>"></i> <?= $lXP ?> Pts 
                                                    <span style="font-size: 10px; color: var(--text-muted); font-weight: 500;">(<?= $currentPoints ?>)</span>
                                                </div>
                                                
                                                <!-- Programs requirement -->
                                                <?php if ($lProg > 0): ?>
                                                    <div style="font-size: 12px; font-weight: 700; color: <?= $attendedEvents >= $lProg ? '#10b981' : '#64748b' ?>;">
                                                        <i class="fas <?= $attendedEvents >= $lProg ? 'fa-check-circle' : 'fa-circle' ?>"></i> <?= $lProg ?> Programs
                                                        <span style="font-size: 10px; color: var(--text-muted); font-weight: 500;">(<?= $attendedEvents ?>)</span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Crew requirement -->
                                                <?php if ($lCrew > 0): ?>
                                                    <div style="font-size: 12px; font-weight: 700; color: <?= $completedCrew >= $lCrew ? '#10b981' : '#64748b' ?>;">
                                                        <i class="fas <?= $completedCrew >= $lCrew ? 'fa-check-circle' : 'fa-circle' ?>"></i> <?= $lCrew ?> Crew Duties
                                                        <span style="font-size: 10px; color: var(--text-muted); font-weight: 500;">(<?= $completedCrew ?>)</span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Streak requirement -->
                                                <?php if ($lStreak > 0): ?>
                                                    <div style="font-size: 12px; font-weight: 700; color: <?= $streak >= $lStreak ? '#10b981' : '#64748b' ?>;">
                                                        <i class="fas <?= $streak >= $lStreak ? 'fa-check-circle' : 'fa-circle' ?>"></i> <?= $lStreak ?> Month Streak
                                                        <span style="font-size: 10px; color: var(--text-muted); font-weight: 500;">(<?= $streak ?>)</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Points Activity Log -->
                        <div class="dashboard-card-wrap">
                            <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin-bottom: 16px;">Points Activity Log</h2>
                            <?php if (empty($activityLog)): ?>
                                <p style="font-size: 13px; color: var(--text-secondary); text-align: center; padding: 20px 0;">No point activity recorded yet.</p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 0;">
                                    <?php foreach ($activityLog as $log): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border);">
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <strong style="font-size: 13px; color: var(--text-primary);"><?= htmlspecialchars($log['event']) ?></strong>
                                                <span style="font-size: 11px; color: var(--text-secondary);"><?= date('j M Y, g:i A', strtotime($log['date'])) ?> &bull; <?= htmlspecialchars($log['desc']) ?></span>
                                            </div>
                                            <?php if ($log['points'] >= 0): ?>
                                                <div style="font-weight: 900; font-size: 14px; color: #10b981; background: #d1fae5; padding: 4px 8px; border-radius: 4px;">
                                                    +<?= $log['points'] ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="font-weight: 900; font-size: 14px; color: #ef4444; background: #fee2e2; padding: 4px 8px; border-radius: 4px;">
                                                    <?= $log['points'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Rewards & Leaderboard -->
                    <div>
                        <!-- UKM Leaderboard -->
                        <div class="dashboard-card-wrap" style="margin-bottom: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin: 0;">UKM Leaderboard</h2>
                                <a href="leaderboard.php" style="font-size: 12px; font-weight: 800; color: var(--accent-blue); text-decoration: none;">View Top 50 <i class="fas fa-angle-right"></i></a>
                            </div>
                            
                            <div style="display:flex; flex-direction:column; gap:12px;">
                                <?php foreach ($leaderboard as $idx => $lu): 
                                    $isMe = $lu['id'] === $studentId;
                                ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:var(--radius-sm); border: 1px solid <?= $isMe ? 'var(--accent-blue); background:#eff6ff;' : 'var(--border); background:var(--white);' ?>; box-shadow: var(--shadow-sm);">
                                        <div style="display:flex; align-items:center; gap:10px; min-width: 0;">
                                            <span style="font-weight:900; font-size:12px; width:20px; text-align:center; color:var(--text-secondary);"><?= $idx + 1 ?></span>
                                            <div style="flex-shrink: 0;">
                                                <?= ProgressionService::renderAvatarHTML($lu, 'sm') ?>
                                            </div>
                                            <div style="min-width: 0;">
                                                <h4 style="font-size:13px; font-weight:800; font-family:'Outfit'; margin: 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($lu['nama']) ?></h4>
                                                <span style="font-size:10px; font-weight: 700; color:var(--text-secondary); display: flex; align-items: center; gap: 4px;">
                                                    Lvl <?= $lu['level'] ?? 1 ?> &bull; <i class="fas fa-fire" style="color: #f97316;"></i> <?= $lu['streak'] ?? 0 ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span style="font-weight:900; font-size:13px; color:var(--accent-blue); flex-shrink: 0;"><?= $lu['mata'] ?> Pts</span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($leaderboard)): ?>
                                    <p style="color:var(--text-secondary); font-size:13px; text-align:center; padding: 20px 0;">No leaderboard data available.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Voucher Rewards Catalog -->
                        <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px;">
                            <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin-bottom: 4px;">Voucher Catalog</h2>
                            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">Redeem vouchers using your co-curricular points (Level 4 UKM Elite Required).</p>
                            
                            <?php if ($level < 4): ?>
                                <div style="background: #f8fafc; border: 1px dashed var(--border); padding: 16px; border-radius: 8px; text-align: center; margin-bottom: 16px;">
                                    <p style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin: 0 0 4px 0;"><i class="fas fa-lock" style="margin-right: 6px;"></i> Rewards locked</p>
                                    <span style="font-size: 11px; color: var(--text-muted);">Reach Level 4 (UKM Elite) to redeem points for reward vouchers.</span>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($vouchersList)): ?>
                                <p style="font-size: 13px; color: var(--text-secondary); text-align: center; padding: 20px 0; border: 1px dashed var(--border); border-radius: 8px;">No vouchers available in the catalog yet.</p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <?php foreach ($vouchersList as $voucher): 
                                        $claimedByMe = ($voucher['claimed_by'] ?? null) === $studentId;
                                        $claimedByOthers = $voucher['is_claimed'] && !$claimedByMe;
                                    ?>
                                        <div style="border: 1px solid var(--border); padding: 14px; border-radius: var(--radius-sm); background: <?= $voucher['is_claimed'] ? '#f8fafc' : 'var(--white)' ?>; display: flex; justify-content: space-between; align-items: center; gap: 12px; opacity: <?= $claimedByOthers ? '0.5' : '1' ?>;">
                                            <div style="flex: 1; min-width: 0;">
                                                <h4 style="font-size: 14px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-primary); font-family:'Outfit'; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($voucher['title']) ?></h4>
                                                <?php if ($claimedByMe): ?>
                                                    <span style="font-size: 11px; font-weight: 800; color: #10b981; background: #d1fae5; padding: 2px 8px; border-radius: 4px; display: inline-block;">Claimed! Code: <strong><?= htmlspecialchars($voucher['code']) ?></strong></span>
                                                <?php else: ?>
                                                    <span style="font-size: 12px; font-weight: 800; color: var(--accent);"><?= $voucher['points_cost'] ?> Pts</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="flex-shrink: 0;">
                                                <?php if ($voucher['is_claimed']): ?>
                                                    <?php if ($claimedByMe): ?>
                                                        <button class="btn btn-sm" disabled style="background: #10b981; color: white; border: none; font-weight: 800; border-radius: 6px;">Claimed</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm" disabled style="background: #e2e8f0; color: #94a3b8; border: none; font-weight: 800; border-radius: 6px;">Out of Stock</button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <form method="POST" action="dashboard_pelajar.php" style="margin: 0;">
                                                        <input type="hidden" name="action" value="claim_voucher">
                                                        <input type="hidden" name="voucher_id" value="<?= $voucher['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary" 
                                                            <?= ($level < 4 || $currentPoints < (int)$voucher['points_cost']) ? 'disabled style="background:#cbd5e1; color:#94a3b8; border:none; cursor:not-allowed;"' : 'style="border-radius: 6px; font-weight:800;"' ?>
                                                            onclick="return confirm('Are you sure you want to claim this voucher for <?= $voucher['points_cost'] ?> points?');">
                                                            Claim
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Level 4 Digital e-Certificate Card -->
                        <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px; text-align: center; border: 1px solid var(--border);">
                            <div style="font-size: 40px; color: #ca8a04; margin-bottom: 12px;"><i class="fas fa-file-signature"></i></div>
                            <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin-bottom: 8px;">Official e-Certificate</h2>
                            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.5;">Level 4 (UKM Elite) students receive an official digital Certificate of Excellence recognizing active university program leadership and participation.</p>
                            
                            <?php if ($level >= 4): ?>
                                <a href="print-cert.php" target="_blank" class="btn btn-outline" style="border-color: #ca8a04; color: #ca8a04; font-weight: 800; border-radius: 999px; width: 100%; display: block; box-sizing: border-box; text-decoration: none; padding: 10px 0;"><i class="fas fa-print"></i> Download & Print e-Certificate</a>
                            <?php else: ?>
                                <div style="background: #f8fafc; border: 1px dashed var(--border); padding: 12px; border-radius: 8px; font-size: 12px; font-weight: 700; color: var(--text-muted);">
                                    <i class="fas fa-lock"></i> Locked. Reach Level 4 to unlock.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <style>
    /* Dashboard Split Layout */
    .dashboard-main-split {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 992px) {
        .dashboard-main-split {
            grid-template-columns: 1fr;
        }
    }

    /* Side-by-side Calendar & Countdown container */
    .calendar-countdown-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    @media (max-width: 768px) {
        .calendar-countdown-container {
            grid-template-columns: 1fr;
        }
    }

    /* Compact Calendar Styles */
    .calendar-container {
        width: 100%;
        border-radius: var(--radius-sm);
        overflow: hidden;
        background: var(--white);
    }
    .calendar-header-row {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        font-weight: 800;
        font-size: 10px;
        color: var(--text-secondary);
        padding: 6px 0;
    }
    .calendar-days-grid.compact {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        grid-auto-rows: 40px;
        gap: 2px;
    }
    .cal-day {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 4px;
        background: var(--bg-secondary);
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
        position: relative;
    }
    .cal-day:hover {
        background: #e2e8f0;
    }
    .cal-day.other-month {
        opacity: 0.3;
        pointer-events: none;
    }
    .cal-date-num {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-primary);
        z-index: 2;
    }
    .cal-day.today {
        background: #eff6ff;
        border: 1px solid var(--accent-blue);
    }
    .cal-day.today .cal-date-num {
        color: var(--accent-blue);
    }
    .cal-indicators {
        display: flex;
        gap: 2px;
        margin-top: 2px;
        flex-wrap: wrap;
        justify-content: center;
        padding: 0 2px;
    }
    .ind-dot {
        font-size: 14px;
        line-height: 10px;
    }
    .ind-registered { color: #3b82f6; }
    .ind-crew { color: #8b5cf6; font-size: 12px; margin-top: 1px; }
    .ind-available { color: #f59e0b; }

    /* Countdown */
    .countdown-boxes {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    .cd-box {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        width: 60px;
    }
    .cd-val {
        font-size: 20px;
        font-weight: 900;
        font-family: 'Outfit';
    }
    .cd-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        opacity: 0.8;
    }

    /* Event Details Modal */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .modal-overlay.show {
        opacity: 1;
    }
    .modal-content {
        background: var(--white);
        border-radius: var(--radius-lg);
        width: 90%;
        max-width: 500px;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        box-shadow: var(--shadow-lg);
        transform: translateY(20px);
        transition: transform 0.3s;
        overflow: hidden;
    }
    .modal-overlay.show .modal-content {
        transform: translateY(0);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
    }
    .modal-title {
        font-size: 16px;
        font-weight: 800;
        font-family: 'Outfit';
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 18px;
        color: var(--text-muted);
        cursor: pointer;
    }
    .modal-body {
        padding: 20px;
        overflow-y: auto;
    }
    </style>

    <!-- Event Details Modal HTML -->
    <div class="modal-overlay" id="eventModalOverlay" onclick="closeEventModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title" id="eventModalTitle">Events on Date</div>
                <button class="modal-close" onclick="closeEventModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- JS will populate -->
            </div>
        </div>
    </div>

    <script>
    function switchTab(evt, tabId) {
        // Hide all tab contents
        document.getElementById('tab-overview').style.display = 'none';
        document.getElementById('tab-my-events').style.display = 'none';
        document.getElementById('tab-my-crew').style.display = 'none';
        document.getElementById('tab-recommended').style.display = 'none';
        document.getElementById('tab-points').style.display = 'none';

        // Remove active class from all buttons
        const tabBtns = document.querySelectorAll('.tab-nav-btn');
        tabBtns.forEach(btn => btn.classList.remove('active'));

        // Show the active tab and add active class to button
        document.getElementById(tabId).style.display = 'block';
        evt.currentTarget.classList.add('active');
    }

    // ==========================================
    // CALENDAR & COUNTDOWN JS LOGIC
    // ==========================================
    const rawEvents = <?= $calendarEventsJson ?>;
    let currentDate = new Date();

    function renderCalendar() {
        const monthYearLabel = document.getElementById('calendarMonthYear');
        const grid = document.getElementById('calendarDaysGrid');
        
        if(!grid || !monthYearLabel) return;
        
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        monthYearLabel.textContent = `${monthNames[month]} ${year}`;
        
        // First day of month
        const firstDay = new Date(year, month, 1).getDay();
        // Days in month
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        // Days in prev month
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        
        let html = '';
        
        // Previous month filler days
        for(let i = 0; i < firstDay; i++) {
            const prevDate = daysInPrevMonth - firstDay + i + 1;
            html += `<div class="cal-day other-month"><span class="cal-date-num">${prevDate}</span></div>`;
        }
        
        const todayStr = new Date().toISOString().split('T')[0];
        
        // Current month days
        for(let i = 1; i <= daysInMonth; i++) {
            const dateStr = `${year}-${String(month+1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const isToday = dateStr === todayStr ? 'today' : '';
            
            let dayEvents = [];
            
            // Find events on this date
            rawEvents.forEach(ev => {
                if(dateStr >= ev.start_date && dateStr <= ev.end_date) {
                    dayEvents.push(ev);
                }
            });
            
            let indicatorsHtml = '';
            if (dayEvents.length > 0) {
                // Deduplicate indicators (show max 3 icons)
                let typesShown = new Set();
                dayEvents.forEach(ev => {
                    if (typesShown.size >= 3) return;
                    if (ev.indicator_type === 'crew' && !typesShown.has('crew')) {
                        indicatorsHtml += `<span class="ind-dot ind-crew">&#9733;</span>`;
                        typesShown.add('crew');
                    } else if (ev.indicator_type === 'registered' && !typesShown.has('registered')) {
                        indicatorsHtml += `<span class="ind-dot ind-registered">&bull;</span>`;
                        typesShown.add('registered');
                    } else if (ev.indicator_type === 'available' && !typesShown.has('available')) {
                        indicatorsHtml += `<span class="ind-dot ind-available">&bull;</span>`;
                        typesShown.add('available');
                    }
                });
            }
            
            let clickAttr = dayEvents.length > 0 ? `onclick='event.stopPropagation(); openEventModal(${JSON.stringify(dayEvents).replace(/'/g, "&apos;")}, "${dateStr}")'` : '';

            html += `
                <div class="cal-day ${isToday}" ${clickAttr}>
                    <span class="cal-date-num">${i}</span>
                    <div class="cal-indicators">
                        ${indicatorsHtml}
                    </div>
                </div>
            `;
        }
        
        // Next month filler days
        const totalCells = firstDay + daysInMonth;
        const remainingCells = 42 - totalCells; // Fixed 6 rows for consistency
        for(let i = 1; i <= remainingCells; i++) {
            html += `<div class="cal-day other-month"><span class="cal-date-num">${i}</span></div>`;
        }
        
        grid.innerHTML = html;
    }

    document.getElementById('prevMonthBtn')?.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    document.getElementById('nextMonthBtn')?.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    // Modal Logic
    function openEventModal(eventsList, dateStr) {
        const overlay = document.getElementById('eventModalOverlay');
        const body = document.getElementById('eventModalBody');
        const title = document.getElementById('eventModalTitle');
        
        // Format Date for Title
        const dateObj = new Date(dateStr);
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        title.textContent = "Events on " + dateObj.toLocaleDateString('en-GB', options);

        let bodyHtml = '<div style="display: flex; flex-direction: column; gap: 16px;">';

        eventsList.forEach(ev => {
            let roleBadgeHtml = '';
            if (ev.indicator_type === 'crew') {
                roleBadgeHtml = `<span style="background: #ede9fe; color: #8b5cf6; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-id-badge"></i> ${ev.role_name}</span>`;
            } else if (ev.indicator_type === 'registered') {
                roleBadgeHtml = `<span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-user"></i> Participant</span>`;
            } else {
                roleBadgeHtml = `<span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-globe"></i> Available</span>`;
            }

            let posterSrc = 'public/images/dummy/program1.jpg';
            if (ev.poster) {
                posterSrc = ev.poster.startsWith('http') ? ev.poster : (ev.poster.includes('/') ? ev.poster : 'uploads/posters/' + ev.poster);
            }

            bodyHtml += `
                <div style="display: flex; gap: 16px; border: 1px solid var(--border); padding: 12px; border-radius: var(--radius-sm);">
                    <img src="${posterSrc}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;" onerror="this.src='public/images/dummy/program1.jpg'">
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <h3 style="font-size: 14px; font-weight: 800; line-height: 1.3; font-family:'Outfit'; margin: 0;">${ev.title}</h3>
                        </div>
                        <div style="margin-bottom: 8px;">
                            ${roleBadgeHtml}
                        </div>
                        <div style="font-size: 11px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px;">
                            <div><i class="far fa-clock" style="width: 14px; text-align: center;"></i> ${ev.start_time.substring(0,5)} - ${ev.end_time.substring(0,5)}</div>
                            <div><i class="fas fa-map-marker-alt" style="width: 14px; text-align: center;"></i> ${ev.location}</div>
                        </div>
                        <a href="event-details.php?id=${ev.id}" class="btn btn-primary btn-sm" style="border-radius: 999px; padding: 4px 12px; font-size: 11px;">View Details</a>
                    </div>
                </div>
            `;
        });
        
        bodyHtml += '</div>';
        body.innerHTML = bodyHtml;
        
        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('show'), 10);
    }

    function closeEventModal() {
        const overlay = document.getElementById('eventModalOverlay');
        overlay.classList.remove('show');
        setTimeout(() => overlay.style.display = 'none', 300);
    }

    // Countdown Logic
    function initCountdown() {
        const cdWrap = document.getElementById('nearestCountdown');
        if(!cdWrap) return;
        
        const targetTime = parseInt(cdWrap.getAttribute('data-target')) * 1000;
        if(!targetTime) return;
        
        function updateCd() {
            const now = new Date().getTime();
            const diff = targetTime - now;
            
            if (diff <= 0) {
                document.getElementById('cdDays').textContent = '0';
                document.getElementById('cdHrs').textContent = '0';
                document.getElementById('cdMins').textContent = '0';
                return;
            }
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            document.getElementById('cdDays').textContent = days;
            document.getElementById('cdHrs').textContent = hours;
            document.getElementById('cdMins').textContent = mins;
        }
        
        updateCd();
        setInterval(updateCd, 60000); // update every minute
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderCalendar();
        initCountdown();

        // Handle tab selection via query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            const tabBtn = document.querySelector(`button[onclick*="${tabParam}"]`);
            if (tabBtn) {
                tabBtn.click();
            }
        }
    });
    </script>
</body>
</html>
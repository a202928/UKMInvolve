<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
$activePage = 'leaderboard';

if (!db()->isConfigured()) {
    die("Database not configured.");
}

// Check and archive previous months
LeaderboardService::checkAndArchivePreviousMonths();

$type = $_GET['type'] ?? 'student'; // 'student' or 'organizer'
$timeframe = $_GET['timeframe'] ?? 'This Month'; // 'This Month' or 'All Time'
$filterType = $_GET['filter_type'] ?? 'overall';
$filterValue = $_GET['filter_value'] ?? '';

// Reset filter value if type is overall
if ($filterType === 'overall') {
    $filterValue = '';
}

// Fetch Leaderboard Data
$students = [];
$organizers = [];

if ($type === 'student') {
    $students = LeaderboardService::getStudentLeaderboard($timeframe, $filterType, $filterValue, 50);
} else {
    $organizers = LeaderboardService::getOrganizerLeaderboard($timeframe, $filterType, $filterValue, 50);
}

// Fetch distinct values for filters based on type
$distinctQuery = '?select=fakulti,kolej&peranan=eq.pelajar';
if ($type === 'organizer') {
    $distinctQuery = '?select=fakulti,kolej&peranan=eq.penganjur';
}
$distinctRes = db()->select('users', $distinctQuery);
$faculties = [];
$colleges = [];
if ($distinctRes['ok'] && !empty($distinctRes['data'])) {
    foreach ($distinctRes['data'] as $u) {
        if (!empty($u['fakulti'])) $faculties[$u['fakulti']] = true;
        if (!empty($u['kolej'])) $colleges[$u['kolej']] = true;
    }
}
$faculties = array_keys($faculties);
$colleges = array_keys($colleges);
sort($faculties);
sort($colleges);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .leaderboard-container { max-width: 1000px; margin: 0 auto; }
        .tab-btn {
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 800;
            padding: 12px 24px;
            cursor: pointer;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            transition: var(--transition);
        }
        .tab-btn.active {
            color: var(--accent-blue);
            border-bottom-color: var(--accent-blue);
        }
        
        /* Podium styling */
        .podium-container { 
            display: flex; 
            align-items: flex-end; 
            justify-content: center; 
            gap: 20px; 
            margin: 40px 0 60px; 
            min-height: 320px; 
        }
        .podium-item { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            position: relative; 
            width: 170px; 
        }
        .podium-item.rank-1 { z-index: 3; }
        .podium-item.rank-2 { z-index: 2; margin-bottom: -20px; }
        .podium-item.rank-3 { z-index: 1; margin-bottom: -40px; }
        
        .podium-base { 
            width: 100%; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: flex-start; 
            padding-top: 50px; 
            border-radius: 12px 12px 0 0; 
            color: white; 
            text-align: center; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
        }
        .rank-1 .podium-base { height: 240px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding-top: 50px; }
        .rank-2 .podium-base { height: 200px; background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); }
        .rank-3 .podium-base { height: 180px; background: linear-gradient(135deg, #b45309 0%, #92400e 100%); }
        
        .podium-name { font-weight: 800; font-family: 'Outfit'; font-size: 14px; margin-bottom: 2px; padding: 0 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        .podium-pts { font-weight: 800; font-size: 18px; display: flex; align-items: center; gap: 4px; }
        
        .rank-badge { position: absolute; top: -15px; background: white; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 900; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 11; font-size: 14px; }
        .rank-1 .rank-badge { color: #d97706; top: -20px; width: 40px; height: 40px; font-size: 18px; }
        .rank-2 .rank-badge { color: #4b5563; }
        .rank-3 .rank-badge { color: #92400e; }

        /* General list styles */
        .list-container { background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden; border: 1px solid var(--border); }
        .list-row { display: flex; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border); transition: 0.2s; text-decoration: none; color: inherit; }
        .list-row:hover { background: var(--bg-secondary); }
        .list-row:last-child { border-bottom: none; }
        
        .list-rank { width: 40px; font-size: 16px; font-weight: 800; color: var(--text-muted); }
        .list-info { flex: 1; min-width: 0; }
        .list-name { font-weight: 800; font-family: 'Outfit'; font-size: 15px; color: var(--text-primary); margin-bottom: 4px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; }
        .list-meta { font-size: 12px; color: var(--text-secondary); display: flex; gap: 12px; }
        .list-pts { font-weight: 800; font-size: 16px; color: var(--accent-blue); display: flex; align-items: center; gap: 6px; }
        
        .filter-card { background: white; padding: 24px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border); margin-bottom: 32px; display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 180px; }
        .filter-group label { display: block; font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-select { width: 100%; height: 44px; padding: 0 16px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; background: var(--bg-secondary); outline: none; }
        .btn-filter-submit { height: 44px; padding: 0 24px; font-weight: 800; border-radius: var(--radius-sm); border: none; background: var(--accent-blue); color: white; cursor: pointer; transition: var(--transition); }
        .btn-filter-submit:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container leaderboard-container">
            
            <div class="dashboard-header-container" style="text-align: center; margin-bottom: 30px;">
                <h1 style="font-size: 36px; font-weight: 900; margin-bottom: 8px; color: var(--text-primary); font-family: 'Outfit';">Campus Leaderboard</h1>
                <p style="font-size: 16px; color: var(--text-secondary);">Celebrating active student participation and outstanding event organizers.</p>
            </div>

            <!-- Role Tabs -->
            <div style="display: flex; justify-content: center; gap: 12px; margin-bottom: 30px; border-bottom: 1px solid var(--border);">
                <a href="leaderboard.php?type=student&timeframe=<?= urlencode($timeframe) ?>&filter_type=<?= urlencode($filterType) ?>&filter_value=<?= urlencode($filterValue) ?>" class="tab-btn <?= $type === 'student' ? 'active' : '' ?>">
                    👥 Students Leaderboard
                </a>
                <a href="leaderboard.php?type=organizer&timeframe=<?= urlencode($timeframe) ?>&filter_type=<?= urlencode($filterType) ?>&filter_value=<?= urlencode($filterValue) ?>" class="tab-btn <?= $type === 'organizer' ? 'active' : '' ?>">
                    🏢 Organizers Leaderboard
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" class="filter-card" id="filterForm">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                
                <div class="filter-group">
                    <label>Timeframe</label>
                    <select name="timeframe" class="filter-select" onchange="this.form.submit()">
                        <option value="This Month" <?= $timeframe === 'This Month' ? 'selected' : '' ?>>This Month (Default)</option>
                        <option value="All Time" <?= $timeframe === 'All Time' ? 'selected' : '' ?>>All Time</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Filter By</label>
                    <select name="filter_type" class="filter-select" id="filterType" onchange="toggleFilterValue()">
                        <option value="overall" <?= $filterType === 'overall' ? 'selected' : '' ?>>Overall</option>
                        <option value="faculty" <?= $filterType === 'faculty' ? 'selected' : '' ?>><?= $type === 'organizer' ? 'Faculty Organizers' : 'Faculty Ranking' ?></option>
                        <option value="college" <?= $filterType === 'college' ? 'selected' : '' ?>><?= $type === 'organizer' ? 'College Organizers' : 'College Ranking' ?></option>
                    </select>
                </div>
                
                <div class="filter-group" id="filterValueContainer" style="<?= $filterType === 'overall' ? 'display: none;' : '' ?>">
                    <label id="filterValueLabel"><?= $filterType === 'faculty' ? 'Select Faculty' : 'Select College' ?></label>
                    <select name="filter_value" class="filter-select" id="filterValue" onchange="this.form.submit()">
                        <option value="">Select...</option>
                        <?php 
                        $options = $filterType === 'faculty' ? $faculties : ($filterType === 'college' ? $colleges : []);
                        foreach ($options as $opt): 
                        ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $filterValue === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <!-- ---------------------------------------------------- -->
            <!-- STUDENT LEADERBOARD RENDER -->
            <!-- ---------------------------------------------------- -->
            <?php if ($type === 'student'): ?>
                <?php if (empty($students)): ?>
                    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius-md); border: 1px dashed var(--border);">
                        <i class="fas fa-trophy" style="font-size: 48px; color: var(--border); margin-bottom: 16px; display: block;"></i>
                        <h3 style="font-size: 18px; font-weight: 800; color: var(--text-secondary);">No active students found matching criteria.</h3>
                    </div>
                <?php else: ?>
                    <?php 
                    $studentTitles = [
                        1 => ['title' => 'Student of the Month', 'emoji' => '🥇', 'color' => '#ca8a04', 'bg' => '#fef9c3', 'sub' => '1st Place Winner'],
                        2 => ['title' => 'Outstanding Participant', 'emoji' => '🥈', 'color' => '#475569', 'bg' => '#f1f5f9', 'sub' => '2nd Place Runner Up'],
                        3 => ['title' => 'Campus Achiever', 'emoji' => '🥉', 'color' => '#b45309', 'bg' => '#ffedd5', 'sub' => '3rd Place Rank']
                    ];
                    ?>
                    <!-- Top 3 Podium -->
                    <?php if (count($students) >= 3 && $students[0]['mata'] > 0): ?>
                        <div class="podium-container">
                            <!-- Rank 2 -->
                            <a href="public-profile.php?id=<?= $students[1]['id'] ?>" class="podium-item rank-2" style="text-decoration: none;">
                                <div class="rank-badge">🥈</div>
                                <div style="margin-bottom: 10px; display: flex; justify-content: center; align-items: center; overflow: visible;">
                                    <?= ProgressionService::renderAvatarHTML($students[1], 'lg') ?>
                                </div>
                                <div class="podium-base">
                                    <div class="podium-name" title="<?= htmlspecialchars($students[1]['nama']) ?>">
                                        <?= htmlspecialchars(explode(' ', trim($students[1]['nama']))[0]) ?>
                                    </div>
                                    <div style="font-size: 10px; font-weight: 800; background: rgba(255,255,255,0.25); color: white; padding: 1px 8px; border-radius: 4px; margin-bottom: 6px;">
                                        <?= $studentTitles[2]['title'] ?>
                                    </div>
                                    <div class="podium-pts" style="flex-direction: column; align-items: center;">
                                        <?php if ($students[1]['streak'] > 0): ?>
                                            <span style="font-size: 10px; color: #fef08a; display: block; margin-bottom: 2px;">🔥 <?= $students[1]['streak'] ?> Months</span>
                                        <?php endif; ?>
                                        <span style="font-size: 16px;"><?= $students[1]['mata'] ?> Pts</span>
                                    </div>
                                    <span style="font-size: 10px; color: rgba(255,255,255,0.85); font-weight: 800; margin-top: 4px;">Lvl <?= $students[1]['level'] ?> • <?= htmlspecialchars($students[1]['fakulti'] ?: 'General') ?></span>
                                    <span style="font-size: 9px; color: rgba(255,255,255,0.75); font-weight: 800; margin-top: 2px;"><?= htmlspecialchars($students[1]['kolej'] ?: 'UKM') ?></span>
                                </div>
                            </a>
                            
                            <!-- Rank 1 -->
                            <a href="public-profile.php?id=<?= $students[0]['id'] ?>" class="podium-item rank-1" style="text-decoration: none;">
                                <div class="rank-badge"><i class="fas fa-crown" style="color: #ca8a04;"></i></div>
                                <div style="margin-bottom: 12px; display: flex; justify-content: center; align-items: center; overflow: visible;">
                                    <?= ProgressionService::renderAvatarHTML($students[0], 'xl') ?>
                                </div>
                                <div class="podium-base">
                                    <div class="podium-name" title="<?= htmlspecialchars($students[0]['nama']) ?>">
                                        <?= htmlspecialchars(explode(' ', trim($students[0]['nama']))[0]) ?>
                                    </div>
                                    <div style="font-size: 11px; font-weight: 800; background: rgba(255,255,255,0.3); color: white; padding: 2px 10px; border-radius: 4px; margin-bottom: 6px;">
                                        👑 <?= $studentTitles[1]['title'] ?>
                                    </div>
                                    <div class="podium-pts" style="flex-direction: column; align-items: center;">
                                        <?php if ($students[0]['streak'] > 0): ?>
                                            <span style="font-size: 10px; color: #fef08a; display: block; margin-bottom: 2px;">🔥 <?= $students[0]['streak'] ?> Months</span>
                                        <?php endif; ?>
                                        <span style="font-size: 18px;"><?= $students[0]['mata'] ?> Pts</span>
                                    </div>
                                    <span style="font-size: 10px; color: rgba(255,255,255,0.85); font-weight: 800; margin-top: 4px;">Lvl <?= $students[0]['level'] ?> • <?= htmlspecialchars($students[0]['fakulti'] ?: 'General') ?></span>
                                    <span style="font-size: 9px; color: rgba(255,255,255,0.75); font-weight: 800; margin-top: 2px;"><?= htmlspecialchars($students[0]['kolej'] ?: 'UKM') ?></span>
                                </div>
                            </a>
                            
                            <!-- Rank 3 -->
                            <a href="public-profile.php?id=<?= $students[2]['id'] ?>" class="podium-item rank-3" style="text-decoration: none;">
                                <div class="rank-badge">🥉</div>
                                <div style="margin-bottom: 10px; display: flex; justify-content: center; align-items: center; overflow: visible;">
                                    <?= ProgressionService::renderAvatarHTML($students[2], 'lg') ?>
                                </div>
                                <div class="podium-base">
                                    <div class="podium-name" title="<?= htmlspecialchars($students[2]['nama']) ?>">
                                        <?= htmlspecialchars(explode(' ', trim($students[2]['nama']))[0]) ?>
                                    </div>
                                    <div style="font-size: 10px; font-weight: 800; background: rgba(255,255,255,0.25); color: white; padding: 1px 8px; border-radius: 4px; margin-bottom: 6px;">
                                        <?= $studentTitles[3]['title'] ?>
                                    </div>
                                    <div class="podium-pts" style="flex-direction: column; align-items: center;">
                                        <?php if ($students[2]['streak'] > 0): ?>
                                            <span style="font-size: 10px; color: #fef08a; display: block; margin-bottom: 2px;">🔥 <?= $students[2]['streak'] ?> Months</span>
                                        <?php endif; ?>
                                        <span style="font-size: 16px;"><?= $students[2]['mata'] ?> Pts</span>
                                    </div>
                                    <span style="font-size: 10px; color: rgba(255,255,255,0.85); font-weight: 800; margin-top: 4px;">Lvl <?= $students[2]['level'] ?> • <?= htmlspecialchars($students[2]['fakulti'] ?: 'General') ?></span>
                                    <span style="font-size: 9px; color: rgba(255,255,255,0.75); font-weight: 800; margin-top: 2px;"><?= htmlspecialchars($students[2]['kolej'] ?: 'UKM') ?></span>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Rankings list -->
                    <div class="list-container">
                        <?php 
                        $startIndex = (count($students) >= 3 && $students[0]['mata'] > 0) ? 3 : 0;
                        for ($i = 0; $i < count($students); $i++): 
                            $st = $students[$i];
                            $rank = $i + 1;
                            $tInfo = $studentTitles[$rank] ?? null;
                        ?>
                            <a href="public-profile.php?id=<?= $st['id'] ?>" class="list-row">
                                <div class="list-rank">#<?= $rank ?></div>
                                <div style="flex-shrink: 0; display: inline-flex; margin-right: 16px;">
                                    <?= ProgressionService::renderAvatarHTML($st, 'sm') ?>
                                </div>
                                
                                <div class="list-info">
                                    <div class="list-name" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <?= htmlspecialchars($st['nama']) ?>
                                        <span style="background: var(--bg-secondary); border: 1px solid var(--border); color: var(--text-secondary); font-size: 10px; padding: 2px 8px; border-radius: 4px; font-weight: 800;">Lvl <?= $st['level'] ?></span>
                                        <?php if ($tInfo): ?>
                                            <span style="font-size: 10px; font-weight: 800; color: <?= $tInfo['color'] ?>; background: <?= $tInfo['bg'] ?>; padding: 2px 8px; border-radius: 4px;"><?= $tInfo['title'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($st['streak'] > 0): ?>
                                            <span style="font-size: 12px; font-weight: 700; color: #ea580c; display: inline-flex; align-items: center; gap: 2px;" title="Active Streak">
                                                <i class="fas fa-fire"></i> <?= $st['streak'] ?> Months
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="list-meta">
                                        <span><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($st['fakulti'] ?: 'No Faculty') ?></span>
                                        <span><i class="fas fa-building"></i> <?= htmlspecialchars($st['kolej'] ?: 'No College') ?></span>
                                    </div>
                                </div>
                                
                                <div class="list-pts">
                                    <?= $st['mata'] ?> Pts
                                </div>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <!-- ---------------------------------------------------- -->
            <!-- ORGANIZER LEADERBOARD RENDER -->
            <!-- ---------------------------------------------------- -->
            <?php else: ?>
                <?php if (empty($organizers)): ?>
                    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius-md); border: 1px dashed var(--border);">
                        <i class="fas fa-building-user" style="font-size: 48px; color: var(--border); margin-bottom: 16px; display: block;"></i>
                        <h3 style="font-size: 18px; font-weight: 800; color: var(--text-secondary);">No active organizers found matching criteria.</h3>
                    </div>
                <?php else: ?>
                    <!-- Top 3 Podium for Organizers -->
                    <?php if (count($organizers) >= 3 && $organizers[0]['score'] > 0): 
                        $orgTitles = [
                            1 => ['title' => 'Organizer of the Month', 'emoji' => '🥇', 'color' => '#ca8a04'],
                            2 => ['title' => 'Outstanding Organizer', 'emoji' => '🥈', 'color' => '#475569'],
                            3 => ['title' => 'Excellent Organizer', 'emoji' => '🥉', 'color' => '#b45309']
                        ];
                    ?>
                        <div class="podium-container">
                            <!-- Rank 2 -->
                            <a href="organizer-profile.php?id=<?= $organizers[1]['id'] ?>" class="podium-item rank-2" style="text-decoration: none;">
                                <div class="rank-badge">🥈</div>
                                <div style="margin-bottom: 10px; width: 70px; height: 70px; border-radius: 50%; overflow: hidden; border: 3px solid #9ca3af; display: flex; align-items: center; justify-content: center; background: white; box-shadow: var(--shadow-md);">
                                    <?php if (!empty($organizers[1]['avatar_url']) && file_exists($organizers[1]['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($organizers[1]['avatar_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="font-weight: 800; font-size: 24px; color: var(--text-muted);"><?= strtoupper(substr($organizers[1]['name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="podium-base">
                                    <div class="podium-name" title="<?= htmlspecialchars($organizers[1]['name']) ?>">
                                        <?= htmlspecialchars(explode(' ', trim($organizers[1]['name']))[0]) ?>
                                    </div>
                                    <div style="font-size: 10px; font-weight: 800; background: rgba(255,255,255,0.25); color: white; padding: 1px 8px; border-radius: 4px; margin-bottom: 6px; white-space: nowrap; overflow: hidden; max-width: 90%;">
                                        <?= $orgTitles[2]['title'] ?>
                                    </div>
                                    <div class="podium-pts" style="font-size: 18px; color: white;">
                                        <?= $organizers[1]['events_conducted'] ?> Event<?= $organizers[1]['events_conducted'] == 1 ? '' : 's' ?>
                                    </div>
                                    <span style="font-size: 10px; color: rgba(255,255,255,0.85); font-weight: 800; margin-top: 4px;">★ <?= round($organizers[1]['average_rating'], 1) ?> Rating • <?= $organizers[1]['total_participants'] ?> Regs</span>
                                    <span style="font-size: 9px; color: rgba(255,255,255,0.75); font-weight: 800; margin-top: 2px;"><?= $organizers[1]['total_attendance'] ?> Attended • <?= htmlspecialchars($organizers[1]['fakulti'] ?: ($organizers[1]['kolej'] ?: 'UKM')) ?></span>
                                </div>
                            </a>
                            
                            <!-- Rank 1 -->
                            <a href="organizer-profile.php?id=<?= $organizers[0]['id'] ?>" class="podium-item rank-1" style="text-decoration: none;">
                                <div class="rank-badge"><i class="fas fa-award" style="color: #ca8a04;"></i></div>
                                <div style="margin-bottom: 12px; width: 90px; height: 90px; border-radius: 50%; overflow: hidden; border: 4px solid #fbbf24; display: flex; align-items: center; justify-content: center; background: white; box-shadow: var(--shadow-md);">
                                    <?php if (!empty($organizers[0]['avatar_url']) && file_exists($organizers[0]['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($organizers[0]['avatar_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="font-weight: 800; font-size: 32px; color: var(--text-muted);"><?= strtoupper(substr($organizers[0]['name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="podium-base">
                                    <div class="podium-name" title="<?= htmlspecialchars($organizers[0]['name']) ?>">
                                        <?= htmlspecialchars(explode(' ', trim($organizers[0]['name']))[0]) ?>
                                    </div>
                                    <div style="font-size: 10px; font-weight: 800; background: rgba(255,255,255,0.3); color: white; padding: 2px 10px; border-radius: 4px; margin-bottom: 6px; white-space: nowrap; overflow: hidden; max-width: 90%;">
                                        🏆 <?= $orgTitles[1]['title'] ?>
                                    </div>
                                    <div class="podium-pts" style="font-size: 20px; color: white;">
                                        <?= $organizers[0]['events_conducted'] ?> Event<?= $organizers[0]['events_conducted'] == 1 ? '' : 's' ?>
                                    </div>
                                    <span style="font-size: 10px; color: rgba(255,255,255,0.85); font-weight: 800; margin-top: 4px;">★ <?= round($organizers[0]['average_rating'], 1) ?> Rating • <?= $organizers[0]['total_participants'] ?> Regs</span>
                                    <span style="font-size: 9px; color: rgba(255,255,255,0.75); font-weight: 800; margin-top: 2px;"><?= $organizers[0]['total_attendance'] ?> Attended • <?= htmlspecialchars($organizers[0]['fakulti'] ?: ($organizers[0]['kolej'] ?: 'UKM')) ?></span>
                                </div>
                            </a>
                            
                            <!-- Rank 3 -->
                            <a href="organizer-profile.php?id=<?= $organizers[2]['id'] ?>" class="podium-item rank-3" style="text-decoration: none;">
                                <div class="rank-badge">🥉</div>
                                <div style="margin-bottom: 10px; width: 70px; height: 70px; border-radius: 50%; overflow: hidden; border: 3px solid #b45309; display: flex; align-items: center; justify-content: center; background: white; box-shadow: var(--shadow-md);">
                                    <?php if (!empty($organizers[2]['avatar_url']) && file_exists($organizers[2]['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($organizers[2]['avatar_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="font-weight: 800; font-size: 24px; color: var(--text-muted);"><?= strtoupper(substr($organizers[2]['name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="podium-base">
                                    <div class="podium-name" title="<?= htmlspecialchars($organizers[2]['name']) ?>">
                                        <?= htmlspecialchars(explode(' ', trim($organizers[2]['name']))[0]) ?>
                                    </div>
                                    <div style="font-size: 10px; font-weight: 800; background: rgba(255,255,255,0.25); color: white; padding: 1px 8px; border-radius: 4px; margin-bottom: 6px; white-space: nowrap; overflow: hidden; max-width: 90%;">
                                        <?= $orgTitles[3]['title'] ?>
                                    </div>
                                    <div class="podium-pts" style="font-size: 18px; color: white;">
                                        <?= $organizers[2]['events_conducted'] ?> Event<?= $organizers[2]['events_conducted'] == 1 ? '' : 's' ?>
                                    </div>
                                    <span style="font-size: 10px; color: rgba(255,255,255,0.85); font-weight: 800; margin-top: 4px;">★ <?= round($organizers[2]['average_rating'], 1) ?> Rating • <?= $organizers[2]['total_participants'] ?> Regs</span>
                                    <span style="font-size: 9px; color: rgba(255,255,255,0.75); font-weight: 800; margin-top: 2px;"><?= $organizers[2]['total_attendance'] ?> Attended • <?= htmlspecialchars($organizers[2]['fakulti'] ?: ($organizers[2]['kolej'] ?: 'UKM')) ?></span>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- List of Organizers -->
                    <div class="list-container">
                        <?php 
                        for ($i = 0; $i < count($organizers); $i++): 
                            $org = $organizers[$i];
                            $rank = $i + 1;
                            $tInfo = $orgTitles[$rank] ?? null;
                            $initial = strtoupper(substr($org['name'], 0, 1));
                        ?>
                            <a href="organizer-profile.php?id=<?= $org['id'] ?>" class="list-row">
                                <div class="list-rank">#<?= $rank ?></div>
                                <div style="flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%; overflow: hidden; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; background: var(--bg-secondary); margin-right: 16px;">
                                    <?php if (!empty($org['avatar_url']) && file_exists($org['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($org['avatar_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="font-weight: 800; font-size: 16px; color: var(--text-muted);"><?= $initial ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="list-info">
                                    <div class="list-name" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <?= htmlspecialchars($org['name']) ?>
                                        <?php if ($tInfo): ?>
                                            <span style="font-size: 10px; font-weight: 800; color: <?= $tInfo['color'] ?>; background: <?= $rank === 1 ? '#fef9c3' : ($rank === 2 ? '#f1f5f9' : '#ffedd5') ?>; padding: 2px 8px; border-radius: 4px;"><?= $tInfo['title'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="list-meta">
                                        <span><i class="fas fa-building"></i> <?= htmlspecialchars($org['fakulti'] ?: ($org['kolej'] ?: 'No Faculty/College')) ?></span>
                                        <span>🏆 <?= $org['events_conducted'] ?> Event<?= $org['events_conducted'] == 1 ? '' : 's' ?></span>
                                        <span>★ <?= round($org['average_rating'], 1) ?> Avg Rating</span>
                                    </div>
                                </div>
                                
                                <div class="list-pts" style="flex-direction: column; align-items: flex-end; text-align: right;">
                                    <span style="font-size: 15px; font-weight: 900; color: var(--accent-blue);"><?= $org['events_conducted'] ?> Event<?= $org['events_conducted'] == 1 ? '' : 's' ?></span>
                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 700; margin-top: 4px;"><?= $org['total_participants'] ?> Regs • <?= $org['total_attendance'] ?> Att</span>
                                </div>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>
    
    <script>
        const faculties = <?= json_encode($faculties) ?>;
        const colleges = <?= json_encode($colleges) ?>;
        
        function toggleFilterValue() {
            const type = document.getElementById('filterType').value;
            const container = document.getElementById('filterValueContainer');
            const label = document.getElementById('filterValueLabel');
            const select = document.getElementById('filterValue');
            const form = document.getElementById('filterForm');
            
            if (type === 'overall') {
                container.style.display = 'none';
                select.value = '';
                form.submit();
                return;
            }
            
            container.style.display = 'block';
            select.innerHTML = '<option value="">Select...</option>';
            
            let options = [];
            if (type === 'faculty') {
                label.textContent = 'Select Faculty';
                options = faculties;
            } else if (type === 'college') {
                label.textContent = 'Select College';
                options = colleges;
            }
            
            options.forEach(opt => {
                const el = document.createElement('option');
                el.value = opt;
                el.textContent = opt;
                select.appendChild(el);
            });
        }
    </script>
</body>
</html>

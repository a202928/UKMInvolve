<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
$activePage = '';

$profileId = $_GET['id'] ?? '';
if (!$profileId) {
    header('Location: index.php');
    exit();
}

if (!db()->isConfigured()) {
    die("Database not configured.");
}

$profileUser = users()->findById($profileId);
if (!$profileUser || ($profileUser['peranan'] ?? '') !== 'pelajar') {
    die("Student profile not found.");
}

$viewerId = $_SESSION['user_id'] ?? null;
$isSelf = $viewerId === $profileId;

// Fetch Badges
$earnedBadges = users()->getEarnedBadges($profileId);
$currentPoints = (int)($profileUser['mata'] ?? 0);

// Centered Progression System logic
$prog = ProgressionService::getStudentProgression($profileId, $profileUser);
$level = $prog['level'];
$levelName = $prog['level_name'];
$streak = $prog['streak'];
$nextLevel = $prog['next_level'];
$nextLevelName = $nextLevel ? $nextLevel['name'] : 'Max Level';
$nextLevelPoints = $nextLevel ? (int)$nextLevel['xp'] : $currentPoints;

// Fetch Registrations
$regsResult = registrations()->listByStudent($profileId);
$regs = array_filter($regsResult, fn($r) => ($r['status'] ?? '') !== 'Cancelled');
$upcomingEvents = [];
$pastEvents = [];
$profileEventIds = [];

$today = date('Y-m-d');
foreach ($regs as $reg) {
    $program = $reg['program'] ?? null;
    if ($program) {
        $profileEventIds[] = $program['id'];
        $ev = [
            'id' => $program['id'],
            'title' => $program['nama'],
            'date' => formatProgramDates($program['start_date'] ?? null, $program['end_date'] ?? null, $program['tarikh'] ?? null),
            'image' => !empty($program['poster_url']) ? $program['poster_url'] : 'program1.jpg',
            'category' => $program['kategori']['nama'] ?? 'Umum',
        ];
        
        $pDate = $program['start_date'] ?? $program['tarikh'] ?? '';
        if ($pDate >= $today) {
            $upcomingEvents[] = $ev;
        } else {
            $pastEvents[] = $ev;
        }
    }
}

// Fetch Crew Experience
$crewExperience = [];
$crewRes = db()->select('crew_applications', '?select=*,program(*),program_crew_positions(*)&pelajar_id=eq.' . rawurlencode($profileId));
if ($crewRes['ok'] && !empty($crewRes['data'])) {
    foreach ($crewRes['data'] as $app) {
        if (in_array($app['status'], ['Accepted', 'Completed']) && isset($app['program'])) {
            $crewExperience[] = [
                'id' => $app['program_id'],
                'title' => $app['program']['nama'] ?? '',
                'position' => $app['program_crew_positions']['nama_jawatan'] ?? 'Crew',
                'status' => $app['status'],
                'date' => formatProgramDates($app['program']['start_date'] ?? null, $app['program']['end_date'] ?? null, $app['program']['tarikh'] ?? null),
            ];
        }
    }
}
$mutualEvents = [];
if ($viewerId && !$isSelf && ($_SESSION['peranan'] ?? '') === 'pelajar') {
    $viewerRegs = registrations()->listByStudent($viewerId);
    $viewerEventIds = array_map(fn($r) => $r['program_id'], array_filter($viewerRegs, fn($r) => ($r['status'] ?? '') !== 'Cancelled'));
    
    $mutualIds = array_intersect($profileEventIds, $viewerEventIds);
    foreach ($regs as $reg) {
        if (in_array($reg['program_id'], $mutualIds)) {
            $program = $reg['program'];
            $mutualEvents[] = [
                'id' => $program['id'],
                'title' => $program['nama']
            ];
        }
    }
}

$avatarUrl = $profileUser['avatar_url'] ?? '';
$initial = strtoupper(substr($profileUser['nama'] ?? 'S', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profileUser['nama']) ?> | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container" style="max-width: 1000px;">
            
            <div style="display: flex; gap: 24px; align-items: flex-start;">
                
                <!-- LEFT COLUMN: Profile Identity -->
                <div style="flex: 0 0 320px; display: flex; flex-direction: column; gap: 24px;">
                    <div class="dashboard-card-wrap" style="text-align: center; padding: 32px 24px;">
                        <div style="margin: 0 auto 16px; display: flex; justify-content: center; align-items: center; overflow: visible;">
                            <?= ProgressionService::renderAvatarHTML($profileUser, 'xl') ?>
                        </div>
                        <h1 style="font-size: 22px; font-family: 'Outfit'; font-weight: 800; margin-bottom: 4px;"><?= htmlspecialchars($profileUser['nama']) ?></h1>
                        <p style="font-size: 14px; color: var(--accent-blue); font-weight: 700; margin-bottom: 12px;"><?= htmlspecialchars($levelName) ?></p>
                        
                        <div style="display: flex; flex-direction: column; gap: 8px; text-align: left; background: var(--bg-main); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div style="display: flex; gap: 8px; font-size: 13px;">
                                <i class="fas fa-graduation-cap" style="color: var(--text-muted); width: 16px;"></i>
                                <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($profileUser['fakulti'] ?: 'Faculty not specified') ?></span>
                            </div>
                            <div style="display: flex; gap: 8px; font-size: 13px;">
                                <i class="fas fa-building" style="color: var(--text-muted); width: 16px;"></i>
                                <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($profileUser['kolej'] ?: 'College not specified') ?></span>
                            </div>
                            <div style="display: flex; gap: 8px; font-size: 13px;">
                                <i class="fas fa-calendar-alt" style="color: var(--text-muted); width: 16px;"></i>
                                <span style="font-weight: 600; color: var(--text-primary);">Year <?= htmlspecialchars($profileUser['tahun_pengajian'] ?: '?') ?></span>
                            </div>
                        </div>

                        <?php if (!empty($profileUser['bio'])): ?>
                            <div style="margin-top: 16px; font-size: 13px; color: var(--text-secondary); line-height: 1.6; text-align: left;">
                                "<?= nl2br(htmlspecialchars($profileUser['bio'])) ?>"
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isSelf && !empty($mutualEvents)): ?>
                    <div class="dashboard-card-wrap">
                        <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 8px;"><i class="fas fa-handshake" style="color: #10b981; margin-right: 6px;"></i> Mutual Connections</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">You and <?= htmlspecialchars(explode(' ', trim($profileUser['nama']))[0]) ?> attended <strong><?= count($mutualEvents) ?></strong> events together.</p>
                        <ul style="font-size: 12px; color: var(--text-primary); padding-left: 20px; margin: 0; display: flex; flex-direction: column; gap: 6px;">
                            <?php foreach (array_slice($mutualEvents, 0, 3) as $me): ?>
                                <li><a href="event-details.php?id=<?= $me['id'] ?>" style="color: var(--accent-blue); font-weight: 600; text-decoration: none;"><?= htmlspecialchars($me['title']) ?></a></li>
                            <?php endforeach; ?>
                            <?php if (count($mutualEvents) > 3): ?>
                                <li style="color: var(--text-muted);">+ <?= count($mutualEvents) - 3 ?> more events</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT COLUMN: Stats & Activities -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 24px;">
                    <!-- Gamification Overview -->
                    <div class="dashboard-card-wrap">
                        <h2 style="font-size: 18px; margin-bottom: 16px;">Gamification Profile</h2>
                        
                        <div style="display: flex; gap: 24px; align-items: center; margin-bottom: 24px;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-bottom: 8px;">
                                    <span><?= $currentPoints ?> Points</span>
                                    <?php if ($nextLevelPoints !== $currentPoints): ?>
                                        <span style="color: var(--text-muted);">Next: <?= htmlspecialchars($nextLevelName) ?> (<?= $nextLevelPoints ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div style="background: var(--bg-secondary); height: 10px; border-radius: 999px; overflow: hidden;">
                                    <?php 
                                        $percent = $nextLevelPoints > 0 ? min(100, max(0, ($currentPoints / $nextLevelPoints) * 100)) : 100; 
                                    ?>
                                    <div style="width: <?= $percent ?>%; height: 100%; background: linear-gradient(90deg, #60a5fa 0%, #2563eb 100%);"></div>
                                </div>
                            </div>
                            <div style="background: #fef3c7; color: #d97706; padding: 12px 20px; border-radius: var(--radius-md); font-weight: 800; font-size: 20px; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-coins"></i> <?= $currentPoints ?>
                            </div>
                        </div>

                        <h3 style="font-size: 14px; font-weight: 800; margin-bottom: 12px;">Badges Earned</h3>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <?php if (empty($earnedBadges)): ?>
                                <p style="font-size: 13px; color: var(--text-secondary);">No badges earned yet.</p>
                            <?php else: ?>
                                <?php foreach ($earnedBadges as $eb): 
                                    $badgeColor = match($eb['nama']) {
                                        'Gold' => '#fbbf24', 'Silver' => '#9ca3af', 'Bronze' => '#b45309', default => '#3b82f6'
                                    };
                                ?>
                                    <div style="padding: 10px 16px; background: var(--bg-main); border: 1px solid var(--border); border-radius: 999px; display: flex; align-items: center; gap: 8px; box-shadow: var(--shadow-sm);">
                                        <i class="fas <?= htmlspecialchars($eb['gambar'] ?? 'fa-award') ?>" style="color: <?= $badgeColor ?>; font-size: 16px;"></i>
                                        <span style="font-size: 12px; font-weight: 700;"><?= htmlspecialchars($eb['nama']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="dashboard-card-wrap">
                        <h2 style="font-size: 18px; margin-bottom: 16px;">Upcoming Events</h2>
                        <?php if (empty($upcomingEvents)): ?>
                            <p style="font-size: 13px; color: var(--text-secondary); text-align: center; padding: 20px 0;">No upcoming events.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($upcomingEvents as $ev): ?>
                                    <div style="display: flex; gap: 16px; align-items: center; padding: 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-main);">
                                        <img src="<?= htmlspecialchars(getImagePath($ev['image'])) ?>" alt="Poster" style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius-sm);">
                                        <div style="flex: 1;">
                                            <a href="event-details.php?id=<?= $ev['id'] ?>" style="font-size: 14px; font-weight: 800; color: var(--text-primary); text-decoration: none; font-family: 'Outfit'; display: block; margin-bottom: 4px;"><?= htmlspecialchars($ev['title']) ?></a>
                                            <div style="font-size: 12px; color: var(--text-secondary);">
                                                <i class="far fa-calendar-alt"></i> <?= htmlspecialchars($ev['date']) ?> • <?= htmlspecialchars($ev['category']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Past Activities -->
                    <div class="dashboard-card-wrap">
                        <h2 style="font-size: 18px; margin-bottom: 16px;">Past Activities</h2>
                        <?php if (empty($pastEvents)): ?>
                            <p style="font-size: 13px; color: var(--text-secondary); text-align: center; padding: 20px 0;">No past activities.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach (array_slice($pastEvents, 0, 5) as $ev): ?>
                                    <div style="display: flex; gap: 16px; align-items: center; padding: 12px; border-bottom: 1px solid var(--border);">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <a href="event-details.php?id=<?= $ev['id'] ?>" style="font-size: 14px; font-weight: 800; color: var(--text-primary); text-decoration: none; display: block; margin-bottom: 2px;"><?= htmlspecialchars($ev['title']) ?></a>
                                            <div style="font-size: 12px; color: var(--text-secondary);">
                                                Attended on <?= htmlspecialchars($ev['date']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Crew Experience / Portfolio -->
                    <div class="dashboard-card-wrap">
                        <h2 style="font-size: 18px; margin-bottom: 16px;"><i class="fas fa-users-cog" style="color: var(--accent-blue); margin-right: 8px;"></i> Committee Experience</h2>
                        <?php if (empty($crewExperience)): ?>
                            <p style="font-size: 13px; color: var(--text-secondary); text-align: center; padding: 20px 0;">No committee experience to display.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($crewExperience as $crew): ?>
                                    <div style="display: flex; gap: 16px; align-items: flex-start; padding: 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-main);">
                                        <div style="width: 40px; height: 40px; border-radius: var(--radius-sm); background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                                            <i class="fas fa-id-badge"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="font-size: 14px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px;"><?= htmlspecialchars($crew['position']) ?></div>
                                            <a href="event-details.php?id=<?= $crew['id'] ?>" style="font-size: 13px; font-weight: 600; color: var(--accent-blue); text-decoration: none; display: block; margin-bottom: 4px;"><?= htmlspecialchars($crew['title']) ?></a>
                                            <div style="font-size: 12px; color: var(--text-secondary); display: flex; gap: 12px;">
                                                <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($crew['date']) ?></span>
                                                <span style="font-weight: 700; color: <?= $crew['status'] === 'Completed' ? '#059669' : '#d97706' ?>;"><?= $crew['status'] ?></span>
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
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

$organizerId = $_GET['id'] ?? '';
if (empty($organizerId)) {
    header('Location: index.php');
    exit();
}

$studentId = $_SESSION['user_id'] ?? '';
$isLoggedIn = !empty($studentId);

// 1. Fetch organizer profile details from DB
$org = null;
if (db()->isConfigured() && $organizerId) {
    $org = users()->findById($organizerId);
}

if (!$org || ($org['peranan'] ?? '') !== 'penganjur') {
    header('Location: index.php');
    exit();
}

// Parse social links if present
$socialLinks = [];
if (!empty($org['social_links'])) {
    if (is_array($org['social_links'])) {
        $socialLinks = $org['social_links'];
    } else {
        $socialLinks = json_decode($org['social_links'], true) ?: [];
    }
}

// 2. Fetch events created by this organizer
$orgEvents = [];
$upcomingEvents = [];
$pastEvents = [];
$totalParticipants = 0;
$todayDate = date('Y-m-d');

if (db()->isConfigured()) {
    $orgEvents = programs()->listByOrganizer($organizerId);
    
    foreach ($orgEvents as $evt) {
        $pId = $evt['id'];
        
        // Count active registrations directly from DB
        $regCount = 0;
        $regRes = db()->select('pendaftaran', "?program_id=eq.$pId&status=neq.Cancelled");
        if ($regRes['ok'] && is_array($regRes['data'])) {
            $regCount = count($regRes['data']);
        }
        
        $totalParticipants += $regCount;
        $evtDate = $evt['start_date'] ?? $evt['tarikh'] ?? '';
        
        $isCompleted = isProgramCompleted($evt);
        if ($isCompleted || (!empty($evtDate) && $evtDate < $todayDate)) {
            $pastEvents[] = $evt;
        } else {
            $upcomingEvents[] = $evt;
        }
    }
}

// Determine active sub-tab
$activeTab = strtolower(trim($_GET['tab'] ?? 'feed'));
if (!in_array($activeTab, ['feed', 'events', 'timeline', 'about'], true)) {
    $activeTab = 'feed';
}

// 4. Handle creating feed post (for owner)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    if ($isLoggedIn && $_SESSION['user_id'] === $organizerId && $_SESSION['role'] === 'penganjur') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $postType = trim($_POST['post_type'] ?? 'post');
        
        if ($title !== '' && $content !== '') {
            $ins = db()->insert('organizer_posts', [
                'organizer_id' => $organizerId,
                'title' => $title,
                'content' => $content,
                'post_type' => $postType
            ]);
            if ($ins['ok']) {
                $_SESSION['success_message'] = "Update posted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to publish post.";
            }
        } else {
            $_SESSION['error_message'] = "Title and content fields are required.";
        }
    }
    header("Location: organizer-profile.php?id=" . $organizerId . "&tab=feed");
    exit();
}

// 5. Construct unified Feed timeline
$feedItems = [];
if (db()->isConfigured()) {
    // A. Add general posts
    $postRes = db()->select('organizer_posts', '?organizer_id=eq.' . rawurlencode($organizerId) . '&order=created_at.desc');
    if ($postRes['ok'] && !empty($postRes['data'])) {
        foreach ($postRes['data'] as $post) {
            $feedItems[] = [
                'type' => 'post',
                'title' => $post['title'],
                'content' => $post['content'],
                'post_type' => $post['post_type'] ?? 'post',
                'timestamp' => strtotime($post['created_at']),
                'date_formatted' => date('j M Y, g:i A', strtotime($post['created_at'])),
                'icon' => $post['post_type'] === 'recruitment' ? 'fa-user-plus' : ($post['post_type'] === 'announcement' ? 'fa-bullhorn' : 'fa-rss'),
                'color' => $post['post_type'] === 'recruitment' ? '#10b981' : ($post['post_type'] === 'announcement' ? '#f59e0b' : '#3b82f6'),
                'badge' => $post['post_type'] === 'recruitment' ? 'Recruitment' : ($post['post_type'] === 'announcement' ? 'Announcement' : 'Feed Post')
            ];
        }
    }

    // B. Add event creation updates
    foreach ($orgEvents as $evt) {
        $createdAtStr = $evt['created_at'] ?? $evt['tarikh'] . ' 00:00:00';
        $feedItems[] = [
            'type' => 'event',
            'event_id' => $evt['id'],
            'title' => 'Created event: ' . ($evt['nama'] ?? ''),
            'content' => $evt['penerangan'] ?? '',
            'event_date' => formatProgramDates($evt['start_date'] ?? null, $evt['end_date'] ?? null, $evt['tarikh'] ?? null),
            'location' => !empty($evt['venue_name']) ? $evt['venue_name'] : ($evt['lokasi'] ?? ''),
            'image' => !empty($evt['poster_url']) ? $evt['poster_url'] : ($evt['gambar'] ?? 'program1.jpg'),
            'timestamp' => strtotime($createdAtStr),
            'date_formatted' => date('j M Y, g:i A', strtotime($createdAtStr)),
            'icon' => 'fa-calendar-plus',
            'color' => '#8b5cf6',
            'badge' => ''
        ];
    }

    // C. Add event announcements
    $annRes = db()->select('program_announcements', '?organizer_id=eq.' . rawurlencode($organizerId) . '&target_audience=eq.All&order=created_at.desc');
    if ($annRes['ok'] && !empty($annRes['data'])) {
        foreach ($annRes['data'] as $ann) {
            $eventTitle = 'an event';
            $pId = (int)$ann['program_id'];
            // Inline program lookup
            foreach ($orgEvents as $oe) {
                if ($oe['id'] == $pId) {
                    $eventTitle = $oe['nama'];
                    break;
                }
            }
            
            $feedItems[] = [
                'type' => 'announcement',
                'title' => 'Posted announcement for ' . $eventTitle,
                'content' => $ann['message'],
                'event_id' => $pId,
                'timestamp' => strtotime($ann['created_at']),
                'date_formatted' => date('j M Y, g:i A', strtotime($ann['created_at'])),
                'icon' => 'fa-bullhorn',
                'color' => '#ea580c',
                'badge' => 'Announcement'
            ];
        }
    }
}

// Sort unified feed items descending by timestamp
usort($feedItems, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

// 6. Build Timeline Accomplishments (events grouped by year)
$timelineYears = [];
foreach ($orgEvents as $evt) {
    $evtDate = $evt['start_date'] ?? $evt['tarikh'] ?? null;
    if ($evtDate) {
        $year = date('Y', strtotime($evtDate));
        $timelineYears[$year][] = [
            'id' => $evt['id'],
            'title' => $evt['nama'],
            'date' => formatProgramDates($evt['start_date'] ?? null, $evt['end_date'] ?? null, $evt['tarikh'] ?? null),
            'location' => !empty($evt['venue_name']) ? $evt['venue_name'] : ($evt['lokasi'] ?? ''),
            'image' => !empty($evt['poster_url']) ? $evt['poster_url'] : ($evt['gambar'] ?? 'program1.jpg')
        ];
    }
}
krsort($timelineYears);

$orgName = $org['nama'] ?? '';
$orgInitial = strtoupper(substr($orgName, 0, 1));
$orgAvatarUrl = $org['avatar_url'] ?? '';  // use directly, no cleanup
$orgHasAvatar = !empty($orgAvatarUrl);

$orgType = users()->getOrganizerType($org);
$orgTypeLabel = match ($orgType) {
    'faculty' => 'Faculty',
    'college' => 'College Representation',
    default => 'Student Organization'
};
$orgTypeBadgeClass = match ($orgType) {
    'faculty' => 'badge-faculty',
    'college' => 'badge-college',
    default => 'badge-organization'
};

$affiliationLabel = '';
if ($orgType === 'faculty' && !empty($org['fakulti'])) {
    $affiliationLabel = $org['fakulti'];
} elseif ($orgType === 'college' && !empty($org['kolej'])) {
    $affiliationLabel = $org['kolej'];
} elseif (!empty($org['organisasi'])) {
    $affiliationLabel = $org['organisasi'];
} else {
    $affiliationLabel = $orgTypeLabel;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($orgName) ?> Profile | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .profile-container {
            padding: 40px 0 80px;
        }
        /* LinkedIn style header/banner */
        .org-header-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            position: relative;
        }
        .org-cover-banner {
            height: 200px;
            background-image: url('ukm_background2.jpeg');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .org-cover-decorations {
            position: absolute;
            inset: 0;
            opacity: 0.15;
            background-image: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.4) 1px, transparent 1px),
                              radial-gradient(circle at 75% 60%, rgba(255,255,255,0.4) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .org-header-body {
            padding: 24px 32px 32px;
            position: relative;
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .org-profile-logo-wrap {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-md);
            background: var(--white);
            border: 4px solid var(--white);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-top: -80px;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .org-profile-logo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .org-profile-logo-fallback {
            font-size: 48px;
            font-weight: 800;
            color: var(--accent-blue);
            text-transform: uppercase;
        }
        .org-header-details {
            flex: 1;
            margin-top: 10px;
        }
        .org-header-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
        }
        .org-header-title-row h1 {
            font-size: 26px;
            font-weight: 800;
            font-family: 'Outfit';
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .org-stats-row {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .org-stat-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .org-stat-val {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-primary);
        }
        .org-stat-lbl {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }
        
        /* Layout Grid */
        .org-profile-feed-container {
            max-width: 800px;
            margin: 30px auto 0;
        }
        @media (max-width: 992px) {
            .org-header-body {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .org-profile-logo-wrap {
                margin-top: -70px;
            }
            .org-header-title-row {
                flex-direction: column;
                align-items: center;
            }
            .org-stats-row {
                justify-content: center;
            }
        }

        /* Timeline styles */
        .timeline-container {
            position: relative;
            padding-left: 24px;
            margin-top: 10px;
        }
        .timeline-container::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 7px;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }
        .timeline-year-group {
            margin-bottom: 30px;
            position: relative;
        }
        .timeline-year-node {
            position: absolute;
            left: -24px;
            top: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--accent-blue);
            border: 4px solid var(--white);
            box-shadow: 0 0 0 2px var(--accent-blue);
            z-index: 5;
        }
        .timeline-year-title {
            font-size: 20px;
            font-weight: 800;
            font-family: 'Outfit';
            color: var(--accent-blue);
            margin-bottom: 16px;
            line-height: 1;
        }
        .timeline-event-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px;
            display: flex;
            gap: 16px;
            margin-bottom: 12px;
            transition: var(--transition);
        }
        .timeline-event-card:hover {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            border-color: var(--accent-blue);
        }

        /* Feed Post styles */
        .feed-post-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            position: relative;
        }
        .feed-post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .feed-post-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .feed-post-title {
            font-weight: 800;
            font-size: 15px;
            color: var(--text-primary);
        }
        .feed-post-date {
            font-size: 11px;
            color: var(--text-secondary);
        }
        .feed-post-badge {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: auto;
        }
        .feed-post-body {
            font-size: 14px;
            color: var(--text-primary);
            line-height: 1.6;
            white-space: pre-wrap;
        }

        /* Sidebar widget */
        .sidebar-panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .sidebar-panel h3 {
            font-size: 16px;
            font-weight: 800;
            font-family: 'Outfit';
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="container profile-container">
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

        <!-- LINKEDIN ORGANIZER HEADER CARD -->
        <div class="org-header-card">
            <div class="org-cover-banner">
                <div class="org-cover-decorations"></div>
            </div>
            
            <div class="org-header-body">
                <div class="org-profile-logo-wrap">
                    <?php if ($orgHasAvatar): ?>
                        <img src="<?= htmlspecialchars($orgAvatarUrl) ?>" alt="Logo" class="org-profile-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                        <span class="org-profile-logo-fallback" style="display: none;"><?= $orgInitial ?></span>
                    <?php else: ?>
                        <span class="org-profile-logo-fallback"><?= $orgInitial ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="org-header-details">
                    <div class="org-header-title-row">
                        <div style="flex: 1; min-width: 0;">
                            <h1 style="margin-bottom: 8px;"><?= htmlspecialchars($orgName) ?></h1>
                            <span class="org-card-type-badge <?= $orgTypeBadgeClass ?>" style="display: inline-block; margin-bottom: 12px;"><?= htmlspecialchars($affiliationLabel) ?></span>
                            <div class="org-about-intro" style="font-size: 14px; color: var(--text-secondary); line-height: 1.6; max-width: 700px; margin-top: 8px;">
                                <?= htmlspecialchars($org['bio'] ?: 'No biography or description details provided by the organization.') ?>
                            </div>
                            
                            <!-- Contact & Social Details -->
                            <div class="org-contact-social-row" style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 16px; font-size: 13px; color: var(--text-secondary); align-items: center;">
                                <?php if (!empty($org['emel'])): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="far fa-envelope" style="color: var(--accent-blue);"></i>
                                        <a href="mailto:<?= htmlspecialchars($org['emel']) ?>" style="color: inherit; text-decoration: none; font-weight: 500; transition: var(--transition);" onmouseover="this.style.color='var(--accent-blue)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($org['emel']) ?></a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($org['no_telefon'])): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-phone-alt" style="color: var(--accent-blue);"></i>
                                        <a href="tel:<?= htmlspecialchars($org['no_telefon']) ?>" style="color: inherit; text-decoration: none; font-weight: 500; transition: var(--transition);" onmouseover="this.style.color='var(--accent-blue)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($org['no_telefon']) ?></a>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($socialLinks['facebook']) || !empty($socialLinks['instagram']) || !empty($socialLinks['linkedin'])): ?>
                                    <?php if (!empty($org['emel']) || !empty($org['no_telefon'])): ?>
                                        <span style="color: var(--border); font-weight: 300;">|</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($socialLinks['facebook'])): ?>
                                        <a href="<?= htmlspecialchars($socialLinks['facebook']) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; color: inherit; text-decoration: none; font-weight: 600; transition: var(--transition);" onmouseover="this.style.color='#1877f2'" onmouseout="this.style.color='inherit'">
                                            <i class="fab fa-facebook" style="font-size: 15px;"></i> Facebook
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($socialLinks['instagram'])): ?>
                                        <a href="<?= htmlspecialchars($socialLinks['instagram']) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; color: inherit; text-decoration: none; font-weight: 600; transition: var(--transition);" onmouseover="this.style.color='#e1306c'" onmouseout="this.style.color='inherit'">
                                            <i class="fab fa-instagram" style="font-size: 15px;"></i> Instagram
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($socialLinks['linkedin'])): ?>
                                        <a href="<?= htmlspecialchars($socialLinks['linkedin']) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; color: inherit; text-decoration: none; font-weight: 600; transition: var(--transition);" onmouseover="this.style.color='#0077b5'" onmouseout="this.style.color='inherit'">
                                            <i class="fab fa-linkedin" style="font-size: 15px;"></i> LinkedIn
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; align-items: center; align-self: flex-start;">
                            <?php if ($isLoggedIn && $_SESSION['user_id'] === $organizerId): ?>
                                <a href="profile.php" class="btn btn-outline" style="border-radius: 999px;"><i class="fas fa-user-gear"></i> Edit Profile</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="org-stats-row">
                        <div class="org-stat-item">
                            <span class="org-stat-val"><?= count($orgEvents) ?></span>
                            <span class="org-stat-lbl">Events</span>
                        </div>
                        <div class="org-stat-item">
                            <span class="org-stat-val"><?= $totalParticipants ?></span>
                            <span class="org-stat-lbl">Participants</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- CENTERED FEED CONTENT -->
        <div class="org-profile-feed-container">
            
            <!-- POST CREATOR (OWNER ONLY) -->
            <?php if ($isLoggedIn && $_SESSION['user_id'] === $organizerId): ?>
                <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px;">
                    <h3 style="font-size:16px; font-weight:800; margin-bottom:12px;"><i class="fas fa-edit" style="color:var(--accent-blue); margin-right:6px;"></i> Share an Update</h3>
                    <form action="organizer-profile.php?id=<?= $organizerId ?>" method="POST">
                        <input type="hidden" name="action" value="create_post">
                        <div style="margin-bottom: 12px;">
                            <input type="text" name="title" placeholder="Post Title (e.g., Volunteer Recruitment)" required class="form-input-profile" style="border-radius:var(--radius-sm);">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <textarea name="content" placeholder="Share announcement, recruitment details or updates..." required class="form-textarea-profile" style="min-height: 80px;"></textarea>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:12px; font-weight:700; color:var(--text-secondary);">Post Type:</span>
                                <select name="post_type" class="form-select-profile" style="padding:4px 10px; font-size:12px; border-radius:4px; width:auto; height:auto;">
                                    <option value="post">General Post</option>
                                    <option value="announcement">Announcement</option>
                                    <option value="recruitment">Recruitment</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" style="border-radius:999px; font-weight:800; padding:6px 20px;">Publish Post</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- TIMELINE TIMELINE FEED -->
            <?php if (empty($feedItems)): ?>
                <div style="text-align: center; padding: 60px 40px; border: 1px dashed var(--border); border-radius: var(--radius-lg); background: var(--white);">
                    <i class="far fa-comments" style="font-size: 36px; color: var(--text-muted); margin-bottom: 12px;"></i>
                    <p style="color: var(--text-secondary); font-size: 14px;">No posts or activity updates from this organization yet.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <?php foreach ($feedItems as $item): ?>
                        <div class="feed-post-card">
                            <div class="feed-post-header">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $item['color'] ?>; color: white; display: flex; align-items: center; justify-content: center; font-size: 13px;">
                                    <i class="fas <?= $item['icon'] ?>"></i>
                                </div>
                                <div class="feed-post-meta">
                                    <div class="feed-post-title"><?= htmlspecialchars($item['title']) ?></div>
                                    <div class="feed-post-date"><?= $item['date_formatted'] ?></div>
                                </div>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="feed-post-badge" style="background: <?= $item['color'] ?>20; color: <?= $item['color'] ?>;"><?= $item['badge'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="feed-post-body"><?= htmlspecialchars($item['content']) ?></div>
                            
                            <!-- Event attachment block if event -->
                            <?php if ($item['type'] === 'event'): ?>
                                <div style="display: flex; gap: 14px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; margin-top: 14px; background: var(--bg-secondary);">
                                    <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px;">
                                    <div>
                                        <h4 style="font-size:13px; font-weight:800; margin-bottom:4px; font-family:'Outfit';"><?= htmlspecialchars(substr($item['title'], 14)) ?></h4>
                                        <div style="font-size:11px; color:var(--text-secondary); display:flex; flex-direction:column; gap:2px; margin-bottom: 6px;">
                                            <div><i class="far fa-calendar-alt"></i> <?= $item['event_date'] ?></div>
                                            <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['location']) ?></div>
                                        </div>
                                        <a href="event-details.php?id=<?= $item['event_id'] ?>" class="btn btn-primary btn-sm" style="font-size:10px; border-radius: 999px; padding: 3px 12px;">View Details</a>
                                    </div>
                                </div>
                            <?php elseif ($item['type'] === 'announcement' && !empty($item['event_id'])): ?>
                                <div style="margin-top: 12px; font-size:12px; border-top: 1px dashed var(--border); padding-top:8px;">
                                    <a href="event-details.php?id=<?= $item['event_id'] ?>" style="color: var(--accent-blue); font-weight: 700; text-decoration: none;">View Event Details <i class="fas fa-angle-right" style="font-size:10px;"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

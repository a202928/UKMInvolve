<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

$programId = (int)($_GET['id'] ?? 0);
$event = null;
$row = null;

if (db()->isConfigured() && $programId > 0) {
    $row = programs()->findById($programId);
    if ($row) {
        $event = programs()->toStudentDetail($row);
        $crewPositions = programs()->getCrewPositions($programId);
        $crewStats = programs()->getCrewPositionStats($programId);
    }
}

if (!$event || !$row) {
    header('Location: events.php');
    exit();
}

$studentId = $_SESSION['user_id'] ?? '';
$isLoggedIn = !empty($studentId);
$showSavedToast = false;

// Action queries for joining (saving is now AJAX)
$action = $_GET['action'] ?? '';

if ($action === 'join') {
    if (!$isLoggedIn) {
        header('Location: login.php?redirect=' . urlencode('event-details.php?id=' . $programId . '&action=join'));
        exit();
    } else {
        header('Location: daftar-program-form.php?id=' . $programId);
        exit();
    }
}

if (isset($_GET['saved']) && $_GET['saved'] == 1) {
    $showSavedToast = true;
}

// Check if student is already registered as participant
$isRegistered = false;
$hasCrewApplication = false;
$isEligible = true;

if ($isLoggedIn && db()->isConfigured()) {
    $duplicate = registrations()->findDuplicate($studentId, $programId);
    if ($duplicate) {
        $isRegistered = true;
    }
    
    // Check for existing crew application
    $existingCrew = db()->select('crew_applications', '?program_id=eq.' . $programId . '&pelajar_id=eq.' . rawurlencode($studentId));
    if ($existingCrew['ok'] && !empty($existingCrew['data'])) {
        $hasCrewApplication = true;
    }
    
    // Check target audience
    $userRes = db()->select('users', '?select=fakulti,kolej&id=eq.' . rawurlencode($studentId) . '&limit=1');
    if ($userRes['ok'] && !empty($userRes['data'])) {
        $uFakulti = $userRes['data'][0]['fakulti'] ?? '';
        $uKolej = $userRes['data'][0]['kolej'] ?? '';
        
        $audienceRaw = $event['target_audience'] ?? ['ALL'];
        $audience = is_string($audienceRaw) ? json_decode($audienceRaw, true) : $audienceRaw;
        if (!is_array($audience)) $audience = ['ALL'];
        
        if (!in_array('ALL', $audience)) {
            if (!in_array($uFakulti, $audience) && !in_array($uKolej, $audience)) {
                $isEligible = false;
            }
        }
    }
}

$seatsLeft = max(0, $event['capacity'] - $event['participants']);
$isFull = $seatsLeft <= 0;
$pointsAwarded = (int)($row['mata'] ?? 100);
$isSaved = false;
if ($isLoggedIn && db()->isConfigured()) {
    $isSaved = savedEvents()->isSaved($studentId, $programId);
}

// Retrieve save count and share count for statistics
$savedCount = 0;
if (db()->isConfigured()) {
    $saveCountRes = db()->select('saved_events', '?program_id=eq.' . $programId);
    if ($saveCountRes['ok'] && is_array($saveCountRes['data'])) {
        $savedCount = count($saveCountRes['data']);
    }
}
$sharesCount = (int)($row['shares_count'] ?? 0);
$eventUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Deadline check
$isPastDeadline = false;
if (!empty($event['deadline_date'])) {
    $deadlineDateTime = $event['deadline_date'] . ' ' . ($event['deadline_time'] ?: '23:59:00');
    if (strtotime($deadlineDateTime) < time()) {
        $isPastDeadline = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- OpenGraph Rich Share Preview Metadata -->
    <meta property="og:title" content="<?= htmlspecialchars($event['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(mb_strimwidth(strip_tags($event['description']), 0, 160, "...")) ?>">
    <meta property="og:image" content="<?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . getImagePath($event['image'])) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($eventUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="UKMInvolve">

    <!-- Custom Sharing Modal Styles -->
    <style>
        .share-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .share-modal {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 30px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            border: 1px solid var(--border);
        }
        .share-modal h3 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .share-modal-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
        }
        .share-modal-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary) !important;
        }
        .share-modal-item:hover {
            background: var(--bg-secondary);
            border-color: var(--accent-blue);
            transform: translateY(-2px);
        }
        .share-modal-item i {
            font-size: 24px;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
    <div class="container" style="margin-top: 20px;">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-banner alert-banner-success" style="margin-bottom: 0;">
                <i class="fas fa-check-circle" style="color: #10b981;"></i>
                <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert-banner alert-banner-error" style="margin-bottom: 0;">
                <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
                <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- EVENT HERO SECTION -->
    <section class="event-details-hero">
        <div class="container" style="margin-bottom: 20px;">
            <a href="javascript:history.back()" style="display:inline-flex; align-items:center; gap:8px; color:var(--accent-blue); text-decoration:none; font-weight:700; font-size:14px; background:var(--white); padding:8px 16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); transition:var(--transition);"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="container event-details-hero-container">
            <div class="event-details-hero-left">
                <div class="event-details-tags">
                    <span class="tag tag-category">Category: <?= htmlspecialchars($event['category']) ?></span>
                    <span class="tag tag-registration">Role: <?= htmlspecialchars($event['jenis_pendaftaran']) ?></span>
                </div>
                <h1><?= htmlspecialchars($event['title']) ?></h1>
                
                <div class="event-details-meta">
                    <div class="event-details-meta-item">
                        <div class="event-details-meta-icon"><i class="far fa-calendar-alt"></i></div>
                        <div class="event-details-meta-text">
                            <div>Date</div>
                            <div><?= htmlspecialchars($event['date']) ?></div>
                        </div>
                    </div>
                    <div class="event-details-meta-item">
                        <div class="event-details-meta-icon"><i class="far fa-clock"></i></div>
                        <div class="event-details-meta-text">
                            <div>Time</div>
                            <div><?= htmlspecialchars($event['time']) ?></div>
                        </div>
                    </div>
                    <div class="event-details-meta-item">
                        <div class="event-details-meta-icon">
                            <?php if (($event['venue_type'] ?? 'physical') === 'online'): ?>
                                <i class="fas fa-laptop"></i>
                            <?php else: ?>
                                <i class="fas fa-map-marker-alt"></i>
                            <?php endif; ?>
                        </div>
                        <div class="event-details-meta-text">
                            <div>Venue</div>
                            <div>
                                <?php if (($event['venue_type'] ?? 'physical') === 'online'): ?>
                                    Online Event (<?= htmlspecialchars(!empty($event['platform']) ? $event['platform'] : 'TBA') ?>)
                                <?php else: ?>
                                    <?= htmlspecialchars($event['location']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="event-details-meta-item">
                        <div class="event-details-meta-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="event-details-meta-text">
                            <div>Organizer</div>
                            <div>
                                <?php if (!empty($event['penganjur_id'])): ?>
                                    <a href="organizer-profile.php?id=<?= urlencode($event['penganjur_id']) ?>" style="color: var(--accent-blue); text-decoration: none; font-weight: 700;" title="View Organizer Profile"><?= htmlspecialchars($event['organizer']) ?></a>
                                <?php else: ?>
                                    <?= htmlspecialchars($event['organizer']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="event-details-poster-wrap">
                <img src="<?= htmlspecialchars(getImagePath($event['image'])) ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="event-details-poster">
            </div>
        </div>
    </section>

    <!-- EVENT BODY & SIDEBAR -->
    <section class="container event-details-body">
        <div class="event-details-grid">
            <div>
                <!-- Description Panel -->
                <div class="event-details-panel">
                    <h2>Event Description</h2>
                    <p><?= htmlspecialchars($event['description'] ?: 'No description provided for this activity.') ?></p>
                </div>

                <!-- Venue & Access Panel -->
                <div class="event-details-panel">
                    <h2>Venue & Access</h2>
                    
                    <?php if (($event['venue_type'] ?? 'physical') === 'online'): ?>
                        <!-- ONLINE EVENT ACCESS -->
                        <div style="display: flex; align-items: flex-start; gap: 16px; padding: 16px; background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.1); border-radius: var(--radius-sm);">
                            <div style="font-size: 28px; color: var(--accent-blue); padding-top: 4px;">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 6px; color: var(--text-primary);">Online Event Access</h3>
                                <p style="font-size: 14px; color: var(--text-secondary); margin: 0; white-space: normal;">
                                    <strong>Platform:</strong> <?= htmlspecialchars(!empty($event['platform']) ? $event['platform'] : 'Online') ?><br>
                                    <strong>Meeting Link:</strong> 
                                    <?php if (!empty($event['meeting_link']) && strtolower($event['meeting_link']) !== 'tba'): ?>
                                        <a href="<?= htmlspecialchars($event['meeting_link']) ?>" target="_blank" style="color: var(--accent-blue); font-weight: 700; text-decoration: underline;">
                                            Join Meeting / Stream <i class="fas fa-external-link-alt" style="font-size: 11px; margin-left: 4px;"></i>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">TBA (To Be Announced — link will be shared via Announcements)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- PHYSICAL & HYBRID VENUE ACCESS -->
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="display: flex; align-items: flex-start; gap: 16px; padding: 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm);">
                                <div style="font-size: 28px; color: var(--accent-blue); padding-top: 4px;">
                                    <i class="fas fa-location-dot"></i>
                                </div>
                                <div>
                                    <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 4px; color: var(--text-primary);"><?= htmlspecialchars(!empty($event['venue_name']) ? $event['venue_name'] : $event['location']) ?></h3>
                                    <?php if (!empty($event['venue_address'])): ?>
                                        <p style="font-size: 14px; color: var(--text-secondary); margin: 0 0 12px 0; white-space: normal; line-height: 1.5;"><?= htmlspecialchars($event['venue_address']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $mapsLink = !empty($event['google_maps_link']) 
                                        ? $event['google_maps_link'] 
                                        : 'https://www.google.com/maps/search/?api=1&query=' . urlencode((!empty($event['venue_address']) ? $event['venue_address'] : $event['location']));
                                    ?>
                                    <a href="<?= htmlspecialchars($mapsLink) ?>" target="_blank" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 8px; border-radius: var(--radius-sm);">
                                        <i class="fas fa-compass"></i> Open in Google Maps
                                    </a>
                                </div>
                            </div>

                            <!-- Embedded Map Preview -->
                            <div style="position: relative; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border);">
                                <iframe 
                                    width="100%" 
                                    height="300" 
                                    style="border:0;" 
                                    loading="lazy" 
                                    allowfullscreen 
                                    src="https://maps.google.com/maps?q=<?= urlencode(!empty($event['latitude']) && !empty($event['longitude']) ? ($event['latitude'] . ',' . $event['longitude']) : (!empty($event['venue_address']) ? $event['venue_address'] : $event['location'])) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed">
                                </iframe>
                            </div>

                            <?php if (($event['venue_type'] ?? 'physical') === 'hybrid'): ?>
                                <!-- HYBRID EVENT ONLINE ACCESS SECTION -->
                                <div style="display: flex; align-items: flex-start; gap: 16px; padding: 16px; background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.1); border-radius: var(--radius-sm); margin-top: 10px;">
                                    <div style="font-size: 28px; color: var(--accent-blue); padding-top: 4px;">
                                        <i class="fas fa-laptop-code"></i>
                                    </div>
                                    <div>
                                        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 6px; color: var(--text-primary);">Online Session Access (Hybrid Event)</h3>
                                        <p style="font-size: 14px; color: var(--text-secondary); margin: 0; white-space: normal;">
                                            <strong>Platform:</strong> <?= htmlspecialchars(!empty($event['platform']) ? $event['platform'] : 'Online') ?><br>
                                            <strong>Meeting Link:</strong> 
                                            <?php if (!empty($event['meeting_link']) && strtolower($event['meeting_link']) !== 'tba'): ?>
                                                <a href="<?= htmlspecialchars($event['meeting_link']) ?>" target="_blank" style="color: var(--accent-blue); font-weight: 700; text-decoration: underline;">
                                                    Join Meeting / Stream <i class="fas fa-external-link-alt" style="font-size: 11px; margin-left: 4px;"></i>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-style: italic;">TBA (To Be Announced — link will be shared via Announcements)</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Objectives Panel -->
                <?php if (!empty($event['objectives'])): ?>
                    <div class="event-details-panel">
                        <h2>Learning Objectives</h2>
                        <ul class="detail-list">
                            <?php foreach ($event['objectives'] as $obj): ?>
                                <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars($obj) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Requirements Panel -->
                <?php if (!empty($event['requirements'])): ?>
                    <div class="event-details-panel">
                        <h2>Requirements</h2>
                        <ul class="detail-list">
                            <?php foreach ($event['requirements'] as $req): ?>
                                <li><i class="fas fa-info-circle"></i> <?= htmlspecialchars($req) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Sessions Panel -->
                <?php if (!empty($event['sessions'])): ?>
                    <div class="event-details-panel">
                        <h2>Program Sessions</h2>
                        <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 10px;">
                            <?php foreach ($event['sessions'] as $idx => $session): ?>
                                <div style="display: flex; gap: 14px; padding: 14px; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                                    <div style="font-weight: 800; color: var(--accent-blue);">Sesi <?= $idx + 1 ?></div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($session['topic']) ?></div>
                                        <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                                            <i class="far fa-calendar" style="margin-right: 6px;"></i> <?= date('j M Y', strtotime($session['date'])) ?> | 
                                            <i class="far fa-clock" style="margin-left: 6px; margin-right: 6px;"></i> <?= htmlspecialchars($session['time']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>


            </div>

            <!-- REGISTRATION ACTION SIDEBAR -->
            <div class="sidebar-sticky">
                <div class="registration-card">
                    <h3 class="registration-card-title">Event Registration</h3>
                    
                    <!-- SOCIAL PROOF STATS WIDGET -->
                    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px; text-align: left; display: flex; flex-direction: column; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-users" style="color: var(--accent-blue); width: 18px;"></i>
                            <span id="registeredCountText"><strong><?= $event['participants'] ?></strong> students have registered</span>
                        </span>
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-heart" style="color: #ef4444; width: 18px;"></i>
                            <span id="savedCountText">Saved by <strong><?= $savedCount ?></strong> students</span>
                        </span>
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-share-nodes" style="color: var(--accent); width: 18px;"></i>
                            <span>Shared <strong id="sharesCountVal"><?= $sharesCount ?></strong> times</span>
                        </span>
                    </div>
                    <?php if ($event['jenis_pendaftaran'] !== 'Hebahan Sahaja'): ?>
                        <div class="registration-card-points">
                            <i class="fas fa-star" style="margin-right: 6px;"></i> Earn <?= $pointsAwarded ?> Activity Points
                        </div>
                    <?php endif; ?>

                    <div class="registration-card-meta">
                        <div class="registration-card-meta-row">
                            <span>Capacity</span>
                            <strong><?= $event['capacity'] ?></strong>
                        </div>
                        <div class="registration-card-meta-row">
                            <span>Seats Remaining</span>
                            <strong style="color: <?= $isFull ? '#ef4444' : '#10b981' ?>;"><?= $seatsLeft ?></strong>
                        </div>
                        <div class="registration-card-meta-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border);">
                            <span>Registration Type</span>
                            <strong><?= htmlspecialchars($event['jenis_pendaftaran']) ?></strong>
                        </div>
                    </di                    <div class="registration-actions">
                        <!-- Side-by-Side Main Actions -->
                        <?php if ($event['jenis_pendaftaran'] === 'Hebahan Sahaja'): ?>
                            <div style="display: flex; gap: 8px; width: 100%;">
                                <button class="btn btn-outline" style="flex: 2; color: var(--text-secondary); cursor: default; padding: 10px; font-size: 13px;" disabled>
                                    Announcement Only
                                </button>
                                <?php if (!$isLoggedIn): ?>
                                    <a href="event-details.php?id=<?= $programId ?>&action=save" class="btn btn-outline" style="flex: 1; padding: 10px; border-color: #cbd5e1; display: inline-flex; justify-content: center; align-items: center;" title="Save Event">
                                        <i class="far fa-heart"></i>
                                    </a>
                                <?php else: ?>
                                    <button id="saveBtn" class="btn btn-outline" style="flex: 1; padding: 10px; display: inline-flex; justify-content: center; align-items: center; <?= $isSaved ? 'background-color: #fff7ed; border-color: #fdba74; color: #f97316;' : '' ?>" onclick="toggleSaveEvent(<?= $programId ?>, <?= $isSaved ? 'true' : 'false' ?>)" title="Save Event">
                                        <i id="saveIcon" class="<?= $isSaved ? 'fas' : 'far' ?> fa-heart"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-accent" style="flex: 1; padding: 10px; display: inline-flex; justify-content: center; align-items: center;" onclick="openShareModal()" title="Share Event">
                                    <i class="fas fa-share-nodes"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <?php if (!$isLoggedIn): ?>
                                <!-- Guest view: Auth Triggers -->
                                <div style="display: flex; gap: 8px; width: 100%;">
                                    <a href="event-details.php?id=<?= $programId ?>&action=join" class="btn btn-primary" style="flex: 2; padding: 10px; font-size: 13px; text-align: center; display: inline-flex; align-items: center; justify-content: center;">Register</a>
                                    <a href="event-details.php?id=<?= $programId ?>&action=save" class="btn btn-outline" style="flex: 1; padding: 10px; border-color: #cbd5e1; display: inline-flex; justify-content: center; align-items: center;" title="Save Event">
                                        <i class="far fa-heart"></i>
                                    </a>
                                    <button class="btn btn-accent" style="flex: 1; padding: 10px; display: inline-flex; justify-content: center; align-items: center;" onclick="openShareModal()" title="Share Event">
                                        <i class="fas fa-share-nodes"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Logged In view -->
                                <?php if ($_SESSION['role'] === 'pelajar'): ?>
                                    <div style="display: flex; gap: 8px; width: 100%;">
                                        <?php if ($isPastDeadline): ?>
                                            <button class="btn btn-outline" style="flex: 2; color: #ef4444; border-color: #ef4444; padding: 10px; font-size: 13px; cursor: default;" disabled>
                                                Closed
                                            </button>
                                        <?php elseif (!$isEligible): ?>
                                            <button class="btn btn-outline" style="flex: 2; color: #ef4444; border-color: #ef4444; padding: 10px; font-size: 13px; cursor: default;" disabled>
                                                Ineligible
                                            </button>
                                        <?php elseif ($isRegistered || $hasCrewApplication): ?>
                                            <button class="btn btn-outline" style="flex: 2; color: #10b981; border-color: #10b981; padding: 10px; font-size: 13px; cursor: default;" disabled>
                                                Registered
                                            </button>
                                        <?php else: ?>
                                            <?php if ($isFull): ?>
                                                <button class="btn btn-outline" style="flex: 2; color: #ef4444; border-color: #ef4444; padding: 10px; font-size: 13px; cursor: default;" disabled>
                                                    Full
                                                </button>
                                            <?php else: ?>
                                                <a href="daftar-program-form.php?id=<?= $programId ?>" class="btn btn-primary" style="flex: 2; padding: 10px; font-size: 13px; text-align: center; display: inline-flex; align-items: center; justify-content: center;">Register</a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <button id="saveBtn" class="btn btn-outline" style="flex: 1; padding: 10px; display: inline-flex; justify-content: center; align-items: center; <?= $isSaved ? 'background-color: #fff7ed; border-color: #fdba74; color: #f97316;' : '' ?>" onclick="toggleSaveEvent(<?= $programId ?>, <?= $isSaved ? 'true' : 'false' ?>)" title="Save Event">
                                            <i id="saveIcon" class="<?= $isSaved ? 'fas' : 'far' ?> fa-heart"></i>
                                        </button>

                                        <button class="btn btn-accent" style="flex: 1; padding: 10px; display: inline-flex; justify-content: center; align-items: center;" onclick="openShareModal()" title="Share Event">
                                            <i class="fas fa-share-nodes"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div style="display: flex; gap: 8px; width: 100%;">
                                        <button class="btn btn-outline" style="flex: 3; color: var(--text-secondary); cursor: default; padding: 10px; font-size: 13px;" disabled>
                                            Students Only
                                        </button>
                                        <button class="btn btn-accent" style="flex: 1; padding: 10px; display: inline-flex; justify-content: center; align-items: center;" onclick="openShareModal()" title="Share Event">
                                            <i class="fas fa-share-nodes"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Crew recruitment section -->
                        <?php if ($isLoggedIn && $_SESSION['role'] === 'pelajar' && !$isPastDeadline && $isEligible && !$isRegistered && !$hasCrewApplication): ?>
                            <?php if (in_array($event['jenis_pendaftaran'], ['Crew/AJK', 'Peserta & Crew/AJK'])): ?>
                                <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--border);">
                                <h4 style="font-size: 13px; font-weight: 800; text-transform: uppercase; margin-bottom: 12px; color: var(--text-secondary);">Available Crew Positions</h4>
                                
                                <?php 
                                $prog = ProgressionService::getStudentProgression($studentId);
                                $studentLevel = $prog['level'];
                                ?>
                                
                                <?php if ($studentLevel < 2): ?>
                                    <div style="background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; padding: 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 700; text-align: center; margin-bottom: 8px;">
                                        <i class="fas fa-lock" style="margin-right: 6px;"></i> Reach Level 2 to unlock Crew Applications.
                                    </div>
                                <?php elseif (empty($crewPositions)): ?>
                                    <p style="font-size: 13px; color: var(--text-secondary);">No positions available.</p>
                                <?php else: ?>
                                    <form action="apply-crew.php" method="POST">
                                        <input type="hidden" name="program_id" value="<?= $programId ?>">
                                        <div style="margin-bottom: 16px; display: flex; flex-direction: column; gap: 8px;">
                                            <?php foreach ($crewPositions as $pos): 
                                                $pid = $pos['id'];
                                                $stat = $crewStats[$pid] ?? null;
                                                $remaining = $stat ? $stat['remaining'] : $pos['kuota'];
                                                $isPosFull = $remaining <= 0;
                                                
                                                $isMT = ProgressionService::isMTPosition($pos['nama_jawatan']);
                                                $isBlockedByMT = $isMT && ($studentLevel < 3);
                                                $isLabelFull = $isPosFull || $isBlockedByMT;
                                            ?>
                                                <label style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border: 1px solid <?= $isLabelFull ? '#fecaca' : 'var(--border)' ?>; border-radius: var(--radius-sm); background: <?= $isLabelFull ? '#fef2f2' : 'var(--bg-main)' ?>; cursor: <?= $isLabelFull ? 'not-allowed' : 'pointer' ?>; opacity: <?= $isLabelFull ? '0.7' : '1' ?>;">
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <input type="radio" name="position_id" value="<?= $pid ?>" required <?= $isLabelFull ? 'disabled' : '' ?>>
                                                        <div>
                                                            <div style="font-weight: 800; color: var(--text-primary); font-size: 14px; margin-bottom: 2px;">
                                                                <?= htmlspecialchars($pos['nama_jawatan']) ?>
                                                                <?php if ($isMT): ?>
                                                                    <span style="background: rgba(249, 115, 22, 0.1); color: var(--accent); font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 800;">MT Role</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div style="font-size: 11px; color: var(--text-secondary);"><?= $pos['mata_ganjaran'] ?> pts</div>
                                                            <?php if ($isBlockedByMT): ?>
                                                                <div style="font-size: 11px; font-weight: 800; color: #ef4444; margin-top: 4px;"><i class="fas fa-lock"></i> Reach Level 3 to apply for Majlis Tertinggi (MT) positions.</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if ($isPosFull): ?>
                                                        <div style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 800;">FULL</div>
                                                    <?php elseif (!$isBlockedByMT): ?>
                                                        <div style="font-size: 12px; font-weight: 700; color: #059669;">Remaining: <?= $remaining ?> / <?= $pos['kuota'] ?></div>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <textarea name="alasan" class="form-textarea-profile" placeholder="Why should we choose you? (Requirements/Experience)" required style="margin-bottom: 12px; font-size: 13px; min-height: 60px;"></textarea>
                                        <button type="submit" class="btn btn-primary" style="background-color: var(--accent); color: white; width: 100%;">Apply as Crew</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Q&A / Hub Section -->
                        <?php if ($isLoggedIn && $_SESSION['role'] === 'pelajar'): ?>
                            <a href="program-hub.php?id=<?= $programId ?>" class="btn btn-outline" style="color: var(--accent); border-color: var(--accent); gap: 8px; display: flex; align-items: center; justify-content: center; margin-top: 8px; width: 100%;">
                                <i class="fas fa-comments"></i> Q&A / Hub
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contact & Deadline Panel -->
                <div class="event-details-panel" style="margin-top: 24px; padding: 24px; text-align: left;">
                    <h3 style="font-size: 16px; font-weight: 800; border-bottom: 2px solid var(--bg-secondary); padding-bottom: 10px; margin-bottom: 16px; font-family: 'Outfit';">Additional Information</h3>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <?php if ($event['deadline_date']): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 10px 0; font-weight: 700; color: var(--text-secondary); width: 140px;"><i class="fas fa-stopwatch" style="margin-right:6px; color:#ef4444;"></i> Deadline</td>
                            <td style="padding: 10px 0; color: var(--text-primary); font-weight: 700;">
                                <?= date('j M Y', strtotime($event['deadline_date'])) ?>
                                <?= $event['deadline_time'] ? ' ' . date('g:i A', strtotime($event['deadline_time'])) : '' ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($event['contact_person']): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 10px 0; font-weight: 700; color: var(--text-secondary);"><i class="fas fa-user" style="margin-right:6px;"></i> Contact</td>
                            <td style="padding: 10px 0; color: var(--text-primary);"><?= htmlspecialchars($event['contact_person']) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($event['contact_number']): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 10px 0; font-weight: 700; color: var(--text-secondary);"><i class="fas fa-phone" style="margin-right:6px;"></i> Phone</td>
                            <td style="padding: 10px 0; color: var(--text-primary);"><?= htmlspecialchars($event['contact_number']) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($event['contact_email']): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 10px 0; font-weight: 700; color: var(--text-secondary);"><i class="fas fa-envelope" style="margin-right:6px;"></i> Email</td>
                            <td style="padding: 10px 0; color: var(--text-primary); overflow-wrap: anywhere;">
                                <a href="mailto:<?= htmlspecialchars($event['contact_email']) ?>" style="color:var(--accent-blue); text-decoration:none;">
                                    <?= htmlspecialchars($event['contact_email']) ?>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($event['whatsapp_link'] || $event['telegram_link'] || $event['instagram_link']): ?>
                        <tr>
                            <td style="padding: 10px 0; font-weight: 700; color: var(--text-secondary);"><i class="fas fa-link" style="margin-right:6px;"></i> Socials</td>
                            <td style="padding: 10px 0; display: flex; gap: 10px; align-items: center;">
                                <?php if ($event['whatsapp_link']): ?>
                                    <a href="<?= htmlspecialchars($event['whatsapp_link']) ?>" target="_blank" style="color: #25D366; font-size: 18px;"><i class="fab fa-whatsapp"></i></a>
                                <?php endif; ?>
                                <?php if ($event['telegram_link']): ?>
                                    <a href="<?= htmlspecialchars($event['telegram_link']) ?>" target="_blank" style="color: #0088cc; font-size: 18px;"><i class="fab fa-telegram"></i></a>
                                <?php endif; ?>
                                <?php if ($event['instagram_link']): ?>
                                    <a href="<?= htmlspecialchars($event['instagram_link']) ?>" target="_blank" style="color: #E1306C; font-size: 18px;"><i class="fab fa-instagram"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- EVENT SHARE MODAL -->
    <div class="share-modal-backdrop" id="shareModalBackdrop" onclick="closeShareModal()">
        <div class="share-modal" id="shareModal" onclick="event.stopPropagation()">
            <h3 style="font-family: 'Outfit'; font-size: 20px; font-weight: 800; margin-bottom: 8px; color: var(--primary);">Share: <?= htmlspecialchars($event['title']) ?></h3>
            <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 20px;">Choose a platform to share this event:</p>

            <div class="share-modal-grid">
                <div class="share-modal-item" onclick="copyEventLink()">
                    <i class="far fa-copy" style="color: #475569;"></i>
                    <span>Copy Link</span>
                </div>
                <a class="share-modal-item" href="https://api.whatsapp.com/send?text=<?= urlencode("Hi! Check out this event: " . $event['title'] . "\n" . $eventUrl) ?>" target="_blank" onclick="recordShareAction()">
                    <i class="fab fa-whatsapp" style="color: #25D366;"></i>
                    <span>WhatsApp</span>
                </a>
                <a class="share-modal-item" href="https://t.me/share/url?url=<?= urlencode($eventUrl) ?>&text=<?= urlencode("Hi! Check out this event: " . $event['title']) ?>" target="_blank" onclick="recordShareAction()">
                    <i class="fab fa-telegram" style="color: #0088cc;"></i>
                    <span>Telegram</span>
                </a>
                <a class="share-modal-item" href="mailto:?subject=<?= rawurlencode("Event Recommendation: " . $event['title']) ?>&body=<?= rawurlencode("Hi! I think you'd be interested in " . $event['title'] . ".\n\nView details here:\n" . $eventUrl) ?>" target="_blank" onclick="recordShareAction()">
                    <i class="far fa-envelope" style="color: #ea4335;"></i>
                    <span>Email</span>
                </a>
                <a class="share-modal-item" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($eventUrl) ?>" target="_blank" onclick="recordShareAction()">
                    <i class="fab fa-facebook" style="color: #1877f2;"></i>
                    <span>Facebook</span>
                </a>
                <a class="share-modal-item" href="https://twitter.com/intent/tweet?url=<?= urlencode($eventUrl) ?>&text=<?= urlencode("Check out this event: " . $event['title']) ?>" target="_blank" onclick="recordShareAction()">
                    <i class="fab fa-x-twitter" style="color: #0f172a;"></i>
                    <span>X (Twitter)</span>
                </a>
            </div>

            <button class="btn btn-outline" style="width: 100%; border-radius: 999px; font-size: 13px;" onclick="closeShareModal()">Close</button>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container" id="toastContainer" style="display: none; opacity: 0; transition: opacity 0.5s ease;">
        <div class="toast">
            <i class="fas fa-heart" style="color: var(--accent);"></i>
            <span id="toastMsg">Event saved to your interests!</span>
        </div>
    </div>

    <script>
        function toggleSaveEvent(programId, currentlySaved) {
            const action = currentlySaved ? 'unsave' : 'save';
            fetch('save-event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ program_id: programId, action: action })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('saveBtn');
                    const icon = document.getElementById('saveIcon');
                    const text = document.getElementById('saveText');
                    
                    if (action === 'save') {
                        btn.style.backgroundColor = '#fff7ed';
                        btn.style.borderColor = '#fdba74';
                        btn.style.color = '#f97316';
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        if (text) {
                            text.textContent = 'Saved to Interests';
                        }
                        btn.setAttribute('onclick', `toggleSaveEvent(${programId}, true)`);
                        showToast('Event saved to your interests!');
                    } else {
                        btn.style.backgroundColor = '';
                        btn.style.borderColor = '';
                        btn.style.color = '';
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        if (text) {
                            text.textContent = 'Save Event';
                        }
                        btn.setAttribute('onclick', `toggleSaveEvent(${programId}, false)`);
                        showToast('Event removed from your interests.');
                    }

                    // Dynamically update the saved counter statistic on the page
                    const savedCountTextEl = document.getElementById('savedCountText');
                    if (savedCountTextEl) {
                        const strongEl = savedCountTextEl.querySelector('strong');
                        if (strongEl) {
                            let currentCount = parseInt(strongEl.textContent) || 0;
                            strongEl.textContent = action === 'save' ? (currentCount + 1) : Math.max(0, currentCount - 1);
                        }
                    }
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Error updating saved event.');
                    }
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An error occurred. Please try again.');
            });
        }

        function showToast(message) {
            const toast = document.getElementById('toastContainer');
            const msgEl = document.getElementById('toastMsg');
            msgEl.textContent = message;
            toast.style.display = 'flex';
            setTimeout(() => toast.style.opacity = '1', 10);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.style.display = 'none', 500);
            }, 3000);
        }

        <?php if ($showSavedToast): ?>
            showToast('Event saved to your interests!');
        <?php endif; ?>

        function openShareModal() {
            const shareData = {
                title: <?= json_encode($event['title']) ?>,
                text: <?= json_encode(mb_strimwidth(strip_tags($event['description']), 0, 100, "...")) ?>,
                url: <?= json_encode($eventUrl) ?>
            };
            
            try {
                if (navigator.share) {
                    navigator.share(shareData)
                    .then(() => recordShareAction())
                    .catch(err => {
                        console.log('Native share failed/cancelled, using modal fallback:', err);
                        showFallbackShareModal();
                    });
                } else {
                    showFallbackShareModal();
                }
            } catch (e) {
                console.error('Native share error, falling back:', e);
                showFallbackShareModal();
            }
        }

        function showFallbackShareModal() {
            const backdrop = document.getElementById('shareModalBackdrop');
            const modal = document.getElementById('shareModal');
            backdrop.style.display = 'flex';
            setTimeout(() => {
                backdrop.style.opacity = '1';
                modal.style.transform = 'translateY(0)';
            }, 10);
        }

        function closeShareModal() {
            const backdrop = document.getElementById('shareModalBackdrop');
            const modal = document.getElementById('shareModal');
            backdrop.style.opacity = '0';
            modal.style.transform = 'translateY(20px)';
            setTimeout(() => {
                backdrop.style.display = 'none';
            }, 300);
        }

        function recordShareAction() {
            fetch('share-action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ program_id: <?= $programId ?> })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const sharesCountEl = document.getElementById('sharesCountVal');
                    if (sharesCountEl) {
                        sharesCountEl.textContent = data.shares_count;
                    }
                }
            })
            .catch(err => console.error(err));
        }

        function copyEventLink() {
            const tempInput = document.createElement('textarea');
            tempInput.value = "Let's join " + <?= json_encode($event['title']) ?> + " on UKMInvolve! Join me here:\n" + <?= json_encode($eventUrl) ?>;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            recordShareAction();
            showToast('✓ Link copied successfully');
            closeShareModal();
        }
    </script>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$tab = strtolower(trim($_GET['tab'] ?? 'all'));
if (!in_array($tab, ['all', 'events', 'organizers', 'faculties', 'colleges', 'organizations'], true)) {
    $tab = 'all';
}

$studentId = $_SESSION['user_id'] ?? '';
$isLoggedIn = !empty($studentId);

// 1. Fetch matching events (active events only)
$events = [];
if (db()->isConfigured()) {
    $activeRows = programs()->listActiveWithCategory();
    foreach ($activeRows as $row) {
        $event = programs()->toStudentSearchRow($row);
        
        $match = false;
        if ($q === '') {
            $match = true;
        } else {
            $match = str_contains(strtolower($event['title']), strtolower($q)) ||
                     str_contains(strtolower($event['description']), strtolower($q)) ||
                     str_contains(strtolower($event['location']), strtolower($q)) ||
                     str_contains(strtolower($event['organizer']), strtolower($q));
        }
        
        if ($match) {
            $events[] = $event;
        }
    }
}

// 2. Fetch matching organizers
$organizers = [];
if (db()->isConfigured()) {
    $orgRes = db()->select('users', '?peranan=eq.penganjur&status=eq.aktif');
    if ($orgRes['ok'] && !empty($orgRes['data'])) {
        foreach ($orgRes['data'] as $org) {
            $orgName = $org['nama'] ?? '';
            $orgBio = $org['bio'] ?? '';
            $orgFakulti = $org['fakulti'] ?? '';
            $orgKolej = $org['kolej'] ?? '';
            $orgOrg = $org['organisasi'] ?? '';
            
            $match = false;
            if ($q === '') {
                $match = true;
            } else {
                $match = str_contains(strtolower($orgName), strtolower($q)) ||
                         str_contains(strtolower($orgBio), strtolower($q)) ||
                         str_contains(strtolower($orgFakulti), strtolower($q)) ||
                         str_contains(strtolower($orgKolej), strtolower($q)) ||
                         str_contains(strtolower($orgOrg), strtolower($q));
            }
            
            if ($match) {
                $type = users()->getOrganizerType($org);
                
                $organizers[] = [
                    'id' => $org['id'],
                    'name' => $orgName,
                    'bio' => $orgBio,
                    'type' => $type,
                    'avatar_url' => $org['avatar_url'] ?? ''
                ];
            }
        }
    }
}

// Split organizers by category for tabs
$filteredOrganizers = [];
if ($tab === 'all' || $tab === 'organizers') {
    $filteredOrganizers = $organizers;
} elseif ($tab === 'faculties') {
    $filteredOrganizers = array_filter($organizers, fn($o) => $o['type'] === 'faculty');
} elseif ($tab === 'colleges') {
    $filteredOrganizers = array_filter($organizers, fn($o) => $o['type'] === 'college');
} elseif ($tab === 'organizations') {
    $filteredOrganizers = array_filter($organizers, fn($o) => $o['type'] === 'organization');
}

$filteredEvents = ($tab === 'all' || $tab === 'events') ? $events : [];

$totalEventsCount = count($events);
$totalOrgsCount = count($organizers);
$facultiesCount = count(array_filter($organizers, fn($o) => $o['type'] === 'faculty'));
$collegesCount = count(array_filter($organizers, fn($o) => $o['type'] === 'college'));
$orgsCount = count(array_filter($organizers, fn($o) => $o['type'] === 'organization'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .search-results-section {
            padding: 40px 0 80px;
        }
        .search-tab-wrapper {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
            overflow-x: auto;
            white-space: nowrap;
        }
        .search-tab-btn {
            background: none;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 999px;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .search-tab-btn:hover {
            color: var(--accent-blue);
            background: var(--bg-secondary);
        }
        .search-tab-btn.active {
            background: var(--accent-blue);
            color: var(--white);
        }
        .search-tab-badge {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 999px;
            background: rgba(255,255,255,0.25);
            font-weight: 800;
        }
        .search-tab-btn:not(.active) .search-tab-badge {
            background: var(--border);
            color: var(--text-secondary);
        }
        .organizer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        .org-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: var(--transition);
        }
        .org-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .org-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        .org-card-logo-wrap {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--border);
            flex-shrink: 0;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .org-card-logo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .org-card-logo-fallback {
            font-size: 24px;
            font-weight: 800;
            color: var(--accent-blue);
            text-transform: uppercase;
        }
        .org-card-title {
            font-size: 16px;
            font-weight: 800;
            font-family: 'Outfit';
            color: var(--text-primary);
            line-height: 1.3;
        }
        .org-card-type-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
        }
        .badge-faculty { background: #eff6ff; color: #2563eb; }
        .badge-college { background: #fef3c7; color: #d97706; }
        .badge-organization { background: #f3e8ff; color: #9333ea; }
        
        .org-card-bio {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 20px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            min-height: 58px;
        }
        .org-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border);
            padding-top: 16px;
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <!-- HEADER BANNER -->
    <section class="hero" style="padding: 60px 0 80px; margin-bottom: -40px;">
        <div class="hero-overlay"></div>
        <div class="hero-shapes">
            <div class="hero-shape hero-shape-1"></div>
            <div class="hero-shape hero-shape-2"></div>
        </div>
        <div class="hero-container container" style="position: relative; z-index: 10;">
            <h1 style="margin-bottom: 10px;">Search Results</h1>
            <p style="margin-bottom: 0;">Explore events and organizations across UKM.</p>
        </div>
    </section>

    <!-- MAIN CONTAINER -->
    <main class="container search-results-section">
        <!-- SEARCH BAR -->
        <div class="search-card" style="margin-top: 0; box-shadow: var(--shadow-sm); margin-bottom: 32px; padding: 20px;">
            <form action="search.php" method="GET" style="display: flex; gap: 12px;">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <div style="position: relative; flex: 1; display: flex; align-items: center;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; color: var(--text-secondary);"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name, description, category, faculty..." style="width: 100%; padding: 12px 16px 12px 44px; border: 1px solid var(--border); border-radius: 999px; outline: none; font-size: 14px; transition: var(--transition);">
                </div>
                <button type="submit" class="btn btn-primary" style="border-radius: 999px; font-weight: 800; padding: 0 30px;">Search</button>
            </form>
        </div>

        <!-- FILTER TABS -->
        <div class="search-tab-wrapper">
            <a href="search.php?q=<?= urlencode($q) ?>&tab=all" class="search-tab-btn <?= $tab === 'all' ? 'active' : '' ?>">
                All <span class="search-tab-badge"><?= $totalEventsCount + $totalOrgsCount ?></span>
            </a>
            <a href="search.php?q=<?= urlencode($q) ?>&tab=events" class="search-tab-btn <?= $tab === 'events' ? 'active' : '' ?>">
                Events <span class="search-tab-badge"><?= $totalEventsCount ?></span>
            </a>
            <a href="search.php?q=<?= urlencode($q) ?>&tab=organizers" class="search-tab-btn <?= $tab === 'organizers' ? 'active' : '' ?>">
                Organizers <span class="search-tab-badge"><?= $totalOrgsCount ?></span>
            </a>
            <a href="search.php?q=<?= urlencode($q) ?>&tab=faculties" class="search-tab-btn <?= $tab === 'faculties' ? 'active' : '' ?>">
                Faculties <span class="search-tab-badge"><?= $facultiesCount ?></span>
            </a>
            <a href="search.php?q=<?= urlencode($q) ?>&tab=colleges" class="search-tab-btn <?= $tab === 'colleges' ? 'active' : '' ?>">
                Colleges <span class="search-tab-badge"><?= $collegesCount ?></span>
            </a>
            <a href="search.php?q=<?= urlencode($q) ?>&tab=organizations" class="search-tab-btn <?= $tab === 'organizations' ? 'active' : '' ?>">
                Organizations <span class="search-tab-badge"><?= $orgsCount ?></span>
            </a>
        </div>

        <!-- SEARCH CONTENT -->
        <?php if (empty($filteredEvents) && empty($filteredOrganizers)): ?>
            <!-- EMPTY STATE -->
            <div style="text-align: center; padding: 80px 40px; border: 2px dashed var(--border); border-radius: var(--radius-md); background: var(--white);">
                <div style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px;">
                    <i class="fas fa-magnifying-glass"></i>
                </div>
                <h3 style="font-size: 20px; margin-bottom: 8px;">No results found</h3>
                <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto;">We couldn't find any events or organizations matching "<strong><?= htmlspecialchars($q) ?></strong>". Try checking spelling or using fewer keywords.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 40px;">
                
                <!-- ORGANIZERS SECTION -->
                <?php if (!empty($filteredOrganizers)): ?>
                    <div>
                        <?php if ($tab === 'all'): ?>
                            <h2 style="font-family: 'Outfit'; font-size: 20px; font-weight: 800; margin-bottom: 20px; color: var(--text-primary);">Organizers & Organizations</h2>
                        <?php endif; ?>
                        
                        <div class="organizer-grid">
                            <?php foreach ($filteredOrganizers as $org): 
                                $badgeClass = match ($org['type']) {
                                    'faculty' => 'badge-faculty',
                                    'college' => 'badge-college',
                                    default => 'badge-organization'
                                };
                                $typeLabel = match ($org['type']) {
                                    'faculty' => 'Faculty',
                                    'college' => 'College',
                                    default => 'Organization'
                                };
                                $avatarPath = $org['avatar_url'];
                                $hasAvatar = !empty($avatarPath) && file_exists($avatarPath);
                            ?>
                                <div class="org-card">
                                    <div>
                                        <div class="org-card-header">
                                            <div class="org-card-logo-wrap">
                                                <?php if ($hasAvatar): ?>
                                                    <img src="<?= htmlspecialchars($avatarPath) ?>" alt="<?= htmlspecialchars($org['name']) ?>" class="org-card-logo">
                                                <?php else: ?>
                                                    <span class="org-card-logo-fallback"><?= strtoupper(substr($org['name'], 0, 1)) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="organizer-profile.php?id=<?= $org['id'] ?>" class="org-card-title" style="text-decoration: none;"><?= htmlspecialchars($org['name']) ?></a>
                                                <div>
                                                    <span class="org-card-type-badge <?= $badgeClass ?>"><?= $typeLabel ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="org-card-bio"><?= htmlspecialchars($org['bio'] ?: 'No biography or description provided yet.') ?></p>
                                    </div>
                                    <div class="org-card-footer" style="justify-content: flex-end;">
                                        <a href="organizer-profile.php?id=<?= $org['id'] ?>" class="btn btn-outline btn-sm" style="border-radius: 999px;">View Profile</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- EVENTS SECTION -->
                <?php if (!empty($filteredEvents)): ?>
                    <div>
                        <?php if ($tab === 'all'): ?>
                            <h2 style="font-family: 'Outfit'; font-size: 20px; font-weight: 800; margin-bottom: 20px; color: var(--text-primary); margin-top: 16px;">Active Events & Programmes</h2>
                        <?php endif; ?>
                        
                        <div class="grid-cards">
                            <?php foreach ($filteredEvents as $event): 
                                $seatsLeft = max(0, $event['capacity'] - $event['participants']);
                                $isFull = $seatsLeft <= 0;
                                $badgeBg = $isFull ? '#ef4444' : '#10b981';
                                $badgeText = $isFull ? 'Full' : 'Open';
                            ?>
                                <div class="event-card">
                                    <div class="event-img-wrap">
                                        <span class="event-badge" style="background-color: <?= $badgeBg ?>;"><?= $badgeText ?></span>
                                        <img src="<?= htmlspecialchars(getImagePath($event['image'])) ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="event-img">
                                    </div>
                                    <div class="event-card-body">
                                        <div class="event-category-organizer">
                                            <span class="event-category"><?= htmlspecialchars($event['category']) ?></span>
                                            <?php if ($event['penganjur_id']): ?>
                                                <a href="organizer-profile.php?id=<?= $event['penganjur_id'] ?>" class="event-organizer" style="color: var(--accent-blue); text-decoration: none; font-weight: 700;" title="View Organizer Profile"><?= htmlspecialchars($event['organizer']) ?></a>
                                            <?php else: ?>
                                                <span class="event-organizer"><?= htmlspecialchars($event['organizer']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h3><?= htmlspecialchars($event['title']) ?></h3>
                                        
                                        <div class="event-meta-list">
                                            <div class="event-meta-item">
                                                <i class="far fa-calendar-alt"></i>
                                                <span><?= date('j M Y', strtotime($event['start_date'])) ?></span>
                                            </div>
                                            <div class="event-meta-item">
                                                <i class="far fa-clock"></i>
                                                <span><?= date('g:i A', strtotime($event['start_time'])) ?></span>
                                            </div>
                                            <div class="event-meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?= htmlspecialchars($event['location']) ?></span>
                                            </div>
                                        </div>

                                        <div class="event-card-footer">
                                            <span class="event-seats <?= $isFull ? 'full' : '' ?>">
                                                <strong><?= $seatsLeft ?></strong> seats left
                                            </span>
                                            <a href="event-details.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>
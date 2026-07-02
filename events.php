<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

// Fetch active programs from database
$allEvents = [];
if (db()->isConfigured()) {
    $activeRows = programs()->listActiveWithCategory();
    foreach ($activeRows as $row) {
        $allEvents[] = programs()->toStudentSearchRow($row);
    }
}

// Fetch active categories for dropdown
$categoriesList = [];
if (db()->isConfigured()) {
    $categoriesList = categories()->listAll(true);
}

$filteredEvents = $allEvents;

// 1. Text Query Filter (Title or Description)
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $filteredEvents = array_filter($filteredEvents, function($e) use ($q) {
        return str_contains(strtolower($e['title']), strtolower($q)) || 
               str_contains(strtolower($e['description']), strtolower($q));
    });
}

// 2. Category Filter (by Name or Slug)
$categoryFilter = trim($_GET['category'] ?? '');
if ($categoryFilter !== '') {
    $filteredEvents = array_filter($filteredEvents, function($e) use ($categoryFilter) {
        $catSlug = $e['category_slug'] ?? slugify($e['category'] ?? '');
        return strtolower($catSlug) === strtolower($categoryFilter);
    });
}

// 3. Location Filter
$locationFilter = trim($_GET['location'] ?? '');
if ($locationFilter !== '') {
    $filteredEvents = array_filter($filteredEvents, function($e) use ($locationFilter) {
        return str_contains(strtolower($e['location']), strtolower($locationFilter));
    });
}

// 4. Date range Filter
$dateFilter = trim($_GET['date'] ?? '');
if ($dateFilter !== '') {
    $today = date('Y-m-d');
    $filteredEvents = array_filter($filteredEvents, function($e) use ($dateFilter, $today) {
        $eventDate = $e['start_date'] ?? $e['dateISO'] ?? '';
        if (!$eventDate) return true;
        
        if ($dateFilter === 'today') {
            return $eventDate === $today;
        } elseif ($dateFilter === 'week') {
            $weekEnd = date('Y-m-d', strtotime('+7 days'));
            return $eventDate >= $today && $eventDate <= $weekEnd;
        } elseif ($dateFilter === 'month') {
            $monthEnd = date('Y-m-d', strtotime('+30 days'));
            return $eventDate >= $today && $eventDate <= $monthEnd;
        }
        return true;
    });
}

// 5. Organizer fetching (only if search query is not empty)
$matchingOrganizers = [];
if ($q !== '' && db()->isConfigured()) {
    $orgRes = db()->select('users', '?peranan=eq.penganjur&status=eq.aktif');
    if ($orgRes['ok'] && !empty($orgRes['data'])) {
        foreach ($orgRes['data'] as $org) {
            $orgName = $org['nama'] ?? '';
            if (str_contains(strtolower($orgName), strtolower($q)) || str_contains(strtolower($org['organisasi'] ?? ''), strtolower($q))) {
                $type = users()->getOrganizerType($org);
                $matchingOrganizers[] = [
                    'id' => $org['id'],
                    'name' => $orgName,
                    'bio' => $org['bio'] ?? '',
                    'type' => $type,
                    'avatar_url' => $org['avatar_url'] ?? ''
                ];
            }
        }
    }
}

// 6. Pagination calculation
$limit = 6;
$totalItems = count($filteredEvents);
$totalPages = max(1, ceil($totalItems / $limit));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $limit;
$paginatedEvents = array_slice($filteredEvents, $offset, $limit);

// Build query string for pagination links preserving other filters
$queryParams = $_GET;
unset($queryParams['page']);
$baseQuery = http_build_query($queryParams);
$baseQueryString = $baseQuery ? $baseQuery . '&' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .organizer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
        .org-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: space-between; transition: var(--transition); }
        .org-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .org-card-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
        .org-card-logo-wrap { width: 60px; height: 60px; border-radius: 50%; overflow: hidden; border: 2px solid var(--border); flex-shrink: 0; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; }
        .org-card-logo { width: 100%; height: 100%; object-fit: cover; }
        .org-card-logo-fallback { font-size: 24px; font-weight: 800; color: var(--accent-blue); text-transform: uppercase; }
        .org-card-title { font-size: 16px; font-weight: 800; font-family: 'Outfit'; color: var(--text-primary); line-height: 1.3; }
        .org-card-type-badge { display: inline-block; font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; margin-top: 4px; }
        .badge-faculty { background: #eff6ff; color: #2563eb; }
        .badge-college { background: #fef3c7; color: #d97706; }
        .badge-organization { background: #f3e8ff; color: #9333ea; }
        .org-card-bio { font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 20px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; min-height: 58px; }
        .org-card-footer { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 16px; margin-top: auto; }
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
            <h1 style="margin-bottom: 10px;">Browse UKM Events</h1>
            <p style="margin-bottom: 0;">Discover programs, workshops, and activities across the campus.</p>
        </div>
    </section>

    <!-- SEARCH & FILTER BAR -->
    <section class="container" style="margin-top: 30px; margin-bottom: 50px;">
        <div class="search-card" style="margin-top: 0; box-shadow: var(--shadow-sm);">
            <form action="events.php" method="GET" class="search-form">
                <div class="search-group">
                    <label class="search-label" for="search-q">Event Name</label>
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="search-q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name or description...">
                    </div>
                </div>
                <div class="search-group">
                    <label class="search-label" for="search-cat">Category</label>
                    <select id="search-cat" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $categoryFilter === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-group">
                    <label class="search-label" for="search-date">Date</label>
                    <select id="search-date" name="date">
                        <option value="">Any Time</option>
                        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>This Month</option>
                    </select>
                </div>
                <div class="search-group">
                    <label class="search-label" for="search-loc">Location</label>
                    <div class="search-input-wrapper">
                        <i class="fas fa-map-marker-alt search-icon"></i>
                        <input type="text" id="search-loc" name="location" value="<?= htmlspecialchars($locationFilter) ?>" placeholder="e.g. FST, HEP, Online">
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-accent btn-search" style="flex: 1;">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if ($q !== '' || $categoryFilter !== '' || $locationFilter !== '' || $dateFilter !== ''): ?>
                        <a href="events.php" class="btn btn-outline" style="height: 50px; display: flex; align-items: center; justify-content: center; padding: 0 16px;" title="Reset Filters">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <!-- EVENTS GRID -->
    <section class="container" style="margin-bottom: 80px;">
        <?php if (!empty($matchingOrganizers)): ?>
            <div style="margin-bottom: 50px;">
                <h2 style="font-family: 'Outfit'; font-size: 24px; font-weight: 800; margin-bottom: 20px; color: var(--text-primary);">Organizers matching "<?= htmlspecialchars($q) ?>"</h2>
                <div class="organizer-grid">
                    <?php foreach ($matchingOrganizers as $org): 
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
            
            <?php if (!empty($paginatedEvents)): ?>
                <h2 style="font-family: 'Outfit'; font-size: 24px; font-weight: 800; margin-bottom: 20px; color: var(--text-primary);">Events matching "<?= htmlspecialchars($q) ?>"</h2>
            <?php endif; ?>
        <?php endif; ?>

        <div class="grid-cards">
            <?php if (empty($paginatedEvents)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 80px 40px; border: 2px dashed var(--border); border-radius: var(--radius-md); background: var(--white);">
                    <div style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px;">
                        <i class="far fa-calendar-times"></i>
                    </div>
                    <h3 style="font-size: 20px; margin-bottom: 8px;">No events found</h3>
                    <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto 24px;">We couldn't find any upcoming activities matching your search parameters. Try adjusting your filters.</p>
                    <a href="events.php" class="btn btn-primary btn-sm">Clear All Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($paginatedEvents as $event): 
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
                                <?php if (!empty($event['penganjur_id'])): ?>
                                    <a href="organizer-profile.php?id=<?= urlencode($event['penganjur_id']) ?>" class="event-organizer" style="color: var(--accent-blue); text-decoration: none; font-weight: 700;" title="View Organizer Profile"><?= htmlspecialchars($event['organizer']) ?></a>
                                <?php else: ?>
                                    <span class="event-organizer" title="<?= htmlspecialchars($event['organizer']) ?>"><?= htmlspecialchars($event['organizer']) ?></span>
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
            <?php endif; ?>
        </div>

        <!-- PAGINATION CONTROLS -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?<?= $baseQueryString ?>page=<?= max(1, $page - 1) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>" aria-label="Previous Page">
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= $baseQueryString ?>page=<?= $i ?>" class="pagination-btn <?= $page === $i ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <a href="?<?= $baseQueryString ?>page=<?= min($totalPages, $page + 1) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" aria-label="Next Page">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </section>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let timeout = null;
        const form = document.querySelector('.search-form');
        
        // Auto-submit text inputs with debounce
        document.querySelectorAll('.search-form input[type="text"]').forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    form.submit();
                }, 600);
            });
        });
        
        // Auto-submit select dropdowns immediately
        document.querySelectorAll('.search-form select').forEach(select => {
            select.addEventListener('change', function() {
                form.submit();
            });
        });
        
        // Maintain focus on search input after reload
        const qInput = document.getElementById('search-q');
        if (qInput && qInput.value) {
            // Only focus if the user hasn't clicked somewhere else yet
            if (!document.activeElement || document.activeElement.tagName === 'BODY') {
                qInput.focus();
                let val = qInput.value;
                qInput.value = '';
                qInput.value = val;
            }
        }
    });
    </script>
</body>
</html>


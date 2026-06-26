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

// Slice first 3 events as featured events
$featuredEvents = array_slice($allEvents, 0, 3);
// Slice next 6 events for the upcoming events grid
$upcomingEvents = array_slice($allEvents, 3, 6);

// Fetch categories dynamically
$categoriesList = [];
if (db()->isConfigured()) {
    $dbCats = categories()->listAll(true);
    foreach ($dbCats as $cat) {
        $count = 0;
        foreach ($allEvents as $event) {
            if (($event['category_slug'] ?? '') === $cat['slug']) {
                $count++;
            }
        }
        $categoriesList[] = [
            'name' => $cat['nama'],
            'slug' => $cat['slug'],
            'icon' => $cat['icon'] ?? 'fa-layer-group',
            'color' => $cat['color'] ?? '#3b82f6',
            'count' => $count
        ];
    }
}

// Fetch platform statistics dynamically from database
$totalEvents = 0;
$totalStudents = 0;
$totalOrganizers = 0;
$totalRegistrations = 0;

if (db()->isConfigured()) {
    $totalEvents = count(db()->select('program', '?select=id')['data'] ?? []);
    $totalStudents = users()->countByPeranan('pelajar');
    $totalOrganizers = users()->countByPeranan('penganjur');
    $totalRegistrations = registrations()->countAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Events and Activities | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <!-- SECTION 1: HERO BANNER -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-shapes">
            <div class="hero-shape hero-shape-1"></div>
            <div class="hero-shape hero-shape-2"></div>
        </div>
        <div class="container hero-container">
            <span class="hero-tagline">UKM Event Hub</span>
            <h1>Discover Events and Activities at UKM</h1>
            <p>Join workshops, competitions, seminars, volunteering programmes and more. Expand your network and earn valuable activity points.</p>
            <div class="hero-actions">
                <a href="#events" class="btn btn-primary">Explore Events</a>
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-outline" style="color: #fff; border-color: rgba(255,255,255,0.3);">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 2: SEARCH BAR -->
    <section class="container search-section">
        <div class="search-card">
            <form action="search.php" method="GET" class="search-form">
                <div class="search-group">
                    <label class="search-label" for="search-q">Event Name</label>
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="search-q" name="q" placeholder="Search events...">
                    </div>
                </div>
                <div class="search-group">
                    <label class="search-label" for="search-cat">Category</label>
                    <select id="search-cat" name="category">
                        <option value="">All Categories</option>
                        <option value="akademik">Academic</option>
                        <option value="teknologi">Technology</option>
                        <option value="sukan">Sports</option>
                        <option value="kepimpinan">Leadership</option>
                        <option value="komuniti">Volunteer</option>
                        <option value="seni">Culture</option>
                        <option value="keusahawanan">Entrepreneurship</option>
                    </select>
                </div>
                <div class="search-group">
                    <label class="search-label" for="search-date">Date</label>
                    <select id="search-date" name="date">
                        <option value="">Any Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
                <div class="search-group">
                    <label class="search-label" for="search-loc">Location</label>
                    <div class="search-input-wrapper">
                        <i class="fas fa-map-marker-alt search-icon"></i>
                        <input type="text" id="search-loc" name="location" placeholder="e.g. FST, DECT">
                    </div>
                </div>
                <button type="submit" class="btn btn-accent btn-search">
                    <i class="fas fa-magnifying-glass"></i> Search
                </button>
            </form>
        </div>
    </section>

    <!-- SECTION 3: FEATURED EVENTS -->
    <section class="container" style="margin-bottom: 80px;" id="events">
        <div class="section-title-wrap">
            <div>
                <h2>Featured Events</h2>
                <p>Highly recommended programs and upcoming highlights at UKM.</p>
            </div>
            <a href="events.php" class="btn btn-outline btn-sm">See All Events <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="grid-cards">
            <?php if (empty($featuredEvents)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; border: 2px dashed var(--border); border-radius: var(--radius-md);">
                    <p style="color: var(--text-secondary); font-size: 15px;">No upcoming events found. Stay tuned for updates!</p>
                </div>
            <?php else: ?>
                <?php foreach ($featuredEvents as $event): 
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
    </section>

    <!-- SECTION 4: CATEGORIES -->
    <section class="categories-section" id="categories">
        <div class="container">
            <div class="section-title-wrap" style="text-align: center; flex-direction: column; align-items: center; margin-bottom: 48px;">
                <h2>Explore by Category</h2>
                <p>Find programs tailored to your learning, leadership, and personal growth interests.</p>
            </div>
            
            <div class="categories-grid">
                <?php foreach ($categoriesList as $cat): ?>
                    <div class="category-card" onclick="window.location.href='events.php?category=<?= htmlspecialchars($cat['slug']) ?>'">
                        <div class="category-icon-wrap" style="background-color: <?= htmlspecialchars($cat['color']) ?>;">
                            <i class="fas <?= htmlspecialchars($cat['icon']) ?>"></i>
                        </div>
                        <h4><?= htmlspecialchars($cat['name']) ?></h4>
                        <div style="font-size: 13px; color: var(--text-secondary); margin-top: 6px; font-weight: 500;">
                            <?= $cat['count'] ?> Event<?= $cat['count'] !== 1 ? 's' : '' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 5: UPCOMING EVENTS -->
    <?php if (!empty($upcomingEvents)): ?>
    <section class="container" style="padding: 80px 0;">
        <div class="section-title-wrap" style="margin-bottom: 40px;">
            <div>
                <h2>All Upcoming Events</h2>
                <p>Browse the full list of campus events and register before spots fill up.</p>
            </div>
        </div>

        <div class="grid-cards">
            <?php foreach ($upcomingEvents as $event): 
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
        </div>
        
        <div style="text-align: center; margin-top: 48px;">
            <a href="events.php" class="btn btn-outline" style="min-width: 200px;">View All Programmes</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- SECTION 9: STATISTICS SECTION -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($totalEvents) ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($totalStudents) ?></div>
                    <div class="stat-label">Students Enrolled</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($totalOrganizers) ?></div>
                    <div class="stat-label">Organizers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($totalRegistrations) ?></div>
                    <div class="stat-label">Registrations</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION FOR SCROLL TARGET -->
    <section id="about" style="padding: 80px 0; background-color: var(--white); border-bottom: 1px solid var(--border);">
        <div class="container" style="max-width: 800px; text-align: center;">
            <span class="hero-tagline" style="background-color: var(--bg-secondary); color: var(--accent-blue);">About UKMInvolve</span>
            <h2 style="font-size: 32px; margin-top: 16px; margin-bottom: 20px;">Empowering Student Engagement</h2>
            <p style="color: var(--text-secondary); font-size: 16px; line-height: 1.8;">
                UKMInvolve is a dedicated platform designed to centralize and simplify student participation in campus life at Universiti Kebangsaan Malaysia. By bridging the gap between organizers and students, we make it seamless to find activities, track co-curricular points, and achieve badges to honor your campus involvement.
            </p>
        </div>
    </section>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

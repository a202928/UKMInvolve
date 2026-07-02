<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'saved-events';

$studentId = $_SESSION['user_id'] ?? '';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Events | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Saved Events</h1>
                    <p>Manage the events you have bookmarked for later.</p>
                </div>
            </div>

            <div class="dashboard-card-wrap">
                <div class="grid-cards">
                    <?php if (count($savedEventsList) > 0): ?>
                        <?php foreach ($savedEventsList as $program): 
                            $seatsLeft = max(0, $program['capacity'] - $program['participants']);
                            $isFull = $seatsLeft <= 0;
                            $badgeBg = $isFull ? '#ef4444' : '#10b981';
                            $badgeText = $isFull ? 'Full' : 'Open';
                        ?>
                            <div class="event-card" id="event-card-<?= $program['id'] ?>">
                                <div class="event-img-wrap">
                                    <span class="event-badge" style="background-color: <?= $badgeBg ?>;"><?= $badgeText ?></span>
                                    <img src="<?= htmlspecialchars(getImagePath($program['image'])) ?>" alt="<?= htmlspecialchars($program['title']) ?>" class="event-img">
                                </div>
                                <div class="event-card-body">
                                    <div class="event-category-organizer">
                                        <span class="event-category">
                                            <i class="fas <?= htmlspecialchars($program['category_icon']) ?>" style="color: <?= htmlspecialchars($program['category_color']) ?>; margin-right: 4px;"></i>
                                            <?= htmlspecialchars($program['category']) ?>
                                        </span>
                                        <?php if (!empty($program['penganjur_id'])): ?>
                                            <a href="organizer-profile.php?id=<?= urlencode($program['penganjur_id']) ?>" class="event-organizer" style="color: var(--accent-blue); text-decoration: none; font-weight: 700;" title="View Organizer Profile"><?= htmlspecialchars($program['organizer']) ?></a>
                                        <?php else: ?>
                                            <span class="event-organizer" title="<?= htmlspecialchars($program['organizer']) ?>"><?= htmlspecialchars($program['organizer']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3><?= htmlspecialchars($program['title']) ?></h3>
                                    
                                    <div class="event-meta-list">
                                        <div class="event-meta-item">
                                            <i class="far fa-calendar-alt"></i>
                                            <span><?= htmlspecialchars($program['date']) ?></span>
                                        </div>
                                        <div class="event-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($program['location']) ?></span>
                                        </div>
                                    </div>

                                    <div class="event-card-footer" style="display: flex; gap: 8px;">
                                        <a href="event-details.php?id=<?= $program['id'] ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; border-radius: 999px;">View Details</a>
                                        <button class="btn btn-outline btn-sm" style="border-radius: 999px; padding: 0 16px; color: #ef4444; border-color: #fca5a5;" onclick="unsaveEvent(<?= $program['id'] ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 60px 40px; border: 1px dashed var(--border); border-radius: var(--radius-md); background: var(--bg-main);">
                            <i class="far fa-heart" style="font-size: 36px; color: var(--text-muted); margin-bottom: 12px;"></i>
                            <h3 style="font-size: 16px; font-weight: 700;">No saved events</h3>
                            <p style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">Explore campus activities and save them here for later.</p>
                            <a href="events.php" class="btn btn-primary" style="margin-top: 16px; border-radius: 999px;">Browse Events</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    function unsaveEvent(programId) {
        if (!confirm('Remove this event from saved list?')) return;
        
        fetch('save-event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ program_id: programId, action: 'unsave' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('event-card-' + programId);
                if (card) {
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        // check if empty
                        const cards = document.querySelectorAll('.event-card');
                        if (cards.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                }
            } else {
                alert('Error removing event.');
            }
        });
    }
    </script>
</body>
</html>

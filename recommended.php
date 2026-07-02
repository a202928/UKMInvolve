<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'recommended';

$interestList = [];
if (db()->isConfigured()) {
    foreach (categories()->listAll(true) as $cat) {
        $interestList[$cat['slug']] = [$cat['nama'], $cat['icon'] ?? 'fa-layer-group'];
    }
}

if (empty($interestList)) {
    $interestList = [
        'kepimpinan' => ['Kepimpinan', 'fa-trophy'],
        'teknologi' => ['Teknologi', 'fa-code'],
        'komuniti' => ['Khidmat Komuniti', 'fa-heart'],
    ];
}

$studentId = $_SESSION['user_id'] ?? '';
$successMsg = '';

// Handle saving interests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedInterests = $_POST['interests'] ?? [];
    if (count($selectedInterests) < 1) {
        $errorMessage = 'Please select at least 1 interest.';
    } elseif (count($selectedInterests) > 3) {
        $errorMessage = 'You can only select up to 3 interests.';
    } else {
        if (interests()->saveInterests($studentId, $selectedInterests)) {
            users()->awardPoints($studentId, 'interest');
            $_SESSION['success_message'] = 'Your interests have been saved and updated successfully!';
            header('Location: recommended.php');
            exit();
        } else {
            $errorMessage = 'Failed to save interests.';
        }
    }
} 

// Load interests from DB
$selectedInterests = interests()->getStudentInterestSlugs($studentId);
if (empty($selectedInterests)) {
    // Fallback default interests if none saved yet
    $selectedInterests = array_slice(array_keys($interestList), 0, 3);
}

$rawPrograms = [];
if (db()->isConfigured()) {
    // Show active events only
    $rawPrograms = programs()->listActiveWithCategory();
}

// Fetch data for the smart recommendation algorithm
$studentData = [];
$followedOrganizers = [];
$historyFreq = [];

if (db()->isConfigured() && $studentId) {
    $studentData = users()->findById($studentId) ?: [];
    // Fetch followed organizers
    $followedRes = db()->select('followed_organizers', '?select=organizer_id&user_id=eq.' . $studentId);
    if ($followedRes['ok'] && !empty($followedRes['data'])) {
        $followedOrganizers = array_column($followedRes['data'], 'organizer_id');
    }

    // Calculate history frequency
    $regs = registrations()->listByStudent($studentId);
    foreach ($regs as $reg) {
        $slug = $reg['program']['kategori']['slug'] ?? null;
        if ($slug) {
            $historyFreq[$slug] = ($historyFreq[$slug] ?? 0) + 1;
        }
    }
}

// SMART RECOMMENDATION ALGORITHM (Centralized)
$scoredPrograms = [];

foreach ($rawPrograms as $rawRow) {
    $matchResult = programs()->calculateMatchScore($rawRow, $studentData, $selectedInterests, $followedOrganizers, $historyFreq);
    
    if ($matchResult['score'] > 0) {
        $formatted = programs()->toRecommendedRow($rawRow);
        $formatted['match_score'] = $matchResult['score'];
        $formatted['tags'] = $matchResult['tags'];
        $scoredPrograms[] = $formatted;
    }
}

// Sort by score descending
usort($scoredPrograms, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

// Enforce that we only show events that have a decent match (score >= 40 indicates at least an interest or history match)
// UNLESS they literally picked nothing, in which case we show highest score events.
if (!empty($selectedInterests)) {
    $recommendedPrograms = array_filter($scoredPrograms, function($p) {
        return $p['match_score'] >= 40; 
    });
} else {
    $recommendedPrograms = $scoredPrograms;
}

// Slice top 12 recommendations
$recommendedPrograms = array_slice($recommendedPrograms, 0, 12);

$studentInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>For You | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .interests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .interest-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px;
            background: var(--white);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
            user-select: none;
        }
        .interest-card input {
            display: none;
        }
        .interest-card:hover {
            border-color: var(--accent-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        .interest-card.selected {
            background: #eff6ff;
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            font-weight: 700;
        }
        .interest-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: #eff6ff;
            color: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            transition: var(--transition);
        }
        .interest-card.selected .interest-icon {
            background: var(--accent-blue);
            color: var(--white);
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <a href="dashboard_pelajar.php" style="display:inline-flex; align-items:center; gap:8px; color:var(--accent-blue); text-decoration:none; font-weight:700; font-size:14px; background:var(--white); padding:8px 16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); transition:var(--transition);"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Recommended Events</h1>
                    <p>Choose your interests and get personalized campus event suggestions.</p>
                </div>
            </div>

            <!-- CHOOSE INTERESTS -->
            <form method="POST" class="dashboard-card-wrap" id="interestForm">
                <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Select Your Interests</h2>
                <p style="font-size: 14px; color: var(--text-secondary);">Customize your feed by selecting co-curricular categories that you are interested in.</p>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert-banner alert-banner-success" style="margin-top: 16px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-circle-check" style="font-size:18px; color:#10b981;"></i>
                            <span><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert-banner alert-banner-danger" style="margin-top: 16px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-circle-exclamation" style="font-size:18px; color:#ef4444;"></i>
                            <span><?= htmlspecialchars($errorMessage) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="interests-grid">
                    <?php foreach ($interestList as $id => $data): ?>
                        <?php $checked = in_array($id, $selectedInterests); ?>
                        <label class="interest-card <?= $checked ? 'selected' : '' ?>">
                            <input type="checkbox" name="interests[]" value="<?= $id ?>" <?= $checked ? 'checked' : '' ?>>
                            <div class="interest-icon"><i class="fas <?= $data[1] ?>"></i></div>
                            <span><?= $data[0] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <span style="font-size: 14px; color: var(--text-secondary); font-weight: 600;" id="selectedCount">
                        <?= count($selectedInterests) ?> interest(s) selected
                    </span>
                    <button class="btn btn-primary" type="submit" style="border-radius: 999px;">
                        <i class="fas fa-wand-magic-sparkles" style="margin-right: 6px;"></i> Update Recommendations
                    </button>
                </div>
            </form>

            <!-- RECOMMENDED EVENTS -->
            <div class="dashboard-card-wrap">
                <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Tailored Suggestions For You</h2>
                <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">Events matching your selected co-curricular preferences.</p>

                <div class="grid-cards">
                    <?php if (count($recommendedPrograms) > 0): ?>
                        <?php foreach ($recommendedPrograms as $program): 
                            $seatsLeft = max(0, $program['capacity'] - $program['participants']);
                            $isFull = $seatsLeft <= 0;
                            $badgeBg = $isFull ? '#ef4444' : '#10b981';
                            $badgeText = $isFull ? 'Full' : 'Open';
                        ?>
                            <div class="event-card">
                                <div class="event-img-wrap">
                                    <span class="event-badge" style="background-color: <?= $badgeBg ?>;"><?= $badgeText ?></span>
                                    <img src="<?= htmlspecialchars(getImagePath($program['image'])) ?>" alt="<?= htmlspecialchars($program['title']) ?>" class="event-img">
                                </div>
                                <div class="event-card-body">
                                    <div class="event-category-organizer" style="display:flex; justify-content:space-between; align-items:center;">
                                        <span class="event-category">
                                            <i class="fas <?= htmlspecialchars($program['category_icon'] ?? 'fa-layer-group') ?>" style="color: <?= htmlspecialchars($program['category_color'] ?? '#5b8def') ?>; margin-right: 4px;"></i>
                                            <?= htmlspecialchars($interestList[$program['category']][0] ?? $program['category']) ?>
                                        </span>
                                        <?php if (!empty($program['tags'])): ?>
                                            <div style="display:flex; gap:4px;">
                                                <?php foreach ($program['tags'] as $tag): ?>
                                                    <span style="font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; background:<?= htmlspecialchars($tag['color']) ?>15; color:<?= htmlspecialchars($tag['color']) ?>;">
                                                        <i class="fas <?= htmlspecialchars($tag['icon']) ?>"></i> <?= htmlspecialchars($tag['text']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <h3 style="height: 48px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-top: 8px;"><?= htmlspecialchars($program['title']) ?></h3>
                                    
                                    <div class="event-meta-list" style="margin-top: 12px;">
                                        <div class="event-meta-item">
                                            <i class="far fa-calendar-alt"></i>
                                            <span><?= htmlspecialchars($program['date']) ?></span>
                                        </div>
                                        <div class="event-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($program['location']) ?></span>
                                        </div>
                                        <div class="event-meta-item">
                                            <i class="fas fa-coins"></i>
                                            <span>+<?= htmlspecialchars($program['points']) ?> Points</span>
                                        </div>
                                    </div>

                                    <div class="event-card-footer">
                                        <a href="event-details.php?id=<?= $program['id'] ?>" class="btn btn-primary btn-sm" style="width: 100%; text-align: center; border-radius: 999px;">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 60px 40px; border: 1px dashed var(--border); border-radius: var(--radius-md); background: var(--bg-main);">
                            <i class="fas fa-lightbulb" style="font-size: 36px; color: var(--text-muted); margin-bottom: 12px;"></i>
                            <h3 style="font-size: 16px; font-weight: 700;">No recommendations yet</h3>
                            <p style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">Please select at least one interest above to see suggestions.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    document.querySelectorAll('.interest-card').forEach(card => {
        card.addEventListener('click', function (e) {
            e.preventDefault(); // PREVENT DOUBLE TOGGLE BY BROWSER
            
            const input = card.querySelector('input');
            const checkedCount = document.querySelectorAll('.interest-card input:checked').length;
            
            if (!input.checked && checkedCount >= 3) {
                alert('You can only select up to 3 interests.');
                return;
            }
            
            input.checked = !input.checked;
            card.classList.toggle('selected', input.checked);
            
            // Update the count text
            const newCount = document.querySelectorAll('.interest-card input:checked').length;
            document.getElementById('selectedCount').textContent = newCount + ' interest(s) selected';
        });
    });
    
    document.getElementById('interestForm').addEventListener('submit', function(e) {
        const count = document.querySelectorAll('.interest-card input:checked').length;
        if (count < 1) {
            e.preventDefault();
            alert('Please select at least 1 interest.');
        }
    });
    </script>
</body>
</html>
</body>
</html>

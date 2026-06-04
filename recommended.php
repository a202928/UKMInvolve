<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'recommended';

$interestList = [];
if (db()->isConfigured()) {
    foreach (categories()->listAll() as $cat) {
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

$defaultInterests = array_slice(array_keys($interestList), 0, 3);
$selectedInterests = $_POST['interests'] ?? $defaultInterests;

$programs = [];
if (db()->isConfigured()) {
    foreach (programs()->listWithCategory() as $row) {
        $programs[] = programs()->toRecommendedRow($row);
    }
}

$recommendedPrograms = array_filter($programs, function ($program) use ($selectedInterests) {
    return in_array($program['category'], $selectedInterests, true);
});

$studentInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>For You | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;--muted:#6b7280;--text:#111827}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
.dashboard-wrapper{height:100vh;display:grid;grid-template-columns:240px 1fr;background:var(--page)}
.sidebar{height:100vh;background:#fff;border-right:1px solid var(--border);padding:28px 20px;display:flex;flex-direction:column;justify-content:space-between}
.sidebar-header{display:flex;align-items:center;gap:12px;margin-bottom:30px}
.sidebar-logo-wrap{width:38px;height:38px;border-radius:14px;background:#eaf4ff;display:flex;align-items:center;justify-content:center}
.sidebar-logo{width:28px;height:28px}
.sidebar-title{font-size:19px;font-weight:800}
.sidebar-label{font-size:11px;color:#9ca3af;text-transform:uppercase;margin-bottom:10px;padding-left:8px}
.sidebar-nav{display:flex;flex-direction:column;gap:8px}
.sidebar-link{padding:11px 12px;border-radius:14px;display:flex;gap:12px;align-items:center;color:#374151;font-weight:500}
.sidebar-link.active,.sidebar-link:hover{background:#eff6ff;color:#2563eb;font-weight:700}
.logout-link{color:#f97316}
.user-profile{display:flex;align-items:center;gap:10px;background:#f8fbff;border:1px solid var(--border);border-radius:16px;padding:12px}
.user-avatar{width:38px;height:38px;border-radius:50%;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.main-section{height:100vh;overflow-y:auto;padding:28px}
.page-header{margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{color:var(--muted);font-size:14px;margin-top:4px}
.card{background:#fff;border:1px solid var(--border);border-radius:26px;padding:22px;box-shadow:0 8px 20px rgba(37,99,235,.06);margin-bottom:22px}
.card h2{font-size:22px;margin-bottom:8px}
.card p{color:var(--muted);font-size:14px}
.interests-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px;margin-top:18px}
.interest-card{border:1px solid var(--border);border-radius:18px;padding:15px;background:#fff;cursor:pointer;display:flex;align-items:center;gap:12px}
.interest-card input{display:none}
.interest-card.selected{background:#eff6ff;border-color:var(--primary);color:var(--dark);font-weight:700}
.interest-icon{width:42px;height:42px;border-radius:14px;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center}
.save-row{display:flex;justify-content:space-between;align-items:center;margin-top:18px}
.btn-save{border:none;background:var(--primary);color:#fff;padding:11px 18px;border-radius:999px;font-weight:800;cursor:pointer}
.selected-text{font-size:13px;color:var(--muted)}
.program-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:18px}
.program-card{background:#fff;border:1px solid var(--border);border-radius:24px;overflow:hidden;box-shadow:0 8px 20px rgba(37,99,235,.06)}
.program-image{height:160px;background:#dbeafe;position:relative}
.program-image img{width:100%;height:100%;object-fit:cover}
.match-badge{position:absolute;top:12px;right:12px;background:#10b981;color:#fff;padding:7px 12px;border-radius:999px;font-size:12px;font-weight:800}
.program-content{padding:18px}
.program-content h3{font-size:18px;margin-bottom:8px}
.program-meta{display:flex;flex-direction:column;gap:7px;color:var(--muted);font-size:13px;margin:12px 0}
.program-meta i{color:var(--dark);width:18px}
.tag{display:inline-block;background:#eff6ff;color:#2563eb;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;margin-bottom:10px}
.btn-view{width:100%;border:none;background:var(--primary);color:white;padding:10px;border-radius:999px;font-weight:800;cursor:pointer}
.empty{background:white;border:1px solid var(--border);border-radius:24px;padding:40px;text-align:center;color:var(--muted)}
</style>
</head>

<body>
<div class="dashboard-wrapper">

<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <div class="sidebar-logo-wrap"><img src="UKM.png" class="sidebar-logo"></div>
            <h3 class="sidebar-title">UKMInvolve</h3>
        </div>

        <p class="sidebar-label">Menu</p>
        <nav class="sidebar-nav">
            <a href="dashboard_pelajar.php" class="sidebar-link"><i class="fas fa-house"></i> Home</a>
            <a href="search.php" class="sidebar-link"><i class="fas fa-magnifying-glass"></i> Search</a>
            <a href="recommended.php" class="sidebar-link active"><i class="fas fa-lightbulb"></i> For You</a>
            <a href="rekod-penyertaan.php" class="sidebar-link"><i class="fas fa-clock-rotate-left"></i> History</a>
            <a href="logout.php" class="sidebar-link logout-link"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </div>

    <div class="user-profile">
        <div class="user-avatar"><?= htmlspecialchars($studentInitial) ?></div>
        <div>
            <h4><?= htmlspecialchars($_SESSION['nama'] ?? 'Pelajar') ?></h4>
            <p><?= htmlspecialchars($_SESSION['emel'] ?? '') ?></p>
        </div>
    </div>
</aside>

<main class="main-section">

    <div class="page-header">
        <h1>For You</h1>
        <p>Choose your interests first, then UKMInvolve will recommend suitable events for you.</p>
    </div>

    <form method="POST" class="card" id="interestForm">
        <h2>Choose Your Interests</h2>
        <p>Select one or more categories to personalize your recommendations.</p>

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

        <div class="save-row">
            <span class="selected-text">
                <?= count($selectedInterests) ?> interest(s) selected
            </span>
            <button class="btn-save" type="submit">
                <i class="fas fa-wand-magic-sparkles"></i> Generate Recommendations
            </button>
        </div>
    </form>

    <div class="page-header">
        <h1>Recommended Events</h1>
        <p>Based on your selected interests.</p>
    </div>

    <div class="program-grid">
        <?php if (count($recommendedPrograms) > 0): ?>
            <?php foreach ($recommendedPrograms as $program): ?>
                <div class="program-card">
                    <div class="program-image">
                        <img src="images/<?= $program['image'] ?>" alt="<?= $program['title'] ?>">
                        <div class="match-badge">
                            <i class="fas fa-bolt"></i> Match
                        </div>
                    </div>

                    <div class="program-content">
                        <span class="tag"><?= htmlspecialchars($interestList[$program['category']][0] ?? $program['category']) ?></span>
                        <h3><?= $program['title'] ?></h3>

                        <div class="program-meta">
                            <div><i class="fas fa-calendar"></i> <?= $program['date'] ?></div>
                            <div><i class="fas fa-location-dot"></i> <?= $program['location'] ?></div>
                            <div><i class="fas fa-coins"></i> +<?= $program['points'] ?> Points</div>
                        </div>

                        <a href="daftar-program-form.php?id=<?= (int) $program['id'] ?>" class="btn-view" style="display:block;text-align:center;line-height:1.2;">View Event</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <h3>No recommendation yet</h3>
                <p>Please choose at least one interest above.</p>
            </div>
        <?php endif; ?>
    </div>

</main>
</div>

<script>
document.querySelectorAll('.interest-card').forEach(card => {
    card.addEventListener('click', function () {
        setTimeout(() => {
            card.classList.toggle('selected', card.querySelector('input').checked);
        }, 10);
    });
});
</script>

</body>
</html>
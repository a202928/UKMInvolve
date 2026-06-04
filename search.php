<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'search';

function getImagePath($filename) {
    $paths = [$filename, "images/" . $filename, "images/events/" . $filename];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return "";
}

$programs = [];
if (db()->isConfigured()) {
    foreach (programs()->listWithCategory() as $row) {
        $programs[] = programs()->toStudentSearchRow($row);
    }
}

$categories = array_values(array_unique(array_column($programs, 'category')));
sort($categories);

$searchQuery = $_GET['search'] ?? '';
$selectedCategory = $_GET['category'] ?? 'semua';
$selectedStatus = $_GET['status'] ?? 'semua';
$selectedDate = $_GET['date'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';

$filteredPrograms = array_filter($programs, function($program) use ($searchQuery, $selectedCategory, $selectedStatus, $selectedDate) {
    if ($searchQuery && stripos($program['title'], $searchQuery) === false && stripos($program['description'], $searchQuery) === false) return false;
    if ($selectedCategory !== 'semua' && $program['category'] !== $selectedCategory) return false;
    if ($selectedStatus !== 'semua' && $program['status'] !== $selectedStatus) return false;
    if ($selectedDate && $program['date'] !== $selectedDate) return false;
    return true;
});

usort($filteredPrograms, function($a, $b) use ($sortBy) {
    if ($sortBy === 'oldest') return strtotime($a['date']) <=> strtotime($b['date']);
    if ($sortBy === 'rating') return $b['rating'] <=> $a['rating'];
    if ($sortBy === 'popular') return $b['participants'] <=> $a['participants'];
    return strtotime($b['date']) <=> strtotime($a['date']);
});

$menu = [
    'dashboard_pelajar' => ['Home', 'fa-house'],
    'search' => ['Search', 'fa-magnifying-glass'],
    'recommended' => ['For You', 'fa-lightbulb'],
    'rekod-penyertaan' => ['History', 'fa-clock-rotate-left'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Program | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--primary-dark:#2563eb;--soft:#eff6ff;
    --text:#111827;--muted:#6b7280;--border:#dbeafe;--orange:#f97316;
}
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#dceeff,#f8fbff,#edf6ff);height:100vh;overflow:hidden;color:var(--text)}
a{text-decoration:none;color:inherit}
button,input,select{font-family:inherit}

.dashboard-wrapper{width:100%;height:100vh;display:grid;grid-template-columns:240px 1fr;background:var(--page);overflow:hidden}

/* SIDEBAR */
.sidebar{height:100vh;position:sticky;top:0;background:#fff;border-right:1px solid var(--border);padding:28px 20px;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden}
.sidebar-top{display:flex;flex-direction:column;gap:30px}
.sidebar-header{display:flex;align-items:center;gap:12px}
.sidebar-logo-wrap{width:38px;height:38px;border-radius:14px;background:#eaf4ff;display:flex;align-items:center;justify-content:center}
.sidebar-logo{width:28px;height:28px;object-fit:contain}
.sidebar-title{font-size:19px;font-weight:800}
.sidebar-label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;padding-left:8px}
.sidebar-nav{display:flex;flex-direction:column;gap:8px}
.sidebar-link{color:#374151;font-size:15px;font-weight:500;padding:11px 12px;border-radius:14px;display:flex;align-items:center;gap:12px;transition:.25s}
.sidebar-link i{width:18px;text-align:center}
.sidebar-link:hover,.sidebar-link.active{background:#eff6ff;color:#2563eb;font-weight:700}
.logout-link{color:#f97316}
.logout-link:hover{background:#fff7ed;color:#f97316}
.user-profile{display:flex;align-items:center;gap:10px;background:#f8fbff;border:1px solid var(--border);border-radius:16px;padding:12px}
.user-avatar{width:38px;height:38px;border-radius:50%;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.user-info h4{font-size:14px}
.user-info p{font-size:12px;color:var(--muted)}

/* MAIN */
.main-section{height:100vh;overflow-y:auto;padding:28px;background:var(--page)}
.main-section::-webkit-scrollbar{width:8px}
.main-section::-webkit-scrollbar-thumb{background:#bfdbfe;border-radius:999px}

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{color:var(--muted);font-size:14px;margin-top:4px}
.total-card{background:#fff;border:1px solid var(--border);border-radius:20px;padding:14px 18px;text-align:right;box-shadow:0 8px 20px rgba(37,99,235,.05)}
.total-card h3{color:var(--primary-dark);font-size:24px}
.total-card p{font-size:12px;color:var(--muted)}

/* SEARCH PANEL */
.search-panel{background:#fff;border:1px solid var(--border);border-radius:26px;padding:22px;box-shadow:0 10px 25px rgba(37,99,235,.06);margin-bottom:22px}
.search-main{display:flex;align-items:center;gap:12px;background:#f8fbff;border:1px solid var(--border);border-radius:999px;padding:14px 18px;margin-bottom:18px}
.search-main i{color:#9ca3af}
.search-main input{border:none;outline:none;background:transparent;width:100%;font-size:15px}

.filters-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.filter-group label{font-size:13px;font-weight:700;margin-bottom:7px;display:block}
.filter-group select,.filter-group input{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:14px;background:#fff;outline:none}
.filter-group select:focus,.filter-group input:focus{border-color:var(--primary)}

.quick-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.quick-filter{padding:9px 14px;border-radius:999px;border:1px solid var(--border);background:#fff;color:#374151;font-size:13px;font-weight:700;cursor:pointer}
.quick-filter.active,.quick-filter:hover{background:var(--primary);color:#fff;border-color:var(--primary)}

.sort-row{display:flex;justify-content:space-between;align-items:center;margin-top:18px;padding-top:18px;border-top:1px solid var(--border);gap:12px;flex-wrap:wrap}
.sort-buttons{display:flex;gap:8px;flex-wrap:wrap}
.sort-btn{padding:8px 14px;border:none;border-radius:999px;background:#eff6ff;color:#2563eb;font-weight:700;cursor:pointer}
.sort-btn.active{background:var(--primary);color:#fff}
.result-count{font-size:13px;color:var(--muted);font-weight:700}

/* GRID */
.programs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
.program-card{background:#fff;border:1px solid var(--border);border-radius:24px;overflow:hidden;box-shadow:0 8px 20px rgba(37,99,235,.06);transition:.25s}
.program-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(37,99,235,.12)}
.program-image{height:160px;position:relative;overflow:hidden;background:#dbeafe}
.program-image img{width:100%;height:100%;object-fit:cover}
.program-badges{position:absolute;top:12px;right:12px;display:flex;flex-direction:column;gap:8px}
.badge{padding:6px 11px;border-radius:999px;font-size:11px;font-weight:800;background:rgba(255,255,255,.92)}
.status-available{background:#10b981;color:#fff}
.status-full{background:#ef4444;color:#fff}

.program-content{padding:18px}
.program-content h3{font-size:18px;line-height:1.35;margin-bottom:8px}
.program-desc{font-size:13px;color:var(--muted);line-height:1.5;margin-bottom:14px}
.meta-list{display:flex;flex-direction:column;gap:7px;font-size:13px;color:var(--muted);margin-bottom:14px}
.meta-list i{color:var(--primary-dark);width:18px}

.program-stats{display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid var(--border);gap:14px}
.availability{flex:1}
.availability-text{font-size:12px;font-weight:800;margin-bottom:6px}
.availability-bar{height:7px;background:#e5e7eb;border-radius:999px;overflow:hidden}
.availability-fill{height:100%;border-radius:999px}
.high{background:#10b981}.medium{background:#f59e0b}.low{background:#ef4444}
.rating{color:#f59e0b;font-weight:800;font-size:13px}

.actions{display:flex;gap:10px;margin-top:14px}
.btn-detail,.btn-register{flex:1;padding:10px;border-radius:999px;font-size:13px;font-weight:800;cursor:pointer;text-align:center}
.btn-detail{border:1px solid var(--primary);color:var(--primary);background:#fff}
.btn-register{border:none;background:var(--primary);color:#fff}
.btn-disabled{background:#e5e7eb;color:#9ca3af;cursor:not-allowed}

.empty-state{grid-column:1/-1;background:#fff;border:1px solid var(--border);border-radius:24px;text-align:center;padding:50px;color:var(--muted)}
.empty-state i{font-size:42px;color:#bfdbfe;margin-bottom:14px}
.empty-state h3{color:var(--text);margin-bottom:8px}

@media(max-width:900px){
    body{overflow:auto}
    .dashboard-wrapper{grid-template-columns:1fr;height:auto}
    .sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
    .sidebar-nav{flex-direction:row;overflow-x:auto}
    .sidebar-link{white-space:nowrap}
    .user-profile{display:none}
    .main-section{height:auto;overflow:visible}
    .filters-row{grid-template-columns:1fr}
}
</style>
</head>

<body>
<div class="dashboard-wrapper">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-header">
                <div class="sidebar-logo-wrap">
                    <img src="UKM.png" alt="UKM Logo" class="sidebar-logo">
                </div>
                <h3 class="sidebar-title">UKMInvolve</h3>
            </div>

            <div>
                <p class="sidebar-label">Menu</p>
                <nav class="sidebar-nav">
                    <?php foreach ($menu as $page => $item): ?>
                        <a href="<?= $page ?>.php"
                           class="sidebar-link <?= ($activePage === $page) ? 'active' : '' ?> <?= ($page === 'logout') ? 'logout-link' : '' ?>">
                            <i class="fas <?= $item[1] ?>"></i>
                            <?= $item[0] ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <div class="user-profile">
            <div class="user-avatar">P</div>
            <div class="user-info">
                <h4>Pelajar</h4>
                <p>UKM Account</p>
            </div>
        </div>
    </aside>

    <main class="main-section">

        <div class="page-header">
            <div>
                <h1>Search Events</h1>
                <p>Find programmes that match your interest and event type.</p>
            </div>

            <div class="total-card">
                <h3><?= count($filteredPrograms) ?></h3>
                <p>Events Found</p>
            </div>
        </div>

        <div class="quick-filters">
            <button class="quick-filter <?= $selectedCategory === 'semua' ? 'active' : '' ?>" onclick="setFilter('category','semua')">All</button>
            <?php foreach ($categories as $category): ?>
                <button class="quick-filter <?= $selectedCategory === $category ? 'active' : '' ?>"
                        onclick="setFilter('category','<?= $category ?>')">
                    <?= $category ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form method="GET" class="search-panel">
            <div class="search-main">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search event name or keyword..." value="<?= htmlspecialchars($searchQuery) ?>">
            </div>

            <div class="filters-row">
                <div class="filter-group">
                    <label>Event Type</label>
                    <select name="category" onchange="this.form.submit()">
                        <option value="semua">All Types</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                                <?= $category ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="semua" <?= $selectedStatus === 'semua' ? 'selected' : '' ?>>All Status</option>
                        <option value="available" <?= $selectedStatus === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="full" <?= $selectedStatus === 'full' ? 'selected' : '' ?>>Full</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
                </div>

                <div class="filter-group">
                    <label>Sort By</label>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rating</option>
                        <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    </select>
                </div>
            </div>

            <div class="sort-row">
                <div class="sort-buttons">
                    <button type="button" class="sort-btn <?= $sortBy === 'newest' ? 'active' : '' ?>" onclick="setSort('newest')">Newest</button>
                    <button type="button" class="sort-btn <?= $sortBy === 'rating' ? 'active' : '' ?>" onclick="setSort('rating')">Rating</button>
                    <button type="button" class="sort-btn <?= $sortBy === 'popular' ? 'active' : '' ?>" onclick="setSort('popular')">Popular</button>
                </div>
                <span class="result-count"><?= count($filteredPrograms) ?> result(s)</span>
            </div>

            <input type="hidden" name="sort" id="sortField" value="<?= $sortBy ?>">
        </form>

        <div class="programs-grid">
            <?php if (count($filteredPrograms) > 0): ?>
                <?php foreach ($filteredPrograms as $program): 
                    $imagePath = getImagePath($program['image']);
                    $availability = $program['capacity'] - $program['participants'];
                    $availabilityPercent = round(($availability / $program['capacity']) * 100);
                    $barClass = $availabilityPercent > 50 ? 'high' : ($availabilityPercent > 20 ? 'medium' : 'low');
                    $isFull = $program['status'] === 'full';
                ?>
                    <div class="program-card">
                        <div class="program-image">
                            <?php if ($imagePath): ?>
                                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($program['title']) ?>">
                            <?php endif; ?>

                            <div class="program-badges">
                                <span class="badge"><?= $program['category'] ?></span>
                                <span class="badge status-<?= $program['status'] ?>">
                                    <?= $isFull ? 'Full' : 'Available' ?>
                                </span>
                            </div>
                        </div>

                        <div class="program-content">
                            <h3><?= htmlspecialchars($program['title']) ?></h3>
                            <p class="program-desc"><?= htmlspecialchars($program['description']) ?></p>

                            <div class="meta-list">
                                <div><i class="fas fa-calendar"></i><?= date('d M Y', strtotime($program['date'])) ?> • <?= $program['time'] ?></div>
                                <div><i class="fas fa-location-dot"></i><?= htmlspecialchars($program['location']) ?></div>
                                <div><i class="fas fa-users"></i><?= $program['participants'] ?> / <?= $program['capacity'] ?> participants</div>
                                <div><i class="fas fa-coins"></i>+<?= $program['points'] ?> Points</div>
                            </div>

                            <div class="program-stats">
                                <div class="availability">
                                    <div class="availability-text"><?= $availability ?> slot available</div>
                                    <div class="availability-bar">
                                        <div class="availability-fill <?= $barClass ?>" style="width: <?= $availabilityPercent ?>%"></div>
                                    </div>
                                </div>

                                <div class="rating">
                                    <i class="fas fa-star"></i> <?= $program['rating'] ?>
                                </div>
                            </div>

                            <div class="actions">
                                <button class="btn-detail" onclick="alert('Program details: <?= htmlspecialchars($program['title']) ?>')">
                                    Details
                                </button>

                                <?php if ($isFull): ?>
                                    <button class="btn-register btn-disabled" disabled>Full</button>
                                <?php else: ?>
                                    <a href="daftar-program-form.php?id=<?= $program['id'] ?>" class="btn-register">Register</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Events Found</h3>
                    <p>Try changing the event type, status, date, or keyword.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
function setFilter(type, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(type, value);
    window.location.href = url.toString();
}

function setSort(value) {
    document.getElementById('sortField').value = value;
    document.querySelector('.search-panel').submit();
}
</script>

</body>
</html>
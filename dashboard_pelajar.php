<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'dashboard_pelajar';

$studentName   = $_SESSION['nama'] ?? 'Pelajar';
$currentPoints = (int) ($_SESSION['mata'] ?? 0);
$nextLevelPoints = 1000;
$progressPercent = min(100, ($currentPoints / $nextLevelPoints) * 100);
$studentInitial = strtoupper(substr($studentName, 0, 1));

// Real participation stats from DB
$joinedEvents  = 0;
$leaderboard   = [];
if (db()->isConfigured() && !empty($_SESSION['user_id'])) {
    $regs = registrations()->listByStudent($_SESSION['user_id']);
    $joinedEvents = count($regs);

    // Top 5 users by mata for leaderboard
    $topUsers = db()->select('users', '?select=id,nama,mata&peranan=eq.pelajar&order=mata.desc&limit=5');
    if ($topUsers['ok']) {
        foreach ($topUsers['data'] as $u) {
            $leaderboard[] = $u;
        }
    }
}

// Level calculation
$level = 1; $levelName = 'New Explorer';
if ($currentPoints >= 1200)      { $level = 5; $levelName = 'UKM Champion'; }
elseif ($currentPoints >= 800)   { $level = 4; $levelName = 'Active Achiever'; }
elseif ($currentPoints >= 500)   { $level = 3; $levelName = 'Campus Explorer'; }
elseif ($currentPoints >= 200)   { $level = 2; $levelName = 'Active Starter'; }

function getImagePath($filename) {
    $paths = [
        $filename,
        "images/" . $filename,
        "images/events/" . $filename
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return "";
}

$recommendedImage = getImagePath("program5.jpg");

$events = [];
if (db()->isConfigured()) {
    $rows = programs()->listWithCategory();
    $rows = array_slice($rows, 0, 4);
    foreach ($rows as $row) {
        $events[] = programs()->toDashboardEvent($row);
    }
}

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
    <title>Dashboard Pelajar | UKMInvolve</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --page: #f8fbff;
            --card: #ffffff;
            --primary: #5b8def;
            --primary-dark: #2563eb;
            --soft-blue: #eff6ff;
            --text: #111827;
            --muted: #6b7280;
            --border: #dbeafe;
            --orange: #f97316;
            --green: #10b981;
            --purple: #8b5cf6;
            --yellow: #facc15;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #dceeff 0%, #f8fbff 45%, #edf6ff 100%);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input {
            font-family: inherit;
        }

        .dashboard-wrapper {
            width: 100%;
            height: 100vh;
            display: grid;
            grid-template-columns: 240px 1fr;
            background: var(--page);
            overflow: hidden;
        }

        .sidebar {
            height: 100vh;
            position: sticky;
            top: 0;
            background: #ffffff;
            border-right: 1px solid var(--border);
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            z-index: 10;
        }

        .sidebar-top {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo-wrap {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            background: #eaf4ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        .sidebar-title {
            font-size: 19px;
            font-weight: 800;
            color: #111827;
        }

        .sidebar-label {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 10px;
            padding-left: 8px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-link {
            color: #374151;
            font-size: 15px;
            font-weight: 500;
            padding: 11px 12px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.25s ease;
        }

        .sidebar-link i {
            width: 18px;
            text-align: center;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
        }

        .logout-link {
            color: #f97316;
        }

        .logout-link:hover {
            background: #fff7ed;
            color: #f97316;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fbff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            padding: 12px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .user-info h4 {
            font-size: 14px;
        }

        .user-info p {
            font-size: 12px;
            color: var(--muted);
        }

        .main-section {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 28px;
            display: grid;
            grid-template-columns: 1.7fr 0.82fr;
            gap: 24px;
            background: var(--page);
        }

        .main-section::-webkit-scrollbar {
            width: 8px;
        }

        .main-section::-webkit-scrollbar-thumb {
            background: #bfdbfe;
            border-radius: 999px;
        }

        .left-content,
        .right-content {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .top-search {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .search-bar {
            flex: 1;
            background: white;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-bar i {
            color: #9ca3af;
        }

        .search-bar input {
            border: none;
            outline: none;
            width: 100%;
            background: transparent;
            font-size: 14px;
        }

        .top-icons {
            display: flex;
            gap: 10px;
        }

        .icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: white;
            color: #374151;
            cursor: pointer;
            position: relative;
        }

        .notification-dot {
            position: absolute;
            top: 9px;
            right: 10px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        .hero-calendar-row {
            display: grid;
            grid-template-columns: 330px 1fr;
            gap: 18px;
        }

        .recommend-card {
            background: linear-gradient(135deg, #7bb6ff, #5b8def);
            border-radius: 26px;
            padding: 24px;
            color: white;
            position: relative;
            overflow: hidden;
            height: 330px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 18px 38px rgba(91, 141, 239, 0.22);
        }

        .recommend-card::before {
            content: "";
            position: absolute;
            right: -55px;
            top: -60px;
            width: 210px;
            height: 210px;
            border-radius: 50%;
            background: rgba(255,255,255,0.13);
        }

        .recommend-text {
            position: relative;
            z-index: 2;
        }

        .recommend-label {
            font-size: 10px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .recommend-card h1 {
            font-size: 30px;
            line-height: 1.08;
            margin-bottom: 10px;
        }

        .recommend-card p {
            color: #eef6ff;
            line-height: 1.5;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .recommend-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #111827;
            color: white;
            border-radius: 999px;
            padding: 10px 15px;
            font-weight: 700;
            font-size: 13px;
        }

        .recommend-btn span {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: white;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .recommend-image {
            position: relative;
            z-index: 1;
            height: 105px;
            border-radius: 20px;
            overflow: hidden;
            background: rgba(255,255,255,0.18);
        }

        .recommend-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .calendar-card,
        .small-card,
        .event-card,
        .stats-card,
        .leaderboard-card {
            background: white;
            border: 1px solid var(--border);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.05);
        }

        .calendar-card {
            border-radius: 22px;
            padding: 18px;
            min-height: 330px;
        }

        .calendar-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .calendar-top h4 {
            font-size: 18px;
            margin-bottom: 2px;
        }

        .calendar-top p {
            font-size: 12px;
            color: var(--muted);
        }

        .calendar-control {
            display: flex;
            gap: 8px;
        }

        .calendar-control button {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 50%;
            background: #eff6ff;
            color: var(--primary-dark);
            cursor: pointer;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            text-align: center;
            font-size: 12px;
        }

        .calendar-grid div {
            padding: 7px 0;
            border-radius: 10px;
        }

        .calendar-head {
            color: var(--muted);
            font-weight: 700;
            font-size: 11px;
        }

        .calendar-day {
            cursor: pointer;
        }

        .calendar-day.today {
            background: var(--primary);
            color: white;
            font-weight: 800;
        }

        .calendar-day.selected {
            outline: 2px solid var(--primary);
            outline-offset: -2px;
            border-radius: 10px;
            font-weight: 800;
        }

        .calendar-day.event-date {
            background: #eff6ff;
            color: var(--primary-dark);
            font-weight: 800;
            position: relative;
        }

        .calendar-day.event-date::after {
            content: "";
            width: 5px;
            height: 5px;
            background: #5b8def;
            border-radius: 50%;
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
        }

        .calendar-events {
            margin-top: 12px;
            background: #f8fbff;
            border: 1px solid #eff6ff;
            border-radius: 14px;
            padding: 10px;
            font-size: 12px;
            color: var(--muted);
            min-height: 42px;
        }

        .calendar-events strong {
            color: var(--primary-dark);
        }

        .small-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .small-card {
            border-radius: 18px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .small-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .small-card p {
            font-size: 12px;
            color: var(--muted);
        }

        .small-card h4 {
            font-size: 15px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h3 {
            font-size: 24px;
        }

        .section-header a {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 700;
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .event-card {
            border-radius: 20px;
            overflow: hidden;
        }

        .event-image {
            height: 155px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 42px;
        }

        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-blue { background: linear-gradient(135deg, #bfdbfe, #93c5fd); }
        .event-sky { background: linear-gradient(135deg, #bae6fd, #7dd3fc); }
        .event-green { background: linear-gradient(135deg, #bbf7d0, #a7f3d0); }
        .event-soft { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }

        .event-fav {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.90);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            z-index: 2;
        }

        .event-body {
            padding: 16px;
        }

        .event-tag {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .event-body h4 {
            font-size: 17px;
            margin-bottom: 10px;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 12px;
        }

        .event-meta i {
            color: var(--primary-dark);
            margin-right: 6px;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .xp-pill {
            background: #eff6ff;
            color: var(--primary-dark);
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .mini-btn {
            border: none;
            background: var(--primary);
            color: white;
            padding: 9px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .profile-top {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #dbeafe;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .profile-name {
            font-weight: 700;
            font-size: 15px;
        }

        .stats-card,
        .leaderboard-card {
            border-radius: 22px;
            padding: 16px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .point-circle-wrap {
            display: flex;
            justify-content: center;
        }

        .point-circle {
            width: 112px;
            height: 112px;
            border-radius: 50%;
            background:
                radial-gradient(circle at center, #ffffff 56%, transparent 57%),
                conic-gradient(#5b8def <?= $progressPercent ?>%, #dbeafe 0);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .point-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stats-card h3 {
            text-align: center;
            font-size: 21px;
            margin-top: 8px;
        }

        .stats-card p {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
        }

        .color-stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
        }

        .color-stat {
            border-radius: 16px;
            padding: 12px;
            color: white;
        }

        .color-stat h4 {
            font-size: 17px;
        }

        .color-stat p {
            color: rgba(255,255,255,0.9);
            text-align: left;
            font-size: 11px;
            margin: 0;
        }

        .stat-blue { background: linear-gradient(135deg, #60a5fa, #2563eb); }
        .stat-green { background: linear-gradient(135deg, #34d399, #059669); }
        .stat-orange { background: linear-gradient(135deg, #fbbf24, #f97316); }
        .stat-purple { background: linear-gradient(135deg, #a78bfa, #7c3aed); }

        .mini-stat-box {
            background: #f8fbff;
            border-radius: 16px;
            padding: 12px;
            margin-top: 12px;
            border: 1px solid #eff6ff;
        }

        .mini-stat-box h4 {
            font-size: 13px;
            margin-bottom: 8px;
        }

        .progress-bg {
            width: 100%;
            height: 9px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-fill {
            width: <?= $progressPercent ?>%;
            height: 100%;
            background: linear-gradient(90deg, #93c5fd, #5b8def);
        }

        .bar-chart {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 10px;
            height: 60px;
            margin-top: 8px;
        }

        .bar {
            width: 100%;
            border-radius: 10px 10px 4px 4px;
        }

        .bar:nth-child(1) { background: #93c5fd; }
        .bar:nth-child(2) { background: #60a5fa; }
        .bar:nth-child(3) { background: #34d399; }
        .bar:nth-child(4) { background: #f97316; }
        .bar:nth-child(5) { background: #a78bfa; }

        .bar-labels {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--muted);
            margin-top: 8px;
        }

        .leader-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #dbeafe;
        }

        .leader-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .leader-rank {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #eff6ff;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
        }

        .leader-item.active .leader-rank {
            background: var(--primary);
            color: white;
        }

        .leader-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #dbeafe;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .leader-info h5 {
            font-size: 14px;
        }

        .leader-info p {
            font-size: 12px;
            color: var(--muted);
        }

        .leader-score {
            font-size: 13px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        @media (max-width: 1180px) {
            body {
                overflow: auto;
            }

            .dashboard-wrapper {
                height: auto;
                min-height: 100vh;
            }

            .main-section {
                height: auto;
                overflow: visible;
                grid-template-columns: 1fr;
            }

            .right-content {
                order: -1;
            }

            .hero-calendar-row {
                grid-template-columns: 1fr;
            }

            .recommend-card {
                height: auto;
                min-height: 260px;
            }
        }

        @media (max-width: 900px) {
            .dashboard-wrapper {
                grid-template-columns: 1fr;
            }

            .sidebar {
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .user-profile {
                display: none;
            }

            .sidebar-nav {
                flex-direction: row;
                overflow-x: auto;
            }

            .sidebar-link {
                white-space: nowrap;
            }

            .small-cards,
            .event-grid {
                grid-template-columns: 1fr;
            }
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
            <div class="user-avatar"><?= htmlspecialchars($studentInitial) ?></div>
            <div class="user-info">
                <h4><?= htmlspecialchars($studentName) ?></h4>
                <p>UKM Account</p>
            </div>
        </div>
    </aside>

    <main class="main-section">

        <div class="left-content">

            <div class="top-search">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search your event...">
                </div>

                <div class="top-icons">
                    <button class="icon-btn">
                        <i class="fas fa-envelope"></i>
                    </button>

                    <button class="icon-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
                </div>
            </div>

            <div class="hero-calendar-row">

                <div class="recommend-card">
                    <div class="recommend-text">
                        <div class="recommend-label">Recommended Event</div>
                        <h1>Discover Events</h1>
                        <p>Events matched with your interests. Join activities, collect points and unlock achievements.</p>

                        <a href="#" class="recommend-btn">
                            View Now
                            <span><i class="fas fa-arrow-right"></i></span>
                        </a>
                    </div>

                    <div class="recommend-image">
                        <?php if (!empty($recommendedImage)): ?>
                            <img src="<?= $recommendedImage ?>" alt="Recommended Event">
                        <?php else: ?>
                            <i class="fas fa-calendar-days"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="calendar-card">
                    <div class="calendar-top">
                        <div>
                            <h4 id="calendarMonthYear">January 2026</h4>
                            <p>Click event dates to view details</p>
                        </div>

                        <div class="calendar-control">
                            <button type="button" id="prevMonth">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button type="button" id="nextMonth">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="calendar-grid" id="calendarGrid"></div>

                    <div class="calendar-events" id="calendarEvents">
                        Click an event date to view event details.
                    </div>
                </div>

            </div>

            <div class="small-cards">
                <div class="small-card">
                    <div class="small-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p><?= $joinedEvents ?> events joined</p>
                        <h4>All Events</h4>
                    </div>
                </div>

                <div class="small-card">
                    <div class="small-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <p><?= $currentPoints ?> points earned</p>
                        <h4>My Points</h4>
                    </div>
                </div>

                <div class="small-card">
                    <div class="small-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div>
                        <p>Level <?= $level ?></p>
                        <h4><?= $levelName ?></h4>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <h3>Upcoming Events</h3>
                <a href="#">See all</a>
            </div>

            <div class="event-grid">
                <?php foreach ($events as $event): ?>
                    <?php $eventImage = getImagePath($event['image']); ?>

                    <div class="event-card">
                        <div class="event-image <?= $event['color'] ?>">
                            <?php if (!empty($eventImage)): ?>
                                <img src="<?= $eventImage ?>" alt="<?= $event['title'] ?>">
                            <?php else: ?>
                                <i class="fas <?= $event['icon'] ?>"></i>
                            <?php endif; ?>

                            <div class="event-fav">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>

                        <div class="event-body">
                            <span class="event-tag"><?= $event['category'] ?></span>
                            <h4><?= $event['title'] ?></h4>

                            <div class="event-meta">
                                <div><i class="fas fa-calendar"></i> <?= $event['date'] ?></div>
                                <div><i class="fas fa-location-dot"></i> <?= $event['location'] ?></div>
                            </div>

                            <div class="event-footer">
                                <span class="xp-pill">+<?= $event['points'] ?> Points</span>
                                <button class="mini-btn">View</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="right-content">

            <div class="profile-top">
                <div class="profile-avatar"><?= htmlspecialchars($studentInitial) ?></div>
                <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
            </div>

            <div class="stats-card">
                <div class="card-title">Statistic</div>

                <div class="point-circle-wrap">
                    <div class="point-circle">
                        <div class="point-avatar">😊</div>
                    </div>
                </div>

                <h3><?= $currentPoints ?> Points</h3>
                <p>Keep participating to increase your level and ranking!</p>

                <div class="color-stat-grid">
                    <div class="color-stat stat-blue">
                        <h4>Level <?= $level ?></h4>
                        <p><?= $levelName ?></p>
                    </div>

                    <div class="color-stat stat-green">
                        <h4><?= $joinedEvents ?></h4>
                        <p>Joined Events</p>
                    </div>

                    <div class="color-stat stat-orange">
                        <h4><?= $currentPoints >= 500 ? 'Gold' : ($currentPoints >= 200 ? 'Silver' : 'Bronze') ?></h4>
                        <p>Current Badge</p>
                    </div>

                    <div class="color-stat stat-purple">
                        <h4><?= $joinedEvents ?> Events</h4>
                        <p>Total Joined</p>
                    </div>
                </div>

                <div class="mini-stat-box">
                    <h4>Points Progress</h4>

                    <div style="font-size:12px; color:#6b7280; margin-bottom:8px;">
                        <?= $currentPoints ?> / <?= $nextLevelPoints ?> points to next level
                    </div>

                    <div class="progress-bg">
                        <div class="progress-fill"></div>
                    </div>
                </div>

                <div class="mini-stat-box">
                    <h4>Monthly Activity</h4>

                    <div class="bar-chart">
                        <div class="bar" style="height:28px;"></div>
                        <div class="bar" style="height:38px;"></div>
                        <div class="bar" style="height:30px;"></div>
                        <div class="bar" style="height:52px;"></div>
                        <div class="bar" style="height:26px;"></div>
                    </div>

                    <div class="bar-labels">
                        <span>1-10</span>
                        <span>11-20</span>
                        <span>21-30</span>
                    </div>
                </div>
            </div>

            <div class="leaderboard-card">
                <div class="card-title">Leaderboard</div>

                <?php foreach ($leaderboard as $i => $lu):
                    $isMe = $lu['id'] === ($_SESSION['user_id'] ?? '');
                    $initial = strtoupper(substr($lu['nama'] ?? '?', 0, 1));
                ?>
                <div class="leader-item <?= $isMe ? 'active' : '' ?>">
                    <div class="leader-left">
                        <div class="leader-rank"><?= $i + 1 ?></div>
                        <div class="leader-avatar"><?= htmlspecialchars($initial) ?></div>
                        <div class="leader-info">
                            <h5><?= $isMe ? 'You' : htmlspecialchars($lu['nama']) ?></h5>
                            <p><?= $isMe ? 'Your rank' : 'Student' ?></p>
                        </div>
                    </div>
                    <div class="leader-score"><?= (int)($lu['mata'] ?? 0) ?></div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($leaderboard)): ?>
                    <p style="color:var(--muted);font-size:13px;padding:12px 0;">No data yet.</p>
                <?php endif; ?>
            </div>

        </div>

    </main>

</div>

<script>
    const events = <?= json_encode($events); ?>;

    const calendarGrid = document.getElementById("calendarGrid");
    const monthYear = document.getElementById("calendarMonthYear");
    const calendarEvents = document.getElementById("calendarEvents");

    // Use real current date
    const today = new Date();
    const todayISO = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;

    let currentMonth = today.getMonth();
    let currentYear  = today.getFullYear();

    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    function getEventsByDate(dateISO) {
        return events.filter(event => event.dateISO === dateISO);
    }

    function renderCalendar() {
        calendarGrid.innerHTML = "";

        const dayNames = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];

        dayNames.forEach(day => {
            const dayHead = document.createElement("div");
            dayHead.className = "calendar-head";
            dayHead.textContent = day;
            calendarGrid.appendChild(dayHead);
        });

        monthYear.textContent = monthNames[currentMonth] + " " + currentYear;

        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            calendarGrid.appendChild(document.createElement("div"));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateISO = `${currentYear}-${String(currentMonth + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
            const dayDiv = document.createElement("div");
            const matchedEvents = getEventsByDate(dateISO);

            dayDiv.className = "calendar-day";
            dayDiv.textContent = day;

            if (matchedEvents.length > 0) {
                dayDiv.classList.add("event-date");
            }

            // Highlight today using real date
            if (dateISO === todayISO) {
                dayDiv.classList.add("today");
            }

            dayDiv.addEventListener("click", function () {
                // Highlight selected day
                calendarGrid.querySelectorAll(".calendar-day.selected").forEach(el => el.classList.remove("selected"));
                dayDiv.classList.add("selected");

                if (matchedEvents.length > 0) {
                    calendarEvents.innerHTML = matchedEvents.map(event => {
                        return `<strong>${event.title}</strong><br>${event.date} • ${event.location} • +${event.points} Points`;
                    }).join("<hr style='border:none;border-top:1px solid #dbeafe;margin:8px 0;'>");
                } else {
                    calendarEvents.innerHTML = `No event on <strong>${dateISO}</strong>.`;
                }
            });

            calendarGrid.appendChild(dayDiv);
        }
    }

    document.getElementById("prevMonth").addEventListener("click", function () {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });

    document.getElementById("nextMonth").addEventListener("click", function () {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });

    renderCalendar();

    // Auto-show today's events on load
    const todayEvents = getEventsByDate(todayISO);
    if (todayEvents.length > 0) {
        calendarEvents.innerHTML = todayEvents.map(event => {
            return `<strong>${event.title}</strong><br>${event.date} • ${event.location} • +${event.points} Points`;
        }).join("<hr style='border:none;border-top:1px solid #dbeafe;margin:8px 0;'>");
    } else {
        calendarEvents.innerHTML = `Today is <strong>${todayISO}</strong>. No events scheduled.`;
    }
</script>

</body>
</html>
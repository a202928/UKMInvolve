<?php
$userPeranan = $_SESSION['role'] ?? 'pelajar';
$activePage = $activePage ?? '';

if ($userPeranan === 'pelajar') {
    $menu = [
        'dashboard_pelajar' => ['Home', 'fa-house'],
        'search' => ['Search', 'fa-magnifying-glass'],
        'recommended' => ['For You', 'fa-lightbulb'],
        'rekod-penyertaan' => ['History', 'fa-clock-rotate-left'],
        'logout' => ['Logout', 'fa-right-from-bracket']
    ];
} elseif ($userPeranan === 'penganjur') {
    $menu = [
        'dashboard_penganjur' => ['Dashboard', 'fa-border-all'],
        'hebahan-program' => ['Program Announcements', 'fa-bullhorn'],
        'urus-program' => ['Manage Programs', 'fa-calendar-check'],
        'peserta-kehadiran' => ['Participants', 'fa-users'],
        'laporan-statistik' => ['Reports', 'fa-chart-column']
    ];
} else {
    $menu = [
        'dashboard-pentadbir' => ['Dashboard', 'fa-border-all'],
        'pengurusan-pengguna' => ['Users', 'fa-users-gear'],
        'pengurusan-kategori' => ['Category', 'fa-layer-group'],
        'urus_mata_admin' => ['Manage Points', 'fa-sliders-h'],
        'statistik-sistem' => ['Statistics', 'fa-chart-pie']
    ];
}
?>

<style>
.sidebar {
    background: #ffffff;
    border-right: 1px solid #dbeafe;
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-family: 'Segoe UI', Arial, sans-serif;

    height: 100vh;
    position: sticky;
    top: 0;
    overflow: hidden;
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
    width: 28px !important;
    height: 28px !important;
    object-fit: contain;
}

.sidebar-title {
    font-size: 19px;
    font-weight: 800;
    color: #111827;
    margin: 0;
}

.sidebar-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.sidebar-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 4px;
    padding-left: 8px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sidebar-link {
    text-decoration: none !important;
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
    font-size: 15px;
    text-align: center;
}

.sidebar-link:hover,
.sidebar-link.active {
    background: #eff6ff;
    color: #2563eb;
    font-weight: 700;
}

.sidebar-link.logout-link {
    color: #f97316 !important;
}

.sidebar-link.logout-link:hover,
.sidebar-link.logout-link.active {
    background: #fff7ed !important;
    color: #f97316 !important;
}

.sidebar-footer {
    display: flex;
    flex-direction: column;
    gap: 16px;
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
    font-size: 14px;
}

.user-info h4 {
    font-size: 14px;
    margin: 0;
    color: #111827;
    text-transform: capitalize;
}

.user-info p {
    font-size: 12px;
    color: #6b7280;
    margin: 2px 0 0;
}

@media (max-width: 900px) {
    .sidebar {
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid #dbeafe;
        overflow: visible;
    }

    .sidebar-nav {
        flex-direction: row;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .sidebar-link {
        white-space: nowrap;
    }

    .sidebar-footer {
        display: none;
    }
}
</style>

<aside class="sidebar">

    <div class="sidebar-top">

        <div class="sidebar-header">
            <div class="sidebar-logo-wrap">
                <img src="UKM.png" alt="UKM Logo" class="sidebar-logo">
            </div>
            <h3 class="sidebar-title">UKMInvolve</h3>
        </div>

        <div class="sidebar-section">
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

    <?php if ($userPeranan !== 'pelajar'): ?>
        <div class="sidebar-footer">

            <div class="sidebar-section">
                <p class="sidebar-label">Settings</p>

                <a href="settings.php" class="sidebar-link">
                    <i class="fas fa-gear"></i>
                    Settings
                </a>

                <a href="logout.php" class="sidebar-link logout-link">
                    <i class="fas fa-right-from-bracket"></i>
                    Logout
                </a>
            </div>

            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($userPeranan, 0, 1)) ?>
                </div>

                <div class="user-info">
                    <h4><?= ucfirst($userPeranan) ?></h4>
                    <p>UKM Account</p>
                </div>
            </div>

        </div>
    <?php endif; ?>

</aside>
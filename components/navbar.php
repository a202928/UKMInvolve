<?php
$isLoggedIn = !empty($_SESSION['user_id']);
$userInitial = '';
$userName = '';
$dashboardUrl = '';
$avatarUrl = null;
$userRole = '';

if ($isLoggedIn) {
    $userRole = $_SESSION['role'] ?? 'pelajar';
    $dashboardUrl = dashboardForRole($userRole);
    // Fetch live user data from DB for name & profile pic
    if (db()->isConfigured()) {
        $liveUser = users()->findById($_SESSION['user_id']);
        if ($liveUser) {
            $userName = $liveUser['nama'] ?? 'User';
            $avatarUrl = $liveUser['avatar_url'] ?? null;
        } else {
            $userName = $_SESSION['nama'] ?? 'User';
        }
    } else {
        $userName = $_SESSION['nama'] ?? 'User';
    }
    $userInitial = strtoupper(substr($userName, 0, 1));
}
?>
<header class="navbar">
    <div class="container navbar-container">
        <a href="index.php" class="navbar-brand">
            <img src="UKM.png" alt="UKM Logo" class="navbar-logo">
            <span class="navbar-title">UKMInvolve</span>
        </a>
        
        <ul class="navbar-menu" id="navbarMenu">
            <?php if (!$isLoggedIn): ?>
                <!-- PUBLIC VISITOR -->
                <li><a href="index.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="events.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">Events</a></li>
                <li><a href="index.php#hall-of-fame" class="navbar-link" style="font-size: 28px; line-height: 1; padding: 0 10px;" title="Hall of Fame">🏆</a></li>
                <li><a href="index.php#categories" class="navbar-link">Categories</a></li>
                <li><a href="index.php#about" class="navbar-link">About</a></li>
            <?php elseif ($userRole === 'pelajar'): ?>
                <!-- STUDENT -->
                <li><a href="dashboard_pelajar.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard_pelajar.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="events.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">Events</a></li>
                <li><a href="leaderboard.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : '' ?>" style="font-size: 28px; line-height: 1; padding: 0 10px;" title="Leaderboard">🏆</a></li>
                <li><a href="rekod-penyertaan.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'rekod-penyertaan.php' ? 'active' : '' ?>">History</a></li>
                <li><a href="profile.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">Profile</a></li>

            <?php elseif ($userRole === 'penganjur'): ?>
                <!-- ORGANIZER -->
                <li><a href="dashboard_penganjur.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard_penganjur.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="urus-program.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'urus-program.php' ? 'active' : '' ?>">Manage Events</a></li>
                <li><a href="leaderboard.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : '' ?>" style="font-size: 28px; line-height: 1; padding: 0 10px;" title="Leaderboard">🏆</a></li>
                <li><a href="peserta-kehadiran.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'peserta-kehadiran.php' ? 'active' : '' ?>">Participants</a></li>
                <li><a href="laporan-statistik.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'laporan-statistik.php' ? 'active' : '' ?>">Reports</a></li>

            <?php elseif ($userRole === 'pentadbir'): ?>
                <!-- ADMIN -->
                <li><a href="dashboard-pentadbir.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard-pentadbir.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="pengurusan-pengguna.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'pengurusan-pengguna.php' ? 'active' : '' ?>">Users</a></li>
                <li><a href="leaderboard.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : '' ?>" style="font-size: 28px; line-height: 1; padding: 0 10px;" title="Leaderboard">🏆</a></li>
                <li><a href="statistik-sistem.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'statistik-sistem.php' ? 'active' : '' ?>">Statistics</a></li>
                <li><a href="urus_mata_admin.php" class="navbar-link <?= basename($_SERVER['PHP_SELF']) == 'urus_mata_admin.php' ? 'active' : '' ?>">Settings</a></li>
            <?php endif; ?>
        </ul>

        <div class="navbar-actions">
            <a href="search.php" style="color: var(--text-secondary); margin-right: 16px; font-size: 18px; text-decoration: none; display: flex; align-items: center; transition: var(--transition);" title="Global Search (Organizers, Events, etc)" onmouseover="this.style.color='var(--accent-blue)'" onmouseout="this.style.color='var(--text-secondary)'">
                <i class="fas fa-search"></i>
            </a>
            <?php if ($isLoggedIn): ?>
                <div class="navbar-user-dropdown" id="userDropdown">
                    <div class="navbar-dropdown-toggle" id="dropdownToggle">
                        <?php if ($avatarUrl && file_exists($avatarUrl)): ?>
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="navbar-dropdown-avatar">
                        <?php else: ?>
                            <span class="navbar-dropdown-avatar"><?= htmlspecialchars($userInitial) ?></span>
                        <?php endif; ?>
                        <span class="navbar-user-info"><?= htmlspecialchars($userName) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="navbar-dropdown-menu">
                        <div class="navbar-dropdown-header">
                            <h4><?= htmlspecialchars($userName) ?></h4>
                            <p><?= htmlspecialchars($_SESSION['emel'] ?? '') ?></p>
                        </div>
                        <a href="profile.php" class="navbar-dropdown-item">
                            <i class="fas fa-user-gear"></i> My Profile
                        </a>
                        <?php if ($userRole === 'pelajar'): ?>
                            <a href="rekod-penyertaan.php" class="navbar-dropdown-item">
                                <i class="fas fa-ticket-simple"></i> My Registrations
                            </a>
                        <?php elseif ($userRole === 'penganjur'): ?>
                            <a href="urus-program.php" class="navbar-dropdown-item">
                                <i class="fas fa-calendar-days"></i> Manage Programmes
                            </a>
                            <a href="laporan-statistik.php" class="navbar-dropdown-item">
                                <i class="fas fa-chart-column"></i> Reports
                            </a>
                        <?php elseif ($userRole === 'pentadbir'): ?>
                            <a href="pengurusan-pengguna.php" class="navbar-dropdown-item">
                                <i class="fas fa-users-gear"></i> User Management
                            </a>
                            <a href="statistik-sistem.php" class="navbar-dropdown-item">
                                <i class="fas fa-chart-pie"></i> Statistics
                            </a>
                        <?php endif; ?>
                        <a href="<?= $dashboardUrl ?>" class="navbar-dropdown-item">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                        <div class="navbar-dropdown-divider"></div>
                        <a href="logout.php" class="navbar-dropdown-item logout">
                            <i class="fas fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>
            <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('navbarToggle');
    const menu = document.getElementById('navbarMenu');
    const userDropdown = document.getElementById('userDropdown');
    const dropdownToggle = document.getElementById('dropdownToggle');
    
    if (toggle && menu) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (menu.classList.contains('mobile-active')) {
                menu.classList.remove('mobile-active');
                menu.removeAttribute('style');
            } else {
                menu.classList.add('mobile-active');
                menu.style.display = 'flex';
                menu.style.position = 'absolute';
                menu.style.top = '80px';
                menu.style.left = '0';
                menu.style.width = '100%';
                menu.style.backgroundColor = '#ffffff';
                menu.style.flexDirection = 'column';
                menu.style.padding = '24px';
                menu.style.gap = '16px';
                menu.style.borderBottom = '1px solid #e2e8f0';
                menu.style.boxShadow = '0 10px 25px rgba(15, 23, 42, 0.08)';
                menu.style.zIndex = '999';
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', function() {
            if (menu.classList.contains('mobile-active')) {
                menu.classList.remove('mobile-active');
                menu.removeAttribute('style');
            }
        });
    }

    if (dropdownToggle && userDropdown) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
        });
    }
});
</script>

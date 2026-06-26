<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'dashboard-pentadbir';

$stats = [
    'users' => 0,
    'pelajar' => 0,
    'penganjur' => 0,
    'programs' => 0,
    'registrations' => 0,
    'categories' => 0,
    'pending' => 0,
    'verified' => 0,
    'suspended' => 0,
];

if (db()->isConfigured()) {
    $stats['users'] = users()->countAll();
    $stats['pelajar'] = users()->countByPeranan('pelajar');
    $stats['penganjur'] = users()->countByPeranan('penganjur');
    $stats['pending'] = users()->countByStatus('pending');
    $stats['verified'] = users()->countByStatus('aktif');
    $stats['suspended'] = users()->countByStatus('suspended');
    // Hide dummy programs in counting to align with front listings
    $stats['programs'] = count(array_filter(programs()->listWithCategory(), fn($row) => ($row['penganjur_id'] ?? null) !== null));
    $regResult = db()->select('pendaftaran', '?select=id');
    $stats['registrations'] = $regResult['ok'] ? count($regResult['data']) : 0;
    $stats['categories'] = count(categories()->listAll());
}

$adminInitial = strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Admin Dashboard</h1>
                    <p>System overview, user management, and configuration tools.</p>
                </div>
            </div>

            <!-- STATS CARDS GRID -->
            <div class="stats-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Users</h3>
                        <div class="stat-val"><?= number_format($stats['users']) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Students</h3>
                        <div class="stat-val"><?= number_format($stats['pelajar']) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Organizers</h3>
                        <div class="stat-val"><?= number_format($stats['penganjur']) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-purple">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Events</h3>
                        <div class="stat-val"><?= $stats['programs'] ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Registrations</h3>
                        <div class="stat-val"><?= number_format($stats['registrations']) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue" style="background-color: #fdf2f8; color: #db2777;">
                        <i class="fas fa-file-signature"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Categories</h3>
                        <div class="stat-val"><?= $stats['categories'] ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green" style="background-color: #eff6ff; color: #2563eb;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                </div>
            </div>

            <!-- USER ACCOUNT STATUS SUMMARY -->
            <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-shield" style="color: var(--accent-blue);"></i> User Account Status Summary
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                    <!-- Pending Accounts -->
                    <div style="background: var(--bg-main); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 14px;">
                        <span style="width: 48px; height: 48px; border-radius: 50%; background: rgba(245,158,11,0.1); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-user-clock"></i></span>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 2px;">Pending Accounts</span>
                            <strong style="font-size: 22px; font-weight: 900; color: #d97706;"><?= number_format($stats['pending']) ?></strong>
                        </div>
                    </div>
                    <!-- Verified Accounts -->
                    <div style="background: var(--bg-main); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 14px;">
                        <span style="width: 48px; height: 48px; border-radius: 50%; background: rgba(16,185,129,0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-user-check"></i></span>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 2px;">Verified / Active</span>
                            <strong style="font-size: 22px; font-weight: 900; color: #10b981;"><?= number_format($stats['verified']) ?></strong>
                        </div>
                    </div>
                    <!-- Suspended Accounts -->
                    <div style="background: var(--bg-main); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 14px;">
                        <span style="width: 48px; height: 48px; border-radius: 50%; background: rgba(239,68,68,0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-user-slash"></i></span>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 2px;">Suspended Accounts</span>
                            <strong style="font-size: 22px; font-weight: 900; color: #ef4444;"><?= number_format($stats['suspended']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADMIN MONTHLY ANALYTICS -->
            <?php 
            $adminStats = LeaderboardService::getAdminMonthlyAnalytics();
            ?>
            <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 24px; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fas fa-chart-line" style="color: var(--accent-blue); margin-right: 6px;"></i> Monthly Analytics Summary</span>
                    <span style="font-size: 11px; font-weight: 800; background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 999px; text-transform: uppercase;">This Month</span>
                </h2>
                
                <div class="analytics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <!-- Active Students -->
                    <div style="background: var(--bg-main); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 14px;">
                        <span style="width: 48px; height: 48px; border-radius: 50%; background: rgba(37,99,235,0.1); color: var(--accent-blue); display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-user-graduate"></i></span>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 2px;">Active Students</span>
                            <strong style="font-size: 18px; font-weight: 900; color: var(--text-primary);"><?= number_format($adminStats['active_students']) ?></strong>
                        </div>
                    </div>
                    <!-- Active Organizers -->
                    <div style="background: var(--bg-main); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 14px;">
                        <span style="width: 48px; height: 48px; border-radius: 50%; background: rgba(16,185,129,0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-user-tie"></i></span>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 2px;">Active Organizers</span>
                            <strong style="font-size: 18px; font-weight: 900; color: var(--text-primary);"><?= number_format($adminStats['active_organizers']) ?></strong>
                        </div>
                    </div>
                    <!-- Leaderboard Stats summary -->
                    <div style="background: var(--bg-main); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 14px;">
                        <span style="width: 48px; height: 48px; border-radius: 50%; background: rgba(245,158,11,0.1); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-ranking-star"></i></span>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 2px;">Top Performer Score</span>
                            <strong style="font-size: 18px; font-weight: 900; color: var(--text-primary);"><?= $adminStats['top_student'] ? $adminStats['top_student']['mata'] : 0 ?> Pts</strong>
                        </div>
                    </div>
                </div>

                <!-- Monthly Winners Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                    <!-- Top Student Card -->
                    <div style="border: 1px dashed var(--border); padding: 16px; border-radius: var(--radius-sm); background: var(--bg-main);">
                        <h4 style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #ca8a04; margin: 0 0 12px 0; display: flex; align-items: center; gap: 4px;">🥇 Monthly Top Student</h4>
                        <?php if ($adminStats['top_student']): ?>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?= ProgressionService::renderAvatarHTML($adminStats['top_student'], 'sm') ?>
                                <div style="min-width: 0;">
                                    <strong style="font-size: 14px; font-weight: 800; display: block; color: var(--text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($adminStats['top_student']['nama']) ?></strong>
                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 700;"><?= $adminStats['top_student']['mata'] ?> Pts earned this month</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No record yet</span>
                        <?php endif; ?>
                    </div>
                    <!-- Top Organizer Card -->
                    <div style="border: 1px dashed var(--border); padding: 16px; border-radius: var(--radius-sm); background: var(--bg-main);">
                        <h4 style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #10b981; margin: 0 0 12px 0; display: flex; align-items: center; gap: 4px;">🏢 Monthly Top Organizer</h4>
                        <?php if ($adminStats['top_organizer']): ?>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 34px; height: 34px; border-radius: 50%; overflow: hidden; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; background: var(--bg-secondary); flex-shrink:0;">
                                    <?php if (!empty($adminStats['top_organizer']['avatar_url']) && file_exists($adminStats['top_organizer']['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($adminStats['top_organizer']['avatar_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="font-weight:800; font-size: 14px; color:var(--text-muted);"><?= strtoupper(substr($adminStats['top_organizer']['name'],0,1)) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="min-width: 0;">
                                    <strong style="font-size: 14px; font-weight: 800; display: block; color: var(--text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($adminStats['top_organizer']['name']) ?></strong>
                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 700;"><?= $adminStats['top_organizer']['events_conducted'] ?> event<?= $adminStats['top_organizer']['events_conducted'] == 1 ? '' : 's' ?> (<?= $adminStats['top_organizer']['total_participants'] ?> regs • <?= $adminStats['top_organizer']['total_attendance'] ?> att)</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No record yet</span>
                        <?php endif; ?>
                    </div>
                    <!-- Best Programme Card -->
                    <div style="border: 1px dashed var(--border); padding: 16px; border-radius: var(--radius-sm); background: var(--bg-main);">
                        <h4 style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #d97706; margin: 0 0 12px 0; display: flex; align-items: center; gap: 4px;">🏆 Best Programme</h4>
                        <?php if ($adminStats['best_program']): ?>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?= htmlspecialchars($adminStats['best_program']['poster']) ?>" style="width: 44px; height: 34px; border-radius: 4px; object-fit: cover; border: 1px solid var(--border); flex-shrink:0;">
                                <div style="min-width: 0;">
                                    <strong style="font-size: 14px; font-weight: 800; display: block; color: var(--text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($adminStats['best_program']['name']) ?></strong>
                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 700;"><?= round($adminStats['best_program']['average_rating'], 1) ?> ★ • <?= $adminStats['best_program']['participants'] ?> regs</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No record yet</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- MONTHLY LEADERBOARD WIDGETS -->
            <?php include_once __DIR__ . '/components/leaderboard-widgets.php'; ?>

            <!-- QUICK MANAGEMENT -->
            <div class="dashboard-card-wrap" style="margin-bottom: 0;">
                <h2 style="font-size:22px; font-weight:800; margin-bottom:24px; border-bottom:1px solid var(--border); padding-bottom:14px;">Quick Management Actions</h2>
                
                <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px;">
                    <a href="pengurusan-pengguna.php" class="event-card" style="padding: 24px; transition: var(--transition);" onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='var(--accent-blue)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                        <div style="font-size: 28px; color: var(--accent-blue); margin-bottom: 16px;"><i class="fas fa-users-gear"></i></div>
                        <h3 style="font-size: 18px; margin-bottom: 8px; font-family:'Outfit';">Manage Users</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">Add, edit, or deactivate students, organizers, and administrator accounts.</p>
                    </a>

                    <a href="pengurusan-kategori.php" class="event-card" style="padding: 24px; transition: var(--transition);" onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='var(--accent-blue)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                        <div style="font-size: 28px; color: var(--accent-blue); margin-bottom: 16px;"><i class="fas fa-layer-group"></i></div>
                        <h3 style="font-size: 18px; margin-bottom: 8px; font-family:'Outfit';">Manage Categories</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">Configure category list, customize colors, and icons used by organizers.</p>
                    </a>

                    <a href="urus_mata_admin.php" class="event-card" style="padding: 24px; transition: var(--transition);" onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='var(--accent-blue)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                        <div style="font-size: 28px; color: var(--accent-blue); margin-bottom: 16px;"><i class="fas fa-sliders-h"></i></div>
                        <h3 style="font-size: 18px; margin-bottom: 8px; font-family:'Outfit';">Manage Points Rules</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">Define points rules for registration, attendance, feedback, and badges thresholds.</p>
                    </a>

                    <a href="statistik-sistem.php" class="event-card" style="padding: 24px; transition: var(--transition);" onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='var(--accent-blue)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                        <div style="font-size: 28px; color: var(--accent-blue); margin-bottom: 16px;"><i class="fas fa-chart-pie"></i></div>
                        <h3 style="font-size: 18px; margin-bottom: 8px; font-family:'Outfit';">System Analytics</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">Explore overall system engagement reports, charts, and activity summaries.</p>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>
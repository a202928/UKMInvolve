<?php
session_start();
$_SESSION['role'] = 'pentadbir';
$activePage = 'pemantauan-aktiviti';

// Sample activities data
$activities = [
    [
        'id' => 1,
        'user' => 'penganjur@ukm.edu.my',
        'action' => 'Menerbitkan program',
        'details' => 'Workshop Kepimpinan Mahasiswa',
        'timestamp' => '2026-01-18 14:30:22',
        'type' => 'normal',
        'ip' => '192.168.1.100',
        'user_role' => 'Penganjur',
    ],
    [
        'id' => 2,
        'user' => 'pelajar@ukm.edu.my',
        'action' => 'Mendaftar program',
        'details' => 'Seminar Inovasi Digital',
        'timestamp' => '2026-01-18 14:25:15',
        'type' => 'normal',
        'ip' => '192.168.1.101',
        'user_role' => 'Pelajar',
    ],
    [
        'id' => 3,
        'user' => 'pentadbir@ukm.edu.my',
        'action' => 'Menambah kategori',
        'details' => 'Keusahawanan Digital',
        'timestamp' => '2026-01-18 13:45:30',
        'type' => 'admin',
        'ip' => '192.168.1.102',
        'user_role' => 'Pentadbir',
    ],
    [
        'id' => 4,
        'user' => 'unknown@external.com',
        'action' => 'Percubaan log masuk gagal',
        'details' => 'Kata laluan salah (5 percubaan)',
        'timestamp' => '2026-01-18 12:30:10',
        'type' => 'warning',
        'ip' => '203.45.67.89',
        'user_role' => 'Unknown',
    ],
    [
        'id' => 5,
        'user' => 'penganjur@ukm.edu.my',
        'action' => 'Kemaskini program',
        'details' => 'Program Sukarelawan Komuniti',
        'timestamp' => '2026-01-18 11:15:45',
        'type' => 'normal',
        'ip' => '192.168.1.103',
        'user_role' => 'Penganjur',
    ],
    [
        'id' => 6,
        'user' => 'pelajar@ukm.edu.my',
        'action' => 'Memberi maklum balas',
        'details' => 'Bengkel Penulisan Ilmiah (Rating: 4.5/5)',
        'timestamp' => '2026-01-18 10:45:20',
        'type' => 'normal',
        'ip' => '192.168.1.104',
        'user_role' => 'Pelajar',
    ],
    [
        'id' => 7,
        'user' => 'pentadbir@ukm.edu.my',
        'action' => 'Mengemaskini peranan pengguna',
        'details' => 'Pelajar A123456 -> Penganjur',
        'timestamp' => '2026-01-18 09:30:55',
        'type' => 'admin',
        'ip' => '192.168.1.105',
        'user_role' => 'Pentadbir',
    ],
    [
        'id' => 8,
        'user' => 'unknown@external.com',
        'action' => 'Akses tidak dibenarkan',
        'details' => 'Percubaan akses halaman pentadbir',
        'timestamp' => '2026-01-18 08:15:30',
        'type' => 'warning',
        'ip' => '45.67.89.123',
        'user_role' => 'Unknown',
    ],
];

// Handle filters
$searchQuery = $_GET['search'] ?? '';
$filterType = $_GET['type'] ?? 'semua';
$filterRole = $_GET['role'] ?? 'semua';

// Filter activities
$filteredActivities = array_filter($activities, function($activity) use ($searchQuery, $filterType, $filterRole) {
    // Search filter
    if ($searchQuery && 
        !(stripos($activity['user'], $searchQuery) !== false || 
          stripos($activity['action'], $searchQuery) !== false || 
          stripos($activity['details'], $searchQuery) !== false ||
          stripos($activity['ip'], $searchQuery) !== false)) {
        return false;
    }
    
    // Type filter
    if ($filterType !== 'semua' && $activity['type'] !== $filterType) {
        return false;
    }
    
    // Role filter
    if ($filterRole !== 'semua' && $activity['user_role'] !== $filterRole) {
        return false;
    }
    
    return true;
});

// Statistics
$totalActivities = count($activities);
$normalCount = count(array_filter($activities, function($a) { return $a['type'] === 'normal'; }));
$warningCount = count(array_filter($activities, function($a) { return $a['type'] === 'warning'; }));
$adminCount = count(array_filter($activities, function($a) { return $a['type'] === 'admin'; }));

// Unique user roles for filter
$userRoles = array_unique(array_column($activities, 'user_role'));
sort($userRoles);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemantauan Aktiviti | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Activity Log Styling */
        .activity-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .activity-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .activity-card.warning {
            border-left: 4px solid #ef4444;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(239, 68, 68, 0.02));
        }
        
        .activity-card.admin {
            border-left: 4px solid #8b5cf6;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(139, 92, 246, 0.02));
        }
        
        .activity-card.normal {
            border-left: 4px solid var(--primary);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .activity-content {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .icon-warning { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .icon-admin { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .icon-normal { background: rgba(37, 99, 235, 0.1); color: var(--primary); }
        
        .activity-info {
            flex: 1;
        }
        
        .activity-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .activity-details {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 12px;
            color: var(--text-tertiary);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .badge-warning {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .badge-admin {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }
        
        .badge-normal {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }
        
        /* Filter Section */
        .filter-section {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .filter-select, .filter-input {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--surface);
            transition: border-color 0.2s ease;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .filter-input {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 24px 0;
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-value.total { color: var(--primary); }
        .stat-value.normal { color: #10b981; }
        .stat-value.warning { color: #ef4444; }
        .stat-value.admin { color: #8b5cf6; }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }
        
        .empty-state-icon {
            font-size: 48px;
            color: var(--text-tertiary);
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            font-size: 20px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: var(--text-secondary);
            max-width: 400px;
            margin: 0 auto 20px;
        }
        
        /* Export Button */
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .export-btn:hover {
            background: rgba(37, 99, 235, 0.1);
        }
        
        /* Action Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        /* Activity List */
        .activities-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 24px;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP BAR -->
<header class="topbar"></header>


        <!-- PAGE CONTENT -->
        <section class="content">
            <!-- Header Section -->
            <div class="welcome-section">
                <h1 class="page-title">Pemantauan Aktiviti</h1>
                <p class="page-subtitle">Pantau semua aktiviti dan tindakan pengguna dalam sistem</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value total"><?= $totalActivities ?></div>
                    <div class="stat-label">Jumlah Aktiviti</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value normal"><?= $normalCount ?></div>
                    <div class="stat-label">Aktiviti Normal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value warning"><?= $warningCount ?></div>
                    <div class="stat-label">Amaran Sistem</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value admin"><?= $adminCount ?></div>
                    <div class="stat-label">Tindakan Admin</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-grid">
                    <!-- Search -->
                    <div class="filter-group">
                        <label class="filter-label">Cari Aktiviti</label>
                        <div style="position: relative;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" 
                                   name="search" 
                                   class="filter-input" 
                                   style="padding-left: 44px; width: 100%;"
                                   placeholder="Cari pengguna, tindakan, IP..."
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Jenis Aktiviti</label>
                        <select name="type" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?= $filterType === 'semua' ? 'selected' : '' ?>>Semua Jenis</option>
                            <option value="normal" <?= $filterType === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="admin" <?= $filterType === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="warning" <?= $filterType === 'warning' ? 'selected' : '' ?>>Amaran</option>
                        </select>
                    </div>

                    <!-- Role Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Peranan Pengguna</label>
                        <select name="role" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?= $filterRole === 'semua' ? 'selected' : '' ?>>Semua Peranan</option>
                            <?php foreach ($userRoles as $role): ?>
                                <option value="<?= htmlspecialchars($role) ?>" <?= $filterRole === $role ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="filter-actions">
                        <button type="submit" class="action-btn">
                            <i class="fas fa-filter"></i> Tapis
                        </button>
                        <button type="button" class="export-btn" onclick="exportLogs()">
                            <i class="fas fa-download"></i> Eksport
                        </button>
                    </div>
                </form>
            </div>

            <!-- Activity Log -->
            <div class="activity-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-primary);">
                        <i class="fas fa-history" style="margin-right: 8px;"></i>
                        Log Aktiviti Sistem
                    </h2>
                    <span style="color: var(--text-secondary); font-size: 14px;">
                        <?= count($filteredActivities) ?> aktiviti ditemui
                    </span>
                </div>

                <?php if (count($filteredActivities) > 0): ?>
                    <div class="activities-list">
                        <?php foreach ($filteredActivities as $activity): 
                            $iconClass = "icon-{$activity['type']}";
                            $badgeClass = "badge-{$activity['type']}";
                            $cardClass = "activity-card {$activity['type']}";
                        ?>
                            <div class="<?= $cardClass ?>">
                                <div class="activity-header">
                                    <div class="activity-content">
                                        <div class="activity-icon <?= $iconClass ?>">
                                            <?php if ($activity['type'] === 'warning'): ?>
                                                <i class="fas fa-exclamation-triangle"></i>
                                            <?php elseif ($activity['type'] === 'admin'): ?>
                                                <i class="fas fa-shield-alt"></i>
                                            <?php else: ?>
                                                <i class="fas fa-info-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-info">
                                            <h3 class="activity-title"><?= htmlspecialchars($activity['action']) ?></h3>
                                            <p class="activity-details"><?= htmlspecialchars($activity['details']) ?></p>
                                            <div class="activity-meta">
                                                <span class="meta-item">
                                                    <i class="fas fa-user"></i>
                                                    <strong><?= htmlspecialchars($activity['user_role']) ?>:</strong> 
                                                    <?= htmlspecialchars($activity['user']) ?>
                                                </span>
                                                <span class="meta-item">
                                                    <i class="fas fa-network-wired"></i>
                                                    IP: <?= $activity['ip'] ?>
                                                </span>
                                                <span class="meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <?= date('d/m/Y H:i:s', strtotime($activity['timestamp'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="status-badge <?= $badgeClass ?>">
                                        <?= $activity['type'] === 'warning' ? 'Amaran' : 
                                           ($activity['type'] === 'admin' ? 'Admin' : 'Normal') ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Tiada Aktiviti Dijumpai</h3>
                        <p>Tidak ada aktiviti yang sepadan dengan tapisan anda. Cuba ubah tetapan tapisan.</p>
                        <a href="?" class="action-btn" style="display: inline-block; text-decoration: none;">
                            <i class="fas fa-redo"></i> Reset Tapisan
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>
</div>

<script>
    // Export logs function
    function exportLogs() {
        // In real app, generate and download CSV/Excel file
        const activities = <?= json_encode($filteredActivities) ?>;
        
        // Convert to CSV
        const csvContent = "data:text/csv;charset=utf-8," 
            + "Masa,Tindakan,Butiran,Pengguna,Peranan,IP,Jenis\n"
            + activities.map(activity => 
                `"${activity.timestamp}","${activity.action}","${activity.details}","${activity.user}","${activity.user_role}","${activity.ip}","${activity.type}"`
            ).join("\n");
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "log_aktiviti_<?= date('Y-m-d') ?>.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show notification
        showNotification('Log aktiviti telah dieksport ke CSV');
    }
    
    // Auto refresh activity log (optional)
    function autoRefreshLogs() {
        // In real app, you might want to periodically check for new activities
        // setTimeout(() => {
        //     window.location.reload();
        // }, 30000); // Refresh every 30 seconds
    }
    
    // Show notification
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            animation: slideIn 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        `;
        notification.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize auto-refresh
    document.addEventListener('DOMContentLoaded', function() {
        autoRefreshLogs();
    });
</script>

</body>
</html>
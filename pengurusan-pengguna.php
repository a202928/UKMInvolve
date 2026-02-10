<?php
session_start();
$_SESSION['role'] = 'pentadbir';
$activePage = 'pengurusan-pengguna';

// Sample users data
$users = [
    [
        'id' => 1,
        'nama' => 'Ahmad Faiz bin Abdullah',
        'emel' => 'faiz@ukm.edu.my',
        'peranan' => 'pelajar',
        'fakulti' => 'FSKTM',
        'matrik' => 'A123456',
        'status' => 'aktif',
        'tarikhDaftar' => '2025-09-15',
        'last_login' => '2026-01-18 14:30:00',
    ],
    [
        'id' => 2,
        'nama' => 'Dr. Siti Aminah binti Mahmud',
        'emel' => 'siti.aminah@ukm.edu.my',
        'peranan' => 'penganjur',
        'organisasi' => 'Pusat Pembangunan Pelajar',
        'status' => 'aktif',
        'tarikhDaftar' => '2025-01-10',
        'last_login' => '2026-01-17 09:15:00',
    ],
    [
        'id' => 3,
        'nama' => 'Muhammad Ali bin Omar',
        'emel' => 'ali@ukm.edu.my',
        'peranan' => 'pelajar',
        'fakulti' => 'FEP',
        'matrik' => 'A123457',
        'status' => 'aktif',
        'tarikhDaftar' => '2025-09-12',
        'last_login' => '2026-01-18 11:20:00',
    ],
    [
        'id' => 4,
        'nama' => 'Prof. Dr. Ahmad Zaki Abdullah',
        'emel' => 'zaki@ukm.edu.my',
        'peranan' => 'penganjur',
        'organisasi' => 'Fakulti Sains & Teknologi',
        'status' => 'aktif',
        'tarikhDaftar' => '2024-08-20',
        'last_login' => '2026-01-16 16:45:00',
    ],
    [
        'id' => 5,
        'nama' => 'Nurul Hidayah binti Hassan',
        'emel' => 'nurul@ukm.edu.my',
        'peranan' => 'pentadbir',
        'status' => 'aktif',
        'tarikhDaftar' => '2024-01-05',
        'last_login' => '2026-01-18 08:30:00',
    ],
    [
        'id' => 6,
        'nama' => 'Lim Wei Chen',
        'emel' => 'weichen@ukm.edu.my',
        'peranan' => 'pelajar',
        'fakulti' => 'FST',
        'matrik' => 'A123458',
        'status' => 'aktif',
        'tarikhDaftar' => '2025-09-10',
        'last_login' => '2026-01-17 13:10:00',
    ],
    [
        'id' => 7,
        'nama' => 'Cikgu Rosnah binti Yusof',
        'emel' => 'rosnah@ukm.edu.my',
        'peranan' => 'penganjur',
        'organisasi' => 'Kelab Bahasa',
        'status' => 'suspended',
        'tarikhDaftar' => '2025-03-15',
        'last_login' => '2026-01-10 10:20:00',
    ],
    [
        'id' => 8,
        'nama' => 'Syed Amirul bin Syed Ahmad',
        'emel' => 'syed@ukm.edu.my',
        'peranan' => 'pelajar',
        'fakulti' => 'FUU',
        'matrik' => 'A123459',
        'status' => 'aktif',
        'tarikhDaftar' => '2025-09-05',
        'last_login' => '2026-01-18 07:45:00',
    ],
];

// Handle filters
$searchQuery = $_GET['search'] ?? '';
$filterRole = $_GET['role'] ?? 'semua';
$filterStatus = $_GET['status'] ?? 'semua';

// Filter users
$filteredUsers = array_filter($users, function($user) use ($searchQuery, $filterRole, $filterStatus) {
    // Search filter
    if ($searchQuery && 
        !(stripos($user['nama'], $searchQuery) !== false || 
          stripos($user['emel'], $searchQuery) !== false ||
          ($user['peranan'] === 'pelajar' && stripos($user['matrik'], $searchQuery) !== false))) {
        return false;
    }
    
    // Role filter
    if ($filterRole !== 'semua' && $user['peranan'] !== $filterRole) {
        return false;
    }
    
    // Status filter
    if ($filterStatus !== 'semua' && $user['status'] !== $filterStatus) {
        return false;
    }
    
    return true;
});

// Statistics
$totalUsers = count($users);
$pelajarCount = count(array_filter($users, function($u) { return $u['peranan'] === 'pelajar'; }));
$penganjurCount = count(array_filter($users, function($u) { return $u['peranan'] === 'penganjur'; }));
$pentadbirCount = count(array_filter($users, function($u) { return $u['peranan'] === 'pentadbir'; }));
$aktifCount = count(array_filter($users, function($u) { return $u['status'] === 'aktif'; }));
$suspendedCount = count(array_filter($users, function($u) { return $u['status'] === 'suspended'; }));

// Handle actions
if (isset($_POST['action'])) {
    $userId = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'suspend') {
        echo "<script>alert('Pengguna ID $userId akan digantung. (Simulasi)');</script>";
    } elseif ($action === 'activate') {
        echo "<script>alert('Pengguna ID $userId akan diaktifkan. (Simulasi)');</script>";
    } elseif ($action === 'delete') {
        echo "<script>if(confirm('Adakah anda pasti mahu memadam pengguna ini?')) { alert('Pengguna ID $userId akan dipadam. (Simulasi)'); }</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Pengguna | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* User Management Styling */
        .user-management-container {
            max-width: 1200px;
            margin: 0 auto;
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
        .stat-value.pelajar { color: #3b82f6; }
        .stat-value.penganjur { color: #8b5cf6; }
        .stat-value.pentadbir { color: #10b981; }
        .stat-value.active { color: #10b981; }
        .stat-value.suspended { color: #ef4444; }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Users Table */
        .table-container {
            background: var(--surface);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        
        .table-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
        }
        
        .table-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Table Styling */
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background: var(--background);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border);
        }
        
        .users-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
        }
        
        .users-table tr:hover td {
            background: rgba(37, 99, 235, 0.03);
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-suspended {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        /* Role Badges */
        .role-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .role-pelajar {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .role-penganjur {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }
        
        .role-pentadbir {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
        }
        
        .btn-edit {
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-edit:hover {
            background: rgba(37, 99, 235, 0.1);
        }
        
        .btn-suspend {
            border: 2px solid #f59e0b;
            color: #f59e0b;
        }
        
        .btn-suspend:hover {
            background: rgba(245, 158, 11, 0.1);
        }
        
        .btn-delete {
            border: 2px solid #ef4444;
            color: #ef4444;
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        .btn-activate {
            border: 2px solid #10b981;
            color: #10b981;
        }
        
        .btn-activate:hover {
            background: rgba(16, 185, 129, 0.1);
        }
        
        /* Add User Button */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-add:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
        
        /* User Info */
        .user-info {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .user-info strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        /* Last Login */
        .last-login {
            font-size: 12px;
            color: var(--text-tertiary);
            margin-top: 4px;
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
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h1 class="page-title">Pengurusan Pengguna</h1>
                    <p class="page-subtitle">Urus akaun pengguna dan peranan dalam sistem</p>
                </div>
                <button class="btn-add" onclick="openAddUserModal()">
                    <i class="fas fa-plus"></i> Tambah Pengguna
                </button>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value total"><?= $totalUsers ?></div>
                    <div class="stat-label">Jumlah Pengguna</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value pelajar"><?= $pelajarCount ?></div>
                    <div class="stat-label">Pelajar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value penganjur"><?= $penganjurCount ?></div>
                    <div class="stat-label">Penganjur</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value pentadbir"><?= $pentadbirCount ?></div>
                    <div class="stat-label">Pentadbir</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value active"><?= $aktifCount ?></div>
                    <div class="stat-label">Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value suspended"><?= $suspendedCount ?></div>
                    <div class="stat-label">Digantung</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-grid">
                    <!-- Search -->
                    <div class="filter-group">
                        <label class="filter-label">Cari Pengguna</label>
                        <div style="position: relative;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" 
                                   name="search" 
                                   class="filter-input" 
                                   style="padding-left: 44px; width: 100%;"
                                   placeholder="Cari nama, emel atau no. matrik..."
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                        </div>
                    </div>

                    <!-- Role Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Peranan</label>
                        <select name="role" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?= $filterRole === 'semua' ? 'selected' : '' ?>>Semua Peranan</option>
                            <option value="pelajar" <?= $filterRole === 'pelajar' ? 'selected' : '' ?>>Pelajar</option>
                            <option value="penganjur" <?= $filterRole === 'penganjur' ? 'selected' : '' ?>>Penganjur</option>
                            <option value="pentadbir" <?= $filterRole === 'pentadbir' ? 'selected' : '' ?>>Pentadbir</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?= $filterStatus === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="suspended" <?= $filterStatus === 'suspended' ? 'selected' : '' ?>>Digantung</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="filter-actions">
                        <button type="submit" class="btn-add" style="padding: 12px 24px;">
                            <i class="fas fa-filter"></i> Tapis
                        </button>
                        <a href="?" class="btn-add" style="background: transparent; color: var(--primary); text-decoration: none; border: 2px solid var(--primary);">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">
                        <i class="fas fa-users-cog"></i>
                        Senarai Pengguna
                        <span style="font-size: 14px; color: var(--text-secondary); margin-left: 8px;">
                            (<?= count($filteredUsers) ?> pengguna ditemui)
                        </span>
                    </h2>
                </div>
                
                <?php if (count($filteredUsers) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Emel</th>
                                <th>Peranan</th>
                                <th>Maklumat</th>
                                <th>Status</th>
                                <th style="text-align: center;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredUsers as $user): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);">
                                        <?= htmlspecialchars($user['nama']) ?>
                                    </div>
                                    <div class="last-login">
                                        <i class="far fa-clock"></i>
                                        Daftar: <?= date('d/m/Y', strtotime($user['tarikhDaftar'])) ?>
                                        <?php if (isset($user['last_login'])): ?>
                                            <br>
                                            <i class="fas fa-sign-in-alt"></i>
                                            Log masuk: <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="color: var(--text-secondary);">
                                        <?= htmlspecialchars($user['emel']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?= $user['peranan'] ?>">
                                        <?= ucfirst($user['peranan']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <?php if ($user['peranan'] === 'pelajar'): ?>
                                            <strong>Fakulti:</strong> <?= $user['fakulti'] ?><br>
                                            <strong>No. Matrik:</strong> <?= $user['matrik'] ?>
                                        <?php elseif ($user['peranan'] === 'penganjur'): ?>
                                            <strong>Organisasi:</strong> <?= $user['organisasi'] ?>
                                        <?php else: ?>
                                            <strong>Pentadbir Sistem</strong>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $user['status'] ?>">
                                        <?= $user['status'] === 'aktif' ? 'Aktif' : 'Digantung' ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-buttons" style="justify-content: center;">
                                        <button class="btn-action btn-edit" onclick="editUser(<?= $user['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if ($user['status'] === 'aktif'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="btn-action btn-suspend" onclick="return confirm('Adakah anda pasti mahu menggantung pengguna ini?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn-action btn-activate" onclick="return confirm('Adakah anda pasti mahu mengaktifkan semula pengguna ini?')">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn-action btn-delete" onclick="return confirm('Adakah anda pasti mahu memadam pengguna ini?\\n\\nTindakan ini tidak boleh dibatalkan.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3>Tiada Pengguna Dijumpai</h3>
                    <p>Tidak ada pengguna yang sepadan dengan tapisan anda. Cuba ubah tetapan tapisan.</p>
                    <a href="?" class="btn-add" style="display: inline-block; text-decoration: none;">
                        <i class="fas fa-redo"></i> Reset Tapisan
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </section>
    </main>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background: white; margin: 5% auto; padding: 30px; border-radius: var(--radius); max-width: 600px; width: 90%; box-shadow: var(--shadow-lg);">
        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
                <i class="fas fa-user-plus"></i> Tambah Pengguna Baharu
            </h3>
            <p style="color: var(--text-secondary); font-size: 14px;">
                Isi maklumat pengguna baharu untuk sistem
            </p>
        </div>
        
        <form id="addUserForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="filter-label">Nama Penuh</label>
                    <input type="text" class="filter-input" placeholder="Ahmad Faiz bin Abdullah" required>
                </div>
                
                <div class="form-group">
                    <label class="filter-label">Emel UKM</label>
                    <input type="email" class="filter-input" placeholder="faiz@ukm.edu.my" required>
                </div>
                
                <div class="form-group">
                    <label class="filter-label">Peranan</label>
                    <select class="filter-select" required onchange="toggleUserFields(this.value)">
                        <option value="">Pilih Peranan</option>
                        <option value="pelajar">Pelajar</option>
                        <option value="penganjur">Penganjur</option>
                        <option value="pentadbir">Pentadbir</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="filter-label">Kata Laluan</label>
                    <input type="password" class="filter-input" placeholder="Min 8 aksara" required>
                </div>
            </div>
            
            <!-- Student Fields -->
            <div id="studentFields" style="display: none; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="filter-label">No. Matrik</label>
                    <input type="text" class="filter-input" placeholder="A123456">
                </div>
                
                <div class="form-group">
                    <label class="filter-label">Fakulti</label>
                    <select class="filter-select">
                        <option value="">Pilih Fakulti</option>
                        <option value="FSKTM">FSKTM</option>
                        <option value="FEP">FEP</option>
                        <option value="FST">FST</option>
                        <option value="FKAB">FKAB</option>
                        <option value="FPI">FPI</option>
                        <option value="FUU">FUU</option>
                    </select>
                </div>
            </div>
            
            <!-- Organizer Fields -->
            <div id="organizerFields" style="display: none; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="filter-label">Organisasi</label>
                    <input type="text" class="filter-input" placeholder="Pusat Pembangunan Pelajar">
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
                <button type="button" onclick="closeAddUserModal()" style="padding: 12px 24px; border: 1px solid var(--border); background: transparent; border-radius: 8px; cursor: pointer;">
                    Batal
                </button>
                <button type="submit" class="btn-add" style="padding: 12px 24px; border: none;">
                    <i class="fas fa-plus"></i> Tambah Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Show/hide additional fields based on role
    function toggleUserFields(role) {
        const studentFields = document.getElementById('studentFields');
        const organizerFields = document.getElementById('organizerFields');
        
        if (role === 'pelajar') {
            studentFields.style.display = 'grid';
            organizerFields.style.display = 'none';
        } else if (role === 'penganjur') {
            studentFields.style.display = 'none';
            organizerFields.style.display = 'block';
        } else {
            studentFields.style.display = 'none';
            organizerFields.style.display = 'none';
        }
    }
    
    // Modal functions
    function openAddUserModal() {
        document.getElementById('addUserModal').style.display = 'block';
    }
    
    function closeAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
        document.getElementById('addUserForm').reset();
        toggleUserFields('');
    }
    
    // Edit user
    function editUser(userId) {
        // In real app, fetch user data via AJAX and open edit modal
        console.log('Editing user:', userId);
        alert('Edit pengguna (Simulasi). ID: ' + userId);
    }
    
    // Handle add user form submission
    document.getElementById('addUserForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // In real app, send AJAX request to add user
        console.log('Adding new user:', {
            name: this.querySelector('input[type="text"]').value,
            email: this.querySelector('input[type="email"]').value,
            role: this.querySelector('select').value
        });
        
        // Show success message
        showNotification('Pengguna berjaya ditambah');
        closeAddUserModal();
    });
    
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
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('addUserModal');
        if (event.target === modal) {
            closeAddUserModal();
        }
    }
</script>

</body>
</html>
<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'pengurusan-pengguna';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = trim($_POST['nama'] ?? '');
        $emel = strtolower(trim($_POST['emel'] ?? ''));
        $password = $_POST['password'] ?? '';
        $peranan = $_POST['peranan'] ?? 'pelajar';
        
        $matrik = ($peranan === 'pelajar') ? trim($_POST['matrik'] ?? '') : null;
        $fakulti = ($peranan === 'pelajar') ? trim($_POST['fakulti'] ?? '') : null;
        $organisasi = ($peranan === 'penganjur') ? trim($_POST['organisasi'] ?? '') : null;
        
        if ($nama === '' || $emel === '' || $password === '') {
            $error = 'Name, email, and password are required.';
        } else {
            $existing = users()->findByEmel($emel);
            if ($existing) {
                $error = 'A user with this email is already registered.';
            } else {
                $res = users()->create([
                    'nama' => $nama,
                    'emel' => $emel,
                    'password' => $password,
                    'peranan' => $peranan,
                    'matrik' => $matrik,
                    'fakulti' => $fakulti,
                    'organisasi' => $organisasi
                ]);
                
                if ($res['ok']) {
                    $success = "User '{$nama}' successfully registered.";
                } else {
                    $error = 'Failed to register user: ' . ($res['error'] ?? 'Unknown error');
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nama = trim($_POST['nama'] ?? '');
        $emel = strtolower(trim($_POST['emel'] ?? ''));
        $peranan = $_POST['peranan'] ?? 'pelajar';
        $status = $_POST['status'] ?? 'aktif';
        
        $matrik = ($peranan === 'pelajar') ? trim($_POST['matrik'] ?? '') : null;
        $fakulti = ($peranan === 'pelajar') ? trim($_POST['fakulti'] ?? '') : null;
        $organisasi = ($peranan === 'penganjur') ? trim($_POST['organisasi'] ?? '') : null;
        $password = $_POST['password'] ?? '';
        
        if ($id === '') {
            $error = 'Invalid user ID.';
        } elseif ($nama === '' || $emel === '') {
            $error = 'Name and email are required.';
        } else {
            // Self deactivation/role-change safety checks
            if ($id === $_SESSION['user_id']) {
                if ($status !== 'aktif') {
                    $error = 'You cannot deactivate your own admin account while logged in.';
                } elseif ($peranan !== 'pentadbir') {
                    $error = 'You cannot change the role of your own admin account while logged in.';
                }
            }
            
            if ($error === '') {
                $existing = users()->findByEmel($emel);
                if ($existing && $existing['id'] !== $id) {
                    $error = 'This email is already in use by another user.';
                } else {
                    $payload = [
                        'nama' => $nama,
                        'emel' => $emel,
                        'peranan' => $peranan,
                        'status' => $status,
                        'matrik' => $matrik,
                        'fakulti' => $fakulti,
                        'organisasi' => $organisasi
                    ];
                    
                    if ($password !== '') {
                        $payload['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    $res = users()->updateUser($id, $payload);
                    if ($res['ok']) {
                        $success = "User details for '{$nama}' successfully updated.";
                        if ($id === $_SESSION['user_id']) {
                            $_SESSION['nama'] = $nama;
                        }
                    } else {
                        $error = 'Failed to update user details: ' . ($res['error'] ?? 'Unknown error');
                    }
                }
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'] ?? '';
        $status = $_POST['status'] ?? 'aktif';
        
        if ($id === '') {
            $error = 'Invalid user ID.';
        } elseif ($id === $_SESSION['user_id']) {
            $error = 'You cannot change the status of your own admin account while logged in.';
        } else {
            $res = users()->updateStatus($id, $status);
            if ($res['ok']) {
                $success = 'Account status successfully updated.';
            } else {
                $error = 'Failed to change account status: ' . ($res['error'] ?? 'Unknown error');
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        
        if ($id === '') {
            $error = 'Invalid user ID.';
        } elseif ($id === $_SESSION['user_id']) {
            $error = 'You cannot delete your own admin account while logged in.';
        } else {
            // Cascading delete user dependencies safely to prevent foreign key violations
            db()->delete('student_interests', '?student_id=eq.' . rawurlencode($id));
            db()->delete('kehadiran', '?pelajar_id=eq.' . rawurlencode($id));
            db()->delete('rekod_mata', '?student_id=eq.' . rawurlencode($id));
            db()->delete('lencana_pelajar', '?student_id=eq.' . rawurlencode($id));
            db()->delete('pendaftaran', '?pelajar_id=eq.' . rawurlencode($id));
            db()->delete('maklum_balas', '?pelajar_id=eq.' . rawurlencode($id));
            
            $res = users()->deleteUser($id);
            if ($res['ok']) {
                $success = 'User account and all associated records successfully deleted.';
            } else {
                $error = 'Failed to delete user: ' . ($res['error'] ?? 'Unknown error');
            }
        }
    }
}

$users = [];
if (db()->isConfigured()) {
    foreach (users()->listAll() as $row) {
        $users[] = users()->toAdminRow($row);
    }
}

$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? 'semua';
$status = $_GET['status'] ?? 'semua';

$filteredUsers = array_filter($users, function($user) use ($search, $role, $status) {
    if ($search && stripos($user['nama'], $search) === false && stripos($user['emel'], $search) === false) return false;
    if ($role !== 'semua' && $user['peranan'] !== $role) return false;
    if ($status !== 'semua' && $user['status'] !== $status) return false;
    return true;
});

$totalUsers = count($users);
$pelajarCount = count(array_filter($users, fn($u) => $u['peranan'] === 'pelajar'));
$penganjurCount = count(array_filter($users, fn($u) => $u['peranan'] === 'penganjur'));
$pentadbirCount = count(array_filter($users, fn($u) => $u['peranan'] === 'pentadbir'));

$adminInitial = strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1));

$menu = [
    'dashboard-pentadbir' => ['Dashboard', 'fa-house'],
    'pengurusan-pengguna' => ['Users', 'fa-users-gear'],
    'pengurusan-kategori' => ['Category', 'fa-layer-group'],
    'urus_mata_admin' => ['Manage Points', 'fa-sliders-h'],
    'statistik-sistem' => ['Statistics', 'fa-chart-pie'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .filter-section {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 32px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 20px;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-label {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 14px;
        }
        .filter-select, .filter-input {
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--white);
            outline: none;
            transition: var(--transition);
            width: 100%;
        }
        .filter-select:focus, .filter-input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        
        /* Modal Popup styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 32px;
            max-width: 650px;
            width: 100%;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            position: relative;
            animation: modalFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes modalFadeIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
            font-family: 'Outfit', sans-serif;
        }
        .modal-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .modal-actions {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }
        
        /* Table Styles */
        .users-table tr {
            border-bottom: 1px solid var(--border);
            transition: var(--transition);
        }
        .users-table tr:hover td {
            background-color: var(--bg-main);
        }
        .users-table td {
            padding: 16px;
            vertical-align: middle;
            font-size: 14px;
            color: var(--text-primary);
        }
        
        /* User Profile Avatar block */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
        }
        .user-name {
            font-weight: 700;
            color: var(--text-primary);
        }
        .user-email {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }
        
        /* Badge designs */
        .role-badge, .status-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            display: inline-block;
        }
        .role-pelajar { background: rgba(37, 99, 235, 0.1); color: var(--accent-blue); }
        .role-penganjur { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .role-pentadbir { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-aktif { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-suspended { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        /* Small Round Action Buttons */
        .btn-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-circle:hover {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            background: rgba(37, 99, 235, 0.05);
            transform: scale(1.05);
        }
        .btn-circle.btn-suspend:hover {
            border-color: #f97316;
            color: #f97316;
            background: rgba(249, 115, 22, 0.05);
        }
        .btn-circle.btn-activate:hover {
            border-color: #10b981;
            color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }
        .btn-circle.btn-delete:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <!-- HEADER -->
            <div class="dashboard-header-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div class="dashboard-header-title">
                    <h1>User Management</h1>
                    <p>Add, edit, suspend, or delete student, organizer, and administrator accounts.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal()" style="background-color: var(--accent-blue); color: var(--white); border: none; font-weight: 700; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>

            <!-- NOTIFICATIONS -->
            <?php if ($success): ?>
                <div class="alert-banner alert-banner-success" style="margin-bottom: 24px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 14px 20px; border-radius: 8px; color: #10b981; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-circle-check" style="font-size: 18px;"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-banner alert-banner-error" style="margin-bottom: 24px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 14px 20px; border-radius: 8px; color: #ef4444; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-triangle-exclamation" style="font-size: 18px;"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- STATS CARDS GRID -->
            <div class="stats-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 32px;">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Users</h3>
                        <div class="stat-val"><?= $totalUsers ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Students</h3>
                        <div class="stat-val"><?= $pelajarCount ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Organizers</h3>
                        <div class="stat-val"><?= $penganjurCount ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-purple">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Administrators</h3>
                        <div class="stat-val"><?= $pentadbirCount ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search User</label>
                        <input type="text" name="search" class="filter-input" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Account Status</label>
                        <select name="status" class="filter-select">
                            <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="background-color: var(--accent-blue); color: var(--white); font-weight: 700; height: 46px; padding: 0 24px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </form>
            </div>

            <!-- TABS & TABLE WRAPPER -->
            <div class="tab-nav-wrapper" style="margin-bottom: 24px;">
                <button type="button" class="tab-nav-btn active" onclick="switchTab('all')">All Users (<?= $totalUsers ?>)</button>
                <button type="button" class="tab-nav-btn" onclick="switchTab('pentadbir')">Admins (<?= $pentadbirCount ?>)</button>
                <button type="button" class="tab-nav-btn" onclick="switchTab('pelajar')">Students (<?= $pelajarCount ?>)</button>
                <button type="button" class="tab-nav-btn" onclick="switchTab('penganjur')">Organizers (<?= $penganjurCount ?>)</button>
            </div>

            <div class="dashboard-card-wrap">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit';">User Directory</h2>
                    <p id="user-found-count" style="font-size: 14px; color: var(--text-secondary); font-weight: 600;"><?= count($filteredUsers) ?> user(s) found.</p>
                </div>

                <div style="overflow-x: auto; background: var(--white); border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <table class="users-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                                <th style="padding: 16px 24px; font-weight: 700; color: var(--text-primary); font-family: 'Outfit'; font-size: 14px;">Name & Email</th>
                                <th style="padding: 16px; font-weight: 700; color: var(--text-primary); font-family: 'Outfit'; font-size: 14px;">Role</th>
                                <th style="padding: 16px; font-weight: 700; color: var(--text-primary); font-family: 'Outfit'; font-size: 14px;">Details</th>
                                <th style="padding: 16px; font-weight: 700; color: var(--text-primary); font-family: 'Outfit'; font-size: 14px;">Registered Date</th>
                                <th style="padding: 16px; font-weight: 700; color: var(--text-primary); font-family: 'Outfit'; font-size: 14px;">Status</th>
                                <th style="padding: 16px 24px; font-weight: 700; color: var(--text-primary); font-family: 'Outfit'; font-size: 14px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($filteredUsers as $user): ?>
                            <tr class="user-row" data-role="<?= htmlspecialchars($user['peranan']) ?>">
                                <td style="padding: 16px 24px;">
                                    <div class="user-cell">
                                        <div class="avatar-circle"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
                                        <div>
                                            <div class="user-name"><?= htmlspecialchars($user['nama']) ?></div>
                                            <div class="user-email"><?= htmlspecialchars($user['emel']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="role-badge role-<?= $user['peranan'] ?>"><?= ucfirst($user['peranan'] === 'pelajar' ? 'Student' : ($user['peranan'] === 'penganjur' ? 'Organizer' : 'Admin')) ?></span></td>
                                <td>
                                    <?php if ($user['peranan'] === 'pelajar'): ?>
                                        <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($user['fakulti'] ?: '-') ?></span> <span style="color: var(--text-muted);">•</span> <?= htmlspecialchars($user['matrik'] ?: '-') ?>
                                    <?php elseif ($user['peranan'] === 'penganjur'): ?>
                                        <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($user['organisasi'] ?: '-') ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-style: italic;">System Administrator</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td><span class="status-badge status-<?= $user['status'] ?>"><?= $user['status'] === 'aktif' ? 'Active' : 'Suspended' ?></span></td>
                                <td style="padding: 16px 24px;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <button class="btn-circle" onclick="openViewModal('<?= $user['id'] ?>', '<?= htmlspecialchars(addslashes($user['nama'])) ?>', '<?= htmlspecialchars(addslashes($user['emel'])) ?>', '<?= htmlspecialchars(addslashes($user['peranan'])) ?>', '<?= htmlspecialchars(addslashes($user['status'])) ?>', '<?= htmlspecialchars(addslashes($user['matrik'])) ?>', '<?= htmlspecialchars(addslashes($user['fakulti'])) ?>', '<?= htmlspecialchars(addslashes($user['organisasi'])) ?>', '<?= htmlspecialchars(addslashes($user['created_at'])) ?>')" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-circle" onclick="openEditModal('<?= $user['id'] ?>', '<?= htmlspecialchars(addslashes($user['nama'])) ?>', '<?= htmlspecialchars(addslashes($user['emel'])) ?>', '<?= htmlspecialchars(addslashes($user['peranan'])) ?>', '<?= htmlspecialchars(addslashes($user['status'])) ?>', '<?= htmlspecialchars(addslashes($user['matrik'])) ?>', '<?= htmlspecialchars(addslashes($user['fakulti'])) ?>', '<?= htmlspecialchars(addslashes($user['organisasi'])) ?>')" title="Edit Account">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['status'] === 'aktif'): ?>
                                            <button class="btn-circle btn-suspend" onclick="toggleUserStatus('<?= $user['id'] ?>', 'suspended', '<?= htmlspecialchars(addslashes($user['nama'])) ?>')" title="Suspend Account">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-circle btn-activate" onclick="toggleUserStatus('<?= $user['id'] ?>', 'aktif', '<?= htmlspecialchars(addslashes($user['nama'])) ?>')" title="Activate Account">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-circle btn-delete" onclick="deleteUser('<?= $user['id'] ?>', '<?= htmlspecialchars(addslashes($user['nama'])) ?>')" title="Delete Account">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <!-- VIEW USER MODAL -->
    <div class="modal" id="viewUserModal">
        <div class="modal-content">
            <h2 class="modal-title">View User Details</h2>
            <p class="modal-subtitle">Full account details for the selected user profile.</p>
            
            <div class="modal-grid">
                <div class="filter-group">
                    <label class="filter-label">Full Name</label>
                    <div id="viewUserName" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Email Address</label>
                    <div id="viewUserEmail" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Role</label>
                    <div id="viewUserRole" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <div id="viewUserStatus" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
                <div class="filter-group" id="viewMatrikGroup">
                    <label class="filter-label">Matric Number</label>
                    <div id="viewUserMatrik" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
                <div class="filter-group" id="viewFakultiGroup">
                    <label class="filter-label">Faculty / College</label>
                    <div id="viewUserFakulti" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
                <div class="filter-group" id="viewOrganisasiGroup" style="grid-column: span 2;">
                    <label class="filter-label">Organization</label>
                    <div id="viewUserOrganisasi" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
                <div class="filter-group" style="grid-column: span 2;">
                    <label class="filter-label">Registration Date</label>
                    <div id="viewUserRegDate" style="padding: 12px 16px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-weight: 700;"></div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()" style="width: 100%; border: 1px solid var(--border); background: var(--white); height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Close Details</button>
            </div>
        </div>
    </div>

    <!-- ADD USER MODAL -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <h2 class="modal-title">Add New User</h2>
                <p class="modal-subtitle">Create student, organizer, or administrative account.</p>

                <div class="modal-grid">
                    <div class="filter-group">
                        <label class="filter-label">Full Name</label>
                        <input class="filter-input" name="nama" placeholder="Full name" required>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Email Address</label>
                        <input class="filter-input" type="email" name="emel" placeholder="email@ukm.edu.my" required>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Role</label>
                        <select class="filter-select" name="peranan" id="addUserRole" onchange="toggleAddRoleFields()" required>
                            <option value="pelajar">Student</option>
                            <option value="penganjur">Organizer</option>
                            <option value="pentadbir">Admin</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Password</label>
                        <input class="filter-input" type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="filter-group" id="addMatrikGroup">
                        <label class="filter-label">Matric Number</label>
                        <input class="filter-input" name="matrik" placeholder="e.g. A182902">
                    </div>
                    <div class="filter-group" id="addFakultiGroup">
                        <label class="filter-label">Faculty</label>
                        <input class="filter-input" name="fakulti" placeholder="e.g. FTSM">
                    </div>
                    <div class="filter-group" id="addOrganisasiGroup" style="grid-column: span 2; display:none;">
                        <label class="filter-label">Organization Name</label>
                        <input class="filter-input" name="organisasi" placeholder="e.g. Persatuan Mahasiswa Anak Sabah">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()" style="flex: 1; border: 1px solid var(--border); background: var(--white); height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; background-color: var(--accent-blue); color: var(--white); border: none; height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editUserId">
                <h2 class="modal-title">Edit User Account</h2>
                <p class="modal-subtitle">Modify user roles, affiliations, passwords, or suspend status.</p>

                <div class="modal-grid">
                    <div class="filter-group">
                        <label class="filter-label">Full Name</label>
                        <input class="filter-input" name="nama" id="editUserName" required>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Email Address</label>
                        <input class="filter-input" type="email" name="emel" id="editUserEmail" required>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Role</label>
                        <select class="filter-select" name="peranan" id="editUserRole" onchange="toggleEditRoleFields()" required>
                            <option value="pelajar">Student</option>
                            <option value="penganjur">Organizer</option>
                            <option value="pentadbir">Admin</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Account Status</label>
                        <select class="filter-select" name="status" id="editUserStatus" required>
                            <option value="aktif">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="filter-group" id="editMatrikGroup">
                        <label class="filter-label">Matric Number</label>
                        <input class="filter-input" name="matrik" id="editUserMatrik">
                    </div>
                    <div class="filter-group" id="editFakultiGroup">
                        <label class="filter-label">Faculty / College</label>
                        <input class="filter-input" name="fakulti" id="editUserFakulti">
                    </div>
                    <div class="filter-group" id="editOrganisasiGroup" style="grid-column: span 2;">
                        <label class="filter-label">Organization Name</label>
                        <input class="filter-input" name="organisasi" id="editUserOrganisasi">
                    </div>
                    <div class="filter-group" style="grid-column: span 2;">
                        <label class="filter-label">New Password (Leave blank to keep unchanged)</label>
                        <input class="filter-input" type="password" name="password" placeholder="Enter new password">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex: 1; border: 1px solid var(--border); background: var(--white); height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; background-color: var(--accent-blue); color: var(--white); border: none; height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden Action Forms -->
    <form id="delete-user-form" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-user-id">
    </form>

    <form id="status-user-form" method="POST" style="display:none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="id" id="status-user-id">
        <input type="hidden" name="status" id="status-user-val">
    </form>

    <script>
    function openModal() {
        document.getElementById('addUserModal').style.display = 'flex';
        toggleAddRoleFields();
    }
    function closeModal() {
        document.getElementById('addUserModal').style.display = 'none';
    }
    function toggleAddRoleFields() {
        var role = document.getElementById('addUserRole').value;
        document.getElementById('addMatrikGroup').style.display = (role === 'pelajar') ? 'block' : 'none';
        document.getElementById('addFakultiGroup').style.display = (role === 'pelajar') ? 'block' : 'none';
        document.getElementById('addOrganisasiGroup').style.display = (role === 'penganjur') ? 'block' : 'none';
    }

    function openEditModal(id, name, emel, peranan, status, matrik, fakulti, organisasi) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editUserName').value = name;
        document.getElementById('editUserEmail').value = emel;
        document.getElementById('editUserRole').value = peranan;
        document.getElementById('editUserStatus').value = status;
        document.getElementById('editUserMatrik').value = matrik;
        document.getElementById('editUserFakulti').value = fakulti;
        document.getElementById('editUserOrganisasi').value = organisasi;
        
        document.getElementById('editUserModal').style.display = 'flex';
        toggleEditRoleFields();
    }
    function closeEditModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }
    function toggleEditRoleFields() {
        var role = document.getElementById('editUserRole').value;
        document.getElementById('editMatrikGroup').style.display = (role === 'pelajar') ? 'block' : 'none';
        document.getElementById('editFakultiGroup').style.display = (role === 'pelajar') ? 'block' : 'none';
        document.getElementById('editOrganisasiGroup').style.display = (role === 'penganjur') ? 'block' : 'none';
    }

    function openViewModal(id, name, emel, peranan, status, matrik, fakulti, organisasi, regDate) {
        document.getElementById('viewUserName').textContent = name;
        document.getElementById('viewUserEmail').textContent = emel;
        document.getElementById('viewUserRole').textContent = peranan.charAt(0).toUpperCase() + peranan.slice(1);
        document.getElementById('viewUserStatus').textContent = status === 'aktif' ? 'Active' : 'Suspended';
        document.getElementById('viewUserMatrik').textContent = matrik || '-';
        document.getElementById('viewUserFakulti').textContent = fakulti || '-';
        document.getElementById('viewUserOrganisasi').textContent = organisasi || '-';
        document.getElementById('viewUserRegDate').textContent = regDate || '-';
        
        document.getElementById('viewMatrikGroup').style.display = (peranan === 'pelajar') ? 'block' : 'none';
        document.getElementById('viewFakultiGroup').style.display = (peranan === 'pelajar') ? 'block' : 'none';
        document.getElementById('viewOrganisasiGroup').style.display = (peranan === 'penganjur') ? 'block' : 'none';
        
        document.getElementById('viewUserModal').style.display = 'flex';
    }
    function closeViewModal() {
        document.getElementById('viewUserModal').style.display = 'none';
    }

    function toggleUserStatus(id, newStatus, name) {
        var actionText = newStatus === 'suspended' ? 'suspend' : 'reactivate';
        if (confirm("Are you sure you want to " + actionText + " the user account '" + name + "'?")) {
            document.getElementById('status-user-id').value = id;
            document.getElementById('status-user-val').value = newStatus;
            document.getElementById('status-user-form').submit();
        }
    }

    function deleteUser(id, name) {
        if (confirm("Are you sure you want to permanently delete user account '" + name + "'? All participation, points, and interests records will also be deleted.")) {
            document.getElementById('delete-user-id').value = id;
            document.getElementById('delete-user-form').submit();
        }
    }

    function switchTab(role) {
        const buttons = document.querySelectorAll('.tab-nav-btn');
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick').includes("'" + role + "'")) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        const rows = document.querySelectorAll('.user-row');
        let count = 0;
        rows.forEach(row => {
            const userRole = row.getAttribute('data-role');
            if (role === 'all' || userRole === role) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
        document.getElementById('user-found-count').textContent = count + ' user(s) found.';
    }

    window.onclick = function(e) {
        if (e.target === document.getElementById('addUserModal')) closeModal();
        if (e.target === document.getElementById('editUserModal')) closeEditModal();
        if (e.target === document.getElementById('viewUserModal')) closeViewModal();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const initialRole = '<?= htmlspecialchars($role) ?>';
        if (initialRole !== 'semua') {
            switchTab(initialRole);
        }
    });
    </script>

</body>
</html>

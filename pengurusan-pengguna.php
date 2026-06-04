<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'pengurusan-pengguna';

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

$menu = [
    'dashboard-pentadbir' => ['Dashboard', 'fa-house'],
    'pengurusan-pengguna' => ['Pengguna', 'fa-users-gear'],
    'pengurusan-kategori' => ['Kategori', 'fa-layer-group'],
    'urus_mata_admin' => ['Urus Mata', 'fa-sliders-h'],
    'statistik-sistem' => ['Statistik', 'fa-chart-pie'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Pengurusan Pengguna | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;--muted:#6b7280;--text:#111827;--orange:#f97316}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button,input,select{font-family:inherit}

.dashboard-wrapper{height:100vh;display:grid;grid-template-columns:240px 1fr;background:var(--page);overflow:hidden}
.sidebar{height:100vh;background:#fff;border-right:1px solid var(--border);padding:28px 20px;display:flex;flex-direction:column;justify-content:space-between}
.sidebar-header{display:flex;align-items:center;gap:12px;margin-bottom:30px}
.sidebar-logo-wrap{width:38px;height:38px;border-radius:14px;background:#eaf4ff;display:flex;align-items:center;justify-content:center}
.sidebar-logo{width:28px;height:28px;object-fit:contain}
.sidebar-title{font-size:19px;font-weight:800}
.sidebar-label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;padding-left:8px}
.sidebar-nav{display:flex;flex-direction:column;gap:8px}
.sidebar-link{padding:11px 12px;border-radius:14px;display:flex;gap:12px;align-items:center;color:#374151;font-weight:500;transition:.25s}
.sidebar-link i{width:18px;text-align:center}
.sidebar-link.active,.sidebar-link:hover{background:#eff6ff;color:#2563eb;font-weight:700}
.logout-link{color:#f97316}
.logout-link:hover{background:#fff7ed;color:#f97316}
.user-profile{display:flex;align-items:center;gap:10px;background:#f8fbff;border:1px solid var(--border);border-radius:16px;padding:12px}
.user-avatar{width:38px;height:38px;border-radius:50%;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.user-profile h4{font-size:14px}
.user-profile p{font-size:12px;color:var(--muted)}

.main-section{height:100vh;overflow-y:auto;padding:28px;background:var(--page)}
.main-section::-webkit-scrollbar{width:8px}
.main-section::-webkit-scrollbar-thumb{background:#bfdbfe;border-radius:999px}

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{font-size:14px;color:var(--muted);margin-top:4px}
.btn-add{background:var(--primary);color:white;border:none;border-radius:999px;padding:12px 18px;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{background:white;border:1px solid var(--border);border-radius:22px;padding:20px;box-shadow:0 8px 20px rgba(37,99,235,.06)}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:var(--muted);font-weight:600}
.stat-icon{width:46px;height:46px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:20px}
.icon-blue{background:#eff6ff;color:#2563eb}
.icon-green{background:#ecfdf5;color:#059669}
.icon-purple{background:#f5f3ff;color:#7c3aed}
.icon-orange{background:#fff7ed;color:#f97316}

.filter-card{background:white;border:1px solid var(--border);border-radius:22px;padding:20px;margin-bottom:22px;box-shadow:0 8px 20px rgba(37,99,235,.06)}
.filter-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr auto;gap:14px;align-items:end}
.form-group label{font-size:13px;font-weight:800;margin-bottom:8px;display:block}
.form-input,.form-select{width:100%;padding:13px 15px;border:1px solid var(--border);border-radius:16px;outline:none;background:white}
.form-input:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(91,141,239,.12)}
.btn-filter{background:var(--primary);color:white;border:none;border-radius:999px;padding:13px 18px;font-weight:800;cursor:pointer}

.table-card{background:white;border:1px solid var(--border);border-radius:26px;padding:22px;box-shadow:0 8px 20px rgba(37,99,235,.06)}
.table-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.table-header h2{font-size:22px}
.table-header p{font-size:13px;color:var(--muted)}
.table-wrapper{overflow-x:auto}
.users-table{width:100%;border-collapse:collapse;min-width:850px}
.users-table th{background:#f8fbff;color:#374151;font-size:13px;text-align:left;padding:14px;border-bottom:1px solid var(--border)}
.users-table td{padding:15px 14px;border-bottom:1px solid var(--border);font-size:14px}
.users-table tr:hover td{background:#f8fbff}

.user-cell{display:flex;align-items:center;gap:10px}
.avatar{width:38px;height:38px;border-radius:50%;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.user-name{font-weight:800}
.user-email{font-size:12px;color:var(--muted);margin-top:2px}

.role-badge,.status-badge{padding:7px 11px;border-radius:999px;font-size:12px;font-weight:800;display:inline-block}
.role-pelajar{background:#eff6ff;color:#2563eb}
.role-penganjur{background:#f5f3ff;color:#7c3aed}
.role-pentadbir{background:#ecfdf5;color:#059669}
.status-aktif{background:#ecfdf5;color:#059669}
.status-suspended{background:#fef2f2;color:#dc2626}

.action-buttons{display:flex;gap:8px;justify-content:center}
.btn-action{width:34px;height:34px;border-radius:10px;border:none;cursor:pointer}
.btn-edit{background:#eff6ff;color:#2563eb}
.btn-suspend{background:#fff7ed;color:#f97316}
.btn-delete{background:#fef2f2;color:#dc2626}
.btn-activate{background:#ecfdf5;color:#059669}

.modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1000;align-items:center;justify-content:center}
.modal-content{background:white;border-radius:26px;padding:26px;max-width:620px;width:92%;box-shadow:0 20px 50px rgba(15,23,42,.20)}
.modal-content h2{font-size:22px;margin-bottom:6px}
.modal-content p{font-size:13px;color:var(--muted);margin-bottom:18px}
.modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.modal-actions{display:flex;gap:12px;margin-top:20px}
.btn-close,.btn-save{flex:1;border-radius:999px;padding:12px;font-weight:800;cursor:pointer}
.btn-close{background:white;border:1px solid var(--border)}
.btn-save{background:var(--primary);border:none;color:white}

@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}.filter-grid{grid-template-columns:1fr}}
@media(max-width:900px){
body{overflow:auto}.dashboard-wrapper{grid-template-columns:1fr;height:auto}.sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
.sidebar-nav{flex-direction:row;overflow-x:auto}.sidebar-link{white-space:nowrap}.user-profile{display:none}.main-section{height:auto;overflow:visible}
}
@media(max-width:600px){.stats-grid,.modal-grid{grid-template-columns:1fr}.page-header{flex-direction:column;align-items:flex-start;gap:12px}}
</style>
</head>

<body>
<div class="dashboard-wrapper">

<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <div class="sidebar-logo-wrap"><img src="UKM.png" class="sidebar-logo" alt="UKM"></div>
            <h3 class="sidebar-title">UKMInvolve</h3>
        </div>

        <p class="sidebar-label">Menu</p>
        <nav class="sidebar-nav">
            <?php foreach ($menu as $page => $item): ?>
                <a href="<?= $page ?>.php" class="sidebar-link <?= ($activePage === $page) ? 'active' : '' ?> <?= ($page === 'logout') ? 'logout-link' : '' ?>">
                    <i class="fas <?= $item[1] ?>"></i><?= $item[0] ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="user-profile">
        <div class="user-avatar">A</div>
        <div><h4>Pentadbir</h4><p>Admin Account</p></div>
    </div>
</aside>

<main class="main-section">

    <div class="page-header">
        <div>
            <h1>User Management</h1>
            <p>Manage students, organizers and admin accounts.</p>
        </div>
        <button class="btn-add" onclick="openModal()"><i class="fas fa-plus"></i> Add User</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon icon-blue"><i class="fas fa-users"></i></div><h2><?= $totalUsers ?></h2><p>Total Users</p></div>
        <div class="stat-card"><div class="stat-icon icon-green"><i class="fas fa-user-graduate"></i></div><h2><?= $pelajarCount ?></h2><p>Students</p></div>
        <div class="stat-card"><div class="stat-icon icon-purple"><i class="fas fa-user-tie"></i></div><h2><?= $penganjurCount ?></h2><p>Organizers</p></div>
        <div class="stat-card"><div class="stat-icon icon-orange"><i class="fas fa-shield-halved"></i></div><h2><?= $pentadbirCount ?></h2><p>Admins</p></div>
    </div>

    <form method="GET" class="filter-card">
        <div class="filter-grid">
            <div class="form-group">
                <label>Search User</label>
                <input type="text" name="search" class="form-input" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-select">
                    <option value="semua" <?= $role === 'semua' ? 'selected' : '' ?>>All Roles</option>
                    <option value="pelajar" <?= $role === 'pelajar' ? 'selected' : '' ?>>Student</option>
                    <option value="penganjur" <?= $role === 'penganjur' ? 'selected' : '' ?>>Organizer</option>
                    <option value="pentadbir" <?= $role === 'pentadbir' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>All Status</option>
                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>

            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
        </div>
    </form>

    <div class="table-card">
        <div class="table-header">
            <div>
                <h2>User List</h2>
                <p><?= count($filteredUsers) ?> user(s) found.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($filteredUsers as $user): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($user['nama']) ?></div>
                                    <div class="user-email"><?= htmlspecialchars($user['emel']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="role-badge role-<?= $user['peranan'] ?>"><?= ucfirst($user['peranan']) ?></span></td>
                        <td>
                            <?php if ($user['peranan'] === 'pelajar'): ?>
                                <?= $user['fakulti'] ?> • <?= $user['matrik'] ?>
                            <?php elseif ($user['peranan'] === 'penganjur'): ?>
                                <?= $user['organisasi'] ?>
                            <?php else: ?>
                                Pentadbir Sistem
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge status-<?= $user['status'] ?>"><?= $user['status'] === 'aktif' ? 'Active' : 'Suspended' ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="alert('Edit user ID <?= $user['id'] ?>')"><i class="fas fa-edit"></i></button>
                                <?php if ($user['status'] === 'aktif'): ?>
                                    <button class="btn-action btn-suspend" onclick="alert('Suspend user ID <?= $user['id'] ?>')"><i class="fas fa-ban"></i></button>
                                <?php else: ?>
                                    <button class="btn-action btn-activate" onclick="alert('Activate user ID <?= $user['id'] ?>')"><i class="fas fa-check"></i></button>
                                <?php endif; ?>
                                <button class="btn-action btn-delete" onclick="confirm('Delete this user?')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</div>

<div class="modal" id="addUserModal">
    <div class="modal-content">
        <h2>Add New User</h2>
        <p>Create student, organizer or admin account.</p>

        <div class="modal-grid">
            <div class="form-group">
                <label>Full Name</label>
                <input class="form-input" placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input class="form-input" placeholder="email@ukm.edu.my">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select class="form-select">
                    <option>Student</option>
                    <option>Organizer</option>
                    <option>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input class="form-input" type="password" placeholder="Password">
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn-close" onclick="closeModal()">Cancel</button>
            <button class="btn-save" onclick="alert('User added successfully'); closeModal();">Save User</button>
        </div>
    </div>
</div>

<script>
function openModal(){document.getElementById('addUserModal').style.display='flex'}
function closeModal(){document.getElementById('addUserModal').style.display='none'}
window.onclick=function(e){if(e.target===document.getElementById('addUserModal')) closeModal()}
</script>

</body>
</html>
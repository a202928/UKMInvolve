<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'pengurusan-kategori';

$categories = [];
if (db()->isConfigured()) {
    foreach (categories()->listAll() as $row) {
        $count = categories()->programCount((int) $row['id']);
        $categories[] = categories()->toAdminRow($row, $count);
    }
}

$totalCategories = count($categories);
$totalPrograms = array_sum(array_column($categories, 'jumlahProgram'));
$activeCategories = count(array_filter($categories, fn($c) => $c['jumlahProgram'] > 10));

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
<title>Pengurusan Kategori | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;--muted:#6b7280;--text:#111827;--orange:#f97316}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button,input{font-family:inherit}

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

.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.stat-card{background:white;border:1px solid var(--border);border-radius:22px;padding:20px;box-shadow:0 8px 20px rgba(37,99,235,.06)}
.stat-icon{width:46px;height:46px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:20px}
.icon-blue{background:#eff6ff;color:#2563eb}
.icon-green{background:#ecfdf5;color:#059669}
.icon-orange{background:#fff7ed;color:#f97316}
.stat-card h2{font-size:28px;margin-bottom:4px}
.stat-card p{font-size:13px;color:var(--muted);font-weight:600}

.category-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px}
.category-card{background:white;border:1px solid var(--border);border-radius:24px;padding:20px;box-shadow:0 8px 20px rgba(37,99,235,.06);transition:.25s}
.category-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(37,99,235,.12)}
.category-top{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.category-icon{width:54px;height:54px;border-radius:18px;color:white;display:flex;align-items:center;justify-content:center;font-size:22px}
.category-name{font-size:18px;font-weight:800;margin-bottom:4px}
.category-count{font-size:13px;color:var(--muted)}
.active-badge{display:inline-flex;margin-top:8px;background:#fff7ed;color:#f97316;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800}

.category-actions{display:flex;gap:10px;border-top:1px solid var(--border);padding-top:14px}
.btn-edit,.btn-delete{flex:1;border:none;border-radius:999px;padding:10px;font-weight:800;cursor:pointer}
.btn-edit{background:#eff6ff;color:#2563eb}
.btn-delete{background:#fef2f2;color:#dc2626}

.modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1000;align-items:center;justify-content:center}
.modal-content{background:white;border-radius:26px;padding:26px;max-width:520px;width:92%;box-shadow:0 20px 50px rgba(15,23,42,.20)}
.modal-content h2{font-size:22px;margin-bottom:6px}
.modal-content p{font-size:13px;color:var(--muted);margin-bottom:18px}
.form-group{margin-bottom:14px}
.form-group label{font-size:13px;font-weight:800;margin-bottom:8px;display:block}
.form-input{width:100%;padding:13px 15px;border:1px solid var(--border);border-radius:16px;outline:none}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(91,141,239,.12)}
.modal-actions{display:flex;gap:12px;margin-top:20px}
.btn-close,.btn-save{flex:1;border-radius:999px;padding:12px;font-weight:800;cursor:pointer}
.btn-close{background:white;border:1px solid var(--border)}
.btn-save{background:var(--primary);border:none;color:white}

@media(max-width:1100px){.stats-grid{grid-template-columns:1fr 1fr}}
@media(max-width:900px){
body{overflow:auto}.dashboard-wrapper{grid-template-columns:1fr;height:auto}.sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
.sidebar-nav{flex-direction:row;overflow-x:auto}.sidebar-link{white-space:nowrap}.user-profile{display:none}.main-section{height:auto;overflow:visible}
}
@media(max-width:600px){.stats-grid,.category-grid{grid-template-columns:1fr}.page-header{flex-direction:column;align-items:flex-start;gap:12px}}
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
            <h1>Category Management</h1>
            <p>Manage programme categories used in UKMInvolve.</p>
        </div>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="fas fa-layer-group"></i></div>
            <h2><?= $totalCategories ?></h2>
            <p>Total Categories</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-green"><i class="fas fa-calendar-days"></i></div>
            <h2><?= $totalPrograms ?></h2>
            <p>Total Programmes</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-orange"><i class="fas fa-fire"></i></div>
            <h2><?= $activeCategories ?></h2>
            <p>Active Categories</p>
        </div>
    </div>

    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="category-top">
                    <div class="category-icon" style="background:<?= $category['color'] ?>">
                        <i class="fas <?= $category['icon'] ?>"></i>
                    </div>

                    <div>
                        <div class="category-name"><?= htmlspecialchars($category['nama']) ?></div>
                        <div class="category-count"><?= $category['jumlahProgram'] ?> programmes</div>

                        <?php if ($category['jumlahProgram'] > 10): ?>
                            <span class="active-badge">
                                <i class="fas fa-fire"></i> Active
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="category-actions">
                    <button class="btn-edit" onclick="openEditModal('<?= htmlspecialchars($category['nama']) ?>')">
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    <button class="btn-delete" onclick="confirm('Delete this category?')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</main>
</div>

<div class="modal" id="addModal">
    <div class="modal-content">
        <h2>Add Category</h2>
        <p>Create a new programme category.</p>

        <div class="form-group">
            <label>Category Name</label>
            <input class="form-input" placeholder="Example: Digital Entrepreneurship">
        </div>

        <div class="modal-actions">
            <button class="btn-close" onclick="closeAddModal()">Cancel</button>
            <button class="btn-save" onclick="alert('Category added successfully'); closeAddModal();">Save Category</button>
        </div>
    </div>
</div>

<div class="modal" id="editModal">
    <div class="modal-content">
        <h2>Edit Category</h2>
        <p>Update category name.</p>

        <div class="form-group">
            <label>Category Name</label>
            <input class="form-input" id="editCategoryName">
        </div>

        <div class="modal-actions">
            <button class="btn-close" onclick="closeEditModal()">Cancel</button>
            <button class="btn-save" onclick="alert('Category updated successfully'); closeEditModal();">Update Category</button>
        </div>
    </div>
</div>

<script>
function openAddModal(){document.getElementById('addModal').style.display='flex'}
function closeAddModal(){document.getElementById('addModal').style.display='none'}

function openEditModal(name){
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editModal').style.display='flex';
}
function closeEditModal(){document.getElementById('editModal').style.display='none'}

window.onclick=function(e){
    if(e.target===document.getElementById('addModal')) closeAddModal();
    if(e.target===document.getElementById('editModal')) closeEditModal();
}
</script>

</body>
</html>
<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'pengurusan-kategori';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nama = trim($_POST['nama'] ?? '');
        $color = trim($_POST['color'] ?? '#5b8def');
        $icon = trim($_POST['icon'] ?? 'fa-layer-group');
        $mata = (int)($_POST['mata'] ?? 100);
        if ($nama === '') {
            $error = 'Category name cannot be empty.';
        } else {
            $existing = categories()->findByName($nama);
            if ($existing) {
                $error = 'A category with this name already exists.';
            } else {
                $res = categories()->createCategory($nama, $color, $icon, $mata);
                if ($res['ok']) {
                    $success = "Category '{$nama}' successfully added.";
                } else {
                    $error = 'Failed to add category: ' . ($res['error'] ?? 'Unknown error');
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $color = trim($_POST['color'] ?? '#5b8def');
        $icon = trim($_POST['icon'] ?? 'fa-layer-group');
        $status = trim($_POST['status'] ?? 'aktif');
        $mata = (int)($_POST['mata'] ?? 100);
        if ($id <= 0 || $nama === '') {
            $error = 'Incomplete details.';
        } else {
            $existing = categories()->findByName($nama);
            if ($existing && (int)$existing['id'] !== $id) {
                $error = 'This category name is already used by another category.';
            } else {
                $res = categories()->updateCategory($id, $nama, $color, $icon, $status, $mata);
                if ($res['ok']) {
                    $success = "Category '{$nama}' successfully updated.";
                } else {
                    $error = 'Failed to update category: ' . ($res['error'] ?? 'Unknown error');
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $error = 'Invalid category.';
        } else {
            $pCount = categories()->programCount($id);
            if ($pCount > 0) {
                // Cannot delete permanently, deactivate instead
                $catResult = db()->select('kategori', '?id=eq.' . $id . '&limit=1');
                if ($catResult['ok'] && !empty($catResult['data'][0])) {
                    $catName = $catResult['data'][0]['nama'];
                    $color = $catResult['data'][0]['color'] ?? '#5b8def';
                    $icon = $catResult['data'][0]['icon'] ?? 'fa-layer-group';
                    $mata = (int)($catResult['data'][0]['mata'] ?? 100);
                    $res = categories()->updateCategory($id, $catName, $color, $icon, 'tidak aktif', $mata);
                    if ($res['ok']) {
                        $success = "Category '{$catName}' is currently used by a programme. It has been deactivated instead of deleted.";
                    } else {
                        $error = 'Failed to update category status: ' . ($res['error'] ?? 'Unknown error');
                    }
                } else {
                    $error = 'Category not found.';
                }
            } else {
                $res = categories()->deleteCategory($id);
                if ($res['ok']) {
                    $success = 'Category successfully deleted.';
                } else {
                    $error = 'Failed to delete category: ' . ($res['error'] ?? 'Unknown error');
                }
            }
        }
    }
}

$categories = [];
if (db()->isConfigured()) {
    foreach (categories()->listAll() as $row) {
        $count = categories()->programCount((int) $row['id']);
        $categories[] = categories()->toAdminRow($row, $count);
    }
}

$totalCategories = count($categories);
$totalPrograms = array_sum(array_column($categories, 'jumlahProgram'));
$activeCategories = count(array_filter($categories, fn($c) => $c['status'] === 'aktif'));

$adminInitial = strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1));

$menu = [
    'dashboard-pentadbir' => ['Dashboard', 'fa-house'],
    'pengurusan-pengguna' => ['Users', 'fa-users-gear'],
    'pengurusan-kategori' => ['Category', 'fa-layer-group'],
    'urus_mata_admin' => ['Manage Points', 'fa-sliders-h'],
    'statistik-sistem' => ['Statistics', 'fa-chart-pie'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];

$suggestedIcons = [
    'fa-graduation-cap' => 'Academic',
    'fa-volleyball' => 'Sports',
    'fa-laptop-code' => 'Technology',
    'fa-hands-helping' => 'Volunteer',
    'fa-globe' => 'Culture',
    'fa-palette' => 'Arts',
    'fa-heart-pulse' => 'Health',
    'fa-briefcase' => 'Business',
    'fa-seedling' => 'Environment',
    'fa-trophy' => 'Leadership',
    'fa-people-carry-box' => 'Community',
    'fa-gears' => 'Workshop',
    'fa-chalkboard-user' => 'Seminar',
    'fa-award' => 'Competition',
    'fa-lightbulb' => 'Innovation',
    'fa-hands-praying' => 'Spiritual'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Card custom elements */
        .category-card {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .category-top {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }
        .category-icon {
            width: 54px;
            height: 54px;
            border-radius: var(--radius-sm);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .category-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
            font-family: 'Outfit', sans-serif;
            margin-bottom: 6px;
        }
        .category-count {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .active-badge {
            display: inline-flex;
            margin-top: 8px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent-blue);
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }
        .inactive-badge {
            display: inline-flex;
            margin-top: 8px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }
        
        .category-actions {
            display: flex;
            gap: 12px;
            border-top: 1px solid var(--border);
            padding-top: 18px;
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
            max-width: 520px;
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
        
        /* Form fields */
        .filter-group {
            margin-bottom: 16px;
        }
        .filter-label {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 14px;
            margin-bottom: 8px;
            display: block;
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
        .modal-actions {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }

        /* Icon picker */
        .icon-picker-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 8px;
            max-height: 160px;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--bg-main);
        }
        .icon-picker-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            background: var(--white);
        }
        .icon-picker-item i {
            font-size: 18px;
            color: var(--text-secondary);
            transition: var(--transition);
        }
        .icon-picker-item:hover {
            border-color: var(--accent-blue);
            background: rgba(37, 99, 235, 0.05);
        }
        .icon-picker-item.selected {
            border-color: var(--accent-blue);
            background: var(--accent-blue);
        }
        .icon-picker-item.selected i {
            color: var(--white);
        }
        .icon-picker-label {
            font-size: 9px;
            margin-top: 6px;
            color: var(--text-secondary);
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        .icon-picker-item.selected .icon-picker-label {
            color: var(--white);
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
                    <h1>Category Management</h1>
                    <p>Configure co-curricular categories, points distribution rules, colors, and iconography.</p>
                </div>
                <button class="btn btn-primary" onclick="openAddModal()" style="background-color: var(--accent-blue); color: var(--white); border: none; font-weight: 700; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                    <i class="fas fa-plus"></i> Add Category
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
            <div class="stats-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 32px;">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Categories</h3>
                        <div class="stat-val"><?= $totalCategories ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-layer-group"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Programmes</h3>
                        <div class="stat-val"><?= $totalPrograms ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Active Categories</h3>
                        <div class="stat-val"><?= $activeCategories ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-toggle-on"></i>
                    </div>
                </div>
            </div>

            <!-- CATEGORY LISTING GRID -->
            <div class="category-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; margin-bottom: 40px;">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-top">
                            <div class="category-icon" style="background:<?= $category['color'] ?>">
                                <i class="fas <?= $category['icon'] ?>"></i>
                            </div>

                            <div>
                                <h3 class="category-name"><?= htmlspecialchars($category['nama']) ?></h3>
                                <div class="category-count"><?= $category['jumlahProgram'] ?> programmes &bull; +<?= $category['mata'] ?> Points</div>

                                <?php if ($category['status'] === 'tidak aktif'): ?>
                                    <span class="inactive-badge">
                                        <i class="fas fa-ban" style="margin-right:4px;"></i> Inactive
                                    </span>
                                <?php else: ?>
                                    <span class="active-badge">
                                        <i class="fas fa-check-circle" style="margin-right:4px;"></i> Active
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="category-actions">
                            <button class="btn btn-secondary" style="flex: 1; height: 38px; padding: 0; font-size: 13px; font-weight: 700; border: 1px solid var(--border); background: var(--white); cursor: pointer; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: var(--transition);" onclick="openEditModal(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['nama'])) ?>', '<?= htmlspecialchars(addslashes($category['color'])) ?>', '<?= htmlspecialchars(addslashes($category['icon'])) ?>', '<?= htmlspecialchars(addslashes($category['status'])) ?>', <?= $category['mata'] ?>)">
                                <i class="fas fa-edit" style="color: var(--accent-blue);"></i> Edit
                            </button>

                            <button class="btn btn-secondary" style="flex: 1; height: 38px; padding: 0; font-size: 13px; font-weight: 700; border: 1px solid rgba(239, 68, 68, 0.2); background: var(--white); color: #ef4444; cursor: pointer; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: var(--transition);" onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['nama'])) ?>', <?= $category['jumlahProgram'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <!-- ADD CATEGORY MODAL -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <h2 class="modal-title">Add Category</h2>
                <p class="modal-subtitle">Create a new program classification group.</p>

                <div class="filter-group">
                    <label class="filter-label">Category Name</label>
                    <input class="filter-input" name="nama" placeholder="Example: Digital Entrepreneurship" required>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Visual Palette Theme Color</label>
                    <input class="filter-input" type="color" name="color" value="#2563eb" style="height: 48px; padding: 4px;">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Standard Completion Points</label>
                    <input class="filter-input" type="number" name="mata" value="100" min="0" required>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Icon Representation</label>
                    <input type="hidden" name="icon" id="addCategoryIcon" value="fa-graduation-cap">
                    <div class="icon-picker-grid" id="addIconPicker">
                        <?php foreach ($suggestedIcons as $class => $label): ?>
                            <div class="icon-picker-item" data-icon="<?= $class ?>" onclick="selectPickerIcon('add', '<?= $class ?>')">
                                <i class="fas <?= $class ?>"></i>
                                <div class="icon-picker-label"><?= $label ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()" style="flex: 1; border: 1px solid var(--border); background: var(--white); height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; background-color: var(--accent-blue); color: var(--white); border: none; height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT CATEGORY MODAL -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editCategoryId">
                <h2 class="modal-title">Edit Category</h2>
                <p class="modal-subtitle">Modify parameters and icon configurations.</p>

                <div class="filter-group">
                    <label class="filter-label">Category Name</label>
                    <input class="filter-input" name="nama" id="editCategoryName" required>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Theme Color</label>
                    <input class="filter-input" type="color" name="color" id="editCategoryColor" style="height: 48px; padding: 4px;">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Standard Completion Points</label>
                    <input class="filter-input" type="number" name="mata" id="editCategoryPoints" min="0" required>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Icon Representation</label>
                    <input type="hidden" name="icon" id="editCategoryIcon" required>
                    <div class="icon-picker-grid" id="editIconPicker">
                        <?php foreach ($suggestedIcons as $class => $label): ?>
                            <div class="icon-picker-item" data-icon="<?= $class ?>" onclick="selectPickerIcon('edit', '<?= $class ?>')">
                                <i class="fas <?= $class ?>"></i>
                                <div class="icon-picker-label"><?= $label ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" name="status" id="editCategoryStatus">
                        <option value="aktif">Active</option>
                        <option value="tidak aktif">Inactive</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex: 1; border: 1px solid var(--border); background: var(--white); height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; background-color: var(--accent-blue); color: var(--white); border: none; height: 46px; border-radius: 8px; font-weight: 700; cursor: pointer;">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden delete form -->
    <form id="delete-form" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-id">
    </form>

    <script>
    function selectPickerIcon(prefix, iconClass) {
        document.getElementById(prefix + 'CategoryIcon').value = iconClass;
        const grid = document.getElementById(prefix + 'IconPicker');
        const items = grid.querySelectorAll('.icon-picker-item');
        items.forEach(item => {
            if (item.getAttribute('data-icon') === iconClass) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }
    
    function openAddModal(){
        selectPickerIcon('add', 'fa-graduation-cap');
        document.getElementById('addModal').style.display='flex';
    }
    function closeAddModal(){document.getElementById('addModal').style.display='none'}
    
    function openEditModal(id, name, color, icon, status, points){
        document.getElementById('editCategoryId').value = id;
        document.getElementById('editCategoryName').value = name;
        document.getElementById('editCategoryColor').value = color;
        document.getElementById('editCategoryIcon').value = icon;
        document.getElementById('editCategoryStatus').value = status;
        document.getElementById('editCategoryPoints').value = points;
        selectPickerIcon('edit', icon);
        document.getElementById('editModal').style.display='flex';
    }
    function closeEditModal(){document.getElementById('editModal').style.display='none'}
    
    function deleteCategory(id, name, count) {
        let msg = "Are you sure you want to permanently delete category '" + name + "'?";
        if (count > 0) {
            msg = "Category '" + name + "' is currently used by " + count + " programme(s). It cannot be permanently deleted, but its status will be changed to Inactive. Proceed?";
        }
        if (confirm(msg)) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-form').submit();
        }
    }
    
    window.onclick=function(e){
        if(e.target===document.getElementById('addModal')) closeAddModal();
        if(e.target===document.getElementById('editModal')) closeEditModal();
    }
    </script>
</body>
</html>

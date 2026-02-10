<?php
session_start();
$_SESSION['role'] = 'pentadbir';
$activePage = 'pengurusan-kategori';

// Sample categories data
$categories = [
    ['id' => 1, 'nama' => 'Kepimpinan', 'jumlahProgram' => 12, 'color' => '#f59e0b', 'icon' => 'fa-trophy'],
    ['id' => 2, 'nama' => 'Teknologi & IT', 'jumlahProgram' => 18, 'color' => '#3b82f6', 'icon' => 'fa-code'],
    ['id' => 3, 'nama' => 'Keusahawanan', 'jumlahProgram' => 8, 'color' => '#10b981', 'icon' => 'fa-briefcase'],
    ['id' => 4, 'nama' => 'Khidmat Komuniti', 'jumlahProgram' => 15, 'color' => '#ef4444', 'icon' => 'fa-heart'],
    ['id' => 5, 'nama' => 'Seni & Budaya', 'jumlahProgram' => 10, 'color' => '#8b5cf6', 'icon' => 'fa-palette'],
    ['id' => 6, 'nama' => 'Sukan & Kesihatan', 'jumlahProgram' => 14, 'color' => '#f97316', 'icon' => 'fa-dumbbell'],
    ['id' => 7, 'nama' => 'Akademik', 'jumlahProgram' => 20, 'color' => '#6366f1', 'icon' => 'fa-graduation-cap'],
    ['id' => 8, 'nama' => 'Antarabangsa', 'jumlahProgram' => 5, 'color' => '#06b6d4', 'icon' => 'fa-globe'],
    ['id' => 9, 'nama' => 'Media & Kreatif', 'jumlahProgram' => 7, 'color' => '#ec4899', 'icon' => 'fa-camera'],
    ['id' => 10, 'nama' => 'Kerjaya', 'jumlahProgram' => 9, 'color' => '#14b8a6', 'icon' => 'fa-chart-line'],
    ['id' => 11, 'nama' => 'Kelab & Persatuan', 'jumlahProgram' => 6, 'color' => '#84cc16', 'icon' => 'fa-users'],
    ['id' => 12, 'nama' => 'Penyelidikan', 'jumlahProgram' => 4, 'color' => '#8b5cf6', 'icon' => 'fa-flask'],
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $newCategory = trim($_POST['category_name']);
        if (!empty($newCategory)) {
            // In real app, insert into database
            // For demo, add to array
            $newId = count($categories) + 1;
            $categories[] = [
                'id' => $newId,
                'nama' => $newCategory,
                'jumlahProgram' => 0,
                'color' => '#6b7280', // Default gray
                'icon' => 'fa-tag' // Default icon
            ];
            
            echo "<script>alert('Kategori \"' + " . json_encode($newCategory) . " + '\" telah ditambah.');</script>";
        }
    } elseif (isset($_POST['delete_category'])) {
        $categoryId = $_POST['category_id'];
        $categoryName = $_POST['category_name'];
        
        // In real app, check if category has programs before deleting
        // For demo, just show confirmation
        echo "<script>
            if (confirm('Adakah anda pasti mahu memadam kategori \"$categoryName\"?\\n\\nKategori yang mempunyai program tidak boleh dipadam.')) {
                alert('Kategori \"$categoryName\" akan dipadam. (Simulasi)');
                // In real app, you would redirect or reload
            }
        </script>";
    }
}

// Calculate totals
$totalCategories = count($categories);
$totalPrograms = array_sum(array_column($categories, 'jumlahProgram'));
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Kategori | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .category-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .category-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .category-info {
            flex: 1;
        }
        
        .category-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .category-programs {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .category-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        
        /* Buttons */
        .btn-edit {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-edit:hover {
            background: rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }
        
        .btn-delete {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            background: transparent;
            border: 2px solid #ef4444;
            color: #ef4444;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.1);
            transform: translateY(-1px);
        }
        
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
        }
        
        .btn-add:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
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
        
        .stat-value.categories { color: var(--primary); }
        .stat-value.programs { color: #10b981; }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: var(--radius);
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
        }
        
        .modal-header {
            margin-bottom: 24px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .modal-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        
        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text-primary);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-cancel:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .btn-submit {
            flex: 1;
            padding: 12px;
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
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
        
        /* Warning Badge */
        .warning-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 8px;
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
                    <h1 class="page-title">Pengurusan Kategori</h1>
                    <p class="page-subtitle">Urus kategori program untuk sistem</p>
                </div>
                <button class="btn-add" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </button>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value categories"><?= $totalCategories ?></div>
                    <div class="stat-label">Jumlah Kategori</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value programs"><?= $totalPrograms ?></div>
                    <div class="stat-label">Jumlah Program</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #8b5cf6;">
                        <?= round($totalPrograms / max($totalCategories, 1), 1) ?>
                    </div>
                    <div class="stat-label">Purata Program/Kategori</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #f59e0b;">
                        <?= count(array_filter($categories, function($cat) { return $cat['jumlahProgram'] > 10; })) ?>
                    </div>
                    <div class="stat-label">Kategori Aktif (>10 program)</div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="categories-grid">
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <div class="category-icon" style="background: <?= $category['color'] ?>">
                                    <i class="fas <?= $category['icon'] ?>"></i>
                                </div>
                                <div class="category-info">
                                    <h3 class="category-name"><?= htmlspecialchars($category['nama']) ?></h3>
                                    <p class="category-programs">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= $category['jumlahProgram'] ?> program
                                    </p>
                                    <?php if ($category['jumlahProgram'] > 10): ?>
                                        <span class="warning-badge">
                                            <i class="fas fa-fire"></i> Kategori Aktif
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="category-actions">
                                <button class="btn-edit" onclick="editCategory(<?= $category['id'] ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <form method="POST" style="display: inline; flex: 1;">
                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                    <input type="hidden" name="category_name" value="<?= htmlspecialchars($category['nama']) ?>">
                                    <button type="submit" name="delete_category" class="btn-delete">
                                        <i class="fas fa-trash-alt"></i> Padam
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div class="empty-state-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h3>Tiada Kategori</h3>
                        <p>Belum ada kategori program ditambah. Sila tambah kategori pertama anda.</p>
                        <button class="btn-add" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Tambah Kategori Pertama
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Kategori Baharu</h2>
            <p class="modal-subtitle">Masukkan nama kategori program baharu</p>
        </div>
        
        <form method="POST" id="addCategoryForm">
            <div class="form-group">
                <label class="form-label">Nama Kategori</label>
                <input type="text" 
                       name="category_name" 
                       class="form-input" 
                       placeholder="Contoh: Keusahawanan Digital"
                       required
                       autofocus>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">
                    Batal
                </button>
                <button type="submit" name="add_category" class="btn-submit">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Kategori</h2>
            <p class="modal-subtitle">Kemaskini maklumat kategori</p>
        </div>
        
        <form method="POST" id="editCategoryForm">
            <input type="hidden" name="category_id" id="editCategoryId">
            
            <div class="form-group">
                <label class="form-label">Nama Kategori</label>
                <input type="text" 
                       name="category_name" 
                       id="editCategoryName"
                       class="form-input" 
                       required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Warna Kategori</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php
                    $colorOptions = [
                        '#f59e0b', '#3b82f6', '#10b981', '#ef4444', 
                        '#8b5cf6', '#f97316', '#6366f1', '#06b6d4',
                        '#ec4899', '#14b8a6', '#84cc16', '#6b7280'
                    ];
                    
                    foreach ($colorOptions as $color):
                    ?>
                        <label style="display: inline-block;">
                            <input type="radio" 
                                   name="category_color" 
                                   value="<?= $color ?>" 
                                   class="color-radio"
                                   style="display: none;">
                            <span class="color-option" 
                                  style="display: inline-block; width: 40px; height: 40px; border-radius: 8px; background: <?= $color ?>; cursor: pointer; border: 2px solid transparent;"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Ikon Kategori</label>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                    <?php
                    $iconOptions = [
                        'fa-trophy', 'fa-code', 'fa-briefcase', 'fa-heart',
                        'fa-palette', 'fa-dumbbell', 'fa-graduation-cap', 'fa-globe',
                        'fa-camera', 'fa-chart-line', 'fa-users', 'fa-flask',
                        'fa-music', 'fa-book', 'fa-lightbulb', 'fa-star'
                    ];
                    
                    foreach ($iconOptions as $icon):
                    ?>
                        <label style="text-align: center; cursor: pointer;">
                            <input type="radio" 
                                   name="category_icon" 
                                   value="<?= $icon ?>" 
                                   class="icon-radio"
                                   style="display: none;">
                            <div class="icon-option" 
                                 style="padding: 10px; border: 2px solid var(--border); border-radius: 8px; transition: all 0.2s ease;">
                                <i class="fas <?= $icon ?>" style="font-size: 20px; color: var(--text-secondary);"></i>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">
                    Batal
                </button>
                <button type="submit" name="update_category" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal functions
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }
    
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
        document.getElementById('addCategoryForm').reset();
    }
    
    function openEditModal() {
        document.getElementById('editModal').style.display = 'block';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Edit category
    function editCategory(categoryId) {
        // In real app, fetch category data via AJAX
        // For demo, just show the modal
        openEditModal();
        
        // In real app, you would populate the form with existing data
        // Example:
        // fetch(`get_category.php?id=${categoryId}`)
        //     .then(response => response.json())
        //     .then(data => {
        //         document.getElementById('editCategoryId').value = data.id;
        //         document.getElementById('editCategoryName').value = data.nama;
        //         // Set selected color and icon
        //         openEditModal();
        //     });
    }
    
    // Add interactivity to color and icon options
    document.addEventListener('DOMContentLoaded', function() {
        // Color options
        const colorRadios = document.querySelectorAll('.color-radio');
        const colorOptions = document.querySelectorAll('.color-option');
        
        colorOptions.forEach((option, index) => {
            option.addEventListener('click', function() {
                // Remove selected class from all
                colorOptions.forEach(opt => {
                    opt.style.borderColor = 'transparent';
                    opt.style.boxShadow = 'none';
                });
                
                // Add selected class to clicked
                this.style.borderColor = 'var(--primary)';
                this.style.boxShadow = '0 0 0 2px rgba(37, 99, 235, 0.2)';
                
                // Check corresponding radio
                colorRadios[index].checked = true;
            });
        });
        
        // Icon options
        const iconRadios = document.querySelectorAll('.icon-radio');
        const iconOptions = document.querySelectorAll('.icon-option');
        
        iconOptions.forEach((option, index) => {
            option.addEventListener('click', function() {
                // Remove selected class from all
                iconOptions.forEach(opt => {
                    opt.style.borderColor = 'var(--border)';
                    opt.style.backgroundColor = 'transparent';
                    opt.querySelector('i').style.color = 'var(--text-secondary)';
                });
                
                // Add selected class to clicked
                this.style.borderColor = 'var(--primary)';
                this.style.backgroundColor = 'rgba(37, 99, 235, 0.1)';
                this.querySelector('i').style.color = 'var(--primary)';
                
                // Check corresponding radio
                iconRadios[index].checked = true;
            });
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeAddModal();
                closeEditModal();
            }
        }
        
        // Handle Enter key in add modal
        document.getElementById('addCategoryForm')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.name === 'category_name') {
                e.preventDefault();
                this.submit();
            }
        });
    });
    
    // Show notification
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? 'var(--primary)' : '#ef4444'};
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
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i>
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
</script>

</body>
</html>
<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'pilihan-minat';
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilihan Minat | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles for Pilihan Minat */
        .interests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        
        .interest-card {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .interest-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .interest-card.selected {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        
        .interest-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .interest-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .interest-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .selection-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        
        .success-message {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 500;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .checkbox-wrapper {
            position: relative;
            width: 20px;
            height: 20px;
        }
        
        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 6px;
            position: relative;
            cursor: pointer;
        }
        
        .custom-checkbox.selected {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .custom-checkbox.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        
        .card-header {
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .card-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
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
                <h1 class="page-title">Pilihan Minat</h1>
                <p class="page-subtitle">Pilih kategori yang anda minati untuk mendapatkan cadangan program yang lebih relevan</p>
            </div>

            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Kategori Minat</h2>
                    <p class="card-subtitle">Pilih semua kategori yang berkaitan dengan minat anda</p>
                </div>

                <div class="interests-grid">
                    <?php
                    $categories = [
                        ['id' => 'kepimpinan', 'label' => 'Kepimpinan', 'icon' => 'fa-trophy', 'color' => '#f59e0b'],
                        ['id' => 'teknologi', 'label' => 'Teknologi & IT', 'icon' => 'fa-code', 'color' => '#3b82f6'],
                        ['id' => 'keusahawanan', 'label' => 'Keusahawanan', 'icon' => 'fa-briefcase', 'color' => '#10b981'],
                        ['id' => 'komuniti', 'label' => 'Khidmat Komuniti', 'icon' => 'fa-heart', 'color' => '#ef4444'],
                        ['id' => 'seni', 'label' => 'Seni & Budaya', 'icon' => 'fa-palette', 'color' => '#8b5cf6'],
                        ['id' => 'sukan', 'label' => 'Sukan & Kesihatan', 'icon' => 'fa-dumbbell', 'color' => '#f97316'],
                        ['id' => 'akademik', 'label' => 'Akademik', 'icon' => 'fa-graduation-cap', 'color' => '#6366f1'],
                        ['id' => 'antarabangsa', 'label' => 'Antarabangsa', 'icon' => 'fa-globe', 'color' => '#06b6d4'],
                        ['id' => 'persatuan', 'label' => 'Kelab & Persatuan', 'icon' => 'fa-users', 'color' => '#84cc16'],
                        ['id' => 'muzik', 'label' => 'Muzik & Persembahan', 'icon' => 'fa-music', 'color' => '#ec4899'],
                        ['id' => 'media', 'label' => 'Media & Fotografi', 'icon' => 'fa-camera', 'color' => '#14b8a6'],
                        ['id' => 'ilmiah', 'label' => 'Penyelidikan & Inovasi', 'icon' => 'fa-book-open', 'color' => '#8b5cf6'],
                    ];
                    
                    // Simulate selected categories (in real app, fetch from database)
                    $selectedCategories = ['kepimpinan', 'teknologi', 'komuniti'];
                    
                    foreach ($categories as $category):
                        $isSelected = in_array($category['id'], $selectedCategories);
                    ?>
                        <div class="interest-card <?= $isSelected ? 'selected' : '' ?>" 
                             onclick="toggleCategory('<?= $category['id'] ?>')"
                             data-category-id="<?= $category['id'] ?>">
                            <div class="interest-content">
                                <div class="checkbox-wrapper">
                                    <div class="custom-checkbox <?= $isSelected ? 'selected' : '' ?>"></div>
                                </div>
                                <div class="interest-icon" style="background: <?= $category['color'] ?>">
                                    <i class="fas <?= $category['icon'] ?>"></i>
                                </div>
                                <span class="interest-label"><?= $category['label'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="selection-info">
                    <p class="selected-count">
                        <span id="selectedCount"><?= count($selectedCategories) ?></span> kategori dipilih
                    </p>
                    <div class="flex items-center gap-3">
                        <div id="successMessage" class="success-message" style="display: none;">
                            ✓ Pilihan disimpan
                        </div>
                        <button onclick="savePreferences()" class="btn-primary">
                            Simpan Pilihan Minat
                        </button>
                    </div>
                </div>
            </div>

        </section>
    </main>
</div>

<script>
    let selectedCategories = <?= json_encode($selectedCategories) ?>;
    
    function toggleCategory(categoryId) {
        const card = document.querySelector(`[data-category-id="${categoryId}"]`);
        const checkbox = card.querySelector('.custom-checkbox');
        
        if (selectedCategories.includes(categoryId)) {
            // Remove from selected
            selectedCategories = selectedCategories.filter(id => id !== categoryId);
            card.classList.remove('selected');
            checkbox.classList.remove('selected');
        } else {
            // Add to selected
            selectedCategories.push(categoryId);
            card.classList.add('selected');
            checkbox.classList.add('selected');
        }
        
        // Update count
        document.getElementById('selectedCount').textContent = selectedCategories.length;
        
        // Hide success message if shown
        document.getElementById('successMessage').style.display = 'none';
    }
    
    function savePreferences() {
        if (selectedCategories.length === 0) {
            alert("Sila pilih sekurang-kurangnya satu kategori minat");
            return;
        }
        
        // Here you would typically send the data to server via AJAX
        // For now, just show success message
        const successMessage = document.getElementById('successMessage');
        successMessage.style.display = 'block';
        
        // Hide message after 3 seconds
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
        
        // Example of sending data to server (uncomment when backend is ready)
        /*
        fetch('save_interests.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                interests: selectedCategories
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successMessage.style.display = 'block';
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 3000);
            }
        });
        */
        
        console.log('Selected categories:', selectedCategories);
    }
    
    // Add hover effects
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.interest-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('selected')) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('selected')) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });
    });
</script>

</body>
</html>
<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'dashboard';
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-layout">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP BAR -->
        <header class="topbar">
            <div class="search-box">
                <input type="text" placeholder="🔍 Cari program, bengkel, seminar..." />
                <button>Cari</button>
            </div>
            <div class="topbar-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <section class="content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="page-title">Selamat Datang, Pelajar!</h1>
                <p class="page-subtitle">Terokai program dan aktiviti menarik untuk perkembangan anda</p>
            </div>

            <!-- Programs Section -->
            <div class="section-header">
                <h2 class="section-title">Program akan datang</h2>
                <a href="#" class="view-all">Lihat Semua →</a>
            </div>

            <!-- PROGRAM GRID -->
            <div class="program-grid">
                <?php
                $programs = [
                    [
                        'title' => 'Workshop Kepimpinan Mahasiswa',
                        'date' => '25 Januari 2026',
                        'location' => 'UKM Bangi',
                        'image' => 'program1.jpg',
                        'tag' => 'Kepimpinan',
                        'icon' => 'fa-users'
                    ],
                    [
                        'title' => 'Seminar Inovasi Digital',
                        'date' => '28 Januari 2026',
                        'location' => 'Dewan Tun Canselor',
                        'image' => 'program2.jpg',
                        'tag' => 'Teknologi',
                        'icon' => 'fa-laptop-code'
                    ],
                    [
                        'title' => 'Program Sukarelawan Komuniti',
                        'date' => '2 Februari 2026',
                        'location' => 'Selangor',
                        'image' => 'program3.jpg',
                        'tag' => 'Sukarelawan',
                        'icon' => 'fa-hands-helping'
                    ],
                    [
                        'title' => 'Forum Kerjaya Graduan',
                        'date' => '5 Februari 2026',
                        'location' => 'Kuala Lumpur',
                        'image' => 'program4.jpg',
                        'tag' => 'Kerjaya',
                        'icon' => 'fa-briefcase'
                    ]
                ];
                
                $image_folder = 'images/'; // Folder where your images are stored
                
                foreach ($programs as $index => $program):
                    $image_path = $image_folder . $program['image'];
                ?>
                    <div class="program-card">
                        <div class="image-container">
                            <img src="<?= $image_path ?>" alt="<?= $program['title'] ?>" class="program-image">
                            <?php if(!file_exists($image_path)): ?>
                                <!-- Fallback if image doesn't exist -->
                                <div class="image-fallback program-<?= $index + 1 ?>">
                                    <i class="fas <?= $program['icon'] ?> placeholder-icon"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="program-content">
                            <span class="program-tag"><?= $program['tag'] ?></span>
                            <h3><?= $program['title'] ?></h3>
                            <div class="program-meta">
                                <span class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <?= $program['date'] ?>
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= $program['location'] ?>
                                </span>
                            </div>
                            <button class="btn-view">Lihat Program</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </section>
    </main>
</div>

<style>
    /* ===== GLOBAL STYLE ===== */
body {
    background: #f5f7fb;
    font-family: 'Segoe UI', sans-serif;
    color: #1e293b;
}

/* ===== MAIN CONTENT ===== */
.main-content {
    padding: 30px 40px;
}

/* ===== TOPBAR ===== */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 16px 24px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    margin-bottom: 30px;
}

/* SEARCH BOX */
.search-box {
    display: flex;
    gap: 10px;
}

.search-box input {
    padding: 10px 14px;
    width: 320px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    transition: 0.2s;
}

.search-box input:focus {
    outline: none;
    border-color: #003366;
    box-shadow: 0 0 0 3px rgba(0,51,102,0.08);
}

.search-box button {
    background: #003366;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.search-box button:hover {
    background: #002855;
}

/* ===== NOTIFICATION ===== */
.notification-btn {
    background: #f1f5f9;
    border: none;
    padding: 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.3s;
}

.notification-btn:hover {
    background: #e2e8f0;
}

/* ===== WELCOME ===== */
.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #003366;
}

.page-subtitle {
    color: #64748b;
    margin-top: 6px;
}

/* ===== SECTION HEADER ===== */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 40px;
}

.section-title {
    font-size: 22px;
    font-weight: 700;
}

.view-all {
    color: #003366;
    font-weight: 600;
    transition: 0.3s;
}

.view-all:hover {
    color: #FF6600;
}

/* ===== PROGRAM GRID ===== */
.program-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px,1fr));
    gap: 24px;
    margin-top: 20px;
}

/* ===== PROGRAM CARD ===== */
.program-card {
    background: white;
    border-radius: 18px;
    overflow: hidden;
    transition: 0.3s;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
}

.program-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.08);
}

/* IMAGE */
.image-container {
    height: 180px;
    overflow: hidden;
}

.program-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: 0.4s;
}

.program-card:hover .program-image {
    transform: scale(1.08);
}

/* ===== CONTENT ===== */
.program-content {
    padding: 20px;
}

.program-tag {
    display: inline-block;
    background: rgba(0,51,102,0.08);
    color: #003366;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    margin-bottom: 10px;
}

.program-content h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 12px;
}

/* META */
.program-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 14px;
    color: #64748b;
    margin-bottom: 16px;
}

.meta-item i {
    margin-right: 6px;
    color: #003366;
}

/* ===== BUTTON ===== */
.btn-view {
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: #003366;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.btn-view:hover {
    background: #FF6600;
}

/* ===== FALLBACK IMAGE ===== */
.image-fallback {
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 42px;
    height: 100%;
    color: white;
}

.program-1 { background: linear-gradient(135deg,#4f46e5,#7c3aed); }
.program-2 { background: linear-gradient(135deg,#f43f5e,#fb7185); }
.program-3 { background: linear-gradient(135deg,#0ea5e9,#22c55e); }
.program-4 { background: linear-gradient(135deg,#f59e0b,#ef4444); }

</style>

<script>
    // Optional: Add functionality for notifications
    document.querySelector('.notification-btn').addEventListener('click', function() {
        alert('Anda mempunyai 3 notifikasi baru!');
    });
    
    // Add hover effect to program cards
    document.querySelectorAll('.btn-view').forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 12px rgba(37, 99, 235, 0.2)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Check if images load properly
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.program-image').forEach(img => {
            img.onerror = function() {
                this.style.display = 'none';
            };
        });
    });
</script>

</body>
</html>
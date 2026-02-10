<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'cari-program';

// Sample programs data
$programs = [
    [
        'id' => 1,
        'title' => 'Workshop Kepimpinan Mahasiswa',
        'date' => '2026-01-25',
        'time' => '9:00 AM - 5:00 PM',
        'location' => 'Dewan Tun Canselor',
        'category' => 'Kepimpinan',
        'description' => 'Program latihan kepimpinan intensif untuk mahasiswa',
        'image' => 'program1.jpg',
        'participants' => 45,
        'capacity' => 100,
        'status' => 'available',
        'rating' => 4.8
    ],
    [
        'id' => 2,
        'title' => 'Seminar Inovasi Digital',
        'date' => '2026-01-28',
        'time' => '2:00 PM - 5:00 PM',
        'location' => 'Auditorium FSKTM',
        'category' => 'Teknologi',
        'description' => 'Seminar mengenai teknologi digital terkini',
        'image' => 'program2.jpg',
        'participants' => 120,
        'capacity' => 150,
        'status' => 'available',
        'rating' => 4.7
    ],
    [
        'id' => 3,
        'title' => 'Program Sukarelawan Komuniti',
        'date' => '2026-02-02',
        'time' => '8:00 AM - 12:00 PM',
        'location' => 'Komuniti Bangi',
        'category' => 'Komuniti',
        'description' => 'Program khidmat masyarakat di kawasan setempat',
        'image' => 'program3.jpg',
        'participants' => 30,
        'capacity' => 50,
        'status' => 'available',
        'rating' => 4.9
    ],
    [
        'id' => 4,
        'title' => 'Forum Kerjaya Graduan',
        'date' => '2026-02-15',
        'time' => '9:00 AM - 1:00 PM',
        'location' => 'Dewan Kuliah Utama',
        'category' => 'Kerjaya',
        'description' => 'Forum berkongsi peluang kerjaya untuk graduan',
        'image' => 'program4.jpg',
        'participants' => 85,
        'capacity' => 200,
        'status' => 'available',
        'rating' => 4.6
    ],
    [
        'id' => 5,
        'title' => 'Bengkel Penulisan Ilmiah',
        'date' => '2026-01-30',
        'time' => '2:00 PM - 5:00 PM',
        'location' => 'Perpustakaan',
        'category' => 'Akademik',
        'description' => 'Bengkel teknik penulisan akademik yang efektif',
        'image' => 'program1.jpg',
        'participants' => 25,
        'capacity' => 30,
        'status' => 'full',
        'rating' => 4.5
    ],
    [
        'id' => 6,
        'title' => 'Kem Jati Diri',
        'date' => '2026-03-05',
        'time' => '8:00 AM - 6:00 PM',
        'location' => 'Kem Bina Semangat',
        'category' => 'Sukan',
        'description' => 'Kem pembangunan diri dan fizikal',
        'image' => 'program3.jpg',
        'participants' => 40,
        'capacity' => 40,
        'status' => 'full',
        'rating' => 4.4
    ],
    [
        'id' => 7,
        'title' => 'Workshop Kreativiti & Inovasi',
        'date' => '2026-02-10',
        'time' => '10:00 AM - 4:00 PM',
        'location' => 'Bilik Seminar FEP',
        'category' => 'Keusahawanan',
        'description' => 'Bengkel untuk membangunkan idea kreatif',
        'image' => 'program2.jpg',
        'participants' => 60,
        'capacity' => 80,
        'status' => 'available',
        'rating' => 4.3
    ],
    [
        'id' => 8,
        'title' => 'Program Seni Budaya',
        'date' => '2026-02-22',
        'time' => '7:00 PM - 10:00 PM',
        'location' => 'Dewan Budaya',
        'category' => 'Seni',
        'description' => 'Pertunjukan dan bengkel seni tradisional',
        'image' => 'program5.jpg',
        'participants' => 90,
        'capacity' => 150,
        'status' => 'available',
        'rating' => 4.7
    ],
];

// Get all unique categories
$categories = array_values(array_unique(array_column($programs, 'category')));
sort($categories);

// Get all unique locations
$locations = array_values(array_unique(array_column($programs, 'location')));
sort($locations);

// Handle filters
$searchQuery = $_GET['search'] ?? '';
$selectedCategory = $_GET['category'] ?? 'semua';
$selectedLocation = $_GET['location'] ?? 'semua';
$selectedDate = $_GET['date'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';

// Filter programs
$filteredPrograms = array_filter($programs, function($program) use ($searchQuery, $selectedCategory, $selectedLocation, $selectedDate) {
    // Search filter
    if ($searchQuery && 
        stripos($program['title'], $searchQuery) === false && 
        stripos($program['description'], $searchQuery) === false) {
        return false;
    }
    
    // Category filter
    if ($selectedCategory !== 'semua' && $program['category'] !== $selectedCategory) {
        return false;
    }
    
    // Location filter
    if ($selectedLocation !== 'semua' && $program['location'] !== $selectedLocation) {
        return false;
    }
    
    // Date filter
    if ($selectedDate && $program['date'] !== $selectedDate) {
        return false;
    }
    
    return true;
});

// Sort programs
usort($filteredPrograms, function($a, $b) use ($sortBy) {
    switch ($sortBy) {
        case 'newest':
            return strtotime($b['date']) <=> strtotime($a['date']);
        case 'oldest':
            return strtotime($a['date']) <=> strtotime($b['date']);
        case 'rating':
            return $b['rating'] <=> $a['rating'];
        case 'popular':
            return $b['participants'] <=> $a['participants'];
        default:
            return strtotime($b['date']) <=> strtotime($a['date']);
    }
});

// Calculate availability
foreach ($filteredPrograms as &$program) {
    $program['availability'] = $program['capacity'] - $program['participants'];
    $program['availability_percent'] = round(($program['availability'] / $program['capacity']) * 100);
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Program | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Search & Filter Section */
        .search-section {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 32px;
        }
        
        .search-header {
            margin-bottom: 24px;
        }
        
        .search-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .search-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Main Search Bar */
        .main-search {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 24px 16px 56px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 16px;
            background: var(--surface);
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 24px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        /* Filters Grid */
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 20px;
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
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-select, .filter-input {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--surface);
            transition: all 0.2s ease;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        /* Sort Options */
        .sort-options {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        
        .sort-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .sort-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .sort-btn {
            padding: 8px 16px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .sort-btn:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .sort-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Results Count */
        .results-count {
            color: var(--text-secondary);
            font-size: 14px;
            margin-left: auto;
        }
        
        /* Programs Grid */
        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        
        /* Program Card */
        .program-card {
            background: var(--surface);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .program-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .program-image-container {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
        }
        
        .program-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .program-card:hover .program-image {
            transform: scale(1.05);
        }
        
        .program-badges {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        
        .category-badge {
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--text-primary);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-available {
            background: rgba(16, 185, 129, 0.95);
            color: white;
        }
        
        .status-full {
            background: rgba(239, 68, 68, 0.95);
            color: white;
        }
        
        .status-registered {
            background: rgba(37, 99, 235, 0.95);
            color: white;
        }
        
        .program-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .program-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .program-description {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 16px;
            flex: 1;
        }
        
        .program-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .meta-icon {
            width: 20px;
            color: var(--primary);
        }
        
        .program-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        
        .availability {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .availability-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .availability-bar {
            width: 100%;
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .availability-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .availability-low { background: #ef4444; }
        .availability-medium { background: #f59e0b; }
        .availability-high { background: #10b981; }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #f59e0b;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Action Buttons */
        .program-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        
        .btn-details {
            flex: 1;
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
        
        .btn-details:hover {
            background: rgba(37, 99, 235, 0.1);
        }
        
        .btn-register {
            flex: 1;
            padding: 10px;
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-register:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-register.disabled {
            background: var(--border);
            color: var(--text-tertiary);
            cursor: not-allowed;
            transform: none;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            grid-column: 1 / -1;
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
        
        /* Quick Filters */
        .quick-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .quick-filter-btn {
            padding: 8px 16px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .quick-filter-btn:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .quick-filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .programs-grid {
                grid-template-columns: 1fr;
            }
            
            .sort-options {
                flex-direction: column;
                align-items: stretch;
            }
            
            .results-count {
                margin-left: 0;
                margin-top: 12px;
            }
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
                <h1 class="page-title">Cari Program</h1>
                <p class="page-subtitle">Temui program menarik yang sesuai dengan minat anda</p>
            </div>

            <!-- Quick Filters -->
            <div class="quick-filters">
                <button class="quick-filter-btn" onclick="setFilter('category', 'Kepimpinan')">
                    <i class="fas fa-trophy"></i> Kepimpinan
                </button>
                <button class="quick-filter-btn" onclick="setFilter('category', 'Teknologi')">
                    <i class="fas fa-code"></i> Teknologi
                </button>
                <button class="quick-filter-btn" onclick="setFilter('category', 'Komuniti')">
                    <i class="fas fa-heart"></i> Komuniti
                </button>
                <button class="quick-filter-btn" onclick="setFilter('category', 'Kerjaya')">
                    <i class="fas fa-briefcase"></i> Kerjaya
                </button>
                <button class="quick-filter-btn" onclick="setFilter('date', '<?= date('Y-m-d') ?>')">
                    <i class="fas fa-calendar-day"></i> Hari Ini
                </button>
                <button class="quick-filter-btn" onclick="setFilter('status', 'available')">
                    <i class="fas fa-check-circle"></i> Ada Slot
                </button>
            </div>

            <!-- Search & Filter Section -->
            <form method="GET" class="search-section">
                <div class="search-header">
                    <h2 class="search-title">Cari Program yang Tepat untuk Anda</h2>
                    <p class="search-subtitle">Gunakan filter untuk mencari program yang paling sesuai</p>
                </div>

                <!-- Main Search -->
                <div class="main-search">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Cari program mengikut nama, deskripsi atau kata kunci..."
                           value="<?= htmlspecialchars($searchQuery) ?>">
                </div>

                <!-- Advanced Filters -->
                <div class="filters-grid">
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-tag"></i> Kategori
                        </label>
                        <select name="category" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?= $selectedCategory === 'semua' ? 'selected' : '' ?>>Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Location Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-map-marker-alt"></i> Lokasi
                        </label>
                        <select name="location" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?= $selectedLocation === 'semua' ? 'selected' : '' ?>>Semua Lokasi</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= htmlspecialchars($location) ?>" <?= $selectedLocation === $location ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($location) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar-alt"></i> Tarikh
                        </label>
                        <input type="date" 
                               name="date" 
                               class="filter-input" 
                               value="<?= htmlspecialchars($selectedDate) ?>"
                               onchange="this.form.submit()">
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="sort-options">
                    <span class="sort-label">Susun mengikut:</span>
                    <div class="sort-buttons">
                        <button type="button" class="sort-btn <?= $sortBy === 'newest' ? 'active' : '' ?>" onclick="setSort('newest')">
                            Terkini
                        </button>
                        <button type="button" class="sort-btn <?= $sortBy === 'rating' ? 'active' : '' ?>" onclick="setSort('rating')">
                            Rating Tertinggi
                        </button>
                        <button type="button" class="sort-btn <?= $sortBy === 'popular' ? 'active' : '' ?>" onclick="setSort('popular')">
                            Paling Popular
                        </button>
                        <button type="button" class="sort-btn <?= $sortBy === 'oldest' ? 'active' : '' ?>" onclick="setSort('oldest')">
                            Terawal
                        </button>
                    </div>
                    <span class="results-count">
                        <?= count($filteredPrograms) ?> program dijumpai
                    </span>
                </div>
                
                <!-- Hidden sort field -->
                <input type="hidden" name="sort" id="sortField" value="<?= $sortBy ?>">
            </form>

            <!-- Programs Grid -->
            <div class="programs-grid">
                <?php if (count($filteredPrograms) > 0): ?>
                    <?php foreach ($filteredPrograms as $program): 
                        $dateFormatted = date('d M Y', strtotime($program['date']));
                        $isFull = $program['status'] === 'full';
                        $availabilityClass = $program['availability_percent'] > 50 ? 'high' : 
                                          ($program['availability_percent'] > 20 ? 'medium' : 'low');
                    ?>
                        <div class="program-card">
                            <div class="program-image-container">
                                <img src="images/<?= $program['image'] ?>" alt="<?= htmlspecialchars($program['title']) ?>" class="program-image">
                                
                                <div class="program-badges">
                                    <span class="category-badge"><?= $program['category'] ?></span>
                                    <span class="status-badge status-<?= $program['status'] ?>">
                                        <?= $isFull ? 'Penuh' : 'Ada Slot' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="program-content">
                                <h3 class="program-title"><?= htmlspecialchars($program['title']) ?></h3>
                                <p class="program-description"><?= htmlspecialchars($program['description']) ?></p>
                                
                                <div class="program-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar meta-icon"></i>
                                        <span><?= $dateFormatted ?> • <?= $program['time'] ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt meta-icon"></i>
                                        <span><?= htmlspecialchars($program['location']) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-users meta-icon"></i>
                                        <span><?= $program['participants'] ?> / <?= $program['capacity'] ?> peserta</span>
                                    </div>
                                </div>
                                
                                <div class="program-stats">
                                    <div class="availability">
                                        <div class="availability-text">
                                            <?= $program['availability'] ?> slot tersedia
                                        </div>
                                        <div class="availability-bar">
                                            <div class="availability-fill availability-<?= $availabilityClass ?>" 
                                                 style="width: <?= $program['availability_percent'] ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <?= $program['rating'] ?>
                                    </div>
                                </div>
                                
                                <div class="program-actions">
                                    <button class="btn-details" onclick="viewProgramDetails(<?= $program['id'] ?>)">
                                        <i class="fas fa-info-circle"></i> Butiran
                                    </button>
                                    
                                    <?php if ($isFull): ?>
                                        <button class="btn-register disabled" disabled>
                                            <i class="fas fa-times"></i> Penuh
                                        </button>
                                    <?php else: ?>
                                        <a href="daftar-program-form.php?id=<?= $program['id'] ?>" class="btn-register">
                                            <i class="fas fa-user-plus"></i> Daftar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Tiada Program Dijumpai</h3>
                        <p>Tidak ada program yang sepadan dengan tapisan anda. Cuba ubah tetapan tapisan atau kata kunci carian.</p>
                        <a href="?" class="btn-register" style="display: inline-block; width: auto; padding: 12px 24px; text-decoration: none;">
                            <i class="fas fa-redo"></i> Reset Tapisan
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>
</div>

<!-- Program Details Modal -->
<div id="programModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background: white; margin: 2% auto; padding: 0; border-radius: var(--radius); max-width: 800px; width: 95%; max-height: 90vh; overflow-y: auto;">
        <!-- Modal content will be loaded here -->
    </div>
</div>

<script>
    // Set quick filter
    function setFilter(type, value) {
        const url = new URL(window.location.href);
        
        if (type === 'status' && value === 'available') {
            // Filter for available programs
            // In real app, you would submit a form or reload with filter
            showNotification('Menunjukkan program dengan slot tersedia');
            // You would need to implement actual filtering
        } else {
            url.searchParams.set(type, value);
            window.location.href = url.toString();
        }
    }
    
    // Set sort order
    function setSort(sortBy) {
        document.getElementById('sortField').value = sortBy;
        document.querySelector('form').submit();
    }
    
    // View program details
    function viewProgramDetails(programId) {
        // In real app, load program details via AJAX
        // For now, show alert with details
        alert('Butiran Program ID: ' + programId + '\n\nFungsi ini akan memaparkan maklumat lengkap program dalam modal.');
        
        // Example AJAX implementation:
        /*
        fetch(`get_program_details.php?id=${programId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('programModal').style.display = 'block';
                document.querySelector('.modal-content').innerHTML = data;
            });
        */
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
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('programModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
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
    
    // Auto-clear date filter for past dates
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.querySelector('input[name="date"]');
        if (dateInput.value) {
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                // Clear date filter for past dates
                dateInput.value = '';
                // Optionally, you can auto-submit the form:
                // dateInput.form.submit();
            }
        }
        
        // Highlight active quick filters
        updateActiveQuickFilters();
    });
    
    // Update active quick filters based on current URL
    function updateActiveQuickFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        const date = urlParams.get('date');
        
        // Remove active class from all quick filters
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to matching filters
        if (category) {
            document.querySelectorAll('.quick-filter-btn').forEach(btn => {
                if (btn.textContent.includes(category)) {
                    btn.classList.add('active');
                }
            });
        }
        
        if (date) {
            document.querySelectorAll('.quick-filter-btn').forEach(btn => {
                if (btn.textContent.includes('Hari Ini')) {
                    btn.classList.add('active');
                }
            });
        }
    }
</script>

</body>
</html>
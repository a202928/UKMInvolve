<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'recommended';

// In a real app, you would fetch this from database
$userInterests = ['kepimpinan', 'teknologi', 'komuniti', 'akademik'];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended For You | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Filter Section */
        .filter-section {
            display: flex;
            gap: 16px;
            margin: 24px 0;
            flex-wrap: wrap;
        }
        
        .filter-tag {
            padding: 8px 16px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-tag:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
        }
        
        .filter-tag.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .filter-tag.active .tag-count {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .tag-count {
            background: var(--background);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Program Cards */
        .match-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .interest-match {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        
        .interests-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 12px 0;
        }
        
        .program-image-container {
            position: relative;
        }
        
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
                <h1 class="page-title">Recommended For You</h1>
                <p class="page-subtitle">Program yang disyorkan berdasarkan minat anda</p>
            </div>

            <!-- User Interests Summary -->
            <div class="card">
                <h3 style="font-size: 18px; margin-bottom: 16px; color: var(--text-primary);">
                    📊 Minat Anda
                </h3>
                <div class="interests-container">
                    <?php
                    $interestLabels = [
                        'kepimpinan' => ['label' => 'Kepimpinan', 'icon' => 'fa-trophy', 'color' => '#f59e0b'],
                        'teknologi' => ['label' => 'Teknologi & IT', 'icon' => 'fa-code', 'color' => '#3b82f6'],
                        'komuniti' => ['label' => 'Khidmat Komuniti', 'icon' => 'fa-heart', 'color' => '#ef4444'],
                        'akademik' => ['label' => 'Akademik', 'icon' => 'fa-graduation-cap', 'color' => '#6366f1'],
                        'seni' => ['label' => 'Seni & Budaya', 'icon' => 'fa-palette', 'color' => '#8b5cf6'],
                        'sukan' => ['label' => 'Sukan & Kesihatan', 'icon' => 'fa-dumbbell', 'color' => '#f97316'],
                    ];
                    
                    foreach ($userInterests as $interest):
                        if (isset($interestLabels[$interest])):
                    ?>
                        <div class="interest-match">
                            <i class="fas <?= $interestLabels[$interest]['icon'] ?>" 
                               style="color: <?= $interestLabels[$interest]['color'] ?>"></i>
                            <?= $interestLabels[$interest]['label'] ?>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <p style="font-size: 14px; color: var(--text-secondary); margin-top: 12px;">
                    <i class="fas fa-lightbulb"></i> Algoritma kami mencadangkan program yang paling sesuai dengan minat anda
                </p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <button class="filter-tag active" onclick="filterPrograms('all')">
                    Semua Program
                    <span class="tag-count">8</span>
                </button>
                <button class="filter-tag" onclick="filterPrograms('high-match')">
                    <i class="fas fa-fire"></i>
                    Padanan Tinggi
                    <span class="tag-count">4</span>
                </button>
                <button class="filter-tag" onclick="filterPrograms('kepimpinan')">
                    <i class="fas fa-trophy"></i>
                    Kepimpinan
                    <span class="tag-count">3</span>
                </button>
                <button class="filter-tag" onclick="filterPrograms('teknologi')">
                    <i class="fas fa-code"></i>
                    Teknologi
                    <span class="tag-count">2</span>
                </button>
                <button class="filter-tag" onclick="filterPrograms('komuniti')">
                    <i class="fas fa-heart"></i>
                    Komuniti
                    <span class="tag-count">3</span>
                </button>
            </div>

            <!-- Program Grid -->
            <div class="program-grid" id="programsContainer">
                <?php
                // Algorithm to show programs matching user interests
                $allPrograms = [
                    [
                        'id' => 1,
                        'title' => 'Workshop Kepimpinan Mahasiswa 2026',
                        'date' => '15 Februari 2026',
                        'location' => 'Dewan Tun Canselor',
                        'image' => 'program1.jpg',
                        'tags' => ['Kepimpinan', 'Badan Beruniform'],
                        'interests' => ['kepimpinan'],
                        'matchScore' => 95, // High match for kepimpinan
                        'description' => 'Program latihan kepimpinan intensif untuk mahasiswa'
                    ],
                    [
                        'id' => 2,
                        'title' => 'Hackathon Inovasi Digital UKM',
                        'date' => '22 Februari 2026',
                        'location' => 'FTSM, UKM',
                        'image' => 'program2.jpg',
                        'tags' => ['Teknologi', 'Inovasi'],
                        'interests' => ['teknologi', 'akademik'],
                        'matchScore' => 88,
                        'description' => 'Pertandingan pembangunan aplikasi dalam 48 jam'
                    ],
                    [
                        'id' => 3,
                        'title' => 'Program Sukarelawan Pendidikan Luar Bandar',
                        'date' => '28 Februari 2026',
                        'location' => 'Kampung Orang Asli, Pahang',
                        'image' => 'program3.jpg',
                        'tags' => ['Sukarelawan', 'Komuniti'],
                        'interests' => ['komuniti'],
                        'matchScore' => 85,
                        'description' => 'Program khidmat masyarakat membantu pendidikan luar bandar'
                    ],
                    [
                        'id' => 4,
                        'title' => 'Forum Kepimpinan Belia Nasional',
                        'date' => '5 Mac 2026',
                        'location' => 'KL Convention Centre',
                        'image' => 'program4.jpg',
                        'tags' => ['Kepimpinan', 'Jaringan'],
                        'interests' => ['kepimpinan', 'komuniti'],
                        'matchScore' => 92,
                        'description' => 'Forum kepimpinan belia peringkat kebangsaan'
                    ],
                    [
                        'id' => 5,
                        'title' => 'Bengkel AI & Machine Learning',
                        'date' => '12 Mac 2026',
                        'location' => 'Makmal Komputer FTSM',
                        'image' => 'program1.jpg', // Reusing image
                        'tags' => ['Teknologi', 'AI', 'Akademik'],
                        'interests' => ['teknologi', 'akademik'],
                        'matchScore' => 90,
                        'description' => 'Bengkel praktikal pembangunan model AI'
                    ],
                    [
                        'id' => 6,
                        'title' => 'Kem Kepimpinan Pelajar',
                        'date' => '19 Mac 2026',
                        'location' => 'Kem Bina Semangat, Genting',
                        'image' => 'program3.jpg', // Reusing image
                        'tags' => ['Kepimpinan', 'Latihan', 'Sukan'],
                        'interests' => ['kepimpinan', 'sukan'],
                        'matchScore' => 87,
                        'description' => 'Kem latihan kepimpinan dan pembinaan team'
                    ],
                    [
                        'id' => 7,
                        'title' => 'Program Mentor-Mentee Fakulti',
                        'date' => '26 Mac 2026',
                        'location' => 'Fakulti masing-masing',
                        'image' => 'program2.jpg', // Reusing image
                        'tags' => ['Akademik', 'Pembangunan Diri'],
                        'interests' => ['akademik', 'kepimpinan'],
                        'matchScore' => 82,
                        'description' => 'Program bimbingan akademik dan kerjaya'
                    ],
                    [
                        'id' => 8,
                        'title' => 'Tech Conference 2026',
                        'date' => '2 April 2026',
                        'location' => 'MITEC, KL',
                        'image' => 'program4.jpg', // Reusing image
                        'tags' => ['Teknologi', 'Jaringan', 'Inovasi'],
                        'interests' => ['teknologi'],
                        'matchScore' => 94,
                        'description' => 'Konferensi teknologi terbesar di Malaysia'
                    ],
                ];

                // Filter programs based on user interests (algorithm)
                $recommendedPrograms = [];
                foreach ($allPrograms as $program) {
                    $matchCount = 0;
                    foreach ($program['interests'] as $programInterest) {
                        if (in_array($programInterest, $userInterests)) {
                            $matchCount++;
                        }
                    }
                    
                    // Calculate match percentage
                    if ($matchCount > 0) {
                        $program['matchCount'] = $matchCount;
                        $program['totalInterests'] = count($program['interests']);
                        $program['matchPercentage'] = ($matchCount / count($program['interests'])) * 100;
                        $recommendedPrograms[] = $program;
                    }
                }

                // Sort by match score (descending)
                usort($recommendedPrograms, function($a, $b) {
                    return $b['matchScore'] <=> $a['matchScore'];
                });

                if (count($recommendedPrograms) > 0):
                    foreach ($recommendedPrograms as $program):
                        $matchLevel = $program['matchScore'] >= 90 ? 'high' : ($program['matchScore'] >= 80 ? 'medium' : 'low');
                ?>
                    <div class="program-card" data-match="<?= $matchLevel ?>" data-interests="<?= implode(',', $program['interests']) ?>">
                        <div class="program-image-container">
                            <img src="images/<?= $program['image'] ?>" alt="<?= $program['title'] ?>" class="program-image">
                            <div class="match-badge">
                                <i class="fas fa-bolt"></i>
                                <?= $program['matchScore'] ?>% Match
                            </div>
                        </div>
                        <div class="program-content">
                            <!-- Tags -->
                            <div style="margin-bottom: 8px;">
                                <?php foreach ($program['tags'] as $tag): ?>
                                    <span class="program-tag"><?= $tag ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <h3><?= $program['title'] ?></h3>
                            <p style="font-size: 14px; color: var(--text-secondary); margin: 8px 0 12px;">
                                <?= $program['description'] ?>
                            </p>
                            
                            <!-- Matching Interests -->
                            <div class="interests-container">
                                <span style="font-size: 12px; color: var(--text-secondary); margin-right: 8px;">
                                    <i class="fas fa-check-circle" style="color: #10b981;"></i> Sesuai dengan:
                                </span>
                                <?php 
                                foreach ($program['interests'] as $interest):
                                    if (in_array($interest, $userInterests) && isset($interestLabels[$interest])):
                                ?>
                                    <div class="interest-match">
                                        <i class="fas <?= $interestLabels[$interest]['icon'] ?>"></i>
                                        <?= $interestLabels[$interest]['label'] ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                            
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
                <?php 
                    endforeach;
                else: 
                ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Tiada Program Disyorkan</h3>
                    <p>Sila kemaskini pilihan minat anda untuk mendapatkan cadangan program yang lebih relevan.</p>
                    <a href="pilihan-minat.php" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 24px;">
                        <i class="fas fa-edit"></i> Kemaskini Minat
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </section>
    </main>
</div>

<script>
    // Filter programs by category
    function filterPrograms(filter) {
        const programs = document.querySelectorAll('.program-card');
        const filterButtons = document.querySelectorAll('.filter-tag');
        
        // Update active filter button
        filterButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.includes(filter.replace('-', ' ')) || 
                (filter === 'all' && btn.textContent.includes('Semua'))) {
                btn.classList.add('active');
            }
        });
        
        // Filter programs
        let visibleCount = 0;
        programs.forEach(program => {
            if (filter === 'all') {
                program.style.display = 'block';
                visibleCount++;
            } else if (filter === 'high-match') {
                const matchScore = parseInt(program.querySelector('.match-badge').textContent);
                if (matchScore >= 90) {
                    program.style.display = 'block';
                    visibleCount++;
                } else {
                    program.style.display = 'none';
                }
            } else {
                const interests = program.getAttribute('data-interests');
                if (interests.includes(filter)) {
                    program.style.display = 'block';
                    visibleCount++;
                } else {
                    program.style.display = 'none';
                }
            }
        });
        
        // Show empty state if no programs
        const container = document.getElementById('programsContainer');
        if (visibleCount === 0 && filter !== 'all') {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3>Tiada Program dalam Kategori Ini</h3>
                    <p>Tidak ada program yang sesuai dengan filter "${filter}" pada masa ini.</p>
                    <button onclick="filterPrograms('all')" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 24px;">
                        <i class="fas fa-redo"></i> Tunjukkan Semua Program
                    </button>
                </div>
            `;
        }
    }
    
    // Add hover effects
    document.addEventListener('DOMContentLoaded', function() {
        const matchBadges = document.querySelectorAll('.match-badge');
        matchBadges.forEach(badge => {
            const score = parseInt(badge.textContent);
            if (score >= 90) {
                badge.style.background = 'linear-gradient(135deg, #10b981, #34d399)';
            } else if (score >= 80) {
                badge.style.background = 'linear-gradient(135deg, #f59e0b, #fbbf24)';
            } else {
                badge.style.background = 'linear-gradient(135deg, #3b82f6, #60a5fa)';
            }
        });
    });
</script>

</body>
</html>
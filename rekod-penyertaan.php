<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'rekod-penyertaan';
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekod Penyertaan | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Record Cards */
        .record-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        
        .record-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .program-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .record-meta {
            display: flex;
            gap: 24px;
            margin: 16px 0;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-present {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-absent {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .category-tag {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .feedback-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-value.total { color: var(--primary); }
        .stat-value.present { color: #10b981; }
        .stat-value.rate { color: #f59e0b; }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
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
        
        /* Filter Section */
        .filter-section {
            display: flex;
            gap: 12px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Feedback Button */
        .feedback-btn {
            padding: 8px 16px;
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .feedback-btn:hover {
            background: rgba(37, 99, 235, 0.1);
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
        <header class="topbar">
            <div class="search-box">
                <input type="text" placeholder="🔍 Cari rekod penyertaan..." />
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
            <!-- Header Section -->
            <div class="welcome-section">
                <h1 class="page-title">Rekod Penyertaan & Kehadiran</h1>
                <p class="page-subtitle">Lihat sejarah penyertaan dan status kehadiran anda</p>
            </div>

            <!-- Main Card -->
            <div class="card">
                <h2 class="card-title">Sejarah Penyertaan</h2>
                
                <!-- Filter Buttons -->
                <div class="filter-section">
                    <button class="filter-btn active" onclick="filterRecords('all')">Semua</button>
                    <button class="filter-btn" onclick="filterRecords('Hadir')">Hadir</button>
                    <button class="filter-btn" onclick="filterRecords('Tidak Hadir')">Tidak Hadir</button>
                    <button class="filter-btn" onclick="filterRecords('Akademik')">Akademik</button>
                    <button class="filter-btn" onclick="filterRecords('Komuniti')">Komuniti</button>
                    <button class="filter-btn" onclick="filterRecords('Kerjaya')">Kerjaya</button>
                </div>

                <!-- Records List -->
                <div id="recordsContainer">
                    <?php
                    $participationRecords = [
                        [
                            'id' => 1,
                            'program' => 'Workshop Pemikiran Kritikal',
                            'tarikh' => '15 Januari 2026',
                            'lokasi' => 'Bilik Seminar A',
                            'kategori' => 'Akademik',
                            'status' => 'Hadir',
                            'feedback' => true,
                        ],
                        [
                            'id' => 2,
                            'program' => 'Gotong-Royong Kampus',
                            'tarikh' => '18 Januari 2026',
                            'lokasi' => 'Kawasan Kolej',
                            'kategori' => 'Komuniti',
                            'status' => 'Hadir',
                            'feedback' => false,
                        ],
                        [
                            'id' => 3,
                            'program' => 'Seminar Kerjaya IT',
                            'tarikh' => '20 Januari 2026',
                            'lokasi' => 'Dewan FSKTM',
                            'kategori' => 'Kerjaya',
                            'status' => 'Hadir',
                            'feedback' => false,
                        ],
                        [
                            'id' => 4,
                            'program' => 'Bengkel Penulisan Ilmiah',
                            'tarikh' => '22 Januari 2026',
                            'lokasi' => 'Perpustakaan',
                            'kategori' => 'Akademik',
                            'status' => 'Tidak Hadir',
                            'feedback' => false,
                        ],
                        [
                            'id' => 5,
                            'program' => 'Forum Kepimpinan Belia',
                            'tarikh' => '25 Januari 2026',
                            'lokasi' => 'Dewan Tun Canselor',
                            'kategori' => 'Kepimpinan',
                            'status' => 'Hadir',
                            'feedback' => true,
                        ],
                        [
                            'id' => 6,
                            'program' => 'Program Khidmat Komuniti',
                            'tarikh' => '28 Januari 2026',
                            'lokasi' => 'Rumah Anak Yatim',
                            'kategori' => 'Komuniti',
                            'status' => 'Hadir',
                            'feedback' => false,
                        ],
                    ];
                    
                    if (count($participationRecords) > 0):
                        foreach ($participationRecords as $record):
                            $statusClass = $record['status'] === 'Hadir' ? 'status-present' : 'status-absent';
                    ?>
                        <div class="record-card" data-status="<?= $record['status'] ?>" data-category="<?= $record['kategori'] ?>">
                            <div class="record-header">
                                <div>
                                    <h3 class="program-title"><?= $record['program'] ?></h3>
                                    <span class="category-tag"><?= $record['kategori'] ?></span>
                                </div>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $record['status'] ?>
                                </span>
                            </div>
                            
                            <div class="record-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar" style="color: var(--primary);"></i>
                                    <span><?= $record['tarikh'] ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i>
                                    <span><?= $record['lokasi'] ?></span>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                                <?php if ($record['status'] === 'Hadir'): ?>
                                    <?php if ($record['feedback']): ?>
                                        <span class="feedback-badge">
                                            <i class="fas fa-check-circle"></i>
                                            Maklum Balas Diberikan
                                        </span>
                                    <?php else: ?>
                                        <button class="feedback-btn" onclick="openFeedback(<?= $record['id'] ?>)">
                                            <i class="fas fa-comment-alt"></i>
                                            Beri Maklum Balas
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 14px;">
                                        <i class="fas fa-info-circle"></i>
                                        Tidak berkenaan untuk beri maklum balas
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3>Tiada Rekod Penyertaan</h3>
                        <p>Anda belum menyertai sebarang program. Sertai program untuk melihat rekod penyertaan anda.</p>
                        <a href="dashboard.php" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 24px;">
                            <i class="fas fa-calendar-alt"></i> Cari Program
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value total">12</div>
                    <div class="stat-label">Jumlah Program</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value present">10</div>
                    <div class="stat-label">Kehadiran</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value rate">85%</div>
                    <div class="stat-label">Kadar Kehadiran</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #8b5cf6;">6</div>
                    <div class="stat-label">Maklum Balas Diberikan</div>
                </div>
            </div>

        </section>
    </main>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background: white; margin: 5% auto; padding: 30px; border-radius: var(--radius); max-width: 500px; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="font-size: 20px; font-weight: 600;">Beri Maklum Balas</h3>
            <button onclick="closeFeedback()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">
                &times;
            </button>
        </div>
        
        <div id="modalProgramInfo" style="margin-bottom: 24px; padding: 16px; background: var(--background); border-radius: var(--radius);">
            <!-- Program info will be inserted here -->
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">Penilaian</label>
            <div class="rating" style="display: flex; gap: 8px; margin-bottom: 16px;">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star rating-star" data-rating="<?= $i ?>" 
                       style="font-size: 24px; color: #ddd; cursor: pointer; transition: color 0.2s;"></i>
                <?php endfor; ?>
            </div>
            <input type="hidden" id="selectedRating" value="0">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">Ulasan</label>
            <textarea id="feedbackText" placeholder="Kongsikan pengalaman anda menyertai program ini..." 
                      style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; min-height: 120px; font-family: inherit;"></textarea>
        </div>
        
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="closeFeedback()" style="padding: 12px 24px; border: 1px solid var(--border); background: transparent; border-radius: 8px; cursor: pointer;">
                Batal
            </button>
            <button onclick="submitFeedback()" class="btn-primary" style="padding: 12px 24px;">
                Hantar Maklum Balas
            </button>
        </div>
    </div>
</div>

<script>
    let currentRecordId = null;
    let currentRating = 0;
    
    // Filter records
    function filterRecords(filter) {
        const records = document.querySelectorAll('.record-card');
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        // Update active filter button
        filterButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent === filter || (filter === 'all' && btn.textContent === 'Semua')) {
                btn.classList.add('active');
            }
        });
        
        // Filter records
        let visibleCount = 0;
        records.forEach(record => {
            const status = record.getAttribute('data-status');
            const category = record.getAttribute('data-category');
            
            if (filter === 'all' || 
                filter === status || 
                filter === category) {
                record.style.display = 'block';
                visibleCount++;
            } else {
                record.style.display = 'none';
            }
        });
        
        // Show empty state if no records
        const container = document.getElementById('recordsContainer');
        if (visibleCount === 0 && filter !== 'all') {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3>Tiada Rekod</h3>
                    <p>Tidak ada rekod penyertaan yang sesuai dengan filter "${filter}".</p>
                    <button onclick="filterRecords('all')" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 24px;">
                        <i class="fas fa-redo"></i> Tunjukkan Semua Rekod
                    </button>
                </div>
            `;
        }
    }
    
    // Open feedback modal
    function openFeedback(recordId) {
        currentRecordId = recordId;
        
        // Find record data
        const records = <?= json_encode($participationRecords) ?>;
        const record = records.find(r => r.id === recordId);
        
        if (record) {
            document.getElementById('modalProgramInfo').innerHTML = `
                <h4 style="font-weight: 600; margin-bottom: 8px;">${record.program}</h4>
                <div style="display: flex; gap: 16px; font-size: 14px; color: var(--text-secondary);">
                    <span><i class="fas fa-calendar"></i> ${record.tarikh}</span>
                    <span><i class="fas fa-map-marker-alt"></i> ${record.lokasi}</span>
                </div>
            `;
            
            // Reset rating
            currentRating = 0;
            document.getElementById('selectedRating').value = '0';
            document.querySelectorAll('.rating-star').forEach(star => {
                star.style.color = '#ddd';
            });
            
            // Reset textarea
            document.getElementById('feedbackText').value = '';
            
            // Show modal
            document.getElementById('feedbackModal').style.display = 'block';
        }
    }
    
    // Close feedback modal
    function closeFeedback() {
        document.getElementById('feedbackModal').style.display = 'none';
    }
    
    // Setup rating stars
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                currentRating = rating;
                document.getElementById('selectedRating').value = rating;
                
                // Update star colors
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#f59e0b';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
            
            // Add hover effect
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#fbbf24';
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                stars.forEach((s, index) => {
                    if (index >= currentRating) {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    });
    
    // Submit feedback
    function submitFeedback() {
        const rating = currentRating;
        const feedback = document.getElementById('feedbackText').value.trim();
        
        if (rating === 0) {
            alert('Sila berikan penilaian bintang');
            return;
        }
        
        if (feedback === '') {
            alert('Sila tulis ulasan anda');
            return;
        }
        
        // In real app, send to server via AJAX
        console.log('Submitting feedback:', {
            recordId: currentRecordId,
            rating: rating,
            feedback: feedback
        });
        
        // Show success message
        alert('Maklum balas berjaya dihantar!');
        
        // Close modal
        closeFeedback();
        
        // In real app, you would update the UI to show feedback given
        // For now, just refresh the page
        setTimeout(() => {
            location.reload();
        }, 500);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('feedbackModal');
        if (event.target === modal) {
            closeFeedback();
        }
    }
</script>

</body>
</html>
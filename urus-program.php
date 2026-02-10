<?php
session_start();
$_SESSION['role'] = 'penganjur';
$activePage = 'urus-program';

// Sample programs data
$programs = [
    [
        'id' => 1,
        'nama' => 'Workshop Kepimpinan Mahasiswa',
        'tarikh' => '25 Januari 2026',
        'masa' => '9:00 AM - 5:00 PM',
        'lokasi' => 'Dewan Tun Canselor',
        'kategori' => 'Kepimpinan',
        'peserta' => '45/100',
        'status' => 'Aktif',
        'warna' => '#f59e0b',
    ],
    [
        'id' => 2,
        'nama' => 'Seminar Inovasi Digital',
        'tarikh' => '28 Januari 2026',
        'masa' => '2:00 PM - 5:00 PM',
        'lokasi' => 'Auditorium FSKTM',
        'kategori' => 'Teknologi',
        'peserta' => '120/150',
        'status' => 'Aktif',
        'warna' => '#3b82f6',
    ],
    [
        'id' => 3,
        'nama' => 'Program Sukarelawan Komuniti',
        'tarikh' => '2 Februari 2026',
        'masa' => '8:00 AM - 12:00 PM',
        'lokasi' => 'Komuniti Bangi',
        'kategori' => 'Komuniti',
        'peserta' => '30/50',
        'status' => 'Aktif',
        'warna' => '#10b981',
    ],
    [
        'id' => 4,
        'nama' => 'Bengkel Penulisan Ilmiah',
        'tarikh' => '10 Januari 2026',
        'masa' => '2:00 PM - 5:00 PM',
        'lokasi' => 'Perpustakaan',
        'kategori' => 'Akademik',
        'peserta' => '25/30',
        'status' => 'Selesai',
        'warna' => '#8b5cf6',
    ],
    [
        'id' => 5,
        'nama' => 'Forum Kerjaya Graduan',
        'tarikh' => '15 Februari 2026',
        'masa' => '9:00 AM - 1:00 PM',
        'lokasi' => 'Dewan Kuliah Utama',
        'kategori' => 'Kerjaya',
        'peserta' => '0/200',
        'status' => 'Akan Datang',
        'warna' => '#ec4899',
    ],
    [
        'id' => 6,
        'nama' => 'Kem Jati Diri',
        'tarikh' => '5 Januari 2026',
        'masa' => '8:00 AM - 6:00 PM',
        'lokasi' => 'Kem Bina Semangat',
        'kategori' => 'Sukan',
        'peserta' => '40/40',
        'status' => 'Selesai',
        'warna' => '#f97316',
    ],
];

// Handle actions
if (isset($_POST['action'])) {
    $programId = $_POST['program_id'];
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        echo "<script>alert('Program ID $programId akan dibatalkan. (Simulasi)');</script>";
    } elseif ($action === 'edit') {
        echo "<script>window.location.href = 'edit-program.php?id=$programId';</script>";
    }
}

// Filter programs
$filter = $_GET['filter'] ?? 'all';
$filteredPrograms = array_filter($programs, function($program) use ($filter) {
    if ($filter === 'all') return true;
    if ($filter === 'active') return $program['status'] === 'Aktif';
    if ($filter === 'upcoming') return $program['status'] === 'Akan Datang';
    if ($filter === 'completed') return $program['status'] === 'Selesai';
    return true;
});
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urus Program | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Program Cards */
        .programs-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .program-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .program-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .program-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .program-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .program-category {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .program-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 16px 0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .info-icon {
            width: 20px;
            height: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .program-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        
        .program-actions {
            display: flex;
            gap: 12px;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-completed {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }
        
        .status-upcoming {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        /* Buttons */
        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
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
        
        .btn-create {
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
            text-decoration: none;
        }
        
        .btn-create:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Filter Buttons */
        .filter-section {
            display: flex;
            gap: 12px;
            margin: 24px 0;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 10px 20px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
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
        
        /* Participant Progress */
        .participant-progress {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .progress-bar {
            flex: 1;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-fill.high {
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        
        .progress-fill.medium {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }
        
        .progress-fill.low {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
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
                    <h1 class="page-title">Urus Program</h1>
                    <p class="page-subtitle">Kemaskini atau batalkan program yang telah diterbitkan</p>
                </div>
                <a href="hebahan-program.php" class="btn-create">
                    <i class="fas fa-plus"></i> Cipta Program Baharu
                </a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" 
                        onclick="window.location.href='?filter=all'">
                    Semua Program (<?= count($programs) ?>)
                </button>
                <button class="filter-btn <?= $filter === 'active' ? 'active' : '' ?>" 
                        onclick="window.location.href='?filter=active'">
                    <i class="fas fa-play-circle"></i> Aktif (<?= count(array_filter($programs, function($p) { return $p['status'] === 'Aktif'; })) ?>)
                </button>
                <button class="filter-btn <?= $filter === 'upcoming' ? 'active' : '' ?>" 
                        onclick="window.location.href='?filter=upcoming'">
                    <i class="fas fa-clock"></i> Akan Datang (<?= count(array_filter($programs, function($p) { return $p['status'] === 'Akan Datang'; })) ?>)
                </button>
                <button class="filter-btn <?= $filter === 'completed' ? 'active' : '' ?>" 
                        onclick="window.location.href='?filter=completed'">
                    <i class="fas fa-check-circle"></i> Selesai (<?= count(array_filter($programs, function($p) { return $p['status'] === 'Selesai'; })) ?>)
                </button>
            </div>

            <!-- Programs List -->
            <div class="programs-grid">
                <?php if (count($filteredPrograms) > 0): ?>
                    <?php foreach ($filteredPrograms as $program): 
                        // Calculate participant percentage
                        list($current, $total) = explode('/', $program['peserta']);
                        $percentage = ($current / $total) * 100;
                        $progressClass = $percentage >= 80 ? 'high' : ($percentage >= 50 ? 'medium' : 'low');
                        
                        // Status badge class
                        $statusClass = $program['status'] === 'Aktif' ? 'status-active' : 
                                      ($program['status'] === 'Selesai' ? 'status-completed' : 'status-upcoming');
                    ?>
                        <div class="program-card">
                            <div class="program-header">
                                <div>
                                    <h2 class="program-title"><?= htmlspecialchars($program['nama']) ?></h2>
                                    <span class="program-category" style="background: <?= $program['warna'] ?>">
                                        <i class="fas fa-tag"></i> <?= $program['kategori'] ?>
                                    </span>
                                </div>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $program['status'] ?>
                                </span>
                            </div>
                            
                            <div class="program-info">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <span><?= $program['tarikh'] ?> • <?= $program['masa'] ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <span><?= $program['lokasi'] ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="participant-progress">
                                        <span><?= $program['peserta'] ?> peserta</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill <?= $progressClass ?>" 
                                                 style="width: <?= $percentage ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="program-footer">
                                <div>
                                    <span style="font-size: 14px; color: var(--text-secondary);">
                                        <i class="fas fa-info-circle"></i>
                                        <?php
                                        if ($program['status'] === 'Aktif') {
                                            echo 'Pendaftaran masih dibuka';
                                        } elseif ($program['status'] === 'Selesai') {
                                            echo 'Program telah selesai';
                                        } else {
                                            echo 'Pendaftaran akan dibuka tidak lama lagi';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="program-actions">
                                    <button class="btn-edit" onclick="editProgram(<?= $program['id'] ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <?php if ($program['status'] === 'Aktif'): ?>
                                        <button class="btn-delete" onclick="cancelProgram(<?= $program['id'] ?>, '<?= htmlspecialchars($program['nama']) ?>')">
                                            <i class="fas fa-trash-alt"></i> Batal
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3>Tiada Program</h3>
                        <p>Tiada program yang sepadan dengan filter ini. Cuba pilih filter lain atau cipta program baharu.</p>
                        <a href="hebahan-program.php" class="btn-create">
                            <i class="fas fa-plus"></i> Cipta Program Pertama
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>
</div>

<!-- Cancel Program Modal -->
<div id="cancelModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background: white; margin: 10% auto; padding: 30px; border-radius: var(--radius); max-width: 500px; width: 90%;">
        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Batalkan Program</h3>
            <p id="modalProgramName" style="color: var(--text-secondary);"></p>
        </div>
        
        <div style="margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">Sebab Pembatalan</label>
            <textarea id="cancelReason" placeholder="Sila nyatakan sebab pembatalan program..." 
                      style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; min-height: 100px; font-family: inherit;"></textarea>
        </div>
        
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="closeCancelModal()" style="padding: 12px 24px; border: 1px solid var(--border); background: transparent; border-radius: 8px; cursor: pointer;">
                Batal
            </button>
            <button onclick="confirmCancel()" class="btn-delete" style="padding: 12px 24px; border: none;">
                <i class="fas fa-trash-alt"></i> Ya, Batalkan Program
            </button>
        </div>
    </div>
</div>

<script>
    let currentProgramId = null;
    let currentProgramName = '';
    
    // Edit program
    function editProgram(programId) {
        // In real app, redirect to edit page or open modal
        console.log('Editing program:', programId);
        
        // For now, redirect to edit page
        window.location.href = `edit-program.php?id=${programId}`;
    }
    
    // Cancel program modal
    function cancelProgram(programId, programName) {
        currentProgramId = programId;
        currentProgramName = programName;
        
        // Update modal content
        document.getElementById('modalProgramName').textContent = `Adakah anda pasti mahu membatalkan program "${programName}"?`;
        document.getElementById('cancelReason').value = '';
        
        // Show modal
        document.getElementById('cancelModal').style.display = 'block';
    }
    
    // Close cancel modal
    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
        currentProgramId = null;
        currentProgramName = '';
    }
    
    // Confirm cancellation
    function confirmCancel() {
        const reason = document.getElementById('cancelReason').value.trim();
        
        if (!reason) {
            alert('Sila nyatakan sebab pembatalan program.');
            return;
        }
        
        // In real app, send AJAX request to cancel program
        console.log('Cancelling program:', currentProgramId, 'Reason:', reason);
        
        // Simulate server request
        setTimeout(() => {
            // Show success message
            showNotification(`Program "${currentProgramName}" telah dibatalkan.`);
            
            // Close modal
            closeCancelModal();
            
            // Refresh page to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }, 500);
    }
    
    // Show notification
    function showNotification(message) {
        // Create notification element
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
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('cancelModal');
        if (event.target === modal) {
            closeCancelModal();
        }
    }
</script>

</body>
</html>
<?php
session_start();
$_SESSION['role'] = 'penganjur';
$activePage = 'dashboard';
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penganjur | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-text h4 {
            font-size: 14px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-text h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-green { background: linear-gradient(135deg, #10b981, #34d399); }
        .bg-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .bg-purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        
        /* Program List */
        .program-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .program-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .program-item:hover {
            box-shadow: var(--shadow);
            transform: translateX(4px);
        }
        
        .program-info {
            flex: 1;
        }
        
        .program-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .program-date {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .program-meta {
            text-align: right;
        }
        
        .participant-count {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 4px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Chart Container */
        .chart-container {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 32px;
            height: 300px;
            position: relative;
        }
        
        .chart-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
        }
        
        /* Card Styling */
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
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .card-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Chart Styling */
        .chart-bar {
            display: inline-block;
            width: 40px;
            background: var(--primary);
            border-radius: 4px 4px 0 0;
            margin: 0 8px;
            position: relative;
            transition: height 0.3s ease;
        }
        
        .chart-bar:hover {
            opacity: 0.8;
        }
        
        .chart-bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .chart-month {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 8px;
        }
        
        .chart-wrapper {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            height: 200px;
            margin-top: 40px;
            gap: 20px;
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
                <input type="text" placeholder="🔍 Cari program atau peserta..." />
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
                <h1 class="page-title">Dashboard Penganjur</h1>
                <p class="page-subtitle">Ringkasan program dan statistik pengurusan anda</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-text">
                            <h4>Jumlah Program</h4>
                            <h2>8</h2>
                        </div>
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-text">
                            <h4>Jumlah Peserta</h4>
                            <h2>345</h2>
                        </div>
                        <div class="stat-icon bg-green">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-text">
                            <h4>Program Aktif</h4>
                            <h2>3</h2>
                        </div>
                        <div class="stat-icon bg-orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-text">
                            <h4>Penilaian Purata</h4>
                            <h2>4.5<span style="font-size: 16px; color: var(--text-secondary);">/5</span></h2>
                        </div>
                        <div class="stat-icon bg-purple">
                            <i class="fas fa-award"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Trend Penyertaan Pelajar</h2>
                    <p class="card-subtitle">Jumlah peserta mengikut bulan</p>
                </div>
                
                <div class="chart-wrapper">
                    <?php
                    $chartData = [
                        ['bulan' => 'Sep', 'peserta' => 45],
                        ['bulan' => 'Okt', 'peserta' => 52],
                        ['bulan' => 'Nov', 'peserta' => 48],
                        ['bulan' => 'Dis', 'peserta' => 65],
                        ['bulan' => 'Jan', 'peserta' => 75],
                        ['bulan' => 'Feb', 'peserta' => 60],
                    ];
                    
                    $maxValue = max(array_column($chartData, 'peserta'));
                    
                    foreach ($chartData as $data):
                        $height = ($data['peserta'] / $maxValue) * 160; // Scale to max 160px
                    ?>
                    <div class="chart-bar-container">
                        <div class="chart-bar" style="height: <?= $height ?>px;">
                            <span class="chart-bar-value"><?= $data['peserta'] ?></span>
                        </div>
                        <div class="chart-month"><?= $data['bulan'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 40px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Data berdasarkan program yang telah dijalankan
                </div>
            </div>

            <!-- Upcoming Programs -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Program Akan Datang</h2>
                    <p class="card-subtitle">Program yang sedang aktif dan akan datang</p>
                </div>
                
                <div class="program-list">
                    <?php
                    $upcomingPrograms = [
                        [
                            'id' => 1,
                            'nama' => 'Workshop Kepimpinan Mahasiswa',
                            'tarikh' => '25 Januari 2026',
                            'peserta' => '45/100',
                            'status' => 'Aktif',
                        ],
                        [
                            'id' => 2,
                            'nama' => 'Seminar Inovasi Digital',
                            'tarikh' => '28 Januari 2026',
                            'peserta' => '120/150',
                            'status' => 'Aktif',
                        ],
                        [
                            'id' => 3,
                            'nama' => 'Program Sukarelawan Komuniti',
                            'tarikh' => '2 Februari 2026',
                            'peserta' => '30/50',
                            'status' => 'Aktif',
                        ],
                        [
                            'id' => 4,
                            'nama' => 'Forum Kerjaya Graduan',
                            'tarikh' => '15 Februari 2026',
                            'peserta' => '0/200',
                            'status' => 'Akan Datang',
                        ],
                    ];
                    
                    foreach ($upcomingPrograms as $program):
                        $statusColor = $program['status'] === 'Aktif' ? 'status-badge' : 'status-badge';
                        $statusBgColor = $program['status'] === 'Aktif' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(59, 130, 246, 0.1)';
                        $statusTextColor = $program['status'] === 'Aktif' ? '#10b981' : '#3b82f6';
                    ?>
                    <div class="program-item">
                        <div class="program-info">
                            <h3 class="program-name"><?= $program['nama'] ?></h3>
                            <p class="program-date">
                                <i class="fas fa-calendar"></i> <?= $program['tarikh'] ?>
                            </p>
                        </div>
                        <div class="program-meta">
                            <div class="participant-count"><?= $program['peserta'] ?></div>
                            <span class="status-badge" style="background: <?= $statusBgColor ?>; color: <?= $statusTextColor ?>;">
                                <?= $program['status'] ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 24px;">
                    <a href="urus-program.php" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 32px;">
                        <i class="fas fa-cog"></i> Urus Semua Program
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Tindakan Pantas</h2>
                    <p class="card-subtitle">Kemudahan akses cepat untuk penganjur</p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <a href="hebahan-program.php" class="quick-action-btn">
                        <div class="action-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--primary);">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <span>Hebah Program Baru</span>
                    </a>
                    
                    <a href="urus-program.php" class="quick-action-btn">
                        <div class="action-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span>Urus Program</span>
                    </a>
                    
                    <a href="peserta-kehadiran.php" class="quick-action-btn">
                        <div class="action-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <span>Kehadiran Peserta</span>
                    </a>
                    
                    <a href="laporan-statistik.php" class="quick-action-btn">
                        <div class="action-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span>Laporan Statistik</span>
                    </a>
                </div>
            </div>

        </section>
    </main>
</div>

<style>
    /* Quick Actions */
    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px;
        background: var(--surface);
        border: 2px solid var(--border);
        border-radius: var(--radius);
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .quick-action-btn:hover {
        border-color: var(--primary);
        transform: translateY(-4px);
        box-shadow: var(--shadow);
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
    }
    
    .quick-action-btn span {
        font-weight: 600;
        font-size: 15px;
    }
    
    /* Chart bar hover effect */
    .chart-bar-container {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .chart-bar-container:hover .chart-bar {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
</style>

<script>
    // Add hover effects to chart bars
    document.addEventListener('DOMContentLoaded', function() {
        const bars = document.querySelectorAll('.chart-bar');
        bars.forEach(bar => {
            bar.addEventListener('mouseenter', function() {
                this.style.transform = 'scaleY(1.05)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            bar.addEventListener('mouseleave', function() {
                this.style.transform = 'scaleY(1)';
            });
        });
        
        // Add progress animation to stats
        const statValues = document.querySelectorAll('.stat-text h2');
        statValues.forEach(stat => {
            const originalText = stat.textContent;
            const finalValue = parseFloat(originalText.replace(/[^0-9.]/g, ''));
            
            // Animate counting up (simplified)
            let current = 0;
            const increment = finalValue / 20;
            const timer = setInterval(() => {
                current += increment;
                if (current >= finalValue) {
                    current = finalValue;
                    clearInterval(timer);
                }
                
                if (originalText.includes('/')) {
                    stat.textContent = current.toFixed(originalText.includes('.') ? 1 : 0) + '/5';
                } else {
                    stat.textContent = Math.round(current);
                }
            }, 50);
        });
    });
</script>

</body>
</html>
<?php
session_start();
$_SESSION['role'] = 'penganjur';
$activePage = 'laporan-statistik';

// Sample data for demonstration
$programs = [
    ['id' => 1, 'name' => 'Workshop Kepimpinan Mahasiswa', 'date' => '2026-01-25', 'participants' => 45, 'attendance' => 42, 'rating' => 4.8],
    ['id' => 2, 'name' => 'Seminar Inovasi Digital', 'date' => '2026-01-28', 'participants' => 120, 'attendance' => 115, 'rating' => 4.7],
    ['id' => 3, 'name' => 'Program Sukarelawan Komuniti', 'date' => '2026-02-02', 'participants' => 30, 'attendance' => 28, 'rating' => 4.9],
    ['id' => 4, 'name' => 'Forum Kerjaya Graduan', 'date' => '2026-02-15', 'participants' => 85, 'attendance' => 82, 'rating' => 4.6],
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? 'summary';
    $programId = $_POST['program_id'] ?? 'all';
    $dateRange = $_POST['date_range'] ?? 'month';
    $format = $_POST['format'] ?? 'pdf';
    
    // Generate report based on parameters
    $reportData = generateReport($reportType, $programId, $dateRange);
    
    // Simulate download
    echo "<script>
        alert('Laporan $reportType sedang dijana dalam format $format...\\n\\nProgram: " . ($programId === 'all' ? 'Semua' : 'Program ID ' . $programId) . "\\nTempoh: $dateRange\\n\\nLaporan berjaya dijana!');
        showNotification('Laporan berjaya dijana dan sedia dimuat turun');
    </script>";
}

function generateReport($type, $programId, $dateRange) {
    // In real app, fetch data from database based on parameters
    return [
        'type' => $type,
        'program_id' => $programId,
        'date_range' => $dateRange,
        'generated_at' => date('Y-m-d H:i:s'),
        'data' => [] // Would contain actual report data
    ];
}

// Calculate statistics
$totalParticipants = array_sum(array_column($programs, 'participants'));
$totalAttendance = array_sum(array_column($programs, 'attendance'));
$attendanceRate = round(($totalAttendance / $totalParticipants) * 100, 1);
$averageRating = round(array_sum(array_column($programs, 'rating')) / count($programs), 1);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menjana Laporan Statistik | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Report Generator Styling */
        .report-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* Report Configuration */
        .config-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 32px;
        }
        
        .config-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .config-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .config-label {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .config-label i {
            color: var(--primary);
        }
        
        .config-select, .config-input {
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            background: var(--surface);
            transition: all 0.2s ease;
        }
        
        .config-select:focus, .config-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Report Types Grid */
        .report-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .report-type-card {
            padding: 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        
        .report-type-card:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
            transform: translateY(-2px);
        }
        
        .report-type-card.selected {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        
        .report-type-icon {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 12px;
        }
        
        .report-type-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .report-type-desc {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        /* Format Options */
        .format-options {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }
        
        .format-option {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .format-option:hover {
            border-color: var(--primary);
        }
        
        .format-option.selected {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
        }
        
        .format-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .format-pdf .format-icon { color: #ef4444; }
        .format-excel .format-icon { color: #10b981; }
        .format-csv .format-icon { color: #3b82f6; }
        
        /* Preview Section */
        .preview-section {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 32px;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .preview-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* Preview Content */
        .preview-content {
            background: var(--background);
            border-radius: 8px;
            padding: 24px;
            border: 1px solid var(--border);
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .preview-table th {
            background: var(--surface);
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border);
        }
        
        .preview-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .preview-table tr:last-child td {
            border-bottom: none;
        }
        
        .rating-stars {
            color: #f59e0b;
            font-size: 14px;
        }
        
        .attendance-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .attendance-high { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .attendance-medium { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        
        /* Statistics Summary */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        
        .stat-item {
            background: var(--surface);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            border: 1px solid var(--border);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }
        
        .btn-generate {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-generate:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-preview {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-preview:hover {
            background: rgba(37, 99, 235, 0.1);
        }
        
        /* Date Range Picker */
        .date-range-picker {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .date-input {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }
        
        /* Custom Date Range */
        .custom-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 12px;
        }
        
        /* Loading Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        /* Report Tips */
        .report-tips {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(139, 92, 246, 0.05));
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 24px;
            border-left: 4px solid var(--primary);
        }
        
        .tips-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tips-list {
            padding-left: 20px;
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .tips-list li {
            margin-bottom: 8px;
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
                <h1 class="page-title">Menjana Laporan Statistik</h1>
                <p class="page-subtitle">Hasilkan laporan statistik untuk program dan analisis prestasi</p>
            </div>

            <!-- Report Configuration -->
            <form method="POST" class="config-card">
                <h2 class="config-title">
                    <i class="fas fa-cog"></i>
                    Konfigurasi Laporan
                </h2>
                
                <div class="config-grid">
                    <!-- Report Type -->
                    <div class="config-group">
                        <label class="config-label">
                            <i class="fas fa-chart-bar"></i>
                            Jenis Laporan
                        </label>
                        
                        <div class="report-types">
                            <div class="report-type-card selected" onclick="selectReportType('summary')">
                                <div class="report-type-icon">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div class="report-type-title">Ringkasan</div>
                                <div class="report-type-desc">Ringkasan statistik keseluruhan program</div>
                            </div>
                            
                            <div class="report-type-card" onclick="selectReportType('attendance')">
                                <div class="report-type-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="report-type-title">Kehadiran</div>
                                <div class="report-type-desc">Analisis kehadiran peserta</div>
                            </div>
                            
                            <div class="report-type-card" onclick="selectReportType('feedback')">
                                <div class="report-type-icon">
                                    <i class="fas fa-comment-alt"></i>
                                </div>
                                <div class="report-type-title">Maklum Balas</div>
                                <div class="report-type-desc">Analisis maklum balas peserta</div>
                            </div>
                            
                            <div class="report-type-card" onclick="selectReportType('detailed')">
                                <div class="report-type-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="report-type-title">Terperinci</div>
                                <div class="report-type-desc">Laporan lengkap dengan semua data</div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="report_type" id="reportType" value="summary">
                    </div>

                    <!-- Program Selection -->
                    <div class="config-group">
                        <label class="config-label">
                            <i class="fas fa-calendar-alt"></i>
                            Pilih Program
                        </label>
                        <select name="program_id" class="config-select" id="programSelect">
                            <option value="all">Semua Program</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?= $program['id'] ?>"><?= htmlspecialchars($program['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="config-group">
                        <label class="config-label">
                            <i class="fas fa-calendar"></i>
                            Tempoh Masa
                        </label>
                        <select name="date_range" class="config-select" id="dateRange" onchange="toggleCustomDateRange()">
                            <option value="week">Minggu Ini</option>
                            <option value="month">Bulan Ini</option>
                            <option value="quarter">Suku Tahun Ini</option>
                            <option value="year">Tahun Ini</option>
                            <option value="custom">Julat Tersuai</option>
                            <option value="all">Semua Masa</option>
                        </select>
                        
                        <!-- Custom Date Range -->
                        <div id="customDateRange" style="display: none;" class="custom-range">
                            <input type="date" name="start_date" class="date-input" placeholder="Tarikh Mula">
                            <input type="date" name="end_date" class="date-input" placeholder="Tarikh Akhir">
                        </div>
                    </div>

                    <!-- Output Format -->
                    <div class="config-group">
                        <label class="config-label">
                            <i class="fas fa-download"></i>
                            Format Output
                        </label>
                        
                        <div class="format-options">
                            <div class="format-option format-pdf selected" onclick="selectFormat('pdf')">
                                <div class="format-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div>PDF</div>
                            </div>
                            
                            <div class="format-option format-excel" onclick="selectFormat('excel')">
                                <div class="format-icon">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <div>Excel</div>
                            </div>
                            
                            <div class="format-option format-csv" onclick="selectFormat('csv')">
                                <div class="format-icon">
                                    <i class="fas fa-file-csv"></i>
                                </div>
                                <div>CSV</div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="format" id="reportFormat" value="pdf">
                    </div>
                </div>
            </form>

            <!-- Preview Section -->
            <div class="preview-section">
                <div class="preview-header">
                    <h3 class="preview-title">Pratonton Laporan</h3>
                    <span style="color: var(--text-secondary); font-size: 14px;">
                        <i class="fas fa-eye"></i> Data contoh berdasarkan pilihan
                    </span>
                </div>
                
                <div class="preview-content">
                    <!-- Report Preview -->
                    <div id="reportPreview">
                        <?php if (isset($_POST['report_type'])): ?>
                            <!-- Generated Report Preview -->
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-file-alt" style="font-size: 48px; color: var(--primary); margin-bottom: 16px;"></i>
                                <h3 style="color: var(--text-primary); margin-bottom: 8px;">Laporan Dijana</h3>
                                <p style="color: var(--text-secondary);">Laporan <?= htmlspecialchars($_POST['report_type']) ?> dalam format <?= htmlspecialchars($_POST['format']) ?></p>
                            </div>
                        <?php else: ?>
                            <!-- Default Preview -->
                            <table class="preview-table">
                                <thead>
                                    <tr>
                                        <th>Program</th>
                                        <th>Tarikh</th>
                                        <th>Peserta</th>
                                        <th>Kehadiran</th>
                                        <th>Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programs as $program): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($program['name']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($program['date'])) ?></td>
                                        <td><?= $program['participants'] ?></td>
                                        <td>
                                            <span class="attendance-badge <?= $program['attendance']/$program['participants'] > 0.9 ? 'attendance-high' : 'attendance-medium' ?>">
                                                <?= $program['attendance'] ?> (<?= round(($program['attendance']/$program['participants'])*100) ?>%)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="rating-stars">
                                                <?= str_repeat('★', floor($program['rating'])) ?><?= str_repeat('☆', 5 - floor($program['rating'])) ?>
                                                <?= $program['rating'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <!-- Statistics Summary -->
                            <div class="stats-summary">
                                <div class="stat-item">
                                    <div class="stat-value"><?= count($programs) ?></div>
                                    <div class="stat-label">Jumlah Program</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $totalParticipants ?></div>
                                    <div class="stat-label">Jumlah Peserta</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $attendanceRate ?>%</div>
                                    <div class="stat-label">Kadar Kehadiran</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $averageRating ?>/5</div>
                                    <div class="stat-label">Rating Purata</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" class="btn-preview" onclick="generatePreview()">
                        <i class="fas fa-eye"></i> Pratinjau Laporan
                    </button>
                    <button type="submit" name="generate_report" class="btn-generate" onclick="generateReport()">
                        <i class="fas fa-download"></i> Jana & Muat Turun
                    </button>
                </div>
            </div>

            <!-- Report Tips -->
            <div class="report-tips">
                <div class="tips-title">
                    <i class="fas fa-lightbulb"></i>
                    Tips untuk Laporan yang Berkesan
                </div>
                <ul class="tips-list">
                    <li>Pilih jenis laporan yang sesuai dengan keperluan analisis anda</li>
                    <li>Gunakan laporan ringkasan untuk gambaran keseluruhan prestasi program</li>
                    <li>Laporan kehadiran membantu analisis kadar penyertaan peserta</li>
                    <li>Format PDF sesuai untuk perkongsian, Excel untuk analisis lanjut</li>
                    <li>Jana laporan selepas program selesai untuk data yang lengkap</li>
                </ul>
            </div>

        </section>
    </main>
</div>

<script>
    // Initialize default selections
    document.addEventListener('DOMContentLoaded', function() {
        // Set today's date for date inputs
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="start_date"]').value = today;
        document.querySelector('input[name="end_date"]').value = today;
    });
    
    // Select report type
    function selectReportType(type) {
        document.getElementById('reportType').value = type;
        
        // Update UI
        document.querySelectorAll('.report-type-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Update preview
        generatePreview();
    }
    
    // Select format
    function selectFormat(format) {
        document.getElementById('reportFormat').value = format;
        
        // Update UI
        document.querySelectorAll('.format-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
    }
    
    // Toggle custom date range
    function toggleCustomDateRange() {
        const dateRange = document.getElementById('dateRange').value;
        const customRange = document.getElementById('customDateRange');
        
        if (dateRange === 'custom') {
            customRange.style.display = 'grid';
        } else {
            customRange.style.display = 'none';
        }
    }
    
    // Generate report preview
    function generatePreview() {
        const reportType = document.getElementById('reportType').value;
        const programId = document.getElementById('programSelect').value;
        const dateRange = document.getElementById('dateRange').value;
        
        // Show loading state
        const previewBtn = document.querySelector('.btn-preview');
        const originalContent = previewBtn.innerHTML;
        previewBtn.innerHTML = '<div class="loading"></div> Memuatkan...';
        previewBtn.disabled = true;
        
        // In real app, fetch preview data via AJAX
        // For now, simulate with timeout
        setTimeout(() => {
            const previewContent = document.getElementById('reportPreview');
            
            let previewHTML = '';
            if (programId === 'all') {
                previewHTML = `
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Tarikh</th>
                                <th>Peserta</th>
                                <th>Kehadiran</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program): ?>
                            <tr>
                                <td><?= htmlspecialchars($program['name']) ?></td>
                                <td><?= date('d/m/Y', strtotime($program['date'])) ?></td>
                                <td><?= $program['participants'] ?></td>
                                <td>
                                    <span class="attendance-badge ${<?= $program['attendance']/$program['participants'] > 0.9 ?> ? 'attendance-high' : 'attendance-medium'}">
                                        <?= $program['attendance'] ?> (<?= round(($program['attendance']/$program['participants'])*100) ?>%)
                                    </span>
                                </td>
                                <td>
                                    <span class="rating-stars">
                                        ${'★'.repeat(Math.floor(<?= $program['rating'] ?>))}${'☆'.repeat(5 - Math.floor(<?= $program['rating'] ?>))}
                                        <?= $program['rating'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="stats-summary">
                        <div class="stat-item">
                            <div class="stat-value"><?= count($programs) ?></div>
                            <div class="stat-label">Jumlah Program</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $totalParticipants ?></div>
                            <div class="stat-label">Jumlah Peserta</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $attendanceRate ?>%</div>
                            <div class="stat-label">Kadar Kehadiran</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $averageRating ?>/5</div>
                            <div class="stat-label">Rating Purata</div>
                        </div>
                    </div>
                `;
            } else {
                const program = <?= json_encode($programs[0]) ?>;
                previewHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: var(--text-primary); margin-bottom: 16px;">${program.name}</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 24px;">Laporan ${reportType} untuk program ini</p>
                        
                        <div class="stats-summary" style="max-width: 600px; margin: 0 auto;">
                            <div class="stat-item">
                                <div class="stat-value">${program.participants}</div>
                                <div class="stat-label">Jumlah Peserta</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${program.attendance}</div>
                                <div class="stat-label">Kehadiran</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${Math.round((program.attendance/program.participants)*100)}%</div>
                                <div class="stat-label">Kadar Kehadiran</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${program.rating}/5</div>
                                <div class="stat-label">Rating</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            previewContent.innerHTML = previewHTML;
            
            // Restore button
            previewBtn.innerHTML = originalContent;
            previewBtn.disabled = false;
            
            // Show notification
            showNotification('Pratonton laporan dikemas kini');
        }, 1000);
    }
    
    // Generate and download report
    function generateReport() {
        const reportType = document.getElementById('reportType').value;
        const programId = document.getElementById('programSelect').value;
        const dateRange = document.getElementById('dateRange').value;
        const format = document.getElementById('reportFormat').value;
        
        // Show loading state
        const generateBtn = document.querySelector('.btn-generate');
        const originalContent = generateBtn.innerHTML;
        generateBtn.innerHTML = '<div class="loading"></div> Menjana...';
        generateBtn.disabled = true;
        
        // Simulate report generation
        setTimeout(() => {
            // In real app, this would make an AJAX request to generate the report
            // For now, simulate download
            const programName = programId === 'all' ? 'semua-program' : 'program-' + programId;
            const filename = `laporan-${reportType}-${programName}-${new Date().toISOString().split('T')[0]}.${format}`;
            
            // Create download link
            const content = `Laporan ${reportType} untuk ${programId === 'all' ? 'Semua Program' : 'Program ID ' + programId}
Tempoh: ${dateRange}
Dijana pada: ${new Date().toLocaleString()}

---
CONTOH DATA LAPORAN
---`;
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            // Restore button
            generateBtn.innerHTML = originalContent;
            generateBtn.disabled = false;
            
            // Show notification
            showNotification('Laporan berjaya dijana dan dimuat turun');
            
            // In real app, submit the form
            // document.querySelector('form').submit();
        }, 2000);
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
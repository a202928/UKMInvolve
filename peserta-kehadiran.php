<?php
session_start();
$_SESSION['role'] = 'penganjur';
$activePage = 'peserta-kehadiran';

// Sample data
$programs = [
    ['id' => '1', 'name' => 'Workshop Kepimpinan Mahasiswa'],
    ['id' => '2', 'name' => 'Seminar Inovasi Digital'],
    ['id' => '3', 'name' => 'Program Sukarelawan Komuniti'],
    ['id' => '4', 'name' => 'Forum Kerjaya Graduan'],
];

$participants = [
    [
        'id' => 1,
        'nama' => 'Ahmad Faiz bin Abdullah',
        'matrik' => 'A123456',
        'fakulti' => 'FSKTM',
        'emel' => 'faiz@ukm.edu.my',
        'kehadiran' => true,
    ],
    [
        'id' => 2,
        'nama' => 'Nur Aisyah binti Hassan',
        'matrik' => 'A123457',
        'fakulti' => 'FEP',
        'emel' => 'aisyah@ukm.edu.my',
        'kehadiran' => true,
    ],
    [
        'id' => 3,
        'nama' => 'Muhammad Ali bin Omar',
        'matrik' => 'A123458',
        'fakulti' => 'FST',
        'emel' => 'ali@ukm.edu.my',
        'kehadiran' => false,
    ],
    [
        'id' => 4,
        'nama' => 'Siti Nurhaliza binti Razak',
        'matrik' => 'A123459',
        'fakulti' => 'FKAB',
        'emel' => 'siti@ukm.edu.my',
        'kehadiran' => true,
    ],
    [
        'id' => 5,
        'nama' => 'Lim Wei Chen',
        'matrik' => 'A123460',
        'fakulti' => 'FST',
        'emel' => 'weichen@ukm.edu.my',
        'kehadiran' => true,
    ],
    [
        'id' => 6,
        'nama' => 'Nurul Syafiqah binti Ahmad',
        'matrik' => 'A123461',
        'fakulti' => 'FPI',
        'emel' => 'syafiqah@ukm.edu.my',
        'kehadiran' => false,
    ],
];

// Handle attendance toggle
if (isset($_POST['toggle_attendance'])) {
    $participantId = $_POST['participant_id'];
    // In real app, update database here
    echo "<script>alert('Kehadiran peserta ID $participantId telah dikemaskini');</script>";
}

// Calculate statistics
$attendanceCount = count(array_filter($participants, function($p) { return $p['kehadiran']; }));
$attendanceRate = round(($attendanceCount / count($participants)) * 100);

// Handle search
$searchQuery = $_GET['search'] ?? '';
$selectedProgram = $_GET['program'] ?? '1';

if ($searchQuery) {
    $filteredParticipants = array_filter($participants, function($p) use ($searchQuery) {
        return stripos($p['nama'], $searchQuery) !== false || 
               stripos($p['matrik'], $searchQuery) !== false;
    });
} else {
    $filteredParticipants = $participants;
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peserta & Kehadiran | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        /* Program Selector */
        .program-selector {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        
        /* Table Styling */
        .table-container {
            background: var(--surface);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .table-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
        }
        
        .table-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
        }
        
        .search-box-container {
            position: relative;
            max-width: 400px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            background: var(--surface);
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        /* Table */
        .participants-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .participants-table th {
            background: var(--background);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border);
        }
        
        .participants-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
        }
        
        .participants-table tr:hover td {
            background: rgba(37, 99, 235, 0.03);
        }
        
        .participants-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Switch Toggle */
        .switch-container {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #10b981;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #10b981;
        }
        
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        
        .status-text {
            font-size: 14px;
            font-weight: 500;
            min-width: 80px;
        }
        
        .status-present {
            color: #10b981;
        }
        
        .status-absent {
            color: #ef4444;
        }
        
        /* Export Button */
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .export-btn:hover {
            background: rgba(37, 99, 235, 0.1);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
            margin: 0 auto;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }
        
        .action-btn.primary {
            background: var(--primary);
            color: white;
        }
        
        .action-btn.primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .action-btn.secondary {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text-primary);
        }
        
        .action-btn.secondary:hover {
            border-color: var(--primary);
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
                <h1 class="page-title">Peserta & Kehadiran</h1>
                <p class="page-subtitle">Urus senarai peserta dan rekod kehadiran program</p>
            </div>

            <!-- Program Selection -->
            <div class="program-selector">
                <form method="GET" class="flex items-center gap-4">
                    <label class="font-medium whitespace-nowrap" style="color: var(--text-primary);">
                        Pilih Program:
                    </label>
                    <select name="program" class="form-input" style="max-width: 400px; flex: 1;" 
                            onchange="this.form.submit()">
                        <?php foreach ($programs as $program): ?>
                            <option value="<?= $program['id'] ?>" <?= $selectedProgram == $program['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($program['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value total"><?= count($participants) ?></div>
                    <div class="stat-label">Jumlah Peserta</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value present"><?= $attendanceCount ?></div>
                    <div class="stat-label">Hadir</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value rate"><?= $attendanceRate ?>%</div>
                    <div class="stat-label">Kadar Kehadiran</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #8b5cf6;">
                        <?= count($participants) - $attendanceCount ?>
                    </div>
                    <div class="stat-label">Tidak Hadir</div>
                </div>
            </div>

            <!-- Participants Table -->
            <div class="table-container">
                <div class="table-header">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <h2 class="table-title">Senarai Peserta</h2>
                        
                        <!-- Search and Export -->
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <form method="GET" class="search-box-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="hidden" name="program" value="<?= $selectedProgram ?>">
                                <input type="text" 
                                       name="search" 
                                       class="search-input" 
                                       placeholder="Cari nama atau nombor matrik..."
                                       value="<?= htmlspecialchars($searchQuery) ?>">
                            </form>
                            <button class="export-btn" onclick="exportAttendance()">
                                <i class="fas fa-download"></i> Eksport
                            </button>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="action-btn primary" onclick="markAllPresent()">
                            <i class="fas fa-check-circle"></i> Tanda Semua Hadir
                        </button>
                        <button class="action-btn secondary" onclick="resetAttendance()">
                            <i class="fas fa-redo"></i> Reset Kehadiran
                        </button>
                        <button class="action-btn secondary" onclick="sendReminders()">
                            <i class="fas fa-envelope"></i> Hantar Peringatan
                        </button>
                    </div>
                </div>
                
                <!-- Table Content -->
                <?php if (count($filteredParticipants) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="participants-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>No. Matrik</th>
                                <th>Fakulti</th>
                                <th>Emel</th>
                                <th style="text-align: center;">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredParticipants as $participant): ?>
                            <tr>
                                <td><?= htmlspecialchars($participant['nama']) ?></td>
                                <td style="color: var(--text-secondary);"><?= $participant['matrik'] ?></td>
                                <td style="color: var(--text-secondary);"><?= $participant['fakulti'] ?></td>
                                <td style="color: var(--text-secondary);"><?= $participant['emel'] ?></td>
                                <td style="text-align: center;">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" 
                                                   <?= $participant['kehadiran'] ? 'checked' : '' ?>
                                                   onchange="toggleAttendance(<?= $participant['id'] ?>, this)">
                                            <span class="slider"></span>
                                        </label>
                                        <span class="status-text <?= $participant['kehadiran'] ? 'status-present' : 'status-absent' ?>">
                                            <?= $participant['kehadiran'] ? 'Hadir' : 'Tidak Hadir' ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-users-slash"></i>
                    </div>
                    <h3>Tiada Peserta Dijumpai</h3>
                    <p>Tidak ada peserta yang sepadan dengan carian anda. Cuba carian lain.</p>
                    <button onclick="window.location.href='?program=<?= $selectedProgram ?>'" 
                            class="action-btn primary" style="margin-top: 16px;">
                        <i class="fas fa-redo"></i> Reset Carian
                    </button>
                </div>
                <?php endif; ?>
            </div>

        </section>
    </main>
</div>

<script>
    // Toggle attendance
    function toggleAttendance(participantId, checkbox) {
        const isPresent = checkbox.checked;
        const statusText = checkbox.parentElement.nextElementSibling;
        
        // Update status text
        statusText.textContent = isPresent ? 'Hadir' : 'Tidak Hadir';
        statusText.className = `status-text ${isPresent ? 'status-present' : 'status-absent'}`;
        
        // In real app, send AJAX request to update database
        console.log(`Updating participant ${participantId} attendance to: ${isPresent ? 'Present' : 'Absent'}`);
        
        // Show notification
        showNotification(`Kehadiran peserta telah dikemaskini ke "${isPresent ? 'Hadir' : 'Tidak Hadir'}"`);
        
        // Update statistics
        updateStatistics();
    }
    
    // Mark all as present
    function markAllPresent() {
        if (confirm('Adakah anda pasti mahu menandakan semua peserta sebagai hadir?')) {
            document.querySelectorAll('.switch input[type="checkbox"]').forEach(checkbox => {
                if (!checkbox.checked) {
                    checkbox.checked = true;
                    const statusText = checkbox.parentElement.nextElementSibling;
                    statusText.textContent = 'Hadir';
                    statusText.className = 'status-text status-present';
                }
            });
            
            showNotification('Semua peserta telah ditandakan sebagai hadir');
            updateStatistics();
        }
    }
    
    // Reset attendance
    function resetAttendance() {
        if (confirm('Adakah anda pasti mahu menetapkan semula semua kehadiran?')) {
            document.querySelectorAll('.switch input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
                const statusText = checkbox.parentElement.nextElementSibling;
                statusText.textContent = 'Tidak Hadir';
                statusText.className = 'status-text status-absent';
            });
            
            showNotification('Semua kehadiran telah ditetapkan semula');
            updateStatistics();
        }
    }
    
    // Send reminders
    function sendReminders() {
        const absentParticipants = Array.from(document.querySelectorAll('.switch input[type="checkbox"]:not(:checked)'))
            .map(checkbox => {
                const row = checkbox.closest('tr');
                const name = row.cells[0].textContent;
                const email = row.cells[3].textContent;
                return { name, email };
            });
        
        if (absentParticipants.length === 0) {
            alert('Tiada peserta yang tidak hadir untuk dihantar peringatan.');
            return;
        }
        
        if (confirm(`Hantar peringatan kehadiran kepada ${absentParticipants.length} peserta yang tidak hadir?`)) {
            // In real app, send AJAX request to send emails
            console.log('Sending reminders to:', absentParticipants);
            
            showNotification(`Peringatan telah dihantar kepada ${absentParticipants.length} peserta`);
        }
    }
    
    // Export attendance
    function exportAttendance() {
        // In real app, generate and download CSV/Excel file
        const rows = Array.from(document.querySelectorAll('.participants-table tbody tr')).map(row => {
            return {
                name: row.cells[0].textContent,
                matrik: row.cells[1].textContent,
                fakulti: row.cells[2].textContent,
                email: row.cells[3].textContent,
                attendance: row.cells[4].querySelector('.status-text').textContent
            };
        });
        
        // Simulate download
        const csvContent = "data:text/csv;charset=utf-8," 
            + "Nama,No. Matrik,Fakulti,Email,Kehadiran\n"
            + rows.map(row => 
                `"${row.name}","${row.matrik}","${row.fakulti}","${row.email}","${row.attendance}"`
            ).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "kehadiran_peserta.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Data kehadiran telah dieksport ke CSV');
    }
    
    // Update statistics (simulated)
    function updateStatistics() {
        const total = document.querySelectorAll('.switch input[type="checkbox"]').length;
        const present = document.querySelectorAll('.switch input[type="checkbox"]:checked').length;
        const rate = Math.round((present / total) * 100);
        
        // In real app, you would update the stats cards here
        console.log(`Updated stats: Total=${total}, Present=${present}, Rate=${rate}%`);
    }
    
    // Show notification
    function showNotification(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease;
            font-weight: 500;
        `;
        notification.textContent = message;
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
<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'peserta-kehadiran';

$organizerId = $_SESSION['user_id'] ?? null;

// Fetch this organizer's programs from DB
$rawPrograms = db()->isConfigured() ? programs()->listByOrganizer($organizerId ?? '') : [];

$programList = [];
foreach ($rawPrograms as $p) {
    $programList[] = ['id' => (string)$p['id'], 'name' => $p['nama']];
}

$searchQuery      = $_GET['search'] ?? '';
$filterStatus     = $_GET['filter_status'] ?? 'all';
$filterAttendance = $_GET['filter_attendance'] ?? 'all';
$filterType       = $_GET['filter_type'] ?? 'all';
$selectedProgram  = $_GET['program'] ?? ($programList[0]['id'] ?? '');

// Security validation: verify that the selected program belongs to the logged-in organizer
$ownedProgramIds = array_column($programList, 'id');
if ($selectedProgram && !in_array((string)$selectedProgram, $ownedProgramIds, true)) {
    die('Access denied: You do not have permission to manage this program.');
}

// Fetch real participants for selected program
$participants = [];
if (db()->isConfigured() && $selectedProgram) {
    $result = db()->select(
        'pendaftaran',
        '?select=id,nama,no_matrik,fakulti,emel,status,pelajar_id,tarikh_daftar,jenis_pendaftaran&program_id=eq.' . (int)$selectedProgram
    );
    
    // Fetch attendance records for this program
    $attendanceMap = [];
    $attResult = db()->select(
        'kehadiran',
        '?select=pelajar_id,status&program_id=eq.' . (int)$selectedProgram
    );
    if ($attResult['ok']) {
        foreach ($attResult['data'] as $att) {
            if (isset($att['pelajar_id'])) {
                $attendanceMap[$att['pelajar_id']] = $att['status'];
            }
        }
    }
    
    if ($result['ok']) {
        foreach ($result['data'] as $row) {
            $pelajarId = $row['pelajar_id'] ?? '';
            $regStatus = $row['status'] ?? 'Registered';
            $statusKehadiran = $attendanceMap[$pelajarId] ?? 'Pending';
            
            $participants[] = [
                'id'                => $row['id'],
                'pelajar_id'        => $pelajarId,
                'nama'              => $row['nama'],
                'matrik'            => $row['no_matrik'],
                'fakulti'           => $row['fakulti'],
                'emel'              => $row['emel'],
                'tarikh_daftar'     => $row['tarikh_daftar'] ?? '',
                'jenis_pendaftaran' => $row['jenis_pendaftaran'] ?? 'Peserta',
                'status'            => $regStatus,
                'kehadiran'         => ($statusKehadiran === 'Hadir'),
                'attendance_status' => $statusKehadiran,
            ];
        }
    }
}

// Apply search & dropdown filters
$filteredParticipants = array_filter($participants, function($p) use ($searchQuery, $filterStatus, $filterAttendance, $filterType) {
    if ($searchQuery) {
        $nameMatch = stripos($p['nama'], $searchQuery) !== false;
        $matricMatch = stripos($p['matrik'], $searchQuery) !== false;
        if (!$nameMatch && !$matricMatch) {
            return false;
        }
    }
    if ($filterStatus !== 'all') {
        if ($p['status'] !== $filterStatus) {
            return false;
        }
    }
    if ($filterAttendance !== 'all') {
        if ($p['attendance_status'] !== $filterAttendance) {
            return false;
        }
    }
    if ($filterType !== 'all') {
        if ($p['jenis_pendaftaran'] !== $filterType) {
            return false;
        }
    }
    return true;
});

$totalRegistered = count(array_filter($participants, fn($p) => $p['status'] !== 'Cancelled'));
$presentCount     = count(array_filter($participants, fn($p) => $p['status'] !== 'Cancelled' && $p['attendance_status'] === 'Hadir'));
$absentCount      = count(array_filter($participants, fn($p) => $p['status'] !== 'Cancelled' && $p['attendance_status'] === 'Tidak Hadir'));
$pendingCount     = count(array_filter($participants, fn($p) => $p['status'] !== 'Cancelled' && $p['attendance_status'] === 'Pending'));
$pesertaCount     = count(array_filter($participants, fn($p) => $p['status'] !== 'Cancelled' && $p['jenis_pendaftaran'] === 'Peserta'));
$crewCount        = count(array_filter($participants, fn($p) => $p['status'] !== 'Cancelled' && $p['jenis_pendaftaran'] === 'Crew/AJK'));
$attendanceRate   = $totalRegistered > 0 ? round(($presentCount / $totalRegistered) * 100) : 0;

$menu = [
    'dashboard_penganjur' => ['Dashboard', 'fa-house'],
    'hebahan-program' => ['Program Announcements', 'fa-bullhorn'],
    'urus-program' => ['Manage Programs', 'fa-calendar-check'],
    'peserta-kehadiran' => ['Participants', 'fa-users'],
    'laporan-statistik' => ['Reports', 'fa-chart-column'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants & Attendance | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .switch-container {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #cbd5e1;
            transition: .3s;
            border-radius: 999px;
        }
        .slider:before {
            content: "";
            position: absolute;
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        .toast {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            font-weight: 800;
            display: none;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .attendance-table th {
            text-align: left;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border);
            font-weight: 800;
            color: var(--text-primary);
        }
        .attendance-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }
        .attendance-table tr:hover td {
            background: var(--bg-secondary);
        }
        .status-badge-inline {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-present { background-color: #ecfdf5; color: #059669; }
        .badge-absent { background-color: #fef2f2; color: #dc2626; }
        .badge-pending { background-color: #fff7ed; color: #d97706; }
        .badge-registered { background-color: #eff6ff; color: #2563eb; }
        .badge-cancelled { background-color: #f1f5f9; color: #64748b; }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <a href="javascript:history.back()" style="display:inline-flex; align-items:center; gap:8px; color:var(--accent-blue); text-decoration:none; font-weight:700; font-size:14px; background:var(--white); padding:8px 16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); transition:var(--transition);"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Participants & Attendance</h1>
                    <p>Track student registrations, verify attendance, and export lists.</p>
                </div>
            </div>

            <!-- OVERVIEW STATS -->
            <div class="stats-cards-grid">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Registered</h3>
                        <div class="stat-val" id="totalStat"><?= $totalRegistered ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Present</h3>
                        <div class="stat-val" id="presentStat"><?= $presentCount ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Absent</h3>
                        <div class="stat-val" id="absentStat"><?= $absentCount ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-user-xmark"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Pending</h3>
                        <div class="stat-val" id="pendingStat"><?= $pendingCount ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-purple">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- SEARCH AND FILTER CONTROLS -->
            <div class="dashboard-card-wrap">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 16px; align-items: flex-end;">
                    <div class="form-group-profile">
                        <label for="programSelect">Choose Programme</label>
                        <select name="program" id="programSelect" class="form-select-profile" onchange="this.form.submit()">
                            <?php if (empty($programList)): ?>
                                <option value="">No programmes yet</option>
                            <?php else: ?>
                                <?php foreach ($programList as $program): ?>
                                    <option value="<?= $program['id'] ?>" <?= $selectedProgram == $program['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($program['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group-profile">
                        <label for="searchInput">Search Student</label>
                        <input type="text" name="search" id="searchInput" class="form-input-profile" placeholder="Name or Matric No." value="<?= htmlspecialchars($searchQuery) ?>" onchange="this.form.submit()">
                    </div>

                    <div class="form-group-profile">
                        <label for="filterStatus">Reg. Status</label>
                        <select name="filter_status" id="filterStatus" class="form-select-profile" onchange="this.form.submit()">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="Registered" <?= $filterStatus === 'Registered' ? 'selected' : '' ?>>Registered</option>
                            <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group-profile">
                        <label for="filterAttendance">Attendance</label>
                        <select name="filter_attendance" id="filterAttendance" class="form-select-profile" onchange="this.form.submit()">
                            <option value="all" <?= $filterAttendance === 'all' ? 'selected' : '' ?>>All Attendance</option>
                            <option value="Hadir" <?= $filterAttendance === 'Hadir' ? 'selected' : '' ?>>Present</option>
                            <option value="Tidak Hadir" <?= $filterAttendance === 'Tidak Hadir' ? 'selected' : '' ?>>Absent</option>
                            <option value="Pending" <?= $filterAttendance === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>

                    <div class="form-group-profile">
                        <label for="filterType">Role Type</label>
                        <select name="filter_type" id="filterType" class="form-select-profile" onchange="this.form.submit()">
                            <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>All Types</option>
                            <option value="Peserta" <?= $filterType === 'Peserta' ? 'selected' : '' ?>>Participant</option>
                            <option value="Crew/AJK" <?= $filterType === 'Crew/AJK' ? 'selected' : '' ?>>Crew/AJK</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-outline" style="border-radius: 999px; height: 46px;" onclick="exportAttendance()">
                        <i class="fas fa-download" style="margin-right: 6px;"></i> Export CSV
                    </button>
                </form>
            </div>

            <!-- TABLE DETAILS -->
            <div class="dashboard-card-wrap">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit';">Student Attendance Sheet</h2>
                        <p style="font-size: 14px; color: var(--text-secondary); margin-top: 4px;"><?= count($filteredParticipants) ?> student(s) matching current criteria.</p>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-primary btn-sm" style="border-radius: 999px;" onclick="markAllPresent()">
                            <i class="fas fa-check" style="margin-right: 6px;"></i> Mark All Present
                        </button>
                        <button class="btn btn-outline btn-sm" style="border-radius: 999px; border-color: #f97316; color: #f97316;" onclick="resetAttendance()">
                            <i class="fas fa-undo" style="margin-right: 6px;"></i> Reset Attendance
                        </button>
                        <button class="btn btn-outline btn-sm" style="border-radius: 999px;" onclick="sendReminders()">
                            <i class="far fa-bell" style="margin-right: 6px;"></i> Send Reminder
                        </button>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <?php if (count($filteredParticipants) > 0): ?>
                        <table class="attendance-table" id="participantsTable">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Matric No.</th>
                                    <th>Faculty</th>
                                    <th>Role Type</th>
                                    <th>Registration Status</th>
                                    <th>Attendance Status</th>
                                    <th style="text-align: center;">Mark Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredParticipants as $participant): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($participant['nama']) ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($participant['emel']) ?></div>
                                        </td>
                                        <td><code style="font-weight: 700; font-family: 'Inter';"><?= htmlspecialchars($participant['matrik']) ?></code></td>
                                        <td><span style="background: var(--bg-secondary); color: var(--text-primary); padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 12px;"><?= htmlspecialchars($participant['fakulti']) ?></span></td>
                                        <td>
                                            <?php if ($participant['jenis_pendaftaran'] === 'Crew/AJK'): ?>
                                                <span class="status-badge-inline" style="background-color: #f5f3ff; color: #7c3aed;">Crew/AJK</span>
                                            <?php else: ?>
                                                <span class="status-badge-inline" style="background-color: #eff6ff; color: #2563eb;">Participant</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($participant['status'] === 'Cancelled'): ?>
                                                <span class="status-badge-inline badge-cancelled">Cancelled</span>
                                            <?php else: ?>
                                                <span class="status-badge-inline badge-registered">Registered</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="attendance-badge-cell">
                                            <?php if ($participant['attendance_status'] === 'Hadir'): ?>
                                                <span class="status-badge-inline badge-present">Present</span>
                                            <?php elseif ($participant['attendance_status'] === 'Tidak Hadir'): ?>
                                                <span class="status-badge-inline badge-absent">Absent</span>
                                            <?php else: ?>
                                                <span class="status-badge-inline badge-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($participant['status'] === 'Cancelled'): ?>
                                                <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">N/A</span>
                                            <?php else: ?>
                                                <div class="switch-container">
                                                    <label class="switch">
                                                        <input type="checkbox" data-pelajar-id="<?= htmlspecialchars($participant['pelajar_id']) ?>" data-attendance-status="<?= htmlspecialchars($participant['attendance_status']) ?>" <?= $participant['kehadiran'] ? 'checked' : '' ?> onchange="toggleAttendance(this)">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 40px;">
                            <i class="fas fa-users-slash" style="font-size: 42px; color: var(--text-muted); margin-bottom: 16px;"></i>
                            <h3 style="font-size: 16px; font-weight: 700;">No students found</h3>
                            <p style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">Try searching for another matric number or adjust the filter parameters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- TOAST -->
    <div class="toast" id="toastMessage"></div>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    function showToast(message) {
        const toast = document.getElementById('toastMessage');
        toast.textContent = message;
        toast.style.display = 'block';

        setTimeout(() => {
            toast.style.display = 'none';
        }, 2500);
    }

    let stats = {
        total: <?= (int)$totalRegistered ?>,
        present: <?= (int)$presentCount ?>,
        absent: <?= (int)$absentCount ?>,
        pending: <?= (int)$pendingCount ?>,
        peserta: <?= (int)$pesertaCount ?>,
        crew: <?= (int)$crewCount ?>
    };

    function renderStats() {
        document.getElementById('totalStat').textContent = stats.total;
        document.getElementById('presentStat').textContent = stats.present;
        document.getElementById('absentStat').textContent = stats.absent;
        document.getElementById('pendingStat').textContent = stats.pending;
    }

    const selectedProgramId = <?= json_encode($selectedProgram) ?>;

    function getStatKey(status) {
        if (status === 'Hadir') return 'present';
        if (status === 'Tidak Hadir') return 'absent';
        return 'pending';
    }

    function toggleAttendance(checkbox) {
        const tr = checkbox.closest('tr');
        const badgeCell = tr.querySelector('.attendance-badge-cell');
        const pelajarId = checkbox.getAttribute('data-pelajar-id');
        const isChecked = checkbox.checked;
        const oldStatus = checkbox.getAttribute('data-attendance-status') || 'Pending';
        const newStatus = isChecked ? 'Hadir' : 'Tidak Hadir';
        const originalBadgeHTML = badgeCell.innerHTML;

        // Optimistically update badge HTML
        if (isChecked) {
            badgeCell.innerHTML = '<span class="status-badge-inline badge-present">Present</span>';
        } else {
            badgeCell.innerHTML = '<span class="status-badge-inline badge-absent">Absent</span>';
        }

        const oldKey = getStatKey(oldStatus);
        const newKey = getStatKey(newStatus);
        if (stats[oldKey] > 0) stats[oldKey]--;
        stats[newKey]++;
        checkbox.setAttribute('data-attendance-status', newStatus);
        renderStats();

        fetch('update_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'toggle',
                program_id: selectedProgramId,
                pelajar_id: pelajarId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                showToast('Attendance updated successfully.');
            } else {
                showToast('Error: ' + data.error);
                checkbox.checked = !isChecked;
                badgeCell.innerHTML = originalBadgeHTML;
                
                // Revert
                stats[newKey]--;
                stats[oldKey]++;
                checkbox.setAttribute('data-attendance-status', oldStatus);
                renderStats();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error updating attendance.');
            checkbox.checked = !isChecked;
            badgeCell.innerHTML = originalBadgeHTML;
            
            // Revert
            stats[newKey]--;
            stats[oldKey]++;
            checkbox.setAttribute('data-attendance-status', oldStatus);
            renderStats();
        });
    }

    function markAllPresent() {
        if (!confirm('Are you sure you want to mark all participants as present?')) return;

        fetch('update_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'mark_all',
                program_id: selectedProgramId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                document.querySelectorAll('#participantsTable tbody tr').forEach(tr => {
                    const checkbox = tr.querySelector('.switch input');
                    if (checkbox) {
                        checkbox.checked = true;
                        checkbox.setAttribute('data-attendance-status', 'Hadir');
                    }
                    const badgeCell = tr.querySelector('.attendance-badge-cell');
                    if (badgeCell) {
                        badgeCell.innerHTML = '<span class="status-badge-inline badge-present">Present</span>';
                    }
                });
                
                stats.present = stats.total;
                stats.absent = 0;
                stats.pending = 0;
                renderStats();
                
                showToast('All participants marked as present.');
            } else {
                showToast('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error marking all present.');
        });
    }

    function resetAttendance() {
        if (!confirm('Are you sure you want to reset all attendance records?')) return;

        fetch('update_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'reset',
                program_id: selectedProgramId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                document.querySelectorAll('#participantsTable tbody tr').forEach(tr => {
                    const checkbox = tr.querySelector('.switch input');
                    if (checkbox) {
                        checkbox.checked = false;
                        checkbox.setAttribute('data-attendance-status', 'Pending');
                    }
                    const badgeCell = tr.querySelector('.attendance-badge-cell');
                    if (badgeCell) {
                        badgeCell.innerHTML = '<span class="status-badge-inline badge-pending">Pending</span>';
                    }
                });
                
                stats.present = 0;
                stats.absent = 0;
                stats.pending = stats.total;
                renderStats();
                
                showToast('Attendance has been reset.');
            } else {
                showToast('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error resetting attendance.');
        });
    }

    function sendReminders() {
        const absent = stats.absent + stats.pending;
        if (absent === 0) {
            alert('No absent or pending participants to remind.');
            return;
        }
        showToast('Reminder sent to ' + absent + ' participant(s).');
    }

    function exportAttendance() {
        const rows = Array.from(document.querySelectorAll('#participantsTable tbody tr')).map(row => {
            const nameCell = row.cells[0];
            const name = nameCell.querySelector('div:first-child')?.textContent.trim() || '';
            const email = nameCell.querySelector('div:last-child')?.textContent.trim() || '';
            const matric = row.cells[1].textContent.trim();
            const faculty = row.cells[2].textContent.trim();
            const type = row.cells[3].textContent.trim();
            const regStatus = row.cells[4].textContent.trim();
            const attendance = row.cells[5].textContent.trim();

            return `"${name}","${email}","${matric}","${faculty}","${type}","${regStatus}","${attendance}"`;
        });

        const csv = "Name,Email,Matric No,Faculty,Type,Reg Status,Attendance\n" + rows.join("\n");
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");

        link.href = URL.createObjectURL(blob);
        link.download = "attendance_record_" + selectedProgramId + ".csv";
        link.click();

        showToast('Attendance CSV exported.');
    }
    </script>
</body>
</html>
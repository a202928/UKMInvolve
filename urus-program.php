<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'urus-program';

// Cancellation POST Handler
if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $cancelId = (int) ($_POST['program_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($cancelId > 0 && !empty($reason)) {
        $progRow = programs()->findById($cancelId);
        if ($progRow && ($progRow['penganjur_id'] ?? '') === ($_SESSION['user_id'] ?? '')) {
            $updateRes = programs()->update($cancelId, ['status' => 'Cancelled']);
            if ($updateRes['ok']) {
                $regResult = db()->select('pendaftaran', '?program_id=eq.' . $cancelId);
                if ($regResult['ok'] && is_array($regResult['data'])) {
                    foreach ($regResult['data'] as $reg) {
                        $regId = $reg['id'];
                        $studentId = $reg['pelajar_id'];
                        db()->update('pendaftaran', '?id=eq.' . $regId, ['status' => 'Cancelled']);
                        users()->revokePoints($studentId, 'registration', $cancelId);
                    }
                }
                $_SESSION['success_message'] = 'Program "' . $progRow['nama'] . '" has been successfully cancelled.';
            } else {
                $_SESSION['error_message'] = 'Failed to cancel program: ' . ($updateRes['error'] ?? 'Database error');
            }
        } else {
            $_SESSION['error_message'] = 'Access denied or program not found.';
        }
    } else {
        $_SESSION['error_message'] = 'Please provide a cancellation reason.';
    }
    header('Location: urus-program.php');
    exit();
}

$organizerInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
$programs = [];
if (db()->isConfigured()) {
    // Bulk fetch saved counts
    $savedCounts = [];
    $seRes = db()->select('saved_events', '?select=program_id');
    if ($seRes['ok'] && is_array($seRes['data'])) {
        foreach ($seRes['data'] as $se) {
            $pid = $se['program_id'];
            $savedCounts[$pid] = ($savedCounts[$pid] ?? 0) + 1;
        }
    }

    foreach (programs()->listByOrganizer($_SESSION['user_id'] ?? '') as $row) {
        $card = programs()->toOrganizerCard($row);
        $card['saved_count'] = $savedCounts[$row['id']] ?? 0;
        $card['shares_count'] = (int)($row['shares_count'] ?? 0);
        $programs[] = $card;
    }
}

$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'date';

$filteredPrograms = array_filter($programs, function($program) use ($filter) {
    if ($filter === 'all') return true;
    if ($filter === 'active') return $program['status'] === 'Ongoing';
    if ($filter === 'upcoming') return $program['status'] === 'Upcoming';
    if ($filter === 'completed') return $program['status'] === 'Completed';
    if ($filter === 'cancelled') return $program['status'] === 'Cancelled';
    return true;
});

// Sort filtered programs
usort($filteredPrograms, function($a, $b) use ($sort) {
    if ($sort === 'registrations') {
        $countA = (int)explode('/', $a['peserta'])[0];
        $countB = (int)explode('/', $b['peserta'])[0];
        return $countB <=> $countA;
    } elseif ($sort === 'saved') {
        return ($b['saved_count'] ?? 0) <=> ($a['saved_count'] ?? 0);
    } elseif ($sort === 'shares') {
        return ($b['shares_count'] ?? 0) <=> ($a['shares_count'] ?? 0);
    } else {
        // Default: Keep DB date order
        return 0;
    }
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Programs | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <!-- NOTIFICATIONS -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-banner alert-banner-success">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-circle-check" style="font-size:18px; color:#10b981;"></i>
                        <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                    </div>
                    <?php if (isset($_SESSION['new_program_id'])): ?>
                        <a href="event-details.php?id=<?= (int)$_SESSION['new_program_id'] ?>" style="background:#10b981;color:white;padding:8px 16px;border-radius:999px;font-size:13px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            <i class="fas fa-eye"></i> View Programme
                        </a>
                        <?php unset($_SESSION['new_program_id']); ?>
                    <?php endif; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-banner alert-banner-error">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-circle-exclamation" style="font-size:18px; color:#ef4444;"></i>
                        <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Manage Programmes</h1>
                    <p>Update, monitor, or cancel programmes that you have published.</p>
                </div>
                <a href="hebahan-program.php" class="btn btn-primary" style="border-radius: 999px;">
                    <i class="fas fa-plus"></i> New Programme
                </a>
            </div>

            <!-- STATS OVERVIEWS -->
            <div class="stats-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 32px;">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Total Events</h3>
                        <div class="stat-val"><?= count($programs) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-blue">
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Active</h3>
                        <div class="stat-val"><?= count(array_filter($programs, fn($p) => $p['status'] === 'Ongoing')) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-green">
                        <i class="fas fa-play"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Upcoming</h3>
                        <div class="stat-val"><?= count(array_filter($programs, fn($p) => $p['status'] === 'Upcoming')) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-orange">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Completed</h3>
                        <div class="stat-val"><?= count(array_filter($programs, fn($p) => $p['status'] === 'Completed')) ?></div>
                    </div>
                    <div class="dashboard-stat-icon stat-icon-purple">
                        <i class="fas fa-circle-check"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-info">
                        <h3>Cancelled</h3>
                        <div class="stat-val"><?= count(array_filter($programs, fn($p) => $p['status'] === 'Cancelled')) ?></div>
                    </div>
                    <div class="dashboard-stat-icon" style="background-color: #fef2f2; color: #ef4444;">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>

            <!-- FILTERS TAB BUTTONS & SORT BY SELECTOR -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                <div class="tab-nav-wrapper" style="margin-bottom: 0;">
                    <button class="tab-nav-btn <?= $filter === 'all' ? 'active' : '' ?>" onclick="window.location.href='?filter=all&sort=<?= $sort ?>'">All</button>
                    <button class="tab-nav-btn <?= $filter === 'active' ? 'active' : '' ?>" onclick="window.location.href='?filter=active&sort=<?= $sort ?>'">Active</button>
                    <button class="tab-nav-btn <?= $filter === 'upcoming' ? 'active' : '' ?>" onclick="window.location.href='?filter=upcoming&sort=<?= $sort ?>'">Upcoming</button>
                    <button class="tab-nav-btn <?= $filter === 'completed' ? 'active' : '' ?>" onclick="window.location.href='?filter=completed&sort=<?= $sort ?>'">Completed</button>
                    <button class="tab-nav-btn <?= $filter === 'cancelled' ? 'active' : '' ?>" onclick="window.location.href='?filter=cancelled&sort=<?= $sort ?>'">Cancelled</button>
                </div>
                
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--text-secondary);">Sort By:</span>
                    <select onchange="window.location.href='?filter=<?= $filter ?>&sort=' + this.value" style="padding: 8px 16px; border: 2px solid var(--border); border-radius: var(--radius-sm); font-size: 13px; font-weight: 700; background: var(--white); color: var(--text-primary); cursor: pointer; outline: none; transition: var(--transition);">
                        <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Date (Newest)</option>
                        <option value="registrations" <?= $sort === 'registrations' ? 'selected' : '' ?>>Most Registered</option>
                        <option value="saved" <?= $sort === 'saved' ? 'selected' : '' ?>>Most Saved</option>
                        <option value="shares" <?= $sort === 'shares' ? 'selected' : '' ?>>Most Shared</option>
                    </select>
                </div>
            </div>

            <!-- PROGRAMS GRID -->
            <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                <?php if (count($filteredPrograms) > 0): ?>
                    <?php foreach ($filteredPrograms as $program): ?>
                        <?php
                        [$current, $total] = explode('/', $program['peserta']);
                        $totalVal = max(1, (int)$total);
                        $percentage = ($current / $totalVal) * 100;
                        
                        $statusStyle = '';
                        if ($program['status'] === 'Cancelled') {
                            $statusStyle = 'background:#fef2f2; color:#ef4444; border:1px solid #fecaca;';
                        } elseif ($program['status'] === 'Ongoing') {
                            $statusStyle = 'background:#fffbeb; color:#d97706; border:1px solid #fde68a;';
                        } elseif ($program['status'] === 'Completed') {
                            $statusStyle = 'background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb;';
                        } else {
                            $statusStyle = 'background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;';
                        }
                        ?>
                        <div class="event-card">
                            <div class="event-img-wrap" style="height: 150px;">
                                <img src="<?= htmlspecialchars(getImagePath($program['poster'])) ?>" alt="<?= htmlspecialchars($program['nama']) ?>" class="event-img">
                                <span style="position: absolute; top: 12px; right: 12px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; <?= $statusStyle ?>">
                                    <?= htmlspecialchars($program['status']) ?>
                                </span>
                            </div>
                            
                            <div class="event-card-body" style="padding: 20px;">
                                <span class="event-category" style="font-size: 11px; margin-bottom: 6px;"><?= htmlspecialchars($program['kategori']) ?></span>
                                <h3 style="font-size: 16px; margin-bottom: 12px; height: 42px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars($program['nama']) ?></h3>
                                
                                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 16px; display:flex; flex-direction:column; gap:6px;">
                                    <div><i class="far fa-calendar-alt" style="width:16px; color: var(--accent-blue);"></i> <?= htmlspecialchars($program['tarikh']) ?> • <?= htmlspecialchars($program['masa']) ?></div>
                                    <div><i class="fas fa-map-marker-alt" style="width:16px; color: var(--accent-blue);"></i> <?= htmlspecialchars($program['lokasi']) ?></div>
                                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 4px; padding-top: 8px; border-top: 1px dashed var(--border); font-weight: 700;">
                                        <span title="Registrations"><i class="fas fa-users" style="color: var(--accent-blue);"></i> <strong><?= explode('/', $program['peserta'])[0] ?></strong> Regs</span>
                                        <span title="Saved by students"><i class="fas fa-heart" style="color: #ef4444;"></i> <strong><?= $program['saved_count'] ?></strong> Saved</span>
                                        <span title="Shared"><i class="fas fa-share-nodes" style="color: var(--accent);"></i> <strong><?= $program['shares_count'] ?></strong> Shares</span>
                                    </div>
                                </div>

                                <div style="margin-bottom: 18px;">
                                    <div style="display:flex; justify-content:space-between; font-size:11px; font-weight:800; margin-bottom:6px;">
                                        <span>Participation</span>
                                        <span><?= round($percentage) ?>%</span>
                                    </div>
                                    <div style="background:var(--bg-secondary); height:6px; border-radius:999px; overflow:hidden;">
                                        <div style="width: <?= $percentage ?>%; height: 100%; background: var(--accent-blue);"></div>
                                    </div>
                                </div>

                                <div style="display:flex; flex-wrap: wrap; gap:10px; border-top:1px solid var(--border); padding-top:14px;">
                                    <a href="edit-program.php?id=<?= $program['id'] ?>" class="btn btn-outline btn-sm" style="flex:1; min-width:80px; border-radius:999px; text-align:center; display:flex; align-items:center; justify-content:center; gap:6px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="urus-program-hub.php?id=<?= $program['id'] ?>" class="btn btn-outline btn-sm" style="flex:1; min-width:80px; border-radius:999px; text-align:center; display:flex; align-items:center; justify-content:center; gap:6px; color: var(--accent); border-color: var(--accent);">
                                        <i class="fas fa-bullhorn"></i> Hub
                                    </a>
                                    
                                    <?php if (in_array($program['jenis_pendaftaran'] ?? '', ['Crew/AJK', 'Peserta & Crew/AJK'])): 
                                        $pendingCount = 0;
                                        if (db()->isConfigured()) {
                                            $pendingRes = db()->select('crew_applications', '?program_id=eq.' . $program['id'] . '&status=eq.Pending');
                                            if ($pendingRes['ok']) $pendingCount = count($pendingRes['data']);
                                        }
                                    ?>
                                        <a href="manage-crew.php?id=<?= $program['id'] ?>" class="btn btn-outline btn-sm" style="flex:1; min-width:80px; border-radius:999px; text-align:center; display:flex; align-items:center; justify-content:center; gap:6px; color: var(--accent-blue); border-color: var(--accent-blue); position: relative;">
                                            <i class="fas fa-users-cog"></i> Crew
                                            <?php if ($pendingCount > 0): ?>
                                                <span style="position:absolute; top:-6px; right:-6px; background:#ef4444; color:white; font-size:10px; font-weight:800; padding:2px 6px; border-radius:999px;"><?= $pendingCount ?></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($program['status'] === 'Ongoing' || $program['status'] === 'Upcoming'): ?>
                                        <button class="btn btn-sm" onclick="openCancelModal(<?= $program['id'] ?>, '<?= htmlspecialchars($program['nama']) ?>')" style="flex:1; min-width:80px; border-radius:999px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-weight:800;">
                                            <i class="fas fa-ban"></i> Cancel
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm" disabled style="flex:1; min-width:80px; border-radius:999px; background:#f9fafb; color:#d1d5db; border:1px solid #e5e7eb; cursor:not-allowed; font-weight:800;">
                                            <i class="fas fa-ban"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 0; background: var(--white); border: 1px dashed var(--border); border-radius: var(--radius-lg);">
                        <i class="fas fa-calendar-xmark" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                        <h3>No Programme Found</h3>
                        <p style="color: var(--text-secondary); font-size: 14px;">No programme matches this filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <!-- CANCELLATION MODAL -->
    <div id="cancelModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.45); z-index:2000; align-items:center; justify-content:center; padding:16px;">
        <div style="background:var(--white); border-radius:var(--radius-lg); padding:32px; max-width:500px; width:100%; box-shadow:var(--shadow-lg);">
            <h3 style="font-size:22px; margin-bottom:8px; font-family:'Outfit';">Cancel Programme</h3>
            <p id="modalProgramName" style="color:var(--text-secondary); font-size:14px; margin-bottom:18px;"></p>
            
            <textarea id="cancelReason" class="form-textarea-profile" placeholder="Please write the cancellation reason here..." style="margin-bottom:20px;"></textarea>
            
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button class="btn btn-outline" onclick="closeCancelModal()" style="border-radius:999px;">Close</button>
                <button class="btn" onclick="confirmCancel()" style="border-radius:999px; background:#ef4444; color:white; border:none; font-weight:800; padding:10px 20px;">Confirm Cancel</button>
            </div>
        </div>
    </div>

    <form id="cancelForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="program_id" id="cancelProgramId">
        <input type="hidden" name="reason" id="cancelReasonInput">
    </form>

    <script>
    let currentProgramId = null;
    let currentProgramName = '';

    function openCancelModal(id, name) {
        currentProgramId = id;
        currentProgramName = name;
        document.getElementById('modalProgramName').textContent =
            'Are you sure you want to cancel the programme "' + name + '"? Registered students will be notified.';
        document.getElementById('cancelReason').value = '';
        document.getElementById('cancelModal').style.display = 'flex';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }

    function confirmCancel() {
        const reason = document.getElementById('cancelReason').value.trim();

        if (!reason) {
            alert('Please write a cancellation reason.');
            return;
        }

        document.getElementById('cancelProgramId').value = currentProgramId;
        document.getElementById('cancelReasonInput').value = reason;
        document.getElementById('cancelForm').submit();
    }

    window.onclick = function(event) {
        const modal = document.getElementById('cancelModal');
        if (event.target === modal) {
            closeCancelModal();
        }
    }
    </script>
</body>
</html>
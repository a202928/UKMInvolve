<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'urus-program';

$programId = (int)($_GET['id'] ?? 0);
if (!db()->isConfigured() || $programId <= 0) {
    die('Program not found or database not configured.');
}

$program = programs()->findById($programId);
if (!$program || ($program['penganjur_id'] ?? '') !== ($_SESSION['user_id'] ?? '')) {
    die('Access denied.');
}

// Handle actions (Approve, Reject, Complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $applicationId = (int)($_POST['application_id'] ?? 0);
    
    if ($applicationId > 0 && in_array($action, ['Approve', 'Reject', 'Completed'])) {
        if ($action === 'Approve') {
            // Validate vacancies
            $appInfo = db()->select('crew_applications', '?select=*,program_crew_positions(mata_ganjaran)&id=eq.' . $applicationId);
            if ($appInfo['ok'] && !empty($appInfo['data'])) {
                $posId = $appInfo['data'][0]['position_id'];
                $stats = programs()->getCrewPositionStats($programId);
                if (isset($stats[$posId]) && $stats[$posId]['remaining'] <= 0) {
                    $_SESSION['error_message'] = "This position is already full.";
                } else {
                    programs()->updateCrewApplicationStatus($applicationId, 'Accepted');
                    $_SESSION['success_message'] = "Application approved.";
                }
            }
        } elseif ($action === 'Reject') {
            programs()->updateCrewApplicationStatus($applicationId, 'Rejected');
            $_SESSION['success_message'] = "Application rejected.";
        } elseif ($action === 'Completed') {
            programs()->updateCrewApplicationStatus($applicationId, 'Completed');
            // Give points to the student!
            $appInfo = db()->select('crew_applications', '?select=*,program_crew_positions(mata_ganjaran)&id=eq.' . $applicationId);
            if ($appInfo['ok'] && !empty($appInfo['data'])) {
                $points = (int)$appInfo['data'][0]['program_crew_positions']['mata_ganjaran'];
                $studentId = $appInfo['data'][0]['pelajar_id'];
                
                // Add points record and update level/streak
                users()->awardPoints(
                    $studentId,
                    'crew_attendance',
                    $programId,
                    $points
                );
                $_SESSION['success_message'] = "Application marked as Completed and {$points} points awarded.";
            }
        }
        header("Location: manage-crew.php?id=" . $programId);
        exit();
    }
}

$applications = programs()->getCrewApplications($programId);

// Restrict MT position applicants to Level 3+ students only
$applications = array_filter($applications, function($app) {
    $posName = $app['program_crew_positions']['nama_jawatan'] ?? '';
    $lvl = (int)($app['users']['level'] ?? 1);
    if (ProgressionService::isMTPosition($posName) && $lvl < 3) {
        return false;
    }
    return true;
});

$crewPositions = programs()->getCrewPositions($programId);

$menu = [
    'dashboard_penganjur' => ['Dashboard', 'fa-house'],
    'hebahan-program' => ['Announcement', 'fa-bullhorn'],
    'urus-program' => ['Manage Programs', 'fa-calendar-check'],
    'peserta-kehadiran' => ['Participants', 'fa-users'],
    'laporan-statistik' => ['Reports', 'fa-chart-column'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];

$organizerInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Crew | <?= htmlspecialchars($program['nama']) ?> | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Manage Crew Applications</h1>
                    <p>Review applications and recruit committee members for <strong><?= htmlspecialchars($program['nama']) ?></strong>.</p>
                </div>
                <div class="dashboard-header-actions">
                    <a href="urus-program.php" class="btn btn-outline" style="border-radius: 999px;">
                        <i class="fas fa-arrow-left" style="margin-right: 6px;"></i> Back
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-banner alert-banner-success" style="margin-bottom: 24px;">
                    <i class="fas fa-check-circle" style="color: #10b981; font-size: 18px;"></i>
                    <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-banner" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 18px;"></i>
                    <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- POSITION SUMMARY -->
            <?php 
            $stats = programs()->getCrewPositionStats($programId);
            if (!empty($stats)): 
            ?>
            <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <?php foreach ($stats as $posId => $stat): 
                    $isFull = $stat['remaining'] <= 0;
                ?>
                    <div class="dashboard-card-wrap" style="padding: 16px; border: 2px solid <?= $isFull ? '#fecaca' : 'var(--border)' ?>; background: <?= $isFull ? '#fef2f2' : 'var(--white)' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <h4 style="font-size: 14px; font-weight: 800; color: var(--text-primary);"><?= htmlspecialchars($stat['name']) ?></h4>
                            <?php if ($isFull): ?>
                                <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 800;">FULL</span>
                            <?php else: ?>
                                <span style="background: var(--bg-secondary); color: var(--text-secondary); padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700;">Rem: <?= $stat['remaining'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary); display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>Total Vacancies: <strong><?= $stat['vacancies'] ?></strong></div>
                            <div style="color: #059669;">Accepted: <strong><?= $stat['accepted'] ?></strong></div>
                            <div style="color: #d97706;">Pending: <strong><?= $stat['pending'] ?></strong></div>
                            <div style="color: #dc2626;">Rejected: <strong><?= $stat['rejected'] ?></strong></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="dashboard-card-wrap">
                <h3 style="font-size: 18px; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 20px;">Crew Applicants</h3>

                <?php if (empty($applications)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                        <p>No crew applications yet.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border);">
                                    <th style="padding: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 700; text-transform: uppercase;">Applicant</th>
                                    <th style="padding: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 700; text-transform: uppercase;">Position</th>
                                    <th style="padding: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 700; text-transform: uppercase;">Faculty/College</th>
                                    <th style="padding: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 700; text-transform: uppercase;">Reason</th>
                                    <th style="padding: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 700; text-transform: uppercase;">Status</th>
                                    <th style="padding: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 700; text-transform: uppercase; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 16px 12px;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <?= ProgressionService::renderAvatarHTML($app['users'], 'sm') ?>
                                                <div>
                                                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;"><?= htmlspecialchars($app['users']['nama'] ?? 'Unknown') ?></div>
                                                    <div style="font-size: 13px; color: var(--text-secondary);"><i class="fas fa-star" style="color:#f59e0b;"></i> <?= (int)($app['users']['mata'] ?? 0) ?> pts &bull; Level <?= (int)($app['users']['level'] ?? 1) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 16px 12px;">
                                            <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($app['program_crew_positions']['nama_jawatan'] ?? 'Unknown') ?></div>
                                        </td>
                                        <td style="padding: 16px 12px; font-size: 13px; color: var(--text-secondary);">
                                            <?= htmlspecialchars($app['users']['fakulti'] ?? '-') ?><br>
                                            <?= htmlspecialchars($app['users']['kolej'] ?? '-') ?>
                                        </td>
                                        <td style="padding: 16px 12px; font-size: 13px; max-width: 250px;">
                                            <?= nl2br(htmlspecialchars($app['alasan'] ?? '-')) ?>
                                        </td>
                                        <td style="padding: 16px 12px;">
                                            <?php
                                            $badgeColor = '#94a3b8';
                                            $badgeBg = '#f1f5f9';
                                            if ($app['status'] === 'Accepted') {
                                                $badgeColor = '#10b981'; $badgeBg = '#d1fae5';
                                            } elseif ($app['status'] === 'Rejected') {
                                                $badgeColor = '#ef4444'; $badgeBg = '#fee2e2';
                                            } elseif ($app['status'] === 'Completed') {
                                                $badgeColor = '#3b82f6'; $badgeBg = '#dbeafe';
                                            } else {
                                                $badgeColor = '#f59e0b'; $badgeBg = '#fef3c7';
                                            }
                                            ?>
                                            <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700;">
                                                <?= htmlspecialchars($app['status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 16px 12px; text-align: right;">
                                            <form method="POST" style="display: flex; gap: 8px; justify-content: flex-end;">
                                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                <?php if ($app['status'] === 'Pending'): 
                                                    $posId = $app['position_id'];
                                                    $isFull = isset($stats[$posId]) && $stats[$posId]['remaining'] <= 0;
                                                ?>
                                                    <?php if ($isFull): ?>
                                                        <button type="button" class="btn btn-sm" style="background: #f1f5f9; color: #94a3b8; border: none; border-radius: 6px; cursor: not-allowed;" title="Position is Full"><i class="fas fa-check"></i></button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action" value="Approve" class="btn btn-sm" style="background: #10b981; color: white; border: none; cursor: pointer; border-radius: 6px;"><i class="fas fa-check"></i></button>
                                                    <?php endif; ?>
                                                    <button type="submit" name="action" value="Reject" class="btn btn-sm" style="background: #ef4444; color: white; border: none; cursor: pointer; border-radius: 6px;"><i class="fas fa-times"></i></button>
                                                <?php elseif ($app['status'] === 'Accepted'): ?>
                                                    <button type="submit" name="action" value="Completed" class="btn btn-sm btn-outline" style="border-radius: 6px; font-size: 12px;" onclick="return confirm('Mark this crew as Completed and award them <?= (int)($app['program_crew_positions']['mata_ganjaran'] ?? 0) ?> points?')">
                                                        <i class="fas fa-award" style="margin-right: 4px;"></i> Mark Completed
                                                    </button>
                                                <?php else: ?>
                                                    <span style="font-size: 13px; color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

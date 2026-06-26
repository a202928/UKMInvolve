<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
$activePage = 'hall-of-fame';

if (!db()->isConfigured()) {
    die("Database not configured.");
}

// Ensure archiving has run
LeaderboardService::checkAndArchivePreviousMonths();

// Fetch historical winners
$history = LeaderboardService::getHallOfFame();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall of Fame | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .hof-container { max-width: 900px; margin: 0 auto; }
        .month-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
            overflow: hidden;
        }
        .month-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .month-header h2 {
            margin: 0;
            font-size: 20px;
            font-family: 'Outfit';
            font-weight: 800;
        }
        .winners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 24px;
        }
        .winner-box {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 16px;
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: var(--transition);
        }
        .winner-box:hover {
            border-color: var(--accent-blue);
            transform: translateY(-2px);
        }
        .winner-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container hof-container">
            
            <div class="dashboard-header-container" style="text-align: center; margin-bottom: 50px;">
                <div style="font-size: 48px; color: #eab308; margin-bottom: 12px;"><i class="fas fa-crown"></i></div>
                <h1 style="font-size: 38px; font-weight: 900; margin-bottom: 8px; color: var(--text-primary); font-family: 'Outfit';">UKM Involve Hall of Fame</h1>
                <p style="font-size: 16px; color: var(--text-secondary);">Honoring our outstanding monthly students, organizers, and top programmes.</p>
            </div>

            <?php if (empty($history)): ?>
                <div style="text-align: center; padding: 80px 20px; background: white; border-radius: var(--radius-md); border: 1px dashed var(--border);">
                    <i class="fas fa-medal" style="font-size: 64px; color: var(--border); margin-bottom: 20px; display: block;"></i>
                    <h3 style="font-size: 20px; font-weight: 800; color: var(--text-secondary); margin-bottom: 8px;">The Hall of Fame is Empty</h3>
                    <p style="font-size: 14px; color: var(--text-muted); max-width: 400px; margin: 0 auto;">History records will automatically compile at the start of the next calendar month.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($history as $ym => $data): ?>
                        <div class="month-card">
                            <div class="month-header">
                                <h2><?= htmlspecialchars($data['month_name']) ?></h2>
                                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 999px; letter-spacing: 0.5px;">Awarded Winners</span>
                            </div>
                            
                            <div class="winners-grid">
                                
                                <!-- Student Winner -->
                                <div class="winner-box">
                                    <div class="winner-title" style="color: #ca8a04;">
                                        🥇 Student of the Month
                                    </div>
                                    <?php if ($data['student']): ?>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="flex-shrink: 0; position: relative;">
                                                <div class="avatar-frame-container avatar-frame-lvl4 size-sm">
                                                    <?php if (!empty($data['student']['image']) && file_exists($data['student']['image'])): ?>
                                                        <img src="<?= htmlspecialchars($data['student']['image']) ?>" class="avatar">
                                                    <?php else: ?>
                                                        <div class="avatar-initials"><?= strtoupper(substr($data['student']['name'], 0, 1)) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div style="min-width: 0;">
                                                <strong style="font-size: 14px; font-weight: 800; color: var(--text-primary); display: block; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                                    <?= htmlspecialchars($data['student']['name']) ?>
                                                </strong>
                                                <span style="font-size: 11px; color: var(--text-secondary);"><?= htmlspecialchars($data['student']['subtext']) ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No student award archived.</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Organizer Winner -->
                                <div class="winner-box">
                                    <div class="winner-title" style="color: #10b981;">
                                        🏢 Organizer of the Month
                                    </div>
                                    <?php if ($data['organizer']): ?>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; background: white; flex-shrink: 0;">
                                                <?php if (!empty($data['organizer']['image']) && file_exists($data['organizer']['image'])): ?>
                                                    <img src="<?= htmlspecialchars($data['organizer']['image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="font-weight: 800; font-size: 14px; color: var(--text-muted);"><?= strtoupper(substr($data['organizer']['name'], 0, 1)) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="min-width: 0;">
                                                <strong style="font-size: 14px; font-weight: 800; color: var(--text-primary); display: block; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                                    <?= htmlspecialchars($data['organizer']['name']) ?>
                                                </strong>
                                                <span style="font-size: 11px; color: var(--text-secondary);"><?= htmlspecialchars($data['organizer']['subtext']) ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No organizer award archived.</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Program Winner -->
                                <div class="winner-box" style="grid-column: span 1;">
                                    <div class="winner-title" style="color: #d97706;">
                                        🏆 Programme of the Month
                                    </div>
                                    <?php if ($data['program']): ?>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img src="<?= htmlspecialchars($data['program']['image']) ?>" style="width: 44px; height: 34px; border-radius: 4px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0;">
                                            <div style="min-width: 0;">
                                                <strong style="font-size: 14px; font-weight: 800; color: var(--text-primary); display: block; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                                    <?= htmlspecialchars($data['program']['name']) ?>
                                                </strong>
                                                <span style="font-size: 11px; color: var(--text-secondary);"><?= htmlspecialchars($data['program']['subtext']) ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No programme award archived.</span>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pentadbir');
$activePage = 'urus_mata_admin';

// Handle AJAX Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save') {
    $input = json_decode(file_get_contents('php://input'), true);
    header('Content-Type: application/json');
    
    if (!$input) {
        echo json_encode(['ok' => false, 'error' => 'Data tidak sah.']);
        exit;
    }
    
    $points = $input['points'] ?? [];
    $levelsData = $input['levels'] ?? [];
    
    $success = true;
    $errors = [];
    
    // Update mata_peraturan
    foreach ($points as $p) {
        $id = isset($p['id']) ? (int)$p['id'] : 0;
        $val = isset($p['value']) ? (int)$p['value'] : 0;
        if ($id > 0) {
            $res = db()->update('mata_peraturan', '?id=eq.' . $id, ['value' => $val]);
            if (!$res['ok']) {
                $success = false;
                $errors[] = 'Gagal mengemas kini peraturan ID ' . $id . ': ' . ($res['error'] ?? 'Ralat');
            }
        }
    }
    
    // Update level_thresholds with all requirement fields
    foreach ($levelsData as $l) {
        $lvl = isset($l['level']) ? (int)$l['level'] : 0;
        $xp = isset($l['xp']) ? (int)$l['xp'] : 0;
        $reqProg = isset($l['req_programs']) ? (int)$l['req_programs'] : 0;
        $reqCrew = isset($l['req_crew']) ? (int)$l['req_crew'] : 0;
        $reqStreak = isset($l['req_streak']) ? (int)$l['req_streak'] : 0;
        
        if ($lvl > 0) {
            $res = db()->update('level_thresholds', '?level=eq.' . $lvl, [
                'xp' => $xp,
                'req_programs' => $reqProg,
                'req_crew' => $reqCrew,
                'req_streak' => $reqStreak
            ]);
            if (!$res['ok']) {
                $success = false;
                $errors[] = 'Gagal mengemas kini tahap ' . $lvl . ': ' . ($res['error'] ?? 'Ralat');
            }
        }
    }
    
    if ($success) {
        echo json_encode(['ok' => true, 'message' => 'Tetapan mata dan tahap telah berjaya disimpan.']);
    } else {
        echo json_encode(['ok' => false, 'error' => implode(', ', $errors)]);
    }
    exit;
}

// Handle add voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_voucher') {
    $title = trim($_POST['title'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $cost = (int)($_POST['points_cost'] ?? 0);
    
    if ($title !== '' && $code !== '' && $cost >= 0) {
        $res = db()->insert('voucher_rewards', [
            'title' => $title,
            'code' => $code,
            'points_cost' => $cost,
            'is_claimed' => false
        ]);
        if ($res['ok']) {
            $_SESSION['success_message'] = "New voucher added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add voucher: " . ($res['error'] ?? 'Unknown error');
        }
    } else {
        $_SESSION['error_message'] = "Please fill in all voucher details.";
    }
    header("Location: urus_mata_admin.php");
    exit();
}

// Handle delete voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_voucher') {
    $voucherId = (int)($_POST['voucher_id'] ?? 0);
    if ($voucherId > 0) {
        $check = db()->select('voucher_rewards', '?id=eq.' . $voucherId);
        if ($check['ok'] && !empty($check['data'][0])) {
            if ($check['data'][0]['is_claimed']) {
                $_SESSION['error_message'] = "Cannot delete a voucher that has already been claimed.";
            } else {
                $res = db()->delete('voucher_rewards', '?id=eq.' . $voucherId);
                if ($res['ok']) {
                    $_SESSION['success_message'] = "Voucher deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to delete voucher.";
                }
            }
        }
    }
    header("Location: urus_mata_admin.php");
    exit();
}

// Handle save cert template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_cert_template') {
    $title = trim($_POST['template_title'] ?? '');
    $text = trim($_POST['template_text'] ?? '');
    
    if ($title !== '' && $text !== '') {
        $check = db()->select('cert_templates', '?id=eq.1');
        if ($check['ok'] && !empty($check['data'][0])) {
            $res = db()->update('cert_templates', '?id=eq.1', [
                'title' => $title,
                'template_text' => $text
            ]);
        } else {
            $res = db()->insert('cert_templates', [
                'id' => 1,
                'title' => $title,
                'template_text' => $text
            ]);
        }
        
        if ($res['ok']) {
            $_SESSION['success_message'] = "e-Certificate template updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update e-Certificate template.";
        }
    } else {
        $_SESSION['error_message'] = "Template title and text cannot be empty.";
    }
    header("Location: urus_mata_admin.php");
    exit();
}

// Fetch real rules from DB
$rules = [];
if (db()->isConfigured()) {
    $result = db()->select('mata_peraturan', '?order=id.asc');
    if ($result['ok'] && !empty($result['data'])) {
        foreach ($result['data'] as $row) {
            $rules[] = [
                'id'    => $row['id'],
                'title' => $row['title'],
                'desc'  => $row['description'] ?? '',
                'icon'  => $row['icon'] ?? 'fa-star',
                'value' => (int)($row['value'] ?? 0),
            ];
        }
    }
}

// Default rules fallback
if (empty($rules)) {
    $rules = [
        ['id'=>1,'title'=>'Daftar Program','desc'=>'Mata diberi apabila pelajar mendaftar program.','icon'=>'fa-user-plus','value'=>20],
        ['id'=>2,'title'=>'Hadir Program','desc'=>'Mata diberi selepas kehadiran disahkan.','icon'=>'fa-calendar-check','value'=>100],
        ['id'=>3,'title'=>'Beri Maklum Balas','desc'=>'Mata diberi selepas pelajar menghantar maklum balas.','icon'=>'fa-comment-dots','value'=>30],
        ['id'=>4,'title'=>'Lengkapkan Minat','desc'=>'Mata diberi selepas pelajar memilih minat.','icon'=>'fa-heart','value'=>50],
        ['id'=>5,'title'=>'Penyertaan Crew/AJK','desc'=>'Mata diberi apabila pelajar menghadiri program sebagai Crew/AJK.','icon'=>'fa-hands-helping','value'=>200],
    ];
}

$levels = [];
if (db()->isConfigured()) {
    $lvlResult = db()->select('level_thresholds', '?order=level.asc');
    if ($lvlResult['ok'] && !empty($lvlResult['data'])) {
        $levels = array_filter($lvlResult['data'], fn($l) => (int)$l['level'] <= 4);
        $levels = array_values($levels);
    }
}

if (empty($levels)) {
    $levels = [
        ['level'=>1,'name'=>'Participant','xp'=>0,'req_programs'=>0,'req_crew'=>0,'req_streak'=>0],
        ['level'=>2,'name'=>'Crew Member','xp'=>150,'req_programs'=>5,'req_crew'=>0,'req_streak'=>0],
        ['level'=>3,'name'=>'MT (Majlis Tertinggi)','xp'=>500,'req_programs'=>5,'req_crew'=>5,'req_streak'=>0],
        ['level'=>4,'name'=>'UKM Elite','xp'=>1000,'req_programs'=>10,'req_crew'=>8,'req_streak'=>1],
    ];
} else {
    $levels = array_slice($levels, 0, 4);
}

// Fetch Vouchers catalog
$vouchers = [];
if (db()->isConfigured()) {
    $vRes = db()->select('voucher_rewards', '?order=is_claimed.asc,points_cost.asc');
    if ($vRes['ok'] && !empty($vRes['data'])) {
        $vouchers = $vRes['data'];
    }
}

// Fetch e-Certificate template
$certTemplate = null;
if (db()->isConfigured()) {
    $tRes = db()->select('cert_templates', '?id=eq.1');
    if ($tRes['ok'] && !empty($tRes['data'][0])) {
        $certTemplate = $tRes['data'][0];
    }
}

$menu = [
    'dashboard-pentadbir' => ['Dashboard', 'fa-house'],
    'pengurusan-pengguna' => ['Pengguna', 'fa-users-gear'],
    'pengurusan-kategori' => ['Kategori', 'fa-layer-group'],
    'urus_mata_admin'     => ['Urus Mata', 'fa-sliders-h'],
    'statistik-sistem'    => ['Statistik', 'fa-chart-pie'],
    'logout'              => ['Logout', 'fa-right-from-bracket']
];
$adminInitial = strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Points Rules | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 32px;
            margin-bottom: 32px;
        }
        @media (max-width: 1000px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
        .rule-list, .level-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .rule-item {
            display: grid;
            grid-template-columns: 48px 1fr 120px;
            gap: 16px;
            align-items: center;
            border: 1px solid var(--border);
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            transition: var(--transition);
        }
        .rule-item:hover {
            border-color: var(--accent-blue);
            box-shadow: var(--shadow-sm);
        }
        .rule-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .rule-text h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
            font-family: 'Outfit', sans-serif;
        }
        .rule-text p {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 0;
        }
        .point-input {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .point-input input, .level-item input {
            width: 80px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            text-align: center;
            font-weight: 800;
            outline: none;
            transition: var(--transition);
            background: var(--white);
            color: var(--text-primary);
        }
        .point-input input:focus, .level-item input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .point-input span {
            font-size: 12px;
            font-weight: 800;
            color: var(--accent);
        }
        
        .level-item {
            display: grid;
            grid-template-columns: 1fr 100px;
            gap: 16px;
            align-items: center;
            border: 1px solid var(--border);
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            transition: var(--transition);
        }
        .level-item:hover {
            border-color: var(--accent-blue);
            box-shadow: var(--shadow-sm);
        }
        .level-name {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .level-badge {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
        }
        .level-name h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            margin-bottom: 2px;
        }
        .level-name p {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 0;
        }
        .summary-box {
            background: rgba(37, 99, 235, 0.05);
            color: var(--accent-blue);
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: var(--radius-sm);
            padding: 20px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .action-row {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Manage Points Rules</h1>
                    <p>Configure co-curricular points for activities and level threshold requirements for students.</p>
                </div>
            </div>

            <!-- SESSION ALERTS -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-banner alert-banner-success" style="margin-bottom: 24px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px; color: #065f46; font-weight: 700;">
                    <i class="fas fa-circle-check" style="font-size:18px; color:#10b981;"></i>
                    <span><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-banner alert-banner-error" style="margin-bottom: 24px; background: #fef2f2; border: 1px solid #fecaca; padding: 12px 16px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px; color: #991b1b; font-weight: 700;">
                    <i class="fas fa-circle-exclamation" style="font-size:18px; color:#ef4444;"></i>
                    <span><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></span>
                </div>
            <?php endif; ?>

            <!-- SETTINGS PANELS -->
            <div class="settings-grid">
                
                <!-- Rule Points Section -->
                <div class="dashboard-card-wrap" style="margin-bottom: 0;">
                    <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Activity XP Points Allocation</h2>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">Configure completion XP for student activities and co-curricular actions.</p>
                    
                    <div class="rule-list">
                        <?php foreach ($rules as $rule): ?>
                            <div class="rule-item">
                                <div class="rule-icon">
                                    <i class="fas <?= $rule['icon'] ?>"></i>
                                </div>
                                <div class="rule-text">
                                    <h3><?= htmlspecialchars($rule['title']) ?></h3>
                                    <p><?= htmlspecialchars($rule['desc']) ?></p>
                                </div>
                                <div class="point-input">
                                    <input type="number" value="<?= $rule['value'] ?>" class="pointRule" data-id="<?= $rule['id'] ?>">
                                    <span>XP</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Level Thresholds & Requirements -->
                <div class="dashboard-card-wrap" style="margin-bottom: 0;">
                    <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Student Level Requirements</h2>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">Configure point and activity thresholds required to unlock progression levels.</p>
                    
                    <div class="level-list">
                        <?php foreach ($levels as $lvl): 
                            $lvlNum = (int)$lvl['level'];
                        ?>
                            <div class="level-item" style="display: flex; flex-direction: column; align-items: stretch; gap: 12px; padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-sm);">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="level-badge" style="background: rgba(37,99,235,0.1); color: var(--accent-blue); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800;"><?= $lvlNum ?></div>
                                    <div>
                                        <h3 style="margin: 0; font-size: 14px; font-weight: 800; color: var(--text-primary); font-family: 'Outfit';">Level <?= $lvlNum ?>: <?= htmlspecialchars($lvl['name']) ?></h3>
                                        <span style="font-size: 11px; color: var(--text-secondary);">Progression Rules</span>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; border-top: 1px solid var(--border); padding-top: 12px; margin-top: 4px;">
                                    <div>
                                        <label style="font-size: 10px; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 4px;">Min XP Points</label>
                                        <input type="number" value="<?= $lvl['xp'] ?>" class="levelXP" data-level="<?= $lvlNum ?>" <?= $lvlNum == 1 ? 'readonly' : '' ?> style="width: 100%; box-sizing: border-box; text-align: left; padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 10px; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 4px;">Min Programs</label>
                                        <input type="number" value="<?= (int)($lvl['req_programs'] ?? 0) ?>" class="levelProg" data-level="<?= $lvlNum ?>" <?= $lvlNum == 1 ? 'readonly' : '' ?> style="width: 100%; box-sizing: border-box; text-align: left; padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 10px; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 4px;">Min Crew Duties</label>
                                        <input type="number" value="<?= (int)($lvl['req_crew'] ?? 0) ?>" class="levelCrew" data-level="<?= $lvlNum ?>" <?= $lvlNum == 1 ? 'readonly' : '' ?> style="width: 100%; box-sizing: border-box; text-align: left; padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 10px; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 4px;">Min Streak (Mths)</label>
                                        <input type="number" value="<?= (int)($lvl['req_streak'] ?? 0) ?>" class="levelStreak" data-level="<?= $lvlNum ?>" <?= $lvlNum == 1 ? 'readonly' : '' ?> style="width: 100%; box-sizing: border-box; text-align: left; padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- SUMMARY AND SAVE SETTINGS -->
            <div class="dashboard-card-wrap">
                <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;">Settings Summary</h2>
                <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 16px;">Review settings and values before saving them system-wide.</p>
                
                <div class="summary-box" id="summaryBox">
                    Registration gives <?= htmlspecialchars($rules[0]['value'] ?? 20) ?> XP, attendance gives <?= htmlspecialchars($rules[1]['value'] ?? 100) ?> XP, feedback gives <?= htmlspecialchars($rules[2]['value'] ?? 30) ?> XP. Highest level is Level 4 (UKM Elite) at <?= htmlspecialchars($levels[3]['xp'] ?? 1000) ?> XP.
                </div>

                <div class="action-row">
                    <button class="btn btn-secondary" onclick="resetSettings()" style="border: 1px solid var(--border); background: var(--white); height: 46px; border-radius: 8px; font-weight: 700; padding: 0 24px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-rotate-right"></i> Reset to Defaults
                    </button>
                    <button class="btn btn-primary" onclick="saveSettings()" style="background-color: var(--accent-blue); color: var(--white); border: none; height: 46px; border-radius: 8px; font-weight: 700; padding: 0 24px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </div>

            <!-- VOUCHER & CERTIFICATE TEMPLATE MANAGER -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 32px;">
                <!-- Voucher Rewards Manager Card -->
                <div class="dashboard-card-wrap" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;"><i class="fas fa-ticket-simple" style="color: var(--accent-blue); margin-right: 6px;"></i> Voucher Catalog Manager</h2>
                        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 20px;">Add, list, and delete unclaimed points redemption vouchers.</p>
                        
                        <!-- Add Voucher Form -->
                        <form method="POST" action="urus_mata_admin.php" style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 24px; display: flex; flex-direction: column; gap: 12px;">
                            <input type="hidden" name="action" value="add_voucher">
                            <h3 style="font-size: 14px; font-weight: 800; margin: 0; color: var(--text-primary);">Add New Voucher</h3>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 4px;">Voucher Title</label>
                                    <input type="text" name="title" required placeholder="e.g. KFC RM10 Voucher" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                                </div>
                                <div>
                                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 4px;">Redemption Code</label>
                                    <input type="text" name="code" required placeholder="e.g. KFC10-XXXX" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                                </div>
                            </div>
                            
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 4px;">Points Cost (XP)</label>
                                <input type="number" name="points_cost" required placeholder="e.g. 300" min="0" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 6px; font-weight: 800; align-self: flex-end; padding: 8px 20px;">
                                <i class="fas fa-plus"></i> Add Voucher
                            </button>
                        </form>

                        <!-- Vouchers List -->
                        <h3 style="font-size: 14px; font-weight: 800; margin-bottom: 12px; color: var(--text-primary);">Current Vouchers</h3>
                        <?php if (empty($vouchers)): ?>
                            <p style="font-size: 13px; color: var(--text-secondary); text-align: center; padding: 20px 0; border: 1px dashed var(--border); border-radius: 8px;">No vouchers in database.</p>
                        <?php else: ?>
                            <div style="max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px; background: var(--white);">
                                <?php foreach ($vouchers as $v): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid var(--border); font-size: 13px;">
                                        <div style="min-width: 0; flex: 1;">
                                            <strong style="display: block; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($v['title']) ?></strong>
                                            <span style="font-size: 11px; color: var(--text-secondary);">Cost: <?= $v['points_cost'] ?> Pts | Code: <code><?= htmlspecialchars($v['code']) ?></code></span>
                                            <?php if ($v['is_claimed']): ?>
                                                <div style="font-size: 10px; color: #10b981; font-weight: 700; margin-top: 2px;">
                                                    Claimed by matric/id: <span style="font-family: monospace;"><?= htmlspecialchars(substr($v['claimed_by'], 0, 8)) ?>...</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex-shrink: 0; display: flex; gap: 6px;">
                                            <?php if ($v['is_claimed']): ?>
                                                <span style="font-size: 11px; font-weight: 800; color: #10b981; background: #d1fae5; padding: 4px 8px; border-radius: 4px;">Claimed</span>
                                            <?php else: ?>
                                                <form method="POST" action="urus_mata_admin.php" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this voucher?');">
                                                    <input type="hidden" name="action" value="delete_voucher">
                                                    <input type="hidden" name="voucher_id" value="<?= $v['id'] ?>">
                                                    <button type="submit" class="btn btn-sm" style="background: #ef4444; color: white; border: none; font-weight: 800; border-radius: 4px; padding: 4px 10px; cursor: pointer;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- e-Certificate Template Editor Card -->
                <div class="dashboard-card-wrap" style="margin-bottom: 0;">
                    <h2 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px;"><i class="fas fa-file-signature" style="color: #ca8a04; margin-right: 6px;"></i> e-Certificate Template Editor</h2>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 20px;">Configure the layout title and body text for Level 4 e-Certificates.</p>
                    
                    <form method="POST" action="urus_mata_admin.php" style="display: flex; flex-direction: column; gap: 16px;">
                        <input type="hidden" name="action" value="save_cert_template">
                        
                        <div>
                            <label style="font-size: 12px; font-weight: 800; color: var(--text-primary); display: block; margin-bottom: 6px;">Certificate Layout Title</label>
                            <input type="text" name="template_title" required value="<?= htmlspecialchars($certTemplate['title'] ?? 'Active Student Excellence e-Certificate') ?>" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                        </div>
                        
                        <div>
                            <label style="font-size: 12px; font-weight: 800; color: var(--text-primary); display: block; margin-bottom: 6px;">Certificate Body Text</label>
                            <textarea name="template_text" required rows="6" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; line-height: 1.5; font-family: sans-serif; resize: vertical;"><?= htmlspecialchars($certTemplate['template_text'] ?? 'Sijil ini dengan sukacitanya dianugerahkan kepada {name} (No. Matrik: {matric}) bagi mengiktiraf pencapaian cemerlang beliau sebagai ahli UKM Elite dalam Program UKMInvolve pada {date}. Sekalung penghargaan atas dedikasi dan penglibatan aktif dalam memperkasakan aktiviti pembangunan mahasiswa UKM.') ?></textarea>
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 6px;">
                                Use placeholders: <code>{name}</code> for student name, <code>{matric}</code> for matric number, and <code>{date}</code> for date.
                            </span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="background-color: #ca8a04; color: white; border: none; font-weight: 800; border-radius: 8px; align-self: flex-end; padding: 10px 24px; cursor: pointer;">
                            <i class="fas fa-save"></i> Save Template
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    function saveSettings() {
        const points = document.querySelectorAll('.pointRule');
        const pointsData = [];
        points.forEach(input => {
            pointsData.push({
                id: input.getAttribute('data-id'),
                value: parseInt(input.value) || 0
            });
        });

        const levelsData = [];
        for (let lvl = 1; lvl <= 4; lvl++) {
            const xp = document.querySelector(`.levelXP[data-level="${lvl}"]`);
            const prog = document.querySelector(`.levelProg[data-level="${lvl}"]`);
            const crew = document.querySelector(`.levelCrew[data-level="${lvl}"]`);
            const streak = document.querySelector(`.levelStreak[data-level="${lvl}"]`);
            
            if (xp) {
                levelsData.push({
                    level: lvl,
                    xp: parseInt(xp.value) || 0,
                    req_programs: parseInt(prog ? prog.value : 0) || 0,
                    req_crew: parseInt(crew ? crew.value : 0) || 0,
                    req_streak: parseInt(streak ? streak.value : 0) || 0
                });
            }
        }

        fetch('urus_mata_admin.php?action=save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                points: pointsData,
                levels: levelsData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                showNotification(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error saving settings.');
        });
    }

    function resetSettings() {
        if (!confirm('Are you sure you want to reset settings to default values?')) return;

        const defaultPoints = [20, 100, 30, 50, 200];
        const defaultLevels = {
            1: { xp: 0, prog: 0, crew: 0, streak: 0 },
            2: { xp: 150, prog: 5, crew: 0, streak: 0 },
            3: { xp: 500, prog: 5, crew: 5, streak: 0 },
            4: { xp: 1000, prog: 10, crew: 8, streak: 1 }
        };

        document.querySelectorAll('.pointRule').forEach((input, index) => {
            input.value = defaultPoints[index];
        });

        for (let lvl = 1; lvl <= 4; lvl++) {
            const xp = document.querySelector(`.levelXP[data-level="${lvl}"]`);
            const prog = document.querySelector(`.levelProg[data-level="${lvl}"]`);
            const crew = document.querySelector(`.levelCrew[data-level="${lvl}"]`);
            const streak = document.querySelector(`.levelStreak[data-level="${lvl}"]`);
            
            if (xp && defaultLevels[lvl]) {
                xp.value = defaultLevels[lvl].xp;
                if (prog) prog.value = defaultLevels[lvl].prog;
                if (crew) crew.value = defaultLevels[lvl].crew;
                if (streak) streak.value = defaultLevels[lvl].streak;
            }
        }

        saveSettings();
    }
    
    // Toast notification
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--accent-blue);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            animation: slideIn 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        `;
        notification.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        
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
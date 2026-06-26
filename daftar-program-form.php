<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'daftar-program';

$programId = (int) ($_GET['id'] ?? 0);
$program = null;

if (db()->isConfigured() && $programId > 0) {
    $row = programs()->findById($programId);
    if ($row) {
        $program = programs()->toStudentDetail($row);
    }
}

if (!$program) {
    header('Location: events.php');
    exit();
}

$studentId = $_SESSION['user_id'] ?? '';
$studentInfo = null;
if (db()->isConfigured() && $studentId) {
    $studentInfo = users()->findById($studentId);
    
    // COMPULSORY PROFILE COMPLETION CHECK
    if ($studentInfo) {
        $requiredFields = ['nama', 'fakulti', 'kolej', 'tahun_pengajian', 'no_telefon', 'avatar_url'];
        $incomplete = false;
        foreach ($requiredFields as $f) {
            if (empty($studentInfo[$f])) {
                $incomplete = true;
                break;
            }
        }
        if ($incomplete) {
            header("Location: profile.php?incomplete=1");
            exit();
        }
    }
}

$defaultNama = $studentInfo['nama'] ?? $_SESSION['nama'] ?? '';
$defaultEmel = $studentInfo['emel'] ?? $_SESSION['emel'] ?? '';
$defaultMatrik = $studentInfo['matrik'] ?? '';
$defaultFakulti = $studentInfo['fakulti'] ?? '';
$defaultTelefon = $studentInfo['no_telefon'] ?? '';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve values directly from profile/session
    $nama = $defaultNama;
    $no_matrik = $defaultMatrik;
    $fakulti = $defaultFakulti;
    $email = $defaultEmel;
    $telefon = !empty($defaultTelefon) ? $defaultTelefon : '-';
    $alasan = trim($_POST['alasan'] ?? '');
    
    // Validations
    if (empty($nama)) {
        $errors[] = 'Full Name is missing from your profile. Please complete your profile first.';
    }
    if (empty($no_matrik)) {
        $errors[] = 'Matric Number is missing from your profile. Please complete your profile first.';
    }
    if (empty($fakulti)) {
        $errors[] = 'Faculty is missing from your profile. Please complete your profile first.';
    }
    
    // Check duplicates
    if ($studentId && empty($errors)) {
        $duplicate = registrations()->findDuplicate($studentId, $programId);
        if ($duplicate) {
            $errors[] = 'You are already registered for this program!';
        }
    }
    
    // Check target audience
    if (empty($errors)) {
        $audienceRaw = $program['target_audience'] ?? ['ALL'];
        $audience = is_string($audienceRaw) ? json_decode($audienceRaw, true) : $audienceRaw;
        if (!is_array($audience)) $audience = ['ALL'];
        
        if (!in_array('ALL', $audience)) {
            if (!in_array($fakulti, $audience) && !in_array($studentInfo['kolej'] ?? '', $audience)) {
                $errors[] = 'You are not eligible to register for this program (Faculty/College restricted).';
            }
        }
    }
    
    // Check deadline
    if (empty($errors) && !empty($program['deadline_date'])) {
        $deadlineDateTime = $program['deadline_date'] . ' ' . ($program['deadline_time'] ?: '23:59:00');
        if (strtotime($deadlineDateTime) < time()) {
            $errors[] = 'Registration for this program is closed (past deadline).';
        }
    }
    
    if (empty($errors)) {
        if (!db()->isConfigured()) {
            $errors[] = 'Database is not configured.';
        } else {
            // Check capacity dynamically
            $activeRegs = registrations()->countActiveByProgram($programId);
            if ($activeRegs >= $program['capacity']) {
                $errors[] = 'This program is already full.';
            } else {
                $regResult = registrations()->create([
                    'program_id' => $programId,
                    'pelajar_id' => $studentId ?: null,
                    'nama' => $nama,
                    'no_matrik' => $no_matrik,
                    'fakulti' => $fakulti,
                    'emel' => $email,
                    'telefon' => $telefon,
                    'alasan' => $alasan ?: 'N/A',
                    'status' => 'Registered',
                    'jenis_pendaftaran' => $program['jenis_pendaftaran'] ?? 'Peserta',
                ]);

                if ($regResult['ok']) {
                    // Update participants count in DB
                    $newCount = registrations()->countActiveByProgram($programId);
                    programs()->updateParticipantCount($programId, $newCount);
                    $program['participants'] = $newCount;
                    
                    // Award points for successful program registration
                    users()->awardPoints($studentId, 'registration', $programId);
                    
                    header('Location: registration-success.php?id=' . $programId);
                    exit();
                } else {
                    $errors[] = 'Registration failed: ' . ($regResult['error'] ?? 'Database error');
                }
            }
        }
    }
}

$seatsLeft = max(0, $program['capacity'] - $program['participants']);
$isFull = $seatsLeft <= 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for <?= htmlspecialchars($program['title']) ?> | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <?php if ($success): ?>
                <!-- SUCCESS REGISTRATION CONTAINER -->
                <div style="max-width: 600px; margin: 40px auto; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 48px 32px; text-align: center; box-shadow: var(--shadow-lg);">
                    <i class="fas fa-circle-check" style="font-size: 64px; color: #10b981; margin-bottom: 24px;"></i>
                    <h2 style="font-size: 28px; margin-bottom: 12px;">Registration Successful!</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 32px; font-size: 15px; line-height: 1.6;">
                        You have successfully registered for <strong><?= htmlspecialchars($program['title']) ?></strong>.<br>
                        Activity points have been added to your profile.
                    </p>
                    
                    <div style="display: flex; gap: 16px; justify-content: center;">
                        <a href="rekod-penyertaan.php" class="btn btn-primary" style="border-radius:999px;">My Registrations</a>
                        <a href="dashboard_pelajar.php" class="btn btn-outline" style="border-radius:999px;">Back to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <a href="javascript:history.back()" style="display:inline-flex; align-items:center; gap:8px; color:var(--accent-blue); text-decoration:none; font-weight:700; font-size:14px; background:var(--white); padding:8px 16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); transition:var(--transition);"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="dashboard-header-container">
                    <div class="dashboard-header-title">
                        <h1>Program Registration</h1>
                        <p>Confirm registration and add additional notes if needed.</p>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert-banner alert-banner-error">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars(implode(', ', $errors)) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="profile-wrapper">
                    <!-- LEFT COLUMN: EVENT BRIEF -->
                    <div class="profile-avatar-card" style="text-align: left; padding: 24px;">
                        <img src="<?= htmlspecialchars(getImagePath($program['image'])) ?>" alt="<?= htmlspecialchars($program['title']) ?>" style="width:100%; height:auto; max-height:240px; object-fit:contain; border-radius:var(--radius-sm); margin-bottom:16px;">
                        <span class="event-category" style="display:inline-block; margin-bottom:10px; font-weight:800; font-size:12px; text-transform:uppercase; color:var(--accent-blue); background:rgba(37,99,235,0.08); padding:4px 8px; border-radius:4px;">
                            <?= htmlspecialchars($program['category']) ?>
                        </span>
                        <h3 style="font-size: 20px; font-weight:800; color:var(--primary); margin-bottom:12px; line-height:1.3; font-family:'Outfit';"><?= htmlspecialchars($program['title']) ?></h3>
                        
                        <div class="event-meta-list" style="display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
                            <div>
                                <i class="far fa-calendar-alt" style="width:16px; margin-right:6px; color:var(--accent-blue);"></i>
                                <span><?= htmlspecialchars($program['date']) ?></span>
                            </div>
                            <div>
                                <i class="far fa-clock" style="width:16px; margin-right:6px; color:var(--accent-blue);"></i>
                                <span><?= htmlspecialchars($program['time']) ?></span>
                            </div>
                            <div>
                                <i class="fas fa-map-marker-alt" style="width:16px; margin-right:6px; color:var(--accent-blue);"></i>
                                <span><?= htmlspecialchars($program['location']) ?></span>
                            </div>
                        </div>

                        <div style="border-top:1px solid var(--border); padding-top:14px; display:flex; justify-content:space-between; font-size:13px; font-weight:700;">
                            <span style="color:var(--text-secondary);">Available Seats:</span>
                            <span style="color:<?= $isFull ? '#ef4444' : '#10b981' ?>;"><?= $isFull ? 'Registration Full' : $seatsLeft . ' seats left' ?></span>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: SIMPLIFIED FORM -->
                    <div class="dashboard-card-wrap" style="margin-bottom:0;">
                        <h2 style="font-size:22px; font-weight:800; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:14px;">Confirm Your Registration</h2>
                        
                        <div style="background:var(--bg-main); border:1px solid var(--border); border-radius:var(--radius-sm); padding:16px; margin-bottom:24px;">
                            <h4 style="font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-secondary); margin-bottom:10px;">Logged In Profile details</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:14px;">
                                <div><strong style="color:var(--text-secondary);">Name:</strong> <?= htmlspecialchars($defaultNama) ?></div>
                                <div><strong style="color:var(--text-secondary);">Matric:</strong> <?= htmlspecialchars($defaultMatrik ?: 'Not set') ?></div>
                                <div><strong style="color:var(--text-secondary);">Faculty:</strong> <?= htmlspecialchars($defaultFakulti ?: 'Not set') ?></div>
                                <div><strong style="color:var(--text-secondary);">Email:</strong> <?= htmlspecialchars($defaultEmel) ?></div>
                            </div>
                            <?php if (empty($defaultMatrik) || empty($defaultFakulti)): ?>
                                <p style="color:#ef4444; font-size:12px; font-weight:700; margin-top:12px; display:flex; align-items:center; gap:6px;">
                                    <i class="fas fa-circle-exclamation"></i>
                                    Please <a href="profile.php" style="text-decoration:underline; color:#2563eb;">complete your profile</a> before registering.
                                </p>
                            <?php endif; ?>
                        </div>

                        <form method="POST">
                            <div class="form-group-profile" style="margin-bottom: 24px;">
                                <label for="alasan" style="font-weight:700; font-size:13px; color:var(--text-secondary); display:block; margin-bottom:8px;">Additional Notes (Optional)</label>
                                <textarea name="alasan" id="alasan" class="form-textarea-profile" placeholder="Enter any questions, dietary preferences, or additional notes here..."></textarea>
                            </div>

                            <p style="color: var(--text-secondary); font-size:13px; line-height:1.5; margin-bottom:24px; padding:12px; background:#eff6ff; border-radius:var(--radius-sm); border:1px solid #bfdbfe;">
                                <i class="fas fa-circle-info" style="color:var(--accent-blue); margin-right:6px;"></i>
                                By registering, you confirm that your profile details are accurate and you agree to attend all registered sessions.
                            </p>

                            <div style="display:flex; justify-content:flex-end; gap:16px;">
                                <a href="event-details.php?id=<?= $programId ?>" class="btn btn-outline" style="border-radius:999px;">Cancel</a>
                                <button type="submit" class="btn btn-primary" style="border-radius:999px; font-weight:800;" <?= ($isFull || empty($defaultMatrik) || empty($defaultFakulti)) ? 'disabled style="opacity:0.6; cursor:not-allowed;"' : '' ?>>
                                    <?php if ($isFull): ?>
                                        Registration Full
                                    <?php else: ?>
                                        Register Now
                                    <?php endif; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

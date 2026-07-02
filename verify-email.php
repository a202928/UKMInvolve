<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/bootstrap.php';

$emel = strtolower(trim($_GET['emel'] ?? $_POST['emel'] ?? ''));
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';

if (!db()->isConfigured() || !$emel) {
    header('Location: register.php');
    exit();
}

$user = users()->findByEmel($emel);
if (!$user) {
    $_SESSION['error'] = 'User not found.';
    header('Location: register.php');
    exit();
}

// Redirect if already verified
if (($user['status'] ?? '') === 'aktif') {
    loginUser($user);
    header('Location: ' . dashboardForRole($user['peranan']));
    exit();
}

$error = '';
$success = false;

// 1. Handle OTP Verification
if (isset($_POST['verify_otp'])) {
    $otpInput = trim($_POST['otp'] ?? '');
    
    if (empty($otpInput)) {
        $error = 'Sila masukkan kod OTP.';
    } else {
        $dbOtp = $user['verification_code'] ?? '';
        $dbExpiry = $user['verification_code_expires_at'] ?? '';
        
        $isExpired = false;
        if (!empty($dbExpiry)) {
            $isExpired = strtotime($dbExpiry) < time();
        }
        
        if ($dbOtp !== $otpInput) {
            $error = 'Kod OTP tidak sah. Sila cuba lagi.';
        } elseif ($isExpired) {
            $error = 'Kod OTP telah tamat tempoh (15 minit). Sila klik Resend OTP.';
        } else {
            // Success! Update status to aktif
            users()->updateStatus($user['id'], 'aktif');
            
            // Award Welcome Points (+20 pts)
            users()->awardPoints($user['id'], 'welcome', null, 20);
            
            // Sync points & award 'Verified Member' badge
            users()->updateStudentPointsAndBadges($user['id']);
            
            // Refresh user details and log them in
            $updatedUser = users()->findById($user['id']);
            loginUser($updatedUser);
            
            $success = true;
        }
    }
}

// 2. Handle Change Email request
if (isset($_POST['change_email'])) {
    $newEmail = strtolower(trim($_POST['new_email'] ?? ''));
    $role = $user['peranan'] ?? 'pelajar';
    
    if (empty($newEmail)) {
        $error = 'Sila masukkan emel baharu.';
    } elseif ($newEmail === $emel) {
        $error = 'Emel baharu tidak boleh sama dengan emel asal.';
    } else {
        // Validate domain based on role
        $validDomain = false;
        if ($role === 'pelajar') {
            if (substr($newEmail, -17) === '@siswa.ukm.edu.my') {
                $validDomain = true;
            } else {
                $error = 'Sila gunakan emel pelajar rasmi UKM (@siswa.ukm.edu.my) sahaja.';
            }
        } else {
            if (preg_match('/@(ukm\.edu\.my|gmail\.com)$/i', $newEmail)) {
                $validDomain = true;
            } else {
                $error = 'Sila gunakan emel @ukm.edu.my atau gmail.com sahaja.';
            }
        }
        
        if ($validDomain) {
            // Check uniqueness
            $dupUser = users()->findByEmel($newEmail);
            if ($dupUser) {
                $error = 'Emel baharu ini sudah didaftarkan.';
            } else {
                // Update email in DB and trigger a fresh OTP
                $newOtp = sprintf('%06d', mt_rand(100000, 999999));
                $newExpiry = date('c', time() + 15 * 60);
                
                $updateRes = users()->updateUser($user['id'], [
                    'emel' => $newEmail,
                    'verification_code' => $newOtp,
                    'verification_code_expires_at' => $newExpiry,
                    'otp_attempts' => 1,
                    'last_otp_sent_at' => date('c')
                ]);
                
                if ($updateRes['ok']) {
                    // Send OTP email
                    MailerService::sendOTP($newEmail, $user['nama'], $newOtp);
                    $_SESSION['success_message'] = "Emel berjaya ditukar. OTP baharu telah dihantar ke emel baharu anda.";
                    header('Location: verify-email.php?emel=' . urlencode($newEmail) . ($redirect ? '&redirect=' . urlencode($redirect) : ''));
                    exit();
                } else {
                    $error = 'Gagal mengemas kini emel: ' . ($updateRes['error'] ?? 'Ralat Pangkalan Data');
                }
            }
        }
    }
}

// Calculate remaining cooldown timer parameters
$lastSent = $user['last_otp_sent_at'] ?? '';
$cooldownSec = 0;
if (!empty($lastSent)) {
    $elapsed = time() - strtotime($lastSent);
    if ($elapsed < 60) {
        $cooldownSec = 60 - $elapsed;
    }
}

$otpAttempts = (int)($user['otp_attempts'] ?? 0);
$maxAttemptsReached = ($otpAttempts >= 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .success-badge-card {
            border: 2px solid #34d399;
            background: #f0fdf4;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
        }
        .success-badge-icon {
            font-size: 40px;
            color: #10b981;
            margin-bottom: 8px;
        }
        .points-box {
            display: inline-block;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-weight: 800;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main style="padding: 80px 0; display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 160px); position: relative; background: url('ukm_background2.jpeg') no-repeat center center / cover;">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1;"></div>
        
        <div style="width: 100%; max-width: 480px; padding: 0 24px; position: relative; z-index: 10;">
            <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 40px 32px; box-shadow: var(--shadow-lg);">
                
                <?php if ($success): ?>
                    <!-- VERIFICATION SUCCESS SCREEN -->
                    <div style="text-align: center; padding: 10px 0;">
                        <span style="display:inline-flex; width: 80px; height: 80px; border-radius: 50%; background: #d1fae5; color: #10b981; align-items: center; justify-content: center; font-size: 38px; margin-bottom: 20px; border: 4px solid #a7f3d0;">
                            <i class="fas fa-check"></i>
                        </span>
                        <h2 style="font-size: 26px; font-weight: 800; color: var(--primary); margin-bottom: 8px;">✔ Email Verified!</h2>
                        <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                            Welcome to UKMInvolve. Your account is now active!
                        </p>
                        
                        <!-- Gamification Unlocked Banners -->
                        <div class="success-badge-card">
                            <div class="success-badge-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #047857; margin-bottom: 4px;">Badge Earned</div>
                            <div style="font-size: 18px; font-weight: 900; color: #064e3b;">Verified Member</div>
                            <div style="font-size: 12px; color: #065f46; margin-top: 4px;">You have successfully completed email verification.</div>
                            
                            <div class="points-box">
                                <i class="fas fa-coins" style="color: #fbbf24; margin-right: 4px;"></i> +20 Welcome Points
                            </div>
                        </div>

                        <a href="<?= $redirect ?: dashboardForRole($user['peranan']) ?>" class="btn btn-primary" style="width: 100%; height: 48px; border-radius: 999px; font-weight: 800; font-size: 15px; text-decoration: none; display: inline-flex; justify-content: center; align-items: center;">
                            Go to Dashboard
                        </a>
                    </div>

                <?php else: ?>
                    <!-- OTP VERIFICATION SCREEN -->
                    <div style="text-align: center; margin-bottom: 28px;">
                        <img src="UKM.png" alt="UKM Logo" style="width: 44px; height: 44px; margin: 0 auto 12px; object-fit: contain;">
                        <h2 style="font-size: 24px; font-weight: 800; color: var(--primary); margin-bottom: 8px;">Enter OTP</h2>
                        <p style="color: var(--text-secondary); font-size: 13.5px; line-height: 1.5;">
                            Sila masukkan 6-digit <strong>One-Time Password (OTP)</strong> yang dihantar ke:<br>
                            <span style="color: var(--primary); font-weight: 700;"><?= htmlspecialchars($emel) ?></span>
                        </p>
                    </div>

                    <?php 
                    $successMessage = $_SESSION['success_message'] ?? '';
                    unset($_SESSION['success_message']);
                    if ($successMessage): 
                    ?>
                        <div style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-circle-check" style="color: #10b981;"></i>
                            <span><?= htmlspecialchars($successMessage) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 12px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="verify-email.php" method="POST" style="margin-bottom: 24px;">
                        <input type="hidden" name="emel" value="<?= htmlspecialchars($emel) ?>">
                        <?php if ($redirect): ?>
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                        <?php endif; ?>

                        <div class="form-group-profile" style="margin-bottom: 20px;">
                            <label for="otp">One-Time Password (OTP)</label>
                            <input type="text" name="otp" id="otp" maxlength="6" pattern="^[0-9]{6}$" class="form-input-profile" placeholder="e.g. 123456" required style="border-radius: var(--radius-sm); text-align: center; font-size: 20px; letter-spacing: 6px; font-weight: 800; height: 48px;">
                        </div>

                        <button type="submit" name="verify_otp" class="btn btn-primary" style="width: 100%; height: 48px; border-radius: 999px; font-weight: 800; font-size: 15px; display: flex; justify-content: center; align-items: center;">
                            Verify & Activate Account
                        </button>
                    </form>

                    <!-- Resend / Throttle Area -->
                    <div style="text-align: center; border-top: 1px solid var(--border); padding-top: 20px; font-size: 13px; color: var(--text-secondary);">
                        <p style="margin-bottom: 12px;">
                            Tidak menerima OTP? atau OTP tamat tempoh?
                        </p>
                        
                        <?php if ($maxAttemptsReached): ?>
                            <div style="background: #fff1f2; color: #be123c; border: 1px solid #ffe4e6; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 12px; font-weight: 700;">
                                <i class="fas fa-ban"></i> Had limit resend dicapai (Maksimum 5 attempts). Sila hubungi pentadbir atau buat pendaftaran baru.
                            </div>
                            <button class="btn btn-outline" disabled style="width: 100%; height: 38px; border-radius: 999px; font-size: 13px; font-weight: 800;">
                                Resend OTP (Blocked)
                            </button>
                        <?php elseif ($cooldownSec > 0): ?>
                            <button id="resend-btn" class="btn btn-outline" disabled style="width: 100%; height: 38px; border-radius: 999px; font-size: 13px; font-weight: 800;">
                                Resend OTP dalam <span id="cooldown-timer"><?= $cooldownSec ?></span>s
                            </button>
                            <script>
                                let timeLeft = <?= $cooldownSec ?>;
                                const timerSpan = document.getElementById('cooldown-timer');
                                const resendBtn = document.getElementById('resend-btn');
                                const interval = setInterval(() => {
                                    timeLeft--;
                                    if (timeLeft <= 0) {
                                        clearInterval(interval);
                                        resendBtn.disabled = false;
                                        resendBtn.innerText = "Resend OTP";
                                        resendBtn.style.cursor = "pointer";
                                        resendBtn.onclick = () => {
                                            window.location.href = "resend-verification.php?emel=" + encodeURIComponent("<?= $emel ?>") + "<?= $redirect ? '&redirect=' . urlencode($redirect) : '' ?>";
                                        };
                                    } else {
                                        timerSpan.innerText = timeLeft;
                                    }
                                }, 1000);
                            </script>
                        <?php else: ?>
                            <a href="resend-verification.php?emel=<?= urlencode($emel) ?><?= $redirect ? '&redirect=' . urlencode($redirect) : '' ?>" class="btn btn-outline" style="width: 100%; height: 38px; border-radius: 999px; font-size: 13px; font-weight: 800; display: inline-flex; justify-content: center; align-items: center; text-decoration: none;">
                                Resend OTP (<?= 5 - $otpAttempts ?> attempts left)
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Change Email Accordion Toggle -->
                    <div style="text-align: center; margin-top: 24px;">
                        <a href="javascript:void(0)" onclick="toggleChangeEmailForm()" style="color: var(--accent-blue); font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fas fa-pen-to-square"></i> Wrong email? Change Email
                        </a>

                        <div id="change-email-wrapper" style="display: none; text-align: left; border: 1px solid var(--border); background: var(--bg-main); border-radius: var(--radius-sm); padding: 16px; margin-top: 14px;">
                            <form action="verify-email.php" method="POST">
                                <input type="hidden" name="emel" value="<?= htmlspecialchars($emel) ?>">
                                <?php if ($redirect): ?>
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                                <?php endif; ?>

                                <div class="form-group-profile" style="margin-bottom: 12px;">
                                    <label for="new_email" style="font-size: 12px;">Emel Baharu</label>
                                    <input type="email" name="new_email" id="new_email" class="form-input-profile" placeholder="e.g. baharu@siswa.ukm.edu.my" required style="border-radius: var(--radius-sm); font-size: 13px; height: 36px; padding: 6px 12px;">
                                </div>

                                <button type="submit" name="change_email" class="btn btn-primary" style="width: 100%; height: 36px; font-size: 12px; font-weight: 800; border-radius: 999px; display: flex; justify-content: center; align-items: center;">
                                    Tukar Emel & Hantar OTP
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    function toggleChangeEmailForm() {
        const formWrap = document.getElementById('change-email-wrapper');
        if (formWrap.style.display === 'none') {
            formWrap.style.display = 'block';
        } else {
            formWrap.style.display = 'none';
        }
    }
    </script>
</body>
</html>

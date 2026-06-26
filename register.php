<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/bootstrap.php';

if (db()->isConfigured()) {
    users()->cleanExpiredPendingAccounts();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';

$envStatus = Database::checkEnv();
$configHint = Database::getSetupMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main style="padding: 80px 0; display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 160px); position: relative; background: url('ukm_background2.jpeg') no-repeat center center / cover;">
        <!-- Neutral dark overlay to make the form pop -->
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1;"></div>
        
        <div style="width: 100%; max-width: 480px; padding: 0 24px; position: relative; z-index: 10;">
            <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 40px 32px; box-shadow: var(--shadow-lg);">
                <?php if ($success): ?>
                    <!-- SUCCESS STATE -->
                    <div style="text-align: center; padding: 20px 0;">
                        <i class="fas fa-circle-check" style="font-size: 58px; color: #10b981; margin-bottom: 20px;"></i>
                        <h2 style="font-size: 26px; font-weight: 800; color: var(--primary); margin-bottom: 8px;">Registration Successful!</h2>
                        <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.6;">
                            Your account has been registered.<br>
                            You will be redirected to the login page in a moment.
                        </p>
                    </div>

                    <script>
                        setTimeout(() => {
                            window.location.href = "login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>";
                        }, 2000);
                    </script>

                <?php else: ?>
                    <!-- REGISTER FORM -->
                    <div style="text-align: center; margin-bottom: 32px;">
                        <img src="UKM.png" alt="UKM Logo" style="width: 48px; height: 48px; margin: 0 auto 16px; object-fit: contain;">
                        <h2 style="font-size: 26px; font-weight: 800; color: var(--primary); margin-bottom: 8px;">Create Account</h2>
                        <p style="color: var(--text-secondary); font-size: 14px;">Sign up for a student profile on UKMInvolve</p>
                    </div>

                    <?php 
                    if (isset($_SESSION['pending_email'])): 
                        $pendingEmail = $_SESSION['pending_email'];
                        unset($_SESSION['pending_email']);
                    ?>
                        <div style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 24px; text-align: left;">
                            <div style="font-weight: 800; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-triangle-exclamation" style="color: #d97706;"></i> 
                                Email Unverified
                            </div>
                            <p style="margin: 0 0 12px 0; line-height: 1.5; color: #78350f;">
                                The email <strong><?= htmlspecialchars($pendingEmail) ?></strong> has already been registered but has not yet been verified.
                            </p>
                            <div style="display: flex; gap: 8px;">
                                <a href="verify-email.php?emel=<?= urlencode($pendingEmail) ?><?= $redirect ? '&redirect=' . urlencode($redirect) : '' ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11px; background: white; text-decoration: none; border-color: #f59e0b; color: #d97706; border-radius: 4px; font-weight: 800;">Continue Verification</a>
                                <a href="resend-verification.php?emel=<?= urlencode($pendingEmail) ?><?= $redirect ? '&redirect=' . urlencode($redirect) : '' ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11px; background: white; text-decoration: none; border-color: #f59e0b; color: #d97706; border-radius: 4px; font-weight: 800;">Resend OTP</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="process_register.php" method="POST">
                        <?php if ($redirect): ?>
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                        <?php endif; ?>
                        
                        <div class="form-group-profile" style="margin-bottom: 16px;">
                            <label for="nama">Full Name</label>
                            <input type="text" name="nama" id="nama" class="form-input-profile" placeholder="e.g. Muhammad Ali" required style="border-radius: var(--radius-sm);">
                        </div>

                        <div class="form-group-profile" style="margin-bottom: 16px;">
                            <label for="emel">UKM Student Email Address</label>
                            <input type="email" name="emel" id="emel" class="form-input-profile" placeholder="e.g. a123456@siswa.ukm.edu.my" required pattern="^[a-zA-Z0-9._%+-]+@siswa\.ukm\.edu\.my$" title="Sila gunakan emel @siswa.ukm.edu.my sahaja" style="border-radius: var(--radius-sm);">
                            <small style="font-size: 11px; color: var(--text-secondary); display: block; margin-top: 4px;">* Hanya emel dengan domain <strong>@siswa.ukm.edu.my</strong> dibenarkan.</small>
                        </div>

                        <div class="form-group-profile" style="margin-bottom: 16px;">
                            <label for="matrik">Matric Number</label>
                            <input type="text" name="matrik" id="matrik" class="form-input-profile" placeholder="e.g. A123456" required style="border-radius: var(--radius-sm);">
                        </div>

                        <div class="form-group-profile" style="margin-bottom: 16px;">
                            <label for="kata_laluan">Password</label>
                            <input type="password" name="kata_laluan" id="kata_laluan" class="form-input-profile" placeholder="Minimum 6 characters" required style="border-radius: var(--radius-sm);">
                        </div>

                        <div class="form-group-profile" style="margin-bottom: 24px;">
                            <label for="sahkan_kata_laluan">Confirm Password</label>
                            <input type="password" name="sahkan_kata_laluan" id="sahkan_kata_laluan" class="form-input-profile" placeholder="Re-enter password" required style="border-radius: var(--radius-sm);">
                        </div>

                        <?php if ($error): ?>
                            <div style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 12px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-circle-exclamation"></i>
                                <span><?= htmlspecialchars($error) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($configHint && !$envStatus['is_ready']): ?>
                            <div style="background:#fff7ed;color:#9a3412;border: 1px solid #fdba74; padding: 12px 14px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 20px;">
                                <?= htmlspecialchars($configHint) ?>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; border-radius: 999px; font-weight: 800; font-size: 15px; margin-bottom: 24px; display: flex; justify-content: center; align-items: center;">
                            Sign Up
                        </button>
                    </form>

                    <div style="text-align: center; font-size: 14px; color: var(--text-secondary); border-top: 1px solid var(--border); padding-top: 24px;">
                        Already have an account? 
                        <a href="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>" style="font-weight: 700; color: var(--accent-blue);">Log In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

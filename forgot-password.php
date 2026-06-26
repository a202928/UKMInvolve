<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboardForRole($_SESSION['role'] ?? 'pelajar'));
    exit();
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main style="padding: 80px 0; display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 160px); position: relative; background: url('ukm_background2.jpeg') no-repeat center center / cover;">
        <!-- Neutral dark overlay to make the form pop -->
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1;"></div>
        
        <div style="width: 100%; max-width: 450px; padding: 0 24px; position: relative; z-index: 10;">
            <div style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 40px 32px; box-shadow: var(--shadow-lg);">
                <div style="text-align: center; margin-bottom: 32px;">
                    <img src="UKM.png" alt="UKM Logo" style="width: 48px; height: 48px; margin: 0 auto 16px; object-fit: contain;">
                    <h2 style="font-size: 26px; font-weight: 800; color: var(--primary); margin-bottom: 8px;">Forgot Password</h2>
                    <p style="color: var(--text-secondary); font-size: 14px;">Enter your email to receive a password reset link.</p>
                </div>

                <form action="process_forgot_password.php" method="POST">
                    
                    <div class="form-group-profile" style="margin-bottom: 20px;">
                        <label for="emel" style="font-weight: 700; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; display: block;">Email Address</label>
                        <input type="email" name="emel" id="emel" class="form-input-profile" placeholder="yourname@ukm.edu.my" required style="border-radius: var(--radius-sm);">
                    </div>

                    <?php if ($error): ?>
                        <div style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 12px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 12px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($success) ?></span>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; border-radius: 999px; font-weight: 800; font-size: 15px; margin-bottom: 24px; display: flex; justify-content: center; align-items: center;">
                        Send Reset Link
                    </button>
                </form>

                <div style="text-align: center; font-size: 14px; color: var(--text-secondary); border-top: 1px solid var(--border); padding-top: 24px;">
                    Remembered your password? 
                    <a href="login.php" style="font-weight: 700; color: var(--accent-blue);">Log In</a>
                </div>
            </div>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

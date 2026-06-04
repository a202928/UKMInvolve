<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/lib/bootstrap.php';
    header('Location: ' . dashboardForRole($_SESSION['role'] ?? 'pelajar'));
    exit();
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$envStatus = Database::checkEnv();
$dbReady = $envStatus['is_ready'];
$configHint = Database::getSetupMessage();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Log Masuk | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- BLURRED BACKGROUND -->
<div class="bg-blur"></div>

<!-- LOGIN CONTENT -->
<div class="login-container">
    <div class="login-card">

        <div class="login-header">
            <img src="ukm.jpg" alt="Logo UKM" class="logo">
            <h1>UKMInvolve</h1>
            <p>
                Sistem Pengurusan Penglibatan Pelajar<br>
                <span>Universiti Kebangsaan Malaysia</span>
            </p>
        </div>

        <form action="process_login.php" method="POST">
            <div class="form-group">
                <label for="emel">Emel</label>
                <input type="email" name="emel" id="emel" placeholder="nama@siswa.ukm.edu.my" required>
            </div>

            <div class="form-group">
                <label for="kata_laluan">Kata Laluan</label>
                <input type="password" name="kata_laluan" id="kata_laluan" placeholder="Masukkan kata laluan" required>
            </div>

            <?php if ($error): ?>
                <div class="error-box"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($configHint && !$envStatus['is_ready']): ?>
                <div class="error-box" style="background:#fff7ed;color:#9a3412;border-color:#fdba74;">
                    <?= htmlspecialchars($configHint) ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-primary">Log Masuk</button>
        </form>

        <div class="login-footer">
            <a href="register.php">Daftar Akaun Baharu</a><br>
            <a href="#">Lupa Kata Laluan?</a>
        </div>

    </div>
</div>

</body>
</html>

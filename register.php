<?php
session_start();
require_once __DIR__ . '/config/database.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$envStatus = Database::checkEnv();
$configHint = Database::getSetupMessage();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akaun | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- BLURRED BACKGROUND -->
<div class="bg-blur"></div>

<!-- REGISTER CONTENT -->
<div class="login-container">
    <div class="login-card">
        <?php if ($success): ?>
            <!-- SUCCESS STATE -->
            <div class="login-header">
                <img src="ukm.jpg" alt="Logo UKM" class="logo">
                <h1>Pendaftaran Berjaya!</h1>
                <p>
                    Akaun anda telah berjaya didaftarkan.<br>
                    <span>Anda akan dibawa ke halaman log masuk.</span>
                </p>
            </div>

            <script>
                setTimeout(() => {
                    window.location.href = "login.php";
                }, 2000);
            </script>

        <?php else: ?>
            <!-- REGISTER FORM -->
            <div class="login-header">
                <img src="ukm.jpg" alt="Logo UKM" class="logo">
                <h1>Daftar Akaun Baharu</h1>
                <p>
                    UKMInvolve<br>
                    <span>Sistem Penglibatan Pelajar</span>
                </p>
            </div>

            <form action="process_register.php" method="POST">
                <div class="form-group">
                    <label for="nama">Nama Penuh</label>
                    <input type="text" name="nama" id="nama"
                           placeholder="Muhammad Ali bin Abdullah" required>
                </div>

                <div class="form-group">
                    <label for="emel">Emel UKM</label>
                    <input type="email" name="emel" id="emel"
                           placeholder="nama@siswa.ukm.edu.my" required>
                </div>

                <div class="form-group">
                    <label for="matrik">Nombor Matrik</label>
                    <input type="text" name="matrik" id="matrik"
                           placeholder="A123456" required>
                </div>

                <div class="form-group">
                    <label for="kata_laluan">Kata Laluan</label>
                    <input type="password" name="kata_laluan" id="kata_laluan"
                           placeholder="Minimum 6 aksara" required>
                </div>

                <div class="form-group">
                    <label for="sahkan_kata_laluan">Sahkan Kata Laluan</label>
                    <input type="password" name="sahkan_kata_laluan" id="sahkan_kata_laluan"
                           placeholder="Masukkan semula kata laluan" required>
                </div>

                <?php if ($error): ?>
                    <div class="error-box"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($configHint && !$envStatus['is_ready']): ?>
                    <div class="error-box" style="background:#fff7ed;color:#9a3412;border-color:#fdba74;">
                        <?= htmlspecialchars($configHint) ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-primary">Daftar Akaun</button>
            </form>

            <div class="login-footer">
                <a href="login.php">Sudah ada akaun? Log Masuk</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
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
                <label for="email">Emel</label>
                <input type="email" name="email" id="email" placeholder="nama@siswa.ukm.edu.my" required>
            </div>

            <div class="form-group">
                <label for="password">Kata Laluan</label>
                <input type="password" name="password" id="password" placeholder="Masukkan kata laluan" required>
            </div>

            <?php if ($error): ?>
                <div class="error-box"><?= $error ?></div>
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

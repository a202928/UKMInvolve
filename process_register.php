<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

$nama = trim($_POST['nama'] ?? '');
$emel = strtolower(trim($_POST['emel'] ?? ''));
$matrik = trim($_POST['matrik'] ?? '');
$kataLaluan = $_POST['kata_laluan'] ?? '';
$sahkanKataLaluan = $_POST['sahkan_kata_laluan'] ?? '';
$redirect = $_POST['redirect'] ?? '';

if ($nama === '' || $emel === '' || $matrik === '' || $kataLaluan === '') {
    $_SESSION['error'] = 'Sila isi semua medan';
    header('Location: register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit();
}

if (strlen($kataLaluan) < 6) {
    $_SESSION['error'] = 'Kata laluan mestilah sekurang-kurangnya 6 aksara';
    header('Location: register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit();
}

if ($kataLaluan !== $sahkanKataLaluan) {
    $_SESSION['error'] = 'Kata laluan tidak sepadan';
    header('Location: register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit();
}

if (!db()->isConfigured()) {
    $_SESSION['error'] = Database::getSetupMessage() ?: 'Pangkalan data belum dikonfigurasi.';
    header('Location: register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit();
}

// Clean up expired pending accounts first
users()->cleanExpiredPendingAccounts();

// Restrict student registration to @siswa.ukm.edu.my
if (substr($emel, -17) !== '@siswa.ukm.edu.my') {
    $_SESSION['error'] = 'Sila gunakan emel pelajar rasmi UKM (@siswa.ukm.edu.my)';
    header('Location: register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit();
}

$existingUser = users()->findByEmel($emel);
if ($existingUser) {
    if (($existingUser['status'] ?? '') === 'pending') {
        $_SESSION['pending_email'] = $emel;
    } else {
        $_SESSION['error'] = 'Emel ini sudah didaftarkan';
    }
    header('Location: register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit();
}

// Generate OTP (One-Time Password)
$otp = sprintf('%06d', mt_rand(100000, 999999));
$otpExpiry = date('c', time() + 15 * 60); // 15 minutes from now in ISO format

$result = users()->create([
    'nama' => $nama,
    'emel' => $emel,
    'matrik' => $matrik,
    'kata_laluan' => $kataLaluan,
    'peranan' => 'pelajar',
    'status' => 'pending',
    'verification_code' => $otp,
    'verification_code_expires_at' => $otpExpiry,
    'otp_attempts' => 1, // Initial send counts as 1
    'last_otp_sent_at' => date('c')
]);

if (!$result['ok']) {
    $_SESSION['error'] = 'Pendaftaran gagal: ' . mapDatabaseError($result['error'] ?? 'Ralat tidak diketahui');
    header('Location: register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit();
}

// Send OTP email via direct SMTP Mailer
$mailRes = MailerService::sendOTP($emel, $nama, $otp);
if (!$mailRes['ok']) {
    $_SESSION['warning_message'] = "Pendaftaran berjaya tetapi gagal menghantar emel: " . ($mailRes['error'] ?? 'Ralat SMTP');
}

$_SESSION['success_message'] = "OTP telah dihantar ke emel anda.";
header('Location: verify-email.php?emel=' . urlencode($emel) . ($redirect ? '&redirect=' . urlencode($redirect) : ''));
exit();

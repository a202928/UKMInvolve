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
    $_SESSION['error'] = 'Pengguna tidak ditemui.';
    header('Location: register.php');
    exit();
}

if (($user['status'] ?? '') === 'aktif') {
    loginUser($user);
    header('Location: ' . dashboardForRole($user['peranan']));
    exit();
}

// 1. Check Max Resend Attempts (max 5)
$attempts = (int)($user['otp_attempts'] ?? 0);
if ($attempts >= 5) {
    $_SESSION['error'] = 'Had limit resend dicapai (Maksimum 5 attempts). Sila hubungi pentadbir.';
    header('Location: verify-email.php?emel=' . urlencode($emel) . ($redirect ? '&redirect=' . urlencode($redirect) : ''));
    exit();
}

// 2. Check Cooldown Throttle (60 seconds)
$lastSent = $user['last_otp_sent_at'] ?? '';
if (!empty($lastSent)) {
    $elapsed = time() - strtotime($lastSent);
    if ($elapsed < 60) {
        $remaining = 60 - $elapsed;
        $_SESSION['error'] = "Sila tunggu {$remaining} saat sebelum memohon OTP baharu.";
        header('Location: verify-email.php?emel=' . urlencode($emel) . ($redirect ? '&redirect=' . urlencode($redirect) : ''));
        exit();
    }
}

// 3. Generate new OTP & update Database
$newOtp = sprintf('%06d', mt_rand(100000, 999999));
$newExpiry = date('c', time() + 15 * 60);
$newAttempts = $attempts + 1;

$updateRes = users()->updateUser($user['id'], [
    'verification_code' => $newOtp,
    'verification_code_expires_at' => $newExpiry,
    'otp_attempts' => $newAttempts,
    'last_otp_sent_at' => date('c')
]);

if ($updateRes['ok']) {
    // Send OTP email
    $mailRes = MailerService::sendOTP($emel, $user['nama'], $newOtp);
    
    if ($mailRes['ok']) {
        $_SESSION['success_message'] = "OTP baharu telah berjaya dihantar ke emel anda.";
    } else {
        $_SESSION['error'] = "Gagal menghantar emel OTP: " . ($mailRes['error'] ?? 'Ralat SMTP');
    }
} else {
    $_SESSION['error'] = "Gagal menjana OTP baharu di pangkalan data.";
}

header('Location: verify-email.php?emel=' . urlencode($emel) . ($redirect ? '&redirect=' . urlencode($redirect) : ''));
exit();

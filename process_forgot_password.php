<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot-password.php');
    exit();
}

$emel = strtolower(trim($_POST['emel'] ?? ''));

if (empty($emel)) {
    $_SESSION['error'] = 'Please enter your email address.';
    header('Location: forgot-password.php');
    exit();
}

$user = users()->findByEmel($emel);

if (!$user) {
    // Return success anyway to prevent email enumeration
    $_SESSION['success_message'] = 'If your email is registered, you will receive a password reset link shortly.';
    header('Location: forgot-password.php');
    exit();
}

// Generate token
$token = bin2hex(random_bytes(32));
$expiresAt = date('c', time() + 3600); // 1 hour from now

// Update user with token
$updateRes = users()->updateUser($user['id'], [
    'reset_token' => $token,
    'reset_token_expires_at' => $expiresAt
]);

if (!$updateRes['ok']) {
    $_SESSION['error'] = 'An error occurred while generating the reset link. Please try again later.';
    header('Location: forgot-password.php');
    exit();
}

// Create reset link
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$baseDir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$resetLink = $protocol . $domainName . $baseDir . '/reset-password.php?token=' . urlencode($token) . '&emel=' . urlencode($emel);

// Send email
$mailRes = MailerService::sendPasswordResetLink($emel, $user['nama'], $resetLink);

if ($mailRes['ok']) {
    $_SESSION['success_message'] = 'If your email is registered, you will receive a password reset link shortly.';
} else {
    $errDetail = $mailRes['error'] ?? 'Unknown error';
    $_SESSION['error'] = "Failed to send the reset email. Error: $errDetail";
}

header('Location: forgot-password.php');
exit();

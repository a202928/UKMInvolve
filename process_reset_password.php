<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$token = $_POST['token'] ?? '';
$emel = strtolower(trim($_POST['emel'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!$token || !$emel || !$password || !$confirmPassword) {
    $_SESSION['error'] = 'Missing required fields.';
    header("Location: reset-password.php?token=" . urlencode($token) . "&emel=" . urlencode($emel));
    exit();
}

if ($password !== $confirmPassword) {
    $_SESSION['error'] = 'Passwords do not match.';
    header("Location: reset-password.php?token=" . urlencode($token) . "&emel=" . urlencode($emel));
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters long.';
    header("Location: reset-password.php?token=" . urlencode($token) . "&emel=" . urlencode($emel));
    exit();
}

$user = users()->findByEmel($emel);
if (!$user || $user['reset_token'] !== $token) {
    $_SESSION['error'] = 'Invalid or expired password reset link.';
    header('Location: forgot-password.php');
    exit();
}

if (empty($user['reset_token_expires_at']) || strtotime($user['reset_token_expires_at']) < time()) {
    $_SESSION['error'] = 'This password reset link has expired. Please request a new one.';
    header('Location: forgot-password.php');
    exit();
}

// Update password and clear token
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$updateRes = users()->updateUser($user['id'], [
    'password_hash' => $hashedPassword,
    'reset_token' => null,
    'reset_token_expires_at' => null
]);

if ($updateRes['ok']) {
    $_SESSION['success_message'] = 'Your password has been successfully reset. You can now log in.';
    header('Location: login.php');
    exit();
} else {
    $_SESSION['error'] = 'Failed to reset password. Please try again.';
    header("Location: reset-password.php?token=" . urlencode($token) . "&emel=" . urlencode($emel));
    exit();
}

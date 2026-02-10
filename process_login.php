<?php
session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Sila isi semua medan";
    header("Location: login.php");
    exit();
}

// Tentukan peranan berdasarkan emel (demo)
if (str_contains($email, 'pentadbir')) {
    $_SESSION['role'] = 'pentadbir';
} elseif (str_contains($email, 'penganjur')) {
    $_SESSION['role'] = 'penganjur';
} else {
    $_SESSION['role'] = 'pelajar';
}

$_SESSION['email'] = $email;

// Redirect ikut peranan
header("Location: dashboard.php");
exit();

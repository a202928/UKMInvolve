<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

$emel = strtolower(trim($_POST['emel'] ?? ''));
$kataLaluan = $_POST['kata_laluan'] ?? '';

if ($emel === '' || $kataLaluan === '') {
    $_SESSION['error'] = 'Sila isi semua medan';
    header('Location: login.php');
    exit();
}

if (!db()->isConfigured()) {
    $_SESSION['error'] = Database::getSetupMessage() ?: 'Pangkalan data belum dikonfigurasi.';
    header('Location: login.php');
    exit();
}

$user = users()->verifyLogin($emel, $kataLaluan);

if (!$user) {
    $_SESSION['error'] = 'Emel atau kata laluan tidak sah';
    header('Location: login.php');
    exit();
}

if (($user['status'] ?? '') !== 'aktif') {
    $_SESSION['error'] = 'Akaun anda digantung. Sila hubungi pentadbir.';
    header('Location: login.php');
    exit();
}

loginUser($user);

header('Location: ' . dashboardForRole($user['peranan']));
exit();

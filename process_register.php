<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

$nama = trim($_POST['nama'] ?? '');
$emel = strtolower(trim($_POST['emel'] ?? ''));
$matrik = trim($_POST['matrik'] ?? '');
$kataLaluan = $_POST['kata_laluan'] ?? '';
$sahkanKataLaluan = $_POST['sahkan_kata_laluan'] ?? '';

if ($nama === '' || $emel === '' || $matrik === '' || $kataLaluan === '') {
    $_SESSION['error'] = 'Sila isi semua medan';
    header('Location: register.php');
    exit();
}

if (strlen($kataLaluan) < 6) {
    $_SESSION['error'] = 'Kata laluan mestilah sekurang-kurangnya 6 aksara';
    header('Location: register.php');
    exit();
}

if ($kataLaluan !== $sahkanKataLaluan) {
    $_SESSION['error'] = 'Kata laluan tidak sepadan';
    header('Location: register.php');
    exit();
}

if (!db()->isConfigured()) {
    $_SESSION['error'] = Database::getSetupMessage() ?: 'Pangkalan data belum dikonfigurasi.';
    header('Location: register.php');
    exit();
}

if (users()->findByEmel($emel)) {
    $_SESSION['error'] = 'Emel ini sudah didaftarkan';
    header('Location: register.php');
    exit();
}

$result = users()->create([
    'nama' => $nama,
    'emel' => $emel,
    'matrik' => $matrik,
    'kata_laluan' => $kataLaluan,
    'peranan' => 'pelajar',
]);

if (!$result['ok']) {
    $_SESSION['error'] = 'Pendaftaran gagal: ' . mapDatabaseError($result['error'] ?? 'Ralat tidak diketahui');
    header('Location: register.php');
    exit();
}

$user = users()->verifyLogin($emel, $kataLaluan);
if ($user && ($user['status'] ?? '') === 'aktif') {
    loginUser($user);
    header('Location: ' . dashboardForRole($user['peranan']));
    exit();
}

$_SESSION['success'] = 'Akaun berjaya didaftarkan. Sila log masuk.';
header('Location: login.php');
exit();

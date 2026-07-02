<?php
require_once __DIR__ . '/lib/bootstrap.php';

$email = 'a23456@siswa.ukm.edu.my';
$userRes = db()->select('users', '?emel=eq.' . $email);
if (!$userRes['ok'] || empty($userRes['data'])) {
    die("User not found");
}

$user = $userRes['data'][0];
$id = $user['id'];

$prog = ProgressionService::getStudentProgression($id, $user);
echo "User ID: $id\n";
echo "Level Data:\n";
print_r($prog);

$att = db()->select('kehadiran', '?select=id&pelajar_id=eq.' . rawurlencode($id) . '&status=eq.Hadir');
echo "Attendance count: " . count($att['data'] ?? []) . "\n";

$levelsRes = db()->select('level_thresholds', '?order=level.asc');
echo "Levels in DB:\n";
print_r($levelsRes['data'] ?? []);

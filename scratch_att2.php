<?php
require_once __DIR__ . '/lib/bootstrap.php';
$db = db();
$programId = 2; // Usually Gerobok Rezeki is ID 2, let's find it.
$progs = $db->select('program', "?select=id,nama&nama=ilike.*Gerobok*");
if ($progs['ok'] && !empty($progs['data'])) {
    $programId = $progs['data'][0]['id'];
}

$regsRes = db()->select('pendaftaran', '?select=id,nama,no_matrik,fakulti,emel,status,pelajar_id,tarikh_daftar,jenis_pendaftaran&program_id=eq.' . $programId);
$attRes = db()->select('kehadiran', '?select=pelajar_id,status,waktu_masuk&program_id=eq.' . $programId);

$attendanceMap = [];
if ($attRes['ok'] && is_array($attRes['data'])) {
    foreach ($attRes['data'] as $att) {
        $attendanceMap[$att['pelajar_id']] = $att;
    }
}

print_r($attendanceMap);

if ($regsRes['ok'] && is_array($regsRes['data'])) {
    foreach ($regsRes['data'] as $reg) {
        $pId = $reg['pelajar_id'];
        $attStatus = $attendanceMap[$pId]['status'] ?? 'Tidak Hadir';
        echo "PID: $pId, Status: $attStatus\n";
    }
}

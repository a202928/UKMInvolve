<?php
require_once __DIR__ . '/lib/bootstrap.php';
$db = db();
$programId = 2; // Usually Gerobok Rezeki is ID 2, let's find it.
$progs = $db->select('program', "?select=id,nama&nama=ilike.*Gerobok*");
if ($progs['ok'] && !empty($progs['data'])) {
    $programId = $progs['data'][0]['id'];
    echo "Found Program ID: $programId\n";
}

$regs = $db->select('pendaftaran', '?select=id,pelajar_id,nama&program_id=eq.' . $programId);
echo "Regs:\n";
print_r($regs['data']);

$atts = $db->select('kehadiran', '?select=id,pelajar_id,status&program_id=eq.' . $programId);
echo "Atts:\n";
print_r($atts['data']);

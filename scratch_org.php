<?php
require_once __DIR__ . '/bootstrap.php';
$db = db();
// Find organizer PERTAMA
$users = $db->select('users', '?select=id,nama&role=eq.penganjur');
$pertamaId = null;
foreach ($users['data'] as $u) {
    if (stripos($u['nama'], 'PERTAMA') !== false) {
        $pertamaId = $u['id'];
        break;
    }
}
if (!$pertamaId) die("PERTAMA not found");
echo "PERTAMA ID: $pertamaId\n";

$progs = $db->select('program', "?select=id,nama&penganjur_id=eq.$pertamaId");
foreach ($progs['data'] as $p) {
    echo "Program: {$p['nama']} (ID: {$p['id']})\n";
    $regs = $db->select('pendaftaran', "?select=id&program_id=eq.{$p['id']}");
    echo "  Regs count: " . count($regs['data'] ?? []) . "\n";
    $att = $db->select('kehadiran', "?select=id&program_id=eq.{$p['id']}");
    echo "  Att count: " . count($att['data'] ?? []) . "\n";
}

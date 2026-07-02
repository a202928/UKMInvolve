<?php
require_once __DIR__ . '/lib/bootstrap.php';
$db = db();

$progs = $db->select('program', "?nama=ilike.*Banjir Dummy*");
if ($progs['ok'] && !empty($progs['data'])) {
    foreach ($progs['data'] as $p) {
        $pid = $p['id'];
        $res = $db->delete('maklum_balas', "?program_id=eq.$pid");
        echo "Deleted feedback for program $pid: " . json_encode($res) . "\n";
    }
} else {
    echo "No dummy programs found.\n";
}

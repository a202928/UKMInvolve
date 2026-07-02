<?php
require_once __DIR__ . '/lib/bootstrap.php';
$db = db();
$res = $db->delete('maklum_balas', '?program_id=eq.1079');
echo "Deleted feedback for program 1079: " . json_encode($res) . "\n";

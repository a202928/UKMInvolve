<?php
require_once __DIR__ . '/lib/bootstrap.php';
$db = db();
$res = $db->select('maklum_balas', "?program_id=eq.1079");
print_r($res['data']);

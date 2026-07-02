<?php
require_once __DIR__ . '/lib/bootstrap.php';
$db = db();

$progs = $db->select('program', "?select=id,nama&order=id.desc&limit=10");
if ($progs['ok']) {
    print_r($progs['data']);
}

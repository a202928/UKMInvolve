<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
$studentId = '75d1d6a0-e555-4428-a320-f5670afb4ffb'; // Ahmad Faiz's ID based on previous context. Wait, let me query the db for the user.
$user = db()->select('users', '?limit=1&peranan=eq.pelajar');
$studentId = $user['data'][0]['id'];
$regRes = db()->select('pendaftaran', '?select=*,program(*,kategori(*))&pelajar_id=eq.' . rawurlencode($studentId));
print_r($regRes);

<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
$studentId = 'c45d68a5-87ee-49e6-8920-65609be82698';
$regRes = db()->select('pendaftaran', '?select=*,program(*,kategori(*))&pelajar_id=eq.' . rawurlencode($studentId));
echo json_encode($regRes, JSON_PRETTY_PRINT);

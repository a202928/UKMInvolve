<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
$studentId = 'c45d68a5-87ee-49e6-8920-65609be82698';
$regRes = db()->select('pendaftaran', '?select=*,program(*,kategori(*))&pelajar_id=eq.' . rawurlencode($studentId));
$regs = $regRes['ok'] ? $regRes['data'] : [];

foreach ($regs as $reg) {
    if (isset($reg['program'])) {
        $imgRaw = !empty($reg['program']['poster_url']) ? $reg['program']['poster_url'] : (!empty($reg['program']['gambar']) ? $reg['program']['gambar'] : 'program1.jpg');
        $imgPath = getImagePath($imgRaw);
        echo "Program: " . $reg['program']['nama'] . "\n";
        echo "Raw Image: " . $imgRaw . "\n";
        echo "Path: " . $imgPath . "\n\n";
    }
}

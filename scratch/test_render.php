<?php
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
require_once dirname(__DIR__) . '/lib/bootstrap.php';
$studentId = 'c45d68a5-87ee-49e6-8920-65609be82698';
$regRes = db()->select('pendaftaran', '?select=*,program(*,kategori(*))&pelajar_id=eq.' . rawurlencode($studentId));
$regs = $regRes['ok'] ? $regRes['data'] : [];

$myRegisteredEvents = [];
foreach ($regs as $reg) {
    if (isset($reg['program'])) {
        $myRegisteredEvents[] = [
            'title' => $reg['program']['nama'] ?? '',
            'image' => !empty($reg['program']['poster_url']) ? $reg['program']['poster_url'] : (!empty($reg['program']['gambar']) ? $reg['program']['gambar'] : 'program1.jpg'),
        ];
    }
}

foreach ($myRegisteredEvents as $myEv) {
    $imgSrc = htmlspecialchars(getImagePath($myEv['image']));
    echo "Title: {$myEv['title']}\n";
    echo "Image path from getImagePath: {$imgSrc}\n\n";
}

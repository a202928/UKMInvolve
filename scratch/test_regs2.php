<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
$regRes = db()->select('pendaftaran', '?select=*,program(nama,poster_url,gambar)&limit=5');
print_r($regRes);

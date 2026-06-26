<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';

$res = db()->select('program_discussions', '?select=*,users(nama,role,avatar_url)&limit=1');
print_r($res);

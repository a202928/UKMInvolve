<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';

$res = db()->select('program', '?limit=1');
print_r($res);

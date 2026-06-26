<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';

$res = db()->select('program', '?select=deadline_date&limit=1');
if ($res['ok'] || isset($res['data'])) {
    echo "Deadline column exists.\n";
} else {
    echo "Deadline column MISSING.\n";
}

$res2 = db()->select('program_announcements', '?select=id&limit=1');
if ($res2['ok'] || isset($res2['data'])) {
    echo "program_announcements table exists.\n";
} else {
    echo "program_announcements table MISSING.\n";
}

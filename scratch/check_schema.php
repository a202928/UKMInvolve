<?php
require_once 'c:/xampp/htdocs/UKMInvolve_2/lib/bootstrap.php';

$sql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users'";
$res = db()->query($sql);
if ($res['ok']) {
    echo "USERS table columns:\n";
    foreach ($res['data'] as $col) {
        echo "- " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
    }
} else {
    echo "Error: " . ($res['error'] ?? 'Unknown');
}

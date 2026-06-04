<?php
/**
 * One-time script: creates demo users with PHP password_hash (matches login).
 * Run from browser: http://localhost/UKMInvolve_2/supabase/seed_users.php
 * Delete this file after use on production.
 */

require_once dirname(__DIR__) . '/lib/bootstrap.php';

if (!db()->isConfigured()) {
    die('Configure .env first.');
}

$demoUsers = [
    ['nama' => 'Ahmad Faiz', 'emel' => 'faiz@ukm.edu.my', 'password' => 'password123', 'peranan' => 'pelajar', 'matrik' => 'A123456', 'fakulti' => 'FSKTM', 'mata' => 760],
    ['nama' => 'Dr. Siti Aminah', 'emel' => 'siti.aminah@ukm.edu.my', 'password' => 'password123', 'peranan' => 'penganjur', 'organisasi' => 'Pusat Pembangunan Pelajar'],
    ['nama' => 'Nurul Hidayah', 'emel' => 'pentadbir@ukm.edu.my', 'password' => 'password123', 'peranan' => 'pentadbir'],
    ['nama' => 'Muhammad Ali', 'emel' => 'ali@ukm.edu.my', 'password' => 'password123', 'peranan' => 'pelajar', 'matrik' => 'A123457', 'fakulti' => 'FEP', 'mata' => 420],
];

echo '<pre>';
foreach ($demoUsers as $user) {
    $existing = users()->findByEmel($user['emel']);
    if ($existing) {
        echo "Skip (exists): {$user['emel']}\n";
        continue;
    }

    $result = users()->create([
        'nama' => $user['nama'],
        'emel' => $user['emel'],
        'kata_laluan' => $user['password'],
        'peranan' => $user['peranan'],
        'matrik' => $user['matrik'] ?? null,
        'fakulti' => $user['fakulti'] ?? null,
        'organisasi' => $user['organisasi'] ?? null,
    ]);
    if ($result['ok']) {
        echo "Created: {$user['emel']}\n";
    } else {
        echo "Error {$user['emel']}: {$result['error']}\n";
    }
}
echo "Done. Demo password: password123\n";
echo '</pre>';

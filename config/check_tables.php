<?php
/**
 * Verify Supabase tables exist.
 * Open: http://localhost/UKMInvolve_2/config/check_tables.php
 */
require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/lib/SupabaseClient.php';

header('Content-Type: application/json');

if (!Database::isConfigured()) {
    echo json_encode(['ok' => false, 'error' => Database::getSetupMessage()], JSON_PRETTY_PRINT);
    exit;
}

$db = new SupabaseClient();
$tables = ['users', 'kategori', 'program', 'pendaftaran', 'student_interests', 'kehadiran', 'level_thresholds', 'rekod_mata', 'lencana', 'lencana_pelajar'];
$results = [];

foreach ($tables as $table) {
    $select = ($table === 'level_thresholds') ? 'level' : 'id';
    $r = $db->select($table, '?select=' . $select . '&limit=1');
    $results[$table] = [
        'exists' => $r['ok'],
        'error' => $r['ok'] ? null : ($r['error'] ?? 'unknown'),
    ];
}

$allOk = !in_array(false, array_column($results, 'exists'), true);

echo json_encode([
    'ok' => $allOk,
    'tables' => $results,
    'fix' => $allOk ? null : 'Run supabase/install_all.sql in Supabase SQL Editor, then reload this page.',
], JSON_PRETTY_PRINT);

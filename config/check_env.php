<?php
/**
 * Dev helper: verify .env is loaded.
 * Open: http://localhost/UKMInvolve_2/config/check_env.php
 * Remove on production.
 */
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');
$check = Database::checkEnv();
echo json_encode([
    'env_loaded' => $check['env_file_exists'],
    'env_path' => $check['env_file_path'],
    'supabase_url' => $check['supabase_url_set'],
    'supabase_anon_key' => $check['supabase_anon_key_set'],
    'ready' => $check['is_ready'],
    'key_type' => $check['using_key'],
    'postgres_optional' => Database::postgresAvailable(),
    'note' => 'Direct PostgreSQL needs DATABASE_URL + pdo_pgsql. This app uses REST API only.',
], JSON_PRETTY_PRINT);

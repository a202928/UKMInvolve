<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/SupabaseClient.php';
require_once __DIR__ . '/UserRepository.php';

// Optional helpers used by other pages
if (is_file(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}
if (is_file(__DIR__ . '/ProgramRepository.php')) {
    require_once __DIR__ . '/ProgramRepository.php';
}
if (is_file(__DIR__ . '/CategoryRepository.php')) {
    require_once __DIR__ . '/CategoryRepository.php';
}
if (is_file(__DIR__ . '/RegistrationRepository.php')) {
    require_once __DIR__ . '/RegistrationRepository.php';
}

function db(): SupabaseClient
{
    static $client = null;
    if ($client === null) {
        $client = new SupabaseClient();
    }
    return $client;
}

function users(): UserRepository
{
    static $repo = null;
    if ($repo === null) {
        $repo = new UserRepository(db());
    }
    return $repo;
}

function programs(): ProgramRepository
{
    static $repo = null;
    if ($repo === null) {
        $repo = new ProgramRepository(db());
    }
    return $repo;
}

function categories(): CategoryRepository
{
    static $repo = null;
    if ($repo === null) {
        $repo = new CategoryRepository(db());
    }
    return $repo;
}

function registrations(): RegistrationRepository
{
    static $repo = null;
    if ($repo === null) {
        $repo = new RegistrationRepository(db());
    }
    return $repo;
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        header('Location: ' . dashboardForRole($role));
        exit();
    }
}

/** Redirect URL from peranan (DB) / role (session) */
function dashboardForRole(string $role): string
{
    return match ($role) {
        'pentadbir' => 'dashboard-pentadbir.php',
        'penganjur' => 'dashboard_penganjur.php',
        default => 'dashboard_pelajar.php',
    };
}

/** Set session after login — role comes from DB column peranan */
function loginUser(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['emel']    = $user['emel'];
    $_SESSION['role']    = $user['peranan'];
    $_SESSION['mata']    = (int) ($user['mata'] ?? 0);
}

function mapDatabaseError(string $error): string
{
    if (str_contains($error, 'Could not find the table') || str_contains($error, 'schema cache')) {
        return 'Jadual users belum wujud. Jalankan supabase/install_all.sql dalam Supabase SQL Editor.';
    }
    if (str_contains($error, 'permission denied') || str_contains($error, '42501')) {
        return 'Akses ditolak. Jalankan supabase/install_all.sql (GRANT + policies).';
    }
    if (str_contains($error, 'get_user_by_emel')) {
        return 'Jalankan supabase/fix_login.sql dalam Supabase SQL Editor.';
    }
    return $error;
}
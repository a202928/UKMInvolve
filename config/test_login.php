<?php
/**
 * Dev helper – test if a user can be loaded for login.
 * Usage: test_login.php?emel=you@example.com
 * Remove on production.
 */
require_once dirname(__DIR__) . '/lib/bootstrap.php';

header('Content-Type: application/json');

$emel = strtolower(trim($_GET['emel'] ?? ''));
if ($emel === '') {
    echo json_encode(['ok' => false, 'error' => 'Add ?emel=your@email.com'], JSON_PRETTY_PRINT);
    exit;
}

$user = users()->findByEmel($emel);

echo json_encode([
    'ok' => $user !== null,
    'found' => $user !== null,
    'has_password_hash' => !empty($user['password_hash']),
    'hash_length' => isset($user['password_hash']) ? strlen((string) $user['password_hash']) : 0,
    'peranan' => $user['peranan'] ?? null,
    'tip' => $user ? null : 'User not found. Run supabase/fix_login.sql if you just signed up.',
], JSON_PRETTY_PRINT);

<?php
require_once __DIR__ . '/lib/bootstrap.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$programId = (int)($input['program_id'] ?? 0);

if ($programId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

if (!db()->isConfigured()) {
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit();
}

// 1. Fetch current shares count
$row = programs()->findById($programId);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit();
}

$currentShares = (int)($row['shares_count'] ?? 0);
$newShares = $currentShares + 1;

// Update total shares in public.program table
$updateRes = programs()->update($programId, ['shares_count' => $newShares]);
if (!$updateRes['ok']) {
    echo json_encode(['success' => false, 'message' => 'Failed to increment shares count in database']);
    exit();
}

// 2. Track promoter stats if user is logged in
$userId = $_SESSION['user_id'] ?? '';
if (!empty($userId)) {
    // Check if promoter entry already exists
    $shareRes = db()->select('event_shares', '?user_id=eq.' . rawurlencode($userId) . '&event_id=eq.' . $programId);
    if ($shareRes['ok'] && !empty($shareRes['data'])) {
        $shareRow = $shareRes['data'][0];
        $shareId = $shareRow['id'];
        $userShares = (int)($shareRow['share_count'] ?? 0) + 1;
        db()->update('event_shares', '?id=eq.' . $shareId, ['share_count' => $userShares]);
    } else {
        db()->insert('event_shares', [
            'user_id' => $userId,
            'event_id' => $programId,
            'share_count' => 1
        ]);
    }
}

echo json_encode(['success' => true, 'shares_count' => $newShares]);

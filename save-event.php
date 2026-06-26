<?php
require_once __DIR__ . '/lib/bootstrap.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$programId = (int)($input['program_id'] ?? 0);
$action = $input['action'] ?? '';

if ($programId <= 0 || !in_array($action, ['save', 'unsave'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$studentId = $_SESSION['user_id'];

$studentInfo = users()->findById($studentId);
if ($studentInfo) {
    $requiredFields = ['nama', 'fakulti', 'kolej', 'tahun_pengajian', 'no_telefon', 'avatar_url'];
    foreach ($requiredFields as $f) {
        if (empty($studentInfo[$f])) {
            echo json_encode(['success' => false, 'message' => 'Please complete your profile first.', 'redirect' => 'profile.php?incomplete=1']);
            exit();
        }
    }
}

$success = false;

if ($action === 'save') {
    $success = savedEvents()->save($studentId, $programId);
} else {
    $success = savedEvents()->unsave($studentId, $programId);
}

echo json_encode(['success' => $success]);

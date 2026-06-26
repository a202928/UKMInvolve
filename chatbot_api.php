<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/SmartAssistantService.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'guest';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true) ?: [];

$action = $data['action'] ?? '';
$context = $data['context'] ?? [];

$assistant = new SmartAssistantService($userId, $role, $context);

if ($action === 'init') {
    echo json_encode(['status' => 'success', 'data' => $assistant->getGreetingAndQuickActions()]);
    exit();
} elseif ($action === 'message') {
    $message = $data['message'] ?? '';
    if (empty(trim($message))) {
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit();
    }
    
    $responseHtml = $assistant->handleMessage($message);
    
    // Simulate slight delay for realistic typing feel
    usleep(500000); // 0.5s
    
    echo json_encode(['status' => 'success', 'data' => ['html' => $responseHtml]]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
exit();

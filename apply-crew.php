<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php');
    exit();
}

$programId = (int)($_POST['program_id'] ?? 0);
$positionId = (int)($_POST['position_id'] ?? 0);
$alasan = trim($_POST['alasan'] ?? '');
$studentId = $_SESSION['user_id'] ?? '';

if ($programId <= 0 || $positionId <= 0 || empty($alasan) || empty($studentId)) {
    $_SESSION['error_message'] = "Missing required application fields.";
    header('Location: event-details.php?id=' . $programId);
    exit();
}

// Check Student Level Progression
$prog = ProgressionService::getStudentProgression($studentId);
$studentLevel = $prog['level'];

if ($studentLevel < 2) {
    $_SESSION['error_message'] = "Reach Level 2 to unlock Crew Applications.";
    header('Location: event-details.php?id=' . $programId);
    exit();
}

// Fetch Position Details to see if it is an MT position
$posRes = db()->select('program_crew_positions', '?id=eq.' . $positionId);
if ($posRes['ok'] && !empty($posRes['data'][0])) {
    $posName = $posRes['data'][0]['nama_jawatan'] ?? '';
    if (ProgressionService::isMTPosition($posName) && $studentLevel < 3) {
        $_SESSION['error_message'] = "Reach Level 3 to apply for Majlis Tertinggi (MT) positions.";
        header('Location: event-details.php?id=' . $programId);
        exit();
    }
}

$result = programs()->applyForCrewPosition($programId, $studentId, $positionId, $alasan);

if ($result['ok']) {
    $_SESSION['success_message'] = "Your crew application has been submitted successfully!";
} else {
    $_SESSION['error_message'] = $result['error'] ?? "Failed to submit application. Please try again.";
}

header('Location: event-details.php?id=' . $programId);
exit();

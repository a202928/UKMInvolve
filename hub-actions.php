<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

// Only logged in users
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php');
    exit();
}

$action = $_POST['action'] ?? '';
$programId = (int)($_POST['program_id'] ?? 0);
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'pelajar';

if ($programId <= 0) {
    $_SESSION['error_message'] = "Invalid program ID.";
    header('Location: dashboard_' . $role . '.php');
    exit();
}

$db = db();
if (!$db->isConfigured()) {
    $_SESSION['error_message'] = "Database not configured.";
    header('Location: urus-program-hub.php?id=' . $programId);
    exit();
}

// Check if user has access to this program's hub
$programRes = $db->select('program', '?id=eq.' . $programId);
if (!$programRes['ok'] || empty($programRes['data'])) {
    $_SESSION['error_message'] = "Program not found.";
    header('Location: dashboard_' . $role . '.php');
    exit();
}
$program = $programRes['data'][0];

$isOrganizer = ($role === 'penganjur' && $program['penganjur_id'] === $userId);

// Action routing
switch ($action) {
    case 'add_announcement':
        if (!$isOrganizer) {
            $_SESSION['error_message'] = "Unauthorized.";
            break;
        }
        $message = trim($_POST['message'] ?? '');
        $targetAudience = $_POST['target_audience'] ?? 'All';
        if (empty($message)) {
            $_SESSION['error_message'] = "Message cannot be empty.";
        } else {
            $res = $db->insert('program_announcements', [
                'program_id' => $programId,
                'organizer_id' => $userId,
                'message' => $message,
                'target_audience' => $targetAudience
            ]);
            if ($res['ok']) {
                $_SESSION['success_message'] = "Announcement sent.";
                
                // Also create notification
                $title = "New Announcement: " . $program['nama'];
                
                if ($targetAudience === 'All' || $targetAudience === 'Participant') {
                    $regs = $db->select('pendaftaran', '?select=pelajar_id&program_id=eq.' . $programId . '&status=eq.Diluluskan');
                    if ($regs['ok']) {
                        foreach ($regs['data'] as $r) {
                            $db->insert('notifications', [
                                'user_id' => $r['pelajar_id'],
                                'title' => $title,
                                'message' => substr($message, 0, 100) . '...',
                                'link' => 'program-hub.php?id=' . $programId . '&tab=announcements'
                            ]);
                        }
                    }
                }
                
                if ($targetAudience === 'All' || $targetAudience === 'Crew') {
                    $crews = $db->select('crew_applications', '?select=pelajar_id&program_id=eq.' . $programId . '&status=eq.Accepted');
                    if ($crews['ok']) {
                        foreach ($crews['data'] as $c) {
                            $db->insert('notifications', [
                                'user_id' => $c['pelajar_id'],
                                'title' => $title,
                                'message' => substr($message, 0, 100) . '...',
                                'link' => 'program-hub.php?id=' . $programId . '&tab=announcements'
                            ]);
                        }
                    }
                }
            } else {
                $_SESSION['error_message'] = "Failed to send announcement: " . ($res['error'] ?? '');
            }
        }
        header('Location: urus-program-hub.php?id=' . $programId . '&tab=announcements');
        exit();

    case 'delete_announcement':
        if (!$isOrganizer) {
            $_SESSION['error_message'] = "Unauthorized.";
            break;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $res = $db->delete('program_announcements', '?id=eq.' . $id . '&program_id=eq.' . $programId);
            if ($res['ok']) $_SESSION['success_message'] = "Announcement deleted.";
            else $_SESSION['error_message'] = "Failed to delete announcement.";
        }
        header('Location: urus-program-hub.php?id=' . $programId . '&tab=announcements');
        exit();

    case 'add_file':
        if (!$isOrganizer) {
            $_SESSION['error_message'] = "Unauthorized.";
            break;
        }
        $title = trim($_POST['title'] ?? '');
        $fileUrl = trim($_POST['file_url'] ?? '');
        $targetAudience = $_POST['target_audience'] ?? 'All';
        
        if (empty($title) || empty($fileUrl)) {
            $_SESSION['error_message'] = "Title and URL are required.";
        } else {
            $res = $db->insert('program_files', [
                'program_id' => $programId,
                'organizer_id' => $userId,
                'title' => $title,
                'file_url' => $fileUrl,
                'target_audience' => $targetAudience
            ]);
            if ($res['ok']) $_SESSION['success_message'] = "File shared successfully.";
            else $_SESSION['error_message'] = "Failed to share file.";
        }
        header('Location: urus-program-hub.php?id=' . $programId . '&tab=files');
        exit();

    case 'delete_file':
        if (!$isOrganizer) {
            $_SESSION['error_message'] = "Unauthorized.";
            break;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $res = $db->delete('program_files', '?id=eq.' . $id . '&program_id=eq.' . $programId);
            if ($res['ok']) $_SESSION['success_message'] = "File deleted.";
            else $_SESSION['error_message'] = "Failed to delete file.";
        }
        header('Location: urus-program-hub.php?id=' . $programId . '&tab=files');
        exit();

    case 'add_discussion':
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            $_SESSION['error_message'] = "Message cannot be empty.";
        } else {
            // Any logged in student or organizer can post in the discussion board
            // No need to check for participant status if we want to allow pre-registration Q&A
            
            $res = $db->insert('program_discussions', [
                'program_id' => $programId,
                'user_id' => $userId,
                'message' => $message
            ]);
            
            if ($res['ok']) {
                $_SESSION['success_message'] = "Message posted.";
            } else {
                $_SESSION['error_message'] = "Failed to post message.";
            }
        }
        $redirectUrl = $isOrganizer ? 'urus-program-hub.php' : 'program-hub.php';
        header('Location: ' . $redirectUrl . '?id=' . $programId . '&tab=discussion');
        exit();

    case 'delete_discussion':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Users can delete their own msgs, admins can delete any.
            if ($_SESSION['role'] === 'admin') {
                $res = $db->delete('program_discussions', '?id=eq.' . $id);
            } else {
                $res = $db->delete('program_discussions', '?id=eq.' . $id . '&user_id=eq.' . rawurlencode($userId));
            }
            if ($res['ok']) $_SESSION['success_message'] = "Message deleted.";
            else $_SESSION['error_message'] = "Failed to delete message or unauthorized.";
        }
        $redirectUrl = $isOrganizer ? 'urus-program-hub.php' : 'program-hub.php';
        header('Location: ' . $redirectUrl . '?id=' . $programId . '&tab=discussion');
        exit();

    default:
        $_SESSION['error_message'] = "Invalid action.";
        $redirectUrl = $isOrganizer ? 'urus-program-hub.php' : 'program-hub.php';
        header('Location: ' . $redirectUrl . '?id=' . $programId);
        exit();
}

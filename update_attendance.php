<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';

// Allow only organizers
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'penganjur') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Akses dinafikan.']);
    exit;
}

$organizerId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Input tidak sah.']);
    exit;
}

$action = $input['action'] ?? '';
$programId = isset($input['program_id']) ? (int)$input['program_id'] : 0;

if (!$programId) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Program ID diperlukan.']);
    exit;
}

// Verify that this program belongs to the logged-in organizer
$program = programs()->findById($programId);
if (!$program || ($program['penganjur_id'] ?? null) !== $organizerId) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Anda tidak mempunyai kebenaran untuk mengurus program ini.']);
    exit;
}

// Validation 1: Attendance cannot be updated for cancelled programs
if (($program['status'] ?? '') === 'Cancelled') {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Kehadiran tidak boleh diuruskan bagi program yang telah dibatalkan.']);
    exit;
}

// Validation 2: Attendance cannot be marked before the program date/time starts
$todayStr = date('Y-m-d H:i:s');
$programStartStr = $program['tarikh'] . ' ' . ($program['masa'] ?? '00:00:00');
if ($todayStr < $programStartStr) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Kehadiran tidak boleh ditandakan sebelum tarikh dan masa mula program.']);
    exit;
}

if (!db()->isConfigured()) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Pangkalan data tidak dikonfigurasi.']);
    exit;
}

header('Content-Type: application/json');

switch ($action) {
    case 'toggle':
        $pelajarId = $input['pelajar_id'] ?? '';
        $status = $input['status'] ?? 'Tidak Hadir'; // 'Hadir' or 'Tidak Hadir'
        
        if (!$pelajarId) {
            echo json_encode(['ok' => false, 'error' => 'Pelajar ID diperlukan.']);
            exit;
        }
        
        if (!in_array($status, ['Hadir', 'Tidak Hadir'], true)) {
            echo json_encode(['ok' => false, 'error' => 'Status tidak sah.']);
            exit;
        }

        // Validation 3: Only students who registered for the program can have attendance marked
        $regResult = db()->select(
            'pendaftaran',
            '?select=id,status,jenis_pendaftaran&program_id=eq.' . $programId . '&pelajar_id=eq.' . rawurlencode($pelajarId) . '&limit=1'
        );
        if (!$regResult['ok'] || empty($regResult['data'][0])) {
            echo json_encode(['ok' => false, 'error' => 'Pelajar ini tidak berdaftar untuk program ini.']);
            exit;
        }
        $regRow = $regResult['data'][0];
        if (($regRow['status'] ?? '') === 'Cancelled') {
            echo json_encode(['ok' => false, 'error' => 'Pendaftaran pelajar ini telah dibatalkan.']);
            exit;
        }
        
        // Check if attendance record already exists
        $existing = db()->select(
            'kehadiran', 
            '?program_id=eq.' . $programId . '&pelajar_id=eq.' . rawurlencode($pelajarId)
        );
        
        if ($existing['ok'] && count($existing['data']) > 0) {
            $recId = $existing['data'][0]['id'];
            $res = db()->update('kehadiran', '?id=eq.' . $recId, ['status' => $status]);
        } else {
            $res = db()->insert('kehadiran', [
                'program_id' => $programId,
                'pelajar_id' => $pelajarId,
                'status' => $status
            ]);
        }
        
        if ($res['ok']) {
            if ($status === 'Hadir') {
                $regType = $regRow['jenis_pendaftaran'] ?? 'Peserta';
                
                $actType = ($regType === 'Crew/AJK') ? 'crew_attendance' : 'attendance';
                users()->awardPoints($pelajarId, $actType, $programId);
            } else {
                // Revoke both types of attendance points
                users()->revokePoints($pelajarId, 'attendance', $programId);
                users()->revokePoints($pelajarId, 'crew_attendance', $programId);
            }
            echo json_encode(['ok' => true, 'message' => 'Kehadiran berjaya dikemas kini.']);
        } else {
            echo json_encode(['ok' => false, 'error' => $res['error'] ?? 'Gagal mengemas kini kehadiran.']);
        }
        break;
        
    case 'mark_all':
        // Fetch all registrations for this program to get pelajar_ids and roles
        $regResult = db()->select(
            'pendaftaran',
            '?select=pelajar_id,jenis_pendaftaran&program_id=eq.' . $programId . '&status=neq.Cancelled'
        );
        
        if (!$regResult['ok']) {
            echo json_encode(['ok' => false, 'error' => 'Gagal mendapatkan senarai pendaftaran.']);
            exit;
        }
        
        $registrations = $regResult['data'];
        if (empty($registrations)) {
            echo json_encode(['ok' => true, 'message' => 'Tiada peserta berdaftar untuk ditandakan.']);
            exit;
        }
        
        // Fetch existing attendance records to determine who needs insert vs update
        $attResult = db()->select('kehadiran', '?program_id=eq.' . $programId);
        $existingMap = [];
        if ($attResult['ok']) {
            foreach ($attResult['data'] as $att) {
                if (isset($att['pelajar_id'])) {
                    $existingMap[$att['pelajar_id']] = $att['id'];
                }
            }
        }
        
        $success = true;
        $errors = [];
        
        foreach ($registrations as $reg) {
            $pelajarId = $reg['pelajar_id'] ?? null;
            if (!$pelajarId) continue;
            
            if (isset($existingMap[$pelajarId])) {
                $recId = $existingMap[$pelajarId];
                $res = db()->update('kehadiran', '?id=eq.' . $recId, ['status' => 'Hadir']);
            } else {
                $res = db()->insert('kehadiran', [
                    'program_id' => $programId,
                    'pelajar_id' => $pelajarId,
                    'status' => 'Hadir'
                ]);
            }
            
            if ($res['ok']) {
                $regType = $reg['jenis_pendaftaran'] ?? 'Peserta';
                $actType = ($regType === 'Crew/AJK') ? 'crew_attendance' : 'attendance';
                users()->awardPoints($pelajarId, $actType, $programId);
            } else {
                $success = false;
                $errors[] = $res['error'] ?? 'Error updating pelajar: ' . $pelajarId;
            }
        }
        
        if ($success) {
            echo json_encode(['ok' => true, 'message' => 'Semua peserta telah ditandakan sebagai HADIR.']);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Beberapa ralat berlaku semasa mengemas kini.', 'details' => $errors]);
        }
        break;
        
    case 'reset':
        // Revoke points for all marked participants before deletion
        $attResult = db()->select('kehadiran', '?select=pelajar_id&program_id=eq.' . $programId);
        if ($attResult['ok'] && is_array($attResult['data'])) {
            foreach ($attResult['data'] as $att) {
                $pelajarId = $att['pelajar_id'] ?? null;
                if ($pelajarId) {
                    users()->revokePoints($pelajarId, 'attendance', $programId);
                    users()->revokePoints($pelajarId, 'crew_attendance', $programId);
                }
            }
        }

        // Delete all attendance records for this program
        $res = db()->delete('kehadiran', '?program_id=eq.' . $programId);
        
        if ($res['ok']) {
            echo json_encode(['ok' => true, 'message' => 'Semua rekod kehadiran telah diset semula.']);
        } else {
            echo json_encode(['ok' => false, 'error' => $res['error'] ?? 'Gagal set semula kehadiran.']);
        }
        break;
        
    default:
        echo json_encode(['ok' => false, 'error' => 'Aksi tidak sah.']);
        break;
}

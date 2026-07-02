<?php
require_once __DIR__ . '/lib/bootstrap.php';

$email = 'a23456@siswa.ukm.edu.my';
$userRes = db()->select('users', '?emel=eq.' . $email);

if ($userRes['ok'] && !empty($userRes['data'])) {
    $user = $userRes['data'][0];
    $id = $user['id'];
    
    // Update points to 200
    $updateRes = db()->update('users', '?id=eq.' . $id, ['mata' => 200]);
    echo "Updated points: " . json_encode($updateRes) . "\n";
    
    // Delete all their crew duties so they can't reach level 3
    $delRes = db()->delete('crew_applications', '?pelajar_id=eq.' . $id);
    
    // Insert exactly 5 attendance
    db()->delete('kehadiran', '?pelajar_id=eq.' . $id);
    $progs = db()->select('program', '?limit=5');
    if ($progs['ok']) {
        foreach($progs['data'] as $p) {
            db()->insert('kehadiran', [
                'program_id' => $p['id'],
                'pelajar_id' => $id,
                'status' => 'Hadir'
            ]);
        }
    }
    
    echo "Success! User a23456 is now Level 2 (200 pts, 0 completed crew).";
} else {
    echo "User not found";
}

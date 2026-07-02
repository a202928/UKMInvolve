<?php
require_once __DIR__ . '/lib/bootstrap.php';
$db = db();

// 1. Find Organizer "PERTAMA"
$orgs = $db->select('users', '?nama=ilike.*PERTAMA*&peranan=eq.penganjur');
$orgId = $orgs['data'][0]['id'] ?? null;
if (!$orgId) die("Organizer PERTAMA not found\n");

// 2. Find Category "volunteer"
$cats = $db->select('kategori', '?nama=ilike.*sukarelawan*');
// wait, the category might be named differently (e.g. "Kesukarelawanan & Khidmat Masyarakat" or "Kesukarelawanan")
if (empty($cats['data'])) {
    $cats = $db->select('kategori', '?nama=ilike.*Sukarelawan*');
}
if (empty($cats['data'])) {
    $cats = $db->select('kategori', '?nama=ilike.*volunteer*');
}
$catId = $cats['data'][0]['id'] ?? 1; // fallback to 1 if not found
$catName = $cats['data'][0]['nama'] ?? 'Kesukarelawanan';

// 3. Find Students
$students = $db->select('users', '?matrik=eq.A202928');
$student1 = $students['data'][0] ?? null;

$faizList = $db->select('users', '?nama=ilike.*Faiz*&peranan=eq.pelajar');
$student2 = $faizList['data'][0] ?? null;

if (!$student1) die("Student A202928 not found\n");
if (!$student2) die("Student Faiz not found\n");

echo "Organizer: $orgId\nCategory: $catName\nStudent 1: {$student1['id']}\nStudent 2: {$student2['id']}\n";

// 4. Create the program
$progData = [
    'nama' => 'Misi Sukarelawan Pantai Bersih',
    'penerangan' => 'Dummy program for testing past events and attendance.',
    'tarikh' => '2025-10-15', // Past date
    'masa' => '08:00:00',
    'start_date' => '2025-10-15',
    'end_date' => '2025-10-15',
    'start_time' => '08:00:00',
    'end_time' => '17:00:00',
    'lokasi' => 'Pantai Sepat',
    'kategori_id' => $catId,
    'kapasiti' => 50,
    'penganjur_id' => $orgId,
    'status' => 'Selesai' // Past event
];

$res = $db->insert('program', $progData);
if (!$res['ok']) {
    die("Failed to create program: " . json_encode($res));
}
$programId = $res['data'][0]['id'];
echo "Created Program ID: $programId\n";

// 5. Register the students
$regs = [
    [
        'program_id' => $programId,
        'pelajar_id' => $student1['id'],
        'nama' => $student1['nama'],
        'no_matrik' => $student1['matrik'],
        'fakulti' => $student1['fakulti'] ?? 'FTSM',
        'emel' => $student1['emel'],
        'telefon' => $student1['telefon'] ?? '0123456789',
        'alasan' => 'Nak tolong',
        'status' => 'Registered',
        'jenis_pendaftaran' => 'Peserta'
    ],
    [
        'program_id' => $programId,
        'pelajar_id' => $student2['id'],
        'nama' => $student2['nama'],
        'no_matrik' => $student2['matrik'],
        'fakulti' => $student2['fakulti'] ?? 'FTSM',
        'emel' => $student2['emel'],
        'telefon' => $student2['telefon'] ?? '0123456789',
        'alasan' => 'Nak tolong',
        'status' => 'Registered',
        'jenis_pendaftaran' => 'Peserta'
    ]
];

foreach ($regs as $r) {
    $rRes = $db->insert('pendaftaran', $r);
    echo "Reg for {$r['nama']}: " . ($rRes['ok'] ? "OK" : "Fail: " . json_encode($rRes)) . "\n";
}

// 6. Give them attendance
$atts = [
    [
        'program_id' => $programId,
        'pelajar_id' => $student1['id'],
        'status' => 'Hadir'
    ],
    [
        'program_id' => $programId,
        'pelajar_id' => $student2['id'],
        'status' => 'Hadir'
    ]
];
foreach ($atts as $a) {
    $aRes = $db->insert('kehadiran', $a);
    echo "Att for {$a['pelajar_id']}: " . ($aRes['ok'] ? "OK" : "Fail") . "\n";
}

echo "Done.\n";

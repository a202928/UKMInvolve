<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'daftar-program';

// Get program ID from URL
$programId = $_GET['id'] ?? 0;

// Sample programs data (sama seperti di halaman utama)
$programs = [
    1 => [
        'id' => 1,
        'title' => 'Workshop Kepimpinan Mahasiswa',
        'date' => '2026-01-25',
        'time' => '9:00 AM - 5:00 PM',
        'location' => 'Dewan Tun Canselor',
        'category' => 'Kepimpinan',
        'description' => 'Program latihan kepimpinan intensif untuk mahasiswa. Fokus pada pembangunan kemahiran kepimpinan, kerja berpasukan, dan komunikasi efektif.',
        'image' => 'program1.jpg',
        'participants' => 45,
        'capacity' => 100,
        'status' => 'available',
        'rating' => 4.8,
        'objectives' => [
            'Membangunkan kemahiran kepimpinan dalam kalangan mahasiswa',
            'Meningkatkan keyakinan diri dalam pengurusan organisasi',
            'Memperkukuh kemahiran komunikasi dan penyelesaian masalah'
        ],
        'requirements' => [
            'Mahasiswa aktif UKM',
            'Minat dalam aktiviti kepimpinan',
            'Komited untuk menghadiri semua sesi'
        ],
        'contact_person' => 'Pn. Noraini (03-89215432)',
        'deadline' => '2026-01-20',
        'multiple_sessions' => true,
        'sessions' => [
            ['date' => '2026-01-25', 'time' => '9:00 AM - 12:00 PM', 'topic' => 'Pengenalan Kepimpinan'],
            ['date' => '2026-01-25', 'time' => '2:00 PM - 5:00 PM', 'topic' => 'Kerja Berpasukan'],
            ['date' => '2026-01-26', 'time' => '9:00 AM - 12:00 PM', 'topic' => 'Komunikasi Efektif']
        ]
    ],
    2 => [
        'id' => 2,
        'title' => 'Seminar Inovasi Digital',
        'date' => '2026-01-28',
        'time' => '2:00 PM - 5:00 PM',
        'location' => 'Auditorium FSKTM',
        'category' => 'Teknologi',
        'description' => 'Seminar mengenai teknologi digital terkini dan aplikasinya dalam industri. Dikendalikan oleh pakar industri.',
        'image' => 'program2.jpg',
        'participants' => 120,
        'capacity' => 150,
        'status' => 'available',
        'rating' => 4.7,
        'objectives' => [
            'Mendedahkan peserta kepada teknologi digital terkini',
            'Memberi pemahaman tentang aplikasi teknologi dalam industri',
            'Membangunkan minat dalam bidang inovasi digital'
        ],
        'requirements' => [
            'Terbuka kepada semua pelajar UKM',
            'Minat dalam teknologi dan inovasi',
            'Laptop sendiri (jika ada)'
        ],
        'contact_person' => 'Dr. Ahmad (03-89216677)',
        'deadline' => '2026-01-26',
        'multiple_sessions' => false
    ],
    3 => [
        'id' => 3,
        'title' => 'Program Sukarelawan Komuniti',
        'date' => '2026-02-02',
        'time' => '8:00 AM - 12:00 PM',
        'location' => 'Komuniti Bangi',
        'category' => 'Komuniti',
        'description' => 'Program khidmat masyarakat di kawasan setempat. Aktiviti termasuk gotong-royong, bantuan pendidikan dan aktiviti riadah.',
        'image' => 'program3.jpg',
        'participants' => 30,
        'capacity' => 50,
        'status' => 'available',
        'rating' => 4.9,
        'objectives' => [
            'Memberi khidmat kepada komuniti setempat',
            'Membangunkan semangat sukarelawan',
            'Mengukuhkan hubungan universiti-komuniti'
        ],
        'requirements' => [
            'Sihat tubuh badan',
            'Bersedia untuk kerja fizikal ringan',
            'Attire: T-shirt dan seluar panjang'
        ],
        'contact_person' => 'En. Kamal (03-89218899)',
        'deadline' => '2026-01-30',
        'multiple_sessions' => false
    ],
    4 => [
        'id' => 4,
        'title' => 'Forum Kerjaya Graduan',
        'date' => '2026-02-15',
        'time' => '9:00 AM - 1:00 PM',
        'location' => 'Dewan Kuliah Utama',
        'category' => 'Kerjaya',
        'description' => 'Forum berkongsi peluang kerjaya untuk graduan. Dihadiri oleh wakil industri dan alumni yang berjaya.',
        'image' => 'program4.jpg',
        'participants' => 85,
        'capacity' => 200,
        'status' => 'available',
        'rating' => 4.6,
        'objectives' => [
            'Mendedahkan pelajar kepada peluang kerjaya terkini',
            'Memberi pendedahan tentang permintaan industri',
            'Membangunkan jaringan dengan alumni yang berjaya'
        ],
        'requirements' => [
            'Pelajar tahun akhir atau pascasiswazah',
            'Bersedia untuk sesi soal jawab',
            'Membawa resume (jika ada)'
        ],
        'contact_person' => 'Pn. Sarah (03-89217755)',
        'deadline' => '2026-02-10',
        'multiple_sessions' => false
    ],
    5 => [
        'id' => 5,
        'title' => 'Bengkel Penulisan Ilmiah',
        'date' => '2026-01-30',
        'time' => '2:00 PM - 5:00 PM',
        'location' => 'Perpustakaan',
        'category' => 'Akademik',
        'description' => 'Bengkel teknik penulisan akademik yang efektif untuk tesis, artikel jurnal dan kertas kerja.',
        'image' => 'program1.jpg',
        'participants' => 25,
        'capacity' => 30,
        'status' => 'full',
        'rating' => 4.5,
        'objectives' => [
            'Mengajar teknik penulisan akademik yang betul',
            'Memperkenalkan alat bantu penulisan',
            'Meningkatkan kualiti penulisan ilmiah'
        ],
        'requirements' => [
            'Pelajar sarjana atau PhD',
            'Membawa laptop',
            'Mempunyai draf penulisan (jika ada)'
        ],
        'contact_person' => 'Prof. Dr. Lim (03-89219900)',
        'deadline' => '2026-01-25',
        'multiple_sessions' => false
    ],
    6 => [
        'id' => 6,
        'title' => 'Kem Jati Diri',
        'date' => '2026-03-05',
        'time' => '8:00 AM - 6:00 PM',
        'location' => 'Kem Bina Semangat',
        'category' => 'Sukan',
        'description' => 'Kem pembangunan diri dan fizikal melalui aktiviti luar dan cabaran berpasukan.',
        'image' => 'program3.jpg',
        'participants' => 40,
        'capacity' => 40,
        'status' => 'full',
        'rating' => 4.4,
        'objectives' => [
            'Membangunkan ketahanan mental dan fizikal',
            'Mengukuhkan semangat berpasukan',
            'Meningkatkan keyakinan diri'
        ],
        'requirements' => [
            'Sihat tubuh badan',
            'Bersedia untuk aktiviti lasak',
            'Pakaian sukan dan kasut yang sesuai'
        ],
        'contact_person' => 'En. Rahim (03-89218822)',
        'deadline' => '2026-02-25',
        'multiple_sessions' => true,
        'sessions' => [
            ['date' => '2026-03-05', 'time' => '8:00 AM - 12:00 PM', 'topic' => 'Ice Breaking & Team Building'],
            ['date' => '2026-03-05', 'time' => '2:00 PM - 6:00 PM', 'topic' => 'Obstacle Course & Challenges']
        ]
    ],
    7 => [
        'id' => 7,
        'title' => 'Workshop Kreativiti & Inovasi',
        'date' => '2026-02-10',
        'time' => '10:00 AM - 4:00 PM',
        'location' => 'Bilik Seminar FEP',
        'category' => 'Keusahawanan',
        'description' => 'Bengkel untuk membangunkan idea kreatif dan inovatif dalam perniagaan.',
        'image' => 'program2.jpg',
        'participants' => 60,
        'capacity' => 80,
        'status' => 'available',
        'rating' => 4.3,
        'objectives' => [
            'Merangsang pemikiran kreatif dan inovatif',
            'Mengajar teknik penyelesaian masalah kreatif',
            'Membantu membangunkan idea perniagaan'
        ],
        'requirements' => [
            'Terbuka kepada semua pelajar',
            'Membawa buku nota dan pen',
            'Bersedia untuk aktiviti berkumpulan'
        ],
        'contact_person' => 'Pn. Aisyah (03-89215566)',
        'deadline' => '2026-02-05',
        'multiple_sessions' => false
    ],
    8 => [
        'id' => 8,
        'title' => 'Program Seni Budaya',
        'date' => '2026-02-22',
        'time' => '7:00 PM - 10:00 PM',
        'location' => 'Dewan Budaya',
        'category' => 'Seni',
        'description' => 'Pertunjukan dan bengkel seni tradisional termasuk tarian, muzik dan kraftangan.',
        'image' => 'program5.jpg',
        'participants' => 90,
        'capacity' => 150,
        'status' => 'available',
        'rating' => 4.7,
        'objectives' => [
            'Memperkenalkan seni budaya tradisional',
            'Mengekalkan warisan budaya negara',
            'Membangunkan minat dalam seni persembahan'
        ],
        'requirements' => [
            'Terbuka kepada semua pelajar',
            'Minat dalam seni dan budaya',
            'Bersedia untuk berpartisipasi aktif'
        ],
        'contact_person' => 'En. Hafiz (03-89214433)',
        'deadline' => '2026-02-18',
        'multiple_sessions' => false
    ]
];

// Get selected program
$program = $programs[$programId] ?? null;

if (!$program) {
    // Redirect to main program page if program not found
    header('Location: daftar-program.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $no_matrik = $_POST['no_matrik'] ?? '';
    $fakulti = $_POST['fakulti'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $alasan = $_POST['alasan'] ?? '';
    $sesi_dipilih = $_POST['sesi'] ?? [];
    
    // Validation
    $errors = [];
    
    if (empty($nama)) $errors[] = 'Nama diperlukan';
    if (empty($no_matrik)) $errors[] = 'Nombor matrik diperlukan';
    if (empty($fakulti)) $errors[] = 'Fakulti diperlukan';
    if (empty($email)) $errors[] = 'Email diperlukan';
    if (empty($telefon)) $errors[] = 'Nombor telefon diperlukan';
    if (empty($alasan)) $errors[] = 'Alasan penyertaan diperlukan';
    
    if (empty($errors)) {
        // Save registration to session
        if (!isset($_SESSION['registrations'])) {
            $_SESSION['registrations'] = [];
        }
        
        $_SESSION['registrations'][] = [
            'program_id' => $programId,
            'program_title' => $program['title'],
            'nama' => $nama,
            'no_matrik' => $no_matrik,
            'fakulti' => $fakulti,
            'email' => $email,
            'telefon' => $telefon,
            'alasan' => $alasan,
            'sesi_dipilih' => $sesi_dipilih,
            'tarikh_daftar' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        
        // Update program participants count in session
        if (!isset($_SESSION['program_participants'])) {
            $_SESSION['program_participants'] = [];
        }
        if (!isset($_SESSION['program_participants'][$programId])) {
            $_SESSION['program_participants'][$programId] = $program['participants'];
        }
        $_SESSION['program_participants'][$programId]++;
        
        $success = true;
        $successMessage = "Pendaftaran berjaya! Anda akan menerima email pengesahan dalam masa 24 jam.";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Program | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --surface: #ffffff;
            --surface-light: #f8fafc;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-tertiary: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            color: var(--text-primary);
        }
        
        .app-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .content {
            flex: 1;
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Layout Container */
        .registration-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 20px;
        }
        
        @media (max-width: 1024px) {
            .registration-container {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }
        
        /* Program Details Section (Kiri) */
        .program-details-section {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .program-header {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .program-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 12px;
            line-height: 1.3;
        }
        
        .program-category {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .program-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .info-icon {
            width: 20px;
            color: var(--primary);
            margin-top: 3px;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        /* Sections */
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-icon {
            color: var(--primary);
        }
        
        .objectives-list, .requirements-list {
            list-style: none;
            padding-left: 0;
        }
        
        .objectives-list li, .requirements-list li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .objectives-list li:last-child, .requirements-list li:last-child {
            border-bottom: none;
        }
        
        .list-icon {
            color: var(--success);
            margin-top: 3px;
        }
        
        /* Registration Form Section (Kanan) */
        .registration-form-section {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Form Styles */
        .registration-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .form-label .required {
            color: var(--danger);
        }
        
        .form-input, .form-select, .form-textarea {
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            background: var(--surface);
            transition: all 0.2s ease;
            width: 100%;
            font-family: inherit;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }
        
        /* Session Selection */
        .session-selection {
            background: var(--surface-light);
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
            border: 1px solid var(--border);
        }
        
        .session-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .session-item:hover {
            background: rgba(37, 99, 235, 0.05);
        }
        
        .session-item:last-child {
            border-bottom: none;
        }
        
        .session-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .session-details {
            flex: 1;
        }
        
        .session-date {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .session-time {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        
        .session-topic {
            font-size: 13px;
            color: var(--text-tertiary);
            font-style: italic;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-available {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-full {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        /* Action Buttons */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid var(--border);
        }
        
        .btn-cancel {
            flex: 1;
            padding: 14px;
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text-primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            text-align: center;
            font-family: inherit;
        }
        
        .btn-cancel:hover {
            background: var(--border);
        }
        
        .btn-submit {
            flex: 1;
            padding: 14px;
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .btn-submit:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        .btn-submit:disabled {
            background: var(--border);
            color: var(--text-tertiary);
            cursor: not-allowed;
            transform: none;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Progress Bar */
        .progress-container {
            background: var(--border);
            height: 4px;
            border-radius: 2px;
            margin: 25px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--primary);
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        /* Fakulti Options */
        .fakulti-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .fakulti-options {
                grid-template-columns: 1fr;
            }
        }
        
        .fakulti-option {
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            font-weight: 500;
        }
        
        .fakulti-option:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .fakulti-option.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .back-button:hover {
            background: rgba(37, 99, 235, 0.1);
        }
        
        /* Top Bar */
        .topbar {
            background: var(--surface);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .search-box {
            display: flex;
            gap: 8px;
        }
        
        .search-box input {
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            min-width: 300px;
        }
        
        .search-box button {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .topbar-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        
        .notification-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--text-secondary);
            cursor: pointer;
            position: relative;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Welcome Section */
        .welcome-section {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .program-details-section, .registration-form-section {
                padding: 20px;
            }
            
            .program-title {
                font-size: 24px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .content {
                padding: 16px;
            }
            
            .topbar {
                padding: 12px 16px;
                flex-direction: column;
                gap: 16px;
            }
            
            .search-box input {
                min-width: auto;
                flex: 1;
            }
        }
        
        /* Important Notes */
        .important-notes {
            background: #fff8e1;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ffb300;
            margin-top: 20px;
        }
        
        .important-notes h3 {
            font-size: 16px;
            font-weight: 600;
            color: #e65100;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .important-notes ul {
            color: #e65100;
            font-size: 14px;
            padding-left: 20px;
        }
        
        .important-notes li {
            margin-bottom: 8px;
        }
        
        /* Participants Info */
        .participants-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        
        .participants-count {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .participants-progress {
            flex: 1;
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .participants-progress-bar {
            height: 100%;
            background: var(--primary);
            border-radius: 3px;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP BAR -->
        <header class="topbar">
            <div class="search-box">
                <input type="text" placeholder="🔍 Cari program..." />
                <button>Cari</button>
            </div>
            <div class="topbar-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <section class="content">
            <!-- Back Button -->
            <a href="daftar-program.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Kembali ke Senarai Program
            </a>

            <!-- Success/Error Messages -->
            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $successMessage ?>
                </div>
            <?php elseif (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Ralat dalam pendaftaran:</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="welcome-section">
                <h1 class="page-title">Pendaftaran Program</h1>
                <p class="page-subtitle">Sila lengkapkan borang pendaftaran di bawah</p>
            </div>

            <!-- Main Container -->
            <div class="registration-container">
                <!-- Left: Program Details -->
                <div class="program-details-section">
                    <!-- Program Header -->
                    <div class="program-header">
                        <h1 class="program-title"><?= htmlspecialchars($program['title']) ?></h1>
                        <span class="program-category"><?= $program['category'] ?></span>
                        <span class="status-badge status-<?= $program['status'] ?>">
                            <?= $program['status'] === 'available' ? 'Slot Tersedia' : 'Penuh' ?>
                        </span>
                    </div>

                    <!-- Program Image -->
                    <img src="images/<?= $program['image'] ?>" alt="<?= htmlspecialchars($program['title']) ?>" class="program-image" onerror="this.src='https://via.placeholder.com/800x400/2563eb/ffffff?text=Program+Image'">

                    <!-- Program Info Grid -->
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-calendar info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Tarikh & Masa</div>
                                <div class="info-value">
                                    <?= date('d F Y', strtotime($program['date'])) ?><br>
                                    <small><?= $program['time'] ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Lokasi</div>
                                <div class="info-value"><?= $program['location'] ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-users info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Peserta</div>
                                <div class="info-value">
                                    <?php 
                                    // Use session data if available, otherwise use initial data
                                    $currentParticipants = $_SESSION['program_participants'][$programId] ?? $program['participants'];
                                    ?>
                                    <div class="participants-info">
                                        <span class="participants-count"><?= $currentParticipants ?> / <?= $program['capacity'] ?></span>
                                        <div class="participants-progress">
                                            <div class="participants-progress-bar" style="width: <?= min(100, ($currentParticipants / $program['capacity']) * 100) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-user-circle info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Hubungi</div>
                                <div class="info-value"><?= $program['contact_person'] ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Program Description -->
                    <div class="section">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle section-icon"></i>
                            Deskripsi Program
                        </h3>
                        <p style="line-height: 1.6; color: var(--text-secondary);">
                            <?= nl2br(htmlspecialchars($program['description'])) ?>
                        </p>
                    </div>

                    <!-- Program Objectives -->
                    <div class="section">
                        <h3 class="section-title">
                            <i class="fas fa-bullseye section-icon"></i>
                            Objektif Program
                        </h3>
                        <ul class="objectives-list">
                            <?php foreach ($program['objectives'] as $objective): ?>
                                <li>
                                    <i class="fas fa-check-circle list-icon"></i>
                                    <?= htmlspecialchars($objective) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Program Requirements -->
                    <div class="section">
                        <h3 class="section-title">
                            <i class="fas fa-clipboard-check section-icon"></i>
                            Syarat Penyertaan
                        </h3>
                        <ul class="requirements-list">
                            <?php foreach ($program['requirements'] as $requirement): ?>
                                <li>
                                    <i class="fas fa-check list-icon"></i>
                                    <?= htmlspecialchars($requirement) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Right: Registration Form -->
                <div class="registration-form-section">
                    <div class="form-header">
                        <h2 class="form-title">Borang Pendaftaran</h2>
                        <p class="form-subtitle">Sila isi semua maklumat di bawah dengan betul</p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 33%"></div>
                    </div>

                    <form method="POST" class="registration-form" id="registrationForm">
                        <!-- Hidden Fields -->
                        <input type="hidden" name="program_id" value="<?= $programId ?>">
                        
                        <!-- Step 1: Personal Information -->
                        <div class="form-step">
                            <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--primary);">
                                <i class="fas fa-user-circle"></i> Maklumat Peribadi
                            </h3>

                            <!-- Nama Penuh -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Nama Penuh
                                    <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="nama" 
                                       class="form-input" 
                                       placeholder="Contoh: Ahmad Bin Abdullah"
                                       value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>"
                                       required
                                       autocomplete="name">
                            </div>

                            <!-- Nombor Matrik -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-id-card"></i>
                                    Nombor Matrik
                                    <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="no_matrik" 
                                       class="form-input" 
                                       placeholder="Contoh: A123456"
                                       value="<?= isset($_POST['no_matrik']) ? htmlspecialchars($_POST['no_matrik']) : '' ?>"
                                       required
                                       autocomplete="off">
                            </div>

                            <!-- Fakulti -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-university"></i>
                                    Fakulti
                                    <span class="required">*</span>
                                </label>
                                <div class="fakulti-options">
                                    <?php 
                                    $fakultis = [
                                        'FST' => 'Fakulti Sains & Teknologi',
                                        'FEP' => 'Fakulti Ekonomi & Pengurusan',
                                        'FPEND' => 'Fakulti Pendidikan',
                                        'FPER' => 'Fakulti Perubatan',
                                        'FP' => 'Fakulti Pergigian',
                                        'FSSK' => 'Fakulti Sains Sosial & Kemanusiaan',
                                        'FTSM' => 'Fakulti Teknologi & Sains Maklumat',
                                        'FUU' => 'Fakulti Undang-undang',
                                        'FPSK' => 'Fakulti Sains Kesihatan',
                                        'Lain' => 'Fakulti Lain'
                                    ];
                                    
                                    foreach ($fakultis as $code => $nama_fakulti):
                                        $selected = (isset($_POST['fakulti']) && $_POST['fakulti'] == $code) ? 'selected' : '';
                                    ?>
                                        <div class="fakulti-option <?= $selected ?>" onclick="selectFakulti('<?= $code ?>')">
                                            <?= $nama_fakulti ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="fakulti" id="fakultiInput" value="<?= isset($_POST['fakulti']) ? htmlspecialchars($_POST['fakulti']) : '' ?>" required>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email UKM
                                    <span class="required">*</span>
                                </label>
                                <input type="email" 
                                       name="email" 
                                       class="form-input" 
                                       placeholder="Contoh: a123456@siswa.ukm.edu.my"
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                       required
                                       autocomplete="email">
                            </div>

                            <!-- Nombor Telefon -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-phone"></i>
                                    Nombor Telefon
                                    <span class="required">*</span>
                                </label>
                                <input type="tel" 
                                       name="telefon" 
                                       class="form-input" 
                                       placeholder="Contoh: 0123456789"
                                       value="<?= isset($_POST['telefon']) ? htmlspecialchars($_POST['telefon']) : '' ?>"
                                       required
                                       autocomplete="tel">
                            </div>
                        </div>

                        <!-- Step 2: Session Selection (if multiple sessions) -->
                        <?php if ($program['multiple_sessions'] && isset($program['sessions'])): ?>
                            <div class="form-step" style="margin-top: 30px;">
                                <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--primary);">
                                    <i class="fas fa-calendar-alt"></i>
                                    Pilihan Sesi
                                </h3>
                                <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 14px;">
                                    Sila pilih sesi yang ingin anda hadiri. Anda boleh memilih lebih dari satu sesi.
                                </p>

                                <div class="session-selection">
                                    <?php foreach ($program['sessions'] as $index => $session): 
                                        $checked = isset($_POST['sesi']) && in_array($index, $_POST['sesi']) ? 'checked' : '';
                                    ?>
                                        <div class="session-item">
                                            <input type="checkbox" 
                                                   name="sesi[]" 
                                                   value="<?= $index ?>" 
                                                   id="session_<?= $index ?>"
                                                   class="session-checkbox" <?= $checked ?>>
                                            <div class="session-details">
                                                <div class="session-date">
                                                    <?= date('d F Y', strtotime($session['date'])) ?>
                                                </div>
                                                <div class="session-time">
                                                    <?= $session['time'] ?>
                                                </div>
                                                <div class="session-topic">
                                                    <?= $session['topic'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Step 3: Motivation -->
                        <div class="form-step" style="margin-top: 30px;">
                            <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--primary);">
                                <i class="fas fa-comment-alt"></i>
                                Alasan Penyertaan
                            </h3>
                            <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 14px;">
                                Sila nyatakan mengapa anda berminat untuk menyertai program ini dan apa yang anda harapkan dapat dipelajari.
                            </p>

                            <div class="form-group">
                                <textarea name="alasan" 
                                          class="form-textarea" 
                                          placeholder="Nyatakan alasan anda menyertai program ini..."
                                          required><?= isset($_POST['alasan']) ? htmlspecialchars($_POST['alasan']) : '' ?></textarea>
                                <small style="color: var(--text-tertiary); font-size: 12px; margin-top: 5px;">
                                    Minimum 50 patah perkataan
                                </small>
                            </div>
                        </div>

                        <!-- Important Notes -->
                        <div class="important-notes">
                            <h3>
                                <i class="fas fa-exclamation-triangle"></i>
                                Perhatian
                            </h3>
                            <ul>
                                <li>Pendaftaran ditutup pada: <?= date('d F Y', strtotime($program['deadline'])) ?></li>
                                <li>Pengesahan akan dihantar melalui email dalam masa 24 jam</li>
                                <li>Pastikan maklumat yang diisi adalah betul dan tepat</li>
                                <li>Kehadiran adalah wajib untuk semua sesi yang didaftarkan</li>
                            </ul>
                        </div>

                        <!-- Declaration -->
                        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-top: 20px;">
                            <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px;">
                                <input type="checkbox" id="declaration" required style="margin-top: 3px;">
                                <label for="declaration" style="color: var(--text-primary); font-size: 14px; line-height: 1.5;">
                                    <strong>Saya mengisytiharkan bahawa:</strong><br>
                                    1. Semua maklumat yang diberikan adalah benar dan tepat<br>
                                    2. Saya akan menghadiri semua sesi program yang didaftarkan<br>
                                    3. Saya memahami bahawa kehadiran adalah wajib<br>
                                    4. Saya bersetuju dengan terma dan syarat penyertaan
                                </label>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <a href="daftar-program.php" class="btn-cancel">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" class="btn-submit" <?= $program['status'] === 'full' ? 'disabled' : '' ?>>
                                <?php if ($program['status'] === 'full'): ?>
                                    <i class="fas fa-times"></i> Program Penuh
                                <?php else: ?>
                                    <i class="fas fa-paper-plane"></i> Hantar Pendaftaran
                                <?php endif; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </section>
    </main>
</div>

<script>
    // Select fakulti option
    function selectFakulti(code) {
        // Remove selected class from all options
        document.querySelectorAll('.fakulti-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        event.target.classList.add('selected');
        
        // Update hidden input value
        document.getElementById('fakultiInput').value = code;
    }
    
    // Word count for alasan field
    document.querySelector('textarea[name="alasan"]').addEventListener('input', function() {
        const wordCount = this.value.trim().split(/\s+/).length;
        const wordCountDisplay = this.parentElement.querySelector('small');
        
        if (wordCount < 50) {
            wordCountDisplay.innerHTML = `Minimum 50 patah perkataan (kini: ${wordCount} perkataan)`;
            wordCountDisplay.style.color = '#ef4444';
        } else {
            wordCountDisplay.innerHTML = `${wordCount} perkataan - syarat dipenuhi ✓`;
            wordCountDisplay.style.color = '#10b981';
        }
    });
    
    // Form validation before submission
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const alasan = document.querySelector('textarea[name="alasan"]').value.trim();
        const wordCount = alasan.split(/\s+/).length;
        
        if (wordCount < 50) {
            e.preventDefault();
            alert('Sila isikan alasan penyertaan dengan minimum 50 perkataan.');
            return false;
        }
        
        // Check if fakulti is selected
        const fakulti = document.getElementById('fakultiInput').value;
        if (!fakulti) {
            e.preventDefault();
            alert('Sila pilih fakulti anda.');
            return false;
        }
        
        // Check declaration
        if (!document.getElementById('declaration').checked) {
            e.preventDefault();
            alert('Sila setujui pengisytiharan sebelum menghantar borang.');
            return false;
        }
        
        // Show loading
        const submitBtn = document.querySelector('.btn-submit');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        submitBtn.disabled = true;
    });
    
    // Auto format matrik number
    document.querySelector('input[name="no_matrik"]').addEventListener('input', function() {
        let value = this.value.toUpperCase();
        value = value.replace(/[^A-Z0-9]/g, '');
        
        // Auto add prefix if not present
        if (value.length > 0 && !value.match(/^[A-Z]/)) {
            value = 'A' + value;
        }
        
        this.value = value;
    });
    
    // Auto format phone number
    document.querySelector('input[name="telefon"]').addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        
        // Format as 012-3456789
        if (value.length > 3) {
            value = value.substring(0, 3) + '-' + value.substring(3);
        }
        if (value.length > 7) {
            value = value.substring(0, 8) + '-' + value.substring(8);
        }
        
        this.value = value;
    });
    
    // Session selection highlight
    document.querySelectorAll('.session-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sessionItem = this.closest('.session-item');
            if (this.checked) {
                sessionItem.style.background = 'rgba(37, 99, 235, 0.1)';
                sessionItem.style.borderLeft = '3px solid var(--primary)';
            } else {
                sessionItem.style.background = '';
                sessionItem.style.borderLeft = '';
            }
        });
    });
    
    // Initialize session items style
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.session-checkbox').forEach(checkbox => {
            if (checkbox.checked) {
                const sessionItem = checkbox.closest('.session-item');
                sessionItem.style.background = 'rgba(37, 99, 235, 0.1)';
                sessionItem.style.borderLeft = '3px solid var(--primary)';
            }
        });
        
        // Initialize fakulti selection
        const selectedFakulti = document.getElementById('fakultiInput').value;
        if (selectedFakulti) {
            document.querySelectorAll('.fakulti-option').forEach(option => {
                if (option.textContent.includes(selectedFakulti) || option.getAttribute('onclick')?.includes(selectedFakulti)) {
                    option.classList.add('selected');
                }
            });
        }
        
        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                successAlert.style.display = 'none';
            }
        }, 5000);
    });
</script>

</body>
</html>
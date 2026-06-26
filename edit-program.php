<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'urus-program';

$editId = (int) ($_GET['id'] ?? 0);
$dbCategories = categories()->listAll(true);

if (!db()->isConfigured() || $editId <= 0) {
    die('Program not found or database not configured.');
}

$row = programs()->findById($editId);
if (!$row) {
    die('Program not found!');
}

if (($row['penganjur_id'] ?? '') !== ($_SESSION['user_id'] ?? '')) {
    die('Access denied: You do not have permission to edit this program.');
}

$program = programs()->toEditForm($row);
$existingCrew = programs()->getCrewPositions($editId);
$updated = false;
$errorMessage = "";
$newPosterPath = $program['poster'];
$organizerInitial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));

$uploadDir = 'uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (isset($_POST['update_program'])) {

    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = basename($_FILES['poster']['name']);
        $fileSize = $_FILES['poster']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExt = ['jpg', 'jpeg', 'png'];
        $allowedMime = ['image/jpeg', 'image/png'];
        $fileMime = mime_content_type($fileTmpPath);

        if ($fileSize > 5 * 1024 * 1024) {
            $errorMessage = "Poster file size cannot exceed 5MB.";
        } elseif (in_array($fileExt, $allowedExt) && in_array($fileMime, $allowedMime)) {
            $newFileName = uniqid('poster_', true) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $newPosterPath = $destination;
            } else {
                $errorMessage = "Failed to upload poster.";
            }
        } else {
            $errorMessage = "Please upload a poster file in PNG, JPG or JPEG format only.";
        }
    }

    if (!$errorMessage) {
        $nama = trim($_POST['nama_program'] ?? '');
        $durationType = $_POST['duration_type'] ?? 'single';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = ($durationType === 'single') ? $startDate : ($_POST['end_date'] ?? '');
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';

        $venueType = $_POST['venue_type'] ?? 'physical';
        $venueName = trim($_POST['venue_name'] ?? '');
        $venueAddress = trim($_POST['venue_address'] ?? '');
        $googleMapsLink = trim($_POST['google_maps_link'] ?? '');
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        $meetingLink = trim($_POST['meeting_link'] ?? '');
        $platform = trim($_POST['platform'] ?? '');

        if ($venueType === 'online') {
            $lokasi = 'Online' . ($platform !== '' ? ' (' . $platform . ')' : '');
        } else {
            $lokasi = $venueName !== '' ? $venueName : 'Physical Location';
        }

        $kategoriSlug = $_POST['kategori'] ?? '';
        $kapasiti = isset($_POST['kapasiti']) ? (int) $_POST['kapasiti'] : 0;
        $penerangan = trim($_POST['penerangan'] ?? '');
        $jenisPendaftaran = $_POST['jenis_pendaftaran'] ?? 'Peserta';

        $targetAudience = $_POST['target_audience'] ?? ['ALL'];
        if (in_array('ALL', $targetAudience, true) || empty($targetAudience)) {
            $targetAudience = ['ALL'];
        }
        $targetAudienceJson = json_encode(array_values($targetAudience));

        $deadlineDate = $_POST['deadline_date'] ?? '';
        $deadlineTime = $_POST['deadline_time'] ?? '';
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $whatsappLink = trim($_POST['whatsapp_link'] ?? '');
        $instagramLink = trim($_POST['instagram_link'] ?? '');
        $telegramLink = trim($_POST['telegram_link'] ?? '');

        $hasEmptyFields = empty($nama) || empty($startDate) || empty($startTime) || empty($endTime) || empty($kategoriSlug) || empty($penerangan) || empty($jenisPendaftaran) || empty($deadlineDate) || empty($deadlineTime);
        if ($durationType === 'multi' && empty($endDate)) {
            $hasEmptyFields = true;
        }
        if (($venueType === 'physical' || $venueType === 'hybrid') && (empty($venueName) || empty($venueAddress))) {
            $hasEmptyFields = true;
        }

        if ($hasEmptyFields) {
            $errorMessage = 'Please fill in all required fields including Registration Deadline.';
        } else {
            $today = date('Y-m-d');
            if ($startDate < $today) {
                $errorMessage = 'Program start date cannot be in the past.';
            } elseif ($endDate < $startDate) {
                $errorMessage = 'End date cannot be earlier than start date.';
            } elseif ($deadlineDate > $startDate || ($deadlineDate === $startDate && $deadlineTime > $startTime)) {
                $errorMessage = 'Registration deadline cannot be after the program starts.';
            } elseif ($kapasiti <= 0) {
                $errorMessage = 'Capacity must be a positive number.';
            } else {
                $category = categories()->findBySlug($kategoriSlug);
                if (!$category) {
                    $errorMessage = 'The selected category is invalid. Please select a valid category.';
                } elseif (!in_array($jenisPendaftaran, ['Peserta', 'Crew/AJK', 'Peserta & Crew/AJK', 'Hebahan Sahaja'], true)) {
                    $errorMessage = 'The selected registration type is invalid.';
                } else {
                    $mata = ($jenisPendaftaran === 'Hebahan Sahaja') ? 0 : (int) ($category['mata'] ?? 100);
                    $updateData = [
                        'nama' => $nama,
                        'tarikh' => $startDate, // For backwards compatibility
                        'masa' => $startTime . (strlen($startTime) === 5 ? ':00' : ''), // For backwards compatibility
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'start_time' => $startTime . (strlen($startTime) === 5 ? ':00' : ''),
                        'end_time' => $endTime . (strlen($endTime) === 5 ? ':00' : ''),
                        'lokasi' => $lokasi,
                        'kategori_id' => $category['id'],
                        'kapasiti' => $kapasiti,
                        'mata' => $mata,
                        'penerangan' => $penerangan,
                        'poster_url' => $newPosterPath,
                        'jenis_pendaftaran' => $jenisPendaftaran,
                        'target_audience' => $targetAudienceJson,
                        'venue_type' => $venueType,
                        'venue_name' => $venueName !== '' ? $venueName : null,
                        'venue_address' => $venueAddress !== '' ? $venueAddress : null,
                        'google_maps_link' => $googleMapsLink !== '' ? $googleMapsLink : null,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'meeting_link' => $meetingLink !== '' ? $meetingLink : null,
                        'platform' => $platform !== '' ? $platform : null,
                        'deadline_date' => $deadlineDate,
                        'deadline_time' => $deadlineTime . (strlen($deadlineTime) === 5 ? ':00' : ''),
                        'contact_person' => $contactPerson,
                        'contact_number' => $contactNumber,
                        'contact_email' => $contactEmail,
                        'whatsapp_link' => $whatsappLink,
                        'instagram_link' => $instagramLink,
                        'telegram_link' => $telegramLink,
                    ];

                    $saveResult = programs()->update($editId, $updateData);
                    if ($saveResult['ok']) {
                        // Update crew positions
                        if (in_array($jenisPendaftaran, ['Crew/AJK', 'Peserta & Crew/AJK'])) {
                            $crewNames = $_POST['crew_name'] ?? [];
                            $crewVacancies = $_POST['crew_vacancies'] ?? [];
                            $crewPoints = $_POST['crew_points'] ?? [];
                            $crewDesc = $_POST['crew_desc'] ?? [];
                            
                            $positions = [];
                            foreach ($crewNames as $i => $name) {
                                if (!empty(trim($name))) {
                                    $positions[] = [
                                        'name' => $name,
                                        'vacancies' => $crewVacancies[$i] ?? 1,
                                        'points' => $crewPoints[$i] ?? 100,
                                        'description' => $crewDesc[$i] ?? ''
                                    ];
                                }
                            }
                            programs()->createCrewPositions($editId, $positions);
                        } else {
                            // Clear if no longer crew
                            programs()->createCrewPositions($editId, []);
                        }

                        $updatedRow = programs()->findById($editId);
                        if ($updatedRow) {
                            $program = programs()->toEditForm($updatedRow);
                        } else {
                            $program = programs()->toEditForm(array_merge($row, $updateData));
                        }
                        $updated = true;
                    } else {
                        $errorMessage = 'Update failed: ' . ($saveResult['error'] ?? 'Database error');
                    }
                }
            }
        }
    }
}

$menu = [
    'dashboard_penganjur' => ['Dashboard', 'fa-house'],
    'hebahan-program' => ['Announcement', 'fa-bullhorn'],
    'urus-program' => ['Manage Programs', 'fa-calendar-check'],
    'peserta-kehadiran' => ['Participants', 'fa-users'],
    'laporan-statistik' => ['Reports', 'fa-chart-column'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Programme | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .poster-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            height: fit-content;
        }
        .poster-preview {
            width: 100%;
            height: 380px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-sm);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 16px;
        }
        .poster-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .poster-placeholder i {
            font-size: 46px;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.4);
        }
        .poster-placeholder p {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 700;
            font-size: 14px;
        }
        .upload-btn {
            width: 100%;
            background: var(--white);
            color: var(--text-primary);
            border: 1px solid var(--border);
            padding: 12px 16px;
            border-radius: 999px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        .upload-btn:hover {
            background: var(--bg-secondary);
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <a href="javascript:history.back()" style="display:inline-flex; align-items:center; gap:8px; color:var(--accent-blue); text-decoration:none; font-weight:700; font-size:14px; background:var(--white); padding:8px 16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); transition:var(--transition);"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Edit Programme</h1>
                    <p>Update details and upload a new poster for your published event.</p>
                </div>
            </div>

            <!-- SUCCESS BANNER -->
            <?php if ($updated): ?>
                <div class="dashboard-card-wrap" style="text-align: center; padding: 60px 40px; max-width: 650px; margin: 0 auto;">
                    <div style="font-size: 64px; color: #10b981; margin-bottom: 24px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 style="font-size: 28px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 12px;">Programme Updated!</h2>
                    <p style="font-size: 15px; color: var(--text-secondary); margin-bottom: 32px;">Your programme details have been successfully modified.</p>
                    
                    <?php if ($newPosterPath && file_exists($newPosterPath)): ?>
                        <img src="<?= htmlspecialchars($newPosterPath) ?>" alt="Updated Poster" style="max-width: 280px; border-radius: var(--radius-sm); box-shadow: var(--shadow-md); margin-bottom: 32px; display: block; margin-left: auto; margin-right: auto;">
                    <?php endif; ?>

                    <div style="display: flex; gap: 16px; justify-content: center;">
                        <a href="urus-program.php" class="btn btn-primary" style="border-radius: 999px;">
                            <i class="fas fa-calendar-check" style="margin-right: 6px;"></i> Back to Manage Programmes
                        </a>
                        <a href="edit-program.php?id=<?= $program['id'] ?>" class="btn btn-outline" style="border-radius: 999px;">
                            <i class="fas fa-edit" style="margin-right: 6px;"></i> Edit Again
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- ERROR BANNER -->
                <?php if ($errorMessage): ?>
                    <div class="alert-banner alert-banner-error" style="margin-bottom: 24px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-circle-exclamation" style="font-size:18px; color:#ef4444;"></i>
                            <span><?= htmlspecialchars($errorMessage) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- FORM LAYOUT -->
                <form method="POST" enctype="multipart/form-data" class="dashboard-grid-2col">
                    <!-- LEFT COLUMN: POSTER UPLOAD -->
                    <div class="poster-card">
                        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px; color: var(--white); font-family: 'Outfit';">Programme Poster</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Upload a high-quality poster (PNG, JPG, or JPEG) up to 5MB.</p>

                        <div class="poster-preview">
                            <?php if (!empty($program['poster']) && file_exists($program['poster'])): ?>
                                <img id="posterPreview" src="<?= htmlspecialchars($program['poster']) ?>" alt="Program Poster">
                            <?php else: ?>
                                <div class="poster-placeholder" id="posterPlaceholder">
                                    <i class="far fa-image"></i>
                                    <p>No poster uploaded yet</p>
                                </div>
                                <img id="posterPreview" style="display:none;" alt="Poster Preview">
                            <?php endif; ?>
                        </div>

                        <label for="posterInput" class="upload-btn">
                            <i class="fas fa-cloud-upload-alt"></i> Change Poster Image
                        </label>
                        <input type="file" id="posterInput" name="poster" accept="image/png,image/jpeg" hidden>
                    </div>

                    <!-- RIGHT COLUMN: DETAILED INFO -->
                    <div class="dashboard-card-wrap" style="margin-bottom: 0;">
                        <h3 style="font-size: 18px; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 20px; font-family: 'Outfit';">Programme Details</h3>
                        
                        <div class="profile-form-grid">
                            <div class="form-group-profile profile-form-full">
                                <label for="nama_program">Programme Name / Title <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="nama_program" id="nama_program" class="form-input-profile" placeholder="e.g. Artificial Intelligence Workshop 2026" required value="<?= htmlspecialchars($program['nama']) ?>">
                            </div>

                            <div class="form-group-profile">
                                <label for="categorySelect">Category <span style="color:#ef4444;">*</span></label>
                                <select name="kategori" id="categorySelect" class="form-select-profile" onchange="updateCategoryPreview()" required>
                                    <option value="">Choose category</option>
                                    <?php foreach ($dbCategories as $cat): 
                                        $selected = ($program['kategori_slug'] ?? '') === $cat['slug'] ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($cat['slug']) ?>" data-icon="<?= htmlspecialchars($cat['icon'] ?? 'fa-layer-group') ?>" data-color="<?= htmlspecialchars($cat['color'] ?? '#5b8def') ?>" data-points="<?= (int)($cat['mata'] ?? 100) ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($cat['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <!-- Category Preview widget -->
                                <div id="categoryPreview" style="display: none; align-items: center; gap: 8px; margin-top: 8px; padding: 6px 12px; border-radius: var(--radius-sm); background: var(--bg-secondary); border: 1px solid var(--border); width: fit-content;">
                                    <span id="categoryPreviewIconContainer" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; color: white; font-size: 12px;">
                                        <i id="categoryPreviewIcon" class="fas"></i>
                                    </span>
                                    <span id="categoryPreviewName" style="font-size: 12px; font-weight: 700; color: var(--text-primary);"></span>
                                </div>
                            </div>

                            <div class="form-group-profile">
                                <label for="jenis_pendaftaran">Registration Type <span style="color:#ef4444;">*</span></label>
                                <select name="jenis_pendaftaran" id="jenis_pendaftaran" class="form-select-profile" required onchange="toggleCrewPositions(); updateCategoryPreview();">
                                    <option value="Peserta" <?= ($program['jenis_pendaftaran'] ?? 'Peserta') === 'Peserta' ? 'selected' : '' ?>>Participant Registration (Peserta)</option>
                                    <option value="Crew/AJK" <?= ($program['jenis_pendaftaran'] ?? 'Peserta') === 'Crew/AJK' ? 'selected' : '' ?>>Crew Recruitment Only (Crew/AJK)</option>
                                    <option value="Peserta & Crew/AJK" <?= ($program['jenis_pendaftaran'] ?? 'Peserta') === 'Peserta & Crew/AJK' ? 'selected' : '' ?>>Both Participant & Crew Recruitment</option>
                                    <option value="Hebahan Sahaja" <?= ($program['jenis_pendaftaran'] ?? 'Peserta') === 'Hebahan Sahaja' ? 'selected' : '' ?>>Announcement Only (Hebahan Sahaja)</option>
                                </select>
                            </div>

                            <!-- DYNAMIC CREW POSITIONS SECTION -->
                            <div id="crewPositionsSection" class="form-group-profile profile-form-full" style="display: none; background: var(--bg-main); padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-md);">
                                <h4 style="font-size: 14px; font-weight: 800; margin-bottom: 12px; color: var(--text-primary);">Committee / Crew Positions</h4>
                                <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 16px;">Define the available positions, vacancies, and point rewards for crew members.</p>
                                
                                <div id="crewList">
                                    <?php if (!empty($existingCrew)): ?>
                                        <?php foreach ($existingCrew as $pos): ?>
                                            <div class="crew-item" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: start; background: var(--white); padding: 12px; border: 1px solid var(--border); border-radius: var(--radius-sm);">
                                                <div>
                                                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; display: block;">Position Name <span style="color:#ef4444;">*</span></label>
                                                    <input type="text" name="crew_name[]" class="form-input-profile" placeholder="e.g. Program Director" required value="<?= htmlspecialchars($pos['nama_jawatan']) ?>" style="padding: 8px 12px; font-size: 13px;">
                                                    <textarea name="crew_desc[]" class="form-textarea-profile" placeholder="Description & Requirements..." style="margin-top: 8px; font-size: 13px; padding: 8px; min-height: 60px;"><?= htmlspecialchars($pos['deskripsi']) ?></textarea>
                                                </div>
                                                <div>
                                                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; display: block;">Vacancies <span style="color:#ef4444;">*</span></label>
                                                    <input type="number" name="crew_vacancies[]" class="form-input-profile" min="1" required value="<?= (int)$pos['kuota'] ?>" style="padding: 8px 12px; font-size: 13px;">
                                                </div>
                                                <div>
                                                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; display: block;">Points <span style="color:#ef4444;">*</span></label>
                                                    <input type="number" name="crew_points[]" class="form-input-profile" min="1" required value="<?= (int)$pos['mata_ganjaran'] ?>" style="padding: 8px 12px; font-size: 13px;">
                                                </div>
                                                <div style="padding-top: 20px;">
                                                    <button type="button" class="btn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;" onclick="this.closest('.crew-item').remove()">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="btn btn-outline btn-sm" onclick="addCrewPosition()" style="margin-top: 10px;">
                                    <i class="fas fa-plus"></i> Add Position
                                </button>
                            </div>

                            <?php 
                            $targetAudienceRaw = $row['target_audience'] ?? '["ALL"]';
                            $targetAudienceArr = is_array($targetAudienceRaw) ? $targetAudienceRaw : (json_decode($targetAudienceRaw, true) ?: ['ALL']);
                            $isTargetSelected = function($val) use ($targetAudienceArr) {
                                return in_array($val, $targetAudienceArr, true) ? 'checked' : '';
                            };
                            ?>
                            <div class="form-group-profile profile-form-full">
                                <label>Target Audience <span style="color:#ef4444;">*</span></label>
                                <div style="background: var(--bg-main); padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-md);">
                                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 800; cursor: pointer; margin-bottom: 16px;">
                                        <input type="checkbox" name="target_audience[]" value="ALL" id="allAudienceCb" style="width: 18px; height: 18px; accent-color: var(--accent-blue);" <?= $isTargetSelected('ALL') ? 'checked' : '' ?> onchange="toggleAudienceCheckboxes(this)"> 
                                        ALL STUDENTS
                                    </label>

                                    <div id="specificAudiences" style="display: none; border-top: 1px solid var(--border); padding-top: 16px;">
                                        <p style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 12px; text-transform: uppercase;">By Faculty</p>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; margin-bottom: 20px;">
                                            <?php 
                                            $faculties = ['FTSM', 'FEP', 'FPI', 'FUU', 'FKAB', 'FSK', 'FPER', 'FGG', 'FPEND', 'FARMASI'];
                                            foreach ($faculties as $fac): 
                                            ?>
                                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                                <input type="checkbox" name="target_audience[]" value="<?= $fac ?>" class="specific-audience-cb" <?= $isTargetSelected($fac) ?> style="accent-color: var(--accent-blue);"> <?= $fac ?>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <p style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 12px; text-transform: uppercase;">By College</p>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px;">
                                            <?php 
                                            $colleges = ['KDO', 'KPZ', 'KTSN', 'KKM', 'KUO', 'KIZ', 'KAB', 'KBH', 'KRK', 'KTDI', 'KTHO', 'KIY', 'KOLEJ 13'];
                                            foreach ($colleges as $col): 
                                            ?>
                                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                                <input type="checkbox" name="target_audience[]" value="<?= $col ?>" class="specific-audience-cb" <?= $isTargetSelected($col) ?> style="accent-color: var(--accent-blue);"> <?= $col ?>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-profile">
                                <label for="kapasiti">Capacity Limit <span style="color:#ef4444;">*</span></label>
                                <input type="number" name="kapasiti" id="kapasiti" class="form-input-profile" min="1" placeholder="e.g. 100" required value="<?= htmlspecialchars($program['kapasiti']) ?>">
                            </div>

                            <div class="form-group-profile">
                                <label>Points to Award</label>
                                <div style="padding: 12px 16px; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--bg-secondary); font-weight: 600; font-size: 14px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; height: 46px;" id="pointsDisplay">
                                    Select a category to view points
                                </div>
                            </div>

                            <div class="form-group-profile profile-form-full">
                                <label>Duration Type <span style="color:#ef4444;">*</span></label>
                                <div style="display: flex; gap: 24px; margin-top: 8px;">
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                                        <input type="radio" name="duration_type" value="single" <?= ($program['start_date'] === $program['end_date']) ? 'checked' : '' ?> style="accent-color: var(--accent-blue); width: 18px; height: 18px;"> Single-Day Event
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                                        <input type="radio" name="duration_type" value="multi" <?= ($program['start_date'] !== $program['end_date']) ? 'checked' : '' ?> style="accent-color: var(--accent-blue); width: 18px; height: 18px;"> Multi-Day Event
                                    </label>
                                </div>
                            </div>

                            <div class="form-group-profile">
                                <label for="start_date" id="startDateLabel">Date <span style="color:#ef4444;">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-input-profile" required value="<?= htmlspecialchars($program['start_date']) ?>">
                            </div>

                            <div class="form-group-profile" id="endDateGroup" style="display: none;">
                                <label for="end_date">End Date <span style="color:#ef4444;">*</span></label>
                                <input type="date" name="end_date" id="end_date" class="form-input-profile" value="<?= htmlspecialchars($program['end_date']) ?>">
                            </div>

                            <div class="form-group-profile">
                                <label for="start_time">Start Time <span style="color:#ef4444;">*</span></label>
                                <input type="time" name="start_time" id="start_time" class="form-input-profile" required value="<?= htmlspecialchars($program['start_time']) ?>">
                            </div>

                            <div class="form-group-profile">
                                <label for="end_time">End Time <span style="color:#ef4444;">*</span></label>
                                <input type="time" name="end_time" id="end_time" class="form-input-profile" required value="<?= htmlspecialchars($program['end_time']) ?>">
                            </div>

                            <!-- NEW: Registration Deadline -->
                            <div class="form-group-profile" style="border-top: 1px dashed var(--border); padding-top: 16px; margin-top: 8px;">
                                <label for="deadline_date">Registration Deadline Date <span style="color:#ef4444;">*</span></label>
                                <input type="date" name="deadline_date" id="deadline_date" class="form-input-profile" required value="<?= htmlspecialchars($program['deadline_date'] ?? '') ?>">
                            </div>

                            <div class="form-group-profile" style="border-top: 1px dashed var(--border); padding-top: 16px; margin-top: 8px;">
                                <label for="deadline_time">Registration Deadline Time <span style="color:#ef4444;">*</span></label>
                                <input type="time" name="deadline_time" id="deadline_time" class="form-input-profile" required value="<?= htmlspecialchars($program['deadline_time'] ?? '') ?>">
                            </div>

                            <!-- NEW: Contact Information -->
                            <div class="form-group-profile profile-form-full" style="border-top: 1px solid var(--border); padding-top: 24px; margin-top: 16px;">
                                <h4 style="font-size: 16px; font-weight: 800; margin-bottom: 16px; color: var(--text-primary);">Contact Information (Optional)</h4>
                                <div class="profile-form-grid" style="gap: 16px;">
                                    <div class="form-group-profile">
                                        <label for="contact_person">Contact Person Name</label>
                                        <input type="text" name="contact_person" id="contact_person" class="form-input-profile" placeholder="e.g. Ahmad Ali" value="<?= htmlspecialchars($program['contact_person'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="contact_number">Contact Number</label>
                                        <input type="text" name="contact_number" id="contact_number" class="form-input-profile" placeholder="e.g. +60123456789" value="<?= htmlspecialchars($program['contact_number'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="contact_email">Contact Email</label>
                                        <input type="email" name="contact_email" id="contact_email" class="form-input-profile" placeholder="e.g. ahmad@ukm.edu.my" value="<?= htmlspecialchars($program['contact_email'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="whatsapp_link">WhatsApp Link</label>
                                        <input type="url" name="whatsapp_link" id="whatsapp_link" class="form-input-profile" placeholder="https://wa.me/60123456789" value="<?= htmlspecialchars($program['whatsapp_link'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="telegram_link">Telegram Link</label>
                                        <input type="url" name="telegram_link" id="telegram_link" class="form-input-profile" placeholder="https://t.me/ahmad" value="<?= htmlspecialchars($program['telegram_link'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="instagram_link">Instagram Link</label>
                                        <input type="url" name="instagram_link" id="instagram_link" class="form-input-profile" placeholder="https://instagram.com/ahmad" value="<?= htmlspecialchars($program['instagram_link'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Venue Type Selection -->
                            <div class="form-group-profile profile-form-full">
                                <label for="venue_type">Venue Type <span style="color:#ef4444;">*</span></label>
                                <select name="venue_type" id="venue_type" class="form-select-profile" required onchange="toggleVenueFields()">
                                    <option value="physical" <?= ($program['venue_type'] ?? 'physical') === 'physical' ? 'selected' : '' ?>>Physical Event (Face-to-face)</option>
                                    <option value="online" <?= ($program['venue_type'] ?? 'physical') === 'online' ? 'selected' : '' ?>>Online Event (Virtual)</option>
                                    <option value="hybrid" <?= ($program['venue_type'] ?? 'physical') === 'hybrid' ? 'selected' : '' ?>>Hybrid Event (Both Physical & Online)</option>
                                </select>
                            </div>

                            <!-- Physical Location Details Box -->
                            <div id="physicalVenueSection" class="profile-form-full" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; margin-bottom: 16px; display: none;">
                                <h4 style="font-size: 14px; font-weight: 800; margin-bottom: 16px; color: var(--text-primary);"><i class="fas fa-map-location-dot" style="margin-right: 8px; color: var(--accent-blue);"></i> Physical Venue Information</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div class="form-group-profile profile-form-full" style="grid-column: span 2;">
                                        <label for="venue_name">Venue Name <span style="color:#ef4444;">*</span></label>
                                        <input type="text" name="venue_name" id="venue_name" class="form-input-profile" placeholder="e.g. Dewan Gemilang" value="<?= htmlspecialchars($program['venue_name'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile profile-form-full" style="grid-column: span 2;">
                                        <label for="venue_address">Venue Address <span style="color:#ef4444;">*</span></label>
                                        <input type="text" name="venue_address" id="venue_address" class="form-input-profile" placeholder="e.g. UKM, 43600 Bangi, Selangor" value="<?= htmlspecialchars($program['venue_address'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile profile-form-full" style="grid-column: span 2;">
                                        <label for="google_maps_link">Google Maps Share Link</label>
                                        <input type="url" name="google_maps_link" id="google_maps_link" class="form-input-profile" placeholder="e.g. https://maps.app.goo.gl/..." value="<?= htmlspecialchars($program['google_maps_link'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="latitude">Latitude (Optional)</label>
                                        <input type="number" step="any" name="latitude" id="latitude" class="form-input-profile" placeholder="e.g. 2.9289" value="<?= htmlspecialchars($program['latitude'] ?? '') ?>">
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="longitude">Longitude (Optional)</label>
                                        <input type="number" step="any" name="longitude" id="longitude" class="form-input-profile" placeholder="e.g. 101.7801" value="<?= htmlspecialchars($program['longitude'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Online Meeting Details Box -->
                            <div id="onlineMeetingSection" class="profile-form-full" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px; margin-bottom: 16px; display: none;">
                                <h4 style="font-size: 14px; font-weight: 800; margin-bottom: 16px; color: var(--text-primary);"><i class="fas fa-video" style="margin-right: 8px; color: var(--accent);"></i> Online Meeting Details</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div class="form-group-profile">
                                        <label for="platform">Meeting Platform</label>
                                        <select name="platform" id="platform" class="form-select-profile">
                                            <option value="">Select Platform (Optional)</option>
                                            <option value="Google Meet" <?= ($program['platform'] ?? '') === 'Google Meet' ? 'selected' : '' ?>>Google Meet</option>
                                            <option value="Zoom" <?= ($program['platform'] ?? '') === 'Zoom' ? 'selected' : '' ?>>Zoom</option>
                                            <option value="Microsoft Teams" <?= ($program['platform'] ?? '') === 'Microsoft Teams' ? 'selected' : '' ?>>Microsoft Teams</option>
                                            <option value="Webex" <?= ($program['platform'] ?? '') === 'Webex' ? 'selected' : '' ?>>Cisco Webex</option>
                                            <option value="Discord" <?= ($program['platform'] ?? '') === 'Discord' ? 'selected' : '' ?>>Discord</option>
                                            <option value="YouTube Live" <?= ($program['platform'] ?? '') === 'YouTube Live' ? 'selected' : '' ?>>YouTube Live</option>
                                            <option value="Other" <?= ($program['platform'] ?? '') === 'Other' ? 'selected' : '' ?>>Other Platform</option>
                                        </select>
                                    </div>
                                    <div class="form-group-profile">
                                        <label for="meeting_link">Meeting / Streaming Link</label>
                                        <input type="text" name="meeting_link" id="meeting_link" class="form-input-profile" placeholder="e.g. TBA or URL" value="<?= htmlspecialchars($program['meeting_link'] ?? '') ?>">
                                        <p style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">Leave blank or write "TBA" if the link will be shared later.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-profile profile-form-full">
                                <label for="penerangan">Programme Description <span style="color:#ef4444;">*</span></label>
                                <textarea name="penerangan" id="penerangan" class="form-textarea-profile" placeholder="Write a compelling description of the programme, schedule, and guidelines..." required><?= htmlspecialchars($program['penerangan']) ?></textarea>
                            </div>
                        </div>

                        <div style="margin-top: 32px; display: flex; justify-content: flex-end; gap: 16px;">
                            <a href="urus-program.php" class="btn btn-outline" style="border-radius: 999px;">Cancel</a>
                            <button type="submit" name="update_program" class="btn btn-primary" style="border-radius: 999px;">
                                <i class="fas fa-edit" style="margin-right: 6px;"></i> Update Programme
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    const posterInput = document.getElementById('posterInput');
    const posterPreview = document.getElementById('posterPreview');
    const placeholder = document.getElementById('posterPlaceholder');

    if (posterInput) {
        posterInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const allowedTypes = ['image/png', 'image/jpeg'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please upload PNG, JPG or JPEG only.');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                posterPreview.src = e.target.result;
                posterPreview.style.display = 'block';
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // Crew Positions Logic
    function toggleCrewPositions() {
        const jenis = document.getElementById('jenis_pendaftaran').value;
        const section = document.getElementById('crewPositionsSection');
        if (jenis === 'Crew/AJK' || jenis === 'Peserta & Crew/AJK') {
            section.style.display = 'block';
            if (document.getElementById('crewList').children.length === 0) {
                addCrewPosition(); // add one by default
            }
        } else {
            section.style.display = 'none';
        }
    }

    function addCrewPosition() {
        const container = document.getElementById('crewList');
        
        const html = `
            <div class="crew-item" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: start; background: var(--white); padding: 12px; border: 1px solid var(--border); border-radius: var(--radius-sm);">
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; display: block;">Position Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="crew_name[]" class="form-input-profile" placeholder="e.g. Program Director" required style="padding: 8px 12px; font-size: 13px;">
                    <textarea name="crew_desc[]" class="form-textarea-profile" placeholder="Description & Requirements..." style="margin-top: 8px; font-size: 13px; padding: 8px; min-height: 60px;"></textarea>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; display: block;">Vacancies <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="crew_vacancies[]" class="form-input-profile" min="1" value="1" required style="padding: 8px 12px; font-size: 13px;">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; display: block;">Points <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="crew_points[]" class="form-input-profile" min="1" value="100" required style="padding: 8px 12px; font-size: 13px;">
                </div>
                <div style="padding-top: 20px;">
                    <button type="button" class="btn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;" onclick="this.closest('.crew-item').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    function toggleVenueFields() {
        const venueType = document.getElementById('venue_type').value;
        const physicalSection = document.getElementById('physicalVenueSection');
        const onlineSection = document.getElementById('onlineMeetingSection');
        
        const venueNameInput = document.getElementById('venue_name');
        const venueAddrInput = document.getElementById('venue_address');

        if (venueType === 'physical') {
            physicalSection.style.display = 'block';
            onlineSection.style.display = 'none';
            if (venueNameInput) venueNameInput.required = true;
            if (venueAddrInput) venueAddrInput.required = true;
        } else if (venueType === 'online') {
            physicalSection.style.display = 'none';
            onlineSection.style.display = 'block';
            if (venueNameInput) venueNameInput.required = false;
            if (venueAddrInput) venueAddrInput.required = false;
        } else if (venueType === 'hybrid') {
            physicalSection.style.display = 'block';
            onlineSection.style.display = 'block';
            if (venueNameInput) venueNameInput.required = true;
            if (venueAddrInput) venueAddrInput.required = true;
        }
    }

    // Initialize display on load
    document.addEventListener('DOMContentLoaded', function() {
        toggleCrewPositions();
        toggleVenueFields();
    });

    // Dynamic Duration Type logic
    const durationRadios = document.querySelectorAll('input[name="duration_type"]');
    const endDateGroup = document.getElementById('endDateGroup');
    const endDateInput = document.getElementById('end_date');
    const startDateLabel = document.getElementById('startDateLabel');

    function toggleEndDate() {
        const activeRadio = document.querySelector('input[name="duration_type"]:checked');
        const isMulti = activeRadio && activeRadio.value === 'multi';
        if (isMulti) {
            endDateGroup.style.display = 'block';
            endDateInput.setAttribute('required', 'required');
            startDateLabel.innerHTML = 'Start Date <span style="color:#ef4444;">*</span>';
        } else {
            endDateGroup.style.display = 'none';
            endDateInput.removeAttribute('required');
            endDateInput.value = '';
            startDateLabel.innerHTML = 'Programme Date <span style="color:#ef4444;">*</span>';
        }
    }

    durationRadios.forEach(radio => radio.addEventListener('change', toggleEndDate));

    function updateCategoryPreview() {
        const select = document.getElementById('categorySelect');
        const preview = document.getElementById('categoryPreview');
        const iconContainer = document.getElementById('categoryPreviewIconContainer');
        const iconEl = document.getElementById('categoryPreviewIcon');
        const nameEl = document.getElementById('categoryPreviewName');
        const pointsDisplay = document.getElementById('pointsDisplay');
        const regTypeSelect = document.getElementById('jenis_pendaftaran');
        const isHebahanOnly = regTypeSelect && regTypeSelect.value === 'Hebahan Sahaja';
        
        if (!select) return;
        
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const iconClass = selectedOption.getAttribute('data-icon');
            const colorHex = selectedOption.getAttribute('data-color');
            let points = selectedOption.getAttribute('data-points') || '100';
            const text = selectedOption.text;
            
            if (isHebahanOnly) points = '0';
            
            iconEl.className = 'fas ' + iconClass;
            iconContainer.style.backgroundColor = colorHex;
            nameEl.textContent = text;
            preview.style.display = 'flex';
            
            if (pointsDisplay) {
                pointsDisplay.innerHTML = `<i class="fas fa-coins" style="color: var(--orange);"></i> ${points} Points`;
                pointsDisplay.style.color = 'var(--text-primary)';
            }
        } else {
            preview.style.display = 'none';
            if (pointsDisplay) {
                pointsDisplay.innerHTML = `Select a category to view points`;
                pointsDisplay.style.color = 'var(--text-muted)';
            }
        }
    }

    // Initialize on load
    toggleEndDate();
    updateCategoryPreview();

    function toggleAudienceCheckboxes(allCheckbox) {
        const specificDiv = document.getElementById('specificAudiences');
        const specificCbs = document.querySelectorAll('.specific-audience-cb');
        if (allCheckbox.checked) {
            specificDiv.style.display = 'none';
            specificCbs.forEach(cb => {
                cb.checked = false;
                cb.removeAttribute('required');
            });
        } else {
            specificDiv.style.display = 'block';
        }
    }
    toggleAudienceCheckboxes(document.getElementById('allAudienceCb'));
    </script>
</body>
</html>
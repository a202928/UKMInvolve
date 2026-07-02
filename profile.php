<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireLogin();

$userId = $_SESSION['user_id'] ?? '';
$userRole = $_SESSION['role'] ?? '';

$successMessage = '';
$errorMessage = '';

// Ensure profile upload directory exists
$uploadDir = 'uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Fetch live user data
$user = null;
if (db()->isConfigured() && $userId) {
    $user = users()->findById($userId);
}

if (!$user) {
    header('Location: login.php');
    exit();
}

$userInitial = strtoupper(substr($user['nama'] ?? 'U', 0, 1));
$avatarUrl = $user['avatar_url'] ?? null;

// Parse social links if present
$socialLinks = [];
if (!empty($user['social_links'])) {
    if (is_array($user['social_links'])) {
        $socialLinks = $user['social_links'];
    } else {
        $socialLinks = json_decode($user['social_links'], true) ?: [];
    }
}

// Calculate Profile Completion Score for students
$completionScore = 100;
$missingFields = [];
if ($userRole === 'pelajar') {
    $fields = [
        'nama' => 'Full Name',
        'matrik' => 'Matric Number',
        'fakulti' => 'Faculty',
        'tahun_pengajian' => 'Year of Study',
        'kursus_pengajian' => 'Course of Study',
        'kolej' => 'College',
        'no_telefon' => 'Phone Number',
        'bio' => 'Biography',
        'avatar_url' => 'Profile Picture'
    ];
    
    $filled = 0;
    foreach ($fields as $key => $label) {
        if (!empty($user[$key])) {
            $filled++;
        } else {
            $missingFields[] = $label;
        }
    }
    $completionScore = round(($filled / count($fields)) * 100);
}

// Fetch categories and student interests
$interestCategories = [];
$studentInterests = [];
if (db()->isConfigured() && $userRole === 'pelajar') {
    foreach (categories()->listAll(true) as $cat) {
        $interestCategories[$cat['slug']] = [$cat['nama'], $cat['icon'] ?? 'fa-layer-group'];
    }
    $studentInterests = interests()->getStudentInterestSlugs($userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newAvatarPath = '';
    
    // Save student interests if provided
    if ($userRole === 'pelajar' && isset($_POST['interests'])) {
        $selectedInterests = $_POST['interests'];
        if (count($selectedInterests) >= 1 && count($selectedInterests) <= 3) {
            interests()->saveInterests($userId, $selectedInterests);
            $studentInterests = $selectedInterests; // Update for display
        }
    }
    
    // Handle File Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName = basename($_FILES['avatar']['name']);
        $fileSize = $_FILES['avatar']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExt = ['jpg', 'jpeg', 'png'];
        $allowedMime = ['image/jpeg', 'image/png'];
        $fileMime = mime_content_type($fileTmpPath);
        
        if ($fileSize > 2 * 1024 * 1024) {
            $errorMessage = 'Profile picture size cannot exceed 2MB.';
        } elseif (!in_array($fileExt, $allowedExt) || !in_array($fileMime, $allowedMime)) {
            $errorMessage = 'Please upload a PNG, JPG or JPEG image format only.';
        } else {
            $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            
            // Delete old avatar if exists
            if ($avatarUrl && file_exists($avatarUrl)) {
                @unlink($avatarUrl);
            }
            
            if (move_uploaded_file($fileTmpPath, $destination)) {
                $newAvatarPath = $destination;
                $avatarUrl = $destination;
            } else {
                $errorMessage = 'Failed to upload profile picture.';
            }
        }
    }
    
    // Process form parameters
    if (empty($errorMessage)) {
        $updateData = [];
        $updateData['nama'] = trim($_POST['nama'] ?? '');
        $updateData['bio'] = trim($_POST['bio'] ?? '');
        $updateData['no_telefon'] = trim($_POST['no_telefon'] ?? '');
        $updateData['fakulti'] = trim($_POST['fakulti'] ?? '');
        $updateData['kolej'] = trim($_POST['kolej'] ?? '');
        
        if ($newAvatarPath) {
            $updateData['avatar_url'] = $newAvatarPath;
        }
        
        if ($userRole === 'pelajar') {
            $updateData['tahun_pengajian'] = isset($_POST['tahun_pengajian']) ? (int)$_POST['tahun_pengajian'] : null;
            $updateData['kursus_pengajian'] = isset($_POST['kursus_pengajian']) ? trim($_POST['kursus_pengajian']) : null;
        } elseif ($userRole === 'penganjur') {
            $updateData['emel'] = strtolower(trim($_POST['emel'] ?? ''));
            
            $socials = [
                'facebook' => trim($_POST['social_facebook'] ?? ''),
                'instagram' => trim($_POST['social_instagram'] ?? ''),
                'linkedin' => trim($_POST['social_linkedin'] ?? ''),
            ];
            $updateData['social_links'] = $socials;
        }
        
        $res = users()->update($userId, $updateData);
        if ($res['ok']) {
            $_SESSION['success_message'] = 'Profile updated successfully!';
            
            // Reload user session data
            $_SESSION['nama'] = $updateData['nama'];
            
            header('Location: profile.php');
            exit();
        } else {
            $errorMessage = 'Failed to update profile: ' . ($res['error'] ?? 'Database error. Make sure to execute supabase/profile_fields.sql in Supabase SQL editor.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <a href="<?= $userRole === 'pelajar' ? 'dashboard_pelajar.php' : 'dashboard_penganjur.php' ?>" style="display:inline-flex; align-items:center; gap:8px; color:var(--accent-blue); text-decoration:none; font-weight:700; font-size:14px; background:var(--white); padding:8px 16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); transition:var(--transition);"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>My Profile</h1>
                    <p>Customize your profile picture, bio, and contact details.</p>
                </div>
            </div>

            <?php if (isset($_GET['incomplete']) && $_GET['incomplete'] == 1): ?>
                <div class="alert-banner alert-banner-error" style="margin-bottom:24px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-exclamation-triangle" style="font-size:18px; color:#ef4444;"></i>
                        <span><strong>Action Required:</strong> Please complete your profile before you can register for or save events.</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($userRole === 'pelajar'): ?>
                <div class="dashboard-card-wrap" style="padding: 24px; margin-bottom: 32px; background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); border: 1px solid #bfdbfe;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h2 style="font-size: 18px; font-weight: 800; color: #1e3a8a;"><i class="fas fa-bullseye" style="color: #3b82f6; margin-right: 8px;"></i> Profile Completion</h2>
                        <span style="font-size: 20px; font-weight: 900; color: <?= $completionScore === 100 ? '#10b981' : '#3b82f6' ?>;"><?= $completionScore ?>%</span>
                    </div>
                    
                    <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-bottom: 16px;">
                        <div style="width: <?= $completionScore ?>%; height: 100%; background: <?= $completionScore === 100 ? '#10b981' : 'linear-gradient(90deg, #60a5fa 0%, #2563eb 100%)' ?>; transition: width 0.5s ease;"></div>
                    </div>
                    
                    <?php if (!empty($missingFields)): ?>
                        <div style="background: white; border: 1px dashed #cbd5e1; border-radius: var(--radius-sm); padding: 12px;">
                            <p style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 8px;">Missing information to reach 100%:</p>
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #ef4444; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 4px;">
                                <?php foreach ($missingFields as $field): ?>
                                    <li><?= htmlspecialchars($field) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p style="font-size: 13px; font-weight: 700; color: #10b981; margin: 0;"><i class="fas fa-check-circle" style="margin-right: 6px;"></i> Your profile is complete! You can now participate in all platform activities.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-banner alert-banner-success">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-circle-check" style="font-size:18px; color:#10b981;"></i>
                        <span><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert-banner alert-banner-error">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-circle-exclamation" style="font-size:18px; color:#ef4444;"></i>
                        <span><?= htmlspecialchars($errorMessage) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="profile-wrapper">
                <!-- LEFT COLUMN: AVATAR & GAMIFICATION INFO -->
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <div class="profile-avatar-card" style="margin-bottom: 0; width: 100%; box-sizing: border-box;">
                        <div class="profile-image-container" style="border: none; overflow: visible; display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                            <?php 
                            $level = (int)($user['level'] ?? 1);
                            $frameClass = ($userRole === 'pelajar' && $level > 0) ? "avatar-frame-lvl" . $level : "avatar-frame-none";
                            ?>
                            <div class="avatar-frame-container <?= $frameClass ?> size-xl" style="margin: 0;">
                                <?php if ($avatarUrl && file_exists($avatarUrl)): ?>
                                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile Picture" id="avatarPreview" class="avatar">
                                <?php else: ?>
                                    <div class="avatar-initials" id="avatarFallback"><?= htmlspecialchars($userInitial) ?></div>
                                    <img src="" alt="Profile Picture" id="avatarPreview" class="avatar" style="display:none; width:100%; height:100%; object-fit:cover;">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <label for="avatarInput" class="profile-upload-label">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <input type="file" id="avatarInput" name="avatar" accept="image/png,image/jpeg" class="profile-file-input">
                        
                        <h3 style="margin-top: 16px;"><?= htmlspecialchars($user['nama']) ?></h3>
                        <p style="text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-top: 4px; color: var(--text-secondary);">
                            <?= $userRole === 'pelajar' ? 'Student' : ($userRole === 'penganjur' ? 'Organizer' : 'Administrator') ?>
                        </p>
                    </div>

                    <?php if ($userRole === 'pelajar'): 
                        $prog = ProgressionService::getStudentProgression($userId, $user);
                        $lev = $prog['level'];
                        $levName = $prog['level_name'];
                        $str = $prog['streak'];
                        $att = $prog['attended_programs'];
                        $cr = $prog['crew_experience'];
                        $pts = (int)($user['mata'] ?? 0);
                        
                        // Fetch student earned badges
                        $badges = users()->getEarnedBadges($userId);
                    ?>
                        <div class="dashboard-card-wrap" style="padding: 20px; margin-bottom: 0;">
                            <h3 style="font-size: 15px; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 8px; margin-bottom: 12px; font-family:'Outfit';">Gamification Status</h3>
                            
                            <div style="display:flex; flex-direction:column; gap:12px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                                    <span style="color:var(--text-secondary); font-weight:500;">Current Rank</span>
                                    <span style="font-weight:800; color:var(--accent-blue);">Level <?= $lev ?> (<?= htmlspecialchars($levName) ?>)</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                                    <span style="color:var(--text-secondary); font-weight:500;">Active Streak</span>
                                    <span style="font-weight:800; color:#ea580c;"><i class="fas fa-fire"></i> <?= $str ?> month<?= $str == 1 ? '' : 's' ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                                    <span style="color:var(--text-secondary); font-weight:500;">Attended Programs</span>
                                    <span style="font-weight:800; color:var(--text-primary);"><?= $att ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                                    <span style="color:var(--text-secondary); font-weight:500;">Crew Assignments</span>
                                    <span style="font-weight:800; color:var(--text-primary);"><?= $cr ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                                    <span style="color:var(--text-secondary); font-weight:500;">Total Points</span>
                                    <span style="font-weight:800; color:var(--accent);"><?= $pts ?> Pts</span>
                                </div>
                                
                                <div style="border-top:1px dashed var(--border); padding-top:12px; margin-top:4px;">
                                    <h4 style="font-size: 12px; font-weight: 800; margin-bottom: 8px; color: var(--text-secondary);">Earned Badges</h4>
                                    <?php if (empty($badges)): ?>
                                        <span style="font-size: 11px; color: var(--text-muted); font-style: italic;">No badges earned yet.</span>
                                    <?php else: ?>
                                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                            <?php foreach ($badges as $badge): ?>
                                                <span style="font-size:10px; font-weight:800; background:rgba(37,99,235,0.08); color:var(--accent-blue); padding:3px 8px; border-radius:6px; border:1px solid rgba(37,99,235,0.15);" title="<?= htmlspecialchars($badge['description'] ?? '') ?>">
                                                    <i class="fas fa-medal" style="margin-right:3px;"></i> <?= htmlspecialchars($badge['nama']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT CARD: PROFILE DETAILS FORM -->
                <div class="dashboard-card-wrap" style="margin-bottom:0;">
                    <h2 style="font-size:22px; font-weight:800; margin-bottom:24px; border-bottom:1px solid var(--border); padding-bottom:14px;">Profile Information</h2>
                    
                    <div class="profile-form-grid">
                        <div class="form-group-profile">
                            <label for="nama">Full Name / Organization Name</label>
                            <input type="text" name="nama" id="nama" class="form-input-profile" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group-profile">
                            <label for="emel">Email Address <?= $userRole === 'pelajar' ? '(Read-Only)' : '' ?></label>
                            <input type="email" name="emel" id="emel" class="form-input-profile" value="<?= htmlspecialchars($user['emel'] ?? '') ?>" <?= $userRole === 'pelajar' ? 'disabled' : 'required' ?>>
                            <div style="margin-top: 6px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                Email Status: 
                                <?php if (($user['status'] ?? '') === 'aktif'): ?>
                                    <span style="color: #10b981; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-circle" style="font-size: 8px;"></i> Verified</span>
                                <?php else: ?>
                                    <span style="color: #ef4444; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-circle" style="font-size: 8px;"></i> Not Verified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($userRole === 'pelajar'): ?>
                            <!-- Student Specific Fields -->
                            <div class="form-group-profile">
                                <label for="matrik">Matric Number (Read-Only)</label>
                                <input type="text" id="matrik" class="form-input-profile" value="<?= htmlspecialchars($user['matrik'] ?? '') ?>" disabled>
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="fakulti">Faculty</label>
                                <select name="fakulti" id="fakulti" class="form-select-profile" required>
                                    <option value="">Choose Faculty</option>
                                    <option value="FST" <?= ($user['fakulti'] ?? '') === 'FST' ? 'selected' : '' ?>>FST - Science & Technology</option>
                                    <option value="FEP" <?= ($user['fakulti'] ?? '') === 'FEP' ? 'selected' : '' ?>>FEP - Economics & Management</option>
                                    <option value="FPEND" <?= ($user['fakulti'] ?? '') === 'FPEND' ? 'selected' : '' ?>>FPEND - Education</option>
                                    <option value="FPER" <?= ($user['fakulti'] ?? '') === 'FPER' ? 'selected' : '' ?>>FPER - Medicine</option>
                                    <option value="FP" <?= ($user['fakulti'] ?? '') === 'FP' ? 'selected' : '' ?>>FP - Dentistry</option>
                                    <option value="FSSK" <?= ($user['fakulti'] ?? '') === 'FSSK' ? 'selected' : '' ?>>FSSK - Social Sciences & Humanities</option>
                                    <option value="FTSM" <?= ($user['fakulti'] ?? '') === 'FTSM' ? 'selected' : '' ?>>FTSM - Information Science & Technology</option>
                                    <option value="FUU" <?= ($user['fakulti'] ?? '') === 'FUU' ? 'selected' : '' ?>>FUU - Law</option>
                                    <option value="FPSK" <?= ($user['fakulti'] ?? '') === 'FPSK' ? 'selected' : '' ?>>FPSK - Health Sciences</option>
                                    <option value="FKAB" <?= ($user['fakulti'] ?? '') === 'FKAB' ? 'selected' : '' ?>>FKAB - Fakulti Kejuruteraan dan Alam Bina</option>
                                </select>
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="tahun_pengajian">Year of Study</label>
                                <select name="tahun_pengajian" id="tahun_pengajian" class="form-select-profile" required>
                                    <option value="1" <?= (int)($user['tahun_pengajian'] ?? 0) === 1 ? 'selected' : '' ?>>Year 1</option>
                                    <option value="2" <?= (int)($user['tahun_pengajian'] ?? 0) === 2 ? 'selected' : '' ?>>Year 2</option>
                                    <option value="3" <?= (int)($user['tahun_pengajian'] ?? 0) === 3 ? 'selected' : '' ?>>Year 3</option>
                                    <option value="4" <?= (int)($user['tahun_pengajian'] ?? 0) === 4 ? 'selected' : '' ?>>Year 4</option>
                                </select>
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="kursus_pengajian">Course of Study</label>
                                <input type="text" name="kursus_pengajian" id="kursus_pengajian" class="form-input-profile" placeholder="e.g. Sains Komputer" value="<?= htmlspecialchars($user['kursus_pengajian'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="kolej">College</label>
                                <select name="kolej" id="kolej" class="form-select-profile" required>
                                    <option value="">Choose College</option>
                                    <option value="KDO" <?= ($user['kolej'] ?? '') === 'KDO' ? 'selected' : '' ?>>KDO - Kolej Dato' Onn</option>
                                    <option value="KPZ" <?= ($user['kolej'] ?? '') === 'KPZ' ? 'selected' : '' ?>>KPZ - Kolej Pendeta Za'ba</option>
                                    <option value="KTSN" <?= ($user['kolej'] ?? '') === 'KTSN' ? 'selected' : '' ?>>KTSN - Kolej Tun Syed Nasir</option>
                                    <option value="KKM" <?= ($user['kolej'] ?? '') === 'KKM' ? 'selected' : '' ?>>KKM - Kolej Keris Mas</option>
                                    <option value="KUO" <?= ($user['kolej'] ?? '') === 'KUO' ? 'selected' : '' ?>>KUO - Kolej Ungku Omar</option>
                                    <option value="KIZ" <?= ($user['kolej'] ?? '') === 'KIZ' ? 'selected' : '' ?>>KIZ - Kolej Ibu Zain</option>
                                    <option value="KAB" <?= ($user['kolej'] ?? '') === 'KAB' ? 'selected' : '' ?>>KAB - Kolej Aminuddin Baki</option>
                                    <option value="KBH" <?= ($user['kolej'] ?? '') === 'KBH' ? 'selected' : '' ?>>KBH - Kolej Burhanuddin Helmi</option>
                                    <option value="KRK" <?= ($user['kolej'] ?? '') === 'KRK' ? 'selected' : '' ?>>KRK - Kolej Rahim Kajai</option>
                                    <option value="Outside" <?= ($user['kolej'] ?? '') === 'Outside' ? 'selected' : '' ?>>Non-Resident / Outside Campus</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <!-- Organizer/Admin Specific Fields -->
                            <div class="form-group-profile">
                                <label for="fakulti">Faculty Representation (Optional)</label>
                                <select name="fakulti" id="fakulti" class="form-select-profile">
                                    <option value="">None / University Wide</option>
                                    <option value="FST" <?= ($user['fakulti'] ?? '') === 'FST' ? 'selected' : '' ?>>FST - Science & Technology</option>
                                    <option value="FEP" <?= ($user['fakulti'] ?? '') === 'FEP' ? 'selected' : '' ?>>FEP - Economics & Management</option>
                                    <option value="FPEND" <?= ($user['fakulti'] ?? '') === 'FPEND' ? 'selected' : '' ?>>FPEND - Education</option>
                                    <option value="FPER" <?= ($user['fakulti'] ?? '') === 'FPER' ? 'selected' : '' ?>>FPER - Medicine</option>
                                    <option value="FP" <?= ($user['fakulti'] ?? '') === 'FP' ? 'selected' : '' ?>>FP - Dentistry</option>
                                    <option value="FSSK" <?= ($user['fakulti'] ?? '') === 'FSSK' ? 'selected' : '' ?>>FSSK - Social Sciences & Humanities</option>
                                    <option value="FTSM" <?= ($user['fakulti'] ?? '') === 'FTSM' ? 'selected' : '' ?>>FTSM - Information Science & Technology</option>
                                    <option value="FUU" <?= ($user['fakulti'] ?? '') === 'FUU' ? 'selected' : '' ?>>FUU - Law</option>
                                    <option value="FPSK" <?= ($user['fakulti'] ?? '') === 'FPSK' ? 'selected' : '' ?>>FPSK - Health Sciences</option>
                                    <option value="FKAB" <?= ($user['fakulti'] ?? '') === 'FKAB' ? 'selected' : '' ?>>FKAB - Fakulti Kejuruteraan dan Alam Bina</option>
                                </select>
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="kolej">College Representation (Optional)</label>
                                <select name="kolej" id="kolej" class="form-select-profile">
                                    <option value="">None / University Wide</option>
                                    <option value="KDO" <?= ($user['kolej'] ?? '') === 'KDO' ? 'selected' : '' ?>>KDO - Kolej Dato' Onn</option>
                                    <option value="KPZ" <?= ($user['kolej'] ?? '') === 'KPZ' ? 'selected' : '' ?>>KPZ - Kolej Pendeta Za'ba</option>
                                    <option value="KTSN" <?= ($user['kolej'] ?? '') === 'KTSN' ? 'selected' : '' ?>>KTSN - Kolej Tun Syed Nasir</option>
                                    <option value="KKM" <?= ($user['kolej'] ?? '') === 'KKM' ? 'selected' : '' ?>>KKM - Kolej Keris Mas</option>
                                    <option value="KUO" <?= ($user['kolej'] ?? '') === 'KUO' ? 'selected' : '' ?>>KUO - Kolej Ungku Omar</option>
                                    <option value="KIZ" <?= ($user['kolej'] ?? '') === 'KIZ' ? 'selected' : '' ?>>KIZ - Kolej Ibu Zain</option>
                                    <option value="KAB" <?= ($user['kolej'] ?? '') === 'KAB' ? 'selected' : '' ?>>KAB - Kolej Aminuddin Baki</option>
                                    <option value="KBH" <?= ($user['kolej'] ?? '') === 'KBH' ? 'selected' : '' ?>>KBH - Kolej Burhanuddin Helmi</option>
                                    <option value="KRK" <?= ($user['kolej'] ?? '') === 'KRK' ? 'selected' : '' ?>>KRK - Kolej Rahim Kajai</option>
                                    <option value="KTDI" <?= ($user['kolej'] ?? '') === 'KTDI' ? 'selected' : '' ?>>KTDI - Kolej Tun Dr. Ismail</option>
                                    <option value="KTHO" <?= ($user['kolej'] ?? '') === 'KTHO' ? 'selected' : '' ?>>KTHO - Kolej Tun Hussein Onn</option>
                                    <option value="KIY" <?= ($user['kolej'] ?? '') === 'KIY' ? 'selected' : '' ?>>KIY - Kolej Ibrahim Yaakub</option>
                                    <option value="KOLEJ 13" <?= ($user['kolej'] ?? '') === 'KOLEJ 13' ? 'selected' : '' ?>>KOLEJ 13</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group-profile">
                            <label for="no_telefon">Phone Number</label>
                            <input type="text" name="no_telefon" id="no_telefon" class="form-input-profile" placeholder="e.g. 012-3456789" value="<?= htmlspecialchars($user['no_telefon'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group-profile profile-form-full">
                            <label for="bio">Biography / Description</label>
                            <textarea name="bio" id="bio" class="form-textarea-profile" placeholder="Tell us about yourself or your organization..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        
                        <?php if ($userRole === 'penganjur'): ?>
                            <!-- Organizer Social Links -->
                            <div class="profile-form-full">
                                <h3 style="font-size:16px; font-weight:800; margin-top:14px; margin-bottom:14px; color:var(--primary);">Social Media Links</h3>
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="facebook"><i class="fab fa-facebook" style="color:#1877f2; margin-right:4px;"></i> Facebook Link</label>
                                <input type="url" name="social_facebook" id="facebook" class="form-input-profile" placeholder="https://facebook.com/yourpage" value="<?= htmlspecialchars($socialLinks['facebook'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="instagram"><i class="fab fa-instagram" style="color:#e1306c; margin-right:4px;"></i> Instagram Link</label>
                                <input type="url" name="social_instagram" id="instagram" class="form-input-profile" placeholder="https://instagram.com/yourpage" value="<?= htmlspecialchars($socialLinks['instagram'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group-profile">
                                <label for="linkedin"><i class="fab fa-linkedin" style="color:#0077b5; margin-right:4px;"></i> LinkedIn Link</label>
                                <input type="url" name="social_linkedin" id="linkedin" class="form-input-profile" placeholder="https://linkedin.com/company/yourpage" value="<?= htmlspecialchars($socialLinks['linkedin'] ?? '') ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($userRole === 'pelajar'): ?>
                        <div class="profile-form-full" style="margin-top:24px; padding-top:24px; border-top:1px dashed var(--border);">
                            <h3 style="font-size:16px; font-weight:800; margin-bottom:8px; color:var(--primary);">Your Interests</h3>
                            <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">Select 1 to 3 co-curricular categories to receive accurate event recommendations.</p>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;" id="profileInterestsGrid">
                                <?php foreach ($interestCategories as $id => $data): ?>
                                    <?php $checked = in_array($id, $studentInterests); ?>
                                    <label style="border: 1px solid <?= $checked ? 'var(--accent-blue)' : 'var(--border)' ?>; border-radius: var(--radius-md); padding: 12px; background: <?= $checked ? '#eff6ff' : 'var(--white)' ?>; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: var(--transition); user-select: none;" class="profile-interest-card <?= $checked ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="<?= $id ?>" <?= $checked ? 'checked' : '' ?> style="display:none;">
                                        <div style="width: 32px; height: 32px; border-radius: var(--radius-sm); background: <?= $checked ? 'var(--accent-blue)' : '#eff6ff' ?>; color: <?= $checked ? 'var(--white)' : 'var(--accent-blue)' ?>; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; transition: var(--transition);" class="profile-interest-icon"><i class="fas <?= $data[1] ?>"></i></div>
                                        <span style="font-size: 13px; font-weight: <?= $checked ? '700' : '500' ?>; color: <?= $checked ? 'var(--accent-blue)' : 'var(--text-primary)' ?>;" class="profile-interest-text"><?= $data[0] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top: 12px;">
                                <span style="font-size: 13px; color: var(--text-secondary); font-weight: 600;" id="profileSelectedCount">
                                    <?= count($studentInterests) ?> interest(s) selected
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top:32px; display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn btn-primary" style="border-radius:999px; font-weight:800; padding:12px 30px;">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    document.getElementById('avatarInput').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatarPreview');
                const fallback = document.getElementById('avatarFallback');
                
                preview.src = e.target.result;
                preview.style.display = 'block';
                if (fallback) {
                    fallback.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        }
    });

    document.querySelectorAll('.profile-interest-card').forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault(); // PREVENT DOUBLE TOGGLE BY BROWSER
            
            const input = card.querySelector('input');
            const icon = card.querySelector('.profile-interest-icon');
            const text = card.querySelector('.profile-interest-text');
            const checkedCount = document.querySelectorAll('.profile-interest-card input:checked').length;
            
            if (!input.checked && checkedCount >= 3) {
                alert('You can only select up to 3 interests.');
                return;
            }
            
            input.checked = !input.checked;
            
            if (input.checked) {
                card.style.borderColor = 'var(--accent-blue)';
                card.style.background = '#eff6ff';
                icon.style.background = 'var(--accent-blue)';
                icon.style.color = 'var(--white)';
                text.style.color = 'var(--accent-blue)';
                text.style.fontWeight = '700';
            } else {
                card.style.borderColor = 'var(--border)';
                card.style.background = 'var(--white)';
                icon.style.background = '#eff6ff';
                icon.style.color = 'var(--accent-blue)';
                text.style.color = 'var(--text-primary)';
                text.style.fontWeight = '500';
            }
            
            const newCount = document.querySelectorAll('.profile-interest-card input:checked').length;
            document.getElementById('profileSelectedCount').textContent = newCount + ' interest(s) selected';
        });
    });

    const profileForm = document.querySelector('.profile-wrapper');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const interestInputs = document.querySelectorAll('.profile-interest-card input');
            if (interestInputs.length > 0) {
                const count = document.querySelectorAll('.profile-interest-card input:checked').length;
                if (count < 1) {
                    e.preventDefault();
                    alert('Please select at least 1 interest.');
                }
            }
        });
    }
    </script>
</body>
</html>

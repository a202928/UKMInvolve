<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');
$activePage = 'maklum-balas';

$programId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$studentId = $_SESSION['user_id'] ?? '';

$program = null;
if (db()->isConfigured() && $programId > 0) {
    $row = programs()->findById($programId);
    if ($row) {
        $program = programs()->toStudentDetail($row);
    }
}

if (!$program) {
    header('Location: rekod-penyertaan.php');
    exit();
}

if (!$program['is_completed']) {
    $_SESSION['error'] = 'You can only submit feedback after the programme has completed.';
    header('Location: rekod-penyertaan.php');
    exit();
}

// 1. Verify eligibility: Has student attended?
$attended = false;
if (db()->isConfigured() && $studentId) {
    $attCheck = db()->select(
        'kehadiran',
        '?program_id=eq.' . $programId . '&pelajar_id=eq.' . rawurlencode($studentId) . '&status=eq.Hadir'
    );
    if ($attCheck['ok'] && count($attCheck['data']) > 0) {
        $attended = true;
    }
}

if (!$attended) {
    $_SESSION['error'] = 'You can only submit feedback for programmes you have attended.';
    header('Location: rekod-penyertaan.php');
    exit();
}

// 2. Prevent duplicate feedback
$alreadySubmitted = false;
if (db()->isConfigured() && $studentId) {
    $fbCheck = db()->select(
        'maklum_balas',
        '?program_id=eq.' . $programId . '&pelajar_id=eq.' . rawurlencode($studentId)
    );
    if ($fbCheck['ok'] && count($fbCheck['data']) > 0) {
        $alreadySubmitted = true;
    }
}

if ($alreadySubmitted) {
    $_SESSION['error'] = 'You have already submitted feedback for this programme.';
    header('Location: rekod-penyertaan.php');
    exit();
}

$submitted = false;
$errors = [];

// 3. Retrieve student profile and check if demographic information is complete
$profileIncomplete = false;
$student = null;
if (db()->isConfigured() && $studentId) {
    $student = users()->findById($studentId);
    if (!$student || empty($student['tahun_pengajian']) || empty($student['kursus_pengajian'])) {
        $profileIncomplete = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    if ($profileIncomplete) {
        $errors[] = 'Please complete your profile (Year of Study and Course of Study) before submitting feedback.';
    } else {
        $objektif = isset($_POST['pencapaian_objektif']) ? (int)$_POST['pencapaian_objektif'] : 0;
        $pengurusan = isset($_POST['pengurusan_program']) ? (int)$_POST['pengurusan_program'] : 0;
        $kepuasan = isset($_POST['kepuasan_keseluruhan']) ? (int)$_POST['kepuasan_keseluruhan'] : 0;
        $diteruskan = trim($_POST['perlu_diteruskan'] ?? '');
        $cadangan = trim($_POST['cadangan_penambahbaikan'] ?? '');
        
        if ($objektif < 1 || $objektif > 5) {
            $errors[] = 'Penilaian objektif program (Section A) diperlukan.';
        }
        if ($pengurusan < 1 || $pengurusan > 5) {
            $errors[] = 'Penilaian pengurusan program (Section B) diperlukan.';
        }
        if ($kepuasan < 1 || $kepuasan > 5) {
            $errors[] = 'Penilaian kepuasan keseluruhan (Section C) diperlukan.';
        }
        if ($diteruskan !== 'Ya' && $diteruskan !== 'Tidak') {
            $errors[] = 'Sila pilih sama ada program perlu diteruskan (Section D).';
        }
        
        if (empty($errors)) {
            // Serialize response as JSON inside the komen column
            $consolidated = json_encode([
                'objektif_tercapai' => $objektif,
                'pengurusan_keseluruhan' => $pengurusan,
                'kepuasan_keseluruhan' => $kepuasan,
                'perlu_diteruskan' => $diteruskan,
                'cadangan_penambahbaikan' => $cadangan
            ], JSON_UNESCAPED_UNICODE);
            
            $fbResult = db()->insert('maklum_balas', [
                'program_id' => $programId,
                'pelajar_id' => $studentId,
                'rating' => $kepuasan, // Store Section C score as rating
                'komen' => $consolidated
            ]);
            
            if ($fbResult['ok']) {
                // Award points for feedback submission (+10 points)
                users()->awardPoints($studentId, 'feedback', $programId);
                $submitted = true;
            } else {
                $errors[] = 'Gagal menyimpan maklum balas: ' . ($fbResult['error'] ?? 'Ralat tidak diketahui.');
            }
        }
    }
}

$menu = [
    'dashboard_pelajar' => ['Home', 'fa-house'],
    'search' => ['Search', 'fa-magnifying-glass'],
    'recommended' => ['For You', 'fa-lightbulb'],
    'rekod-penyertaan' => ['History', 'fa-clock-rotate-left'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Feedback | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .star-rating {
            display: flex;
            gap: 12px;
            margin: 16px 0 8px;
        }
        .star-btn {
            background: none;
            border: none;
            font-size: 32px;
            color: var(--border);
            cursor: pointer;
            transition: var(--transition);
        }
        .star-btn:hover {
            transform: scale(1.15);
        }
        .star-btn.active i {
            color: #fbbf24;
        }
        .radio-group {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }
        .radio-option {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: var(--transition);
            background: var(--white);
            user-select: none;
        }
        .radio-option:hover {
            border-color: var(--accent-blue);
            background: var(--bg-secondary);
        }
        .radio-option.selected {
            background: #eff6ff;
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            font-weight: 700;
        }
        .radio-input {
            width: 18px;
            height: 18px;
            accent-color: var(--accent-blue);
        }
        .error-message {
            display: none;
            color: #ef4444;
            font-size: 13px;
            margin-top: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container" style="max-width: 800px;">
            <!-- HEADER -->
            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <h1>Programme Feedback</h1>
                    <p>Share your experience to help improve future UKM activities.</p>
                </div>
            </div>

            <!-- SUCCESS CARD -->
            <?php if ($submitted): ?>
                <div class="dashboard-card-wrap" style="text-align: center; padding: 60px 40px;">
                    <div style="font-size: 64px; color: #10b981; margin-bottom: 24px;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <h2 style="font-size: 28px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 12px;">Thank you for your feedback!</h2>
                    <p style="font-size: 18px; color: #10b981; font-weight: 800; margin-bottom: 24px;">+10 Bonus Points earned.</p>
                    <a href="rekod-penyertaan.php" class="btn btn-primary" style="border-radius: 999px;">
                        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to History
                    </a>
                </div>
            <?php elseif ($profileIncomplete): ?>
                <!-- INCOMPLETE PROFILE CARD -->
                <div class="dashboard-card-wrap" style="text-align: center; padding: 60px 40px; border: 1px solid #fecaca; background: #fef2f2;">
                    <div style="font-size: 64px; color: #ef4444; margin-bottom: 24px;">
                        <i class="fas fa-circle-exclamation"></i>
                    </div>
                    <h2 style="font-size: 24px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 12px; color: #991b1b;">Profile Incomplete</h2>
                    <p style="font-size: 15px; color: #7f1d1d; max-width: 500px; margin: 0 auto 32px; line-height: 1.6;">Please complete your profile (Year of Study and Course of Study) before submitting feedback.</p>
                    <a href="profile.php" class="btn btn-primary" style="border-radius: 999px;">
                        <i class="fas fa-user-gear" style="margin-right: 8px;"></i> Edit Profile
                    </a>
                </div>
            <?php else: ?>
                <!-- PROGRAM SUMMARY -->
                <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: var(--white); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 32px; box-shadow: var(--shadow-md);">
                    <h3 style="font-size: 20px; font-weight: 800; font-family: 'Outfit'; margin-bottom: 12px; color: var(--white);"><?= htmlspecialchars($program['title'] ?? 'Programme') ?></h3>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 13px; color: var(--text-muted);">
                        <span><i class="far fa-calendar-alt" style="margin-right: 6px;"></i><?= htmlspecialchars($program['date'] ?? '') ?></span>
                        <span><i class="fas fa-map-marker-alt" style="margin-right: 6px;"></i><?= htmlspecialchars($program['location'] ?? '') ?></span>
                    </div>
                </div>

                <!-- ERROR LIST -->
                <?php if (!empty($errors)): ?>
                    <div class="alert-banner alert-banner-error" style="margin-bottom: 24px;">
                        <ul style="list-style: none; margin: 0; padding: 0;">
                            <?php foreach ($errors as $err): ?>
                                <li style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- FEEDBACK FORM -->
                <form method="POST" class="dashboard-card-wrap" id="feedbackForm">
                    <!-- SECTION A -->
                    <div style="padding-bottom: 24px; border-bottom: 1px solid var(--border); margin-bottom: 24px;">
                        <label style="font-size: 16px; font-weight: 800; color: var(--text-primary);">SECTION A: Adakah objektif program ini tercapai? <span style="color:#ef4444;">*</span></label>
                        <div class="star-rating" data-input="pencapaian_objektif">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <button type="button" class="star-btn" data-value="<?= $i ?>"><i class="fas fa-star"></i></button>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); font-weight: 700; margin-top: 4px;">
                            <span>1 - Sangat Tidak Setuju</span>
                            <span>3 - Neutral</span>
                            <span>5 - Sangat Setuju</span>
                        </div>
                        <input type="hidden" name="pencapaian_objektif" id="pencapaian_objektif" value="0">
                        <div class="error-message">Penilaian objektif program diperlukan.</div>
                    </div>

                    <!-- SECTION B -->
                    <div style="padding-bottom: 24px; border-bottom: 1px solid var(--border); margin-bottom: 24px;">
                        <label style="font-size: 16px; font-weight: 800; color: var(--text-primary);">SECTION B: Bagaimana anda menilai pengurusan program secara keseluruhan? <span style="color:#ef4444;">*</span></label>
                        <div class="star-rating" data-input="pengurusan_program">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <button type="button" class="star-btn" data-value="<?= $i ?>"><i class="fas fa-star"></i></button>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); font-weight: 700; margin-top: 4px;">
                            <span>1 - Sangat Tidak Setuju</span>
                            <span>3 - Neutral</span>
                            <span>5 - Sangat Setuju</span>
                        </div>
                        <input type="hidden" name="pengurusan_program" id="pengurusan_program" value="0">
                        <div class="error-message">Penilaian pengurusan program diperlukan.</div>
                    </div>

                    <!-- SECTION C -->
                    <div style="padding-bottom: 24px; border-bottom: 1px solid var(--border); margin-bottom: 24px;">
                        <label style="font-size: 16px; font-weight: 800; color: var(--text-primary);">SECTION C: Adakah anda berpuas hati dengan pengalaman keseluruhan program ini? <span style="color:#ef4444;">*</span></label>
                        <div class="star-rating" data-input="kepuasan_keseluruhan">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <button type="button" class="star-btn" data-value="<?= $i ?>"><i class="fas fa-star"></i></button>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); font-weight: 700; margin-top: 4px;">
                            <span>1 - Sangat Tidak Setuju</span>
                            <span>3 - Neutral</span>
                            <span>5 - Sangat Setuju</span>
                        </div>
                        <input type="hidden" name="kepuasan_keseluruhan" id="kepuasan_keseluruhan" value="0">
                        <div class="error-message">Penilaian kepuasan keseluruhan diperlukan.</div>
                    </div>

                    <!-- SECTION D -->
                    <div style="padding-bottom: 24px; border-bottom: 1px solid var(--border); margin-bottom: 24px;">
                        <label style="font-size: 16px; font-weight: 800; color: var(--text-primary);">SECTION D: Adakah program seperti ini perlu diteruskan pada masa akan datang? <span style="color:#ef4444;">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="perlu_diteruskan" value="Ya" class="radio-input">
                                Ya
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="perlu_diteruskan" value="Tidak" class="radio-input">
                                Tidak
                            </label>
                        </div>
                        <div class="error-message">Sila pilih sama ada program perlu diteruskan.</div>
                    </div>

                    <!-- SECTION E -->
                    <div style="margin-bottom: 32px;">
                        <label style="font-size: 16px; font-weight: 800; color: var(--text-primary);">SECTION E: Cadangan Penambahbaikan</label>
                        <textarea name="cadangan_penambahbaikan" style="width: 100%; min-height: 120px; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px; margin-top: 12px; outline: none; font-size: 14px; resize: vertical; transition: var(--transition);" placeholder="Berikan cadangan penambahbaikan anda..." onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--border)';"></textarea>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <button type="submit" name="submit_feedback" class="btn btn-primary" style="width: 100%; border-radius: 999px; font-weight: 800; padding: 14px;">
                        <i class="fas fa-paper-plane" style="margin-right: 6px;"></i> Submit Feedback
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

    <script>
    document.querySelectorAll('.star-rating').forEach(group => {
        const input = document.getElementById(group.dataset.input);
        const stars = group.querySelectorAll('.star-btn');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = parseInt(star.dataset.value);
                input.value = value;

                stars.forEach(s => {
                    s.classList.toggle('active', parseInt(s.dataset.value) <= value);
                });

                group.parentElement.querySelector('.error-message').style.display = 'none';
            });
        });
    });

    document.querySelectorAll('.radio-option').forEach(option => {
        option.addEventListener('click', () => {
            const group = option.parentElement;
            group.querySelectorAll('.radio-option').forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            option.querySelector('input').checked = true;
            group.parentElement.querySelector('.error-message').style.display = 'none';
        });
    });

    const form = document.getElementById('feedbackForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let valid = true;

            document.querySelectorAll('.question-group, form > div').forEach(group => {
                const hidden = group.querySelector('input[type="hidden"]');
                const radios = group.querySelectorAll('input[type="radio"]');
                const error = group.querySelector('.error-message');

                if (hidden && hidden.value === '0') {
                    if (error) error.style.display = 'block';
                    valid = false;
                }

                if (radios.length > 0) {
                    const checked = group.querySelector('input[type="radio"]:checked');
                    if (!checked) {
                        if (error) error.style.display = 'block';
                        valid = false;
                    }
                }
            });

            if (!valid) {
                e.preventDefault();
                const firstErr = document.querySelector('.error-message[style*="block"]');
                if (firstErr) {
                    firstErr.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
    }
    </script>
</body>
</html>
<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'maklum-balas';

$programName = $_GET['program'] ?? 'Gotong-Royong Kampus';
$programDate = $_GET['date'] ?? '18 Januari 2026';
$programLocation = $_GET['location'] ?? 'Kawasan Kolej';
$submitted = isset($_POST['submit_feedback']);

$menu = [
    'dashboard_pelajar' => ['Home', 'fa-house'],
    'search' => ['Search', 'fa-magnifying-glass'],
    'recommended' => ['For You', 'fa-lightbulb'],
    'rekod-penyertaan' => ['History', 'fa-clock-rotate-left'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Feedback | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;
    --muted:#6b7280;--text:#111827;--green:#10b981;--red:#ef4444;
    --orange:#f97316;--yellow:#f59e0b;
}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button,input,textarea{font-family:inherit}

.dashboard-wrapper{height:100vh;display:grid;grid-template-columns:240px 1fr;background:var(--page);overflow:hidden}

/* SIDEBAR */
.sidebar{height:100vh;background:#fff;border-right:1px solid var(--border);padding:28px 20px;display:flex;flex-direction:column;justify-content:space-between}
.sidebar-header{display:flex;align-items:center;gap:12px;margin-bottom:30px}
.sidebar-logo-wrap{width:38px;height:38px;border-radius:14px;background:#eaf4ff;display:flex;align-items:center;justify-content:center}
.sidebar-logo{width:28px;height:28px;object-fit:contain}
.sidebar-title{font-size:19px;font-weight:800}
.sidebar-label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;padding-left:8px}
.sidebar-nav{display:flex;flex-direction:column;gap:8px}
.sidebar-link{padding:11px 12px;border-radius:14px;display:flex;gap:12px;align-items:center;color:#374151;font-weight:500;transition:.25s}
.sidebar-link i{width:18px;text-align:center}
.sidebar-link.active,.sidebar-link:hover{background:#eff6ff;color:#2563eb;font-weight:700}
.logout-link{color:#f97316}
.logout-link:hover{background:#fff7ed;color:#f97316}
.user-profile{display:flex;align-items:center;gap:10px;background:#f8fbff;border:1px solid var(--border);border-radius:16px;padding:12px}
.user-avatar{width:38px;height:38px;border-radius:50%;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800}
.user-profile h4{font-size:14px}
.user-profile p{font-size:12px;color:var(--muted)}

/* MAIN */
.main-section{height:100vh;overflow-y:auto;padding:28px;background:var(--page)}
.main-section::-webkit-scrollbar{width:8px}
.main-section::-webkit-scrollbar-thumb{background:#bfdbfe;border-radius:999px}

.feedback-container{max-width:860px;margin:0 auto}
.page-header{margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{color:var(--muted);font-size:14px;margin-top:4px}

.program-card{
    background:linear-gradient(135deg,#7bb6ff,#5b8def);
    color:white;
    border-radius:26px;
    padding:24px;
    margin-bottom:22px;
    box-shadow:0 18px 38px rgba(91,141,239,.20);
}
.program-card h3{font-size:22px;margin-bottom:12px}
.program-meta{display:flex;gap:18px;flex-wrap:wrap;font-size:14px;color:#eef6ff}
.program-meta i{margin-right:6px}

.form-card{
    background:white;
    border:1px solid var(--border);
    border-radius:26px;
    padding:26px;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
}
.question-group{padding:22px 0;border-bottom:1px solid var(--border)}
.question-group:first-child{padding-top:0}
.question-group:last-child{border-bottom:none}
.question-label{font-size:16px;font-weight:800;margin-bottom:14px;display:block}
.required{color:var(--red)}

.star-rating{display:flex;gap:10px;margin:12px 0}
.star-btn{background:none;border:none;font-size:30px;color:#d1d5db;cursor:pointer;transition:.2s}
.star-btn:hover{transform:scale(1.12)}
.star-btn.active i{color:var(--yellow)}
.rating-labels{display:flex;justify-content:space-between;font-size:12px;color:var(--muted)}

.radio-group{display:grid;gap:10px;margin-top:12px}
.radio-option{
    border:1px solid var(--border);
    border-radius:16px;
    padding:13px 15px;
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    transition:.25s;
}
.radio-option:hover,.radio-option.selected{background:#eff6ff;border-color:var(--primary);color:var(--dark);font-weight:700}
.radio-input{width:17px;height:17px}

.feedback-textarea{
    width:100%;
    min-height:130px;
    resize:vertical;
    border:1px solid var(--border);
    border-radius:18px;
    padding:16px;
    font-size:14px;
    outline:none;
    margin-top:12px;
}
.feedback-textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(91,141,239,.12)}

.error-message{display:none;color:var(--red);font-size:13px;margin-top:8px}
.btn-submit{
    width:100%;
    border:none;
    background:var(--primary);
    color:white;
    padding:15px;
    border-radius:999px;
    font-size:15px;
    font-weight:800;
    cursor:pointer;
    margin-top:22px;
}
.btn-submit:hover{background:var(--dark)}

.success-card{
    background:white;
    border:1px solid var(--border);
    border-radius:26px;
    padding:60px 35px;
    text-align:center;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
}
.success-icon{font-size:64px;color:var(--green);margin-bottom:18px}
.success-card h2{font-size:28px;margin-bottom:10px}
.success-card p{color:var(--muted);line-height:1.6;margin-bottom:22px}
.btn-back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:var(--primary);
    color:white;
    padding:12px 24px;
    border-radius:999px;
    font-weight:800;
}

@media(max-width:900px){
    body{overflow:auto}
    .dashboard-wrapper{grid-template-columns:1fr;height:auto}
    .sidebar{height:auto;position:relative;border-right:none;border-bottom:1px solid var(--border)}
    .sidebar-nav{flex-direction:row;overflow-x:auto}
    .sidebar-link{white-space:nowrap}
    .user-profile{display:none}
    .main-section{height:auto;overflow:visible}
}
</style>
</head>

<body>
<div class="dashboard-wrapper">

<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <div class="sidebar-logo-wrap">
                <img src="UKM.png" class="sidebar-logo" alt="UKM">
            </div>
            <h3 class="sidebar-title">UKMInvolve</h3>
        </div>

        <p class="sidebar-label">Menu</p>
        <nav class="sidebar-nav">
            <?php foreach ($menu as $page => $item): ?>
                <a href="<?= $page ?>.php"
                   class="sidebar-link <?= ($activePage === $page) ? 'active' : '' ?> <?= ($page === 'logout') ? 'logout-link' : '' ?>">
                    <i class="fas <?= $item[1] ?>"></i>
                    <?= $item[0] ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="user-profile">
        <div class="user-avatar">P</div>
        <div>
            <h4>Pelajar</h4>
            <p>UKM Account</p>
        </div>
    </div>
</aside>

<main class="main-section">
<div class="feedback-container">

<?php if ($submitted): ?>

    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Thank You!</h2>
        <p>Your feedback has been submitted successfully and will help improve future programmes.</p>
        <a href="rekod-penyertaan.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to History
        </a>
    </div>

<?php else: ?>

    <div class="page-header">
        <h1>Feedback</h1>
        <p>Share your experience and help improve future UKMInvolve events.</p>
    </div>

    <div class="program-card">
        <h3><?= htmlspecialchars($programName) ?></h3>
        <div class="program-meta">
            <span><i class="fas fa-calendar"></i><?= htmlspecialchars($programDate) ?></span>
            <span><i class="fas fa-location-dot"></i><?= htmlspecialchars($programLocation) ?></span>
        </div>
    </div>

    <form method="POST" class="form-card" id="feedbackForm">

        <div class="question-group">
            <label class="question-label">1. Overall rating for this programme <span class="required">*</span></label>
            <div class="star-rating" data-input="penilaian_keseluruhan">
                <?php for($i=1; $i<=5; $i++): ?>
                    <button type="button" class="star-btn" data-value="<?= $i ?>"><i class="fas fa-star"></i></button>
                <?php endfor; ?>
            </div>
            <div class="rating-labels">
                <span>Very Poor</span><span>Excellent</span>
            </div>
            <input type="hidden" name="penilaian_keseluruhan" id="penilaian_keseluruhan" value="0">
            <div class="error-message">Please give a rating</div>
        </div>

        <div class="question-group">
            <label class="question-label">2. Were the programme objectives achieved? <span class="required">*</span></label>
            <div class="radio-group">
                <?php foreach(['Strongly Disagree','Disagree','Neutral','Agree','Strongly Agree'] as $option): ?>
                    <label class="radio-option">
                        <input type="radio" name="pencapaian_objektif" value="<?= $option ?>" class="radio-input">
                        <?= $option ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="error-message">Please choose one option</div>
        </div>

        <div class="question-group">
            <label class="question-label">3. How was the programme management? <span class="required">*</span></label>
            <div class="star-rating" data-input="pengurusan_program">
                <?php for($i=1; $i<=5; $i++): ?>
                    <button type="button" class="star-btn" data-value="<?= $i ?>"><i class="fas fa-star"></i></button>
                <?php endfor; ?>
            </div>
            <div class="rating-labels">
                <span>Poor</span><span>Excellent</span>
            </div>
            <input type="hidden" name="pengurusan_program" id="pengurusan_program" value="0">
            <div class="error-message">Please give a rating</div>
        </div>

        <div class="question-group">
            <label class="question-label">4. Was the programme beneficial to you? <span class="required">*</span></label>
            <div class="radio-group">
                <?php foreach(['Not Beneficial','Less Beneficial','Neutral','Beneficial','Very Beneficial'] as $option): ?>
                    <label class="radio-option">
                        <input type="radio" name="kualiti_aktiviti" value="<?= $option ?>" class="radio-input">
                        <?= $option ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="error-message">Please choose one option</div>
        </div>

        <div class="question-group">
            <label class="question-label">5. Time management and programme flow <span class="required">*</span></label>
            <div class="star-rating" data-input="pengurusan_masa">
                <?php for($i=1; $i<=5; $i++): ?>
                    <button type="button" class="star-btn" data-value="<?= $i ?>"><i class="fas fa-star"></i></button>
                <?php endfor; ?>
            </div>
            <div class="rating-labels">
                <span>Not Organized</span><span>Very Organized</span>
            </div>
            <input type="hidden" name="pengurusan_masa" id="pengurusan_masa" value="0">
            <div class="error-message">Please give a rating</div>
        </div>

        <div class="question-group">
            <label class="question-label">6. Comments or suggestions</label>
            <textarea name="komen" class="feedback-textarea" placeholder="Example: The programme was useful, but the activities could be more interactive..."></textarea>
            <p style="font-size:12px;color:var(--muted);margin-top:8px;">Optional but highly appreciated.</p>
        </div>

        <button type="submit" name="submit_feedback" class="btn-submit">
            <i class="fas fa-paper-plane"></i> Submit Feedback
        </button>

    </form>

<?php endif; ?>

</div>
</main>
</div>

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

        document.querySelectorAll('.question-group').forEach(group => {
            const hidden = group.querySelector('input[type="hidden"]');
            const radios = group.querySelectorAll('input[type="radio"]');
            const error = group.querySelector('.error-message');

            if (hidden && hidden.value === '0') {
                error.style.display = 'block';
                valid = false;
            }

            if (radios.length > 0) {
                const checked = group.querySelector('input[type="radio"]:checked');
                if (!checked) {
                    error.style.display = 'block';
                    valid = false;
                }
            }
        });

        if (!valid) {
            e.preventDefault();
            document.querySelector('.error-message[style*="block"]').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });
}
</script>

</body>
</html>
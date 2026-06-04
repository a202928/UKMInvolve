<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'urus-program';

$editId = (int) ($_GET['id'] ?? 0);
$dbCategories = categories()->listAll();

if (!db()->isConfigured() || $editId <= 0) {
    die('Program tidak ditemui atau pangkalan data belum dikonfigurasi.');
}

$row = programs()->findById($editId);
if (!$row) {
    die('Program tidak ditemui!');
}

$program = programs()->toEditForm($row);
$updated = false;
$errorMessage = "";
$newPosterPath = $program['poster'];

$uploadDir = 'uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (isset($_POST['update_program'])) {

    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = basename($_FILES['poster']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExt = ['jpg', 'jpeg', 'png'];
        $allowedMime = ['image/jpeg', 'image/png'];
        $fileMime = mime_content_type($fileTmpPath);

        if (in_array($fileExt, $allowedExt) && in_array($fileMime, $allowedMime)) {
            $newFileName = uniqid('poster_', true) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $newPosterPath = $destination;
            } else {
                $errorMessage = "Poster gagal dimuat naik.";
            }
        } else {
            $errorMessage = "Sila muat naik poster dalam format PNG, JPG atau JPEG sahaja.";
        }
    }

    if (!$errorMessage) {
        $category = categories()->findByName($_POST['kategori'] ?? $program['kategori']);
        $updateData = [
            'nama' => trim($_POST['nama_program'] ?? $program['nama']),
            'tarikh' => $_POST['tarikh'] ?? $program['tarikh'],
            'masa' => ($_POST['masa'] ?? $program['masa']) . (strlen($_POST['masa'] ?? '') === 5 ? ':00' : ''),
            'lokasi' => trim($_POST['lokasi'] ?? $program['lokasi']),
            'kategori_id' => $category['id'] ?? null,
            'kapasiti' => (int) ($_POST['kapasiti'] ?? $program['kapasiti']),
            'penerangan' => trim($_POST['penerangan'] ?? $program['penerangan']),
            'poster_url' => $newPosterPath,
        ];

        $saveResult = programs()->update($editId, $updateData);
        if ($saveResult['ok']) {
            $program = programs()->toEditForm($saveResult['data'][0] ?? array_merge($program, $updateData));
            $updated = true;
        } else {
            $errorMessage = 'Kemaskini gagal: ' . ($saveResult['error'] ?? 'Ralat pangkalan data');
        }
    }
}

$menu = [
    'dashboard_penganjur' => ['Dashboard', 'fa-house'],
    'hebahan-program' => ['Hebahan', 'fa-bullhorn'],
    'urus-program' => ['Urus Program', 'fa-calendar-check'],
    'peserta-kehadiran' => ['Peserta', 'fa-users'],
    'laporan-statistik' => ['Laporan', 'fa-chart-column'],
    'logout' => ['Logout', 'fa-right-from-bracket']
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Edit Program | UKMInvolve</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --page:#f8fbff;--primary:#5b8def;--dark:#2563eb;--border:#dbeafe;
    --muted:#6b7280;--text:#111827;--green:#10b981;--orange:#f97316;
    --red:#ef4444;
}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fbff;color:var(--text);height:100vh;overflow:hidden}
a{text-decoration:none;color:inherit}
button,input,textarea,select{font-family:inherit}

.dashboard-wrapper{height:100vh;display:grid;grid-template-columns:240px 1fr;background:var(--page);overflow:hidden}

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

.main-section{height:100vh;overflow-y:auto;padding:28px;background:var(--page)}
.main-section::-webkit-scrollbar{width:8px}
.main-section::-webkit-scrollbar-thumb{background:#bfdbfe;border-radius:999px}

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h1{font-size:30px}
.page-header p{color:var(--muted);font-size:14px;margin-top:4px}

.form-layout{display:grid;grid-template-columns:.9fr 1.3fr;gap:22px}
.card{background:white;border:1px solid var(--border);border-radius:26px;padding:24px;box-shadow:0 8px 20px rgba(37,99,235,.06)}

.poster-card{
    background:linear-gradient(135deg,#7bb6ff,#5b8def);
    color:white;
    border-radius:26px;
    padding:24px;
    box-shadow:0 18px 38px rgba(91,141,239,.20);
}
.poster-card h2{font-size:22px;margin-bottom:8px}
.poster-card p{font-size:13px;color:#eef6ff;margin-bottom:18px}

.poster-preview{
    width:100%;
    height:430px;
    background:rgba(255,255,255,.20);
    border:2px dashed rgba(255,255,255,.50);
    border-radius:24px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    margin-bottom:16px;
}
.poster-preview img{width:100%;height:100%;object-fit:cover}
.poster-placeholder i{font-size:46px;margin-bottom:12px}

.upload-btn{
    width:100%;
    background:#111827;
    color:white;
    border:none;
    padding:13px 16px;
    border-radius:999px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
}

.form-title{font-size:22px;font-weight:800;margin-bottom:6px}
.form-subtitle{font-size:13px;color:var(--muted);margin-bottom:22px}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:8px}
.form-full{grid-column:1/-1}
.form-label{font-size:13px;font-weight:800}
.required{color:var(--red)}
.form-input,.form-select,.form-textarea{
    width:100%;
    border:1px solid var(--border);
    border-radius:16px;
    padding:13px 15px;
    font-size:14px;
    outline:none;
    background:white;
}
.form-input:focus,.form-select:focus,.form-textarea:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(91,141,239,.12);
}
.form-textarea{min-height:130px;resize:vertical}

.form-actions{display:flex;gap:12px;margin-top:22px}
.btn-outline,.btn-primary{
    flex:1;
    border-radius:999px;
    padding:13px 16px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}
.btn-outline{background:white;border:1px solid var(--border);color:#374151}
.btn-primary{background:var(--primary);border:none;color:white}
.btn-primary:hover{background:var(--dark)}

.error-box{
    background:#fef2f2;
    color:#dc2626;
    border:1px solid #fecaca;
    padding:12px 14px;
    border-radius:16px;
    font-size:13px;
    margin-bottom:16px;
}

.success-card{
    max-width:650px;
    margin:40px auto;
    background:white;
    border:1px solid var(--border);
    border-radius:26px;
    padding:35px;
    text-align:center;
    box-shadow:0 8px 20px rgba(37,99,235,.06);
}
.success-icon{font-size:58px;color:var(--green);margin-bottom:14px}
.success-card h2{font-size:26px;margin-bottom:8px}
.success-card p{color:var(--muted);font-size:14px;margin-bottom:20px}
.success-poster{max-width:320px;width:100%;border-radius:20px;margin:14px auto 22px;display:block;border:1px solid var(--border)}

@media(max-width:1000px){
    .form-layout{grid-template-columns:1fr}
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
@media(max-width:600px){
    .form-grid{grid-template-columns:1fr}
    .page-header{flex-direction:column;align-items:flex-start;gap:10px}
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
            <h4>Penganjur</h4>
            <p>UKM Account</p>
        </div>
    </div>
</aside>

<main class="main-section">

<?php if ($updated): ?>

    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h2>Program Updated!</h2>
        <p>Your programme information has been successfully updated.</p>

        <?php if ($newPosterPath): ?>
            <img src="<?= htmlspecialchars($newPosterPath) ?>" class="success-poster" alt="Updated Poster">
        <?php endif; ?>

        <div class="form-actions">
            <a href="urus-program.php" class="btn-primary">
                <i class="fas fa-calendar-check"></i> Back to Manage Programmes
            </a>

            <a href="edit-program.php?id=<?= $program['id'] ?>" class="btn-outline">
                <i class="fas fa-edit"></i> Edit Again
            </a>
        </div>
    </div>

<?php else: ?>

    <div class="page-header">
        <div>
            <h1>Edit Programme</h1>
            <p>Update programme details and replace the poster with a real PNG/JPG file.</p>
        </div>
    </div>

    <?php if ($errorMessage): ?>
        <div class="error-box">
            <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-layout">

        <div class="poster-card">
            <h2>Programme Poster</h2>
            <p>Current poster will be shown here. You may upload a new PNG/JPG poster.</p>

            <div class="poster-preview">
                <?php if (!empty($program['poster'])): ?>
                    <img id="posterPreview" src="<?= htmlspecialchars($program['poster']) ?>" alt="Poster Program">
                <?php else: ?>
                    <div class="poster-placeholder" id="posterPlaceholder">
                        <i class="fas fa-image"></i>
                        <p>No poster available</p>
                    </div>
                    <img id="posterPreview" style="display:none;" alt="Poster Preview">
                <?php endif; ?>
            </div>

            <label for="posterInput" class="upload-btn">
                <i class="fas fa-upload"></i> Change Poster PNG/JPG
            </label>

            <input type="file" id="posterInput" name="poster" accept="image/png,image/jpeg" hidden>
        </div>

        <div class="card">
            <h2 class="form-title">Programme Details</h2>
            <p class="form-subtitle">Update the information below before saving changes.</p>

            <div class="form-grid">
                <div class="form-group form-full">
                    <label class="form-label">Programme Name <span class="required">*</span></label>
                    <input type="text" name="nama_program" class="form-input" value="<?= htmlspecialchars($program['nama']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Category <span class="required">*</span></label>
                    <select name="kategori" class="form-select" required>
                        <?php foreach ($dbCategories as $cat):
                            $selected = ($program['kategori'] ?? '') === $cat['nama'] ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($cat['nama']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($cat['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Capacity <span class="required">*</span></label>
                    <input type="number" name="kapasiti" class="form-input" min="1" value="<?= htmlspecialchars($program['kapasiti']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Date <span class="required">*</span></label>
                    <input type="date" name="tarikh" class="form-input" value="<?= htmlspecialchars($program['tarikh']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Time <span class="required">*</span></label>
                    <input type="time" name="masa" class="form-input" value="<?= htmlspecialchars($program['masa']) ?>" required>
                </div>

                <div class="form-group form-full">
                    <label class="form-label">Location <span class="required">*</span></label>
                    <input type="text" name="lokasi" class="form-input" value="<?= htmlspecialchars($program['lokasi']) ?>" required>
                </div>

                <div class="form-group form-full">
                    <label class="form-label">Programme Description</label>
                    <textarea name="penerangan" class="form-textarea"><?= htmlspecialchars($program['penerangan']) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="urus-program.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>

                <button type="submit" name="update_program" class="btn-primary">
                    <i class="fas fa-edit"></i> Update Programme
                </button>
            </div>
        </div>

    </form>

<?php endif; ?>

</main>
</div>

<script>
const posterInput = document.getElementById('posterInput');
const posterPreview = document.getElementById('posterPreview');

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
        };

        reader.readAsDataURL(file);
    });
}
</script>

</body>
</html>
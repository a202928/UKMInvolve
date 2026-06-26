<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('penganjur');
$activePage = 'urus-program';

$programId = (int)($_GET['id'] ?? 0);
if ($programId <= 0) {
    header('Location: urus-program.php');
    exit();
}

$programRow = programs()->findById($programId);
if (!$programRow || $programRow['penganjur_id'] !== $_SESSION['user_id']) {
    header('Location: urus-program.php');
    exit();
}

$program = programs()->toEditForm($programRow);

$tab = $_GET['tab'] ?? 'announcements';

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
    <title>Program Hub - <?= htmlspecialchars($program['nama']) ?> | UKMInvolve</title>
    <link rel="stylesheet" href="public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="dashboard-section">
        <div class="container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-banner alert-banner-success" style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-banner alert-banner-error" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
                    <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-header-container">
                <div class="dashboard-header-title">
                    <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 700; text-transform: uppercase;">Program Hub</div>
                    <h1 style="font-size: 24px;"><?= htmlspecialchars($program['nama']) ?></h1>
                </div>
                <a href="urus-program.php" class="btn btn-outline" style="border-radius: 999px;">
                    <i class="fas fa-arrow-left"></i> Back to Manage
                </a>
            </div>

            <!-- TABS -->
            <div class="tab-nav-wrapper" style="margin-bottom: 24px;">
                <a href="?id=<?= $programId ?>&tab=announcements" class="tab-nav-btn <?= $tab === 'announcements' ? 'active' : '' ?>"><i class="fas fa-bullhorn"></i> Announcements</a>
                <a href="?id=<?= $programId ?>&tab=files" class="tab-nav-btn <?= $tab === 'files' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Files</a>
                <a href="?id=<?= $programId ?>&tab=participants" class="tab-nav-btn <?= $tab === 'participants' ? 'active' : '' ?>"><i class="fas fa-users"></i> Participants</a>
                <a href="?id=<?= $programId ?>&tab=discussion" class="tab-nav-btn <?= $tab === 'discussion' ? 'active' : '' ?>"><i class="fas fa-comments"></i> Discussion Board</a>
            </div>

            <div class="dashboard-card" style="padding: 24px;">
                <?php if ($tab === 'announcements'): ?>
                    <!-- ANNOUNCEMENTS SECTION -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2 style="font-size: 18px; font-weight: 800;">Send Announcement</h2>
                    </div>

                    <form action="hub-actions.php" method="POST" style="margin-bottom: 32px; background: var(--bg-secondary); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <input type="hidden" name="action" value="add_announcement">
                        <input type="hidden" name="program_id" value="<?= $programId ?>">
                        <div class="form-group-profile" style="margin-bottom: 16px;">
                            <label>Message</label>
                            <textarea name="message" class="form-textarea-profile" placeholder="Write your update here..." required style="min-height: 100px;"></textarea>
                        </div>
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <div style="flex: 1;">
                                <label style="font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; display: block;">Target Audience</label>
                                <select name="target_audience" class="form-select-profile">
                                    <option value="All">All (Participants & Crew)</option>
                                    <option value="Participant">Participants Only</option>
                                    <option value="Crew">Crew Only</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-top: 22px;">Send Announcement</button>
                        </div>
                    </form>

                    <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 16px;">Previous Announcements</h3>
                    <?php
                    $announcements = db()->select('program_announcements', '?program_id=eq.' . $programId . '&order=created_at.desc');
                    if ($announcements['ok'] && count($announcements['data']) > 0):
                        foreach ($announcements['data'] as $ann):
                    ?>
                        <div style="padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 12px; background: var(--white);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 700; color: var(--accent-blue); font-size: 13px;">To: <?= htmlspecialchars($ann['target_audience']) ?></span>
                                <span style="font-size: 12px; color: var(--text-secondary);"><i class="far fa-clock"></i> <?= date('j M Y, h:i A', strtotime($ann['created_at'])) ?></span>
                            </div>
                            <p style="font-size: 14px; color: var(--text-primary); white-space: pre-wrap; line-height: 1.5; margin: 0;"><?= htmlspecialchars($ann['message']) ?></p>
                            <form action="hub-actions.php" method="POST" style="margin-top: 12px; text-align: right;" onsubmit="return confirm('Delete this announcement?');">
                                <input type="hidden" name="action" value="delete_announcement">
                                <input type="hidden" name="program_id" value="<?= $programId ?>">
                                <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 12px; font-weight: 700; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div style="text-align: center; padding: 32px; background: #f8fafc; border-radius: var(--radius-sm); color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 12px; color: #cbd5e1;"></i>
                            <p>No announcements sent yet.</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($tab === 'files'): ?>
                    <!-- FILES SECTION -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2 style="font-size: 18px; font-weight: 800;">Program Files</h2>
                    </div>

                    <form action="hub-actions.php" method="POST" style="margin-bottom: 32px; background: var(--bg-secondary); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <input type="hidden" name="action" value="add_file">
                        <input type="hidden" name="program_id" value="<?= $programId ?>">
                        <div class="form-group-profile" style="margin-bottom: 16px;">
                            <label>File Name/Title</label>
                            <input type="text" name="title" class="form-input-profile" placeholder="e.g. Schedule PDF, Module 1" required>
                        </div>
                        <div class="form-group-profile" style="margin-bottom: 16px;">
                            <label>File URL (Google Drive / OneDrive Link)</label>
                            <input type="url" name="file_url" class="form-input-profile" placeholder="https://drive.google.com/..." required>
                        </div>
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <div style="flex: 1;">
                                <label style="font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; display: block;">Target Audience</label>
                                <select name="target_audience" class="form-select-profile">
                                    <option value="All">All (Participants & Crew)</option>
                                    <option value="Participant">Participants Only</option>
                                    <option value="Crew">Crew Only</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-top: 22px;">Share File</button>
                        </div>
                    </form>

                    <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 16px;">Shared Files</h3>
                    <?php
                    $files = db()->select('program_files', '?program_id=eq.' . $programId . '&order=created_at.desc');
                    if ($files['ok'] && count($files['data']) > 0):
                    ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                        <?php foreach ($files['data'] as $file): ?>
                            <div style="padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--white); display: flex; flex-direction: column;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: var(--accent-blue); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div style="flex: 1; overflow: hidden;">
                                        <div style="font-weight: 800; font-size: 14px; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($file['title']) ?></div>
                                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">To: <?= htmlspecialchars($file['target_audience']) ?></div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px; margin-top: auto;">
                                    <a href="<?= htmlspecialchars($file['file_url']) ?>" target="_blank" class="btn btn-outline btn-sm" style="flex: 1; text-align: center;">Open</a>
                                    <form action="hub-actions.php" method="POST" onsubmit="return confirm('Remove this file?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_file">
                                        <input type="hidden" name="program_id" value="<?= $programId ?>">
                                        <input type="hidden" name="id" value="<?= $file['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: #fca5a5;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 32px; background: #f8fafc; border-radius: var(--radius-sm); color: var(--text-secondary);">
                            <i class="fas fa-folder-open" style="font-size: 32px; margin-bottom: 12px; color: #cbd5e1;"></i>
                            <p>No files shared yet.</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($tab === 'participants'): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2 style="font-size: 18px; font-weight: 800;">Registered Participants</h2>
                        <span style="font-size: 13px; color: var(--text-secondary);">List of students registered for this program</span>
                    </div>
                    <?php
                    $participantsResult = db()->select(
                        'pendaftaran',
                        '?select=*,users(avatar_url,tahun_pengajian,kursus_pengajian)&program_id=eq.' . $programId . '&status=neq.Cancelled&order=nama.asc'
                    );
                    if (!$participantsResult['ok']) {
                        // Fallback in case users.kursus_pengajian column is not yet created in Supabase
                        $participantsResult = db()->select(
                            'pendaftaran',
                            '?select=*,users(avatar_url,tahun_pengajian)&program_id=eq.' . $programId . '&status=neq.Cancelled&order=nama.asc'
                        );
                    }
                    $participantsList = $participantsResult['ok'] ? ($participantsResult['data'] ?? []) : [];
                    ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
                        <?php foreach ($participantsList as $p): ?>
                            <?php 
                            $pName = $p['nama'] ?? 'Student';
                            $pMatrik = $p['no_matrik'] ?? '';
                            $pFakulti = $p['fakulti'] ?? '';
                            $pType = $p['jenis_pendaftaran'] ?? 'Peserta';
                            
                            $uData = $p['users'] ?? [];
                            $avatarUrl = $uData['avatar_url'] ?? '';
                            $yearOfStudy = isset($uData['tahun_pengajian']) ? 'Year ' . $uData['tahun_pengajian'] : '';
                            $courseOfStudy = $uData['kursus_pengajian'] ?? '';
                            $pInitial = strtoupper(substr($pName, 0, 1));
                            ?>
                            <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; display: flex; align-items: center; gap: 12px; transition: var(--transition);">
                                <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--accent-blue); color: white; font-weight: 800; font-size: 18px; flex-shrink: 0; border: 2px solid var(--border);">
                                    <?php if ($avatarUrl && file_exists($avatarUrl)): ?>
                                        <img src="<?= htmlspecialchars($avatarUrl) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?= $pInitial ?>
                                    <?php endif; ?>
                                </div>
                                <div style="overflow: hidden; flex: 1;">
                                    <div style="font-weight: 800; font-size: 14px; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($pName) ?>"><?= htmlspecialchars($pName) ?></div>
                                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px; text-transform: uppercase; font-weight: 700;">
                                        <?= htmlspecialchars($pFakulti) ?> &bull; <?= htmlspecialchars($pType) ?>
                                    </div>
                                    <?php if ($courseOfStudy || $yearOfStudy): ?>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($courseOfStudy) ?> <?= $courseOfStudy && $yearOfStudy ? '(' . $yearOfStudy . ')' : $yearOfStudy ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($participantsList)): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: #f8fafc; border-radius: var(--radius-md); color: var(--text-secondary); border: 1px dashed var(--border);">
                                <i class="fas fa-users-slash" style="font-size: 40px; margin-bottom: 12px; color: #cbd5e1;"></i>
                                <p style="font-weight: 700; margin-bottom: 4px;">No participants registered yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($tab === 'discussion'): ?>
                    <!-- DISCUSSION SECTION (FLAT FEED Q&A) -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2 style="font-size: 18px; font-weight: 800;">Discussion Board</h2>
                        <span style="font-size: 13px; color: var(--text-secondary);">Visible to all participants</span>
                    </div>

                    <form action="hub-actions.php" method="POST" style="margin-bottom: 24px; display: flex; gap: 12px;">
                        <input type="hidden" name="action" value="add_discussion">
                        <input type="hidden" name="program_id" value="<?= $programId ?>">
                        <?php if (!empty($_SESSION['avatar_url'])): ?>
                            <img src="<?= htmlspecialchars(getImagePath($_SESSION['avatar_url'])) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px;">
                                <?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <textarea name="message" class="form-textarea-profile" placeholder="Post a message or reply to questions..." required style="min-height: 80px; margin-bottom: 12px;"></textarea>
                            <div style="text-align: right;">
                                <button type="submit" class="btn btn-primary btn-sm">Post Message</button>
                            </div>
                        </div>
                    </form>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <?php
                        $discussions = db()->select('program_discussions', '?select=*,users!user_id(nama,peranan,avatar_url)&program_id=eq.' . $programId . '&order=created_at.asc');
                        if ($discussions['ok'] && count($discussions['data']) > 0):
                            foreach ($discussions['data'] as $msg):
                                $isOrg = ($msg['users']['peranan'] === 'penganjur');
                        ?>
                            <div style="display: flex; gap: 12px; <?= $msg['user_id'] === $_SESSION['user_id'] ? 'flex-direction: row-reverse;' : '' ?>">
                                <?php if (!empty($msg['users']['avatar_url'])): ?>
                                    <img src="<?= htmlspecialchars(getImagePath($msg['users']['avatar_url'])) ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; <?= $isOrg ? 'border: 2px solid var(--accent);' : '' ?>">
                                <?php else: ?>
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $isOrg ? 'var(--accent)' : 'var(--accent-blue)' ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; <?= $isOrg ? 'border: 2px solid var(--accent);' : '' ?>">
                                        <?= strtoupper(substr($msg['users']['nama'] ?? 'U', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div style="max-width: 80%; <?= $msg['user_id'] === $_SESSION['user_id'] ? 'text-align: right;' : '' ?>">
                                    <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">
                                        <span style="font-weight: 700; color: <?= $isOrg ? 'var(--accent)' : 'var(--text-primary)' ?>;"><?= htmlspecialchars($msg['users']['nama']) ?></span>
                                        <?php if ($isOrg): ?>
                                            <span style="background: rgba(91, 141, 239, 0.1); color: var(--accent); font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">Organizer</span>
                                        <?php endif; ?>
                                        <span style="margin-left: 8px;"><i class="far fa-clock"></i> <?= date('j M, h:i A', strtotime($msg['created_at'])) ?></span>
                                    </div>
                                    <div style="background: <?= $msg['user_id'] === $_SESSION['user_id'] ? 'var(--accent)' : 'var(--bg-secondary)' ?>; color: <?= $msg['user_id'] === $_SESSION['user_id'] ? 'white' : 'var(--text-primary)' ?>; padding: 12px 16px; border-radius: 16px; <?= $msg['user_id'] === $_SESSION['user_id'] ? 'border-top-right-radius: 4px;' : 'border-top-left-radius: 4px;' ?> border: 1px solid <?= $msg['user_id'] === $_SESSION['user_id'] ? 'transparent' : 'var(--border)' ?>; text-align: left;">
                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    </div>
                                    <?php if ($msg['user_id'] === $_SESSION['user_id'] || $_SESSION['role'] === 'admin'): ?>
                                        <form action="hub-actions.php" method="POST" style="margin-top: 4px;" onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="action" value="delete_discussion">
                                            <input type="hidden" name="program_id" value="<?= $programId ?>">
                                            <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                            <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 11px; font-weight: 700; cursor: pointer; padding: 0;">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 32px; background: #f8fafc; border-radius: var(--radius-sm); color: var(--text-secondary);">
                                <i class="fas fa-comments" style="font-size: 32px; margin-bottom: 12px; color: #cbd5e1;"></i>
                                <p>No discussions yet. Start the conversation!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include_once __DIR__ . '/components/footer.php'; ?>
</body>
</html>

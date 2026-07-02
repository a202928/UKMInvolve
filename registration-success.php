<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');

$programId = (int)($_GET['id'] ?? 0);
$event = null;
$row = null;

if (db()->isConfigured() && $programId > 0) {
    $row = programs()->findById($programId);
    if ($row) {
        $event = programs()->toStudentDetail($row);
    }
}

if (!$event || !$row) {
    header('Location: events.php');
    exit();
}

// Generate the canonical link
$eventUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/event-details.php?id=" . $programId;
$shareText = "Hi! I just registered for " . $event['title'] . " on UKMInvolve.\n\nJoin me here:\n" . $eventUrl;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful | UKMInvolve</title>
    <link rel="stylesheet" href="public.css?v=999">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .success-page-card {
            max-width: 680px;
            margin: 60px auto;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            animation: cardScaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes cardScaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .success-checkmark-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .success-checkmark-circle i {
            font-size: 40px;
            color: #10b981;
            animation: markBounce 0.5s ease-out 0.2s both;
        }
        @keyframes markBounce {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .event-success-brief {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px;
            margin: 32px 0;
            text-align: left;
        }
        .event-success-poster {
            width: 140px;
            height: 180px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            flex-shrink: 0;
        }
        .event-success-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
        }
        .event-success-info h3 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
            font-family: 'Outfit';
            line-height: 1.3;
        }
        .event-success-meta {
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .event-success-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .event-success-meta i {
            width: 16px;
            color: var(--accent-blue);
        }
        .share-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .share-modal {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 30px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        .share-modal h3 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .share-modal-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
        }
        .share-modal-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            font-size: 13px;
            font-weight: 700;
        }
        .share-modal-item:hover {
            background: var(--bg-secondary);
            border-color: var(--accent-blue);
            transform: translateY(-2px);
        }
        .share-modal-item i {
            font-size: 24px;
        }
    </style>
</head>
<body>

    <!-- REUSABLE NAVBAR -->
    <?php include_once __DIR__ . '/components/navbar.php'; ?>

    <main class="container">
        <div class="success-page-card">
            <div class="success-checkmark-circle">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 style="font-size: 32px; font-weight: 900; margin-bottom: 8px; font-family: 'Outfit';">🎉 Registration Successful!</h1>
            <p style="color: var(--text-secondary); font-size: 15px;">You have successfully registered for the following activity:</p>

            <div class="event-success-brief">
                <img src="<?= htmlspecialchars(getImagePath($event['image'])) ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="event-success-poster">
                <div class="event-success-info">
                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; background: rgba(37,99,235,0.08); color: var(--accent-blue); padding: 2px 6px; border-radius: 4px; align-self: flex-start;">
                        <?= htmlspecialchars($event['category']) ?>
                    </span>
                    <h3><?= htmlspecialchars($event['title']) ?></h3>
                    <div class="event-success-meta">
                        <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($event['date']) ?></span>
                        <span><i class="far fa-clock"></i> <?= htmlspecialchars($event['time']) ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> 
                            <?php if (($event['venue_type'] ?? 'physical') === 'online'): ?>
                                Online Event (<?= htmlspecialchars(!empty($event['platform']) ? $event['platform'] : 'TBA') ?>)
                            <?php else: ?>
                                <?= htmlspecialchars($event['location']) ?>
                            <?php endif; ?>
                        </span>
                        <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars($event['organizer']) ?></span>
                        <span style="margin-top: 8px; font-weight: 800; color: #10b981;">
                            <i class="fas fa-ticket" style="color: #10b981;"></i> Status: Registered
                        </span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="rekod-penyertaan.php" class="btn btn-outline" style="border-radius: 999px; padding: 12px 28px; font-size: 14px;">
                    <i class="fas fa-calendar-days"></i> View My Events
                </a>
                <button onclick="openInviteModal()" class="btn btn-primary" style="border-radius: 999px; padding: 12px 28px; font-size: 14px; background: var(--accent-blue);">
                    <i class="fas fa-users"></i> 👥 Invite Your Friends
                </button>
            </div>
        </div>
    </main>

    <!-- INVITATION MODAL -->
    <div class="share-modal-backdrop" id="inviteModalBackdrop" onclick="closeInviteModal()">
        <div class="share-modal" id="inviteModal" onclick="event.stopPropagation()">
            <h3>Invite Your Friends</h3>
            <p style="color: var(--text-secondary); font-size: 13px;">Share your registration and invite friends to join you!</p>

            <div class="share-modal-grid">
                <div class="share-modal-item" onclick="copyInviteLink()">
                    <i class="far fa-copy" style="color: #475569;"></i>
                    <span>Copy Event Link</span>
                </div>
                <a class="share-modal-item" href="https://api.whatsapp.com/send?text=<?= urlencode($shareText) ?>" target="_blank" onclick="recordInviteShare()">
                    <i class="fab fa-whatsapp" style="color: #25D366;"></i>
                    <span>WhatsApp</span>
                </a>
                <a class="share-modal-item" href="https://t.me/share/url?url=<?= urlencode($eventUrl) ?>&text=<?= urlencode($shareText) ?>" target="_blank" onclick="recordInviteShare()">
                    <i class="fab fa-telegram" style="color: #0088cc;"></i>
                    <span>Telegram</span>
                </a>
                <a class="share-modal-item" href="mailto:?subject=<?= rawurlencode("Join me at " . $event['title']) ?>&body=<?= rawurlencode($shareText) ?>" target="_blank" onclick="recordInviteShare()">
                    <i class="far fa-envelope" style="color: #ea4335;"></i>
                    <span>Email</span>
                </a>
            </div>

            <button class="btn btn-outline" style="width: 100%; border-radius: 999px;" onclick="closeInviteModal()">Close</button>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container" id="successToastContainer" style="display: none; opacity: 0; transition: opacity 0.5s ease;">
        <div class="toast" style="border-left-color: #10b981;">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span id="successToastMsg">Link copied successfully!</span>
        </div>
    </div>

    <script>
        function openInviteModal() {
            const shareData = {
                title: <?= json_encode($event['title']) ?>,
                text: <?= json_encode(str_replace("\n", " ", $shareText)) ?>,
                url: <?= json_encode($eventUrl) ?>
            };
            
            try {
                if (navigator.share) {
                    navigator.share(shareData)
                    .then(() => recordInviteShare())
                    .catch(err => {
                        console.log('Web Share cancelled or failed, opening fallback modal:', err);
                        showFallbackModal();
                    });
                } else {
                    showFallbackModal();
                }
            } catch (e) {
                console.error('Web Share error, falling back:', e);
                showFallbackModal();
            }
        }

        function showFallbackModal() {
            const backdrop = document.getElementById('inviteModalBackdrop');
            const modal = document.getElementById('inviteModal');
            backdrop.style.display = 'flex';
            setTimeout(() => {
                backdrop.style.opacity = '1';
                modal.style.transform = 'translateY(0)';
            }, 10);
        }

        function closeInviteModal() {
            const backdrop = document.getElementById('inviteModalBackdrop');
            const modal = document.getElementById('inviteModal');
            backdrop.style.opacity = '0';
            modal.style.transform = 'translateY(20px)';
            setTimeout(() => {
                backdrop.style.display = 'none';
            }, 300);
        }

        function recordInviteShare() {
            fetch('share-action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ program_id: <?= $programId ?> })
            })
            .then(res => res.json())
            .then(data => {
                console.log('Share count updated:', data);
            })
            .catch(err => console.error(err));
        }

        function copyInviteLink() {
            const tempInput = document.createElement('textarea');
            tempInput.value = "Hi! I just registered for " + <?= json_encode($event['title']) ?> + " on UKMInvolve. Join me here:\n" + <?= json_encode($eventUrl) ?>;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            recordInviteShare();
            showSuccessToast('✓ Link copied successfully');
            closeInviteModal();
        }

        function showSuccessToast(message) {
            const toast = document.getElementById('successToastContainer');
            const msgEl = document.getElementById('successToastMsg');
            msgEl.textContent = message;
            toast.style.display = 'flex';
            setTimeout(() => toast.style.opacity = '1', 10);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.style.display = 'none', 500);
            }, 3000);
        }
    </script>

    <!-- REUSABLE FOOTER -->
    <?php include_once __DIR__ . '/components/footer.php'; ?>

</body>
</html>

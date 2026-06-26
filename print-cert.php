<?php
session_start();
require_once __DIR__ . '/lib/bootstrap.php';
requireRole('pelajar');

$studentId = $_SESSION['user_id'];
$prog = ProgressionService::getStudentProgression($studentId);
if ($prog['level'] < 4) {
    die("Only UKM Elite (Level 4) students are eligible for this certificate.");
}

$userData = users()->findById($studentId);

// Get template
$template = null;
$tRes = db()->select('cert_templates', '?id=eq.1');
if ($tRes['ok'] && !empty($tRes['data'][0])) {
    $template = $tRes['data'][0];
}

$templateTitle = $template['title'] ?? 'Active Student Excellence e-Certificate';
$templateText = $template['template_text'] ?? 'Sijil ini dianugerahkan kepada {name} bagi penglibatan cemerlang.';

// Generate verification code if not already done
$certCode = '';
$certRes = db()->select('student_certs', '?student_id=eq.' . rawurlencode($studentId));
if ($certRes['ok'] && !empty($certRes['data'][0])) {
    $certCode = $certRes['data'][0]['cert_code'];
} else {
    $certCode = 'CERT-' . strtoupper(substr(md5($studentId . time()), 0, 10));
    db()->insert('student_certs', [
        'student_id' => $studentId,
        'template_id' => $template['id'] ?? 1,
        'cert_code' => $certCode
    ]);
}

$certText = str_replace(
    ['{name}', '{matric}', '{date}'],
    [
        strtoupper($userData['nama']),
        $userData['matrik'] ?? '-',
        date('d F Y')
    ],
    $templateText
);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Sijil Kecemerlangan | UKMInvolve</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 40px;
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .cert-container {
            background-color: white;
            width: 842px;
            height: 595px;
            padding: 40px;
            box-sizing: border-box;
            border: 20px solid #1e293b;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            background-image: radial-gradient(circle, #ffffff 60%, #f8fafc 100%);
        }
        .cert-inner-border {
            border: 2px solid #ca8a04;
            height: 100%;
            width: 100%;
            padding: 30px;
            box-sizing: border-box;
            text-align: center;
            position: relative;
        }
        .cert-header {
            margin-top: 10px;
        }
        .cert-header img {
            height: 60px;
            margin: 0 auto 10px;
        }
        .cert-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }
        .cert-award-to {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ca8a04;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .cert-name {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            display: inline-block;
            padding-bottom: 5px;
            margin-bottom: 15px;
            min-width: 300px;
        }
        .cert-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }
        .cert-footer {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 12px;
            color: #64748b;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            width: 150px;
            text-align: center;
            padding-top: 5px;
            font-weight: 600;
        }
        .cert-code-box {
            font-size: 11px;
            font-family: monospace;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 4px 8px;
            border-radius: 4px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .cert-container {
                box-shadow: none;
                margin: 0;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="cert-container">
        <div class="cert-inner-border">
            <div class="cert-header">
                <img src="UKM.png" alt="UKM logo">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #475569; margin-bottom: 5px;">Universiti Kebangsaan Malaysia</div>
            </div>
            
            <div class="cert-title"><?= htmlspecialchars($templateTitle) ?></div>
            
            <div class="cert-award-to">Dianugerahkan Kepada</div>
            <div class="cert-name"><?= htmlspecialchars(strtoupper($userData['nama'])) ?></div>
            
            <div class="cert-text">
                <?= htmlspecialchars($certText) ?>
            </div>
            
            <div class="cert-footer">
                <div style="text-align: left;">
                    <div>No. Rujukan: <span class="cert-code-box"><?= htmlspecialchars($certCode) ?></span></div>
                    <div style="margin-top: 4px;">Tarikh: <?= date('d M Y') ?></div>
                </div>
                
                <div class="signature-line">
                    Urus Setia UKMInvolve
                    <div style="font-size: 10px; color: #94a3b8; font-weight: 400; margin-top: 2px;">Universiti Kebangsaan Malaysia</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>

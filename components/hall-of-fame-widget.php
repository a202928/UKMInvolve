<?php
// Ensure session is active and bootstrap is loaded
if (!class_exists('LeaderboardService')) {
    return;
}

// Ensure archiving has run
LeaderboardService::checkAndArchivePreviousMonths();

// Fetch LIVE data for the current month
$topStudents = LeaderboardService::getStudentLeaderboard('This Month', null, null, 1);
$topOrganizers = LeaderboardService::getOrganizerLeaderboard('This Month', null, null, 1);
$bestProg = LeaderboardService::getBestProgrammeOfTheMonth();

$displayData = [
    'month_name' => date('F Y'),
    'student' => !empty($topStudents) ? [
        'name' => $topStudents[0]['nama'],
        'subtext' => $topStudents[0]['mata'] . ' Pts Earned',
        'image' => $topStudents[0]['avatar_url'] ?? '',
        'level' => $topStudents[0]['level'] ?? 1
    ] : null,
    'organizer' => !empty($topOrganizers) ? [
        'name' => $topOrganizers[0]['name'],
        'subtext' => $topOrganizers[0]['events_conducted'] . ' Events Conducted',
        'image' => $topOrganizers[0]['avatar_url'] ?? ''
    ] : null,
    'program' => $bestProg ? [
        'name' => $bestProg['name'],
        'subtext' => 'By: ' . $bestProg['organizer_name'],
        'image' => $bestProg['poster'] ?? 'program1.jpg'
    ] : null
];
?>

<style>
.premium-hof-card {
    background: linear-gradient(145deg, #020617, #0f172a);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
    max-width: 100%;
    position: relative;
    margin-bottom: 30px;
}
.premium-hof-header {
    padding: 30px 20px;
    text-align: center;
    position: relative;
    background: radial-gradient(ellipse at top, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
}
.premium-hof-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 900;
    font-family: 'Outfit', sans-serif;
    background: linear-gradient(to right, #fbbf24, #fcd34d, #fbbf24);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-transform: uppercase;
    letter-spacing: 3px;
    text-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}
.premium-hof-subtitle {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: #818cf8;
    letter-spacing: 2px;
    margin-top: 10px;
    display: inline-block;
    background: rgba(99,102,241,0.1);
    padding: 4px 12px;
    border-radius: 999px;
    border: 1px solid rgba(99,102,241,0.2);
}
.premium-winners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    background: rgba(255,255,255,0.03);
    border-top: 1px solid rgba(255,255,255,0.05);
}
.premium-winner-box {
    background: #020617;
    padding: 40px 24px;
    text-align: center;
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    overflow: hidden;
    border-right: 1px solid rgba(255,255,255,0.05);
}
.premium-winner-box:last-child {
    border-right: none;
}
.premium-winner-box:hover {
    background: #0f172a;
}
.premium-winner-box::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: transparent;
    transition: all 0.4s;
    opacity: 0.8;
}
.winner-student:hover::before { background: linear-gradient(90deg, #ca8a04, #fde047); box-shadow: 0 0 15px #fde047; }
.winner-org:hover::before { background: linear-gradient(90deg, #059669, #34d399); box-shadow: 0 0 15px #34d399; }
.winner-prog:hover::before { background: linear-gradient(90deg, #b45309, #fbbf24); box-shadow: 0 0 15px #fbbf24; }

.premium-winner-title {
    font-size: 13px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 24px;
    z-index: 1;
}
.title-gold { color: #facc15; }
.title-emerald { color: #34d399; }
.title-amber { color: #fbbf24; }

.premium-avatar-wrap {
    width: 90px; height: 90px;
    border-radius: 50%;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1e293b;
    border: 3px solid rgba(255,255,255,0.1);
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.premium-avatar-wrap img {
    width: 100%; height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.premium-winner-box:hover .premium-avatar-wrap {
    transform: scale(1.1) translateY(-5px);
    border-color: rgba(255,255,255,0.3);
}

.premium-prog-wrap {
    width: 140px; height: 90px;
    border-radius: 12px;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
    overflow: hidden;
    border: 3px solid rgba(255,255,255,0.1);
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.premium-prog-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.premium-winner-box:hover .premium-prog-wrap {
    transform: scale(1.1) translateY(-5px);
    border-color: rgba(255,255,255,0.3);
}

.premium-name {
    font-size: 18px;
    font-weight: 800;
    color: white;
    margin-bottom: 10px;
    font-family: 'Outfit', sans-serif;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}
.premium-subtext {
    font-size: 12px;
    font-weight: 700;
    color: #cbd5e1;
    background: rgba(255, 255, 255, 0.05);
    padding: 6px 16px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}
.premium-initials {
    font-weight: 900;
    font-size: 32px;
    color: #64748b;
}

@media (max-width: 768px) {
    .premium-winner-box {
        border-right: none;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
}
</style>

<div class="premium-hof-card">
    <div class="premium-hof-header">
        <h2><?= htmlspecialchars($displayData['month_name']) ?></h2>
        <span class="premium-hof-subtitle"><i class="fas fa-crown"></i> Hall of Fame </span>
    </div>
    
    <div class="premium-winners-grid">
        
        <!-- Student Winner -->
        <div class="premium-winner-box winner-student">
            <div class="premium-winner-title title-gold">
                🥇 Student of the Month
            </div>
            <?php if ($displayData['student']): ?>
                <div class="premium-avatar-wrap">
                    <?php if (!empty($displayData['student']['image']) && file_exists($displayData['student']['image'])): ?>
                        <img src="<?= htmlspecialchars($displayData['student']['image']) ?>">
                    <?php else: ?>
                        <div class="premium-initials"><?= strtoupper(substr($displayData['student']['name'], 0, 1)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="premium-name">
                    <?= htmlspecialchars($displayData['student']['name']) ?>
                </div>
                <div class="premium-subtext">
                    <?= htmlspecialchars($displayData['student']['subtext']) ?>
                </div>
            <?php else: ?>
                <div class="premium-avatar-wrap"><div class="premium-initials">?</div></div>
                <div class="premium-name" style="color: #64748b;">No Winner</div>
                <div class="premium-subtext">No records archived</div>
            <?php endif; ?>
        </div>

        <!-- Organizer Winner -->
        <div class="premium-winner-box winner-org">
            <div class="premium-winner-title title-emerald">
                🏢 Organizer of the Month
            </div>
            <?php if ($displayData['organizer']): ?>
                <div class="premium-avatar-wrap" style="border-radius: 16px;">
                    <?php if (!empty($displayData['organizer']['image']) && file_exists($displayData['organizer']['image'])): ?>
                        <img src="<?= htmlspecialchars($displayData['organizer']['image']) ?>" style="border-radius: 16px;">
                    <?php else: ?>
                        <div class="premium-initials"><?= strtoupper(substr($displayData['organizer']['name'], 0, 1)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="premium-name">
                    <?= htmlspecialchars($displayData['organizer']['name']) ?>
                </div>
                <div class="premium-subtext">
                    <?= htmlspecialchars($displayData['organizer']['subtext']) ?>
                </div>
            <?php else: ?>
                <div class="premium-avatar-wrap" style="border-radius: 16px;"><div class="premium-initials">?</div></div>
                <div class="premium-name" style="color: #64748b;">No Winner</div>
                <div class="premium-subtext">No records archived</div>
            <?php endif; ?>
        </div>

        <!-- Program Winner -->
        <div class="premium-winner-box winner-prog">
            <div class="premium-winner-title title-amber">
                🏆 Programme of the Month
            </div>
            <?php if ($displayData['program']): ?>
                <div class="premium-prog-wrap">
                    <?php 
                        $progImage = !empty($displayData['program']['image']) ? $displayData['program']['image'] : 'program1.jpg'; 
                        if (strpos($progImage, 'program1.jpg') !== false && !file_exists($progImage)) $progImage = 'program1.jpg'; // handle fallback safely
                    ?>
                    <img src="<?= htmlspecialchars($progImage) ?>">
                </div>
                <div class="premium-name">
                    <?= htmlspecialchars($displayData['program']['name']) ?>
                </div>
                <div class="premium-subtext">
                    <?= htmlspecialchars($displayData['program']['subtext']) ?>
                </div>
            <?php else: ?>
                <div class="premium-prog-wrap"><div style="width:100%; height:100%; background:#1e293b; display:flex; align-items:center; justify-content:center;"><div class="premium-initials">?</div></div></div>
                <div class="premium-name" style="color: #64748b;">No Winner</div>
                <div class="premium-subtext">No records archived</div>
            <?php endif; ?>
        </div>

    </div>
</div>

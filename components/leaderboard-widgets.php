<?php
// Ensure session is active and bootstrap is loaded
if (!class_exists('LeaderboardService')) {
    return;
}

// Ensure archiving has run
LeaderboardService::checkAndArchivePreviousMonths();

// Fetch monthly summaries
$topStudentsCompact = LeaderboardService::getStudentLeaderboard('This Month', null, null, 3);
$topOrganizersCompact = LeaderboardService::getOrganizerLeaderboard('This Month', null, null, 3);
$bestProgCompact = LeaderboardService::getBestProgrammeOfTheMonth();
?>

<div class="leaderboard-widgets-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
    
    <!-- 1. STUDENT LEADERBOARD COMPACT CARD -->
    <div class="dashboard-card-wrap" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="background: rgba(37,99,235,0.1); color: var(--accent-blue); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px;"><i class="fas fa-trophy"></i></span>
                    Student Leaderboard
                </h2>
                <span style="font-size: 11px; font-weight: 800; background: #eff6ff; color: #1e40af; padding: 4px 8px; border-radius: 999px; text-transform: uppercase;">This Month</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                <?php if (empty($topStudentsCompact)): ?>
                    <p style="color:var(--text-secondary); font-size:13px; text-align:center; padding: 20px 0;">No active student records this month.</p>
                <?php else: ?>
                    <?php 
                    $studentTitles = [
                        1 => ['title' => 'Student of the Month', 'emoji' => '🥇', 'color' => '#ca8a04', 'bg' => '#fef9c3'],
                        2 => ['title' => 'Outstanding Participant', 'emoji' => '🥈', 'color' => '#475569', 'bg' => '#f1f5f9'],
                        3 => ['title' => 'Campus Achiever', 'emoji' => '🥉', 'color' => '#b45309', 'bg' => '#ffedd5']
                    ];
                    
                    foreach ($topStudentsCompact as $idx => $st): 
                        $rank = $idx + 1;
                        $tInfo = $studentTitles[$rank] ?? ['title' => 'Participant', 'emoji' => '', 'color' => 'var(--text-secondary)', 'bg' => '#f8fafc'];
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--white); box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: var(--transition);" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                <span style="font-weight: 900; font-size: 14px; width: 24px; text-align: center; color: var(--text-muted);"><?= $tInfo['emoji'] ?: $rank ?></span>
                                <div style="flex-shrink: 0; position: relative;">
                                    <?= ProgressionService::renderAvatarHTML($st, 'sm') ?>
                                </div>
                                <div style="min-width: 0;">
                                    <h4 style="font-size: 13px; font-weight: 800; font-family:'Outfit'; margin: 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; color: var(--text-primary);">
                                        <?= htmlspecialchars($st['nama']) ?>
                                    </h4>
                                    <span style="font-size: 10px; font-weight: 800; color: <?= $tInfo['color'] ?>; background: <?= $tInfo['bg'] ?>; padding: 1px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                                        <?= $tInfo['title'] ?>
                                    </span>
                                </div>
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                <span style="font-weight: 900; font-size: 13px; color: var(--accent-blue); display: block;"><?= $st['mata'] ?> Pts</span>
                                <?php if ($st['streak'] > 0): ?>
                                    <span style="font-size: 10px; color: #ea580c; font-weight: 700;"><i class="fas fa-fire"></i> <?= $st['streak'] ?>M</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="leaderboard.php?type=student" class="btn btn-outline" style="text-align: center; width: 100%; border-radius: 8px; font-weight: 800; text-decoration: none; padding: 8px 0; font-size: 13px;">
            <i class="fas fa-list-ol"></i> View Full Leaderboard
        </a>
    </div>

    <!-- 2. ORGANIZER LEADERBOARD COMPACT CARD -->
    <div class="dashboard-card-wrap" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="background: rgba(16,185,129,0.1); color: #10b981; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px;"><i class="fas fa-building-user"></i></span>
                    Organizer Leaderboard
                </h2>
                <span style="font-size: 11px; font-weight: 800; background: #ecfdf5; color: #047857; padding: 4px 8px; border-radius: 999px; text-transform: uppercase;">This Month</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                <?php if (empty($topOrganizersCompact)): ?>
                    <p style="color:var(--text-secondary); font-size:13px; text-align:center; padding: 20px 0;">No events conducted this month.</p>
                <?php else: ?>
                    <?php 
                    $orgTitles = [
                        1 => ['title' => 'Organizer of the Month', 'emoji' => '🥇', 'color' => '#ca8a04', 'bg' => '#fef9c3'],
                        2 => ['title' => 'Outstanding Organizer', 'emoji' => '🥈', 'color' => '#475569', 'bg' => '#f1f5f9'],
                        3 => ['title' => 'Excellent Organizer', 'emoji' => '🥉', 'color' => '#b45309', 'bg' => '#ffedd5']
                    ];
                    
                    foreach ($topOrganizersCompact as $idx => $org): 
                        $rank = $idx + 1;
                        $tInfo = $orgTitles[$rank] ?? ['title' => 'Organizer', 'emoji' => '', 'color' => 'var(--text-secondary)', 'bg' => '#f8fafc'];
                        
                        $initial = strtoupper(substr($org['name'], 0, 1));
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--white); box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: var(--transition);" onmouseover="this.style.borderColor='#10b981'" onmouseout="this.style.borderColor='var(--border)'">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                <span style="font-weight: 900; font-size: 14px; width: 24px; text-align: center; color: var(--text-muted);"><?= $tInfo['emoji'] ?: $rank ?></span>
                                <div style="flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; overflow: hidden; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                                    <?php if (!empty($org['avatar_url']) && file_exists($org['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($org['avatar_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="font-weight:800; font-size: 14px; color:var(--text-muted);"><?= $initial ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="min-width: 0;">
                                    <h4 style="font-size: 13px; font-weight: 800; font-family:'Outfit'; margin: 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; color: var(--text-primary);">
                                        <?= htmlspecialchars($org['name']) ?>
                                    </h4>
                                    <span style="font-size: 10px; font-weight: 800; color: <?= $tInfo['color'] ?>; background: <?= $tInfo['bg'] ?>; padding: 1px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                                        <?= $tInfo['title'] ?>
                                    </span>
                                </div>
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                <span style="font-weight: 900; font-size: 13px; color: #10b981; display: block;"><?= $org['events_conducted'] ?> Event<?= $org['events_conducted'] == 1 ? '' : 's' ?></span>
                                <span style="font-size: 10px; color: var(--text-secondary); font-weight: 700;"><?= $org['total_participants'] ?> Regs • <?= $org['total_attendance'] ?> Att</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="leaderboard.php?type=organizer" class="btn btn-outline" style="text-align: center; width: 100%; border-radius: 8px; font-weight: 800; text-decoration: none; padding: 8px 0; font-size: 13px; border-color: #10b981; color: #10b981;">
            <i class="fas fa-list-ol"></i> View Organizers
        </a>
    </div>

    <!-- 3. BEST PROGRAMME COMPACT CARD -->
    <div class="dashboard-card-wrap" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h2 style="font-size: 18px; font-weight: 800; font-family:'Outfit'; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="background: rgba(245,158,11,0.1); color: #d97706; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px;"><i class="fas fa-award"></i></span>
                    Best Programme
                </h2>
                <span style="font-size: 11px; font-weight: 800; background: #fef3c7; color: #b45309; padding: 4px 8px; border-radius: 999px; text-transform: uppercase;">This Month</span>
            </div>

            <?php if (!$bestProgCompact): ?>
                <div style="text-align: center; padding: 40px 0; color: var(--text-secondary); font-size: 13px;">
                    <i class="far fa-calendar-times" style="font-size: 32px; color: var(--border); margin-bottom: 12px; display: block;"></i>
                    No completed programmes recorded this month yet.
                </div>
            <?php else: ?>
                <div style="border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; background: var(--white); box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: var(--transition); margin-bottom: 20px;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='var(--border)'">
                    <div style="height: 100px; overflow: hidden; position: relative; background: #e2e8f0;">
                        <img src="<?= htmlspecialchars($bestProgCompact['poster']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.7); color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 800;">
                            ★ <?= round($bestProgCompact['average_rating'], 1) ?> Rating
                        </div>
                        <div style="position: absolute; top: 8px; right: 8px; background: #d97706; color: white; padding: 4px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; box-shadow: var(--shadow-sm);">
                            🏆 Programme of the Month
                        </div>
                    </div>
                    <div style="padding: 12px;">
                        <h4 style="font-size: 14px; font-weight: 800; font-family:'Outfit'; margin: 0 0 4px 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; color: var(--text-primary);">
                            <?= htmlspecialchars($bestProgCompact['name']) ?>
                        </h4>
                        <p style="font-size: 11px; color: var(--text-secondary); margin: 0 0 8px 0; font-weight: 500;">
                            By: <?= htmlspecialchars($bestProgCompact['organizer_name']) ?>
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border); padding-top: 8px; font-size: 11px; font-weight: 700; color: var(--text-secondary);">
                            <span>👥 <?= $bestProgCompact['participants'] ?> Registered</span>
                            <span>🎯 <?= round($bestProgCompact['attendance_rate']) ?>% Attendance</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <a href="hall-of-fame.php" class="btn btn-outline" style="text-align: center; width: 100%; border-radius: 8px; font-weight: 800; text-decoration: none; padding: 8px 0; font-size: 13px; border-color: #d97706; color: #d97706;">
            <i class="fas fa-medal"></i> View Hall of Fame
        </a>
    </div>

</div>

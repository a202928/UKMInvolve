<?php

class ProgressionService
{
    /**
     * Calculate monthly active streak.
     * Completing at least ONE activity (attendance, completed crew duty, or feedback submission)
     * every month increments the streak. If a month is missed, it resets to zero.
     */
    public static function calculateStreak(string $studentId): int
    {
        $db = db();
        $months = [];

        // 1. Attendance (kehadiran)
        $att = $db->select('kehadiran', '?select=created_at&pelajar_id=eq.' . rawurlencode($studentId) . '&status=eq.Hadir');
        if ($att['ok'] && is_array($att['data'])) {
            foreach ($att['data'] as $r) {
                if (!empty($r['created_at'])) {
                    $months[date('Y-m', strtotime($r['created_at']))] = true;
                }
            }
        }

        // 2. Completed Crew Duties
        $crew = $db->select('crew_applications', '?select=reviewed_at&pelajar_id=eq.' . rawurlencode($studentId) . '&status=eq.Completed');
        if ($crew['ok'] && is_array($crew['data'])) {
            foreach ($crew['data'] as $r) {
                $date = !empty($r['reviewed_at']) ? $r['reviewed_at'] : null;
                if ($date) {
                    $months[date('Y-m', strtotime($date))] = true;
                }
            }
        }

        // 3. Feedback Submitted (maklum_balas)
        $fb = $db->select('maklum_balas', '?select=created_at&pelajar_id=eq.' . rawurlencode($studentId));
        if ($fb['ok'] && is_array($fb['data'])) {
            foreach ($fb['data'] as $r) {
                if (!empty($r['created_at'])) {
                    $months[date('Y-m', strtotime($r['created_at']))] = true;
                }
            }
        }

        if (empty($months)) {
            return 0;
        }

        $currentMonth = date('Y-m');
        $prevMonth = date('Y-m', strtotime('first day of last month'));

        $streak = 0;
        $checkMonth = $currentMonth;

        if (isset($months[$currentMonth])) {
            while (isset($months[$checkMonth])) {
                $streak++;
                $checkMonth = date('Y-m', strtotime($checkMonth . '-01 -1 month'));
            }
        } elseif (isset($months[$prevMonth])) {
            $checkMonth = $prevMonth;
            while (isset($months[$checkMonth])) {
                $streak++;
                $checkMonth = date('Y-m', strtotime($checkMonth . '-01 -1 month'));
            }
        }

        return $streak;
    }

    /**
     * Compute points, attendance, crew experience, active streak, and determine student level.
     */
    public static function getStudentProgression(string $studentId, ?array $userData = null): array
    {
        $db = db();
        if (!$userData) {
            $userData = users()->findById($studentId);
        }

        $points = (int)($userData['mata'] ?? 0);

        // Count attended programs
        $attCount = 0;
        $att = $db->select('kehadiran', '?select=id&pelajar_id=eq.' . rawurlencode($studentId) . '&status=eq.Hadir');
        if ($att['ok'] && is_array($att['data'])) {
            $attCount = count($att['data']);
        }

        // Count completed crew duties
        $crewCount = 0;
        $crew = $db->select('crew_applications', '?select=id&pelajar_id=eq.' . rawurlencode($studentId) . '&status=eq.Completed');
        if ($crew['ok'] && is_array($crew['data'])) {
            $crewCount = count($crew['data']);
        }

        // Calculate streak
        $streak = self::calculateStreak($studentId);

        // Fetch configured requirements from DB
        $levelsRes = $db->select('level_thresholds', '?order=level.asc');
        $levels = [];
        if ($levelsRes['ok'] && is_array($levelsRes['data'])) {
            $levels = array_filter($levelsRes['data'], fn($l) => (int)$l['level'] <= 4);
            $levels = array_values($levels);
        }
        if (empty($levels)) {
            // Default fallbacks if level_thresholds table is empty
            $levels = [
                ['level' => 1, 'name' => 'Participant', 'xp' => 0, 'req_programs' => 0, 'req_crew' => 0, 'req_streak' => 0],
                ['level' => 2, 'name' => 'Crew Member', 'xp' => 150, 'req_programs' => 5, 'req_crew' => 0, 'req_streak' => 0],
                ['level' => 3, 'name' => 'MT (Majlis Tertinggi)', 'xp' => 500, 'req_programs' => 5, 'req_crew' => 5, 'req_streak' => 0],
                ['level' => 4, 'name' => 'UKM Elite', 'xp' => 1000, 'req_programs' => 10, 'req_crew' => 8, 'req_streak' => 1]
            ];
        } else {
            // Ensure exactly max 4 levels are present
            $levels = array_slice($levels, 0, 4);
        }

        $currentLevelNum = 1;
        $currentLevel = $levels[0];

        foreach ($levels as $l) {
            $lvlNum = (int)$l['level'];
            $reqXP = (int)$l['xp'];
            $reqProg = (int)($l['req_programs'] ?? 0);
            $reqCrew = (int)($l['req_crew'] ?? 0);
            $reqStreak = (int)($l['req_streak'] ?? 0);

            if ($points >= $reqXP && $attCount >= $reqProg && $crewCount >= $reqCrew && $streak >= $reqStreak) {
                if ($lvlNum > $currentLevelNum) {
                    $currentLevelNum = $lvlNum;
                    $currentLevel = $l;
                }
            }
        }

        // Limit current level to 4
        if ($currentLevelNum > 4) {
            $currentLevelNum = 4;
            $currentLevel = $levels[3];
        }

        $nextLevel = null;
        foreach ($levels as $l) {
            if ((int)$l['level'] === $currentLevelNum + 1) {
                $nextLevel = $l;
                break;
            }
        }

        return [
            'points' => $points,
            'attended_programs' => $attCount,
            'crew_experience' => $crewCount,
            'streak' => $streak,
            'level' => $currentLevelNum,
            'level_name' => $currentLevel['name'] ?? 'Participant',
            'next_level' => $nextLevel,
            'levels_list' => $levels
        ];
    }

    /**
     * Helper to verify if a crew position counts as a Majlis Tertinggi (MT) position.
     */
    public static function isMTPosition(string $positionName): bool
    {
        $name = strtolower(trim($positionName));
        $mtKeywords = [
            'director', 'pengarah', 'timbalan', 'deputy',
            'secretary', 'setiausaha', 'treasurer', 'bendahari'
        ];
        foreach ($mtKeywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render an avatar wrapped inside the appropriate Level frame.
     */
    public static function renderAvatarHTML(array $user, string $sizeClass = 'md'): string
    {
        $role = $user['peranan'] ?? 'pelajar';
        $level = 1;

        if ($role !== 'pelajar') {
            $level = 0;
        } else {
            if (isset($user['level'])) {
                $level = (int)$user['level'];
                if ($level < 1) $level = 1;
            } else {
                $points = (int)($user['mata'] ?? 0);
                if ($points >= 1000) {
                    $level = 4;
                } elseif ($points >= 500) {
                    $level = 3;
                } elseif ($points >= 150) {
                    $level = 2;
                } else {
                    $level = 1;
                }
            }
        }

        if ($level > 4) {
            $level = 4;
        }

        $avatarUrl = $user['avatar_url'] ?? '';
        $name = $user['nama'] ?? 'User';
        $initial = strtoupper(substr($name, 0, 1));

        $frameClass = $level > 0 ? "avatar-frame-lvl" . $level : "avatar-frame-none";

        $html = '<div class="avatar-frame-container ' . $frameClass . ' size-' . $sizeClass . '">';
        if ($avatarUrl && file_exists($avatarUrl)) {
            $html .= '<img src="' . htmlspecialchars($avatarUrl) . '" alt="' . htmlspecialchars($name) . '" class="avatar">';
        } else {
            $html .= '<div class="avatar-initials">' . htmlspecialchars($initial) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}

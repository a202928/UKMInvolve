<?php

class LeaderboardService
{
    /**
     * Check if previous months have been archived in the Hall of Fame.
     * Runs dynamically on page load (self-healing archive reset).
     */
    public static function checkAndArchivePreviousMonths()
    {
        $db = db();
        if (!$db->isConfigured()) return;

        // Check the last 12 months (excluding current month) using rollover-immune date math
        for ($i = 1; $i <= 12; $i++) {
            $checkMonth = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));

            // Check if we already processed this month
            $res = $db->select('hall_of_fame', '?year_month=eq.' . $checkMonth);
            if ($res['ok'] && !empty($res['data'])) {
                continue; // Already processed/archived
            }

            // Check if the hall_of_fame table exists or is accessible
            if (!$res['ok'] && isset($res['error'])) {
                // Table might not exist yet, abort gracefully
                return;
            }

            // Check if there is any activity in this month before archiving
            if (!self::hasActivityInMonth($checkMonth)) {
                // Insert a placeholder to mark this month as processed, preventing future redundant checks
                $db->insert('hall_of_fame', [
                    'year_month' => $checkMonth,
                    'winner_type' => 'empty',
                    'score' => 0.0,
                    'meta_name' => 'No activity'
                ], false);
                continue;
            }

            self::archiveMonth($checkMonth);
        }
    }

    /**
     * Check if a specific month has any events or point records to see if archiving is needed.
     */
    private static function hasActivityInMonth(string $yearMonth): bool
    {
        $db = db();
        $start = $yearMonth . '-01';
        $end = date('Y-m-t', strtotime($start));

        // Check programs
        $progRes = $db->select('program', '?select=id&tarikh=gte.' . $start . '&tarikh=lte.' . $end . '&limit=1');
        if ($progRes['ok'] && !empty($progRes['data'])) {
            return true;
        }

        // Check points
        $startT = $start . 'T00:00:00';
        $endT = $end . 'T23:59:59';
        $pointsRes = $db->select('rekod_mata', '?select=id&created_at=gte.' . $startT . '&created_at=lte.' . $endT . '&limit=1');
        if ($pointsRes['ok'] && !empty($pointsRes['data'])) {
            return true;
        }

        return false;
    }

    /**
     * Compute and save winners for a specific month.
     */
    private static function archiveMonth(string $yearMonth)
    {
        $db = db();

        // 1. Top Student
        $students = self::getStudentLeaderboard('This Month', null, null, 1, $yearMonth);
        if (!empty($students)) {
            $st = $students[0];
            $lvl = $st['level'] ?? 1;
            $fac = $st['fakulti'] ?? 'General';
            $db->insert('hall_of_fame', [
                'year_month' => $yearMonth,
                'winner_type' => 'student',
                'entity_id' => $st['id'],
                'score' => (float)$st['mata'],
                'meta_name' => $st['nama'],
                'meta_subtext' => 'Lvl ' . $lvl . ' • ' . $fac,
                'meta_image' => $st['avatar_url'] ?? ''
            ], false);
        }

        // 2. Top Organizer
        $organizers = self::getOrganizerLeaderboard('This Month', null, null, 1, $yearMonth);
        if (!empty($organizers)) {
            $org = $organizers[0];
            $fac = !empty($org['fakulti']) ? $org['fakulti'] : (!empty($org['kolej']) ? $org['kolej'] : 'General');
            $db->insert('hall_of_fame', [
                'year_month' => $yearMonth,
                'winner_type' => 'organizer',
                'entity_id' => $org['id'],
                'score' => (float)$org['score'],
                'meta_name' => $org['name'],
                'meta_subtext' => $fac,
                'meta_image' => $org['avatar_url'] ?? ''
            ], false);
        }

        // 3. Best Program
        $bestProg = self::getBestProgrammeOfTheMonth($yearMonth);
        if ($bestProg) {
            $rating = round($bestProg['average_rating'], 1);
            $db->insert('hall_of_fame', [
                'year_month' => $yearMonth,
                'winner_type' => 'program',
                'entity_id_int' => (int)$bestProg['id'],
                'score' => (float)$bestProg['score'],
                'meta_name' => $bestProg['name'],
                'meta_subtext' => $rating . ' ★ • ' . $bestProg['participants'] . ' Participants',
                'meta_image' => $bestProg['poster'] ?? ''
            ], false);
        }
    }

    /**
     * Get Student Leaderboard.
     */
    public static function getStudentLeaderboard(string $timeframe = 'This Month', ?string $filterType = null, ?string $filterValue = null, int $limit = 50, ?string $targetMonth = null): array
    {
        $db = db();
        if (!$db->isConfigured()) return [];

        if ($timeframe === 'This Month') {
            $month = $targetMonth ? $targetMonth : date('Y-m');
            $start = $month . '-01T00:00:00';
            $end = date('Y-m-t', strtotime($month . '-01')) . 'T23:59:59';

            $query = '?select=student_id,points,users!student_id(id,nama,avatar_url,fakulti,kolej,level,streak)&created_at=gte.' . $start . '&created_at=lte.' . $end;
            $res = $db->select('rekod_mata', $query);
            if (!$res['ok']) {
                $fallbackQuery = '?select=student_id,points,users!student_id(id,nama,avatar_url,fakulti,kolej)&created_at=gte.' . $start . '&created_at=lte.' . $end;
                $res = $db->select('rekod_mata', $fallbackQuery);
            }

            $pointsByStudent = [];
            if ($res['ok'] && is_array($res['data'])) {
                foreach ($res['data'] as $row) {
                    $studentId = $row['student_id'];
                    $pts = (int)$row['points'];
                    $u = $row['users'] ?? null;
                    if (!$u) continue;

                    // Apply filters in PHP
                    if ($filterType === 'faculty' && !empty($filterValue)) {
                        if (strcasecmp($u['fakulti'] ?? '', $filterValue) !== 0) continue;
                    }
                    if ($filterType === 'college' && !empty($filterValue)) {
                        if (strcasecmp($u['kolej'] ?? '', $filterValue) !== 0) continue;
                    }

                    if (!isset($pointsByStudent[$studentId])) {
                        // Estimate level based on all-time points if level is not returned from DB
                        $points = (int)($u['mata'] ?? 0);
                        $estLvl = $points >= 1000 ? 4 : ($points >= 500 ? 3 : ($points >= 150 ? 2 : 1));

                        $pointsByStudent[$studentId] = [
                            'id' => $studentId,
                            'nama' => $u['nama'],
                            'avatar_url' => $u['avatar_url'] ?? '',
                            'fakulti' => $u['fakulti'] ?? '',
                            'kolej' => $u['kolej'] ?? '',
                            'level' => isset($u['level']) ? (int)$u['level'] : $estLvl,
                            'streak' => $u['streak'] ?? 0,
                            'mata' => 0 // monthly points accumulator
                        ];
                    }
                    if ($pts > 0) {
                        $pointsByStudent[$studentId]['mata'] += $pts;
                    }
                }
            }

            usort($pointsByStudent, function($a, $b) {
                if ($b['mata'] === $a['mata']) {
                    return strcmp($a['nama'], $b['nama']);
                }
                return $b['mata'] <=> $a['mata'];
            });

            return array_slice($pointsByStudent, 0, $limit);

        } else {
            // All Time
            $query = '?select=id,nama,avatar_url,fakulti,kolej,level,streak,mata&peranan=eq.pelajar&order=mata.desc,nama.asc&limit=' . $limit;
            if ($filterType === 'faculty' && !empty($filterValue)) {
                $query .= '&fakulti=eq.' . rawurlencode($filterValue);
            } elseif ($filterType === 'college' && !empty($filterValue)) {
                $query .= '&kolej=eq.' . rawurlencode($filterValue);
            }

            $res = $db->select('users', $query);
            if (!$res['ok']) {
                // Retry without level/streak
                $fallbackQuery = '?select=id,nama,avatar_url,fakulti,kolej,mata&peranan=eq.pelajar&order=mata.desc,nama.asc&limit=' . $limit;
                if ($filterType === 'faculty' && !empty($filterValue)) {
                    $fallbackQuery .= '&fakulti=eq.' . rawurlencode($filterValue);
                } elseif ($filterType === 'college' && !empty($filterValue)) {
                    $fallbackQuery .= '&kolej=eq.' . rawurlencode($filterValue);
                }
                $res = $db->select('users', $fallbackQuery);
            }

            $students = [];
            if ($res['ok'] && is_array($res['data'])) {
                foreach ($res['data'] as $u) {
                    $points = (int)($u['mata'] ?? 0);
                    $estLvl = $points >= 1000 ? 4 : ($points >= 500 ? 3 : ($points >= 150 ? 2 : 1));

                    $students[] = [
                        'id' => $u['id'],
                        'nama' => $u['nama'],
                        'avatar_url' => $u['avatar_url'] ?? '',
                        'fakulti' => $u['fakulti'] ?? '',
                        'kolej' => $u['kolej'] ?? '',
                        'level' => isset($u['level']) ? (int)$u['level'] : $estLvl,
                        'streak' => $u['streak'] ?? 0,
                        'mata' => $points
                    ];
                }
            }
            return $students;
        }
    }

    /**
     * Get Organizer Leaderboard.
     */
    public static function getOrganizerLeaderboard(string $timeframe = 'This Month', ?string $filterType = null, ?string $filterValue = null, int $limit = 3, ?string $targetMonth = null): array
    {
        $db = db();
        if (!$db->isConfigured()) return [];

        $progQuery = '?select=id,nama,tarikh,penganjur_id,users!penganjur_id(id,nama,avatar_url,fakulti,kolej)&penganjur_id=not.is.null';

        if ($timeframe === 'This Month') {
            $month = $targetMonth ? $targetMonth : date('Y-m');
            $start = $month . '-01';
            $end = date('Y-m-t', strtotime($month . '-01'));
            $progQuery .= '&tarikh=gte.' . $start . '&tarikh=lte.' . $end;
        }

        $progRes = $db->select('program', $progQuery);
        if (!$progRes['ok'] || empty($progRes['data'])) {
            return [];
        }

        $pIds = array_column($progRes['data'], 'id');
        $pIdsStr = implode(',', $pIds);

        // Fetch sub-aggregates in bulk
        $regRes = $db->select('pendaftaran', '?select=program_id,status&program_id=in.(' . $pIdsStr . ')');
        $attRes = $db->select('kehadiran', '?select=program_id,status&program_id=in.(' . $pIdsStr . ')');
        $fbRes = $db->select('maklum_balas', '?select=program_id,rating&program_id=in.(' . $pIdsStr . ')');

        $regsByProg = [];
        if ($regRes['ok'] && is_array($regRes['data'])) {
            foreach ($regRes['data'] as $r) {
                if (($r['status'] ?? '') !== 'Cancelled') {
                    $regsByProg[$r['program_id']] = ($regsByProg[$r['program_id']] ?? 0) + 1;
                }
            }
        }

        $attByProg = [];
        if ($attRes['ok'] && is_array($attRes['data'])) {
            foreach ($attRes['data'] as $a) {
                if (($a['status'] ?? '') === 'Hadir') {
                    $attByProg[$a['program_id']] = ($attByProg[$a['program_id']] ?? 0) + 1;
                }
            }
        }

        $fbRatingsByProg = [];
        if ($fbRes['ok'] && is_array($fbRes['data'])) {
            foreach ($fbRes['data'] as $f) {
                $pid = $f['program_id'];
                $rating = (float)$f['rating'];
                if (!isset($fbRatingsByProg[$pid])) {
                    $fbRatingsByProg[$pid] = [];
                }
                $fbRatingsByProg[$pid][] = $rating;
            }
        }

        $orgStats = [];
        foreach ($progRes['data'] as $p) {
            $pid = $p['id'];
            $orgId = $p['penganjur_id'];
            $orgUser = $p['users'] ?? null;
            if (!$orgUser) continue;

            // Apply filters
            if ($filterType === 'faculty' && !empty($filterValue)) {
                if (strcasecmp($orgUser['fakulti'] ?? '', $filterValue) !== 0) continue;
            }
            if ($filterType === 'college' && !empty($filterValue)) {
                if (strcasecmp($orgUser['kolej'] ?? '', $filterValue) !== 0) continue;
            }

            $regs = $regsByProg[$pid] ?? 0;
            $att = $attByProg[$pid] ?? 0;
            $ratings = $fbRatingsByProg[$pid] ?? [];

            $attRate = $regs > 0 ? ($att / $regs) : 0;
            $fbCount = count($ratings);
            $fbRate = $att > 0 ? ($fbCount / $att) : 0;
            $avgRating = $fbCount > 0 ? (array_sum($ratings) / $fbCount) : 0.0;

            if (!isset($orgStats[$orgId])) {
                $orgStats[$orgId] = [
                    'id' => $orgId,
                    'name' => $orgUser['nama'],
                    'avatar_url' => $orgUser['avatar_url'] ?? '',
                    'fakulti' => $orgUser['fakulti'] ?? '',
                    'kolej' => $orgUser['kolej'] ?? '',
                    'events_conducted' => 0,
                    'total_participants' => 0,
                    'total_attendance' => 0,
                    'ratings_sum' => 0.0,
                    'ratings_count' => 0,
                    'attendance_rates' => [],
                    'feedback_rates' => []
                ];
            }

            $orgStats[$orgId]['events_conducted']++;
            $orgStats[$orgId]['total_participants'] += $regs;
            $orgStats[$orgId]['total_attendance'] += $att;
            $orgStats[$orgId]['ratings_sum'] += ($avgRating * $fbCount);
            $orgStats[$orgId]['ratings_count'] += $fbCount;
            $orgStats[$orgId]['attendance_rates'][] = $attRate;
            $orgStats[$orgId]['feedback_rates'][] = $fbRate;
        }

        $leaderboard = [];
        foreach ($orgStats as $orgId => $o) {
            $avgRating = $o['ratings_count'] > 0 ? ($o['ratings_sum'] / $o['ratings_count']) : 0.0;
            $avgAttRate = !empty($o['attendance_rates']) ? (array_sum($o['attendance_rates']) / count($o['attendance_rates'])) : 0.0;
            $avgFbRate = !empty($o['feedback_rates']) ? (array_sum($o['feedback_rates']) / count($o['feedback_rates'])) : 0.0;

            // Score formula:
            // score = (total_events * 50) + (avg_rating * 40) + (avg_attendance_rate * 150) + (total_participants * 0.2) + (avg_feedback_rate * 100)
            $score = ($o['events_conducted'] * 50) + ($avgRating * 40) + ($avgAttRate * 150) + ($o['total_participants'] * 0.2) + ($avgFbRate * 100);

            $leaderboard[] = [
                'id' => $orgId,
                'name' => $o['name'],
                'avatar_url' => $o['avatar_url'],
                'fakulti' => $o['fakulti'],
                'kolej' => $o['kolej'],
                'events_conducted' => $o['events_conducted'],
                'average_rating' => $avgRating,
                'average_attendance_rate' => $avgAttRate,
                'total_participants' => $o['total_participants'],
                'total_attendance' => $o['total_attendance'],
                'score' => $score
            ];
        }

        usort($leaderboard, function($a, $b) {
            if ($b['score'] === $a['score']) {
                return strcmp($a['name'], $b['name']);
            }
            return $b['score'] <=> $a['score'];
        });

        return array_slice($leaderboard, 0, $limit);
    }

    /**
     * Get Best Program of the Month.
     */
    public static function getBestProgrammeOfTheMonth(?string $targetMonth = null): ?array
    {
        $db = db();
        if (!$db->isConfigured()) return null;

        $month = $targetMonth ? $targetMonth : date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($month . '-01'));

        $progRes = $db->select('program', '?select=id,nama,tarikh,poster_url,gambar,penganjur_id,users!penganjur_id(nama)&tarikh=gte.' . $start . '&tarikh=lte.' . $end);
        if (!$progRes['ok'] || empty($progRes['data'])) {
            return null;
        }

        $pIds = array_column($progRes['data'], 'id');
        $pIdsStr = implode(',', $pIds);

        // Bulk fetch metrics
        $regRes = $db->select('pendaftaran', '?select=program_id,status&program_id=in.(' . $pIdsStr . ')');
        $attRes = $db->select('kehadiran', '?select=program_id,status&program_id=in.(' . $pIdsStr . ')');
        $fbRes = $db->select('maklum_balas', '?select=program_id,rating&program_id=in.(' . $pIdsStr . ')');

        $regsByProg = [];
        if ($regRes['ok'] && is_array($regRes['data'])) {
            foreach ($regRes['data'] as $r) {
                if (($r['status'] ?? '') !== 'Cancelled') {
                    $regsByProg[$r['program_id']] = ($regsByProg[$r['program_id']] ?? 0) + 1;
                }
            }
        }

        $attByProg = [];
        if ($attRes['ok'] && is_array($attRes['data'])) {
            foreach ($attRes['data'] as $a) {
                if (($a['status'] ?? '') === 'Hadir') {
                    $attByProg[$a['program_id']] = ($attByProg[$a['program_id']] ?? 0) + 1;
                }
            }
        }

        $fbRatingsByProg = [];
        if ($fbRes['ok'] && is_array($fbRes['data'])) {
            foreach ($fbRes['data'] as $f) {
                $pid = $f['program_id'];
                $rating = (float)$f['rating'];
                if (!isset($fbRatingsByProg[$pid])) {
                    $fbRatingsByProg[$pid] = [];
                }
                $fbRatingsByProg[$pid][] = $rating;
            }
        }

        $programsList = [];
        foreach ($progRes['data'] as $p) {
            $pid = $p['id'];
            $regs = $regsByProg[$pid] ?? 0;
            $att = $attByProg[$pid] ?? 0;
            $ratings = $fbRatingsByProg[$pid] ?? [];

            $attRate = $regs > 0 ? ($att / $regs) : 0;
            $fbCount = count($ratings);
            $fbRate = $att > 0 ? ($fbCount / $att) : 0;
            $avgRating = $fbCount > 0 ? (array_sum($ratings) / $fbCount) : 0.0;

            // Score formula:
            // score = (average_rating * 40) + (attendance_rate * 100) + (feedback_rate * 80) + (participants * 0.1)
            $score = ($avgRating * 40) + ($attRate * 100) + ($fbRate * 80) + ($regs * 0.1);

            $programsList[] = [
                'id' => $pid,
                'name' => $p['nama'],
                'date' => $p['tarikh'],
                'poster' => !empty($p['poster_url']) ? $p['poster_url'] : (!empty($p['gambar']) ? $p['gambar'] : 'program1.jpg'),
                'organizer_name' => $p['users']['nama'] ?? 'Unknown Organizer',
                'participants' => $regs,
                'attendance_rate' => $attRate * 100, // percentage
                'feedback_rate' => $fbRate * 100,
                'average_rating' => $avgRating,
                'score' => $score
            ];
        }

        usort($programsList, fn($a, $b) => $b['score'] <=> $a['score']);
        return !empty($programsList) ? $programsList[0] : null;
    }

    /**
     * Get Personal Rank Widget stats for Student.
     */
    public static function getPersonalRankWidget(string $studentId): array
    {
        $db = db();
        if (!$db->isConfigured()) {
            return ['rank' => 0, 'total' => 0, 'progress' => 0, 'needed' => 0];
        }

        // Get monthly points leaderboard of ALL students
        $students = self::getStudentLeaderboard('This Month', null, null, 10000);
        $totalStudents = count($students);

        if ($totalStudents === 0) {
            // Fallback to all-time leaderboard rank
            $allStudents = self::getStudentLeaderboard('All Time', null, null, 10000);
            $totalStudents = count($allStudents);
            $students = $allStudents;
        }

        $myRank = 0;
        $myPoints = 0;
        foreach ($students as $index => $s) {
            if ($s['id'] === $studentId) {
                $myRank = $index + 1;
                $myPoints = $s['mata'];
                break;
            }
        }

        // Get points of student at rank #10 (or rank #1 if less than 10 students)
        $targetRankIndex = min(9, max(0, $totalStudents - 1));
        $targetPoints = isset($students[$targetRankIndex]) ? $students[$targetRankIndex]['mata'] : 0;

        $needed = max(0, $targetPoints - $myPoints);

        // Progress bar percentage
        $progress = 0;
        if ($myRank <= 10) {
            $progress = 100;
        } else {
            // Relate my points to target rank points
            $progress = $targetPoints > 0 ? round(($myPoints / $targetPoints) * 100) : 0;
            $progress = min(100, max(0, $progress));
        }

        return [
            'rank' => $myRank,
            'total' => $totalStudents,
            'points' => $myPoints,
            'target_points' => $targetPoints,
            'needed' => $needed,
            'progress' => $progress
        ];
    }

    /**
     * Get Personal Organizer Rank Widget stats.
     */
    public static function getPersonalOrganizerRankWidget(string $organizerId): array
    {
        $db = db();
        if (!$db->isConfigured()) {
            return ['rank' => 0, 'total' => 0, 'completed_events_this_month' => 0, 'high_rated_events' => 0];
        }

        $organizers = self::getOrganizerLeaderboard('This Month', null, null, 1000);
        $totalOrganizers = count($organizers);

        $myRank = 0;
        foreach ($organizers as $index => $o) {
            if ($o['id'] === $organizerId) {
                $myRank = $index + 1;
                break;
            }
        }

        // Fetch completed events and ratings for this organizer in the current month
        $month = date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($month . '-01'));

        $eventsCount = 0;
        $highRatedCount = 0;

        $progRes = $db->select('program', '?select=id,tarikh&penganjur_id=eq.' . rawurlencode($organizerId) . '&tarikh=gte.' . $start . '&tarikh=lte.' . $end);
        if ($progRes['ok'] && is_array($progRes['data'])) {
            $eventsCount = count($progRes['data']);
            
            // Fetch rating for each program
            foreach ($progRes['data'] as $p) {
                $fbRes = $db->select('maklum_balas', '?select=rating&program_id=eq.' . $p['id']);
                if ($fbRes['ok'] && !empty($fbRes['data'])) {
                    $ratings = array_column($fbRes['data'], 'rating');
                    $avg = array_sum($ratings) / count($ratings);
                    if ($avg >= 4.8) {
                        $highRatedCount++;
                    }
                }
            }
        }

        return [
            'rank' => $myRank,
            'total' => $totalOrganizers,
            'completed_events_this_month' => $eventsCount,
            'high_rated_events' => $highRatedCount
        ];
    }

    /**
     * Get Hall of Fame records.
     */
    public static function getHallOfFame(): array
    {
        $db = db();
        if (!$db->isConfigured()) return [];

        $res = $db->select('hall_of_fame', '?order=year_month.desc,winner_type.asc');
        $history = [];

        if ($res['ok'] && is_array($res['data'])) {
            foreach ($res['data'] as $row) {
                $ym = $row['year_month'];
                if (!isset($history[$ym])) {
                    $history[$ym] = [
                        'month_name' => date('F Y', strtotime($ym . '-01')),
                        'student' => null,
                        'organizer' => null,
                        'program' => null
                    ];
                }

                $winner = [
                    'name' => $row['meta_name'],
                    'subtext' => $row['meta_subtext'],
                    'image' => $row['meta_image'],
                    'score' => $row['score']
                ];

                if ($row['winner_type'] === 'student') {
                    $history[$ym]['student'] = $winner;
                } elseif ($row['winner_type'] === 'organizer') {
                    $history[$ym]['organizer'] = $winner;
                } elseif ($row['winner_type'] === 'program') {
                    $history[$ym]['program'] = $winner;
                }
            }
        }

        return $history;
    }

    /**
     * Get Monthly Analytics for Administration Dashboard.
     */
    public static function getAdminMonthlyAnalytics(): array
    {
        $db = db();
        if (!$db->isConfigured()) {
            return [
                'active_students' => 0,
                'active_organizers' => 0,
                'top_student' => null,
                'top_organizer' => null,
                'best_program' => null
            ];
        }

        $month = date('Y-m');
        $start = $month . '-01T00:00:00';
        $end = date('Y-m-t', strtotime($month . '-01')) . 'T23:59:59';

        // Count active students (distinct student_id in rekod_mata in current month)
        $actStudRes = $db->select('rekod_mata', '?select=student_id&created_at=gte.' . $start . '&created_at=lte.' . $end);
        $actStudCount = 0;
        if ($actStudRes['ok'] && is_array($actStudRes['data'])) {
            $actStudCount = count(array_unique(array_column($actStudRes['data'], 'student_id')));
        }

        // Count active organizers (distinct penganjur_id for programs with tarikh in current month)
        $pStart = $month . '-01';
        $pEnd = date('Y-m-t', strtotime($month . '-01'));
        $actOrgRes = $db->select('program', '?select=penganjur_id&penganjur_id=not.is.null&tarikh=gte.' . $pStart . '&tarikh=lte.' . $pEnd);
        $actOrgCount = 0;
        if ($actOrgRes['ok'] && is_array($actOrgRes['data'])) {
            $actOrgCount = count(array_unique(array_column($actOrgRes['data'], 'penganjur_id')));
        }

        // Get Top Student
        $topStuds = self::getStudentLeaderboard('This Month', null, null, 1);
        $topStud = !empty($topStuds) ? $topStuds[0] : null;

        // Get Top Organizer
        $topOrgs = self::getOrganizerLeaderboard('This Month', null, null, 1);
        $topOrg = !empty($topOrgs) ? $topOrgs[0] : null;

        // Get Best Program
        $bestProg = self::getBestProgrammeOfTheMonth();

        return [
            'active_students' => $actStudCount,
            'active_organizers' => $actOrgCount,
            'top_student' => $topStud,
            'top_organizer' => $topOrg,
            'best_program' => $bestProg
        ];
    }
}

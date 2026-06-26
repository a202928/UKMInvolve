<?php

/**
 * public.users — columns: id, nama, emel, password_hash, peranan, matrik, fakulti, organisasi, status, mata, created_at
 */
class UserRepository
{
    private const TABLE = 'users';

    public function __construct(private SupabaseClient $db)
    {
    }

    private function firstRow(array $result): ?array
    {
        if (!$result['ok']) {
            return null;
        }

        $data = $result['data'] ?? null;
        if (!is_array($data)) {
            return null;
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        if (isset($data['id'])) {
            return $data;
        }

        return null;
    }

    /** Find user by emel (lowercase) */
    public function findByEmel(string $emel): ?array
    {
        $emel = strtolower(trim($emel));

        $rpc = $this->db->rpc('get_user_by_emel', ['p_emel' => $emel]);
        $row = $this->firstRow($rpc);
        if ($row !== null) {
            return $row;
        }

        $result = $this->db->select(
            self::TABLE,
            '?emel=eq.' . rawurlencode($emel) . '&limit=1'
        );

        return $this->firstRow($result);
    }

    /**
     * Verify emel + plain password against password_hash column.
     */
    public function verifyLogin(string $emel, string $plainPassword): ?array
    {
        $user = $this->findByEmel($emel);
        if (!$user) {
            return null;
        }

        $hash = $user['password_hash'] ?? '';
        if ($hash === '' || !password_verify($plainPassword, $hash)) {
            return null;
        }

        return $user;
    }

    /**
     * Register new user into public.users
     */
    public function create(array $data): array
    {
        $plainPassword = $data['kata_laluan'] ?? $data['password'] ?? '';

        $payload = [
            'nama' => trim($data['nama'] ?? ''),
            'emel' => strtolower(trim($data['emel'] ?? '')),
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'peranan' => $data['peranan'] ?? 'pelajar',
            'matrik' => $data['matrik'] ?? null,
            'fakulti' => $data['fakulti'] ?? null,
            'organisasi' => $data['organisasi'] ?? null,
            'status' => $data['status'] ?? 'aktif',
            'mata' => (int) ($data['mata'] ?? 0),
            'verification_code' => $data['verification_code'] ?? null,
            'verification_code_expires_at' => $data['verification_code_expires_at'] ?? null,
        ];

        return $this->db->insert(self::TABLE, $payload);
    }

    public function update(string $id, array $data): array
    {
        return $this->db->update(self::TABLE, '?id=eq.' . rawurlencode($id), $data);
    }

    public function findById(string $id): ?array
    {
        $result = $this->db->select(
            self::TABLE,
            '?id=eq.' . rawurlencode($id) . '&limit=1'
        );
        return $this->firstRow($result);
    }

    public function listAll(): array
    {
        $result = $this->db->select(self::TABLE, '?order=nama.asc');
        return $result['ok'] ? ($result['data'] ?? []) : [];
    }

    public function countAll(): int
    {
        $result = $this->db->select(self::TABLE, '?select=id');
        return $result['ok'] ? count($result['data']) : 0;
    }

    public function countByPeranan(string $peranan): int
    {
        $result = $this->db->select(
            self::TABLE,
            '?select=id&peranan=eq.' . rawurlencode($peranan)
        );
        return $result['ok'] ? count($result['data']) : 0;
    }

    public function countByStatus(string $status): int
    {
        $result = $this->db->select(
            self::TABLE,
            '?select=id&status=eq.' . rawurlencode($status)
        );
        return $result['ok'] ? count($result['data']) : 0;
    }

    public function toAdminRow(array $user): array
    {
        return [
            'id' => $user['id'],
            'nama' => $user['nama'],
            'emel' => $user['emel'],
            'peranan' => $user['peranan'],
            'fakulti' => $user['fakulti'] ?? '',
            'matrik' => $user['matrik'] ?? '',
            'organisasi' => $user['organisasi'] ?? '',
            'organizer_type' => $user['organizer_type'] ?? '',
            'status' => $user['status'] ?? 'aktif',
            'created_at' => !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-',
        ];
    }

    /**
     * Retrieve all badges earned by a student from the database.
     */
    public function getEarnedBadges(string $studentId): array
    {
        $result = $this->db->select(
            'lencana_pelajar',
            '?select=*,lencana(*)&student_id=eq.' . rawurlencode($studentId) . '&order=created_at.asc'
        );
        if (!$result['ok'] || empty($result['data'])) {
            return [];
        }
        
        $badges = [];
        foreach ($result['data'] as $row) {
            if (!empty($row['lencana'])) {
                $badges[] = $row['lencana'];
            }
        }
        return $badges;
    }

    /**
     * Award points to a student for a specific activity type, preventing duplicate records.
     */
    public function awardPoints(string $studentId, string $activityType, ?int $programId = null, ?int $customPoints = null): bool
    {
        $ruleTitle = match ($activityType) {
            'registration' => 'Daftar Program',
            'attendance' => 'Hadir Program',
            'feedback' => 'Beri Maklum Balas',
            'interest' => 'Lengkapkan Minat',
            'crew_attendance' => 'Penyertaan Crew/AJK',
            'welcome' => 'Welcome Points',
            default => null
        };

        if (!$ruleTitle) {
            return false;
        }

        // Query active multiplier value from DB
        $pts = 0;
        if ($customPoints !== null) {
            $pts = $customPoints;
        } elseif ($activityType === 'feedback') {
            $pts = 10;
        } else {
            $ruleResult = $this->db->select('mata_peraturan', '?title=eq.' . rawurlencode($ruleTitle) . '&limit=1');
            if ($ruleResult['ok'] && !empty($ruleResult['data'][0])) {
                $pts = (int)($ruleResult['data'][0]['value'] ?? 0);
            } else {
                // Default fallbacks if Supabase rules are empty
                $pts = match ($activityType) {
                    'registration' => 20,
                    'attendance' => 100,
                    'interest' => 50,
                    'crew_attendance' => 200,
                    'welcome' => 20,
                    default => 0
                };
            }
        }

        // Duplication check to enforce database integrity
        $query = '?student_id=eq.' . rawurlencode($studentId) . '&activity_type=eq.' . rawurlencode($activityType);
        if ($programId !== null) {
            $query .= '&program_id=eq.' . $programId;
        } else {
            $query .= '&program_id=is.null';
        }

        $existing = $this->db->select('rekod_mata', $query);
        if ($existing['ok'] && count($existing['data']) > 0) {
            return false;
        }

        // Insert point log entry
        $payload = [
            'student_id' => $studentId,
            'activity_type' => $activityType,
            'points' => $pts,
            'program_id' => $programId
        ];
        
        $res = $this->db->insert('rekod_mata', $payload);
        if ($res['ok']) {
            $this->updateStudentPointsAndBadges($studentId);
            return true;
        }

        return false;
    }

    /**
     * Revoke points for a specific student activity (used when attendance is unmarked).
     */
    public function revokePoints(string $studentId, string $activityType, ?int $programId = null): bool
    {
        $query = '?student_id=eq.' . rawurlencode($studentId) . '&activity_type=eq.' . rawurlencode($activityType);
        if ($programId !== null) {
            $query .= '&program_id=eq.' . $programId;
        } else {
            $query .= '&program_id=is.null';
        }

        $existing = $this->db->select('rekod_mata', $query);
        if ($existing['ok'] && count($existing['data']) > 0) {
            $recId = $existing['data'][0]['id'];
            $res = $this->db->delete('rekod_mata', '?id=eq.' . $recId);
            if ($res['ok']) {
                $this->updateStudentPointsAndBadges($studentId);
                return true;
            }
        }
        return false;
    }

    /**
     * Summarize student points in rekod_mata, sync users.mata column,
     * refresh active session, and automatically assign newly qualified badges.
     */
    public function updateStudentPointsAndBadges(string $studentId): array
    {
        // 1. Calculate sum from rekod_mata
        $sumResult = $this->db->select('rekod_mata', '?select=points&student_id=eq.' . rawurlencode($studentId));
        $totalPoints = 0;
        if ($sumResult['ok'] && is_array($sumResult['data'])) {
            foreach ($sumResult['data'] as $rec) {
                $totalPoints += (int)($rec['points'] ?? 0);
            }
        }

        // 2. Sync users table (mata, level, and streak)
        $prog = ProgressionService::getStudentProgression($studentId);
        $this->db->update('users', '?id=eq.' . rawurlencode($studentId), [
            'mata' => $totalPoints,
            'level' => $prog['level'],
            'streak' => $prog['streak']
        ]);

        // 3. Keep current session in sync
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $studentId) {
            $_SESSION['mata'] = $totalPoints;
        }

        // 4. Evaluate badges criteria
        $badgesResult = $this->db->select('lencana');
        $allBadges = $badgesResult['ok'] ? ($badgesResult['data'] ?? []) : [];

        $earnedResult = $this->db->select('lencana_pelajar', '?student_id=eq.' . rawurlencode($studentId));
        $earnedBadgeIds = [];
        if ($earnedResult['ok'] && is_array($earnedResult['data'])) {
            foreach ($earnedResult['data'] as $eb) {
                $earnedBadgeIds[] = (int)$eb['lencana_id'];
            }
        }

        // Fetch attendance count for 'activity_count' criteria
        $attendedCount = 0;
        $attResult = $this->db->select('kehadiran', '?pelajar_id=eq.' . rawurlencode($studentId) . '&status=eq.Hadir');
        if ($attResult['ok'] && is_array($attResult['data'])) {
            $attendedCount = count($attResult['data']);
        }

        $newlyEarned = [];
        foreach ($allBadges as $badge) {
            $badgeId = (int)$badge['id'];
            if (in_array($badgeId, $earnedBadgeIds, true)) {
                continue;
            }

            $criteria = $badge['kriteria'] ?? 'points';
            $threshold = (int)($badge['syarat_nilai'] ?? 0);
            $qualifies = false;

            if ($criteria === 'points' && $totalPoints >= $threshold) {
                $qualifies = true;
            } elseif ($criteria === 'activity_count' && $attendedCount >= $threshold) {
                $qualifies = true;
            } elseif ($criteria === 'email_verified') {
                $user = $this->findById($studentId);
                if ($user && ($user['status'] ?? '') === 'aktif') {
                    $qualifies = true;
                }
            }

            if ($qualifies) {
                $ins = $this->db->insert('lencana_pelajar', [
                    'student_id' => $studentId,
                    'lencana_id' => $badgeId
                ]);
                if ($ins['ok']) {
                    $newlyEarned[] = $badge['nama'];
                }
            }
        }

        return [
            'total_points' => $totalPoints,
            'new_badges' => $newlyEarned
        ];
    }

    public function updateStatus(string $userId, string $status): array
    {
        return $this->db->update(self::TABLE, '?id=eq.' . rawurlencode($userId), ['status' => $status]);
    }

    /**
     * Delete pending accounts that were created more than 7 days ago and never verified.
     */
    public function cleanExpiredPendingAccounts(): void
    {
        $cutoff = date('Y-m-d\TH:i:s', strtotime('-7 days'));
        $this->db->delete(self::TABLE, '?status=eq.pending&created_at=lt.' . $cutoff);
    }

    public function deleteUser(string $userId): array
    {
        return $this->db->delete(self::TABLE, '?id=eq.' . rawurlencode($userId));
    }

    public function updateUser(string $userId, array $payload): array
    {
        return $this->db->update(self::TABLE, '?id=eq.' . rawurlencode($userId), $payload);
    }

    /**
     * Classifies organizer account into 'faculty', 'college', or 'organization' dynamically
     */
    public function getOrganizerType(array $user): string
    {
        if (!empty($user['organizer_type'])) {
            return $user['organizer_type'];
        }
        
        $name = strtoupper(trim($user['nama'] ?? ''));
        
        // Check college matches
        if (
            str_starts_with($name, 'KOL') || 
            str_contains($name, 'COLLEGE') || 
            str_contains($name, 'KOLEJ') || 
            in_array($name, ['KDO', 'KPZ', 'KTSN', 'KUO', 'KIZ', 'KAB', 'KBH', 'KKM', 'KRK', 'KTDI', 'KTHO', 'KIY'])
        ) {
            return 'college';
        }
        
        // Check faculty matches (e.g., PMFST, PMFEP, FPEND, FUU, PEMATRA, PERSIAP)
        if (
            str_starts_with($name, 'PMF') || 
            str_starts_with($name, 'F') || 
            in_array($name, ['PEMATRA', 'PERSIAP'])
        ) {
            return 'faculty';
        }
        
        return 'organization';
    }
}


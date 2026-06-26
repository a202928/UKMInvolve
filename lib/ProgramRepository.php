<?php

class ProgramRepository
{
    public function __construct(private SupabaseClient $db)
    {
    }

    private function getContextFilter(): ?array
    {
        // Return null to disable filtering events from search results based on audience.
        // We want all users (even guests or other faculties) to see all events, 
        // they just won't be able to register.
        return null;
    }

    private function resolveLocation(array $row): string
    {
        $venueType = $row['venue_type'] ?? 'physical';
        if ($venueType === 'online') {
            return !empty($row['platform']) ? 'Online (' . $row['platform'] . ')' : 'Online Event';
        }
        return !empty($row['venue_name']) ? $row['venue_name'] : ($row['lokasi'] ?? '');
    }

    public function listWithCategory(?array $studentContext = null): array
    {
        $result = $this->db->select(
            'program',
            '?select=*,kategori(nama,slug,icon,color),users!penganjur_id(nama)&penganjur_id=not.is.null&order=tarikh.desc'
        );

        if (!$result['ok']) {
            return [];
        }

        $programs = $result['data'];
        $ctx = $studentContext !== null ? $studentContext : $this->getContextFilter();
        
        if ($ctx !== null) {
            $programs = array_filter($programs, function($p) use ($ctx) {
                $audience = $p['target_audience'] ?? ['ALL'];
                $targets = is_array($audience) ? $audience : (json_decode((string)$audience, true) ?: ['ALL']);
                if (in_array('ALL', $targets, true)) return true;
                $fakulti = $ctx['fakulti'] ?? '';
                $kolej = $ctx['kolej'] ?? '';
                return in_array($fakulti, $targets, true) || in_array($kolej, $targets, true);
            });
        }

        return array_values($programs);
    }

    public function listActiveWithCategory(?array $studentContext = null): array
    {
        $today = date('Y-m-d');
        $result = $this->db->select(
            'program',
            '?select=*,kategori(nama,slug,icon,color),users!penganjur_id(nama)&penganjur_id=not.is.null&tarikh=gte.' . $today . '&status=neq.Cancelled&order=tarikh.asc'
        );

        if (!$result['ok']) {
            return [];
        }

        $programs = $result['data'];
        $ctx = $studentContext !== null ? $studentContext : $this->getContextFilter();

        if ($ctx !== null) {
            $programs = array_filter($programs, function($p) use ($ctx) {
                $audience = $p['target_audience'] ?? ['ALL'];
                $targets = is_array($audience) ? $audience : (json_decode((string)$audience, true) ?: ['ALL']);
                if (in_array('ALL', $targets, true)) return true;
                $fakulti = $ctx['fakulti'] ?? '';
                $kolej = $ctx['kolej'] ?? '';
                return in_array($fakulti, $targets, true) || in_array($kolej, $targets, true);
            });
        }

        return array_values($programs);
    }

    public function listByOrganizer(string $organizerId): array
    {
        $result = $this->db->select(
            'program',
            '?select=*,kategori(nama,slug,icon,color),users!penganjur_id(nama)&penganjur_id=eq.' . rawurlencode($organizerId) . '&order=tarikh.desc'
        );

        if (!$result['ok']) {
            return [];
        }

        return $result['data'];
    }

    public function findById(int $id): ?array
    {
        $result = $this->db->select(
            'program',
            '?select=*,kategori(nama,slug,icon,color),users!penganjur_id(nama),program_objektif(teks,sort_order),program_keperluan(teks,sort_order),program_sesi(tarikh,masa_mulai,masa_tamat,topik)&id=eq.' . $id . '&limit=1'
        );

        if (!$result['ok'] || empty($result['data'][0])) {
            return null;
        }

        return $result['data'][0];
    }

    public function create(array $data): array
    {
        return $this->db->insert('program', $data);
    }

    public function update(int $id, array $data): array
    {
        return $this->db->update('program', '?id=eq.' . $id, $data);
    }

    public function updateParticipantCount(int $id, int $count): array
    {
        return $this->db->update('program', '?id=eq.' . $id, ['peserta_semasa' => $count]);
    }

    public function toOrganizerCard(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 0);
        $categoryName = $row['kategori']['nama'] ?? 'Umum';
        $status = programStatusLabel($row['tarikh'] ?? null, $row['status'] ?? null);

        return [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'tarikh' => formatProgramDates($row['start_date'] ?? null, $row['end_date'] ?? null, $row['tarikh'] ?? null),
            'masa' => formatProgramTimes($row['start_time'] ?? null, $row['end_time'] ?? null, $row['masa'] ?? null),
            'lokasi' => $this->resolveLocation($row),
            'kategori' => $categoryName,
            'peserta' => $participants . '/' . $capacity,
            'status' => $status,
            'poster' => $row['poster_url'] ?? '',
            'jenis_pendaftaran' => $row['jenis_pendaftaran'] ?? 'Peserta',
            'venue_type' => $row['venue_type'] ?? 'physical',
            'platform' => $row['platform'] ?? '',
            'shares_count' => (int)($row['shares_count'] ?? 0),
        ];
    }

    public function toStudentSearchRow(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 1);
        $categoryName = $row['kategori']['nama'] ?? 'Umum';
        $organizerName = $row['users']['nama'] ?? 'Penganjur';
        $isCompleted = isProgramCompleted($row);

        return [
            'id' => $row['id'],
            'title' => $row['nama'],
            'date' => formatProgramDates($row['start_date'] ?? null, $row['end_date'] ?? null, $row['tarikh'] ?? null),
            'time' => formatProgramTimes($row['start_time'] ?? null, $row['end_time'] ?? null, $row['masa'] ?? null),
            'location' => $this->resolveLocation($row),
            'category' => $categoryName,
            'category_slug' => $row['kategori']['slug'] ?? '',
            'category_icon' => $row['kategori']['icon'] ?? 'fa-layer-group',
            'category_color' => $row['kategori']['color'] ?? '#5b8def',
            'organizer' => $organizerName,
            'description' => $row['penerangan'] ?? '',
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
            'participants' => $participants,
            'capacity' => $capacity,
            'status' => programAvailability($participants, $capacity),
            'rating' => $isCompleted ? (float) ($row['rating'] ?? 4.5) : 0.0,
            'points' => (int) ($row['mata'] ?? 100),
            'jenis_pendaftaran' => $row['jenis_pendaftaran'] ?? 'Peserta',
            'is_completed' => $isCompleted,
            'time_status' => getProgramStatusTimeBased($row),
            'start_date' => $row['start_date'] ?? $row['tarikh'] ?? null,
            'end_date' => $row['end_date'] ?? $row['tarikh'] ?? null,
            'start_time' => $row['start_time'] ?? $row['masa'] ?? null,
            'end_time' => $row['end_time'] ?? $row['masa'] ?? null,
            'db_status' => $row['status'] ?? null,
            'penganjur_id' => $row['penganjur_id'] ?? null,
            'target_audience' => $row['target_audience'] ?? ['ALL'],
            'venue_type' => $row['venue_type'] ?? 'physical',
            'platform' => $row['platform'] ?? '',
            'venue_name' => $row['venue_name'] ?? '',
            'shares_count' => (int)($row['shares_count'] ?? 0),
        ];
    }

    public function toStudentDetail(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 1);
        $organizerName = $row['users']['nama'] ?? 'Penganjur';
        $isCompleted = isProgramCompleted($row);

        $objectives = [];
        foreach ($row['program_objektif'] ?? [] as $item) {
            $objectives[] = $item['teks'];
        }

        $requirements = [];
        foreach ($row['program_keperluan'] ?? [] as $item) {
            $requirements[] = $item['teks'];
        }

        $sessions = [];
        foreach ($row['program_sesi'] ?? [] as $session) {
            $sessions[] = [
                'date' => $session['tarikh'],
                'time' => formatTimeRange($session['masa_mulai'] ?? null) . ' - ' . formatTimeRange($session['masa_tamat'] ?? null),
                'topic' => $session['topik'],
            ];
        }

        return [
            'id' => $row['id'],
            'title' => $row['nama'],
            'date' => formatProgramDates($row['start_date'] ?? null, $row['end_date'] ?? null, $row['tarikh'] ?? null),
            'time' => formatProgramTimes($row['start_time'] ?? null, $row['end_time'] ?? null, $row['masa'] ?? null),
            'location' => $this->resolveLocation($row),
            'category' => $row['kategori']['nama'] ?? 'Umum',
            'category_icon' => $row['kategori']['icon'] ?? 'fa-layer-group',
            'category_color' => $row['kategori']['color'] ?? '#5b8def',
            'organizer' => $organizerName,
            'description' => $row['penerangan'] ?? '',
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
            'participants' => $participants,
            'capacity' => $capacity,
            'status' => programAvailability($participants, $capacity),
            'rating' => $isCompleted ? (float) ($row['rating'] ?? 4.5) : 0.0,
            'objectives' => $objectives,
            'requirements' => $requirements,
            'contact_person' => $row['contact_person'] ?? '',
            'contact_number' => $row['contact_number'] ?? '',
            'contact_email' => $row['contact_email'] ?? '',
            'whatsapp_link' => $row['whatsapp_link'] ?? '',
            'instagram_link' => $row['instagram_link'] ?? '',
            'telegram_link' => $row['telegram_link'] ?? '',
            'deadline_date' => $row['deadline_date'] ?? '',
            'deadline_time' => $row['deadline_time'] ?? '',
            'multiple_sessions' => count($sessions) > 0,
            'sessions' => $sessions,
            'jenis_pendaftaran' => $row['jenis_pendaftaran'] ?? 'Peserta',
            'is_completed' => $isCompleted,
            'time_status' => getProgramStatusTimeBased($row),
            'start_date' => $row['start_date'] ?? $row['tarikh'] ?? null,
            'end_date' => $row['end_date'] ?? $row['tarikh'] ?? null,
            'start_time' => $row['start_time'] ?? $row['masa'] ?? null,
            'end_time' => $row['end_time'] ?? $row['masa'] ?? null,
            'db_status' => $row['status'] ?? null,
            'target_audience' => $row['target_audience'] ?? ['ALL'],
            'venue_type' => $row['venue_type'] ?? 'physical',
            'venue_name' => $row['venue_name'] ?? '',
            'venue_address' => $row['venue_address'] ?? '',
            'google_maps_link' => $row['google_maps_link'] ?? '',
            'latitude' => $row['latitude'] ?? null,
            'longitude' => $row['longitude'] ?? null,
            'meeting_link' => $row['meeting_link'] ?? '',
            'platform' => $row['platform'] ?? '',
            'shares_count' => (int)($row['shares_count'] ?? 0),
            'penganjur_id' => $row['penganjur_id'] ?? null,
        ];
    }

    public function toEditForm(array $row): array
    {
        return [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'start_date' => $row['start_date'] ?? $row['tarikh'],
            'end_date' => $row['end_date'] ?? $row['tarikh'],
            'start_time' => substr((string) ($row['start_time'] ?? $row['masa'] ?? '09:00:00'), 0, 5),
            'end_time' => substr((string) ($row['end_time'] ?? $row['masa'] ?? '17:00:00'), 0, 5),
            'lokasi' => $row['lokasi'],
            'kategori' => $row['kategori']['nama'] ?? '',
            'kategori_slug' => $row['kategori']['slug'] ?? '',
            'kapasiti' => $row['kapasiti'],
            'penerangan' => $row['penerangan'] ?? '',
            'poster' => $row['poster_url'] ?? '',
            'jenis_pendaftaran' => $row['jenis_pendaftaran'] ?? 'Peserta',
            'mata' => (int) ($row['mata'] ?? 100),
            'deadline_date' => $row['deadline_date'] ?? '',
            'deadline_time' => substr((string)($row['deadline_time'] ?? '23:59:00'), 0, 5),
            'contact_person' => $row['contact_person'] ?? '',
            'contact_number' => $row['contact_number'] ?? '',
            'contact_email' => $row['contact_email'] ?? '',
            'whatsapp_link' => $row['whatsapp_link'] ?? '',
            'instagram_link' => $row['instagram_link'] ?? '',
            'telegram_link' => $row['telegram_link'] ?? '',
            'venue_type' => $row['venue_type'] ?? 'physical',
            'venue_name' => $row['venue_name'] ?? '',
            'venue_address' => $row['venue_address'] ?? '',
            'google_maps_link' => $row['google_maps_link'] ?? '',
            'latitude' => $row['latitude'] ?? null,
            'longitude' => $row['longitude'] ?? null,
            'meeting_link' => $row['meeting_link'] ?? '',
            'platform' => $row['platform'] ?? '',
        ];
    }

    public function toRecommendedRow(array $row): array
    {
        $slug = $row['kategori']['slug'] ?? slugify($row['kategori']['nama'] ?? 'umum');

        return [
            'id' => $row['id'],
            'title' => $row['nama'],
            'category' => $slug,
            'category_icon' => $row['kategori']['icon'] ?? 'fa-layer-group',
            'category_color' => $row['kategori']['color'] ?? '#5b8def',
            'date' => formatProgramDates($row['start_date'] ?? null, $row['end_date'] ?? null, $row['tarikh'] ?? null),
            'dateISO' => $row['start_date'] ?? $row['tarikh'] ?? '',
            'location' => $this->resolveLocation($row),
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
            'points' => (int) ($row['mata'] ?? 100),
            'jenis_pendaftaran' => $row['jenis_pendaftaran'] ?? 'Peserta',
            'capacity' => (int) ($row['kapasiti'] ?? 1),
            'participants' => (int) ($row['peserta_semasa'] ?? 0),
            'venue_type' => $row['venue_type'] ?? 'physical',
            'platform' => $row['platform'] ?? '',
            'shares_count' => (int)($row['shares_count'] ?? 0),
        ];
    }

    public function toOrganizerDashboardRow(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 0);
        $status = getProgramStatusTimeBased($row);

        return [
            'name' => $row['nama'],
            'date' => formatProgramDates($row['start_date'] ?? null, $row['end_date'] ?? null, $row['tarikh'] ?? null),
            'participants' => $participants . '/' . $capacity,
            'status' => $status,
        ];
    }

    public function toDashboardEvent(array $row): array
    {
        $categoryName = $row['kategori']['nama'] ?? 'Umum';
        $icon = $row['kategori']['icon'] ?? 'fa-calendar';
        $color = $row['kategori']['color'] ?? '#5b8def';

        return [
            'id' => $row['id'],
            'title' => $row['nama'],
            'date' => formatProgramDates($row['start_date'] ?? null, $row['end_date'] ?? null, $row['tarikh'] ?? null),
            'dateISO' => $row['tarikh'],
            'location' => $this->resolveLocation($row),
            'category' => $categoryName,
            'kategori_slug' => $row['kategori']['slug'] ?? '',
            'points' => (int) ($row['mata'] ?? 100),
            'icon' => $icon,
            'color' => $color,
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
            'venue_type' => $row['venue_type'] ?? 'physical',
            'platform' => $row['platform'] ?? '',
            'shares_count' => (int)($row['shares_count'] ?? 0),
            'organizer' => $row['users']['nama'] ?? 'Penganjur',
            'penganjur_id' => $row['penganjur_id'] ?? null,
        ];
    }

    // --- CREW RECRUITMENT FUNCTIONS ---

    public function getCrewPositions(int $programId): array
    {
        $result = $this->db->select('program_crew_positions', '?program_id=eq.' . $programId . '&order=id.asc');
        return $result['ok'] ? $result['data'] : [];
    }

    public function createCrewPositions(int $programId, array $positions): void
    {
        // First, clear existing to replace
        $this->db->delete('program_crew_positions', '?program_id=eq.' . $programId);
        
        foreach ($positions as $pos) {
            $this->db->insert('program_crew_positions', [
                'program_id' => $programId,
                'nama_jawatan' => trim($pos['name'] ?? ''),
                'deskripsi' => trim($pos['description'] ?? ''),
                'kuota' => (int)($pos['vacancies'] ?? 1),
                'mata_ganjaran' => (int)($pos['points'] ?? 100)
            ]);
        }
    }

    public function applyForCrewPosition(int $programId, string $studentId, int $positionId, string $alasan): array
    {
        // Check if already applied to this program (either participant or crew)
        $existingReg = $this->db->select('pendaftaran', '?program_id=eq.' . $programId . '&pelajar_id=eq.' . rawurlencode($studentId));
        if ($existingReg['ok'] && !empty($existingReg['data'])) {
            return ['ok' => false, 'error' => 'You are already registered for this program as a participant.'];
        }
        $existingCrew = $this->db->select('crew_applications', '?program_id=eq.' . $programId . '&pelajar_id=eq.' . rawurlencode($studentId));
        if ($existingCrew['ok'] && !empty($existingCrew['data'])) {
            return ['ok' => false, 'error' => 'You have already applied for a crew position in this program.'];
        }

        // Check target audience eligibility and deadline
        $programRes = $this->db->select('program', '?select=target_audience,deadline_date,deadline_time&id=eq.' . $programId);
        $userRes = $this->db->select('users', '?select=fakulti,kolej&id=eq.' . rawurlencode($studentId));
        if ($programRes['ok'] && !empty($programRes['data']) && $userRes['ok'] && !empty($userRes['data'])) {
            $programData = $programRes['data'][0];
            $audienceRaw = $programData['target_audience'] ?? ['ALL'];
            $audience = is_string($audienceRaw) ? json_decode($audienceRaw, true) : $audienceRaw;
            if (!is_array($audience)) $audience = ['ALL'];
            
            $uFakulti = $userRes['data'][0]['fakulti'] ?? '';
            $uKolej = $userRes['data'][0]['kolej'] ?? '';
            
            if (!in_array('ALL', $audience)) {
                if (!in_array($uFakulti, $audience) && !in_array($uKolej, $audience)) {
                    return ['ok' => false, 'error' => 'You are not eligible to apply for this program (Faculty/College restricted).'];
                }
            }

            if (!empty($programData['deadline_date'])) {
                $deadlineDateTime = $programData['deadline_date'] . ' ' . ($programData['deadline_time'] ?: '23:59:00');
                if (strtotime($deadlineDateTime) < time()) {
                    return ['ok' => false, 'error' => 'Registration for this program is closed (past deadline).'];
                }
            }
        }

        $payload = [
            'program_id' => $programId,
            'pelajar_id' => $studentId,
            'position_id' => $positionId,
            'status' => 'Pending',
            'alasan' => $alasan
        ];
        return $this->db->insert('crew_applications', $payload);
    }

    public function getCrewApplications(int $programId): array
    {
        $result = $this->db->select(
            'crew_applications', 
            '?select=*,users!pelajar_id(id,nama,fakulti,kolej,mata,level,avatar_url),program_crew_positions!position_id(id,nama_jawatan,kuota,mata_ganjaran)&program_id=eq.' . $programId . '&order=applied_at.desc'
        );
        return $result['ok'] ? $result['data'] : [];
    }

    public function updateCrewApplicationStatus(int $applicationId, string $status): array
    {
        return $this->db->update('crew_applications', '?id=eq.' . $applicationId, [
            'status' => $status,
            'reviewed_at' => date('c')
        ]);
    }

    public function getCrewPositionStats(int $programId): array
    {
        $positions = $this->getCrewPositions($programId);
        if (empty($positions)) return [];

        $apps = $this->getCrewApplications($programId);
        
        $stats = [];
        foreach ($positions as $pos) {
            $pid = $pos['id'];
            $kuota = (int)$pos['kuota'];
            
            $accepted = 0;
            $pending = 0;
            $rejected = 0;
            
            foreach ($apps as $a) {
                if ($a['position_id'] === $pid) {
                    if (in_array($a['status'], ['Accepted', 'Completed'])) $accepted++;
                    elseif ($a['status'] === 'Pending') $pending++;
                    elseif ($a['status'] === 'Rejected') $rejected++;
                }
            }
            
            $remaining = max(0, $kuota - $accepted);
            
            $stats[$pid] = [
                'id' => $pid,
                'name' => $pos['nama_jawatan'],
                'vacancies' => $kuota,
                'accepted' => $accepted,
                'pending' => $pending,
                'rejected' => $rejected,
                'remaining' => $remaining
            ];
        }
        
        return $stats;
    }

    /**
     * Smart Event Recommendation Algorithm
     */
    public function calculateMatchScore(array $program, array $studentData, array $studentInterests, array $followedOrganizers, array $historyFreq): array
    {
        $score = 0;
        $tags = [];
        $now = time();

        $slug = $program['kategori']['slug'] ?? slugify($program['kategori']['nama'] ?? '');
        $categoryName = $program['kategori']['nama'] ?? 'Umum';
        $orgId = $program['penganjur_id'] ?? null;
        
        // 1. Target Audience Match (High Priority)
        // Verify eligibility before assigning any positive score
        $audienceRaw = $program['target_audience'] ?? ['ALL'];
        $audience = is_string($audienceRaw) ? json_decode($audienceRaw, true) : $audienceRaw;
        if (!is_array($audience)) $audience = ['ALL'];
        
        $uFakulti = $studentData['fakulti'] ?? '';
        $uKolej = $studentData['kolej'] ?? '';
        
        if (!in_array('ALL', $audience)) {
            if (!in_array($uFakulti, $audience) && !in_array($uKolej, $audience)) {
                return ['score' => 0, 'tags' => []]; // Exclude immediately if not eligible
            } else {
                $score += 50; // High Priority for targeting them specifically
                $tags[] = ['text' => 'For Your Faculty/College', 'color' => '#8b5cf6', 'icon' => 'fa-bullseye'];
            }
        }

        // 2. Interest Match (High Priority)
        if (!empty($studentInterests) && in_array($slug, $studentInterests, true)) {
            $score += 40;
            $tags[] = ['text' => 'Matches Your Interest: ' . $categoryName, 'color' => '#10b981', 'icon' => 'fa-star'];
        }

        // 3. Event History Match (Medium Priority)
        if (isset($historyFreq[$slug]) && $historyFreq[$slug] >= 1) {
            $score += 25;
            if (count($tags) < 2) {
                $tags[] = ['text' => 'Often Joined By You', 'color' => '#3b82f6', 'icon' => 'fa-clock-rotate-left'];
            }
        }


        // 5. Bonus: High Points or Upcoming
        $points = (int)($program['mata'] ?? 0);
        if ($points >= 150) {
            $score += 10;
            if (empty($tags)) {
                $tags[] = ['text' => 'High Points', 'color' => '#f59e0b', 'icon' => 'fa-arrow-trend-up'];
            }
        }

        $eventDate = $program['start_date'] ?? $program['tarikh'] ?? '';
        if (!empty($eventDate)) {
            $daysDiff = (strtotime($eventDate) - $now) / 86400;
            if ($daysDiff >= 0 && $daysDiff <= 14) {
                $score += 10;
                if (empty($tags)) {
                    $tags[] = ['text' => 'Soon', 'color' => '#ef4444', 'icon' => 'fa-fire'];
                }
            }
        }

        // Add a tiny dynamic variance
        if ($score > 0) {
            $score += rand(0, 5);
        }

        return [
            'score' => $score,
            'tags' => $tags
        ];
    }
}

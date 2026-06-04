<?php

class ProgramRepository
{
    public function __construct(private SupabaseClient $db)
    {
    }

    public function listWithCategory(): array
    {
        $result = $this->db->select(
            'program',
            '?select=*,kategori(nama,slug)&order=tarikh.desc'
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
            '?select=*,kategori(nama,slug),program_objektif(teks,sort_order),program_keperluan(teks,sort_order),program_sesi(tarikh,masa_mulai,masa_tamat,topik)&id=eq.' . $id . '&limit=1'
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

    public function toOrganizerCard(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 0);
        $categoryName = $row['kategori']['nama'] ?? 'Umum';
        $status = programStatusLabel($row['tarikh'] ?? null);

        return [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'tarikh' => formatMalayDate($row['tarikh'] ?? null),
            'masa' => formatTimeRange($row['masa'] ?? null),
            'lokasi' => $row['lokasi'],
            'kategori' => $categoryName,
            'peserta' => $participants . '/' . $capacity,
            'status' => $status,
            'poster' => $row['poster_url'] ?? '',
        ];
    }

    public function toStudentSearchRow(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 1);
        $categoryName = $row['kategori']['nama'] ?? 'Umum';

        return [
            'id' => $row['id'],
            'title' => $row['nama'],
            'date' => $row['tarikh'],
            'time' => formatTimeRange($row['masa'] ?? null),
            'location' => $row['lokasi'],
            'category' => $categoryName,
            'description' => $row['penerangan'] ?? '',
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
            'participants' => $participants,
            'capacity' => $capacity,
            'status' => programAvailability($participants, $capacity),
            'rating' => (float) ($row['rating'] ?? 4.5),
            'points' => (int) ($row['mata'] ?? 100),
        ];
    }

    public function toStudentDetail(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 1);

        $objectives = [];
        foreach ($row['program_objektif'] ?? [] as $item) {
            $objectives[] = $item['teks'];
        }
        usort($objectives, fn($a, $b) => 0);

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
            'date' => $row['tarikh'],
            'time' => formatTimeRange($row['masa'] ?? null),
            'location' => $row['lokasi'],
            'category' => $row['kategori']['nama'] ?? 'Umum',
            'description' => $row['penerangan'] ?? '',
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
            'participants' => $participants,
            'capacity' => $capacity,
            'status' => programAvailability($participants, $capacity),
            'rating' => (float) ($row['rating'] ?? 4.5),
            'objectives' => $objectives,
            'requirements' => $requirements,
            'contact_person' => $row['contact_person'] ?? '',
            'deadline' => $row['deadline'] ?? '',
            'multiple_sessions' => count($sessions) > 0,
            'sessions' => $sessions,
        ];
    }

    public function toEditForm(array $row): array
    {
        return [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'tarikh' => $row['tarikh'],
            'masa' => substr((string) ($row['masa'] ?? '09:00:00'), 0, 5),
            'lokasi' => $row['lokasi'],
            'kategori' => $row['kategori']['nama'] ?? '',
            'kapasiti' => $row['kapasiti'],
            'penerangan' => $row['penerangan'] ?? '',
            'poster' => $row['poster_url'] ?? '',
        ];
    }

    public function toRecommendedRow(array $row): array
    {
        $slug = $row['kategori']['slug'] ?? slugify($row['kategori']['nama'] ?? 'umum');

        return [
            'id' => $row['id'],
            'title' => $row['nama'],
            'category' => $slug,
            'date' => formatMalayDate($row['tarikh'] ?? null),
            'location' => $row['lokasi'],
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
            'points' => (int) ($row['mata'] ?? 100),
        ];
    }

    public function toOrganizerDashboardRow(array $row): array
    {
        $participants = (int) ($row['peserta_semasa'] ?? 0);
        $capacity = (int) ($row['kapasiti'] ?? 0);
        $status = programStatusLabel($row['tarikh'] ?? null);

        return [
            'name' => $row['nama'],
            'date' => formatMalayDate($row['tarikh'] ?? null),
            'participants' => $participants . '/' . $capacity,
            'status' => match ($status) {
                'Aktif' => 'Active',
                'Akan Datang' => 'Upcoming',
                default => 'Completed',
            },
        ];
    }

    public function toDashboardEvent(array $row): array
    {
        $icons = [
            'Kepimpinan' => ['fa-users', 'event-blue'],
            'Teknologi & IT' => ['fa-laptop-code', 'event-sky'],
            'Teknologi' => ['fa-laptop-code', 'event-sky'],
            'Khidmat Komuniti' => ['fa-hands-helping', 'event-green'],
            'Komuniti' => ['fa-hands-helping', 'event-green'],
            'Kerjaya' => ['fa-briefcase', 'event-soft'],
        ];

        $categoryName = $row['kategori']['nama'] ?? 'Umum';
        [$icon, $color] = $icons[$categoryName] ?? ['fa-calendar', 'event-blue'];

        return [
            'title' => $row['nama'],
            'date' => date('d M Y', strtotime($row['tarikh'])),
            'dateISO' => $row['tarikh'],
            'location' => $row['lokasi'],
            'category' => $categoryName,
            'points' => (int) ($row['mata'] ?? 100),
            'icon' => $icon,
            'color' => $color,
            'image' => !empty($row['poster_url']) ? $row['poster_url'] : ($row['gambar'] ?? 'program1.jpg'),
        ];
    }
}

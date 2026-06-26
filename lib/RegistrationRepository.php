<?php

class RegistrationRepository
{
    public function __construct(private SupabaseClient $db)
    {
    }

    public function create(array $data): array
    {
        return $this->db->insert('pendaftaran', $data);
    }

    public function listByStudent(string $studentId): array
    {
        $result = $this->db->select(
            'pendaftaran',
            '?select=*,program(nama,tarikh,lokasi,mata,status,start_date,end_date,start_time,end_time,poster_url,gambar,penganjur_id,kategori(nama),users!penganjur_id(nama))&pelajar_id=eq.' . rawurlencode($studentId) . '&order=tarikh_daftar.desc'
        );

        if (!$result['ok']) {
            return [];
        }

        return $result['data'];
    }

    public function findDuplicate(string $studentId, int $programId): ?array
    {
        // Duplicate check where status is not Cancelled
        $result = $this->db->select(
            'pendaftaran',
            '?pelajar_id=eq.' . rawurlencode($studentId) . '&program_id=eq.' . $programId . '&status=neq.Cancelled&limit=1'
        );

        if ($result['ok'] && !empty($result['data'][0])) {
            return $result['data'][0];
        }
        return null;
    }

    public function countActiveByProgram(int $programId): int
    {
        // Count active registrations where status is not Cancelled
        $result = $this->db->select(
            'pendaftaran',
            '?select=id&program_id=eq.' . $programId . '&status=neq.Cancelled'
        );

        if (!$result['ok']) {
            return 0;
        }

        return count($result['data'] ?? []);
    }

    public function incrementParticipants(int $programId, int $currentCount): array
    {
        return $this->db->update(
            'program',
            '?id=eq.' . $programId,
            ['peserta_semasa' => $currentCount + 1]
        );
    }

    public function countAll(): int
    {
        $result = $this->db->select('pendaftaran', '?select=id');
        return $result['ok'] ? count($result['data']) : 0;
    }

    public function toHistoryRow(array $row): array
    {
        $program = $row['program'] ?? [];
        $status = match ($row['status'] ?? 'pending') {
            'hadir', 'approved' => 'Hadir',
            'tidak_hadir', 'rejected' => 'Tidak Hadir',
            default => 'Menunggu',
        };

        return [
            'id' => $row['id'],
            'program' => $program['nama'] ?? 'Program',
            'tarikh' => formatMalayDate($program['tarikh'] ?? null),
            'lokasi' => $program['lokasi'] ?? '-',
            'kategori' => (is_array($program['kategori'] ?? null) ? ($program['kategori']['nama'] ?? null) : null) ?? 'Umum',
            'status' => $status,
            'feedback' => false,
            'points' => ($status === 'Hadir') ? (int) ($program['mata'] ?? 0) : 0,
        ];
    }
}

<?php

class CategoryRepository
{
    public function __construct(private SupabaseClient $db)
    {
    }

    public function listAll(): array
    {
        $result = $this->db->select('kategori', '?order=nama.asc');
        if (!$result['ok']) {
            return [];
        }
        return $result['data'];
    }

    public function findBySlug(string $slug): ?array
    {
        $result = $this->db->select(
            'kategori',
            '?slug=eq.' . rawurlencode($slug) . '&limit=1'
        );

        if (!$result['ok'] || empty($result['data'][0])) {
            return null;
        }

        return $result['data'][0];
    }

    public function findByName(string $name): ?array
    {
        $result = $this->db->select(
            'kategori',
            '?nama=eq.' . rawurlencode($name) . '&limit=1'
        );

        if (!$result['ok'] || empty($result['data'][0])) {
            return null;
        }

        return $result['data'][0];
    }

    public function programCount(int $categoryId): int
    {
        $result = $this->db->select(
            'program',
            '?select=id&kategori_id=eq.' . $categoryId
        );

        if (!$result['ok']) {
            return 0;
        }

        return count($result['data']);
    }

    public function toAdminRow(array $category, int $programCount): array
    {
        return [
            'id' => $category['id'],
            'nama' => $category['nama'],
            'jumlahProgram' => $programCount,
            'color' => $category['color'] ?? '#5b8def',
            'icon' => $category['icon'] ?? 'fa-layer-group',
        ];
    }
}

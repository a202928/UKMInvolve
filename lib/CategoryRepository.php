<?php

class CategoryRepository
{
    public function __construct(private SupabaseClient $db)
    {
    }

    public function listAll(bool $onlyActive = false): array
    {
        $query = '?order=nama.asc';
        if ($onlyActive) {
            $query .= '&status=eq.aktif';
        }
        $result = $this->db->select('kategori', $query);
        if (!$result['ok']) {
            if ($onlyActive) {
                $fallbackResult = $this->db->select('kategori', '?order=nama.asc');
                if ($fallbackResult['ok']) {
                    return $fallbackResult['data'];
                }
            }
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
            'status' => $category['status'] ?? 'aktif',
            'mata' => (int)($category['mata'] ?? 100),
        ];
    }

    public function createCategory(string $nama, string $color = '#5b8def', string $icon = 'fa-layer-group', int $mata = 100): array
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nama), '-'));
        $payload = [
            'nama' => trim($nama),
            'slug' => $slug,
            'color' => $color,
            'icon' => $icon,
            'mata' => $mata,
            'status' => 'aktif'
        ];
        return $this->db->insert('kategori', $payload);
    }

    public function updateCategory(int $id, string $nama, string $color, string $icon, string $status, int $mata = 100): array
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nama), '-'));
        $payload = [
            'nama' => trim($nama),
            'slug' => $slug,
            'color' => $color,
            'icon' => $icon,
            'status' => $status,
            'mata' => $mata
        ];
        return $this->db->update('kategori', '?id=eq.' . $id, $payload);
    }

    public function deleteCategory(int $id): array
    {
        return $this->db->delete('kategori', '?id=eq.' . $id);
    }
}


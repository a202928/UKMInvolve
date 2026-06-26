<?php

class StudentInterestRepository
{
    private const TABLE = 'student_interests';

    public function __construct(private SupabaseClient $db)
    {
    }

    public function listByStudent(string $studentId): array
    {
        $result = $this->db->select(
            self::TABLE,
            '?select=*,kategori(id,nama,slug,icon,color)&student_id=eq.' . rawurlencode($studentId)
        );

        if (!$result['ok']) {
            return [];
        }

        return $result['data'] ?? [];
    }

    public function getStudentInterestSlugs(string $studentId): array
    {
        $interests = $this->listByStudent($studentId);
        $slugs = [];
        foreach ($interests as $item) {
            if (!empty($item['kategori']['slug'])) {
                $slugs[] = $item['kategori']['slug'];
            }
        }
        return $slugs;
    }

    public function saveInterests(string $studentId, array $categorySlugs): bool
    {
        // 1. Delete all existing interests for the student
        $deleteResult = $this->db->delete(self::TABLE, '?student_id=eq.' . rawurlencode($studentId));
        if (!$deleteResult['ok']) {
            return false;
        }

        if (empty($categorySlugs)) {
            return true;
        }

        // 2. Fetch category IDs corresponding to the slugs
        $categoriesResult = $this->db->select('kategori', '?select=id,slug');
        if (!$categoriesResult['ok']) {
            return false;
        }

        $slugToId = [];
        foreach ($categoriesResult['data'] as $cat) {
            $slugToId[$cat['slug']] = $cat['id'];
        }

        // 3. Prepare bulk insert payload
        $insertData = [];
        foreach ($categorySlugs as $slug) {
            if (isset($slugToId[$slug])) {
                $insertData[] = [
                    'student_id' => $studentId,
                    'category_id' => (int) $slugToId[$slug]
                ];
            }
        }

        if (empty($insertData)) {
            return true;
        }

        // 4. Insert new interests (bulk insert, single = false)
        $insertResult = $this->db->insert(self::TABLE, $insertData, false);
        return $insertResult['ok'];
    }
}

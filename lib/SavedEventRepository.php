<?php

class SavedEventRepository
{
    private const TABLE = 'saved_events';

    public function __construct(private SupabaseClient $db)
    {
    }

    public function getSavedPrograms(string $userId): array
    {
        $result = $this->db->select(
            self::TABLE,
            '?select=program_id&user_id=eq.' . rawurlencode($userId)
        );

        if (!$result['ok'] || empty($result['data'])) {
            return [];
        }

        return array_column($result['data'], 'program_id');
    }

    public function isSaved(string $userId, int $programId): bool
    {
        $result = $this->db->select(
            self::TABLE,
            '?user_id=eq.' . rawurlencode($userId) . '&program_id=eq.' . $programId . '&limit=1'
        );

        return $result['ok'] && !empty($result['data']);
    }

    public function save(string $userId, int $programId): bool
    {
        if ($this->isSaved($userId, $programId)) {
            return true;
        }

        $result = $this->db->insert(self::TABLE, [
            'user_id' => $userId,
            'program_id' => $programId
        ]);

        return $result['ok'];
    }

    public function unsave(string $userId, int $programId): bool
    {
        $result = $this->db->delete(
            self::TABLE,
            '?user_id=eq.' . rawurlencode($userId) . '&program_id=eq.' . $programId
        );

        return $result['ok'];
    }
}

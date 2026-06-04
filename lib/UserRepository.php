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
            'status' => 'aktif',
            'mata' => (int) ($data['mata'] ?? 0),
        ];

        return $this->db->insert(self::TABLE, $payload);
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
            'status' => $user['status'] ?? 'aktif',
        ];
    }
}

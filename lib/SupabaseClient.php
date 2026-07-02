<?php

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Supabase PostgREST client (uses SUPABASE_URL + SUPABASE_ANON_KEY from .env)
 */
class SupabaseClient
{
    private string $url;
    private string $key;
    private bool $configured;

    public function __construct()
    {
        $this->url = Database::supabaseUrl();
        $this->key = Database::supabaseKey();
        $this->configured = Database::isConfigured();
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function select(string $table, string $query = '', array $extraHeaders = []): array
    {
        return $this->request('GET', $table, $query, null, $extraHeaders);
    }

    public function insert(string $table, array $data, bool $single = true): array
    {
        $headers = $single ? ['Prefer: return=representation'] : ['Prefer: return=minimal'];
        return $this->request('POST', $table, '', $data, $headers);
    }

    public function update(string $table, string $query, array $data): array
    {
        return $this->request('PATCH', $table, $query, $data, ['Prefer: return=representation']);
    }

    public function delete(string $table, string $query): array
    {
        return $this->request('DELETE', $table, $query, null);
    }

    public function rpc(string $function, array $params = []): array
    {
        return $this->request('POST', 'rpc/' . $function, '', $params);
    }

    private function request(
        string $method,
        string $table,
        string $query,
        ?array $body,
        array $extraHeaders = []
    ): array {
        if (!$this->configured) {
            return ['ok' => false, 'error' => 'Supabase tidak dikonfigurasi. Semak fail .env.'];
        }

        $path = str_starts_with($table, 'rpc/') ? $table : 'rest/v1/' . $table;
        $base = rtrim($this->url, '/') . '/' . $path;

        // Separate query string from base URL cleanly
        $queryString = ltrim($query, '?');
        $url = $queryString !== '' ? $base . '?' . $queryString : $base;

        $headers = array_merge([
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
        ], $extraHeaders);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response  = curl_exec($ch);
        $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => $curlError ?: 'Request failed'];
        }

        $decoded = json_decode($response, true);

        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'data' => $decoded ?? [], 'status' => $status];
        }

        $message = is_array($decoded)
            ? ($decoded['message'] ?? $decoded['error'] ?? $decoded['hint'] ?? json_encode($decoded))
            : $response;

        return ['ok' => false, 'error' => (string) $message, 'status' => $status];
    }
}

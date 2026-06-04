<?php

/**
 * Supabase connection settings loaded from .env
 * Uses PostgREST (REST API) – no direct PostgreSQL required for normal operation.
 */

require_once __DIR__ . '/env.php';

class Database
{
    private static ?bool $envLoaded = null;
    private static ?array $envCheck = null;

    public static function envFilePath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    }

    private static function isPlaceholder(string $value): bool
    {
        $lower = strtolower($value);
        $markers = [
            'your_project_ref',
            'your_anon_public_key',
            'your_service_role',
            'paste_',
            'replace_me',
            'xxx',
        ];
        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }
        return false;
    }

    private static function resolveApiKey(): ?string
    {
        $candidates = [
            env('SUPABASE_ANON_KEY'),
            env('SUPABASE_KEY'),
            env('SUPABASE_SERVICE_KEY'),
        ];

        foreach ($candidates as $key) {
            if ($key && !self::isPlaceholder($key)) {
                // PostgREST needs a JWT (eyJ...) – not sb_publishable_ keys
                if (str_starts_with($key, 'sb_publishable_')) {
                    continue;
                }
                return $key;
            }
        }

        return null;
    }

    /** Verify .env exists and required keys are present */
    public static function checkEnv(): array
    {
        if (self::$envCheck !== null) {
            return self::$envCheck;
        }

        $path = self::envFilePath();
        $exists = is_readable($path);

        $url = env('SUPABASE_URL');
        $anonKey = env('SUPABASE_ANON_KEY');
        $serviceKey = env('SUPABASE_SERVICE_KEY');
        $resolvedKey = self::resolveApiKey();

        $urlValid = $url && !self::isPlaceholder($url);
        $hasPublishableOnly = ($anonKey && str_starts_with($anonKey, 'sb_publishable_'))
            || ($serviceKey && str_starts_with($serviceKey, 'sb_publishable_'));

        self::$envCheck = [
            'env_file_exists' => $exists,
            'env_file_path' => $path,
            'supabase_url_set' => $urlValid,
            'supabase_anon_key_set' => $anonKey !== null && $anonKey !== '' && !self::isPlaceholder($anonKey),
            'supabase_service_key_set' => $serviceKey !== null && $serviceKey !== '' && !self::isPlaceholder($serviceKey),
            'is_ready' => $exists && $urlValid && $resolvedKey !== null,
            'using_key' => $resolvedKey ? (str_starts_with((string) env('SUPABASE_ANON_KEY'), 'eyJ') ? 'anon' : 'service_role') : 'none',
            'has_publishable_only' => $hasPublishableOnly,
        ];

        return self::$envCheck;
    }

    public static function getSetupMessage(): string
    {
        $c = self::checkEnv();

        if (!$c['env_file_exists']) {
            return 'Fail .env tidak dijumpai. Salin .env.example ke .env (atau muat semula halaman – sistem akan cipta .env secara automatik).';
        }
        if (!$c['supabase_url_set']) {
            return 'SUPABASE_URL masih kosong atau placeholder dalam .env. Guna URL dari Supabase Dashboard → Project Settings → API.';
        }
        if (!empty($c['has_publishable_only'])) {
            return 'Kunci sb_publishable_ tidak disokong. Guna anon public key (JWT bermula dengan eyJ...) dalam SUPABASE_ANON_KEY.';
        }
        if (!$c['is_ready']) {
            return 'SUPABASE_ANON_KEY masih kosong atau placeholder. Tampal anon public key dari Supabase Dashboard → Project Settings → API.';
        }

        return '';
    }

    public static function isConfigured(): bool
    {
        return self::checkEnv()['is_ready'];
    }

    public static function supabaseUrl(): string
    {
        return rtrim((string) env('SUPABASE_URL', ''), '/');
    }

    /** Prefer anon key (per project requirement); fall back to service_role for server writes */
    public static function supabaseKey(): string
    {
        return (string) (self::resolveApiKey() ?? '');
    }

    /**
     * Optional direct PostgreSQL (requires pdo_pgsql in php.ini).
     * Set in .env: DATABASE_URL=postgresql://postgres.[ref]:[PASSWORD]@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres
     * Password: Supabase Dashboard → Project Settings → Database → Database password
     */
    public static function pdo(): ?PDO
    {
        $dsn = env('DATABASE_URL');
        if (!$dsn) {
            return null;
        }

        if (!extension_loaded('pdo_pgsql')) {
            return null;
        }

        try {
            return new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException) {
            return null;
        }
    }

    public static function postgresAvailable(): bool
    {
        return self::pdo() !== null;
    }
}

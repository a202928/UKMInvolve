<?php

function ensureEnvFile(string $envPath, string $examplePath): void
{
    if (is_readable($envPath)) {
        return;
    }
    if (is_readable($examplePath)) {
        @copy($examplePath, $envPath);
    }
}

function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        return;
    }

    // Remove UTF-8 BOM (common on Windows)
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

    foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key === '') {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
$examplePath = $projectRoot . DIRECTORY_SEPARATOR . '.env.example';

ensureEnvFile($envPath, $examplePath);
loadEnv($envPath);

<?php
declare(strict_types=1);

/**
 * Loads config/.env (simple KEY=VALUE lines) and exposes env().
 * Secrets live only in .env, never in code.
 */
function load_env(string $file): array
{
    $vars = [];
    if (!is_file($file)) {
        return $vars;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
            $v = substr($v, 1, -1);
        }
        $vars[trim($k)] = $v;
    }
    return $vars;
}

function env(string $key, ?string $default = null): ?string
{
    $fromServer = getenv($key); // real environment variables win over .env (handy for tests and servers)
    if ($fromServer !== false) {
        return $fromServer;
    }
    static $vars = null;
    if ($vars === null) {
        $vars = load_env(ROOT . '/config/.env');
    }
    return $vars[$key] ?? $default;
}

/**
 * A secret setting. If KEY_ENC exists in config/.env (or the environment) it is decrypted with the master key
 * (see app/core/Crypto.php); otherwise the plain KEY is used (older installs: run tools/secure_config.php to encrypt it).
 */
function secret(string $name, string $default = ''): string
{
    $enc = env($name . '_ENC');
    if ($enc !== null && $enc !== '') {
        return Crypto::decrypt($enc, $name);
    }
    return (string)env($name, $default);
}

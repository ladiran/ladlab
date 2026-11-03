<?php
declare(strict_types=1);

/**
 * Encryption of secrets at rest (database password, SMTP password, two-step-verification secrets).
 *
 * Algorithm: AES-256-GCM (authenticated). Each value gets its own random IV and is bound to a short "context"
 * label, so a ciphertext copied from one place (e.g. the DB password) will not decrypt as something else.
 * Stored form:  enc1:<base64(iv 12 bytes | tag 16 bytes | ciphertext)>
 *
 * The master key is NOT kept in config/.env. It is read, in this order, from
 *   1. the environment variable APP_KEY (base64, 32 bytes)  - best for a server: nothing on disk next to the code
 *   2. the file named by APP_KEY_FILE (put it OUTSIDE the web folder if you can)
 *   3. storage/keys/app.key (created by the installer; web access denied; owner-only permissions where the OS supports them)
 * Someone who can read only config/.env, or only the database, learns nothing useful: both hold ciphertext.
 */
final class Crypto
{
    private const PREFIX = 'enc1:';
    private static ?string $key = null;

    public static function isEncrypted(string $v): bool
    {
        return str_starts_with($v, self::PREFIX);
    }

    public static function defaultKeyFile(): string
    {
        return ROOT . '/storage/keys/app.key';
    }

    /** Path of the key file in use (even if it does not exist yet). */
    public static function keyFile(): string
    {
        $f = function_exists('env') ? env('APP_KEY_FILE', '') : '';
        return $f !== '' && $f !== null ? $f : self::defaultKeyFile();
    }

    /** 'env', 'file' or 'none' */
    public static function keySource(): string
    {
        $e = function_exists('env') ? (string)env('APP_KEY', '') : '';
        if ($e !== '') {
            return 'env';
        }
        return is_file(self::keyFile()) ? 'file' : 'none';
    }

    public static function hasKey(): bool
    {
        try {
            self::key();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    private static function key(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }
        $raw = '';
        $e = (string)env('APP_KEY', '');
        if ($e !== '') {
            $raw = (string)base64_decode(preg_replace('/^base64:/', '', $e), true);
        } elseif (is_file(self::keyFile())) {
            $raw = (string)base64_decode(trim((string)file_get_contents(self::keyFile())), true);
        }
        if (strlen($raw) !== 32) {
            throw new RuntimeException('The encryption key is missing or damaged. Restore storage/keys/app.key from your backup (or set APP_KEY). Without it the encrypted secrets cannot be read.');
        }
        return self::$key = $raw;
    }

    /** Creates the key file if there is no key at all. Returns the path. */
    public static function ensureKey(): string
    {
        if (self::keySource() !== 'none') {
            return self::keyFile();
        }
        $file = self::keyFile();
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException("Could not create the folder $dir for the encryption key. Make it writable.");
        }
        if (!is_file($dir . '/.htaccess') && str_starts_with(str_replace('\\', '/', $dir), str_replace('\\', '/', ROOT))) {
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        $b64 = base64_encode(random_bytes(32));
        if (file_put_contents($file, $b64 . "\n", LOCK_EX) === false) {
            throw new RuntimeException("Could not write the encryption key to $file.");
        }
        @chmod($file, 0600);
        self::$key = null;
        return $file;
    }

    public static function encrypt(string $plain, string $context): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, $context, 16);
        if ($ct === false) {
            throw new RuntimeException('Encryption failed.');
        }
        return self::PREFIX . base64_encode($iv . $tag . $ct);
    }

    public static function decrypt(string $enc, string $context): string
    {
        if (!self::isEncrypted($enc)) {
            throw new RuntimeException('Not an encrypted value.');
        }
        $raw = base64_decode(substr($enc, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 28) {
            throw new RuntimeException('The encrypted value is damaged.');
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), $context);
        if ($plain === false) {
            throw new RuntimeException('Could not decrypt a stored secret: the key does not match. Restore the original key file.');
        }
        return $plain;
    }

    /** Re-encrypts a value with a different key (used when rotating the master key). */
    public static function withKey(string $rawKey, callable $fn): mixed
    {
        $old = self::$key;
        self::$key = $rawKey;
        try {
            return $fn();
        } finally {
            self::$key = $old;
        }
    }

    public static function currentRawKey(): string
    {
        return self::key();
    }

    /** Rewrites config/.env keeping every line, replacing or adding the given KEY => value pairs. */
    public static function updateEnvFile(array $pairs, array $remove = []): void
    {
        $file = ROOT . '/config/.env';
        $lines = is_file($file) ? (file($file, FILE_IGNORE_NEW_LINES) ?: []) : [];
        $seen = [];
        $out = [];
        foreach ($lines as $line) {
            $k = str_contains($line, '=') && ($line[0] ?? '#') !== '#' ? trim(explode('=', $line, 2)[0]) : null;
            if ($k !== null && in_array($k, $remove, true)) {
                continue;
            }
            if ($k !== null && array_key_exists($k, $pairs)) {
                $out[] = $k . '=' . $pairs[$k];
                $seen[$k] = true;
                continue;
            }
            $out[] = $line;
        }
        foreach ($pairs as $k => $v) {
            if (!isset($seen[$k])) {
                $out[] = $k . '=' . $v;
            }
        }
        $tmp = $file . '.tmp';
        if (file_put_contents($tmp, implode("\n", $out) . "\n", LOCK_EX) === false || !rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Could not write config/.env. Make the config folder writable.');
        }
        @chmod($file, 0600);
    }
}

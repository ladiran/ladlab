<?php
declare(strict_types=1);

/** Time-based one-time passwords (RFC 6238, SHA-1, 6 digits, 30 s): works with Google Authenticator, Microsoft Authenticator, Authy, 1Password and others. */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function newSecret(): string
    {
        return self::base32Encode(random_bytes(20)); // 160 bits, 32 characters
    }

    public static function base32Encode(string $bin): string
    {
        $bits = '';
        foreach (str_split($bin) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    public static function base32Decode(string $s): string
    {
        $s = strtoupper(preg_replace('/[\s=-]/', '', $s));
        $bits = '';
        foreach (str_split($s) as $c) {
            $p = strpos(self::ALPHABET, $c);
            if ($p === false) {
                return '';
            }
            $bits .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }
        return $out;
    }

    public static function codeAt(string $secretB32, int $step): string
    {
        $key = self::base32Decode($secretB32);
        $hash = hash_hmac('sha1', pack('N2', 0, $step), $key, true);
        $o = ord($hash[19]) & 0x0F;
        $n = ((ord($hash[$o]) & 0x7F) << 24) | (ord($hash[$o + 1]) << 16) | (ord($hash[$o + 2]) << 8) | ord($hash[$o + 3]);
        return str_pad((string)($n % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function step(?int $time = null): int
    {
        return intdiv($time ?? time(), 30);
    }

    /**
     * Checks a code, allowing one step either side for clock drift.
     * Returns the matching time step (so the caller can refuse to accept the same one twice), or null.
     */
    public static function verify(string $secretB32, string $code, ?int $time = null): ?int
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return null;
        }
        $now = self::step($time);
        $hit = null;
        for ($d = -1; $d <= 1; $d++) { // constant work whatever the outcome
            if (hash_equals(self::codeAt($secretB32, $now + $d), $code) && $hit === null) {
                $hit = $now + $d;
            }
        }
        return $hit;
    }

    public static function uri(string $secretB32, string $account, string $issuer): string
    {
        $issuer = preg_replace('/[:]/', '', $issuer);
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account) . '?secret=' . $secretB32 . '&issuer=' . rawurlencode($issuer);
    }
}

<?php
declare(strict_types=1);

/** Two-step verification: set-up, sign-in checks, recovery codes. Secrets are encrypted with the master key (see Crypto). */
final class TwoFactor
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /* ------------------------------------------------------------------ set-up */

    /** Starts (or returns) a pending set-up: a fresh secret kept only in the session until the first code is confirmed. */
    public static function beginSetup(): array
    {
        $s = $_SESSION['totp_setup'] ?? null;
        if (!$s || time() - $s['t'] > 900) {
            $s = ['secret' => Totp::newSecret(), 't' => time()];
            $_SESSION['totp_setup'] = $s;
        }
        return $s;
    }

    public static function cancelSetup(): void
    {
        unset($_SESSION['totp_setup']);
    }

    /** Builds what the set-up page shows: secret in groups of four, otpauth link and the QR image. */
    public static function setupView(array $user): ?array
    {
        $s = $_SESSION['totp_setup'] ?? null;
        if (!$s) {
            return null;
        }
        $issuer = Settings::get('site_name') ?: 'ClearSky Academy';
        $uri = Totp::uri($s['secret'], $user['email'], $issuer);
        return ['secret' => $s['secret'], 'grouped' => trim(chunk_split($s['secret'], 4, ' ')), 'uri' => $uri, 'svg' => QrCode::svg($uri, 'QR code for ' . $issuer)];
    }

    /** Confirms the first code and switches 2FA on. Returns the recovery codes (shown once) or null if the code was wrong. */
    public static function confirmSetup(int $userId, string $code): ?array
    {
        $s = $_SESSION['totp_setup'] ?? null;
        if (!$s) {
            return null;
        }
        $step = Totp::verify($s['secret'], $code);
        if ($step === null) {
            return null;
        }
        Db::update('users', ['totp_secret' => Crypto::encrypt($s['secret'], 'totp'), 'totp_enabled_at' => date('Y-m-d H:i:s'), 'totp_last_step' => $step], 'id = ?', [$userId]);
        unset($_SESSION['totp_setup']);
        return self::newRecoveryCodes($userId);
    }

    public static function disable(int $userId): void
    {
        Db::update('users', ['totp_secret' => null, 'totp_enabled_at' => null, 'totp_last_step' => null], 'id = ?', [$userId]);
        Db::delete('recovery_codes', 'user_id = ?', [$userId]);
    }

    /* ------------------------------------------------------------------ checks */

    /** Checks an authenticator code for sign-in or for confirming a sensitive action. A code can be used only once. */
    public static function verifyCode(int $userId, string $code): bool
    {
        $u = Db::one('SELECT totp_secret, totp_last_step FROM users WHERE id = ?', [$userId]);
        if (!$u || empty($u['totp_secret'])) {
            return false;
        }
        try {
            $secret = Crypto::decrypt($u['totp_secret'], 'totp');
        } catch (RuntimeException) {
            return false;
        }
        $step = Totp::verify($secret, $code);
        if ($step === null || ($u['totp_last_step'] !== null && $step <= (int)$u['totp_last_step'])) {
            return false;
        }
        // Atomic: only one request can move the step forward, so a code cannot be replayed in parallel.
        $n = Db::run('UPDATE users SET totp_last_step = ? WHERE id = ? AND (totp_last_step IS NULL OR totp_last_step < ?)', [$step, $userId, $step])->rowCount();
        return $n === 1;
    }

    public static function normaliseRecovery(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /** Uses up a recovery code. */
    public static function useRecoveryCode(int $userId, string $code): bool
    {
        $code = self::normaliseRecovery($code);
        if (strlen($code) !== 10) {
            return false;
        }
        foreach (Db::all('SELECT id, code_hash FROM recovery_codes WHERE user_id = ? AND used_at IS NULL', [$userId]) as $row) {
            if (password_verify($code, $row['code_hash'])) {
                return Db::run('UPDATE recovery_codes SET used_at = NOW() WHERE id = ? AND used_at IS NULL', [$row['id']])->rowCount() === 1;
            }
        }
        return false;
    }

    public static function remainingCodes(int $userId): int
    {
        return (int)Db::val('SELECT COUNT(*) FROM recovery_codes WHERE user_id = ? AND used_at IS NULL', [$userId]);
    }

    /** Replaces all recovery codes with ten new ones. Returns them formatted XXXXX-XXXXX. */
    public static function newRecoveryCodes(int $userId): array
    {
        Db::delete('recovery_codes', 'user_id = ?', [$userId]);
        $out = [];
        for ($i = 0; $i < 10; $i++) {
            $c = '';
            for ($k = 0; $k < 10; $k++) {
                $c .= self::CODE_ALPHABET[random_int(0, 31)];
            }
            Db::insert('recovery_codes', ['user_id' => $userId, 'code_hash' => Auth::hash($c)]);
            $out[] = substr($c, 0, 5) . '-' . substr($c, 5);
        }
        return $out;
    }
}

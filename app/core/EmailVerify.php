<?php
declare(strict_types=1);

/**
 * Proves that a person owns the email address they registered with: a 6-digit code is emailed and must be typed back.
 * Codes last 15 minutes, allow 5 wrong tries each, and can be re-sent at most once a minute and 5 times an hour.
 * Accounts that existed before this feature was installed are not locked out; they are asked to verify when they want to
 * publish an article. New accounts (created on or after the "email_verify_since" setting) cannot sign in until verified.
 */
final class EmailVerify
{
    public const TTL = 900;
    private const MAX_TRIES = 5;
    private const COOLDOWN = 60;
    private const HOURLY = 5;

    /** Is the address proven to belong to this person? (Administrators are always trusted.) */
    public static function verified(array $user): bool
    {
        return !empty($user['email_verified_at']) || ($user['role'] ?? '') === 'admin';
    }

    /** Must this account verify before it can sign in? Only accounts created since the feature was installed. */
    public static function required(array $user): bool
    {
        if (self::verified($user)) {
            return false;
        }
        $since = Settings::get('email_verify_since');
        return $since !== '' && strtotime((string)($user['created_at'] ?? '')) >= strtotime($since);
    }

    /** Remembers (in the session) who is verifying right now. */
    public static function begin(int $userId): void
    {
        $_SESSION['verify'] = ['uid' => $userId, 't' => time()];
    }

    public static function pending(): ?array
    {
        $v = $_SESSION['verify'] ?? null;
        if (!$v || time() - (int)$v['t'] > 1800) {
            unset($_SESSION['verify']);
            return null;
        }
        $u = Db::one('SELECT * FROM users WHERE id = ?', [(int)$v['uid']]);
        return $u && !self::verified($u) ? $u : null;
    }

    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($local, 0, 1) . str_repeat('*', max(2, min(6, mb_strlen($local) - 1))) . '@' . $domain;
    }

    /** @return string 'sent' | 'wait' (too soon) | 'limit' (too many) | 'fail' (the email could not be delivered) */
    public static function send(int $userId): string
    {
        $u = Db::one('SELECT id, name, email FROM users WHERE id = ?', [$userId]);
        if (!$u) {
            return 'fail';
        }
        $last = Db::val('SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM email_codes WHERE user_id = ?', [$userId]);
        if ($last && time() - (int)$last < self::COOLDOWN) {
            return 'wait';
        }
        if ((int)Db::val('SELECT COUNT(*) FROM email_codes WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$userId]) >= self::HOURLY) {
            return 'limit';
        }
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $salt = bin2hex(random_bytes(8));
        Db::run('UPDATE email_codes SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$userId]);
        Db::insert('email_codes', [
            'user_id' => $userId, 'salt' => $salt, 'code_hash' => hash('sha256', $salt . ':' . $userId . ':' . $code),
            'expires_at' => date('Y-m-d H:i:s', time() + self::TTL),
        ]);
        $site = Settings::get('site_name');
        $ok = Mailer::send($u['email'], "[$site] Your verification code: $code", "Hello {$u['name']},\n\nYour verification code is:\n\n    $code\n\nType it on the website to confirm your email address. It works for 15 minutes.\nIf you did not ask for this, you can ignore this email: nothing happens unless the code is entered.\n\nVerification page: " . abs_url('verify-email') . "\n\n$site");
        return $ok ? 'sent' : 'fail';
    }

    /** @return string 'ok' | 'wrong' | 'expired' | 'locked' */
    public static function check(int $userId, string $code): string
    {
        $row = Db::one('SELECT * FROM email_codes WHERE user_id = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1', [$userId]);
        if (!$row || strtotime($row['expires_at']) < time()) {
            return 'expired';
        }
        if ((int)$row['attempts'] >= self::MAX_TRIES) {
            return 'locked';
        }
        Db::run('UPDATE email_codes SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) === 6 && hash_equals($row['code_hash'], hash('sha256', $row['salt'] . ':' . $userId . ':' . $code))) {
            Db::run('UPDATE email_codes SET used_at = NOW() WHERE user_id = ?', [$userId]);
            return 'ok';
        }
        return (int)$row['attempts'] + 1 >= self::MAX_TRIES ? 'locked' : 'wrong';
    }

    public static function triesLeft(int $userId): int
    {
        $row = Db::one('SELECT attempts FROM email_codes WHERE user_id = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1', [$userId]);
        return $row ? max(0, self::MAX_TRIES - (int)$row['attempts']) : 0;
    }

    public static function mark(int $userId): void
    {
        Db::update('users', ['email_verified_at' => date('Y-m-d H:i:s')], 'id = ? AND email_verified_at IS NULL', [$userId]);
        Db::delete('email_codes', 'user_id = ?', [$userId]);
    }
}

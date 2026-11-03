<?php
declare(strict_types=1);

/** Admin → Security: your own two-step verification, a plain-language security checklist, sign-in history and hardening actions. */
final class AdminSecurityController extends AdminBase
{
    use SecurityActions;

    protected function secBase(): string
    {
        return 'admin/security';
    }

    protected function secIsAdmin(): bool
    {
        return true;
    }

    public function index(): void
    {
        $u = Auth::user();
        $checks = $this->checks($u);
        $score = ['ok' => 0, 'warn' => 0, 'bad' => 0];
        foreach ($checks as $c) {
            $score[$c['status']]++;
        }
        $this->page('admin/security', ['title' => 'Security'] + $this->secPanel($u) + [
            'checks' => $checks, 'score' => $score,
            'activity' => Db::all('SELECT e.*, u.name FROM login_events e LEFT JOIN users u ON u.id = e.user_id ORDER BY e.id DESC LIMIT 25'),
            'failed24' => (int)Db::val('SELECT COUNT(*) FROM login_events WHERE success = 0 AND created_at > (NOW() - INTERVAL 1 DAY)'),
            'alerts' => Settings::get('security_alert_emails', '1') !== '0',
        ]);
    }

    /* ------------------------------------------------------------------ checklist */

    private function checks(array $me): array
    {
        $c = [];
        $add = function (string $status, string $title, string $detail, ?array $action = null) use (&$c): void {
            $c[] = ['status' => $status, 'title' => $title, 'detail' => $detail, 'action' => $action];
        };
        $prod = env('APP_ENV', 'local') === 'production';

        // 1. Admin 2FA
        if (Auth::hasTwoFactor($me)) {
            $add('ok', 'Your account uses two-step verification', 'A stolen password alone cannot open the admin area.');
        } else {
            $add('bad', 'Turn on two-step verification for your administrator account', 'This is the single most useful thing you can do. It takes two minutes with Google Authenticator or Microsoft Authenticator. Use the "Two-step verification" box on this page.');
        }
        $noTwo = (int)Db::val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND totp_enabled_at IS NULL");
        if ($noTwo > 0 && Auth::hasTwoFactor($me)) {
            $add('warn', "$noTwo other administrator account(s) have no two-step verification", 'Ask them to turn it on, or tick "Require two-step verification for administrators" below.');
        }
        $add(Settings::get('require_2fa_admin') === '1' ? 'ok' : 'warn', 'Two-step verification is ' . (Settings::get('require_2fa_admin') === '1' ? '' : 'not ') . 'required for administrators', Settings::get('require_2fa_admin') === '1' ? 'Administrators without it can only reach this page.' : 'Recommended once you have set up your own: tick the option in "Settings" below.');

        // 2. Database password
        $dbEnc = (string)env('DB_PASS_ENC', '') !== '';
        $dbPlain = (string)env('DB_PASS', '') !== '';
        if ($dbEnc) {
            $add('ok', 'The database password is stored encrypted', 'config/.env holds only encrypted text (AES-256-GCM). The key is kept separately.');
        } elseif ($dbPlain) {
            $add('bad', 'The database password is stored as plain text in config/.env', 'Anyone who can read that file can open your database. Encrypt it now.', ['label' => 'Encrypt it now', 'do' => 'encrypt']);
        } else {
            $add($prod ? 'bad' : 'warn', 'The database account has no password', $prod ? 'On a live site the database user must have a long random password. Run: php tools/secure_config.php --dedicated-user' : 'Fine for testing on your own computer (WAMP). Before going live, create a dedicated database user with a long random password: php tools/secure_config.php --dedicated-user');
        }
        $dbUser = (string)env('DB_USER', 'root');
        if ($dbUser === 'root') {
            $add($prod ? 'bad' : 'warn', 'The website connects to the database as "root"', 'If the site were ever attacked, root can read and change every database on the server. Use a dedicated user that can only reach this site\'s database: php tools/secure_config.php --dedicated-user (the installer does this for you by default).');
        } else {
            $add('ok', 'The website uses its own database user (not root)', 'Its rights are limited to this site\'s database.');
        }
        if ((string)env('SMTP_PASS', '') !== '') {
            $add('bad', 'The email (SMTP) password is stored as plain text', 'Encrypt it with the database password.', ['label' => 'Encrypt it now', 'do' => 'encrypt']);
        } elseif ((string)env('SMTP_PASS_ENC', '') !== '') {
            $add('ok', 'The email (SMTP) password is stored encrypted', '');
        }

        // 3. Encryption key
        $src = Crypto::keySource();
        if ($src === 'env') {
            $add('ok', 'The encryption key comes from the server environment', 'Best practice: it is not in any file.');
        } elseif ($src === 'file') {
            $kf = str_replace('\\', '/', Crypto::keyFile());
            $inside = str_starts_with($kf, str_replace('\\', '/', ROOT) . '/');
            $add($inside ? 'warn' : 'ok', $inside ? 'The encryption key file is inside the site folder' : 'The encryption key file is outside the site folder', $inside
                ? 'It is protected from web access, but the safest place is outside the site folder. Move it, then add APP_KEY_FILE=/full/path/app.key to config/.env. KEEP A BACKUP COPY somewhere safe (not with the database backup): without it the encrypted secrets cannot be read.'
                : 'Good. Keep a backup copy somewhere safe, separate from your database backups.');
        } elseif ($dbEnc) {
            $add('bad', 'The encryption key is missing', 'Encrypted secrets exist but the key cannot be found. Restore storage/keys/app.key from your backup.');
        }

        // 4. Production settings
        if ($prod && env('APP_DEBUG', '0') === '0') {
            $add('ok', 'Live-site mode is on and error details are hidden', '');
        } elseif ($prod) {
            $add('bad', 'Error details are visible to visitors', 'Set APP_DEBUG=0 in config/.env.');
        } else {
            $add('warn', 'The site is in test mode (APP_ENV=local)', 'Fine on your computer. Before going live set APP_ENV=production and APP_DEBUG=0 in config/.env.');
        }
        $https = is_https();
        $add($https ? 'ok' : ($prod ? 'bad' : 'warn'), $https ? 'This page was served over HTTPS' : 'HTTPS is not in use', $https ? 'Passwords and sessions are encrypted in transit.' : 'On a live site always use HTTPS (the Cloudflare tunnel provides it). Without it, passwords can be read on the network.');
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && !trusted_proxy()) {
            $add('bad', 'Visitors behind Cloudflare are not told apart', 'Add TRUSTED_PROXIES=<address of the tunnel> to config/.env so rate limits and sign-in history use each visitor\'s real address.');
        } elseif (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $add('ok', 'Real visitor addresses are read from Cloudflare', 'Rate limits and sign-in history are per visitor.');
        }

        // 5. Leftovers
        if (is_file(ROOT . '/public/install.php')) {
            $add($prod ? 'bad' : 'warn', 'The installer file is still on the server', 'public/install.php locks itself, but a live site should not carry it.', ['label' => 'Delete it', 'do' => 'delete_installer']);
        } else {
            $add('ok', 'The installer file has been removed', '');
        }
        if (strtolower($me['email']) === 'admin@example.com' || str_ends_with(strtolower($me['email']), '@example.com')) {
            $add('warn', 'Your administrator email looks like a placeholder', 'Set your real email in "My account" so password resets and alerts reach you.');
        }

        // 6. Server
        $add(version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'warn', 'PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=') ? '' : 'Older PHP versions stop receiving security fixes. Use 8.3 or newer when you can.');
        if (ini_get('display_errors') && !in_array(strtolower((string)ini_get('display_errors')), ['0', 'off', ''], true) && $prod) {
            $add('warn', 'PHP is set to display errors', 'Set display_errors=Off in php.ini on a live server.');
        }
        $add(defined('PASSWORD_ARGON2ID') ? 'ok' : 'warn', defined('PASSWORD_ARGON2ID') ? 'Passwords are stored with Argon2id' : 'Passwords are stored with bcrypt (cost 12)', defined('PASSWORD_ARGON2ID') ? '' : 'Still strong. Argon2id needs a PHP build that includes it; no action required.');
        $add('ok', 'Built-in protections are active', 'Login throttling, CSRF tokens on every form, strict content-security policy, session timeouts (30 minutes for administrators), "sign out everywhere", private file storage, sign-in alerts by email, and an audit log.');
        $add('warn', 'Back up the database AND the encryption key regularly', 'Keep copies off this computer. Store the key backup separately from the database backup.');
        return $c;
    }

    /* ------------------------------------------------------------------ actions that change the server */

    private function guardPassword(): array
    {
        $this->verify();
        $u = Auth::user();
        if (!$this->secPasswordOk($u)) {
            flash('error', 'Your password was not correct, so nothing was changed.');
            redirect('admin/security');
        }
        return $u;
    }

    public function settings(): void
    {
        $u = $this->guardPassword();
        $req = isset($_POST['require_2fa_admin']);
        if ($req && !Auth::hasTwoFactor($u)) {
            flash('error', 'Set up two-step verification for your own account first, otherwise you would lock yourself out.');
            redirect('admin/security');
        }
        Settings::set('require_2fa_admin', $req ? '1' : '0');
        Settings::set('security_alert_emails', isset($_POST['security_alert_emails']) ? '1' : '0');
        Audit::log('security_settings', 'require2fa=' . ($req ? 'yes' : 'no'));
        flash('success', 'Security settings saved.');
        redirect('admin/security');
    }

    public function encrypt(): void
    {
        $this->guardPassword();
        try {
            $n = self::encryptConfig();
            flash('success', $n ? "Encrypted $n secret(s) in config/.env. The website keeps working; nothing else changes." : 'There was nothing to encrypt.');
        } catch (Throwable $e) {
            flash('error', 'Could not encrypt: ' . $e->getMessage());
        }
        Audit::log('config_encrypted', '');
        redirect('admin/security');
    }

    /** Encrypts DB_PASS and SMTP_PASS in config/.env (creating the master key if needed). Returns how many were converted. */
    public static function encryptConfig(): int
    {
        $pairs = [];
        $remove = [];
        foreach (['DB_PASS', 'SMTP_PASS'] as $name) {
            $plain = (string)env($name, '');
            if ($plain !== '' && (string)env($name . '_ENC', '') === '') {
                Crypto::ensureKey();
                $pairs[$name . '_ENC'] = Crypto::encrypt($plain, $name);
                $remove[] = $name;
            }
        }
        if (!$pairs) {
            return 0;
        }
        Crypto::updateEnvFile($pairs, $remove);
        return count($pairs);
    }

    public function deleteInstaller(): void
    {
        $this->guardPassword();
        $f = ROOT . '/public/install.php';
        if (is_file($f) && @unlink($f)) {
            Audit::log('installer_deleted', '');
            flash('success', 'The installer file was deleted.');
        } else {
            flash('error', 'Could not delete public/install.php. Delete it by hand.');
        }
        redirect('admin/security');
    }

    /** For a customer or administrator who lost their phone and recovery codes. */
    public function resetUser(string $id): void
    {
        $this->guardPassword();
        $t = Db::one('SELECT id, name, email FROM users WHERE id = ?', [(int)$id]);
        if ($t) {
            TwoFactor::disable((int)$t['id']);
            Auth::bumpEpoch((int)$t['id'], (int)$t['id'] === (int)Auth::user()['id']);
            Audit::log('2fa_reset_by_admin', $t['email']);
            Auth::alert($t, 'Two-step verification was reset', "Hello {$t['name']},\n\nThe site administrator turned off two-step verification on your account (for example because you lost your phone). Please sign in and turn it on again: " . abs_url('account/security') . "\n\nIf you did not ask for this, change your password and tell us.");
            flash('success', "Two-step verification was reset for {$t['email']}. They were asked to set it up again.");
        }
        $back = (string)($_POST['back'] ?? '');
        redirect(preg_match('#^admin/[a-z/]*$#', $back) ? $back : 'admin/security');
    }
}

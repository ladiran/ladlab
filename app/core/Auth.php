<?php
declare(strict_types=1);

/**
 * Sign-in for everyone (customers and administrators share the users table; `role` decides what they may open).
 *
 * Sessions end after an idle period (30 minutes for administrators, 2 hours for customers), after 12 hours in total,
 * when the browser changes, or when the account's "auth epoch" changes ("sign out everywhere", password change, 2FA change).
 */
final class Auth
{
    private const IDLE_CUSTOMER = 7200;
    private const IDLE_ADMIN = 1800;
    private const ABSOLUTE = 43200;
    private const PENDING_TTL = 300;

    private static ?array $row = null;
    private static int $rowFor = 0;

    /* ------------------------------------------------------------------ session state */

    private static function authRow(): ?array
    {
        $uid = (int)($_SESSION['uid'] ?? 0);
        if (self::$row !== null && self::$rowFor === $uid) {
            return self::$row;
        }
        try {
            $r = Db::one('SELECT role, auth_epoch FROM users WHERE id = ?', [$uid]);
        } catch (Throwable) {
            $r = Db::one('SELECT role FROM users WHERE id = ?', [$uid]); // before the latest database update
        }
        self::$rowFor = $uid;
        return self::$row = $r;
    }

    private static function fingerprint(): string
    {
        return hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    public static function check(): bool
    {
        if (empty($_SESSION['uid'])) {
            return false;
        }
        $row = self::authRow();
        $now = time();
        if ($row && (int)($row['auth_epoch'] ?? 0) === 1 && (int)($_SESSION['epoch'] ?? 0) === 0) {
            $_SESSION['epoch'] = (int)$row['auth_epoch']; // signed in before the security update: adopt the current epoch instead of forcing a new sign-in
        }
        if (
            !$row
            || $now - ($_SESSION['last_seen'] ?? 0) > (in_array($row['role'], ['admin', 'lecturer'], true) ? self::IDLE_ADMIN : self::IDLE_CUSTOMER)
            || $now - ($_SESSION['started'] ?? $now) > self::ABSOLUTE
            || (isset($_SESSION['ua']) && !hash_equals((string)$_SESSION['ua'], self::fingerprint()))
            || (isset($row['auth_epoch']) && (int)($_SESSION['epoch'] ?? 0) !== (int)$row['auth_epoch'])
        ) {
            self::logout();
            return false;
        }
        $_SESSION['last_seen'] = $now;
        return true;
    }

    public static function user(): ?array
    {
        static $user = false;
        if ($user === false || ($user !== null && (int)$user['id'] !== (int)($_SESSION['uid'] ?? 0))) {
            $user = self::check()
                ? self::loadUser((int)$_SESSION['uid'])
                : null;
        }
        return $user;
    }

    /** SELECT * (minus the secrets) so this keeps working before the latest database update is applied. */
    private static function loadUser(int $id): ?array
    {
        $u = Db::one('SELECT * FROM users WHERE id = ?', [$id]);
        if ($u === null) {
            return null;
        }
        unset($u['password_hash'], $u['totp_secret']);
        return $u + ['phone' => '', 'address_line' => '', 'city' => '', 'state' => '', 'totp_enabled_at' => null];
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'admin';
    }

    public static function isLecturer(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'lecturer';
    }

    /** Administrator or lecturer. */
    public static function isStaff(): bool
    {
        return self::isAdmin() || self::isLecturer();
    }

    /** Where staff land after signing in: the dashboard for the administrator, the course list for a lecturer. */
    public static function staffHome(): string
    {
        return self::isAdmin() ? 'admin' : 'admin/courses';
    }

    public static function hasTwoFactor(?array $user = null): bool
    {
        $user = $user ?? self::user();
        return $user !== null && !empty($user['totp_enabled_at']);
    }

    /** Administrator screens: not signed in → admin sign-in; signed in as a customer → refused. */
    public static function requireAdmin(): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = request_path();
            redirect('admin/login');
        }
        if (!self::isAdmin()) {
            if (self::isLecturer() && request_path() === '/admin') {
                redirect('admin/courses'); // a lecturer's home is the course list
            }
            abort(403, 'This area is for administrators only.');
        }
        if (Settings::get('require_2fa_admin') === '1' && !self::hasTwoFactor() && !preg_match('#^/admin/(security|logout)#', request_path())) {
            flash('error', 'Two-step verification is required for administrators. Please set it up now; it takes two minutes.');
            redirect('admin/security');
        }
    }

    /** Screens shared by administrators and lecturers (courses, student progress). Everything else stays administrator-only. */
    public static function requireStaff(): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = request_path();
            redirect('admin/login');
        }
        if (!self::isStaff()) {
            abort(403, 'This area is for administrators and lecturers only.');
        }
        if (self::isLecturer() && !self::hasTwoFactor()) {
            // compulsory and not negotiable: a lecturer without two-step verification cannot open the lecturer area at all
            flash('error', 'Two-step verification is compulsory for lecturers. Please turn it on now (two minutes with Google or Microsoft Authenticator); your lecturer area opens as soon as it is on.');
            redirect('account/security');
        }
        if (Settings::get('require_2fa_admin') === '1' && !self::hasTwoFactor() && !preg_match('#^/admin/(security|logout)#', request_path())) {
            flash('error', 'Two-step verification is required for administrators. Please set it up now; it takes two minutes.');
            redirect('admin/security');
        }
    }

    /** Customer screens. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = request_path();
            flash('info', 'Please sign in to continue.');
            redirect('login');
        }
    }

    /* ------------------------------------------------------------------ passwords */

    private const COMMON = [
        'password', 'password1', 'password12', 'password123', 'passw0rd123', 'qwertyuiop', 'qwerty1234', 'qwerty12345', 'qwertyuiop1', '1234567890', '12345678910',
        '0123456789', '11111111111', 'iloveyou12', 'iloveyou123', 'letmein1234', 'welcome123', 'welcome1234', 'admin12345', 'administrator', 'abcdefghij', 'abcd123456',
        'abc1234567', 'changeme123', 'trustno1234', 'football123', 'monkey12345', 'dragon12345', 'master12345', 'sunshine123', 'princess123', 'lovely12345', 'nigeria123',
        'nigerian123', 'lagos12345', 'abuja12345', 'physics123', 'physics1234', 'science123', 'clearsky123', 'clearskyacademy', 'p@ssw0rd123', 'p@ssword123',
    ];

    /** Returns an error message, or '' if the password is acceptable. Everyone needs 10+ characters that are not easy to guess. */
    public static function passwordProblem(string $pw, string $email = '', string $role = 'customer', string $name = ''): string
    {
        if (mb_strlen($pw) < 10) {
            return 'Please choose a password of at least 10 characters. A few random words joined together is easy to remember and hard to guess.';
        }
        if (strlen($pw) > 200) {
            return 'That password is too long.';
        }
        $low = mb_strtolower($pw);
        $local = mb_strtolower((string)strstr($email, '@', true));
        if ($low === mb_strtolower($email) || in_array($low, self::COMMON, true) || count(array_unique(mb_str_split($low))) < 5) {
            return 'That password is too easy to guess. Please choose another.';
        }
        if (($local !== '' && mb_strlen($local) >= 4 && str_contains($low, $local)) || (mb_strlen($name) >= 4 && str_contains($low, mb_strtolower($name)))) {
            return 'Your password should not contain your name or email address.';
        }
        if (preg_match('/^(?:0123456789|1234567890|abcdefghij|qwertyuiop)/', $low) && mb_strlen($low) <= 12) {
            return 'That password is a common keyboard pattern. Please choose another.';
        }
        return '';
    }

    /** Argon2id when this PHP has it (strongest), otherwise bcrypt with a high cost. */
    public static function hash(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1]);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    private static function needsRehash(string $hash): bool
    {
        return defined('PASSWORD_ARGON2ID')
            ? password_needs_rehash($hash, PASSWORD_ARGON2ID, ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1])
            : password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /* ------------------------------------------------------------------ sign-in */

    /** Throttle: 5 failures per account or 10 per IP in 15 minutes. */
    public static function locked(string $email): bool
    {
        $ip = (int)Db::val("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)", [client_ip()]);
        $id = (int)Db::val("SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)", [strtolower($email)]);
        return $ip >= 10 || $id >= 5;
    }

    /**
     * Checks the password.
     * @param string|null $requireRole when set (e.g. 'admin') accounts with another role are treated as a failed sign-in
     * @return false|string false = refused; 'ok' = signed in; '2fa' = password correct, a one-time code is still needed; 'verify' = the email address must be confirmed first
     */
    public static function attempt(string $email, string $password, ?string $requireRole = null): false|string
    {
        $email = strtolower(trim($email));
        $user = Db::one('SELECT * FROM users WHERE email = ?', [$email]);
        // Always verify against something so timing does not reveal whether the account exists.
        $hash = $user['password_hash'] ?? self::hash(bin2hex(random_bytes(8)));
        $ok = password_verify($password, $hash) && $user !== null && ($requireRole === null || $user['role'] === $requireRole || ($requireRole === 'staff' && in_array($user['role'], ['admin', 'lecturer'], true)));
        if (!$ok) {
            Db::run('INSERT INTO login_attempts (ip, identifier) VALUES (?, ?)', [client_ip(), $email]);
            self::event($user['id'] ?? null, $email, false, 'password');
            return false;
        }
        Db::delete('login_attempts', 'identifier = ?', [$email]);
        if (self::needsRehash($hash)) {
            Db::update('users', ['password_hash' => self::hash($password)], 'id = ?', [$user['id']]);
        }
        if ($requireRole === null && EmailVerify::required($user)) {
            EmailVerify::begin((int)$user['id']); // a new account whose email address has not been confirmed yet
            return 'verify';
        }
        if (!empty($user['totp_enabled_at'])) {
            $_SESSION['pending2fa'] = ['uid' => (int)$user['id'], 't' => time(), 'tries' => 0, 'role' => $requireRole];
            return '2fa';
        }
        self::complete($user, 'password');
        return 'ok';
    }

    /** The one-time code step is waiting: returns [user row, role requirement] or null. */
    public static function pending(): ?array
    {
        $p = $_SESSION['pending2fa'] ?? null;
        if (!$p || time() - (int)$p['t'] > self::PENDING_TTL) {
            unset($_SESSION['pending2fa']);
            return null;
        }
        $u = Db::one('SELECT * FROM users WHERE id = ?', [$p['uid']]);
        return $u ? ['user' => $u, 'role' => $p['role'], 'tries' => (int)$p['tries']] : null;
    }

    /** Finishes a sign-in after every required factor was accepted. */
    public static function complete(array $user, string $method): void
    {
        unset($_SESSION['pending2fa']);
        self::start((int)$user['id']);
        Db::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        $known = self::knownDevice((int)$user['id']);
        self::event((int)$user['id'], $user['email'], true, $method);
        Audit::log('login', $user['email'] . ($method !== 'password' ? " ($method)" : ''));
        if (!$known['seen'] && $known['previous']) {
            self::alert($user, 'New sign-in to your account', "Hello {$user['name']},\n\nYour account was just signed in to from a browser we have not seen before.\n\nTime: " . date('j M Y, H:i') . "\nAddress: " . client_ip() . "\nBrowser: " . mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 120) . "\n\nIf this was you, no action is needed. If not, change your password now and use \"Sign out everywhere\":\n" . abs_url($user['role'] === 'admin' ? 'admin/security' : 'account/security'));
        }
        if (empty($user['totp_enabled_at'])) {
            flash('info', $user['role'] === 'admin'
                ? 'Strong advice: turn on two-step verification for this administrator account. Anyone who learns your password could otherwise take over the whole site.'
                : 'Tip: protect your account with two-step verification (Account → Security). It takes two minutes and stops anyone who learns your password.');
        }
    }

    /** @return array{seen: bool, previous: bool} has this user signed in before, and from this same browser? */
    private static function knownDevice(int $userId): array
    {
        try {
            $prev = (int)Db::val('SELECT COUNT(*) FROM login_events WHERE user_id = ? AND success = 1', [$userId]);
            $seen = (int)Db::val('SELECT COUNT(*) FROM login_events WHERE user_id = ? AND success = 1 AND device = ?', [$userId, self::device()]);
            return ['seen' => $seen > 0, 'previous' => $prev > 0];
        } catch (Throwable) {
            return ['seen' => true, 'previous' => false];
        }
    }

    private static function device(): string
    {
        return substr(self::fingerprint(), 0, 16);
    }

    public static function event(?int $userId, string $email, bool $success, string $method): void
    {
        try {
            Db::insert('login_events', [
                'user_id' => $userId, 'email' => mb_substr($email, 0, 190), 'ip' => client_ip(), 'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
                'device' => self::device(), 'success' => $success ? 1 : 0, 'method' => $method,
            ]);
            if (random_int(1, 50) === 1) {
                Db::run('DELETE FROM login_events WHERE created_at < (NOW() - INTERVAL 120 DAY)');
            }
        } catch (Throwable) {
            // history table not installed yet
        }
    }

    /** Emails the account owner about a security event (unless switched off in Admin → Security). */
    public static function alert(array $user, string $subject, string $body): void
    {
        if (Settings::get('security_alert_emails', '1') === '0' || empty($user['email'])) {
            return;
        }
        Mailer::send($user['email'], '[' . Settings::get('site_name') . '] ' . $subject, $body);
    }

    /** Signs a user in (new session id, fresh CSRF token), keeping the shopping cart. */
    public static function start(int $userId): void
    {
        $cart = $_SESSION['cart'] ?? null;
        $coupon = $_SESSION['coupon'] ?? null;
        session_regenerate_id(true);
        self::$row = null;
        $_SESSION['uid'] = $userId;
        $_SESSION['last_seen'] = $_SESSION['started'] = time();
        $_SESSION['ua'] = self::fingerprint();
        try {
            $_SESSION['epoch'] = (int)Db::val('SELECT auth_epoch FROM users WHERE id = ?', [$userId]);
        } catch (Throwable) {
            $_SESSION['epoch'] = 0;
        }
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        if ($cart) {
            $_SESSION['cart'] = $cart;
        }
        if ($coupon) {
            $_SESSION['coupon'] = $coupon;
        }
    }

    /** Ends every other session of this account ("sign out everywhere"); the current one stays signed in when $keepCurrent. */
    public static function bumpEpoch(int $userId, bool $keepCurrent = true): void
    {
        Db::run('UPDATE users SET auth_epoch = auth_epoch + 1 WHERE id = ?', [$userId]);
        self::$row = null;
        if ($keepCurrent && (int)($_SESSION['uid'] ?? 0) === $userId) {
            $_SESSION['epoch'] = (int)Db::val('SELECT auth_epoch FROM users WHERE id = ?', [$userId]);
        }
    }

    public static function logout(): void
    {
        self::$row = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

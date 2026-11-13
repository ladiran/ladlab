<?php
declare(strict_types=1);

/** Customer sign-in, registration, password reset and the "My account" area. */
final class AccountController
{
    /** Returns an error message, or '' if the password is acceptable (see Auth::passwordProblem). */
    public static function passwordProblem(string $pw, string $email, string $role = 'customer', string $name = ''): string
    {
        return Auth::passwordProblem($pw, $email, $role, $name);
    }

    public static function safeNext(?string $path, string $default): string
    {
        $path = (string)$path;
        return preg_match('#^/(account|checkout|shop|order|cart)([A-Za-z0-9/_.\-?=&%]*)$#', $path) ? $path : $default;
    }

    /* ------------------------------------------------------------------ sign in / out */

    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('account');
        }
        form_started('login');
        view('public/login', ['title' => 'Sign in', 'meta_desc' => '']);
    }

    public function login(): void
    {
        csrf_check();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        if (Auth::locked($email)) {
            flash('error', 'Too many failed attempts. Please wait 15 minutes and try again, or reset your password.');
            redirect('login');
        }
        $r = Auth::attempt($email, $password);
        if ($r === false) {
            flash('error', 'Incorrect email or password.');
            remember_input(['email' => $email]);
            redirect('login');
        }
        clear_old();
        if ($r === '2fa') {
            redirect('login/2fa');
        }
        if ($r === 'verify') {
            $sent = EmailVerify::send((int)$_SESSION['verify']['uid']);
            flash('info', 'Please verify your email address before signing in. ' . ($sent === 'sent' ? 'We have emailed you a 6-digit code.' : 'Press "Send a new code" if you need another one.'));
            redirect('verify-email');
        }
        $next = self::safeNext($_SESSION['intended'] ?? null, '/account');
        unset($_SESSION['intended']);
        redirect($next);
    }

    public function logout(): void
    {
        csrf_check();
        Auth::logout();
        session_id(bin2hex(random_bytes(16)));
        session_start();
        flash('success', 'You have been signed out.');
        redirect('/');
    }

    /* ------------------------------------------------------------------ register */

    public function registerForm(): void
    {
        if (Auth::check()) {
            redirect('account');
        }
        form_started('register');
        view('public/register', ['title' => 'Create an account', 'meta_desc' => '']);
    }

    public function register(): void
    {
        csrf_check();
        if (!form_human('register')) {
            flash('error', 'Please wait a moment and try again.');
            redirect('register');
        }
        if (!rate_ok('register', 5, 3600)) {
            abort(429, 'Too many sign-up attempts. Please try again later.');
        }
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        $errors = [];
        if ($name === '') {
            $errors[] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{5,40}$/', $phone)) {
            $errors[] = 'Please check the phone number.';
        }
        if ($pw !== (string)($_POST['password2'] ?? '')) {
            $errors[] = 'The two passwords do not match.';
        } elseif ($p = self::passwordProblem($pw, $email)) {
            $errors[] = $p;
        }
        if (empty($_POST['agree'])) {
            $errors[] = 'Please tick the box to accept the privacy policy.';
        }
        $existingUser = !$errors ? Db::one('SELECT * FROM users WHERE email = ?', [$email]) : null;
        if ($existingUser && !EmailVerify::required($existingUser)) {
            $errors[] = 'An account with this email already exists. Please sign in, or use “Forgot your password?”.';
        }
        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input(['name' => $name, 'email' => $email, 'phone' => $phone]);
            redirect('register');
        }
        if ($existingUser) {
            // an earlier sign-up with this address that was never confirmed: the new details replace it
            Db::update('users', ['name' => $name, 'phone' => $phone, 'password_hash' => Auth::hash($pw), 'pw_changed_at' => date('Y-m-d H:i:s')], 'id = ?', [$existingUser['id']]);
            $id = (int)$existingUser['id'];
        } else {
            $id = Db::insert('users', ['name' => $name, 'email' => $email, 'phone' => $phone, 'password_hash' => Auth::hash($pw), 'role' => 'customer', 'pw_changed_at' => date('Y-m-d H:i:s')]);
        }
        Audit::log('register', $email);
        clear_old();
        EmailVerify::begin($id);
        $sent = EmailVerify::send($id);
        if ($sent === 'sent') {
            flash('success', 'Almost done! We emailed a 6-digit code to ' . EmailVerify::mask($email) . '. Type it below to finish creating your account.');
        } else {
            flash('error', 'Your account was created, but we could not email the code. Check that the address is right, then press "Send a new code".');
        }
        redirect('verify-email');
    }

    /* ------------------------------------------------------------------ forgot / reset password */

    public function forgotForm(): void
    {
        view('public/forgot', ['title' => 'Forgot your password?', 'meta_desc' => '']);
    }

    public function forgot(): void
    {
        csrf_check();
        if (!empty($_POST['website'])) { // honeypot
            redirect('forgot-password');
        }
        if (!rate_ok('forgot', 5, 3600)) {
            abort(429, 'Too many reset requests. Please try again later.');
        }
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = Db::one('SELECT id, name, email FROM users WHERE email = ?', [$email]);
            // At most 3 live requests per account per hour, so the form cannot be used to flood someone's inbox.
            if ($user && (int)Db::val("SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)", [$user['id']]) < 3) {
                $token = bin2hex(random_bytes(32));
                Db::insert('password_resets', ['user_id' => $user['id'], 'token_hash' => hash('sha256', $token), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
                Mailer::send(
                    $user['email'],
                    'Reset your ' . Settings::get('site_name') . ' password',
                    "Hello {$user['name']},\n\nSomeone (hopefully you) asked to reset your password. Open this link within 1 hour to choose a new one:\n\n" . abs_url('reset-password/' . $token) . "\n\nIf you did not ask for this, ignore this email: your password stays the same."
                );
                Audit::log('password_reset_requested', $email);
            }
        }
        // The same answer whether or not the address has an account (prevents guessing who is registered).
        $note = env('MAIL_MODE', 'log') === 'log' && env('APP_ENV') !== 'production' ? ' (Test mode: the email was written to storage/logs/mail.log.)' : '';
        flash('success', 'If an account exists for that email, we have sent a link to reset the password. It works for 1 hour.' . $note);
        redirect('forgot-password');
    }

    private function findToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Db::one(
            'SELECT r.id, r.user_id, u.email, u.role, u.name FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()',
            [hash('sha256', $token)]
        );
    }

    public function resetForm(string $token): void
    {
        $row = $this->findToken($token);
        if (!$row) {
            view('public/message', ['title' => 'Link expired', 'heading' => 'This reset link has expired or was already used', 'text' => 'Please request a new one and use it within an hour.']);
            return;
        }
        view('public/reset', ['title' => 'Choose a new password', 'meta_desc' => '', 'token' => $token, 'role' => $row['role']]);
    }

    public function reset(string $token): void
    {
        csrf_check();
        $row = $this->findToken($token);
        if (!$row) {
            redirect('forgot-password');
        }
        $pw = (string)($_POST['password'] ?? '');
        $error = $pw !== (string)($_POST['password2'] ?? '') ? 'The two passwords do not match.' : self::passwordProblem($pw, $row['email'], $row['role']);
        if ($error) {
            flash('error', $error);
            redirect('reset-password/' . $token);
        }
        Db::update('users', ['password_hash' => Auth::hash($pw), 'pw_changed_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['user_id']]);
        try {
            Db::run('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?', [$row['user_id']]); // the reset link proves they own the address
        } catch (Throwable) {
            // verification not installed yet
        }
        Auth::bumpEpoch((int)$row['user_id'], false); // every signed-in browser of this account must sign in again
        Db::run('DELETE FROM password_resets WHERE user_id = ?', [$row['user_id']]);
        Db::delete('login_attempts', 'identifier = ?', [$row['email']]);
        Audit::log('password_reset_done', $row['email']);
        Mailer::send($row['email'], 'Your ' . Settings::get('site_name') . ' password was changed', "Hello {$row['name']},\n\nYour password was just changed. If this was not you, reset it again straight away and contact us.\n\n" . abs_url('forgot-password'));
        flash('success', 'Your password has been changed. Please sign in.');
        redirect(in_array($row['role'], ['admin', 'lecturer'], true) ? 'admin/login' : 'login');
    }

    /* ------------------------------------------------------------------ my account */

    public function dashboard(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        view('public/account', [
            'title' => 'My account',
            'tab' => 'overview',
            'user' => $u,
            'orders' => Db::all('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5', [$u['id']]),
        ]);
    }

    public function orders(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        view('public/account_orders', [
            'title' => 'My orders',
            'tab' => 'orders',
            'user' => $u,
            'orders' => Db::all('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 100', [$u['id']]),
        ]);
    }

    public function profile(): void
    {
        Auth::requireLogin();
        view('public/account_profile', ['title' => 'My details', 'tab' => 'profile', 'user' => Auth::user()]);
    }

    public function profileSave(): void
    {
        Auth::requireLogin();
        csrf_check();
        $u = Auth::user();
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
        $phone = trim((string)($_POST['phone'] ?? ''));
        $state = (string)($_POST['state'] ?? '');
        $errors = [];
        if ($name === '') {
            $errors[] = 'Please enter your name.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{5,40}$/', $phone)) {
            $errors[] = 'Please check the phone number.';
        }
        if ($state !== '' && !in_array($state, NigeriaStates::LIST, true)) {
            $errors[] = 'Please choose a state from the list.';
        }
        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            redirect('account/profile');
        }
        Db::update('users', [
            'name' => $name, 'phone' => $phone, 'address_line' => mb_substr(trim((string)($_POST['address_line'] ?? '')), 0, 200),
            'city' => mb_substr(trim((string)($_POST['city'] ?? '')), 0, 100), 'state' => $state,
        ], 'id = ?', [$u['id']]);
        flash('success', 'Your details were saved. They will be filled in for you at checkout.');
        redirect('account/profile');
    }

    public function passwordSave(): void
    {
        Auth::requireLogin();
        csrf_check();
        $u = Auth::user();
        $row = Db::one('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
        $new = (string)($_POST['password'] ?? '');
        if (!password_verify((string)($_POST['current_password'] ?? ''), $row['password_hash'])) {
            flash('error', 'Your current password is not correct.');
        } elseif ($new !== (string)($_POST['password2'] ?? '')) {
            flash('error', 'The two new passwords do not match.');
        } elseif ($p = self::passwordProblem($new, $u['email'], $u['role'], $u['name'])) {
            flash('error', $p);
        } else {
            Db::update('users', ['password_hash' => Auth::hash($new), 'pw_changed_at' => date('Y-m-d H:i:s')], 'id = ?', [$u['id']]);
            Auth::bumpEpoch((int)$u['id']); // other browsers are signed out, this one stays
            Auth::alert($u, 'Your password was changed', "Hello {$u['name']},\n\nYour password was just changed. If this was not you, reset it now and contact us: " . abs_url('forgot-password'));
            Audit::log('password_changed', $u['email']);
            flash('success', 'Your password was changed.');
        }
        redirect('account/profile');
    }

    /** Deletes the account (orders are kept for our records, but unlinked from the account). */
    public function delete(): void
    {
        Auth::requireLogin();
        csrf_check();
        $u = Auth::user();
        if ($u['role'] === 'admin') {
            flash('error', 'Administrator accounts cannot be deleted here.');
            redirect('account/profile');
        }
        $row = Db::one('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
        if (!password_verify((string)($_POST['current_password'] ?? ''), $row['password_hash'])) {
            flash('error', 'Your password is not correct, so the account was not deleted.');
            redirect('account/profile');
        }
        Audit::log('account_deleted', $u['email']);
        try {
            // guest articles: unpublished ones are removed; published ones keep the name but lose the email address
            foreach (Db::all("SELECT id, cover_image FROM posts WHERE author_id = ? AND is_user_article = 1 AND status <> 'published'", [$u['id']]) as $pa) {
                if ($pa['cover_image'] !== '') {
                    Upload::remove($pa['cover_image']);
                }
                Db::delete('posts', 'id = ?', [$pa['id']]);
            }
            Db::run("UPDATE posts SET author_email = '' WHERE author_id = ?", [$u['id']]);
        } catch (Throwable) {
            // articles feature not installed yet
        }
        Db::delete('users', 'id = ?', [$u['id']]);
        Auth::logout();
        session_id(bin2hex(random_bytes(16)));
        session_start();
        flash('success', 'Your account has been deleted. Past orders are kept only for our accounting records.');
        redirect('/');
    }
}

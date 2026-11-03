<?php
declare(strict_types=1);

/** The "enter the code we emailed you" page, used when signing up, when signing in to an unverified account, and from My account. */
final class VerifyController
{
    private static function say(string $r): void
    {
        $m = [
            'sent' => ['success', 'We have emailed a new 6-digit code. It works for 15 minutes.'],
            'wait' => ['info', 'A code was sent a moment ago. Please wait a minute before asking for another.'],
            'limit' => ['error', 'Too many codes were requested. Please try again in an hour.'],
            'fail' => ['error', 'We could not send the email just now. Please check that the address is right, then press "Send a new code".'],
        ][$r];
        flash($m[0], $m[1]);
    }

    public function form(): void
    {
        $u = EmailVerify::pending();
        if (!$u) {
            if (Auth::check() && EmailVerify::verified(Auth::user())) {
                redirect('account');
            }
            flash('info', 'Sign in or create an account first.');
            redirect('login');
        }
        view('public/verify_email', [
            'title' => 'Verify your email', 'meta_desc' => '', 'masked' => EmailVerify::mask($u['email']), 'signedIn' => Auth::check(),
            'left' => EmailVerify::triesLeft((int)$u['id']), 'test' => env('MAIL_MODE', 'log') === 'log' && env('APP_ENV') !== 'production',
        ]);
    }

    /** A signed-in account that was created before verification existed asks to verify. */
    public function start(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        if (EmailVerify::verified($u)) {
            flash('info', 'Your email address is already verified.');
            redirect('account');
        }
        EmailVerify::begin((int)$u['id']);
        self::say(EmailVerify::send((int)$u['id']));
        redirect('verify-email');
    }

    public function verify(): void
    {
        csrf_check();
        $u = EmailVerify::pending();
        if (!$u) {
            flash('info', 'Please sign in or register to continue.');
            redirect('login');
        }
        if (!rate_ok('verify', 30, 3600)) {
            abort(429, 'Too many attempts. Please try again later.');
        }
        $r = EmailVerify::check((int)$u['id'], (string)($_POST['code'] ?? ''));
        if ($r !== 'ok') {
            flash('error', [
                'wrong' => 'That code is not right. Check the email and try again.',
                'expired' => 'That code has expired. Press "Send a new code".',
                'locked' => 'Too many wrong tries for that code. Press "Send a new code".',
            ][$r]);
            redirect('verify-email');
        }
        EmailVerify::mark((int)$u['id']);
        unset($_SESSION['verify']);
        Audit::log('email_verified', $u['email']);
        if (Auth::check() && (int)$_SESSION['uid'] === (int)$u['id']) {
            flash('success', 'Thank you! Your email address is verified.');
            redirect('account/articles');
        }
        Auth::start((int)$u['id']);
        Auth::event((int)$u['id'], $u['email'], true, 'register');
        Mailer::send($u['email'], 'Welcome to ' . Settings::get('site_name'), "Hello {$u['name']},\n\nYour email address is verified and your account is ready. You can follow your orders and check out faster next time.\n\nSign in any time: " . abs_url('login') . "\n\n" . Settings::get('site_name'));
        flash('success', 'Welcome! Your email is verified and your account is ready.');
        flash('info', 'Tip: protect it with two-step verification. Open Account → Security (two minutes with Google or Microsoft Authenticator).');
        $next = AccountController::safeNext($_SESSION['intended'] ?? null, '/account');
        unset($_SESSION['intended']);
        redirect($next);
    }

    public function resend(): void
    {
        csrf_check();
        $u = EmailVerify::pending();
        if (!$u) {
            redirect('login');
        }
        self::say(EmailVerify::send((int)$u['id']));
        redirect('verify-email');
    }

    /** "Wrong email address? Start again": removes the unverified, never-used sign-up so the right address can be entered. */
    public function cancel(): void
    {
        csrf_check();
        $u = EmailVerify::pending();
        if ($u && EmailVerify::required($u) && !Auth::check()) {
            $hasOrders = (int)Db::val('SELECT COUNT(*) FROM orders WHERE user_id = ?', [$u['id']]) > 0;
            if (!$hasOrders) {
                Db::delete('users', 'id = ?', [$u['id']]);
            }
        }
        unset($_SESSION['verify']);
        redirect(Auth::check() ? 'account' : 'register');
    }
}

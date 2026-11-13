<?php
declare(strict_types=1);

/** The second sign-in step: a code from the authenticator app (or a recovery code). */
final class TwoFactorController
{
    public function form(): void
    {
        $p = Auth::pending();
        if (!$p) {
            redirect('login');
        }
        $admin = in_array($p['role'], ['admin', 'staff'], true);
        view('public/two_factor', ['title' => 'Two-step verification', 'meta_desc' => '', 'admin' => $admin, 'recovery' => isset($_GET['recovery']), 'email' => $p['user']['email']], $admin ? 'layout/bare' : 'layout/public');
    }

    public function verify(): void
    {
        csrf_check();
        $p = Auth::pending();
        if (!$p) {
            flash('error', 'Your sign-in expired. Please start again.');
            redirect('login');
        }
        $u = $p['user'];
        $admin = in_array($p['role'], ['admin', 'staff'], true);
        $back = $admin ? 'admin/login' : 'login';
        $key = '2fa:' . strtolower($u['email']);
        $failsAcct = (int)Db::val('SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [$key]);
        $failsIp = (int)Db::val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [client_ip()]);
        if ($failsAcct >= 5 || $failsIp >= 15) {
            unset($_SESSION['pending2fa']);
            flash('error', 'Too many wrong codes. Please wait 15 minutes and sign in again.');
            redirect($back);
        }
        $recovery = ($_POST['mode'] ?? '') === 'recovery';
        $code = trim((string)($_POST['code'] ?? ''));
        $ok = $recovery ? TwoFactor::useRecoveryCode((int)$u['id'], $code) : TwoFactor::verifyCode((int)$u['id'], $code);
        if (!$ok) {
            Db::run('INSERT INTO login_attempts (ip, identifier) VALUES (?, ?)', [client_ip(), $key]);
            Auth::event((int)$u['id'], $u['email'], false, $recovery ? 'recovery' : '2fa');
            flash('error', $recovery ? 'That recovery code is not valid or was already used.' : 'That code is not correct. Wait for the next code in your app and try again.');
            redirect('login/2fa' . ($recovery ? '?recovery=1' : ''));
        }
        Db::delete('login_attempts', 'identifier = ?', [$key]);
        Auth::complete($u, $recovery ? 'recovery' : '2fa');
        if ($recovery) {
            $left = TwoFactor::remainingCodes((int)$u['id']);
            flash('info', "You signed in with a recovery code. $left left. " . ($left <= 3 ? 'Please create new codes soon (Security page). ' : '') . 'If you lost your phone, set up two-step verification again on the new one.');
        }
        $intended = (string)($_SESSION['intended'] ?? '');
        unset($_SESSION['intended']);
        if ($admin) {
            redirect(preg_match('#^/admin[A-Za-z0-9/_.\-]*$#', $intended) ? $intended : '/' . ($u['role'] === 'admin' ? 'admin' : 'admin/courses'));
        }
        redirect(AccountController::safeNext($intended, '/account'));
    }
}

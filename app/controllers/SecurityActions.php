<?php
declare(strict_types=1);

/** The two-step-verification actions shared by "My account → Security" and "Admin → Security". */
trait SecurityActions
{
    /** 'account/security' or 'admin/security' */
    abstract protected function secBase(): string;

    abstract protected function secIsAdmin(): bool;

    private function secGuard(): array
    {
        $this->secIsAdmin() ? Auth::requireAdmin() : Auth::requireLogin();
        csrf_check();
        return Auth::user();
    }

    private function secPasswordOk(array $u): bool
    {
        $row = Db::one('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
        return $row && password_verify((string)($_POST['current_password'] ?? ''), $row['password_hash']);
    }

    /** Data the shared 2FA panel needs. */
    protected function secPanel(array $u): array
    {
        $codes = $_SESSION['new_codes'] ?? null;
        unset($_SESSION['new_codes']);
        return [
            'user' => $u, 'base' => $this->secBase(), 'enabled' => Auth::hasTwoFactor($u), 'setup' => TwoFactor::setupView($u), 'codes' => $codes,
            'remaining' => Auth::hasTwoFactor($u) ? TwoFactor::remainingCodes((int)$u['id']) : 0, 'isAdmin' => $this->secIsAdmin(),
            'required' => $this->secIsAdmin() && Settings::get('require_2fa_admin') === '1',
            'events' => Db::all('SELECT * FROM login_events WHERE user_id = ? ORDER BY id DESC LIMIT 10', [$u['id']]),
        ];
    }

    public function start(): void
    {
        $this->secGuard();
        TwoFactor::beginSetup();
        redirect($this->secBase() . '#2fa');
    }

    public function cancel(): void
    {
        $this->secGuard();
        TwoFactor::cancelSetup();
        redirect($this->secBase());
    }

    public function confirm(): void
    {
        $u = $this->secGuard();
        if (!rate_ok('totpconfirm', 10, 900)) {
            abort(429, 'Too many attempts. Please wait a few minutes and try again.');
        }
        $codes = TwoFactor::confirmSetup((int)$u['id'], (string)($_POST['code'] ?? ''));
        if ($codes === null) {
            flash('error', 'That code did not match. Check that the phone clock is set automatically, wait for a new code and try again.');
            redirect($this->secBase() . '#2fa');
        }
        Auth::bumpEpoch((int)$u['id']); // any other signed-in browser must sign in again, now with the second step
        $_SESSION['new_codes'] = $codes;
        Audit::log('2fa_enabled', $u['email']);
        Auth::alert($u, 'Two-step verification was turned on', "Hello {$u['name']},\n\nTwo-step verification was just turned on for your account. If this was not you, contact the site owner immediately.");
        flash('success', 'Two-step verification is ON. Save your recovery codes below: they are shown only once.');
        redirect($this->secBase() . '#2fa');
    }

    public function disable(): void
    {
        $u = $this->secGuard();
        if (!rate_ok('totpdisable', 8, 900)) {
            abort(429, 'Too many attempts. Please wait a few minutes and try again.');
        }
        if ($this->secIsAdmin() && Settings::get('require_2fa_admin') === '1') {
            flash('error', 'Two-step verification is required for administrators, so it cannot be turned off. Untick the requirement first.');
            redirect($this->secBase());
        }
        $code = trim((string)($_POST['code'] ?? ''));
        $second = TwoFactor::verifyCode((int)$u['id'], $code) || TwoFactor::useRecoveryCode((int)$u['id'], $code);
        if (!$this->secPasswordOk($u) || !$second) {
            flash('error', 'Your password or code was not correct, so two-step verification was left on.');
            redirect($this->secBase());
        }
        TwoFactor::disable((int)$u['id']);
        Auth::bumpEpoch((int)$u['id']);
        Audit::log('2fa_disabled', $u['email']);
        Auth::alert($u, 'Two-step verification was turned off', "Hello {$u['name']},\n\nTwo-step verification was just turned OFF for your account. If this was not you, change your password now and contact the site owner.");
        flash('info', 'Two-step verification is now off. We strongly advise turning it back on.');
        redirect($this->secBase());
    }

    public function regenerate(): void
    {
        $u = $this->secGuard();
        if (!rate_ok('totpregen', 8, 900)) {
            abort(429, 'Too many attempts. Please wait a few minutes and try again.');
        }
        if (!Auth::hasTwoFactor($u) || !$this->secPasswordOk($u)) {
            flash('error', 'Your password was not correct.');
            redirect($this->secBase());
        }
        $_SESSION['new_codes'] = TwoFactor::newRecoveryCodes((int)$u['id']);
        Audit::log('2fa_recovery_regenerated', $u['email']);
        flash('success', 'New recovery codes created. The old ones no longer work.');
        redirect($this->secBase() . '#2fa');
    }

    public function signoutAll(): void
    {
        $u = $this->secGuard();
        Auth::bumpEpoch((int)$u['id']);
        Audit::log('signout_everywhere', $u['email']);
        flash('success', 'Every other browser and device has been signed out of your account.');
        redirect($this->secBase());
    }
}

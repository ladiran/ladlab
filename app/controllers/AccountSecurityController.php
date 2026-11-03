<?php
declare(strict_types=1);

/** "My account → Security" for customers: two-step verification, recovery codes, sign-in history, sign out everywhere. */
final class AccountSecurityController
{
    use SecurityActions;

    protected function secBase(): string
    {
        return 'account/security';
    }

    protected function secIsAdmin(): bool
    {
        return false;
    }

    public function index(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        view('public/account_security', ['title' => 'Security', 'meta_desc' => 'Protect your account'] + $this->secPanel($u) + ['tab' => 'security']);
    }
}

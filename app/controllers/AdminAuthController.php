<?php
declare(strict_types=1);

final class AdminAuthController
{
    public function loginForm(): void
    {
        if (Auth::check() && Auth::isStaff()) {
            redirect(Auth::staffHome());
        }
        view('admin/login', ['title' => 'Admin sign in', 'meta_desc' => ''], 'layout/bare');
    }

    public function login(): void
    {
        csrf_check();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if (Auth::locked($email)) {
            flash('error', 'Too many failed attempts. Please wait 15 minutes and try again.');
            redirect('admin/login');
        }
        $r = Auth::attempt($email, $password, 'staff');
        if ($r === false) {
            flash('error', 'Incorrect email or password.');
            redirect('admin/login');
        }
        if ($r === '2fa') {
            redirect('login/2fa');
        }
        $intended = $_SESSION['intended'] ?? '';
        unset($_SESSION['intended']);
        redirect(preg_match('#^/admin[A-Za-z0-9/_.\-]*$#', $intended) ? $intended : '/' . Auth::staffHome());
    }

    public function logout(): void
    {
        csrf_check();
        Auth::logout();
        session_id(bin2hex(random_bytes(16))); // fresh session so the "signed out" message survives the redirect
        session_start();
        flash('success', 'You have been signed out.');
        redirect('admin/login');
    }
}

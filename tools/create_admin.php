<?php
/**
 * Creates (or resets) an administrator account. Use this after importing database/fresh_install.sql
 * by hand, or if you ever lose the admin password and the email reset is not available:
 *
 *   php tools/create_admin.php --email=you@example.com --password="a long passphrase" --name="Your Name"
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command line only.');
}
require dirname(__DIR__) . '/app/bootstrap.php';

$o = getopt('', ['email:', 'password:', 'name::', 'reset-2fa']);
$email = strtolower(trim((string)($o['email'] ?? '')));
$pw = (string)($o['password'] ?? '');
$name = trim((string)($o['name'] ?? 'Administrator'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || Auth::passwordProblem($pw, $email, 'admin', $name) !== '') {
    fwrite(STDERR, "Usage: php tools/create_admin.php --email=you@example.com --password=\"at least 10 characters, not easy to guess\" [--name=\"Your Name\"] [--reset-2fa]\n" . ($pw !== '' ? Auth::passwordProblem($pw, $email, 'admin', $name) . "\n" : ''));
    exit(1);
}
$hash = Auth::hash($pw);
$existing = Db::one('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    Db::update('users', ['password_hash' => $hash, 'role' => 'admin', 'name' => $name, 'email_verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
    Auth::bumpEpoch((int)$existing['id'], false);
    if (isset($o['reset-2fa'])) {
        TwoFactor::disable((int)$existing['id']);
        echo "Two-step verification was switched off for this account; set it up again after signing in.\n";
    }
    echo "Updated the existing account $email: password reset, role set to admin.\n";
} else {
    Db::insert('users', ['name' => $name, 'email' => $email, 'password_hash' => $hash, 'role' => 'admin', 'email_verified_at' => date('Y-m-d H:i:s')]);
    echo "Administrator $email created.\n";
}
foreach (['contact_email', 'notify_email'] as $k) {
    if (Settings::get($k) === '') {
        Settings::set($k, $email);
    }
}

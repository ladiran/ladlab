<?php
/**
 * Turns off two-step verification for one account (lost phone and lost recovery codes). Command line only:
 *   php tools/reset_2fa.php --email=you@example.com
 * The person can set it up again after signing in. Every other signed-in browser of that account is signed out.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command line only.');
}
require dirname(__DIR__) . '/app/bootstrap.php';
$o = getopt('', ['email:']);
$u = Db::one('SELECT id, email FROM users WHERE email = ?', [strtolower(trim((string)($o['email'] ?? '')))]);
if (!$u) {
    fwrite(STDERR, "No account with that email.\nUsage: php tools/reset_2fa.php --email=you@example.com\n");
    exit(1);
}
TwoFactor::disable((int)$u['id']);
Auth::bumpEpoch((int)$u['id'], false);
Audit::log('2fa_reset_cli', $u['email']);
echo "Two-step verification was turned off for {$u['email']}. Sign in and turn it on again.\n";

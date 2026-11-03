<?php
// Command-line installer: php tools/install_cli.php --name=clearsky_academy --admin-email=you@example.com --admin-password=... [--host --port --user --pass --admin-name --site-name --no-sample --production]
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
require ROOT . '/app/Installer.php';

$o = getopt('', ['host::', 'port::', 'name::', 'user::', 'pass::', 'site-name::', 'admin-name::', 'admin-email:', 'admin-password:', 'no-sample', 'production', 'no-app-user', 'app-url::']);
if (is_file(ROOT . '/storage/install.lock')) {
    fwrite(STDERR, "Already installed (storage/install.lock exists).\n");
    exit(1);
}
if ($p = Installer::problems()) {
    fwrite(STDERR, implode("\n", $p) . "\n");
    exit(1);
}
try {
    $log = Installer::run([
        'host' => $o['host'] ?? '127.0.0.1', 'port' => (int)($o['port'] ?? 3306), 'name' => $o['name'] ?? 'clearsky_academy',
        'user' => $o['user'] ?? 'root', 'pass' => $o['pass'] ?? '', 'site_name' => $o['site-name'] ?? 'ClearSky Academy',
        'admin_name' => $o['admin-name'] ?? 'Administrator', 'admin_email' => $o['admin-email'] ?? '', 'admin_password' => $o['admin-password'] ?? '',
        'sample' => !isset($o['no-sample']), 'app_url' => $o['app-url'] ?? '', 'app_user' => !isset($o['no-app-user']), 'env' => isset($o['production']) ? 'production' : 'local',
    ]);
    echo implode("\n", $log) . "\nDone.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}

<?php
// Apply pending database updates: php tools/migrate.php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require ROOT . '/app/Installer.php';

$pending = Migrator::pending();
if (!$pending) {
    echo "Database is up to date.\n";
    exit(0);
}
echo 'Applying: ' . implode(', ', $pending) . "\n";
$done = Migrator::run();
echo 'Done: ' . implode(', ', $done) . "\n";

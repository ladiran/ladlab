<?php
/**
 * Fetches science news. Schedule it every 15 minutes: it does the work only when an update is due (every 2 hours by default,
 * see Admin → News rules), so the rest of the time it exits at once.
 *   php cron/fetch_news.php           (only if due)
 *   php cron/fetch_news.php --force   (always)
 * Windows (WAMP) and Linux commands: Admin → Science news → "Automatic updates".
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command line only.');
}
require dirname(__DIR__) . '/app/bootstrap.php';

$result = NewsEngine::runIfDue(in_array('--force', $argv ?? [], true));
if (isset($result['skipped'])) {
    echo '[' . date('Y-m-d H:i:s') . '] Skipped: ' . $result['skipped'] . "\n";
    exit(0);
}
echo '[' . date('Y-m-d H:i:s') . '] ' . $result['summary'] . "\n";
exit(0);

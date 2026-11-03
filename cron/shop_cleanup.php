<?php
/**
 * Releases the reserved stock of unpaid orders whose hold time has ended. Run hourly (it is also triggered
 * automatically whenever someone opens the shop, checkout or Admin → Orders, at most every 5 minutes).
 *   php cron/shop_cleanup.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command line only.');
}
require dirname(__DIR__) . '/app/bootstrap.php';

$n = Shop::expireUnpaid(true);
echo '[' . date('Y-m-d H:i:s') . "] $n unpaid order(s) expired and their stock released.\n";

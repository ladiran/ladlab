<?php
declare(strict_types=1);

/** Fake news checker: records where the checker program listens (this computer, port 8765). The section stays OFF until the administrator switches it on in Settings, once the program is running. */
return function (PDO $pdo): void {
    $s = $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = svalue");
    $s->execute(['enable_fakenews', '0']);
    $s->execute(['fakenews_api_url', 'http://127.0.0.1:8765']);
};

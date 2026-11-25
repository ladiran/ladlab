<?php
declare(strict_types=1);

/** Fake news checker: switches the section on and records where the checker program listens (this computer, port 8765). */
return function (PDO $pdo): void {
    $s = $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = svalue");
    $s->execute(['enable_fakenews', '1']);
    $s->execute(['fakenews_api_url', 'http://127.0.0.1:8765']);
};

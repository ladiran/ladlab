<?php
declare(strict_types=1);

final class Audit
{
    public static function log(string $action, string $detail = ''): void
    {
        try {
            Db::insert('audit_log', [
                'user_id' => $_SESSION['uid'] ?? null,
                'action' => substr($action, 0, 60),
                'detail' => substr($detail, 0, 500),
                'ip' => client_ip(),
            ]);
        } catch (Throwable) {
            // Never let logging break a request.
        }
    }
}

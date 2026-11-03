<?php
declare(strict_types=1);

/** Sends plain-text email. MAIL_MODE=log (default, for WAMP testing) only writes to storage/logs/mail.log. */
final class Mailer
{
    /** Why the last send() failed (empty after a success). For logs and administrators only: never show it to visitors. */
    public static string $lastError = '';

    /** Tells a signed-in administrator right away that an email did not go out. */
    private static function failed(string $reason): bool
    {
        self::$lastError = $reason;
        try {
            if (PHP_SAPI !== 'cli' && !empty($_SESSION['uid']) && Auth::isAdmin()) {
                flash('error', 'An email could not be sent: ' . $reason . ' Check the email settings (config/.env) and the log storage/logs/mail.log.');
            }
        } catch (Throwable) {
            // never let the warning break the request
        }
        return false;
    }

    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        self::$lastError = '';
        $to = self::clean($to);
        $subject = self::clean($subject);
        $replyTo = $replyTo ? self::clean($replyTo) : null;
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return self::failed('the recipient address is not valid.');
        }
        $from = self::clean(env('MAIL_FROM', 'hello@example.com'));
        $fromName = self::clean(env('MAIL_FROM_NAME', 'ClearSky Academy'));
        $mode = env('MAIL_MODE', 'log');

        if ($mode === 'smtp') {
            try {
                return self::smtp($to, $subject, $body, $from, $fromName, $replyTo);
            } catch (Throwable $e) {
                self::log("SMTP FAILED: {$e->getMessage()}\nTo: $to\nSubject: $subject\n\n$body");
                return self::failed(mb_substr($e->getMessage(), 0, 200));
            }
        }
        if ($mode === 'mail') {
            $headers = "From: $fromName <$from>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $headers .= "\r\nReply-To: $replyTo";
            }
            $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
            return $ok ?: self::failed('PHP mail() did not accept the message (use MAIL_MODE=smtp).');
        }
        self::log("To: $to\nSubject: $subject" . ($replyTo ? "\nReply-To: $replyTo" : '') . "\n\n$body");
        return true;
    }

    private static function clean(string $s): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $s));
    }

    private static function log(string $entry): void
    {
        $dir = ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/mail.log', '[' . date('Y-m-d H:i:s') . "]\n" . $entry . "\n" . str_repeat('-', 60) . "\n", FILE_APPEND | LOCK_EX);
    }

    private static function smtp(string $to, string $subject, string $body, string $from, string $fromName, ?string $replyTo): bool
    {
        $host = (string)env('SMTP_HOST');
        $port = (int)env('SMTP_PORT', '587');
        $secure = env('SMTP_SECURE', 'tls');
        $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port, $errno, $errstr, 12);
        if (!$fp) {
            throw new RuntimeException("Cannot connect to SMTP: $errstr");
        }
        stream_set_timeout($fp, 12);

        $read = function () use ($fp): string {
            $resp = '';
            while (($line = fgets($fp, 515)) !== false) {
                $resp .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $resp;
        };
        $cmd = function (string $c, string $expect) use ($fp, $read): string {
            fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!str_starts_with($r, $expect)) {
                throw new RuntimeException("SMTP error after '" . (str_starts_with($c, 'AUTH') ? 'AUTH' : $c) . "': " . trim($r));
            }
            return $r;
        };

        $greet = $read();
        if (!str_starts_with($greet, '220')) {
            throw new RuntimeException('SMTP greeting failed: ' . trim($greet));
        }
        $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
        $cmd($ehlo, '250');
        if ($secure === 'tls') {
            $cmd('STARTTLS', '220');
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS negotiation failed.');
            }
            $cmd($ehlo, '250');
        }
        if (env('SMTP_USER')) {
            $cmd('AUTH LOGIN', '334');
            $cmd(base64_encode((string)env('SMTP_USER')), '334');
            $cmd(base64_encode(secret('SMTP_PASS')), '235');
        }
        $cmd("MAIL FROM:<$from>", '250');
        $cmd("RCPT TO:<$to>", '250');
        $cmd('DATA', '354');

        $headers = [
            "From: $fromName <$from>",
            "To: <$to>",
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . (parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), PHP_URL_HOST) ?: 'localhost') . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = "Reply-To: <$replyTo>";
        }
        $data = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
        fwrite($fp, $data . "\r\n.\r\n");
        $r = $read();
        if (!str_starts_with($r, '250')) {
            throw new RuntimeException('SMTP rejected the message: ' . trim($r));
        }
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    }
}

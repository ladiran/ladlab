<?php
declare(strict_types=1);

/** Small, defensive HTTP client (cURL): HTTPS only, size-capped, no private-network targets. */
final class Http
{
    /** Local testing only: NEWS_ALLOW_LOCAL=1 in .env permits http:// and localhost targets. */
    public static function allowLocal(): bool
    {
        return env('NEWS_ALLOW_LOCAL', '0') === '1' && env('APP_ENV', 'local') !== 'production';
    }

    /**
     * @param array $o timeout, max_bytes, headers[], etag, last_modified, accept
     * @return array{code:int, body:string, headers:array<string,string>, url:string, error:string}
     */
    public static function get(string $url, array $o = []): array
    {
        $result = ['code' => 0, 'body' => '', 'headers' => [], 'url' => $url, 'error' => ''];
        if (!function_exists('curl_init')) {
            $result['error'] = 'PHP cURL is not enabled.';
            return $result;
        }
        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = (string)($parts['host'] ?? '');
        $local = self::allowLocal();
        if ($host === '' || !in_array($scheme, $local ? ['http', 'https'] : ['https'], true)) {
            $result['error'] = 'Only https:// addresses are allowed.';
            return $result;
        }
        if (!$local && !self::isPublicHost($host)) {
            $result['error'] = 'That address points to a private or invalid network location.';
            return $result;
        }

        $max = (int)($o['max_bytes'] ?? 3 * 1024 * 1024);
        $headers = array_merge([
            'Accept: ' . ($o['accept'] ?? 'application/rss+xml, application/atom+xml, application/xml, text/xml, application/json;q=0.8, */*;q=0.5'),
        ], $o['headers'] ?? []);
        if (!empty($o['etag'])) {
            $headers[] = 'If-None-Match: ' . $o['etag'];
        }
        if (!empty($o['last_modified'])) {
            $headers[] = 'If-Modified-Since: ' . $o['last_modified'];
        }

        $body = '';
        $resp = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_PROTOCOLS => $local ? (CURLPROTO_HTTP | CURLPROTO_HTTPS) : CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => $local ? (CURLPROTO_HTTP | CURLPROTO_HTTPS) : CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => (int)($o['timeout'] ?? 15),
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'ClearSkyAcademyBot/1.0 (+' . abs_url('/news/sources') . ')',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$resp): int {
                $p = explode(':', $line, 2);
                if (count($p) === 2) {
                    $resp[strtolower(trim($p[0]))] = trim($p[1]);
                } elseif (str_starts_with($line, 'HTTP/')) {
                    $resp = []; // a redirect started a new header block
                }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$body, $max): int {
                $body .= $chunk;
                return strlen($body) > $max ? -1 : strlen($chunk); // abort oversized responses
            },
        ]);
        if (PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && defined('CURLSSLOPT_NATIVE_CA')) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA); // WAMP has no CA bundle: trust the Windows store
        }
        curl_exec($ch);
        $errNo = curl_errno($ch);
        $err = curl_error($ch);
        $result['code'] = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $result['url'] = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;

        $result['body'] = $body;
        $result['headers'] = $resp;
        if ($errNo === CURLE_WRITE_ERROR && strlen($body) > $max) {
            $result['error'] = 'The response was larger than ' . round($max / 1048576) . ' MB.';
        } elseif ($errNo !== 0) {
            $result['error'] = $err . (stripos($err, 'certificate') !== false ? ' (WAMP may need a CA bundle: set curl.cainfo in php.ini)' : '');
        }
        return $result;
    }

    /** True if the host resolves only to public IP addresses. */
    public static function isPublicHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool)filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        $ips = [];
        foreach ($records as $r) {
            $ips[] = $r['ip'] ?? ($r['ipv6'] ?? null);
        }
        if (!$ips) {
            $ip = gethostbyname($host);
            if ($ip === $host) {
                return false;
            }
            $ips[] = $ip;
        }
        foreach (array_filter($ips) as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }
        return true;
    }
}

<?php
declare(strict_types=1);

/**
 * Bridge to the fake news checker (services/fakenews), a small Python program that listens on this machine only.
 * Visitors never reach it directly: their browser talks to /fakenews/check on this site, which forwards the request.
 *
 * Safety rules: the target address comes only from the admin setting (never from a visitor); plain http:// is accepted
 * for this computer only; redirects are never followed; sizes and times are capped; the reply is rebuilt field by field.
 */
final class FakeNews
{
    public const DEFAULT_URL = 'http://127.0.0.1:8765';
    private const MAX_REPLY = 262144; // 256 KB

    /** The configured address if it is acceptable, otherwise the default. */
    public static function baseUrl(): string
    {
        $u = rtrim(trim(setting('fakenews_api_url', self::DEFAULT_URL)), '/');
        return self::validBase($u) ? $u : self::DEFAULT_URL;
    }

    public static function validBase(string $url): bool
    {
        $p = parse_url($url);
        if ($p === false || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment'])) {
            return false;
        }
        $scheme = strtolower((string)($p['scheme'] ?? ''));
        $host = strtolower(trim((string)($p['host'] ?? ''), '[]'));
        $path = (string)($p['path'] ?? '');
        if ($host === '' || ($path !== '' && $path !== '/')) {
            return false;
        }
        if ($scheme === 'https') {
            return true;
        }
        return $scheme === 'http' && self::isLoopback($host);
    }

    private static function isLoopback(string $host): bool
    {
        return in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }

    /** True when the checker program answers its health check. Short timeouts, so a down service never stalls a page. */
    public static function online(?string $base = null): bool
    {
        $r = self::request('GET', ($base ?? self::baseUrl()) . '/api/health', null, 3);
        $d = json_decode($r['body'], true);
        return $r['code'] === 200 && is_array($d) && ($d['status'] ?? '') === 'ok';
    }

    /**
     * Runs a check. $payload holds title, url and handle (already trimmed and length-limited by the caller).
     * @return array{ok:bool, status:int, data:?array, error:string}
     */
    public static function check(array $payload, ?string $base = null): array
    {
        $json = json_encode(array_intersect_key($payload, array_flip(['title', 'url', 'handle'])), JSON_UNESCAPED_UNICODE);
        $r = self::request('POST', ($base ?? self::baseUrl()) . '/api/check', (string)$json, 30);
        if ($r['code'] === 0) {
            return ['ok' => false, 'status' => 503, 'data' => null, 'error' => 'The checker is not running right now. Please try again later.'];
        }
        if ($r['code'] === 422) {
            return ['ok' => false, 'status' => 422, 'data' => null, 'error' => 'Please enter a headline, a link or a social media handle.'];
        }
        $d = json_decode($r['body'], true);
        if ($r['code'] !== 200 || !is_array($d)) {
            error_log('FakeNews: unexpected reply HTTP ' . $r['code'] . ' ' . $r['error']);
            return ['ok' => false, 'status' => 502, 'data' => null, 'error' => 'The checker could not complete this check. Please try again.'];
        }
        return ['ok' => true, 'status' => 200, 'data' => self::clean($d), 'error' => ''];
    }

    /** Rebuilds the reply from known fields only, with fixed types and lengths. */
    public static function clean(array $d): array
    {
        $verdict = in_array($d['verdict'] ?? '', ['verified_fake', 'unverified', 'authenticated'], true) ? $d['verdict'] : 'unverified';
        $str = fn($v, int $max): string => mb_substr(is_scalar($v) ? (string)$v : '', 0, $max);
        $evidence = [];
        foreach (array_slice(is_array($d['evidence'] ?? null) ? $d['evidence'] : [], 0, 30) as $e) {
            if (!is_array($e)) {
                continue;
            }
            $link = $str($e['link'] ?? '', 500);
            $evidence[] = [
                'source' => $str($e['source'] ?? '', 40),
                'direction' => in_array($e['direction'] ?? '', ['fake', 'authentic', 'neutral'], true) ? $e['direction'] : 'neutral',
                'detail' => $str($e['detail'] ?? '', 500),
                'link' => preg_match('#^https?://#i', $link) ? $link : '',
            ];
        }
        // The checker reports why a check was skipped ("factcheck: KEY not set"). Visitors only need to know which part was
        // unavailable; the technical reason stays in the checker's own log.
        $names = ['factcheck' => 'published fact-checks', 'gdelt' => 'news coverage', 'safebrowsing' => 'link safety list',
                  'rdap' => 'website age', 'wayback' => 'web archive', 'social' => 'social account',
                  'classifier' => 'writing-style model', 'url' => 'link', 'page fetch' => 'page title'];
        $skipped = [];
        foreach (array_slice(is_array($d['skipped'] ?? null) ? $d['skipped'] : [], 0, 20) as $item) {
            $key = strtolower(trim(explode(':', is_scalar($item) ? (string)$item : '', 2)[0]));
            if (isset($names[$key])) {
                $skipped[$names[$key]] = true;
            }
        }
        $skipped = array_keys($skipped);
        return [
            'verdict' => $verdict,
            'score' => max(0, min(100, (int)($d['score'] ?? 50))),
            'summary' => $str($d['summary'] ?? '', 600),
            'evidence' => $evidence,
            'skipped' => $skipped,
        ];
    }

    /** @return array{code:int, body:string, error:string} code 0 means no answer at all */
    private static function request(string $method, string $url, ?string $json, int $timeout): array
    {
        $out = ['code' => 0, 'body' => '', 'error' => ''];
        if (!function_exists('curl_init')) {
            $out['error'] = 'PHP cURL is not enabled.';
            return $out;
        }
        $host = strtolower(trim((string)parse_url($url, PHP_URL_HOST), '[]'));
        $body = '';
        $ch = curl_init($url);
        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$body): int {
                $body .= $chunk;
                return strlen($body) > self::MAX_REPLY ? -1 : strlen($chunk);
            },
        ];
        if ($json !== null) {
            $opts[CURLOPT_POSTFIELDS] = $json;
        }
        if (self::isLoopback($host)) {
            $opts[CURLOPT_PROXY] = ''; // never route a call to this same computer through an outside proxy
        }
        if (PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && defined('CURLSSLOPT_NATIVE_CA')) {
            $opts[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }
        curl_setopt_array($ch, $opts);
        curl_exec($ch);
        $out['code'] = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $out['error'] = curl_error($ch);
        $out['body'] = $body;
        return $out;
    }
}

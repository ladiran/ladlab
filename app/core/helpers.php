<?php
declare(strict_types=1);

/* ---------- escaping & urls ---------- */

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Public URL of an uploaded file stored as a relative path like uploads/images/x.jpg */
function upload_url(?string $rel): string
{
    return $rel ? url($rel) : '';
}

function abs_url(string $path = ''): string
{
    $configured = env('APP_URL');
    if ($configured) {
        return rtrim($configured, '/') . url($path);
    }
    $https = is_https();
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

function redirect(string $to): never
{
    $target = preg_match('#^https?://#', $to) ? $to : url($to);
    header('Location: ' . $target);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Is the direct connection from a proxy we trust (the same machine, e.g. a Cloudflare tunnel, or an address in TRUSTED_PROXIES)? */
function trusted_proxy(): bool
{
    $addr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($addr === '127.0.0.1' || $addr === '::1') {
        return true;
    }
    foreach (array_filter(array_map('trim', explode(',', (string)env('TRUSTED_PROXIES', '')))) as $p) {
        if ($p === $addr) {
            return true;
        }
    }
    return false;
}

/** The visitor's real address: behind a trusted proxy (Cloudflare tunnel) it comes from the proxy's header, otherwise from the connection. */
function client_ip(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (trusted_proxy()) {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
            $parts = explode(',', (string)($_SERVER[$h] ?? ''));
            // X-Forwarded-For: only the LAST entry (added by our own proxy) can be trusted; earlier ones are client-supplied.
            $v = trim($h === 'HTTP_CF_CONNECTING_IP' ? $parts[0] : end($parts));
            if ($v !== '' && filter_var($v, FILTER_VALIDATE_IP)) {
                $ip = $v;
                break;
            }
        }
    }
    return substr($ip, 0, 45);
}

/** True when the visitor used HTTPS, directly or through a trusted proxy that terminates it. */
function is_https(): bool
{
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((string)($_SERVER['SERVER_PORT'] ?? '')) === '443') {
        return true;
    }
    return trusted_proxy() && (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https' || str_contains((string)($_SERVER['HTTP_CF_VISITOR'] ?? ''), 'https'));
}

/* ---------- flash messages & old input ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function remember_input(array $data): void
{
    unset($data['_csrf'], $data['password'], $data['password2'], $data['current_password']);
    $_SESSION['old'] = $data;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        abort(419, 'Your session expired. Please go back, refresh the page and try again.');
    }
}

/* ---------- settings & modules ---------- */

function setting(string $key, string $default = ''): string
{
    return Settings::get($key, $default);
}

function module_enabled(string $name): bool
{
    return Settings::get('enable_' . $name, '0') === '1';
}

/* ---------- text utilities ---------- */

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(trim(preg_replace('~[^A-Za-z0-9]+~', '-', $text), '-'));
    return $text !== '' ? $text : 'item';
}

function excerpt(string $text, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    if (mb_strlen($text) <= $len) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $len - 1), " ,.;:-") . '…';
}

function fmt_date(?string $date, string $format = 'j M Y'): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '';
}

/** "5 min ago", "3 h ago", "2 d ago", or a date for older items. */
function time_ago(?string $datetime): string
{
    $ts = $datetime ? strtotime($datetime) : false;
    if (!$ts) {
        return '';
    }
    $d = max(0, time() - $ts);
    return match (true) {
        $d < 60 => 'just now',
        $d < 3600 => (int)floor($d / 60) . ' min ago',
        $d < 86400 => (int)floor($d / 3600) . ' h ago',
        $d < 7 * 86400 => (int)floor($d / 86400) . ' d ago',
        default => date('j M Y', $ts),
    };
}

function md(?string $text): string
{
    return Markdown::render((string)$text);
}

/** For text written by visitors (guest articles): safer rendering. */
function md_ugc(?string $text): string
{
    return Markdown::renderUntrusted((string)$text);
}

function nl_lines(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$text) ?: [])));
}

/* ---------- views ---------- */

function view(string $template, array $vars = [], string $layout = 'layout/public'): void
{
    $vars += ['title' => '', 'meta_desc' => '', 'og_image' => '', 'body_class' => '', 'jsonld' => [], 'og_type' => 'website', 'noindex' => false];
    extract($vars, EXTR_SKIP);
    ob_start();
    require ROOT . '/app/views/' . $template . '.php';
    $content = ob_get_clean();
    require ROOT . '/app/views/' . $layout . '.php';
}

function partial(string $template, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require ROOT . '/app/views/partials/' . $template . '.php';
}

function abort(int $code = 404, string $message = ''): never
{
    http_response_code($code);
    $titles = [403 => 'Not allowed', 404 => 'Page not found', 419 => 'Session expired', 429 => 'Too many requests', 500 => 'Something went wrong'];
    $isAdmin = str_starts_with(request_path(), '/admin');
    view('public/error', [
        'title' => $titles[$code] ?? 'Error',
        'code' => $code,
        'heading' => $titles[$code] ?? 'Error',
        'message' => $message,
    ], $isAdmin && Auth::isAdmin() ? 'layout/admin' : 'layout/public');
    exit;
}

function request_path(): string
{
    return $GLOBALS['REQUEST_PATH'] ?? '/';
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- form protection ---------- */

/** Allows $max hits per $window seconds for a bucket per IP. Returns false if exceeded. */
function rate_ok(string $bucket, int $max, int $window): bool
{
    $ip = client_ip();
    Db::run('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    $hits = (int)Db::val(
        'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND ip = ? AND created_at > (NOW() - INTERVAL ' . (int)$window . ' SECOND)',
        [$bucket, $ip]
    );
    if ($hits >= $max) {
        return false;
    }
    Db::run('INSERT INTO rate_limits (bucket, ip) VALUES (?, ?)', [$bucket, $ip]);
    return true;
}

function form_started(string $form): void
{
    $_SESSION['form_t'][$form] = time();
}

/** Honeypot + minimum fill time. Returns true when the submission looks human. */
function form_human(string $form): bool
{
    if (!empty($_POST['website'])) {
        return false;
    }
    $t = $_SESSION['form_t'][$form] ?? 0;
    return $t > 0 && (time() - $t) >= 3;
}

function honeypot(): string
{
    return '<div class="hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}

<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT . '/config/config.php';

date_default_timezone_set('Africa/Lagos');
mb_internal_encoding('UTF-8');

$debug = env('APP_DEBUG', '0') === '1';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

/* Base path: '' when the site root is the web root, '/clearsky_academy' when it lives in a sub-folder (typical on WAMP). */
if (PHP_SAPI === 'cli') {
    define('BASE_PATH', rtrim((string)parse_url((string)env('APP_URL', ''), PHP_URL_PATH), '/'));
    $GLOBALS['REQUEST_PATH'] = '/';
} else {
    (function () {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/'); // dirname() gives a backslash on Windows for "/index.php"
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // Request came through the project-root .htaccess (URL has no /public part): the base is the parent folder.
        if (str_ends_with($dir, '/public') && !str_starts_with($uri, $dir . '/') && $uri !== $dir) {
            $dir = substr($dir, 0, -7);
        }
        define('BASE_PATH', $dir);
        $GLOBALS['REQUEST_PATH'] = '/' . trim(substr($uri, strlen($dir)), '/');
    })();
}

require ROOT . '/app/core/helpers.php';
spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers'] as $dir) {
        $file = ROOT . "/app/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

set_exception_handler(function (Throwable $e) use ($debug): void {
    error_log((string)$e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($debug) {
        echo '<pre style="padding:1rem;white-space:pre-wrap">' . e((string)$e) . '</pre>';
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Error</title><body style="font-family:sans-serif;padding:2rem"><h1>Something went wrong</h1><p>Please try again in a moment.</p>';
    }
    exit;
});

if (PHP_SAPI !== 'cli') {
    /* Sessions */
    $https = is_https();
    header_remove('X-Powered-By');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_name('csa_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_PATH ?: '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    /* Security headers */
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-site');
    header('X-Permitted-Cross-Domain-Policies: none');
    if (!empty($_SESSION['uid']) || str_starts_with(request_path(), '/admin')) {
        header('Cache-Control: no-store'); // signed-in pages are never kept by browsers or proxies
    }
    if (!Seo::indexingAllowed() || Seo::isPrivatePath(request_path())) {
        header('X-Robots-Tag: noindex, nofollow'); // also covers non-HTML responses
    }
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

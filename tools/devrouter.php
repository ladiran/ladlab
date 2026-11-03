<?php
// Router for PHP's built-in server (testing without Apache): php -S 127.0.0.1:8080 -t public tools/devrouter.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../public' . $path;
if ($path !== '/' && is_file($file)) {
    return false; // let the built-in server deliver real files (css, js, images, install.php)
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../public/index.php';

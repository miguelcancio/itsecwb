<?php declare(strict_types=1);

require_once __DIR__ . '/includes/logger.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if (is_file($file)) {
    return false;
}

if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/index.php';
    return;
}

if (str_ends_with($path, '.php')) {
    $candidate = __DIR__ . $path;
    if (is_file($candidate)) {
        require $candidate;
        return;
    }
}

http_response_code(404);
log_event('not_found', 'Route not found', ['path' => $path]);
require __DIR__ . '/errors/404.php';



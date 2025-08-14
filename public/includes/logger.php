<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/supabase.php';

function app_log_dir(): string {
    $dir = __DIR__ . '/../../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

function app_log_path(): string {
    return app_log_dir() . '/app.log';
}

/**
 * Get the current client IP address
 * Handles various proxy scenarios and returns the most likely real IP
 */
function get_client_ip(): string {
    // Check for forwarded IP headers (common with proxies/load balancers)
    $headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR', // Standard proxy header
        'HTTP_X_FORWARDED',     // Alternative proxy header
        'HTTP_X_CLUSTER_CLIENT_IP', // Load balancer
        'HTTP_FORWARDED_FOR',   // RFC 7239
        'HTTP_FORWARDED',       // RFC 7239
        'HTTP_X_REAL_IP',      // Nginx proxy
        'HTTP_CLIENT_IP',      // Client IP
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Handle comma-separated IPs (take first one)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Validate IP format
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}



function log_event(string $type, string $message, array $context = []): void {
    $entry = [
        'ts' => gmdate('c'),
        'type' => $type,
        'message' => $message,
        'ip' => get_client_ip(),
        'user_id' => $_SESSION['user']['id'] ?? null,
        'role' => $_SESSION['user']['role'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'context' => $context,
    ];
    $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents(app_log_path(), $line, FILE_APPEND | LOCK_EX);

    // Mirror to DB (non-blocking)
    try {
        $level = 'info';
        if ($type === 'php_error' || $type === 'exception') {
            $level = 'error';
        } elseif (substr($type, -5) === '_fail') {
            $level = 'warn';
        }

        $data = [
            'level' => $level,
            'event' => $type,
            'message' => $message,
            'user_id' => $entry['user_id'],
            'ip' => $entry['ip'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'route' => $entry['uri'],
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'status_code' => http_response_code(),
            'context' => $context,
            'created_at' => $entry['ts'],
        ];
        sb_insert('app_logs', $data);
    } catch (Throwable $e) {
        // ignore
    }
}

// Production-safe error/exception handlers
set_exception_handler(function (Throwable $e) {
    log_event('exception', 'Unhandled exception', [
        'class' => get_class($e),
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);
    http_response_code(500);
    $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    if ($debug) {
        echo 'Error: ' . htmlspecialchars($e->getMessage());
    } else {
        echo 'An unexpected error occurred.';
    }
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    // Convert errors to exceptions for uniform logging
    log_event('php_error', $message, ['severity' => $severity, 'file' => $file, 'line' => $line]);
    return true; // prevent default handler
});



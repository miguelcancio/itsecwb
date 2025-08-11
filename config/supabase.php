<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use GuzzleHttp\Client as HttpClient;

// Composer autoload
require __DIR__ . '/../vendor/autoload.php';

// Load .env from project root
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Environment flags
$appDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
if ($appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    // E_STRICT is deprecated; do not reference it
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
    ini_set('display_errors', '0');
}

// Basic configuration fetchers
function env_string(string $key, ?string $default = null): ?string {
    return isset($_ENV[$key]) && $_ENV[$key] !== '' ? (string)$_ENV[$key] : $default;
}

function supabase_config(): array {
    $url = env_string('SUPABASE_URL');
    $key = env_string('SUPABASE_KEY');
    if (!$url || !$key) {
        http_response_code(500);
        exit('Service misconfiguration.');
    }
    $url = rtrim($url, '/');
    return [
        'base_url' => $url . '/rest/v1',
        'anon_key' => $key,
    ];
}

function ca_bundle_path_or_verify(): mixed {
    $envPath = env_string('SUPABASE_CA_BUNDLE') ?? env_string('CA_BUNDLE_PATH');
    if ($envPath && is_file($envPath)) {
        return $envPath;
    }
    $local = __DIR__ . '/cacert.pem';
    if (is_file($local)) {
        return $local;
    }
    // Optional unsafe skip in debug if explicitly allowed
    $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    $allowInsecure = ($_ENV['ALLOW_INSECURE_SSL'] ?? 'false') === 'true';
    if ($debug && $allowInsecure) {
        return false; // Guzzle: disable verification (NOT for production)
    }
    return true; // use system CA store
}

function supabase_http(): HttpClient {
    static $client = null;
    if ($client === null) {
        $client = new HttpClient([
            'http_errors' => false,
            'timeout' => 15,
            'connect_timeout' => 10,
            'verify' => ca_bundle_path_or_verify(),
        ]);
    }
    return $client;
}

function supabase_headers(): array {
    $cfg = supabase_config();
    return [
        'apikey' => $cfg['anon_key'],
        'Authorization' => 'Bearer ' . $cfg['anon_key'],
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Prefer' => 'return=representation',
    ];
}

function supabase_build_query(array $filters = [], ?int $limit = null, ?int $offset = null, ?string $select = '*'): string {
    $params = [];
    if ($select) {
        $params['select'] = $select;
    }
    foreach ($filters as $column => $condition) {
        if (is_array($condition)) {
            $op = $condition['op'] ?? 'eq';
            $value = $condition['value'] ?? null;
        } else {
            $op = 'eq';
            $value = $condition;
        }
        $params[$column] = $op . '.' . rawurlencode((string)$value);
    }
    if ($limit !== null) {
        $params['limit'] = (string)$limit;
    }
    if ($offset !== null) {
        $params['offset'] = (string)$offset;
    }
    return http_build_query($params);
}

function sb_get(string $table, array $filters = [], ?int $limit = null, ?int $offset = null, string $select = '*'): array {
    $cfg = supabase_config();
    $query = supabase_build_query($filters, $limit, $offset, $select);
    $url = $cfg['base_url'] . '/' . rawurlencode($table) . ($query ? ('?' . $query) : '');
    $res = supabase_http()->request('GET', $url, [ 'headers' => supabase_headers() ]);
    $status = $res->getStatusCode();
    $body = (string)$res->getBody();
    if ($status >= 200 && $status < 300) {
        $json = json_decode($body, true);
        return is_array($json) ? $json : [];
    }
    return [];
}

function sb_insert(string $table, array $data): ?array {
    $cfg = supabase_config();
    $url = $cfg['base_url'] . '/' . rawurlencode($table);
    $res = supabase_http()->request('POST', $url, [
        'headers' => supabase_headers(),
        'body' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
    $status = $res->getStatusCode();
    $body = (string)$res->getBody();
    if ($status >= 200 && $status < 300) {
        $json = json_decode($body, true);
        return is_array($json) ? ($json[0] ?? $json) : null;
    }
    return null;
}

function sb_update(string $table, array $filters, array $data): bool {
    $cfg = supabase_config();
    $query = supabase_build_query($filters, null, null, null);
    $url = $cfg['base_url'] . '/' . rawurlencode($table) . ($query ? ('?' . $query) : '');
    $res = supabase_http()->request('PATCH', $url, [
        'headers' => supabase_headers(),
        'body' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
    $status = $res->getStatusCode();
    return $status >= 200 && $status < 300;
}

function sb_delete(string $table, array $filters): bool {
    $cfg = supabase_config();
    $query = supabase_build_query($filters);
    $url = $cfg['base_url'] . '/' . rawurlencode($table) . ($query ? ('?' . $query) : '');
    $res = supabase_http()->request('DELETE', $url, [
        'headers' => supabase_headers(),
    ]);
    $status = $res->getStatusCode();
    return $status >= 200 && $status < 300;
}

// Simple centralized redirect helper with exit
function redirect(string $path): void {
    header('Location: ' . $path, true, 302);
    exit;
}

// Centralized JSON responder (for API endpoints if needed)
function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

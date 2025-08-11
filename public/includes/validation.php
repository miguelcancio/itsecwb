<?php
declare(strict_types=1);

// Include guard in case this file is loaded via different paths
if (defined('APP_VALIDATION_INCLUDED')) { return; }
define('APP_VALIDATION_INCLUDED', true);

require_once __DIR__ . '/logger.php';

function sanitize_text(string $value, int $maxLen = 255): string {
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH) ?? '';
    return mb_substr($value, 0, $maxLen);
}

function validate_email(?string $email): ?string {
    $email = trim((string)$email);
    if ($email === '' || mb_strlen($email) > 254) {
        log_event('validation_fail', 'Invalid email length');
        return null;
    }
    $filtered = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$filtered) {
        log_event('validation_fail', 'Invalid email format', ['email' => $email]);
        return null;
    }
    return strtolower($filtered);
}

function validate_password(?string $password): ?string {
    $password = (string)$password;
    $len = mb_strlen($password);
    if ($len < 12 || $len > 128) {
        log_event('validation_fail', 'Password length policy violation');
        return null;
    }
    $hasUpper = preg_match('/[A-Z]/', $password) === 1;
    $hasLower = preg_match('/[a-z]/', $password) === 1;
    $hasDigit = preg_match('/\d/', $password) === 1;
    $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password) === 1;
    if (!($hasUpper && $hasLower && $hasDigit && $hasSpecial)) {
        log_event('validation_fail', 'Password complexity policy violation');
        return null;
    }
    return $password;
}

function validate_int_range($value, int $min, int $max): ?int {
    if (!is_numeric($value)) {
        log_event('validation_fail', 'Non-numeric input for integer range');
        return null;
    }
    $int = (int)$value;
    if ($int < $min || $int > $max) {
        log_event('validation_fail', 'Integer out of range', ['min' => $min, 'max' => $max, 'value' => $int]);
        return null;
    }
    return $int;
}

// CSRF
function ensure_session_started(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_token(): string {
    ensure_session_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf_token(string $token): void {
    ensure_session_started();
    $valid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    if (!$valid) {
        log_event('access_control_fail', 'Invalid CSRF token');
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}



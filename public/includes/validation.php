<?php
declare(strict_types=1);

// Include guard in case this file is loaded via different paths
if (defined('APP_VALIDATION_INCLUDED')) { return; }
define('APP_VALIDATION_INCLUDED', true);

require_once __DIR__ . '/logger.php';

/**
 * @deprecated Use validate_text_input() instead. This function will be removed in a future version.
 * Sanitizes text input by removing potentially dangerous characters.
 */
function sanitize_text(string $value, int $maxLen = 255): string {
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH) ?? '';
    return mb_substr($value, 0, $maxLen);
}

/**
 * Validates text input using a reject-based approach.
 * Returns the clean input or null if validation fails.
 */
function validate_text_input(?string $input, int $maxLen = 255, string $context = 'general'): ?string {
    if ($input === null) {
        log_event('validation_fail', 'Null input provided', ['context' => $context]);
        return null;
    }
    
    $input = trim((string)$input);
    
    // Check length
    if (mb_strlen($input) === 0) {
        log_event('validation_fail', 'Empty input provided', ['context' => $context]);
        return null;
    }
    
    if (mb_strlen($input) > $maxLen) {
        log_event('validation_fail', 'Input too long', [
            'context' => $context,
            'length' => mb_strlen($input),
            'max_length' => $maxLen
        ]);
        return null;
    }
    
    // Block dangerous patterns
    $dangerousPatterns = [
        // Script tags and JavaScript
        '/<script\b[^>]*>/i',
        '/<\/script>/i',
        '/javascript:/i',
        '/vbscript:/i',
        '/data:/i',
        '/on\w+\s*=/i', // Event handlers like onclick, onload, etc.
        
        // HTML tags that could be dangerous
        '/<iframe\b[^>]*>/i',
        '/<\/iframe>/i',
        '/<object\b[^>]*>/i',
        '/<\/object>/i',
        '/<embed\b[^>]*>/i',
        '/<\/embed>/i',
        '/<form\b[^>]*>/i',
        '/<\/form>/i',
        '/<input\b[^>]*>/i',
        '/<textarea\b[^>]*>/i',
        '/<select\b[^>]*>/i',
        '/<button\b[^>]*>/i',
        
        // SQL injection patterns
        '/union\s+select/i',
        '/drop\s+table/i',
        '/delete\s+from/i',
        '/insert\s+into/i',
        '/update\s+set/i',
        '/alter\s+table/i',
        '/exec\s*\(/i',
        '/execute\s*\(/i',
        
        // XSS patterns
        '/<img\b[^>]*on\w+\s*=/i',
        '/<a\b[^>]*javascript:/i',
        '/<div\b[^>]*on\w+\s*=/i',
        '/<span\b[^>]*on\w+\s*=/i',
        
        // Other dangerous patterns
        '/<meta\b[^>]*>/i',
        '/<link\b[^>]*>/i',
        '/<style\b[^>]*>/i',
        '/<title\b[^>]*>/i'
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            log_event('validation_fail', 'Dangerous pattern detected', [
                'context' => $context,
                'pattern' => $pattern,
                'input_sample' => mb_substr($input, 0, 100)
            ]);
            return null;
        }
    }
    
    // Block excessive whitespace
    if (preg_match('/\s{10,}/', $input)) {
        log_event('validation_fail', 'Excessive whitespace detected', ['context' => $context]);
        return null;
    }
    
    // Block control characters
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $input)) {
        log_event('validation_fail', 'Control characters detected', ['context' => $context]);
        return null;
    }
    
    return $input;
}

/**
 * Validates security question text specifically.
 * More restrictive than general text validation.
 */
function validate_security_question_text(?string $input): ?string {
    if ($input === null) {
        log_event('validation_fail', 'Security question is null');
        return null;
    }
    
    $input = trim((string)$input);
    
    // Check length
    if (mb_strlen($input) < 10) {
        log_event('validation_fail', 'Security question too short', ['length' => mb_strlen($input)]);
        return null;
    }
    
    if (mb_strlen($input) > 255) {
        log_event('validation_fail', 'Security question too long', ['length' => mb_strlen($input)]);
        return null;
    }
    
    // Use general text validation
    return validate_text_input($input, 255, 'security_question');
}

/**
 * Validates general user input text.
 * Standard validation for most user-provided text.
 */
function validate_user_input_text(?string $input, int $maxLen = 255): ?string {
    return validate_text_input($input, $maxLen, 'user_input');
}

/**
 * Validates admin notes text.
 * Allows slightly more content but still blocks dangerous patterns.
 */
function validate_admin_notes(?string $input, int $maxLen = 1000): ?string {
    if ($input === null) {
        log_event('validation_fail', 'Admin notes is null');
        return null;
    }
    
    $input = trim((string)$input);
    
    // Check length
    if (mb_strlen($input) === 0) {
        return null; // Allow empty admin notes
    }
    
    if (mb_strlen($input) > $maxLen) {
        log_event('validation_fail', 'Admin notes too long', [
            'length' => mb_strlen($input),
            'max_length' => $maxLen
        ]);
        return null;
    }
    
    // Use general text validation
    return validate_text_input($input, $maxLen, 'admin_notes');
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

function validate_security_answer(?string $answer): ?string {
    $answer = trim((string)$answer);
    
    // Block very short answers (≤2 characters)
    if (mb_strlen($answer) <= 2) {
        log_event('validation_fail', 'Security answer too short', ['answer_length' => mb_strlen($answer)]);
        return null;
    }
    
    // Block common/weak answers
    $commonAnswers = [
        'yes', 'no', 'maybe', 'ok', 'test', 'password', '123', 'abc', 'qwerty', 
        'admin', 'user', 'answer', 'response', 'reply', 'none', 'nothing', 'unknown',
        'true', 'false', 'yes', 'no', 'maybe', 'ok', 'test', 'password', '123', 
        'abc', 'qwerty', 'admin', 'user', 'answer', 'response', 'reply', 'none', 
        'nothing', 'unknown', 'true', 'false', 'idk', 'dunno', 'whatever', 'same',
        'default', 'blank', 'empty', 'null', 'undefined', 'n/a', 'na', 'skip'
    ];
    
    $normalizedAnswer = strtolower($answer);
    if (in_array($normalizedAnswer, $commonAnswers)) {
        log_event('validation_fail', 'Security answer too common/weak', ['answer' => $answer]);
        return null;
    }
    
    // Block numeric-only answers
    if (preg_match('/^\d+$/', $answer)) {
        log_event('validation_fail', 'Security answer is numeric-only', ['answer' => $answer]);
        return null;
    }
    
    // Block repeated character patterns (e.g., "aaa", "111", "abcabc")
    if (preg_match('/(.)\1{2,}/', $answer) || preg_match('/(.{2,})\1/', $answer)) {
        log_event('validation_fail', 'Security answer has repeated patterns', ['answer' => $answer]);
        return null;
    }
    
    // Block single character repeated
    if (mb_strlen($answer) > 0 && preg_match('/^(.)\1*$/', $answer)) {
        log_event('validation_fail', 'Security answer is single character repeated', ['answer' => $answer]);
        return null;
    }
    
    // Block answers that are too long
    if (mb_strlen($answer) > 100) {
        log_event('validation_fail', 'Security answer too long', ['answer_length' => mb_strlen($answer)]);
        return null;
    }
    
    // Block answers with only whitespace characters
    if (preg_match('/^\s+$/', $answer)) {
        log_event('validation_fail', 'Security answer contains only whitespace');
        return null;
    }
    
    // Block answers that are just punctuation
    if (preg_match('/^[^\w\s]+$/', $answer)) {
        log_event('validation_fail', 'Security answer contains only punctuation', ['answer' => $answer]);
        return null;
    }
    
    return $answer;
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



<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/user.php';

ensure_session_started();

// Redirect GET requests to the unified login UI on index.php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    redirect('/index.php');
}

// Handle POST login submissions
$error = null;
require_csrf_token($_POST['csrf_token'] ?? '');
$email = validate_email($_POST['email'] ?? '') ?? '';
$password = (string)($_POST['password'] ?? '');

$user = $email ? get_user_by_email($email) : null;
if (!$user) {
    log_event('auth_fail', 'Invalid credentials');
    $error = 'Invalid username and/or password';
} else {
    if (!empty($user['is_disabled'])) {
        log_event('auth_fail', 'Disabled account login attempt', ['user_id' => $user['id']]);
        $error = 'Invalid username and/or password';
    } elseif (is_account_locked($user)) {
        log_event('auth_fail', 'Locked account login attempt', ['user_id' => $user['id']]);
        $error = 'Account temporarily locked. Please try again later.';
    } elseif (!password_verify($password, $user['password_hash'] ?? '')) {
        increment_failed_attempts($user);
        log_event('auth_fail', 'Password mismatch', ['user_id' => $user['id']]);
        $error = 'Invalid username and/or password';
    } else {
        reset_failed_attempts($user['id']);
        $currentIp = get_client_ip();
        $currentTime = now_iso();
        record_login($user['id'], $currentIp);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'last_login_at' => $currentTime,
            'last_login_ip' => $currentIp,
        ];
        $_SESSION['show_login_info'] = true;
        log_event('auth_success', 'User logged in');
        switch ($user['role']) {
            case 'admin': redirect('/admin/dashboard.php');
            case 'manager': redirect('/manager/dashboard.php');
            default: redirect('/customer/dashboard.php');
        }
    }
}

// On failure, go back to index login UI with email parameter for security messages
redirect('/index.php' . ($email ? '?email=' . urlencode($email) . '&error=1' : '?error=1'));

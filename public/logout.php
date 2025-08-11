<?php
declare(strict_types=1);
require __DIR__ . '/../config/supabase.php';
require __DIR__ . '/includes/logger.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
log_event('auth_success', 'User logged out');
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
redirect('/index.php');

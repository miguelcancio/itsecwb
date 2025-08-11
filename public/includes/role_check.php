<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';

function require_role($roles): void {
    $userRole = $_SESSION['user']['role'] ?? null;
    $roles = is_array($roles) ? $roles : [$roles];
    if (!$userRole || !in_array($userRole, $roles, true)) {
        log_event('access_control_fail', 'Role denied', ['required' => $roles, 'have' => $userRole]);
        http_response_code(403);
        include __DIR__ . '/../errors/403.php';
        exit;
    }
}



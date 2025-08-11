<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/supabase.php';
require_once __DIR__ . '/validation.php';

const MAX_FAILED_ATTEMPTS = 5;
const ACCOUNT_LOCK_MINUTES = 15;
const PASSWORD_HISTORY_COUNT = 5;
const PASSWORD_MIN_AGE_DAYS = 1;

function now_iso(): string { return gmdate('c'); }

function get_user_by_email(string $email): ?array {
    $rows = sb_get('users', ['email' => strtolower($email)], 1);
    return $rows[0] ?? null;
}

function get_user_by_id(string $id): ?array {
    $rows = sb_get('users', ['id' => $id], 1);
    return $rows[0] ?? null;
}

function is_account_locked(array $user): bool {
    if (!empty($user['locked_until'])) {
        return strtotime($user['locked_until']) > time();
    }
    return false;
}

function increment_failed_attempts(array $user): void {
    $failed = (int)($user['failed_attempts'] ?? 0) + 1;
    $data = ['failed_attempts' => $failed];
    if ($failed >= MAX_FAILED_ATTEMPTS) {
        $data['locked_until'] = gmdate('c', time() + ACCOUNT_LOCK_MINUTES * 60);
    }
    sb_update('users', ['id' => $user['id']], $data);
}

function reset_failed_attempts(string $userId): void {
    sb_update('users', ['id' => $userId], ['failed_attempts' => 0, 'locked_until' => null]);
}

function record_login(string $userId, string $ip): void {
    sb_update('users', ['id' => $userId], [
        'last_login_at' => now_iso(),
        'last_login_ip' => $ip
    ]);
}

function create_user(string $email, string $password, string $role = 'customer'): ?array {
    $emailValid = validate_email($email);
    $passValid = validate_password($password);
    if (!$emailValid || !$passValid) {
        return null;
    }
    $userExisting = get_user_by_email($emailValid);
    if ($userExisting) {
        log_event('auth_fail', 'Registration email already exists', ['email' => $emailValid]);
        return null;
    }
    $passwordHash = password_hash($passValid, PASSWORD_DEFAULT);
    $user = sb_insert('users', [
        'email' => $emailValid,
        'password_hash' => $passwordHash,
        'role' => $role,
        'failed_attempts' => 0,
        'locked_until' => null,
        'password_changed_at' => now_iso(),
        'created_at' => now_iso(),
        'updated_at' => now_iso(),
        'is_disabled' => false
    ]);
    if ($user && !empty($user['id'])) {
        sb_insert('password_history', [
            'user_id' => $user['id'],
            'password_hash' => $passwordHash,
            'changed_at' => now_iso(),
        ]);
    }
    return $user ?: null;
}

function get_password_history_hashes(string $userId, int $limit = 100): array {
    $rows = sb_get('password_history', ['user_id' => $userId], $limit, 0);
    $hashes = [];
    foreach ($rows as $row) { if (!empty($row['password_hash'])) { $hashes[] = $row['password_hash']; } }
    return $hashes;
}

function password_too_young(?string $changedAt): bool {
    if (empty($changedAt)) { return false; }
    $minTs = strtotime('+' . PASSWORD_MIN_AGE_DAYS . ' day', strtotime($changedAt));
    return time() < $minTs;
}

function change_password(string $userId, string $currentPassword, string $newPassword): array {
    $user = get_user_by_id($userId);
    if (!$user) { return [false, 'User not found']; }
    // Re-authenticate
    if (!password_verify($currentPassword, $user['password_hash'] ?? '')) {
        return [false, 'Invalid current password'];
    }
    // Policy checks
    $valid = validate_password($newPassword);
    if (!$valid) { return [false, 'New password does not meet policy']; }
    if (password_too_young($user['password_changed_at'] ?? null)) {
        return [false, 'Password can only be changed once per day'];
    }
    $history = get_password_history_hashes($userId);
    foreach ($history as $oldHash) {
        if (password_verify($newPassword, $oldHash)) {
            return [false, 'Password reuse is not allowed'];
        }
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $ok = sb_update('users', ['id' => $userId], [
        'password_hash' => $hash,
        'password_changed_at' => now_iso(),
        'updated_at' => now_iso(),
    ]);
    if ($ok) {
        sb_insert('password_history', [
            'user_id' => $userId,
            'password_hash' => $hash,
            'changed_at' => now_iso(),
        ]);
        return [true, null];
    }
    return [false, 'Failed to update password'];
}

function list_users(): array {
    return sb_get('users', [], 100);
}

function update_user_role(string $userId, string $role): bool {
    return sb_update('users', ['id' => $userId], ['role' => $role, 'updated_at' => now_iso()]);
}

function set_user_disabled(string $userId, bool $disabled): bool {
    return sb_update('users', ['id' => $userId], ['is_disabled' => $disabled, 'updated_at' => now_iso()]);
}



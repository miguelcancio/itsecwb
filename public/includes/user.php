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

function record_login(string $userId, string $ip): bool {
    $result = sb_update('users', ['id' => $userId], [
        'last_login_at' => now_iso(),
        'last_login_ip' => $ip
    ]);
    
    // Log the result for debugging
    log_event('login_update', 'Database update result', [
        'user_id' => $userId,
        'ip' => $ip,
        'success' => $result,
        'timestamp' => now_iso()
    ]);
    
    return $result;
}

function create_user(string $email, string $password, string $role = 'customer'): array {
    // Validate email
    $emailValid = validate_email($email);
    if (!$emailValid) {
        return ['success' => false, 'error_code' => 'INVALID_EMAIL', 'message' => 'Please enter a valid email address (e.g., user@example.com)'];
    }
    
    // Validate password
    $passValid = validate_password($password);
    if (!$passValid) {
        return ['success' => false, 'error_code' => 'WEAK_PASSWORD', 'message' => 'Password must be at least 12 characters with uppercase, lowercase, number, and special character'];
    }
    
    // Check if user already exists
    $userExisting = get_user_by_email($emailValid);
    if ($userExisting) {
        log_event('auth_fail', 'Registration email already exists', ['email' => $emailValid]);
        return ['success' => false, 'error_code' => 'EMAIL_EXISTS', 'message' => 'An account with this email address already exists. Please use a different email or try logging in.'];
    }
    
    // Create user
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
        
        log_event('user_created', 'User created successfully', [
            'user_id' => $user['id'],
            'email' => $emailValid,
            'role' => $role
        ]);
        
        return ['success' => true, 'user' => $user];
    }
    
    // Database insertion failed
    log_event('system_error', 'Failed to create user in database', ['email' => $emailValid]);
    return ['success' => false, 'error_code' => 'DATABASE_ERROR', 'message' => 'Unable to create account at this time. Please try again later.'];
}

function create_user_with_security_question(string $email, string $password, string $role, string $securityQuestion, string $securityAnswer): array {
    // Validate email
    $emailValid = validate_email($email);
    if (!$emailValid) {
        return ['success' => false, 'error_code' => 'INVALID_EMAIL', 'message' => 'Please enter a valid email address (e.g., user@example.com)'];
    }
    
    // Validate password
    $passValid = validate_password($password);
    if (!$passValid) {
        return ['success' => false, 'error_code' => 'WEAK_PASSWORD', 'message' => 'Password must be at least 12 characters with uppercase, lowercase, number, and special character'];
    }
    
    // Validate security question
    if (empty($securityQuestion)) {
        return ['success' => false, 'error_code' => 'EMPTY_SECURITY_QUESTION', 'message' => 'Security question is required'];
    }
    
    $validatedQuestion = validate_security_question_text($securityQuestion);
    if (!$validatedQuestion) {
        return ['success' => false, 'error_code' => 'WEAK_SECURITY_QUESTION', 'message' => 'Security question must be at least 10 characters long'];
    }
    
    // Validate security answer
    if (empty($securityAnswer)) {
        return ['success' => false, 'error_code' => 'EMPTY_SECURITY_ANSWER', 'message' => 'Security answer is required'];
    }
    
    $validatedAnswer = validate_security_answer($securityAnswer);
    if (!$validatedAnswer) {
        return ['success' => false, 'error_code' => 'WEAK_SECURITY_ANSWER', 'message' => 'Security answer is too common. Please choose a more unique answer'];
    }
    
    // Check if user already exists
    $userExisting = get_user_by_email($emailValid);
    if ($userExisting) {
        log_event('auth_fail', 'Registration email already exists', ['email' => $emailValid]);
        return ['success' => false, 'error_code' => 'EMAIL_EXISTS', 'message' => 'An account with this email address already exists. Please use a different email or try logging in.'];
    }
    
    // Create user
    $passwordHash = password_hash($passValid, PASSWORD_DEFAULT);
    $securityAnswerHash = password_hash($validatedAnswer, PASSWORD_DEFAULT);
    
    $user = sb_insert('users', [
        'email' => $emailValid,
        'password_hash' => $passwordHash,
        'role' => $role,
        'security_question' => $validatedQuestion,
        'security_answer_hash' => $securityAnswerHash,
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
        
        log_event('user_created', 'User created with security question', [
            'user_id' => $user['id'],
            'email' => $emailValid,
            'role' => $role
        ]);
        
        return ['success' => true, 'user' => $user];
    }
    
    // Database insertion failed
    log_event('system_error', 'Failed to create user in database', ['email' => $emailValid]);
    return ['success' => false, 'error_code' => 'DATABASE_ERROR', 'message' => 'Unable to create account at this time. Please try again later.'];
}

function verify_security_answer(string $userId, string $answer): bool {
    $user = get_user_by_id($userId);
    if (!$user || empty($user['security_answer_hash'])) {
        return false;
    }
    
    $isValid = password_verify($answer, $user['security_answer_hash']);
    
    if ($isValid) {
        log_event('security_answer_verified', 'Security answer verified successfully', ['user_id' => $userId]);
    } else {
        log_event('security_answer_failed', 'Security answer verification failed', ['user_id' => $userId]);
    }
    
    return $isValid;
}

function get_user_security_question(string $email): ?string {
    $user = get_user_by_email($email);
    if (!$user || empty($user['security_question'])) {
        return null;
    }
    
    return $user['security_question'];
}

function has_security_question(string $userId): bool {
    $user = get_user_by_id($userId);
    if (!$user) {
        return false;
    }
    
    return !empty($user['security_question']) && !empty($user['security_answer_hash']);
}

function get_user_security_question_by_id(string $userId): ?string {
    $user = get_user_by_id($userId);
    if (!$user || empty($user['security_question'])) {
        return null;
    }
    
    return $user['security_question'];
}

function update_user_security_question(string $userId, string $newQuestion, string $newAnswer): bool {
    $user = get_user_by_id($userId);
    if (!$user) {
        return false;
    }
    
    // Validate input lengths
    if (mb_strlen($newQuestion) < 10) {
        return false;
    }
    
    // Validate security answer using the new validation function
    $validatedAnswer = validate_security_answer($newAnswer);
    if (!$validatedAnswer) {
        log_event('validation_fail', 'Security answer validation failed during update', ['user_id' => $userId]);
        return false;
    }
    
    // Hash the new security answer
    $newAnswerHash = password_hash($validatedAnswer, PASSWORD_DEFAULT);
    
    // Update the user's security question and answer
    $success = sb_update('users', ['id' => $userId], [
        'security_question' => $newQuestion,
        'security_answer_hash' => $newAnswerHash,
        'updated_at' => now_iso()
    ]);
    
    if ($success) {
        log_event('security_question_updated', 'Security question updated', [
            'user_id' => $userId,
            'question_length' => mb_strlen($newQuestion)
        ]);
    }
    
    return $success;
}



function reset_password_with_question(string $userId, string $newPassword): array {
    $user = get_user_by_id($userId);
    if (!$user) {
        return [false, 'User not found'];
    }
    
    // Validate new password
    $validPassword = validate_password($newPassword);
    if (!$validPassword) {
        return [false, 'New password does not meet requirements'];
    }
    
    // Check password age policy
    if (password_too_young($user['password_changed_at'] ?? null)) {
        return [false, 'Password can only be changed once per day'];
    }
    
    // Check password history
    $history = get_password_history_hashes($userId);
    foreach ($history as $oldHash) {
        if (password_verify($newPassword, $oldHash)) {
            return [false, 'Password reuse is not allowed'];
        }
    }
    
    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update user password
    $updateSuccess = sb_update('users', ['id' => $userId], [
        'password_hash' => $newPasswordHash,
        'password_changed_at' => now_iso(),
        'updated_at' => now_iso(),
        'failed_attempts' => 0, // Reset failed attempts
        'locked_until' => null  // Unlock account if locked
    ]);
    
    if ($updateSuccess) {
        // Add to password history
        sb_insert('password_history', [
            'user_id' => $userId,
            'password_hash' => $newPasswordHash,
            'changed_at' => now_iso(),
        ]);
        

        
        log_event('password_reset_completed', 'Password reset completed via security question', [
            'user_id' => $userId
        ]);
        
        return [true, null];
    }
    
    return [false, 'Failed to update password'];
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
    // Validate role
    $validRoles = ['customer', 'manager', 'admin'];
    if (!in_array($role, $validRoles, true)) {
        return false;
    }
    
    return sb_update('users', ['id' => $userId], ['role' => $role, 'updated_at' => now_iso()]);
}

function set_user_disabled(string $userId, bool $disabled): bool {
    return sb_update('users', ['id' => $userId], ['is_disabled' => $disabled, 'updated_at' => now_iso()]);
}

function get_users_without_security_questions(): array {
    $allUsers = list_users();
    $usersWithoutQuestions = [];
    
    foreach ($allUsers as $user) {
        if (!has_security_question($user['id'])) {
            $usersWithoutQuestions[] = $user;
        }
    }
    
    return $usersWithoutQuestions;
}



function get_security_question_statistics(): array {
    $allUsers = list_users();
    $totalUsers = count($allUsers);
    $usersWithQuestions = 0;
    $usersWithoutQuestions = 0;
    
    foreach ($allUsers as $user) {
        if (has_security_question($user['id'])) {
            $usersWithQuestions++;
        } else {
            $usersWithoutQuestions++;
        }
    }
    
    $completionPercentage = $totalUsers > 0 ? round(($usersWithQuestions / $totalUsers) * 100, 1) : 0;
    
    return [
        'total_users' => $totalUsers,
        'users_with_questions' => $usersWithQuestions,
        'users_without_questions' => $usersWithoutQuestions,
        'completion_percentage' => $completionPercentage,
        'is_complete' => $usersWithoutQuestions === 0
    ];
}

// Manager functions for managing customers (Role B users)
function get_users_by_role(string $role): array {
    return sb_get('users', ['role' => $role], 1000, 0, '*');
}

function update_user_status(string $userId, bool $isActive): bool {
    $data = [
        'is_disabled' => !$isActive,
        'updated_at' => now_iso()
    ];
    
    if ($isActive) {
        // If activating, also reset any lock status
        $data['locked_until'] = null;
        $data['failed_attempts'] = 0;
    }
    
    return sb_update('users', ['id' => $userId], $data);
}





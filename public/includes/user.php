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

function create_user_with_security_question(string $email, string $password, string $role, string $securityQuestion, string $securityAnswer): ?array {
    $emailValid = validate_email($email);
    $passValid = validate_password($password);
    
    // Validate security question and answer
    if (!$emailValid || !$passValid || !$securityQuestion || !$securityAnswer) {
        return null;
    }
    
    // Validate security question and answer using new validation functions
    $validatedQuestion = validate_security_question_text($securityQuestion);
    $validatedAnswer = validate_security_answer($securityAnswer);
    
    if (!$validatedQuestion || !$validatedAnswer) {
        log_event('validation_fail', 'Security question or answer validation failed', [
            'question_valid' => $validatedQuestion !== null,
            'answer_valid' => $validatedAnswer !== null
        ]);
        return null;
    }
    
    $userExisting = get_user_by_email($emailValid);
    if ($userExisting) {
        log_event('auth_fail', 'Registration email already exists', ['email' => $emailValid]);
        return null;
    }
    
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
    }
    
    return $user ?: null;
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

function get_pending_reset_request(string $userId): ?array {
    $rows = sb_get('password_reset_requests', [
        'user_id' => $userId,
        'status' => 'pending'
    ], 1);
    
    return $rows[0] ?? null;
}

function request_password_reset(string $userId): ?array {
    // Check if user already has a pending request
    $existingRequest = get_pending_reset_request($userId);
    if ($existingRequest) {
        return null; // Already has pending request
    }
    
    // Generate secure reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = gmdate('c', time() + (24 * 60 * 60)); // 24 hours from now
    
    $request = sb_insert('password_reset_requests', [
        'user_id' => $userId,
        'status' => 'pending',
        'reset_token' => $resetToken,
        'expires_at' => $expiresAt,
        'created_at' => now_iso()
    ]);
    
    if ($request) {
        log_event('password_reset_requested', 'Password reset requested', [
            'user_id' => $userId,
            'request_id' => $request['id'] ?? null
        ]);
    }
    
    return $request;
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
        
        // Update any pending reset requests to completed
        sb_update('password_reset_requests', 
            ['user_id' => $userId, 'status' => 'pending'], 
            ['status' => 'completed', 'admin_notes' => 'Completed via security question']
        );
        
        log_event('password_reset_completed', 'Password reset completed via security question', [
            'user_id' => $userId
        ]);
        
        return [true, null];
    }
    
    return [false, 'Failed to update password'];
}

function get_all_reset_requests(): array {
    $rows = sb_get('password_reset_requests', [], 1000, 0, 'created_at DESC');
    return $rows ?? [];
}

function approve_password_reset(string $requestId, string $adminNotes = ''): bool {
    $request = sb_get('password_reset_requests', ['id' => $requestId], 1);
    if (empty($request)) {
        return false;
    }
    
    $request = $request[0];
    if ($request['status'] !== 'pending') {
        return false;
    }
    
    // Check if request is expired
    if (!empty($request['expires_at']) && strtotime($request['expires_at']) < time()) {
        return false;
    }
    
    $updateData = [
        'status' => 'approved',
        'updated_at' => now_iso()
    ];
    
    if (!empty($adminNotes)) {
        $updateData['admin_notes'] = $adminNotes;
    }
    
    $success = sb_update('password_reset_requests', ['id' => $requestId], $updateData);
    
    if ($success) {
        log_event('password_reset_approved', 'Password reset request approved by admin', [
            'request_id' => $requestId,
            'user_id' => $request['user_id'],
            'admin_notes' => $adminNotes
        ]);
    }
    
    return $success;
}

function deny_password_reset(string $requestId, string $adminNotes): bool {
    if (empty($adminNotes)) {
        return false; // Admin notes are required for denial
    }
    
    $request = sb_get('password_reset_requests', ['id' => $requestId], 1);
    if (empty($request)) {
        return false;
    }
    
    $request = $request[0];
    if ($request['status'] !== 'pending') {
        return false;
    }
    
    $success = sb_update('password_reset_requests', ['id' => $requestId], [
        'status' => 'denied',
        'admin_notes' => $adminNotes,
        'updated_at' => now_iso()
    ]);
    
    if ($success) {
        log_event('password_reset_denied', 'Password reset request denied by admin', [
            'request_id' => $requestId,
            'user_id' => $request['user_id'],
            'admin_notes' => $adminNotes
        ]);
    }
    
    return $success;
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

function bulk_set_security_questions(array $userIds, string $securityQuestion, string $securityAnswer): array {
    if (empty($userIds) || empty($securityQuestion) || empty($securityAnswer)) {
        return ['success' => false, 'message' => 'Invalid input parameters'];
    }
    
    // Validate security question and answer lengths
    if (mb_strlen($securityQuestion) < 10) {
        return ['success' => false, 'message' => 'Security question must be at least 10 characters'];
    }
    
    // Validate security answer using the new validation function
    $validatedAnswer = validate_security_answer($securityAnswer);
    if (!$validatedAnswer) {
        return ['success' => false, 'message' => 'Security answer does not meet security requirements'];
    }
    
    $securityAnswerHash = password_hash($validatedAnswer, PASSWORD_DEFAULT);
    $successCount = 0;
    $failedCount = 0;
    $failedUsers = [];
    
    foreach ($userIds as $userId) {
        $user = get_user_by_id($userId);
        if ($user && !has_security_question($userId)) {
            $success = sb_update('users', ['id' => $userId], [
                'security_question' => $securityQuestion,
                'security_answer_hash' => $securityAnswerHash,
                'updated_at' => now_iso()
            ]);
            
            if ($success) {
                $successCount++;
                log_event('bulk_security_question_set', 'Security question set via bulk operation', [
                    'user_id' => $userId,
                    'admin_operation' => true
                ]);
            } else {
                $failedCount++;
                $failedUsers[] = $user['email'];
            }
        } else {
            $failedCount++;
            $failedUsers[] = $user['email'] ?? 'Unknown';
        }
    }
    
    $result = [
        'success' => $successCount > 0,
        'success_count' => $successCount,
        'failed_count' => $failedCount,
        'failed_users' => $failedUsers,
        'message' => "Successfully set security questions for {$successCount} users"
    ];
    
    if ($failedCount > 0) {
        $result['message'] .= ", {$failedCount} users failed";
    }
    
    return $result;
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



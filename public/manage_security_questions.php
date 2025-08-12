<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/auth_check.php';

$message = null;
$error = null;
$currentQuestion = '';
$hasQuestions = false;

// Get current user's security question status
$userId = $_SESSION['user']['id'];
$hasQuestions = has_security_question($userId);
if ($hasQuestions) {
    $currentQuestion = get_user_security_question_by_id($userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_security_question') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newQuestion = trim($_POST['new_question'] ?? '');
        $newAnswer = trim($_POST['new_answer'] ?? '');
        
        // Validate current password first
        $user = get_user_by_id($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'] ?? '')) {
            $error = 'Current password is incorrect.';
        } elseif (mb_strlen($newQuestion) < 10) {
            $error = 'Security question must be at least 10 characters long.';
        } elseif (mb_strlen($newAnswer) < 3) {
            $error = 'Security answer must be at least 3 characters long.';
        } else {
            // Update security question
            if (update_user_security_question($userId, $newQuestion, $newAnswer)) {
                $message = 'Security question updated successfully!';
                $currentQuestion = $newQuestion;
                $hasQuestions = true;
                
                log_event('security_question_updated', 'User updated security question', [
                    'user_id' => $userId
                ]);
            } else {
                $error = 'Failed to update security question. Please try again.';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Manage Security Questions</h2>
    <p>Security questions help protect your account and allow you to reset your password if needed.</p>
    
    <?php if ($message): ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Current Status</h3>
    <div class="status-section">
        <?php if ($hasQuestions): ?>
            <div class="status-indicator status-set">
                <span class="status-icon">✓</span>
                <div class="status-content">
                    <strong>Security Questions Set</strong>
                    <p>Your account is protected with security questions.</p>
                    <div class="current-question">
                        <label>Current Question:</label>
                        <p><?php echo htmlspecialchars($currentQuestion); ?></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="status-indicator status-not-set">
                <span class="status-icon">⚠</span>
                <div class="status-content">
                    <strong>Security Questions Not Set</strong>
                    <p>Your account is not protected with security questions. Please set them below.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3><?php echo $hasQuestions ? 'Update Security Questions' : 'Set Security Questions'; ?></h3>
    <p>Choose a question that only you know the answer to. This will be used for password recovery.</p>
    
    <form method="post" novalidate class="security-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="update_security_question">
        
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required 
                   placeholder="Enter your current password to verify your identity">
            <small>We need to verify your identity before making changes.</small>
        </div>
        
        <div class="form-group">
            <label for="new_question">Security Question</label>
            <input type="text" id="new_question" name="new_question" required maxlength="255" 
                   placeholder="e.g., What was your first pet's name?" 
                   value="<?php echo htmlspecialchars($_POST['new_question'] ?? ''); ?>">
            <small class="question-help">
                Choose a question that only you know the answer to. Minimum 10 characters.
            </small>
        </div>
        
        <div class="form-group">
            <label for="new_answer">Security Answer</label>
            <input type="text" id="new_answer" name="new_answer" required maxlength="255" 
                   placeholder="Your answer to the security question" 
                   value="<?php echo htmlspecialchars($_POST['new_answer'] ?? ''); ?>">
            <small class="answer-help">
                This answer will be used to verify your identity for password recovery. Minimum 3 characters.
            </small>
        </div>
        
        <button class="btn btn-large" type="submit">
            <?php echo $hasQuestions ? 'Update Security Questions' : 'Set Security Questions'; ?>
        </button>
    </form>
</div>

<div class="card">
    <h3>Tips for Good Security Questions</h3>
    <div class="tips-grid">
        <div class="tip-item">
            <h4>✅ Good Examples</h4>
            <ul>
                <li>"What was the name of your first pet?"</li>
                <li>"In which city were you born?"</li>
                <li>"What was your mother's maiden name?"</li>
                <li>"What was your favorite book as a child?"</li>
            </ul>
        </div>
        <div class="tip-item">
            <h4>❌ Avoid These</h4>
            <ul>
                <li>"What is your favorite color?" (too common)</li>
                <li>"What is 2+2?" (too simple)</li>
                <li>"What is your name?" (too obvious)</li>
                <li>"What is your password?" (security risk)</li>
            </ul>
        </div>
    </div>
</div>

<div class="navigation-links">
    <a href="/<?php echo htmlspecialchars($_SESSION['user']['role']); ?>/change_password.php" class="btn secondary">
        Change Password
    </a>
    <a href="/<?php echo htmlspecialchars($_SESSION['user']['role']); ?>/dashboard.php" class="btn">
        Back to Dashboard
    </a>
</div>

<style>
.status-section {
    margin: 20px 0;
}

.status-indicator {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    border-radius: 12px;
    border: 2px solid;
}

.status-set {
    background: #f0fdf4;
    border-color: #22c55e;
    color: #166534;
}

.status-not-set {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}

.status-icon {
    font-size: 24px;
    font-weight: bold;
    flex-shrink: 0;
}

.status-content strong {
    display: block;
    font-size: 18px;
    margin-bottom: 8px;
}

.status-content p {
    margin: 0 0 16px 0;
    opacity: 0.9;
}

.current-question {
    background: rgba(255, 255, 255, 0.7);
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.current-question label {
    font-weight: 600;
    display: block;
    margin-bottom: 4px;
}

.current-question p {
    margin: 0;
    font-style: italic;
}

.security-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group small {
    display: block;
    margin-top: 6px;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.4;
}

.question-help {
    color: #7c3aed;
}

.answer-help {
    color: #dc2626;
}

.btn-large {
    width: 100%;
    padding: 14px 20px;
    font-size: 16px;
    font-weight: 600;
    margin-top: 10px;
}

.tips-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 16px;
}

.tip-item {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.tip-item h4 {
    margin: 0 0 12px 0;
    color: #374151;
}

.tip-item ul {
    margin: 0;
    padding-left: 20px;
}

.tip-item li {
    margin-bottom: 6px;
    color: #6b7280;
}

.navigation-links {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-top: 32px;
}

.success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

@media (max-width: 768px) {
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .navigation-links {
        flex-direction: column;
        align-items: center;
    }
    
    .status-indicator {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?> 
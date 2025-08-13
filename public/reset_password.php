<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/user.php';

$message = null;
$error = null;
$step = 1;
$userEmail = '';
$userId = null;
$securityQuestion = '';
$hasSecurityQuestions = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'step1_email') {
        $email = trim($_POST['email'] ?? '');
        if (validate_email($email)) {
            $user = get_user_by_email($email);
            if ($user) {
                // Check if user has security questions set
                $hasSecurityQuestions = has_security_question($user['id']);
                if (!$hasSecurityQuestions) {
                    $error = 'This account does not have security questions set up. Please contact an administrator or log in to set up security questions.';
                } else {
                    $userEmail = $email;
                    $userId = $user['id'];
                    $securityQuestion = get_user_security_question($email);
                    $step = 2;
                    
                    log_event('password_reset_initiated', 'Password reset initiated', [
                        'email' => $email,
                        'user_id' => $user['id']
                    ]);
                }
            } else {
                $error = 'No account found with that email address.';
            }
        } else {
            $error = 'Please enter a valid email address.';
        }
    } elseif ($action === 'step2_answer') {
        $answer = trim($_POST['security_answer'] ?? '');
        $userId = $_POST['user_id'] ?? '';
        
        if ($answer && $userId) {
            if (verify_security_answer($userId, $answer)) {
                $step = 3;
                log_event('security_answer_verified', 'Security answer verified for password reset', [
                    'user_id' => $userId
                ]);
            } else {
                $error = 'Incorrect security answer. Please try again.';
                $step = 2;
                // Re-populate the form data
                $user = get_user_by_id($userId);
                $userEmail = $user['email'];
                $securityQuestion = get_user_security_question($userEmail);
            }
        } else {
            $error = 'Please provide your security answer.';
            $step = 2;
        }
    } elseif ($action === 'step3_new_password') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $userId = $_POST['user_id'] ?? '';
        
        if ($newPassword && $confirmPassword && $userId) {
            if ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match.';
                $step = 3;
            } else {
                [$success, $message] = reset_password_with_question($userId, $newPassword);
                if ($success) {
                    $step = 4; // Success step
                    log_event('password_reset_completed', 'Password reset completed successfully', [
                        'user_id' => $userId
                    ]);
                } else {
                    $error = $message;
                    $step = 3;
                }
            }
        } else {
            $error = 'Please provide both passwords.';
            $step = 3;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Reset Your Password</h2>
    <p>Use your security question to reset your password.</p>
    
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
</div>

<?php if ($step === 1): ?>
<!-- Step 1: Email Input -->
<div class="card">
    <h3>Step 1: Enter Your Email</h3>
    <p>We'll send you to the next step if your account has security questions set up.</p>
    
    <form method="post" novalidate class="reset-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="step1_email">
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required 
                   placeholder="Enter your registered email address" 
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        
        <button class="btn btn-large" type="submit">Continue</button>
    </form>
    
    <div class="form-links">
        <a href="/index.php">Back to Login</a>
    </div>
</div>

<?php elseif ($step === 2): ?>
<!-- Step 2: Security Question -->
<div class="card">
    <h3>Step 2: Answer Your Security Question</h3>
    <p>Please answer the security question for: <strong><?php echo htmlspecialchars($userEmail); ?></strong></p>
    
    <div class="security-question-display">
        <label>Security Question:</label>
        <p class="question-text"><?php echo htmlspecialchars($securityQuestion); ?></p>
    </div>
    
    <form method="post" novalidate class="reset-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="step2_answer">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
        
        <div class="form-group">
            <label for="security_answer">Your Answer</label>
            <input type="text" id="security_answer" name="security_answer" required 
                   placeholder="Enter your answer to the security question">
        </div>
        
        <button class="btn btn-large" type="submit">Verify Answer</button>
    </form>
    
    <div class="form-links">
        <a href="/reset_password.php">Start Over</a>
    </div>
</div>

<?php elseif ($step === 3): ?>
<!-- Step 3: New Password -->
<div class="card">
    <h3>Step 3: Set New Password</h3>
    <p>Your security answer was correct. Now set your new password.</p>
    
    <form method="post" novalidate class="reset-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="step3_new_password">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
        
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required 
                   minlength="12" maxlength="128" 
                   placeholder="Enter your new password">
            <small>Password must be at least 12 characters long and meet complexity requirements.</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required 
                   minlength="12" maxlength="128" 
                   placeholder="Confirm your new password">
        </div>
        
        <button class="btn btn-large" type="submit">Reset Password</button>
    </form>
    
    <div class="form-links">
        <a href="/reset_password.php">Start Over</a>
    </div>
</div>

<?php elseif ($step === 4): ?>
<!-- Step 4: Success -->
<div class="card success-card">
    <div class="success-icon">✓</div>
    <h3>Password Reset Successful!</h3>
    <p>Your password has been reset successfully. You can now log in with your new password.</p>
    
    <div class="success-actions">
        <a href="/index.php" class="btn btn-large">Go to Login</a>
    </div>
</div>
<?php endif; ?>

<?php if ($step < 4): ?>
<div class="card info-card">
    <h3>Need Help?</h3>
    <div class="help-content">
        <div class="help-item">
            <span class="help-icon">❓</span>
            <div>
                <strong>Don't have security questions?</strong>
                <p>Contact an administrator or log in to set up security questions.</p>
            </div>
        </div>
        <div class="help-item">
            <span class="help-icon">🔒</span>
            <div>
                <strong>Remember your password?</strong>
                <p><a href="/index.php">Go back to login</a> instead.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.reset-form {
    max-width: 500px;
    margin: 0 auto;
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
}

.btn-large {
    width: 100%;
    padding: 14px 20px;
    font-size: 16px;
    font-weight: 600;
    margin-top: 10px;
}

.form-links {
    text-align: center;
    margin-top: 20px;
}

.form-links a {
    color: #2563eb;
    text-decoration: none;
    font-size: 14px;
}

.form-links a:hover {
    text-decoration: underline;
}

.security-question-display {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    margin-bottom: 24px;
}

.security-question-display label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.question-text {
    margin: 0;
    font-size: 16px;
    color: #374151;
    font-style: italic;
    padding: 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.success-card {
    text-align: center;
    background: #f0fdf4;
    border: 2px solid #22c55e;
}

.success-icon {
    font-size: 48px;
    color: #22c55e;
    margin-bottom: 16px;
}

.success-actions {
    margin-top: 24px;
}

.info-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
}

.help-content {
    margin-top: 16px;
}

.help-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 20px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.help-item:last-child {
    margin-bottom: 0;
}

.help-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.help-item strong {
    display: block;
    margin-bottom: 4px;
    color: #374151;
}

.help-item p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.help-item a {
    color: #2563eb;
    text-decoration: none;
}

.help-item a:hover {
    text-decoration: underline;
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
    .help-item {
        flex-direction: column;
        text-align: center;
    }
    
    .help-icon {
        margin-bottom: 8px;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?> 
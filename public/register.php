<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/user.php';

ensure_session_started();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');
    $email = validate_email($_POST['email'] ?? '');
    $password = validate_password($_POST['password'] ?? '');
    $securityQuestion = trim($_POST['security_question'] ?? '');
    $securityAnswer = trim($_POST['security_answer'] ?? '');
    
    if (!$email || !$password || !$securityQuestion || !$securityAnswer) {
        $error = 'Please provide a valid email, strong password, and security question with answer.';
    } elseif (mb_strlen($securityQuestion) < 10) {
        $error = 'Security question must be at least 10 characters long.';
    } else {
        $user = create_user_with_security_question($email, $password, 'customer', $securityQuestion, $securityAnswer);
        if ($user) {
            log_event('registration', 'New customer registered with security question', ['user_id' => $user['id']]);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            redirect('/customer/dashboard.php');
        } else {
            $error = 'Registration failed. Please check your information and try again.';
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
  <div class="card">
    <h2>Create Your Account</h2>
    <p>Please fill in the form below to create your account. Security questions are required for password recovery.</p>
    
    <?php if (!empty($error)): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    
    <form method="post" novalidate class="registration-form">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required maxlength="254" 
               placeholder="Enter your email address" 
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="12" maxlength="128" 
               autocomplete="new-password" placeholder="Create a strong password">
        <small class="password-help">
          Password must be at least 12 characters with uppercase, lowercase, number, and special character.
        </small>
      </div>
      
      <div class="form-group">
        <label for="security_question">Security Question</label>
        <input type="text" id="security_question" name="security_question" required maxlength="255" 
               placeholder="e.g., What was your first pet's name?" 
               value="<?php echo htmlspecialchars($_POST['security_question'] ?? ''); ?>">
        <small class="question-help">
          Choose a question that only you know the answer to. Minimum 10 characters.
        </small>
      </div>
      
      <div class="form-group">
        <label for="security_answer">Security Answer</label>
        <input type="text" id="security_answer" name="security_answer" required maxlength="255" 
               placeholder="Your answer to the security question" 
               value="<?php echo htmlspecialchars($_POST['security_answer'] ?? ''); ?>">
        <small class="answer-help">
          This answer will be used to verify your identity for password recovery. Must be meaningful and not easily guessable.
        </small>
      </div>
      
      <button class="btn btn-large" type="submit">Create Account</button>
    </form>
    
    <div class="login-link">
      <p>Already have an account? <a href="/login.php">Sign in here</a></p>
    </div>
  </div>

<style>
.registration-form {
  max-width: 500px;
  margin: 0 auto;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
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

.password-help {
  color: #059669;
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

.login-link {
  text-align: center;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.login-link a {
  color: #2563eb;
  text-decoration: none;
  font-weight: 500;
}

.login-link a:hover {
  text-decoration: underline;
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

@media (max-width: 640px) {
  .form-group input {
    font-size: 16px; /* Prevents zoom on iOS */
  }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

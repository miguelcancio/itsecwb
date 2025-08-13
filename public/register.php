<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/user.php';

ensure_session_started();

$error = null;
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');
    
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $securityQuestion = trim($_POST['security_question'] ?? '');
    $securityAnswer = trim($_POST['security_answer'] ?? '');
    
    // Attempt to create user
    $result = create_user_with_security_question($email, $password, 'customer', $securityQuestion, $securityAnswer);
    
    if ($result['success']) {
        $user = $result['user'];
        log_event('registration', 'New customer registered with security question', ['user_id' => $user['id']]);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        redirect('/customer/dashboard.php');
    } else {
        // Handle specific error types
        switch ($result['error_code']) {
            case 'INVALID_EMAIL':
                $fieldErrors['email'] = $result['message'];
                break;
            case 'WEAK_PASSWORD':
                $fieldErrors['password'] = $result['message'];
                break;
            case 'EMPTY_SECURITY_QUESTION':
            case 'WEAK_SECURITY_QUESTION':
                $fieldErrors['security_question'] = $result['message'];
                break;
            case 'EMPTY_SECURITY_ANSWER':
            case 'WEAK_SECURITY_ANSWER':
                $fieldErrors['security_answer'] = $result['message'];
                break;
            case 'EMAIL_EXISTS':
                $fieldErrors['email'] = $result['message'];
                break;
            case 'DATABASE_ERROR':
                $error = $result['message'];
                break;
            default:
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
      
      <div class="form-group <?php echo isset($fieldErrors['email']) ? 'has-error' : ''; ?>">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required maxlength="254" 
               placeholder="Enter your email address" 
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <?php if (isset($fieldErrors['email'])): ?>
          <span class="field-error"><?php echo htmlspecialchars($fieldErrors['email']); ?></span>
        <?php endif; ?>
      </div>
      
      <div class="form-group <?php echo isset($fieldErrors['password']) ? 'has-error' : ''; ?> password-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="12" maxlength="128" 
               autocomplete="new-password" placeholder="Create a strong password">
        <button class="password-toggle" type="button" aria-label="Toggle password" onclick="(function(btn){var i=document.getElementById('password'); if(i.type==='password'){ i.type='text'; btn.textContent='🙈'; } else { i.type='password'; btn.textContent='👁'; }})(this)">👁</button>
        <?php if (isset($fieldErrors['password'])): ?>
          <span class="field-error"><?php echo htmlspecialchars($fieldErrors['password']); ?></span>
        <?php endif; ?>
        <small class="password-help">
          Password must be at least 12 characters with uppercase, lowercase, number, and special character.
        </small>
      </div>
      
      <div class="form-group <?php echo isset($fieldErrors['security_question']) ? 'has-error' : ''; ?>">
        <label for="security_question">Security Question</label>
        <input type="text" id="security_question" name="security_question" required maxlength="255" 
               placeholder="e.g., What was your first pet's name?" 
               value="<?php echo htmlspecialchars($_POST['security_question'] ?? ''); ?>">
        <?php if (isset($fieldErrors['security_question'])): ?>
          <span class="field-error"><?php echo htmlspecialchars($fieldErrors['security_question']); ?></span>
        <?php endif; ?>
        <small class="question-help">
          Choose a question that only you know the answer to. Minimum 10 characters.
        </small>
      </div>
      
      <div class="form-group <?php echo isset($fieldErrors['security_answer']) ? 'has-error' : ''; ?>">
        <label for="security_answer">Security Answer</label>
        <input type="text" id="security_answer" name="security_answer" required maxlength="128" 
               placeholder="Your answer to the security question" 
               value="<?php echo htmlspecialchars($_POST['security_answer'] ?? ''); ?>">
        <?php if (isset($fieldErrors['security_answer'])): ?>
          <span class="field-error"><?php echo htmlspecialchars($fieldErrors['security_answer']); ?></span>
        <?php endif; ?>
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

.form-group.has-error input {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.field-error {
  display: block;
  color: #dc2626;
  font-size: 13px;
  margin-top: 4px;
  font-weight: 500;
}

.form-group.has-error label {
  color: #dc2626;
}

.form-group.has-error small {
  color: #dc2626;
}

.form-group.has-error input:focus {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

/* Animation for error states */
.form-group.has-error {
  animation: shake 0.5s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

/* Success state styling */
.form-group.success input {
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.form-group.success label {
  color: #059669;
}

@media (max-width: 640px) {
  .form-group input {
    font-size: 16px; /* Prevents zoom on iOS */
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.registration-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const questionInput = document.getElementById('security_question');
    const answerInput = document.getElementById('security_answer');
    
    // Real-time validation feedback
    function validateField(input, validationRules) {
        const formGroup = input.closest('.form-group');
        const value = input.value.trim();
        
        // Remove existing error states
        formGroup.classList.remove('has-error', 'success');
        const existingError = formGroup.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Validate based on field type
        let isValid = true;
        let errorMessage = '';
        
        if (input === emailInput) {
            if (!value) {
                isValid = false;
                errorMessage = 'Email address is required';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        } else if (input === passwordInput) {
            if (!value) {
                isValid = false;
                errorMessage = 'Password is required';
            } else if (value.length < 12) {
                isValid = false;
                errorMessage = 'Password must be at least 12 characters long';
            } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])/.test(value)) {
                isValid = false;
                errorMessage = 'Password must include uppercase, lowercase, number, and special character';
            }
        } else if (input === questionInput) {
            if (!value) {
                isValid = false;
                errorMessage = 'Security question is required';
            } else if (value.length < 10) {
                isValid = false;
                errorMessage = 'Security question must be at least 10 characters long';
            }
        } else if (input === answerInput) {
            if (!value) {
                isValid = false;
                errorMessage = 'Security answer is required';
            } else if (value.length <= 2) {
                isValid = false;
                errorMessage = 'Security answer must be at least 3 characters long';
            }
        }
        
        // Apply validation result
        if (!isValid) {
            formGroup.classList.add('has-error');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error';
            errorSpan.textContent = errorMessage;
            input.parentNode.appendChild(errorSpan);
        } else {
            formGroup.classList.add('success');
        }
    }
    
    // Add event listeners for real-time validation
    [emailInput, passwordInput, questionInput, answerInput].forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => {
            // Remove error state on input
            const formGroup = input.closest('.form-group');
            formGroup.classList.remove('has-error');
            const existingError = formGroup.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
        });
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // Validate all fields
        [emailInput, passwordInput, questionInput, answerInput].forEach(input => {
            validateField(input);
            if (input.closest('.form-group').classList.contains('has-error')) {
                hasErrors = true;
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            // Scroll to first error
            const firstError = document.querySelector('.has-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

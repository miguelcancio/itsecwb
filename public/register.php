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
    if (!$email || !$password) {
        $error = 'Please provide a valid email and a strong password.';
    } else {
        $user = create_user($email, $password, 'customer');
        if ($user) {
            log_event('registration', 'New customer registered', ['user_id' => $user['id']]);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            redirect('/customer/dashboard.php');
        } else {
            $error = 'Registration failed';
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
  <div class="card">
    <h2>Register</h2>
    <?php if (!empty($error)): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <div>
        <label>Email</label><br>
        <input type="email" name="email" required maxlength="254">
      </div>
      <div>
        <label>Password</label><br>
        <input type="password" name="password" required minlength="12" maxlength="128" autocomplete="new-password">
      </div>
      <button class="btn" type="submit">Create account</button>
    </form>
  </div>
<?php include __DIR__ . '/includes/footer.php'; ?>

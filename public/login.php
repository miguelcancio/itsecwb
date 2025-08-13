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
    $email = validate_email($_POST['email'] ?? '') ?? '';
    $password = (string)($_POST['password'] ?? '');

    $user = $email ? get_user_by_email($email) : null;
    if (!$user) {
        log_event('auth_fail', 'Invalid credentials');
        $error = 'Invalid username and/or password';
    } else {
        if (!empty($user['is_disabled'])) {
            log_event('auth_fail', 'Disabled account login attempt', ['user_id' => $user['id']]);
            $error = 'Invalid username and/or password';
        } elseif (is_account_locked($user)) {
            log_event('auth_fail', 'Locked account login attempt', ['user_id' => $user['id']]);
            $error = 'Account temporarily locked. Please try again later.';
        } elseif (!password_verify($password, $user['password_hash'] ?? '')) {
            increment_failed_attempts($user);
            log_event('auth_fail', 'Password mismatch', ['user_id' => $user['id']]);
            $error = 'Invalid username and/or password';
        } else {
            reset_failed_attempts($user['id']);
            $currentIp = get_client_ip();
            $currentTime = now_iso();
            
            // Try to update the database
            record_login($user['id'], $currentIp);
            
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'last_login_at' => $currentTime,
                'last_login_ip' => $currentIp,
            ];
            $_SESSION['show_login_info'] = true;
            log_event('auth_success', 'User logged in');
            switch ($user['role']) {
                case 'admin': redirect('/admin/dashboard.php');
                case 'manager': redirect('/manager/dashboard.php');
                default: redirect('/customer/dashboard.php');
            }
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
  <div class="card">
    <h2>Login</h2>
    <?php if (!empty($error)): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <div>
        <label>Email</label><br>
        <input type="email" name="email" required maxlength="254">
      </div>
      <div>
        <label>Password</label><br>
        <input type="password" name="password" required minlength="12" maxlength="128" autocomplete="current-password">
      </div>
      <button class="btn" type="submit">Sign in</button>
    </form>
    
    <div style="margin-top: 20px; text-align: center;">
      <a href="/reset_password.php" style="color: #007bff; text-decoration: none;">Forgot your password? Reset it here</a>
    </div>
  </div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/user.php';
require_role('admin');

$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    [$ok, $why] = change_password($_SESSION['user']['id'], (string)($_POST['current_password'] ?? ''), (string)($_POST['new_password'] ?? ''));
    $ok ? $msg = 'Password changed' : $err = $why;
}

include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Change Password</h2>
    <?php if ($msg): ?><p class="success"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
    <?php if ($err): ?><p class="error"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <div>
        <label>Current password</label><br>
        <input type="password" name="current_password" required>
      </div>
      <div>
        <label>New password</label><br>
        <input type="password" name="new_password" required minlength="12" maxlength="128">
      </div>
      <button class="btn" type="submit">Update</button>
    </form>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>



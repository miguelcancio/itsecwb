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

// Check if user has security questions set
$hasSecurityQuestions = has_security_question($_SESSION['user']['id']);

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

  <div class="card">
    <h3>Security Questions</h3>
    <div class="security-status">
      <?php if ($hasSecurityQuestions): ?>
        <div class="status-set">
          <span class="status-icon">✓</span>
          <div class="status-content">
            <strong>Security Questions Set</strong>
            <p>Your account is protected with security questions for password recovery.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="status-not-set">
          <span class="status-icon">⚠</span>
          <div class="status-content">
            <strong>Security Questions Not Set</strong>
            <p>Your account is not protected with security questions. This is required for password recovery.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
    
    <div class="security-actions">
      <a href="/manage_security_questions.php" class="btn <?php echo $hasSecurityQuestions ? 'secondary' : ''; ?>">
        <?php echo $hasSecurityQuestions ? 'Manage Security Questions' : 'Set Security Questions'; ?>
      </a>
    </div>
  </div>

<style>
.security-status {
  margin: 20px 0;
}

.status-set, .status-not-set {
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
  margin: 0;
  opacity: 0.9;
}

.security-actions {
  margin-top: 20px;
  text-align: center;
}

@media (max-width: 640px) {
  .status-set, .status-not-set {
    flex-direction: column;
    text-align: center;
  }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>



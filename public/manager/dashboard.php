<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/user.php';
require_role(['manager','admin']);

$user = $_SESSION['user'];
$hasSecurityQuestions = has_security_question($user['id']);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <h2>Welcome, <?php echo htmlspecialchars($user['email']); ?>!</h2>
  <p>This is your manager dashboard.</p>
</div>

<div class="card">
  <h3>Quick Actions</h3>
  <div class="action-grid">
    <a href="/manager/manage_reservations.php" class="action-card">
      <div class="action-icon">📋</div>
      <h4>Manage Reservations</h4>
      <p>View and manage dorm reservations</p>
    </a>
    
    <a href="/manager/manage_rooms.php" class="action-card">
      <div class="action-icon">🏠</div>
      <h4>Manage Rooms</h4>
      <p>Add, edit, and remove dorm rooms</p>
    </a>
    
    <a href="/manager/manage_customers.php" class="action-card">
      <div class="action-icon">👥</div>
      <h4>Manage Customers</h4>
      <p>View and manage customer accounts</p>
    </a>
    
    <a href="/manager/change_password.php" class="action-card">
      <div class="action-icon">🔒</div>
      <h4>Change Password</h4>
      <p>Update your account password</p>
    </a>
    
    <a href="/manage_security_questions.php" class="action-card <?php echo $hasSecurityQuestions ? 'completed' : 'important'; ?>">
      <div class="action-icon"><?php echo $hasSecurityQuestions ? '✓' : '⚠'; ?></div>
      <h4>Security Questions</h4>
      <p><?php echo $hasSecurityQuestions ? 'Manage your security questions' : 'Set up security questions (Required)'; ?></p>
    </a>
  </div>
</div>

<?php if (!$hasSecurityQuestions): ?>
<div class="card security-reminder">
  <div class="reminder-header">
    <span class="reminder-icon">⚠</span>
    <div class="reminder-content">
      <h3>Security Setup Required</h3>
      <p>Your account is not protected with security questions. This is required for password recovery functionality.</p>
    </div>
  </div>
  <div class="reminder-actions">
    <a href="/manage_security_questions.php" class="btn">Set Security Questions Now</a>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <h3>Account Status</h3>
  <div class="status-grid">
    <div class="status-item">
      <span class="status-label">Email:</span>
      <span class="status-value"><?php echo htmlspecialchars($user['email']); ?></span>
    </div>
    <div class="status-item">
      <span class="status-label">Role:</span>
      <span class="status-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
    </div>
    <div class="status-item">
      <span class="status-label">Security Questions:</span>
      <span class="status-value <?php echo $hasSecurityQuestions ? 'status-ok' : 'status-warning'; ?>">
        <?php echo $hasSecurityQuestions ? '✓ Set' : '⚠ Not Set'; ?>
      </span>
    </div>
    <div class="status-item">
      <span class="status-label">Account Status:</span>
      <span class="status-value status-ok">✓ Active</span>
    </div>
  </div>
</div>

<style>
.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.action-card {
  display: block;
  padding: 24px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
  text-align: center;
}

.action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #7c3aed;
}

.action-card.completed {
  border-color: #22c55e;
  background: #f0fdf4;
}

.action-card.important {
  border-color: #f59e0b;
  background: #fef3c7;
}

.action-icon {
  font-size: 32px;
  margin-bottom: 16px;
  display: block;
}

.action-card h4 {
  margin: 0 0 12px 0;
  color: #374151;
  font-size: 18px;
}

.action-card p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
  line-height: 1.4;
}

.security-reminder {
  border: 2px solid #f59e0b;
  background: #fef3c7;
}

.reminder-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
}

.reminder-icon {
  font-size: 24px;
  font-weight: bold;
  color: #92400e;
  flex-shrink: 0;
}

.reminder-content h3 {
  margin: 0 0 8px 0;
  color: #92400e;
  font-size: 18px;
}

.reminder-content p {
  margin: 0;
  color: #92400e;
  opacity: 0.9;
}

.reminder-actions {
  text-align: center;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-top: 20px;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.status-label {
  font-weight: 600;
  color: #374151;
}

.status-value {
  font-weight: 500;
}

.status-ok {
  color: #166534;
}

.status-warning {
  color: #92400e;
}

@media (max-width: 768px) {
  .action-grid {
    grid-template-columns: 1fr;
  }
  
  .status-grid {
    grid-template-columns: 1fr;
  }
  
  .reminder-header {
    flex-direction: column;
    text-align: center;
  }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>



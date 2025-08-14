<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/user.php';
require_role('admin');

$user = $_SESSION['user'];
$hasSecurityQuestions = has_security_question($user['id']);

// Get system statistics
$users = list_users();
$usersWithoutQuestions = array_filter($users, function($user) {
    return !has_security_question($user['id']);
});

// Get security statistics
$lockedAccounts = count_locked_accounts();
$usersWithFailedAttempts = count_users_with_failed_attempts();
$totalFailedAttempts = get_total_failed_attempts();
$usersApproachingLockout = get_users_approaching_lockout();
$recentlyLockedAccounts = get_recently_locked_accounts();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <h2>Welcome, <?php echo htmlspecialchars($user['email']); ?>!</h2>
  <p>This is your administrator dashboard.</p>
</div>

<div class="card">
  <h3>System Overview</h3>
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-content">
        <span class="stat-number"><?php echo count($users); ?></span>
        <span class="stat-label">Total Users</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-content">
        <span class="stat-number"><?php echo count($users) - count($usersWithoutQuestions); ?></span>
        <span class="stat-label">With Security Q</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">⚠</div>
      <div class="stat-content">
        <span class="stat-number"><?php echo count($usersWithoutQuestions); ?></span>
        <span class="stat-label">Without Security Q</span>
      </div>
    </div>
    <div class="stat-card security-stat">
      <div class="stat-icon">🔒</div>
      <div class="stat-content">
        <span class="stat-number"><?php echo $lockedAccounts; ?></span>
        <span class="stat-label">Locked Accounts</span>
      </div>
    </div>
    <div class="stat-card security-stat">
      <div class="stat-icon">🚨</div>
      <div class="stat-content">
        <span class="stat-number"><?php echo $usersWithFailedAttempts; ?></span>
        <span class="stat-label">Failed Login Users</span>
      </div>
    </div>
    <div class="stat-card security-stat">
      <div class="stat-icon">📊</div>
      <div class="stat-content">
        <span class="stat-number"><?php echo $totalFailedAttempts; ?></span>
        <span class="stat-label">Total Failed Attempts</span>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <h3>Quick Actions</h3>
  <div class="action-grid">
    <a href="/admin/manage_users.php" class="action-card">
      <div class="action-icon">👥</div>
      <h4>Manage Users</h4>
      <p>Create, edit, and manage user accounts</p>
    </a>
    
    <a href="/admin/manage_rooms.php" class="action-card">
      <div class="action-icon">🏠</div>
      <h4>Manage Rooms</h4>
      <p>Configure dormitory rooms and availability</p>
    </a>
    

    
    <a href="/admin/change_password.php" class="action-card">
      <div class="action-icon">🔒</div>
      <h4>Change Password</h4>
      <p>Update your account password</p>
    </a>
    
    <a href="/manage_security_questions.php" class="action-card <?php echo $hasSecurityQuestions ? 'completed' : 'important'; ?>">
      <div class="action-icon"><?php echo $hasSecurityQuestions ? '✓' : '⚠'; ?></div>
      <h4>Security Questions</h4>
      <p><?php echo $hasSecurityQuestions ? 'Manage your security questions' : 'Set up security questions (Required)'; ?></p>
    </a>
    
    <a href="/admin/view_logs.php" class="action-card secondary">
      <div class="action-icon">📊</div>
      <h4>View Logs</h4>
      <p>Monitor system activity and security events</p>
    </a>
  </div>
</div>

<?php if (!empty($usersApproachingLockout) || !empty($recentlyLockedAccounts)): ?>
<div class="card security-alerts-section">
  <h3>Security Alerts</h3>
  
  <?php if (!empty($usersApproachingLockout)): ?>
  <div class="security-alert warning-alert">
    <div class="alert-header">
      <div class="alert-icon-container">
        <span class="alert-icon">⚠</span>
      </div>
      <div class="alert-content">
        <h4>Users Approaching Lockout</h4>
        <p><strong><?php echo count($usersApproachingLockout); ?> users</strong> have 3+ failed login attempts and are approaching account lockout.</p>
        <div class="alert-details">
          <span class="detail-item">
            <span class="detail-icon">👥</span>
            <span class="detail-text">Users at risk: <?php echo count($usersApproachingLockout); ?></span>
          </span>
          <span class="detail-item">
            <span class="detail-icon">🔒</span>
            <span class="detail-text">Lockout threshold: 5 attempts</span>
          </span>
        </div>
      </div>
    </div>
    <div class="alert-actions">
      <a href="/admin/manage_users.php" class="btn btn-warning">Review Users</a>
      <span class="alert-priority">High Priority</span>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if (!empty($recentlyLockedAccounts)): ?>
  <div class="security-alert danger-alert">
    <div class="alert-header">
      <div class="alert-icon-container">
        <span class="alert-icon">🔒</span>
      </div>
      <div class="alert-content">
        <h4>Recently Locked Accounts</h4>
        <p><strong><?php echo count($recentlyLockedAccounts); ?> accounts</strong> were locked due to failed login attempts in the last hour.</p>
        <div class="alert-details">
          <span class="detail-item">
            <span class="detail-icon">⏰</span>
            <span class="detail-text">Timeframe: Last hour</span>
          </span>
          <span class="detail-item">
            <span class="detail-icon">🚨</span>
            <span class="detail-text">Action required: Review and assist</span>
          </span>
        </div>
      </div>
    </div>
    <div class="alert-actions">
      <a href="/admin/manage_users.php" class="btn btn-danger">Review Users</a>
      <span class="alert-priority">Critical</span>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

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

<?php if (!empty($usersWithoutQuestions)): ?>
<div class="card security-alert">
  <div class="alert-header">
    <span class="alert-icon">🚨</span>
    <div class="alert-content">
      <h3>System Security Alert</h3>
      <p><strong><?php echo count($usersWithoutQuestions); ?> users</strong> do not have security questions set. This affects password recovery functionality.</p>
    </div>
  </div>
  <div class="alert-actions">
    <a href="/admin/manage_users.php" class="btn">Manage Users</a>
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
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.stat-icon {
  font-size: 32px;
  flex-shrink: 0;
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-number {
  font-size: 24px;
  font-weight: bold;
  color: #2563eb;
  line-height: 1;
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
  margin-top: 4px;
}

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
  border-color: #dc2626;
}

.action-card.completed {
  border-color: #22c55e;
  background: #f0fdf4;
}

.action-card.important {
  border-color: #f59e0b;
  background: #fef3c7;
}

.action-card.secondary {
  border-color: #6b7280;
  background: #f9fafb;
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

.security-alert {
  border: 2px solid #dc2626;
  background: #fef2f2;
}

.alert-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
}

.alert-icon {
  font-size: 24px;
  font-weight: bold;
  color: #dc2626;
  flex-shrink: 0;
}

.alert-content h3 {
  margin: 0 0 8px 0;
  color: #dc2626;
  font-size: 18px;
}

.alert-content p {
  margin: 0;
  color: #dc2626;
  opacity: 0.9;
}

.alert-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.status-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 20px;
  background: white;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  min-height: 80px;
  justify-content: center;
}

.status-label {
  font-weight: 600;
  color: #6b7280;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-value {
  font-weight: 600;
  font-size: 16px;
  color: #374151;
  word-break: break-word;
  line-height: 1.4;
}

.status-ok {
  color: #166534;
}

.status-warning {
  color: #92400e;
}

.stat-warning {
  color: #92400e;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .action-grid {
    grid-template-columns: 1fr;
  }
  
  .status-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .status-item {
    padding: 16px;
    min-height: 70px;
  }
  
  .reminder-header,
  .alert-header {
    flex-direction: column;
    text-align: center;
  }
  
  .alert-actions {
    flex-direction: column;
    align-items: center;
  }
  
  /* Enhanced Security Alerts Responsive Design */
  .security-alerts-section h3 {
    font-size: 18px;
    justify-content: center;
  }
  
  .security-alert .alert-header {
    flex-direction: column;
    text-align: center;
    gap: 16px;
    padding: 20px 20px 0 20px;
  }
  
  .alert-icon-container {
    width: 50px;
    height: 50px;
  }
  
  .security-alert .alert-icon {
    font-size: 24px;
  }
  
  .alert-details {
    justify-content: center;
    gap: 12px;
  }
  
  .detail-item {
    font-size: 12px;
    padding: 6px 10px;
  }
  
  .security-alert .alert-actions {
    flex-direction: column;
    gap: 16px;
    padding: 20px;
    margin: 0 20px 20px 20px;
    text-align: center;
    justify-content: center;
  }
  
  .btn-warning,
  .btn-danger {
    width: 100%;
    max-width: 200px;
    text-align: center;
    min-width: auto;
  }
  
  .alert-priority {
    order: -1;
    margin-bottom: 8px;
    min-width: auto;
    width: 100%;
    max-width: 150px;
  }
}

/* Security Statistics Styling */
.stat-card.security-stat {
  border-color: #dc2626;
  background: #fef2f2;
}

.stat-card.security-stat .stat-number {
  color: #dc2626;
}

.stat-card.security-stat:nth-child(4) {
  border-color: #dc2626;
  background: #fef2f2;
}

.stat-card.security-stat:nth-child(5) {
  border-color: #f59e0b;
  background: #fef3c7;
}

.stat-card.security-stat:nth-child(5) .stat-number {
  color: #92400e;
}

.stat-card.security-stat:nth-child(6) {
  border-color: #7c3aed;
  background: #f3f4f6;
}

.stat-card.security-stat:nth-child(6) .stat-number {
  color: #7c3aed;
}

/* Security Alerts Section */
.security-alerts-section {
  margin-top: 20px;
}

.security-alerts-section h3 {
  margin-bottom: 20px;
  color: #374151;
  font-size: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 12px;
}

.security-alerts-section h3::before {
  content: "🚨";
  font-size: 24px;
}

.warning-alert {
  border: 2px solid #f59e0b;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  margin-bottom: 20px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(245, 158, 11, 0.15);
  overflow: hidden;
  position: relative;
}

.warning-alert::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.danger-alert {
  border: 2px solid #dc2626;
  background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
  margin-bottom: 20px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(220, 38, 38, 0.15);
  overflow: hidden;
  position: relative;
}

.danger-alert::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #dc2626, #ef4444);
}

.security-alert .alert-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 20px;
  padding: 24px 24px 0 24px;
}

.alert-icon-container {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.warning-alert .alert-icon-container {
  border: 3px solid #f59e0b;
}

.danger-alert .alert-icon-container {
  border: 3px solid #dc2626;
}

.security-alert .alert-icon {
  font-size: 28px;
  font-weight: bold;
}

.warning-alert .alert-icon {
  color: #92400e;
}

.danger-alert .alert-icon {
  color: #dc2626;
}

.security-alert .alert-content {
  flex: 1;
  min-width: 0;
}

.security-alert .alert-content h4 {
  margin: 0 0 12px 0;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.3;
}

.warning-alert .alert-content h4 {
  color: #92400e;
}

.danger-alert .alert-content h4 {
  color: #dc2626;
}

.security-alert .alert-content p {
  margin: 0 0 16px 0;
  opacity: 0.9;
  font-size: 15px;
  line-height: 1.5;
}

.warning-alert .alert-content p {
  color: #92400e;
}

.danger-alert .alert-content p {
  color: #dc2626;
}

.alert-details {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 16px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.7);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
}

.warning-alert .detail-item {
  color: #92400e;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.danger-alert .detail-item {
  color: #dc2626;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.detail-icon {
  font-size: 16px;
  opacity: 0.8;
}

.detail-text {
  white-space: nowrap;
}

.security-alert .alert-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  padding: 20px 24px 24px 24px;
  background: rgba(255, 255, 255, 0.5);
  margin: 0 24px 24px 24px;
  border-radius: 12px;
  text-align: center;
}

.btn-warning {
  background: #f59e0b;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
  min-width: 140px;
}

.btn-warning:hover {
  background: #d97706;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.btn-danger {
  background: #dc2626;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
  min-width: 140px;
}

.btn-danger:hover {
  background: #b91c1c;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
}

.alert-priority {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 8px 16px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.9);
  min-width: 120px;
  text-align: center;
}

.warning-alert .alert-priority {
  color: #92400e;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.danger-alert .alert-priority {
  color: #dc2626;
  border: 1px solid rgba(220, 38, 38, 0.3);
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>



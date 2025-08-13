<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/logger.php';
require_role('admin');

$message = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_user') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $securityQuestion = trim($_POST['security_question'] ?? '');
        $securityAnswer = trim($_POST['security_answer'] ?? '');
        
        if ($email && $password && $role && $securityQuestion && $securityAnswer) {
            $u = create_user_with_security_question($email, $password, $role, $securityQuestion, $securityAnswer);
            $u ? $message = 'User created' : $error = 'Create failed';
        } else {
            $error = 'Invalid input';
        }
    } elseif ($action === 'set_security_question') {
        $userId = $_POST['user_id'] ?? '';
        $securityQuestion = trim($_POST['security_question'] ?? '');
        $securityAnswer = trim($_POST['security_answer'] ?? '');
        
        if ($userId && $securityQuestion && $securityAnswer) {
            if (update_user_security_question($userId, $securityQuestion, $securityAnswer)) {
                $message = 'Security question set successfully';
                log_event('admin_security_question_set', 'Admin set security question for user', [
                    'admin_id' => $_SESSION['user']['id'],
                    'user_id' => $userId
                ]);
            } else {
                $error = 'Failed to set security question';
            }
        } else {
            $error = 'Invalid input';
        }
    } elseif ($action === 'change_role') {
        $userId = $_POST['user_id'] ?? '';
        $newRole = $_POST['new_role'] ?? '';
        
        if ($userId && $newRole) {
            if (update_user_role($userId, $newRole)) {
                $message = 'User role updated successfully';
                log_event('admin_role_change', 'Admin changed user role', [
                    'admin_id' => $_SESSION['user']['id'],
                    'user_id' => $userId,
                    'new_role' => $newRole
                ]);
            } else {
                $error = 'Failed to update user role';
            }
        } else {
            $error = 'Invalid input for role change';
        }
    }
}

$users = list_users();
$usersWithoutQuestions = array_filter($users, function($user) {
    return !has_security_question($user['id']);
});

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Manage Users</h2>
    <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    
    <div class="security-overview">
        <div class="overview-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($users); ?></span>
                <span class="stat-label">Total Users</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($users) - count($usersWithoutQuestions); ?></span>
                <span class="stat-label">With Security Questions</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($usersWithoutQuestions); ?></span>
                <span class="stat-label">Without Security Questions</span>
            </div>
        </div>
        
        <?php if (!empty($usersWithoutQuestions)): ?>
            <div class="security-alert">
                <span class="alert-icon">⚠</span>
                <div class="alert-content">
                    <strong>Security Alert</strong>
                    <p><?php echo count($usersWithoutQuestions); ?> users do not have security questions set. This affects password recovery functionality.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Create User</h3>
    <form method="post" novalidate class="create-user-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="create_user">
        
        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required minlength="12" maxlength="128">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="customer">Customer</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Security Question</label>
                <input type="text" name="security_question" placeholder="Security Question (e.g., What was your first pet's name?)" required maxlength="255">
                <small>Minimum 10 characters</small>
            </div>
            <div class="form-group">
                <label>Security Answer</label>
                <input type="text" name="security_answer" placeholder="Security Answer" required maxlength="255">
                <small>Minimum 3 characters</small>
            </div>
        </div>
        
        <button class="btn" type="submit">Create User</button>
    </form>
</div>



<div class="card">
    <h3>User List</h3>
    <div class="table-container">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Security Questions</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="<?php echo has_security_question($user['id']) ? 'has-questions' : 'no-questions'; ?>">
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                        <td>
                            <?php if (has_security_question($user['id'])): ?>
                                <span class="status-badge status-set">✓ Set</span>
                            <?php else: ?>
                                <span class="status-badge status-not-set">⚠ Not Set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['is_disabled']): ?>
                                <span class="status-badge status-disabled">Disabled</span>
                            <?php elseif (is_account_locked($user)): ?>
                                <span class="status-badge status-locked">Locked</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if (!has_security_question($user['id'])): ?>
                                    <button class="btn btn-small" onclick="openSecurityModal('<?php echo htmlspecialchars($user['id']); ?>', '<?php echo htmlspecialchars($user['email']); ?>')">
                                        Set Security Q
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-small secondary" onclick="changeRole('<?php echo htmlspecialchars($user['id']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')">
                                    Change Role
                                </button>
                                <button class="btn btn-small <?php echo $user['is_disabled'] ? 'success' : 'danger'; ?>" onclick="toggleUser('<?php echo htmlspecialchars($user['id']); ?>', <?php echo $user['is_disabled'] ? 'false' : 'true'; ?>)">
                                    <?php echo $user['is_disabled'] ? 'Enable' : 'Disable'; ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Security Question Modal -->
<div id="securityModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Set Security Question</h3>
        <p>Setting security question for: <strong id="modalUserEmail"></strong></p>
        
        <form method="post" novalidate class="security-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="set_security_question">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <div class="form-group">
                <label>Security Question</label>
                <input type="text" name="security_question" placeholder="e.g., What was your first pet's name?" required maxlength="255">
                <small>Minimum 10 characters</small>
            </div>
            
            <div class="form-group">
                <label>Security Answer</label>
                <input type="text" name="security_answer" placeholder="Your answer to the security question" required maxlength="255">
                <small>Minimum 3 characters</small>
            </div>
            
            <button class="btn btn-large" type="submit">Set Security Question</button>
        </form>
    </div>
</div>

<!-- Role Change Modal -->
<div id="roleModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Change User Role</h3>
        <p>Changing role for user ID: <strong id="roleModalCurrentRole"></strong></p>
        
        <div class="form-group">
            <label for="newRoleSelect">New Role:</label>
            <select id="newRoleSelect" class="form-control">
                <option value="customer">Customer</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
            </select>
            <small>Select the new role for this user</small>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-large" onclick="submitRoleChange()">Change Role</button>
            <button class="btn btn-large secondary" onclick="document.getElementById('roleModal').style.display = 'none'">Cancel</button>
        </div>
        
        <input type="hidden" id="roleModalUserId" value="">
    </div>
</div>

<style>
.security-overview {
    margin: 20px 0;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #2563eb;
    margin-bottom: 8px;
}

.stat-label {
    color: #6b7280;
    font-size: 14px;
}

.security-alert {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: #fef3c7;
    border: 2px solid #f59e0b;
    border-radius: 12px;
    color: #92400e;
}

.alert-icon {
    font-size: 24px;
    font-weight: bold;
    flex-shrink: 0;
}

.alert-content strong {
    display: block;
    font-size: 18px;
    margin-bottom: 8px;
}

.alert-content p {
    margin: 0;
    opacity: 0.9;
}

.create-user-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-row:last-of-type {
    grid-template-columns: 1fr 1fr;
    max-width: 100%;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-group input, .form-group select {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
}

.form-group small {
    display: block;
    margin-top: 6px;
    font-size: 14px;
    color: #6b7280;
}



.table-container {
    overflow-x: auto;
}

.user-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.user-table th,
.user-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.user-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.user-table tr:hover {
    background: #f9fafb;
}

.user-table tr.has-questions {
    background: #f0fdf4;
}

.user-table tr.no-questions {
    background: #fef3c7;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.role-admin {
    background: #dc2626;
    color: white;
}

.role-manager {
    background: #7c3aed;
    color: white;
}

.role-customer {
    background: #059669;
    color: white;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-set {
    background: #dcfce7;
    color: #166534;
}

.status-not-set {
    background: #fef3c7;
    color: #92400e;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-disabled {
    background: #fee2e2;
    color: #dc2626;
}

.status-locked {
    background: #fef3c7;
    color: #92400e;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    top: 20px;
    right: 20px;
}

.close:hover {
    color: #000;
}

.security-form {
    margin-top: 20px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.modal-actions .btn {
    min-width: 100px;
}

#newRoleSelect {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 16px;
    background: white;
}

#newRoleSelect:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .overview-stats {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    

    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
</style>

<script>
function openSecurityModal(userId, userEmail) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserEmail').textContent = userEmail;
    document.getElementById('securityModal').style.display = 'block';
}

// Close modals when clicking on X
document.querySelectorAll('.close').forEach(function(closeBtn) {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});

// Close modals when clicking outside of them
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}

function changeRole(userId, currentRole) {
    // Set the current role and user ID in the modal
    document.getElementById('roleModalUserId').value = userId;
    document.getElementById('roleModalCurrentRole').textContent = userId;
    
    // Set the current role as selected in dropdown
    document.getElementById('newRoleSelect').value = currentRole;
    
    // Show the modal
    document.getElementById('roleModal').style.display = 'block';
}

function submitRoleChange() {
    const userId = document.getElementById('roleModalUserId').value;
    const newRole = document.getElementById('newRoleSelect').value;
    const currentRole = document.getElementById('roleModalCurrentRole').textContent;
    
    if (newRole && newRole !== currentRole) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'change_role');
        formData.append('user_id', userId);
        formData.append('new_role', newRole);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        // Submit the form
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            // Reload the page to show updated role
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to change role. Please try again.');
        });
    }
    
    // Close the modal
    document.getElementById('roleModal').style.display = 'none';
}

function toggleUser(userId, disable) {
    const action = disable ? 'disable' : 'enable';
    if (confirm(`Are you sure you want to ${action} user ${userId}?`)) {
        // Implement user toggle functionality
        alert('User toggle functionality to be implemented');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>



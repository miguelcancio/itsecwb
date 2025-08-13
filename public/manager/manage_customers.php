<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/logger.php';
require_role(['manager','admin']);

$message = null;
$error = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    // Re-authenticate manager for sensitive actions
    $managerId = $_SESSION['user']['id'] ?? '';
    $managerPassword = (string)($_POST['manager_password'] ?? '');
    $manager = $managerId ? get_user_by_id($managerId) : null;
    if (!$manager || !password_verify($managerPassword, $manager['password_hash'] ?? '')) {
        $error = 'Re-authentication required';
        log_event('reauth_fail', 'Manager re-auth failed for sensitive action', ['manager_id' => $managerId, 'action' => $action]);
    } else if ($action === 'update_status') {
        $userId = $_POST['user_id'] ?? '';
        $isActive = (($_POST['is_active'] ?? '0') === '1');
        if (update_user_status($userId, $isActive)) {
            $message = 'User status updated successfully';
            log_event('user_status_update', 'Manager updated customer status', ['manager_id' => $managerId, 'user_id' => $userId, 'is_active' => $isActive]);
        } else {
            $error = 'Failed to update user status';
        }
    } else if ($action === 'update_role') {
        $userId = $_POST['user_id'] ?? '';
        $newRole = $_POST['role'] ?? '';
        if ($newRole === 'customer') {
            if (update_user_role($userId, $newRole)) {
                $message = 'User role updated successfully';
                log_event('user_role_update', 'Manager set user role', ['manager_id' => $managerId, 'user_id' => $userId, 'role' => $newRole]);
            } else {
                $error = 'Failed to update user role';
            }
        } else {
            $error = 'Invalid role assignment';
        }
    }
}

// Fetch all customers (Role B users)
$customers = get_users_by_role('customer');

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Manage Customers</h2>
    <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    
    <div class="info-section">
        <p><strong>Role:</strong> Manager - You can manage customer accounts, view their information, and update their status.</p>
    </div>
    
    <!-- Customers Table -->
    <div class="table-section">
        <h3>Customer Accounts</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($customer['id']); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($customer['email']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($customer['full_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php $isActive = empty($customer['is_disabled']); ?>
                        <span class="status-<?php echo $isActive ? 'active' : 'inactive'; ?>">
                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($customer['created_at'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($customer['last_login_at'] ?? 'Never'); ?></td>
                    <td>
                        <!-- Status Toggle -->
                        <button type="button" class="btn small <?php echo $isActive ? 'warning' : 'success'; ?>" onclick="showStatusForm('<?php echo htmlspecialchars($customer['id']); ?>', <?php echo $isActive ? 'false' : 'true'; ?>)">
                            <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                        </button>
                        
                        <!-- Role Management (Managers can only assign customer role) -->
                        <button type="button" class="btn small secondary" onclick="showRoleForm('<?php echo htmlspecialchars($customer['id']); ?>')" <?php echo $customer['role'] === 'customer' ? 'disabled' : ''; ?>>
                            <?php echo $customer['role'] === 'customer' ? 'Customer Role' : 'Set as Customer'; ?>
                        </button>
                        
                        <!-- View Customer Details -->
                        <button class="btn small info" onclick="viewCustomerDetails('<?php echo htmlspecialchars($customer['id']); ?>')">
                            View Details
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Status Update Form (hidden by default) -->
<div id="statusForm" style="display: none;" class="action-form">
    <h4>Update Customer Status</h4>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="user_id" id="status_user_id">
        <input type="hidden" name="is_active" id="status_is_active">
        
        <div class="form-group">
            <label for="status_password">Enter your password to confirm:</label>
            <input type="password" id="status_password" name="manager_password" required placeholder="Your password">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn" id="status_submit_btn">Update Status</button>
            <button type="button" class="btn secondary" onclick="hideStatusForm()">Cancel</button>
        </div>
    </form>
</div>

<!-- Role Update Form (hidden by default) -->
<div id="roleForm" style="display: none;" class="action-form">
    <h4>Update Customer Role</h4>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="update_role">
        <input type="hidden" name="user_id" id="role_user_id">
        <input type="hidden" name="role" value="customer">
        
        <div class="form-group">
            <label for="role_password">Enter your password to confirm:</label>
            <input type="password" id="role_password" name="manager_password" required placeholder="Your password">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Set as Customer</button>
            <button type="button" class="btn secondary" onclick="hideRoleForm()">Cancel</button>
        </div>
    </form>
</div>

<!-- Customer Details Modal -->
<div id="customerModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeCustomerModal()">&times;</span>
        <h3>Customer Details</h3>
        <div id="customerDetails">
            <!-- Customer details will be loaded here -->
        </div>
    </div>
</div>

<style>
.info-section {
    margin-bottom: 20px;
    padding: 16px;
    background: #dbeafe;
    border: 1px solid #3b82f6;
    border-radius: 8px;
    color: #1e40af;
}

/* Action form styles */
.action-form {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    border: 1px solid #e5e7eb;
    min-width: 350px;
}

.info-section p {
    margin: 0;
    font-size: 14px;
}

.table-section {
    margin-top: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.status-active {
    color: #166534;
    font-weight: 500;
}

.status-inactive {
    color: #dc2626;
    font-weight: 500;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    margin: 2px;
}

.btn.small {
    padding: 4px 8px;
    font-size: 12px;
}

.btn.success {
    background: #22c55e;
    color: white;
}

.btn.success:hover {
    background: #16a34a;
}

.btn.warning {
    background: #f59e0b;
    color: white;
}

.btn.warning:hover {
    background: #d97706;
}

.btn.secondary {
    background: #6b7280;
    color: white;
}

.btn.secondary:hover {
    background: #4b5563;
}

.btn.secondary:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.btn.info {
    background: #3b82f6;
    color: white;
}

.btn.info:hover {
    background: #2563eb;
}

/* Modal Styles */
.modal {
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
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

.customer-detail {
    margin-bottom: 16px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.customer-detail label {
    font-weight: 600;
    color: #374151;
    display: block;
    margin-bottom: 4px;
}

.customer-detail span {
    color: #6b7280;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    
    .btn {
        display: block;
        width: 100%;
        margin: 4px 0;
    }
}
</style>

<script>
function viewCustomerDetails(userId) {
    // For now, we'll show a simple message
    // In a real application, you'd fetch customer details via AJAX
    document.getElementById('customerDetails').innerHTML = `
        <div class="customer-detail">
            <label>Customer ID:</label>
            <span>${userId}</span>
        </div>
        <div class="customer-detail">
            <label>Note:</label>
            <span>Detailed customer information would be loaded here via AJAX in a production environment.</span>
        </div>
        <div class="customer-detail">
            <label>Reservations:</label>
            <span>View customer's reservation history and current bookings.</span>
        </div>
    `;
    document.getElementById('customerModal').style.display = 'block';
}

function closeCustomerModal() {
    document.getElementById('customerModal').style.display = 'none';
}

function showStatusForm(userId, isActive) {
    document.getElementById('status_user_id').value = userId;
    document.getElementById('status_is_active').value = isActive ? '1' : '0';
    document.getElementById('status_submit_btn').textContent = isActive ? 'Activate Customer' : 'Deactivate Customer';
    document.getElementById('statusForm').style.display = 'block';
    document.getElementById('status_password').focus();
}

function hideStatusForm() {
    document.getElementById('statusForm').style.display = 'none';
    document.getElementById('status_password').value = '';
}

function showRoleForm(userId) {
    document.getElementById('role_user_id').value = userId;
    document.getElementById('roleForm').style.display = 'block';
    document.getElementById('role_password').focus();
}

function hideRoleForm() {
    document.getElementById('roleForm').style.display = 'none';
    document.getElementById('role_password').value = '';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const customerModal = document.getElementById('customerModal');
    const statusForm = document.getElementById('statusForm');
    const roleForm = document.getElementById('roleForm');
    
    if (event.target === customerModal) {
        customerModal.style.display = 'none';
    }
    if (event.target === statusForm) {
        statusForm.style.display = 'none';
    }
    if (event.target === roleForm) {
        roleForm.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

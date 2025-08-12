<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/user.php';
require_role('admin');

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve') {
        $requestId = $_POST['request_id'] ?? '';
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        
        if ($requestId) {
            if (approve_password_reset($requestId, $adminNotes)) {
                $message = 'Password reset request approved successfully.';
            } else {
                $error = 'Failed to approve request.';
            }
        } else {
            $error = 'Request ID required.';
        }
    } elseif ($action === 'deny') {
        $requestId = $_POST['request_id'] ?? '';
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        
        if ($requestId) {
            if (deny_password_reset($requestId, $adminNotes)) {
                $message = 'Password reset request denied successfully.';
            } else {
                $error = 'Failed to deny request.';
            }
        } else {
            $error = 'Request ID required.';
        }
    }
}

// Get all password reset requests
$resetRequests = get_all_reset_requests();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Manage Password Reset Requests</h2>
    <p>Review and manage password reset requests from users.</p>
    
    <?php if ($message): ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Password Reset Requests</h3>
    
    <?php if (empty($resetRequests)): ?>
        <p>No password reset requests found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>User Email</th>
                    <th>Security Question</th>
                    <th>Requested At</th>
                    <th>Status</th>
                    <th>Expires At</th>
                    <th>Admin Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resetRequests as $request): ?>
                    <?php 
                    $user = get_user_by_id($request['user_id']);
                    $isExpired = !empty($request['expires_at']) && strtotime($request['expires_at']) < time();
                    ?>
                    <tr class="<?php echo $isExpired ? 'expired' : ''; ?>">
                        <td>
                            <?php if ($user): ?>
                                <?php echo htmlspecialchars($user['email']); ?>
                                <br><small>Role: <?php echo htmlspecialchars($user['role']); ?></small>
                            <?php else: ?>
                                <em>User not found</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user && !empty($user['security_question'])): ?>
                                <strong><?php echo htmlspecialchars($user['security_question']); ?></strong>
                            <?php else: ?>
                                <em>No security question</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo !empty($request['requested_at']) ? date('M j, Y g:i A', strtotime($request['requested_at'])) : 'N/A'; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                            </span>
                            <?php if ($isExpired && $request['status'] === 'pending'): ?>
                                <br><small class="expired-text">(Expired)</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo !empty($request['expires_at']) ? date('M j, Y g:i A', strtotime($request['expires_at'])) : 'N/A'; ?>
                        </td>
                        <td>
                            <?php if (!empty($request['admin_notes'])): ?>
                                <div class="admin-notes">
                                    <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                </div>
                            <?php else: ?>
                                <em>No notes</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="action-buttons">
                                    <button class="btn btn-small" onclick="showApproveModal('<?php echo htmlspecialchars($request['id']); ?>')">
                                        Approve
                                    </button>
                                    <button class="btn btn-small secondary" onclick="showDenyModal('<?php echo htmlspecialchars($request['id']); ?>')">
                                        Deny
                                    </button>
                                </div>
                            <?php elseif ($request['status'] === 'completed'): ?>
                                <span class="success">✓ Completed</span>
                            <?php elseif ($request['status'] === 'denied'): ?>
                                <span class="error">✗ Denied</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Approve Password Reset Request</h3>
        <form method="post" id="approveForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="request_id" id="approveRequestId">
            
            <div class="form-group">
                <label for="approveNotes">Admin Notes (Optional)</label>
                <textarea name="admin_notes" id="approveNotes" rows="3" placeholder="Add any notes about this approval..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn">Approve Request</button>
                <button type="button" class="btn secondary" onclick="hideModal('approveModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Deny Modal -->
<div id="denyModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Deny Password Reset Request</h3>
        <form method="post" id="denyForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="deny">
            <input type="hidden" name="request_id" id="denyRequestId">
            
            <div class="form-group">
                <label for="denyNotes">Admin Notes (Required)</label>
                <textarea name="admin_notes" id="denyNotes" rows="3" placeholder="Please provide a reason for denying this request..." required></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn secondary">Deny Request</button>
                <button type="button" class="btn" onclick="hideModal('denyModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-approved {
    background: #d1fae5;
    color: #065f46;
}

.status-denied {
    background: #fee2e2;
    color: #991b1b;
}

.status-completed {
    background: #dbeafe;
    color: #1e40af;
}

.expired {
    background-color: #fef2f2;
}

.expired-text {
    color: #dc2626;
    font-weight: bold;
}

.admin-notes {
    background: #f8fafc;
    padding: 8px;
    border-radius: 4px;
    font-size: 0.9em;
    max-width: 200px;
    word-wrap: break-word;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-direction: column;
}

.btn-small {
    padding: 4px 8px;
    font-size: 0.8em;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 20px;
}

.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
}
</style>

<script>
function showApproveModal(requestId) {
    document.getElementById('approveRequestId').value = requestId;
    document.getElementById('approveModal').style.display = 'flex';
}

function showDenyModal(requestId) {
    document.getElementById('denyRequestId').value = requestId;
    document.getElementById('denyModal').style.display = 'flex';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
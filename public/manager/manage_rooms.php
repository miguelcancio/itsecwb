<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/room.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../../config/supabase.php';
require_role(['manager','admin']);

$message = null;
$error = null;

// Clear any previous errors from session
if (isset($_SESSION['error'])) {
    unset($_SESSION['error']);
}
if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    // Current manager ID for logging
    $managerId = $_SESSION['user']['id'] ?? '';
    
    // Create is not destructive; allow without re-auth
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 1);
        
        if (create_room($name, $description, $capacity)) {
            $message = 'Room created successfully';
            if (function_exists('log_event')) {
                log_event('room_create', 'Manager created room', ['manager_id' => $managerId, 'name' => $name]);
            }
        } else {
            $error = 'Failed to create room';
        }
    } elseif ($action === 'update') {
        $roomId = $_POST['room_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 1);
        $isActive = isset($_POST['is_active']);
        
        $updateData = [
            'name' => $name,
            'description' => $description,
            'capacity' => $capacity,
            'is_active' => $isActive
        ];
        
        // Re-authenticate manager for update (privilege change on resource)
        $managerPassword = (string)($_POST['manager_password'] ?? '');
        $manager = $managerId ? get_user_by_id($managerId) : null;
        if (!$manager) {
            $error = 'Manager account not found';
            if (function_exists('log_event')) {
                log_event('reauth_fail', 'Manager re-auth failed - account not found', ['manager_id' => $managerId]);
            }
        } elseif (!password_verify($managerPassword, $manager['password_hash'] ?? '')) {
            $error = 'Incorrect password';
            if (function_exists('log_event')) {
                log_event('reauth_fail', 'Manager re-auth failed - incorrect password', ['manager_id' => $managerId]);
            }
        } elseif (update_room($roomId, $updateData)) {
            $message = 'Room updated successfully';
            if (function_exists('log_event')) {
                log_event('room_update', 'Manager updated room', ['manager_id' => $managerId, 'room_id' => $roomId]);
            }
        } else {
            $error = 'Failed to update room';
        }
    } elseif ($action === 'delete') {
        // Re-authenticate manager for delete only (destructive)
        $managerPassword = (string)($_POST['manager_password'] ?? '');
        
        $manager = $managerId ? get_user_by_id($managerId) : null;
        
        if (!$manager) {
            $error = 'Manager account not found';
            if (function_exists('log_event')) {
                log_event('reauth_fail', 'Manager re-auth failed - account not found', ['manager_id' => $managerId]);
            }
        } elseif (!password_verify($managerPassword, $manager['password_hash'] ?? '')) {
            $error = 'Incorrect password';
            if (function_exists('log_event')) {
                log_event('reauth_fail', 'Manager re-auth failed - incorrect password', ['manager_id' => $managerId]);
            }
        } else {
            $roomId = $_POST['room_id'] ?? '';
            
            // Check if room has reservations before attempting delete
            $reservations = sb_get('reservations', ['room_id' => $roomId], 1);
            if (!empty($reservations)) {
                $error = 'Cannot delete room. Room has existing reservations.';
                if (function_exists('log_event')) {
                    log_event('room_delete_blocked', 'Manager attempted to delete room with reservations', ['manager_id' => $managerId, 'room_id' => $roomId]);
                }
            } else {
                // Try to delete the room

                $deleteResult = delete_room($roomId);

                
                if ($deleteResult === true) {
                    $message = 'Room deleted successfully';

                    if (function_exists('log_event')) {
                        log_event('room_delete', 'Manager deleted room', ['manager_id' => $managerId, 'room_id' => $roomId]);
                    }
                } else {
                    // More detailed error message
                    $error = 'Failed to delete room due to database error.';
                    if (function_exists('log_event')) {
                        log_event('room_delete_error', 'Manager failed to delete room - database error', ['manager_id' => $managerId, 'room_id' => $roomId]);
                    }
                }
            }
        }
    }
}

// Fetch all rooms
$rooms = list_rooms();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Manage Rooms</h2>
    <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    
    <!-- Create New Room Form -->
    <div class="form-section">
        <h3>Add New Room</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="name">Room Name *</label>
                <input type="text" id="name" name="name" required maxlength="64" placeholder="e.g., Room 101">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="255" placeholder="Room description..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="capacity">Capacity</label>
                <input type="number" id="capacity" name="capacity" min="1" max="10" value="1">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Create Room</button>
            </div>
        </form>
    </div>
    
    <!-- Existing Rooms Table -->
    <div class="table-section">
        <h3>Existing Rooms</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($room['name']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($room['description'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars((string)$room['capacity']); ?></td>
                    <td>
                        <span class="status-<?php echo $room['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $room['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn small" onclick="editRoom('<?php echo htmlspecialchars($room['id']); ?>', '<?php echo htmlspecialchars($room['name']); ?>', '<?php echo htmlspecialchars($room['description'] ?? ''); ?>', <?php echo $room['capacity']; ?>, <?php echo $room['is_active'] ? 'true' : 'false'; ?>)">Edit</button>
                        
                        <button type="button" class="btn small danger" onclick="showDeleteForm('<?php echo htmlspecialchars($room['id']); ?>')">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Room Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Room</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="room_id" id="edit_room_id">
            
            <div class="form-group">
                <label for="edit_name">Room Name *</label>
                <input type="text" id="edit_name" name="name" required maxlength="64">
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" maxlength="255"></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_capacity">Capacity</label>
                <input type="number" id="edit_capacity" name="capacity" min="1" max="10">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="edit_is_active" name="is_active">
                    Active
                </label>
            </div>
            
            <div class="form-group">
                <label for="edit_password">Enter your password to confirm:</label>
                <input type="password" id="edit_password" name="manager_password" required placeholder="Your password">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Update Room</button>
                <button type="button" class="btn secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form (hidden by default) -->
<div id="deleteForm" style="display: none;" class="delete-form">
    <h4>Confirm Room Deletion</h4>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="room_id" id="delete_room_id">
        
        <div class="form-group">
            <label for="delete_password">Enter your password to confirm:</label>
            <input type="password" id="delete_password" name="manager_password" required placeholder="Your password">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn danger">Delete Room</button>
            <button type="button" class="btn secondary" onclick="hideDeleteForm()">Cancel</button>
        </div>
    </form>
</div>

<style>
.form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 4px;
    color: #374151;
}

.form-group input,
.form-group textarea {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 60px;
}

.table-section {
    margin-top: 30px;
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
}

.btn.small {
    padding: 4px 8px;
    font-size: 12px;
}

.btn.danger {
    background: #dc2626;
    color: white;
}

.btn.danger:hover {
    background: #b91c1c;
}

.btn.secondary {
    background: #6b7280;
    color: white;
}

.btn.secondary:hover {
    background: #4b5563;
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
    max-width: 500px;
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

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
}

/* Message transitions */
.success, .error {
    transition: opacity 0.3s ease;
}

/* Delete form styles */
.delete-form {
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
    min-width: 300px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-hide error/success messages after 5 seconds
  const messages = document.querySelectorAll('.success, .error');
  messages.forEach(function(msg) {
    setTimeout(function() {
      msg.style.opacity = '0';
      setTimeout(function() {
        msg.style.display = 'none';
      }, 300);
    }, 5000);
  });
});

function showDeleteForm(roomId) {
  document.getElementById('delete_room_id').value = roomId;
  document.getElementById('deleteForm').style.display = 'block';
  document.getElementById('delete_password').focus();
}

function hideDeleteForm() {
  document.getElementById('deleteForm').style.display = 'none';
  document.getElementById('delete_password').value = '';
}

function editRoom(id, name, description, capacity, isActive) {
    document.getElementById('edit_room_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_capacity').value = capacity;
    document.getElementById('edit_is_active').checked = isActive;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

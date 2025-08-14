<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/room.php';
require_once __DIR__ . '/../includes/reservation.php';
require_role('admin');

$message = null; 
$error = null;
$editRoomId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 1);
        
        if ($name) {
            $room = create_room($name, $description, $capacity);
            if ($room) {
                $message = 'Room created successfully';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode('Room created successfully'));
                exit;
            } else {
                $error = 'Create failed';
            }
        } else {
            $error = 'Room name is required';
        }
    } elseif ($action === 'update') {
        $roomId = $_POST['room_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 1);
        $isActive = isset($_POST['is_active']);
        
        if ($roomId && $name) {
            $data = [
                'name' => $name,
                'description' => $description,
                'capacity' => $capacity,
                'is_active' => $isActive
            ];
            if (update_room($roomId, $data)) {
                $message = 'Room updated successfully';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode('Room updated successfully'));
                exit;
            } else {
                $error = 'Update failed';
            }
        } else {
            $error = 'Room ID and name are required';
        }
    } elseif ($action === 'delete') {
        $roomId = $_POST['room_id'] ?? '';
        if ($roomId) {
            if (delete_room($roomId)) {
                $message = 'Room deleted successfully';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode('Room deleted successfully'));
                exit;
            } else {
                $error = 'Cannot delete room with reservations';
            }
        } else {
            $error = 'Room ID required';
        }
    } elseif ($action === 'edit') {
        $editRoomId = $_POST['room_id'] ?? '';
    }
}

// Get message from URL if redirected
if (empty($message) && isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

$rooms = list_rooms();
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>Manage Rooms</h2>
        <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <h3>Create Room</h3>
        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Room Name</label>
                <input type="text" name="name" required maxlength="64" placeholder="e.g., Room 101">
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" maxlength="255" placeholder="e.g., Single room with AC">
            </div>
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="capacity" min="1" max="10" value="1" required>
            </div>
            <button class="btn" type="submit">Create Room</button>
        </form>
    </div>

    <div class="card">
        <h3>Existing Rooms</h3>
        <?php if (empty($rooms)): ?>
            <p>No rooms created yet.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Current Reservations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['name']); ?></td>
                            <td><?php echo htmlspecialchars($room['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars((string)($room['capacity'] ?? 1)); ?></td>
                            <td>
                                <span class="status-badge <?php echo !empty($room['is_active']) ? 'active' : 'inactive'; ?>">
                                    <?php echo !empty($room['is_active']) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                // Get current reservations for this room
                                $currentReservations = sb_get('reservations', [
                                    'room_id' => $room['id'],
                                    'status' => ['in' => ['pending', 'approved']]
                                ], 10, 0, 'date_from,date_to');
                                
                                if (empty($currentReservations)) {
                                    echo '<span class="available">Available</span>';
                                } else {
                                    echo '<span class="booked">' . count($currentReservations) . ' reservation(s)</span>';
                                }
                                ?>
                            </td>
                            <td class="actions">
                                <?php if ($editRoomId === $room['id']): ?>
                                    <!-- Edit Form -->
                                    <form method="post" class="edit-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
                                        <div class="edit-inputs">
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required maxlength="64" placeholder="Room name">
                                            <input type="text" name="description" value="<?php echo htmlspecialchars($room['description'] ?? ''); ?>" maxlength="255" placeholder="Description">
                                            <input type="number" name="capacity" value="<?php echo htmlspecialchars((string)($room['capacity'] ?? 1)); ?>" min="1" max="10" required>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="is_active" <?php echo !empty($room['is_active']) ? 'checked' : ''; ?>>
                                                Active
                                            </label>
                                        </div>
                                        <div class="edit-buttons">
                                            <button class="btn secondary" type="submit">Save</button>
                                            <button class="btn" type="button" onclick="cancelEdit()">Cancel</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <!-- Action Buttons -->
                                    <button class="btn secondary" onclick="editRoom('<?php echo htmlspecialchars($room['id']); ?>')">Edit</button>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this room?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
                                        <button class="btn danger" type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Room Availability Overview</h3>
        <div class="availability-grid">
            <?php foreach ($rooms as $room): ?>
                <?php if (!empty($room['is_active'])): ?>
                    <div class="room-availability">
                        <h4><?php echo htmlspecialchars($room['name']); ?></h4>
                        <p class="room-desc"><?php echo htmlspecialchars($room['description'] ?? ''); ?></p>
                        <p class="room-capacity">Capacity: <?php echo htmlspecialchars((string)($room['capacity'] ?? 1)); ?></p>
                        
                        <?php 
                        // Get upcoming reservations for this room
                        $upcomingReservations = sb_get('reservations', [
                            'room_id' => $room['id'],
                            'status' => ['in' => ['pending', 'approved']],
                            'date_from' => ['gte' => date('Y-m-d')]
                        ], 10, 0, 'date_from,date_to,status');
                        ?>
                        
                        <div class="reservation-list">
                            <h5>Upcoming Reservations:</h5>
                            <?php if (empty($upcomingReservations)): ?>
                                <p class="available">No upcoming reservations</p>
                            <?php else: ?>
                                <?php foreach ($upcomingReservations as $reservation): ?>
                                    <div class="reservation-item">
                                        <span class="dates">
                                            <?php echo htmlspecialchars(date('M j', strtotime($reservation['date_from']))); ?> - 
                                            <?php echo htmlspecialchars(date('M j', strtotime($reservation['date_to']))); ?>
                                        </span>
                                        <span class="status <?php echo $reservation['status']; ?>">
                                            <?php echo ucfirst($reservation['status']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function editRoom(roomId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="room_id" value="${roomId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function cancelEdit() {
    window.location.href = window.location.pathname;
}
</script>

<style>
.container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
.btn { background: #007bff; color: white; }
.btn.secondary { background: #6c757d; }
.btn.danger { background: #dc3545; }
.success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
.error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }

.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
.data-table th { background: #f8f9fa; font-weight: bold; }

.status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.inactive { background: #f8d7da; color: #721c24; }

.available { color: #28a745; font-weight: bold; }
.booked { color: #dc3545; font-weight: bold; }

.actions { white-space: nowrap; }
.edit-form { margin: 0; }
.edit-inputs { margin-bottom: 10px; }
.edit-inputs input { margin-bottom: 5px; }
.edit-buttons { display: flex; gap: 5px; }

.checkbox-label { display: flex; align-items: center; gap: 5px; margin: 0; }

.availability-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 15px; }
.room-availability { border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f8f9fa; }
.room-availability h4 { margin: 0 0 10px 0; color: #007bff; }
.room-desc { color: #666; margin: 5px 0; }
.room-capacity { font-weight: bold; margin: 5px 0; }
.reservation-list h5 { margin: 15px 0 10px 0; font-size: 14px; }
.reservation-item { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #eee; }
.reservation-item:last-child { border-bottom: none; }
.dates { font-size: 14px; }
.status { padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.status.pending { background: #fff3cd; color: #856404; }
.status.approved { background: #d4edda; color: #155724; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

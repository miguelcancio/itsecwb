<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/room.php';
require_role('admin');

$message = null; $error = null;
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
            $room ? $message = 'Room created' : $error = 'Create failed';
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
            update_room($roomId, $data) ? $message = 'Room updated' : $error = 'Update failed';
        } else {
            $error = 'Room ID and name are required';
        }
    } elseif ($action === 'delete') {
        $roomId = $_POST['room_id'] ?? '';
        if ($roomId) {
            delete_room($roomId) ? $message = 'Room deleted' : $error = 'Cannot delete room with reservations';
        } else {
            $error = 'Room ID required';
        }
    }
}

$rooms = list_rooms();
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Manage Rooms</h2>
    <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

    <h3>Create Room</h3>
    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="action" value="create">
      <div>
        <label>Room Name</label><br>
        <input type="text" name="name" required maxlength="64" placeholder="e.g., Room 101">
      </div>
      <div>
        <label>Description</label><br>
        <input type="text" name="description" maxlength="255" placeholder="e.g., Single room with AC">
      </div>
      <div>
        <label>Capacity</label><br>
        <input type="number" name="capacity" min="1" max="10" value="1" required>
      </div>
      <button class="btn" type="submit">Create Room</button>
    </form>
  </div>

  <div class="card">
    <h3>Existing Rooms</h3>
    <table>
      <thead><tr><th>Name</th><th>Description</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rooms as $room): ?>
          <tr>
            <td><?php echo htmlspecialchars($room['name']); ?></td>
            <td><?php echo htmlspecialchars($room['description']); ?></td>
            <td><?php echo htmlspecialchars($room['capacity']); ?></td>
            <td><?php echo !empty($room['is_active']) ? 'Active' : 'Inactive'; ?></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
                <input type="text" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required maxlength="64">
                <input type="text" name="description" value="<?php echo htmlspecialchars($room['description']); ?>" maxlength="255">
                <input type="number" name="capacity" value="<?php echo htmlspecialchars($room['capacity']); ?>" min="1" max="10" required>
                <label><input type="checkbox" name="is_active" <?php echo !empty($room['is_active']) ? 'checked' : ''; ?>> Active</label>
                <button class="btn secondary" type="submit">Update</button>
              </form>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
                <button class="btn" type="submit" onclick="return confirm('Delete this room?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

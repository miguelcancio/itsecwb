<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/reservation.php';
require_role(['customer','manager','admin']);

$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $room = $_POST['room'] ?? '';
        $from = $_POST['date_from'] ?? '';
        $to = $_POST['date_to'] ?? '';
        $rec = create_reservation($_SESSION['user']['id'], $room, $from, $to);
        $rec ? $msg = 'Reservation created' : $err = 'Failed to create';
    } elseif ($action === 'delete') {
        $rid = $_POST['reservation_id'] ?? '';
        delete_reservation($rid, $_SESSION['user']['id']) ? $msg = 'Deleted' : $err = 'Delete failed';
    }
}

$reservations = list_reservations_for_user($_SESSION['user']['id']);
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>My Reservations</h2>
    <?php if ($msg): ?><p class="success"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
    <?php if ($err): ?><p class="error"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>
    <h3>Create Reservation</h3>
    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="action" value="create">
      <input type="text" name="room" placeholder="Room" required maxlength="64">
      <input type="date" name="date_from" required>
      <input type="date" name="date_to" required>
      <button class="btn" type="submit">Create</button>
    </form>
  </div>

  <div class="card">
    <h3>Existing</h3>
    <table>
      <thead><tr><th>Room</th><th>From</th><th>To</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($reservations as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['room']); ?></td>
            <td><?php echo htmlspecialchars($r['date_from']); ?></td>
            <td><?php echo htmlspecialchars($r['date_to']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($r['id']); ?>">
                <button class="btn secondary" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>



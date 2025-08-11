<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/reservation.php';
require_role(['manager','admin']);

$message = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    if ($action === 'status') {
        $reservationId = $_POST['reservation_id'] ?? '';
        $status = $_POST['status'] ?? '';
        if ($reservationId && in_array($status, ['pending','approved','rejected','cancelled'], true)) {
            update_reservation_status($reservationId, $status) ? $message = 'Updated' : $error = 'Failed';
        } else { $error = 'Invalid input'; }
    }
}

$reservations = list_all_reservations();
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Manage Reservations</h2>
    <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <table>
      <thead><tr><th>Customer</th><th>Room</th><th>From</th><th>To</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($reservations as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['user_id']); ?></td>
            <td><?php echo htmlspecialchars($r['room']); ?></td>
            <td><?php echo htmlspecialchars($r['date_from']); ?></td>
            <td><?php echo htmlspecialchars($r['date_to']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($r['id']); ?>">
                <select name="status">
                  <?php foreach (['pending','approved','rejected','cancelled'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $r['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn" type="submit">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>



<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/reservation.php';
require_role(['manager','admin']);

$message = null; $error = null;
// Room/month selection for availability overview
$selectedRoomId = $_GET['room_id'] ?? '';
$selectedMonth = (int)($_GET['month'] ?? date('n'));
$selectedYear = (int)($_GET['year'] ?? date('Y'));
if ($selectedMonth < 1) { $selectedMonth = 12; $selectedYear--; }
if ($selectedMonth > 12) { $selectedMonth = 1; $selectedYear++; }
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

$reservations = list_reservations_with_rooms();
// Fetch rooms and availability when a room is selected
require_once __DIR__ . '/../includes/room.php';
$rooms = list_rooms();
$roomAvailability = null;
if ($selectedRoomId) {
    $roomAvailability = get_room_availability_calendar($selectedRoomId, $selectedMonth, $selectedYear);
}
include __DIR__ . '/../includes/header.php';
?>
  <div class="card">
    <h2>Manage Reservations</h2>
    <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <table>
      <thead><tr><th>Customer ID</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($reservations as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['user_id']); ?></td>
            <td>
              <strong><?php echo htmlspecialchars($r['room_name'] ?? 'Unknown Room'); ?></strong>
              <?php if (!empty($r['room_description']) && $r['room_description'] !== 'Legacy room'): ?>
                <br><small><?php echo htmlspecialchars($r['room_description']); ?></small>
              <?php endif; ?>
              <?php if (!empty($r['room_capacity'])): ?>
                <br><small>Capacity: <?php echo htmlspecialchars((string)$r['room_capacity']); ?></small>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($r['date_from']); ?></td>
            <td><?php echo htmlspecialchars($r['date_to']); ?></td>
            <td>
              <span class="status-<?php echo htmlspecialchars($r['status']); ?>">
                <?php echo ucfirst(htmlspecialchars($r['status'])); ?>
              </span>
            </td>
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

  <div class="card">
    <h3>Room Availability Overview</h3>
    <form method="get" class="form-inline" style="margin-bottom: 12px;">
      <label>Room:</label>
      <select name="room_id">
        <option value="">Select room...</option>
        <?php foreach ($rooms as $room): ?>
          <option value="<?php echo htmlspecialchars($room['id']); ?>" <?php echo $selectedRoomId===$room['id']?'selected':''; ?>>
            <?php echo htmlspecialchars($room['name']); ?> (<?php echo !empty($room['is_active'])?'Active':'Inactive'; ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <label>Month:</label>
      <input type="number" name="month" min="1" max="12" value="<?php echo $selectedMonth; ?>">
      <label>Year:</label>
      <input type="number" name="year" min="2000" max="2100" value="<?php echo $selectedYear; ?>">
      <button class="btn secondary" type="submit">View</button>
    </form>

    <?php if ($selectedRoomId && $roomAvailability && empty($roomAvailability['error'])): ?>
      <h4><?php echo htmlspecialchars($roomAvailability['room']['name']); ?> — <?php echo date('F Y', mktime(0,0,0,$selectedMonth,1,$selectedYear)); ?></h4>
      <div class="calendar-grid">
        <div class="calendar-header">
          <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
        </div>
        <div class="calendar-body">
          <?php
          $firstDay = mktime(0,0,0,$selectedMonth,1,$selectedYear);
          $firstDow = date('w',$firstDay);
          $daysInMonth = (int)date('t', $firstDay);
          for ($i=0; $i<$firstDow; $i++) { echo '<div class="calendar-day empty"></div>'; }
          for ($d=1; $d<=$daysInMonth; $d++) {
            $info = $roomAvailability['calendar'][$d] ?? null;
            $isAvailable = $info['available'] ?? false;
            $cls = 'calendar-day' . ($isAvailable ? ' available' : ' unavailable');
            $label = sprintf('%d', $d);
            echo '<div class="'.$cls.'"><span class="day-number">'.$label.'</span></div>';
          }
          ?>
        </div>
      </div>
    <?php elseif ($selectedRoomId && $roomAvailability && !empty($roomAvailability['error'])): ?>
      <p class="error"><?php echo htmlspecialchars($roomAvailability['error']); ?></p>
    <?php else: ?>
      <p>Select a room and month to view availability.</p>
    <?php endif; ?>
  </div>

  <style>
  .form-inline { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .calendar-grid { border:1px solid #ddd; border-radius:8px; overflow:hidden; }
  .calendar-header { display:grid; grid-template-columns:repeat(7,1fr); background:#f8f9fa; }
  .calendar-header > div { padding:10px; text-align:center; font-weight:bold; border-right:1px solid #ddd; }
  .calendar-header > div:last-child { border-right:none; }
  .calendar-body { display:grid; grid-template-columns:repeat(7,1fr); }
  .calendar-day { min-height:60px; padding:6px; border-right:1px solid #ddd; border-bottom:1px solid #ddd; position:relative; }
  .calendar-day:nth-child(7n) { border-right:none; }
  .calendar-day.available { background:#d4edda; }
  .calendar-day.unavailable { background:#f8d7da; }
  .calendar-day.empty { background:#f8f9fa; }
  .day-number { font-weight:bold; font-size:12px; }
  </style>
<?php include __DIR__ . '/../includes/footer.php'; ?>



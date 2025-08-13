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

<div class="manage-reservations-container">
  <div class="card main-card">
    <div class="card-header">
      <h2><i class="fas fa-calendar-alt"></i> Manage Reservations</h2>
      <p class="subtitle">View and manage all room reservations in the system</p>
    </div>
    
    <?php if ($message): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
    <?php endif; ?>

    <div class="table-container">
      <table class="reservations-table">
        <thead>
          <tr>
            <th class="col-customer-id"><i class="fas fa-user"></i> Customer ID</th>
            <th class="col-customer-email"><i class="fas fa-envelope"></i> Customer Email</th>
            <th class="col-room"><i class="fas fa-bed"></i> Room</th>
            <th class="col-checkin"><i class="fas fa-sign-in-alt"></i> Check-in</th>
            <th class="col-checkout"><i class="fas fa-sign-out-alt"></i> Check-out</th>
            <th class="col-status"><i class="fas fa-info-circle"></i> Status</th>
            <th class="col-actions"><i class="fas fa-cogs"></i> Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $r): ?>
            <tr class="reservation-row">
              <td class="customer-id">
                <code><?php echo htmlspecialchars($r['user_id']); ?></code>
              </td>
              <td class="customer-email">
                <a href="mailto:<?php echo htmlspecialchars($r['user_email'] ?? ''); ?>" class="email-link">
                  <?php echo htmlspecialchars($r['user_email'] ?? 'Unknown'); ?>
                </a>
              </td>
              <td class="room-info">
                <div class="room-name">
                  <strong><?php echo htmlspecialchars($r['room_name'] ?? 'Unknown Room'); ?></strong>
                </div>
                <?php if (!empty($r['room_description']) && $r['room_description'] !== 'Legacy room'): ?>
                  <div class="room-description"><?php echo htmlspecialchars($r['room_description']); ?></div>
                <?php endif; ?>
                <?php if (!empty($r['room_capacity'])): ?>
                  <div class="room-capacity">
                    <i class="fas fa-users"></i> <?php echo htmlspecialchars((string)$r['room_capacity']); ?> person<?php echo $r['room_capacity'] > 1 ? 's' : ''; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="check-in">
                <div class="date-display">
                  <span class="date"><?php echo date('M j', strtotime($r['date_from'])); ?></span>
                  <span class="year"><?php echo date('Y', strtotime($r['date_from'])); ?></span>
                </div>
              </td>
              <td class="check-out">
                <div class="date-display">
                  <span class="date"><?php echo date('M j', strtotime($r['date_to'])); ?></span>
                  <span class="year"><?php echo date('Y', strtotime($r['date_to'])); ?></span>
                </div>
              </td>
              <td class="status-cell">
                <span class="status-badge status-<?php echo htmlspecialchars($r['status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($r['status'])); ?>
                </span>
              </td>
              <td class="actions-cell">
                <form method="post" class="status-form">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="status">
                  <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($r['id']); ?>">
                  <div class="status-controls">
                    <select name="status" class="status-select">
                      <?php foreach (['pending','approved','rejected','cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $r['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary btn-save" type="submit">
                      <i class="fas fa-save"></i> Save
                    </button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card availability-card">
    <div class="card-header">
      <h3><i class="fas fa-calendar-check"></i> Room Availability Overview</h3>
      <p class="subtitle">Check room availability for specific dates</p>
    </div>
    
    <form method="get" class="availability-form">
      <div class="form-row">
        <div class="form-group">
          <label for="room-select"><i class="fas fa-bed"></i> Room:</label>
          <select id="room-select" name="room_id" class="form-control">
            <option value="">Select room...</option>
            <?php foreach ($rooms as $room): ?>
              <option value="<?php echo htmlspecialchars($room['id']); ?>" <?php echo $selectedRoomId===$room['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($room['name']); ?> 
                <span class="room-status <?php echo !empty($room['is_active'])?'active':'inactive'; ?>">
                  (<?php echo !empty($room['is_active'])?'Active':'Inactive'; ?>)
                </span>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="month-select"><i class="fas fa-calendar"></i> Month:</label>
          <input type="number" id="month-select" name="month" min="1" max="12" value="<?php echo $selectedMonth; ?>" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="year-select"><i class="fas fa-calendar-alt"></i> Year:</label>
          <input type="number" id="year-select" name="year" min="2000" max="2100" value="<?php echo $selectedYear; ?>" class="form-control">
        </div>
        
        <div class="form-group">
          <button class="btn btn-secondary btn-view" type="submit">
            <i class="fas fa-eye"></i> View
          </button>
        </div>
      </div>
    </form>

    <?php if ($selectedRoomId && $roomAvailability && empty($roomAvailability['error'])): ?>
      <div class="availability-section">
        <h4 class="room-title">
          <i class="fas fa-bed"></i> 
          <?php echo htmlspecialchars($roomAvailability['room']['name']); ?> 
          <span class="month-year">— <?php echo date('F Y', mktime(0,0,0,$selectedMonth,1,$selectedYear)); ?></span>
        </h4>
        
        <div class="calendar-container">
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
          
          <div class="calendar-legend">
            <div class="legend-item">
              <span class="legend-color available"></span>
              <span>Available</span>
            </div>
            <div class="legend-item">
              <span class="legend-color unavailable"></span>
              <span>Unavailable</span>
            </div>
          </div>
        </div>
      </div>
    <?php elseif ($selectedRoomId && $roomAvailability && !empty($roomAvailability['error'])): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <span><?php echo htmlspecialchars($roomAvailability['error']); ?></span>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-calendar-plus"></i>
        <p>Select a room and month to view availability.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
/* Main Container */
.manage-reservations-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

/* Card Styling */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
  margin-bottom: 24px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

.main-card {
  margin-bottom: 32px;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 24px 32px;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h2, .card-header h3 {
  margin: 0 0 8px 0;
  font-size: 1.5rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-header .subtitle {
  margin: 0;
  opacity: 0.9;
  font-size: 0.95rem;
}

/* Alerts */
.alert {
  padding: 16px 24px;
  margin: 24px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}

.alert-success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.alert-error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

/* Table Styling */
.table-container {
  overflow-x: auto;
  margin: 24px;
}

.reservations-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
  table-layout: fixed;
  border: 1px solid #e5e7eb;
}

.reservations-table th {
  background: #f8fafc;
  padding: 16px 12px;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  white-space: nowrap;
  height: 60px;
  vertical-align: middle;
}

.reservations-table th:last-child {
  border-right: none;
}

.reservations-table th i {
  color: #6b7280;
  font-size: 0.8rem;
}

.reservations-table td {
  padding: 16px 12px;
  border-bottom: 1px solid #f3f4f6;
  border-right: 1px solid #f3f4f6;
  vertical-align: top;
  height: 80px;
}

.reservations-table td:last-child {
  border-right: none;
}

.reservation-row:hover {
  background: #f9fafb;
  transition: background-color 0.2s ease;
}

/* Column Widths */
.col-customer-id { width: 200px; }
.col-customer-email { width: 220px; }
.col-room { width: 250px; }
.col-checkin { width: 120px; }
.col-checkout { width: 120px; }
.col-status { width: 120px; }
.col-actions { width: 180px; }

/* Customer ID */
.customer-id code {
  background: #f3f4f6;
  padding: 6px 10px;
  border-radius: 6px;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.75rem;
  color: #374151;
  display: block;
  word-break: break-all;
  line-height: 1.3;
}

/* Customer Email */
.customer-email .email-link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
  display: block;
  word-break: break-all;
}

.customer-email .email-link:hover {
  text-decoration: underline;
  color: #2563eb;
}

/* Room Info */
.room-info .room-name {
  margin-bottom: 6px;
  color: #111827;
  font-size: 0.95rem;
}

.room-info .room-description {
  color: #6b7280;
  font-size: 0.8rem;
  margin-bottom: 6px;
  line-height: 1.3;
}

.room-info .room-capacity {
  color: #059669;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Date Display */
.date-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.date-display .date {
  font-weight: 600;
  color: #111827;
  font-size: 0.9rem;
}

.date-display .year {
  color: #6b7280;
  font-size: 0.75rem;
}

/* Status Badges */
.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: inline-block;
  text-align: center;
  min-width: 80px;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.status-cancelled {
  background: #f3f4f6;
  color: #374151;
}

/* Actions */
.status-controls {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 140px;
}

.status-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.85rem;
  background: white;
  width: 100%;
}

.btn-save {
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
}

.btn-save:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

/* Availability Form */
.availability-form {
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.form-row {
  display: flex;
  gap: 20px;
  align-items: end;
  flex-wrap: wrap;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 150px;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-control {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9rem;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-view {
  padding: 10px 20px;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-view:hover {
  background: #4b5563;
  transform: translateY(-1px);
}

/* Room Status in Select */
.room-status.active {
  color: #059669;
}

.room-status.inactive {
  color: #dc2626;
}

/* Availability Section */
.availability-section {
  padding: 24px;
}

.room-title {
  margin: 0 0 20px 0;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 12px;
}

.room-title .month-year {
  color: #6b7280;
  font-weight: 400;
}

/* Calendar */
.calendar-container {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.calendar-grid {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.calendar-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

.calendar-header > div {
  padding: 12px 8px;
  text-align: center;
  font-weight: 600;
  color: #374151;
  font-size: 0.85rem;
  border-right: 1px solid #e5e7eb;
}

.calendar-header > div:last-child {
  border-right: none;
}

.calendar-body {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
  min-height: 50px;
  padding: 8px;
  border-right: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.calendar-day:nth-child(7n) {
  border-right: none;
}

.calendar-day.available {
  background: #d1fae5;
}

.calendar-day.unavailable {
  background: #fee2e2;
}

.calendar-day.empty {
  background: #f9fafb;
}

.day-number {
  font-weight: 600;
  font-size: 0.9rem;
  color: #374151;
}

/* Calendar Legend */
.calendar-legend {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  color: #374151;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.legend-color.available {
  background: #d1fae5;
}

.legend-color.unavailable {
  background: #fee2e2;
}

/* Empty State */
.empty-state {
  padding: 48px 24px;
  text-align: center;
  color: #6b7280;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 1.1rem;
}

/* Button Base Styles */
.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .form-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .form-group {
    min-width: auto;
  }
  
  .calendar-container {
    flex-direction: column;
  }
  
  .col-customer-id { width: 180px; }
  .col-customer-email { width: 200px; }
  .col-room { width: 220px; }
}

@media (max-width: 768px) {
  .manage-reservations-container {
    padding: 16px;
  }
  
  .card-header {
    padding: 20px 24px;
  }
  
  .table-container {
    margin: 16px;
  }
  
  .reservations-table {
    font-size: 0.8rem;
  }
  
  .reservations-table th,
  .reservations-table td {
    padding: 12px 8px;
  }
  
  .status-controls {
    min-width: 120px;
  }
  
  .col-customer-id { width: 150px; }
  .col-customer-email { width: 170px; }
  .col-room { width: 190px; }
  .col-checkin { width: 100px; }
  .col-checkout { width: 100px; }
  .col-status { width: 100px; }
  .col-actions { width: 150px; }
}

/* Smooth Transitions */
* {
  transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>



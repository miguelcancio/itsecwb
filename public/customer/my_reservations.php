<?php
declare(strict_types=1);

// Enable debug mode for troubleshooting
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/room.php';
require_role(['customer','manager','admin']);

$msg = null; 
$err = null;
$selectedRoomId = $_GET['room_id'] ?? '';
$selectedMonth = (int)($_GET['month'] ?? date('n'));
$selectedYear = (int)($_GET['year'] ?? date('Y'));

// Fix month/year boundaries
if ($selectedMonth < 1) {
    $selectedMonth = 12;
    $selectedYear--;
}
if ($selectedMonth > 12) {
    $selectedMonth = 1;
    $selectedYear++;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/validation.php';
    require_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $roomId = $_POST['room_id'] ?? '';
        $from = $_POST['date_from'] ?? '';
        $to = $_POST['date_to'] ?? '';
        
        if ($roomId && $from && $to) {
            error_log("Attempting to create reservation: Room ID: $roomId, From: $from, To: $to, User: " . $_SESSION['user']['id']);
            
            $rec = create_reservation($_SESSION['user']['id'], $roomId, $from, $to);
            if ($rec) {
                $msg = 'Reservation created successfully!';
                // Redirect to refresh the page
                header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode('Reservation created successfully!'));
                exit;
            } else {
                $debug = debug_room_availability($roomId, $from, $to);
                error_log("Reservation failed. Debug info: " . json_encode($debug));
                
                $err = 'Room not available on selected dates. ';
                if (!empty($debug['conflicts']['by_room_id']) || !empty($debug['conflicts']['by_room_name'])) {
                    $err .= 'Conflicts found with existing reservations.';
                } else {
                    $err .= 'Please check your dates.';
                }
            }
        } else {
            $err = 'Please select room and dates';
        }
    } elseif ($action === 'delete') {
        $rid = $_POST['reservation_id'] ?? '';
        if (delete_reservation($rid, $_SESSION['user']['id'])) {
            $msg = 'Reservation cancelled successfully';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode('Reservation cancelled successfully'));
            exit;
        } else {
            $err = 'Failed to cancel reservation';
        }
    }
}

// Get message from URL if redirected
if (empty($msg) && isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}

$reservations = get_user_reservations_with_rooms($_SESSION['user']['id']);
$availableRooms = get_active_rooms();

// Get availability for selected room
$roomAvailability = null;
if ($selectedRoomId) {
    $roomAvailability = get_room_availability_calendar($selectedRoomId, $selectedMonth, $selectedYear);
    
    // Debug: Test the function directly
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("Testing room availability for room ID: $selectedRoomId, month: $selectedMonth, year: $selectedYear");
        error_log("Result: " . json_encode($roomAvailability));
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>My Reservations</h2>
        <?php if ($msg): ?><p class="success"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
        <?php if ($err): ?><p class="error"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>
        
        <!-- Debug Information (remove in production) -->
        <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
            <div class="debug-info">
                <h4>Debug Info:</h4>
                <p><strong>Available Rooms:</strong> <?php echo count($availableRooms); ?></p>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user']['id'] ?? 'Not set'); ?></p>
                <p><strong>Selected Room ID:</strong> <?php echo htmlspecialchars($selectedRoomId ?: 'None'); ?></p>
                <p><strong>Selected Month/Year:</strong> <?php echo $selectedMonth . '/' . $selectedYear; ?></p>
                <p><strong>Current URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown'); ?></p>
                <p><strong>GET Parameters:</strong> <?php echo htmlspecialchars(json_encode($_GET)); ?></p>
                <?php if (!empty($availableRooms)): ?>
                    <p><strong>First Room:</strong> ID: <?php echo htmlspecialchars($availableRooms[0]['id'] ?? 'No ID'); ?>, Name: <?php echo htmlspecialchars($availableRooms[0]['name'] ?? 'No name'); ?></p>
                <?php endif; ?>
                <?php if ($selectedRoomId): ?>
                    <p><strong>Room Availability Result:</strong> <?php echo $roomAvailability ? 'Success' : 'Failed'; ?></p>
                    <?php if ($roomAvailability && isset($roomAvailability['error'])): ?>
                        <p><strong>Error:</strong> <?php echo htmlspecialchars($roomAvailability['error']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($availableRooms)): ?>
        <div class="card">
            <h3>Room Availability & Booking</h3>
            
            <!-- Room Selection -->
            <div class="room-selection">
                <label><strong>Select a Room:</strong></label>
                <select id="roomSelector" onchange="changeRoom(this.value)">
                    <option value="">Choose a room to see availability...</option>
                    <?php foreach ($availableRooms as $room): ?>
                        <option value="<?php echo htmlspecialchars($room['id']); ?>" <?php echo $selectedRoomId === $room['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($room['name']); ?> 
                            (<?php echo htmlspecialchars((string)($room['capacity'])); ?> person<?php echo $room['capacity'] > 1 ? 's' : ''; ?>)
                            <?php if (!empty($room['description'])): ?> - <?php echo htmlspecialchars($room['description']); ?><?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Test Button -->
                <div style="margin-top: 10px;">
                    <button type="button" onclick="testRoomSelection()" class="btn secondary">Test Room Selection</button>
                    <span id="testResult" style="margin-left: 10px; font-size: 12px;"></span>
                </div>
                
                <!-- Direct Room Links for Testing -->
                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                    <p style="margin: 0 0 10px 0; font-size: 14px;"><strong>Quick Test Links:</strong></p>
                    <?php foreach (array_slice($availableRooms, 0, 3) as $room): ?>
                        <a href="?room_id=<?php echo urlencode($room['id']); ?>" class="btn secondary" style="margin-right: 5px; margin-bottom: 5px; display: inline-block;">
                            Test <?php echo htmlspecialchars($room['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($selectedRoomId && $roomAvailability && !isset($roomAvailability['error'])): ?>
                <!-- Room Availability Calendar -->
                <div class="availability-section">
                    <h4><?php echo htmlspecialchars($roomAvailability['room']['name']); ?> - Availability Calendar</h4>
                    
                    <!-- Month Navigation -->
                    <div class="month-navigation">
                        <?php 
                        $prevMonth = $selectedMonth - 1;
                        $prevYear = $selectedYear;
                        if ($prevMonth < 1) {
                            $prevMonth = 12;
                            $prevYear--;
                        }
                        ?>
                        <a href="?room_id=<?php echo urlencode($selectedRoomId); ?>&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn secondary">
                            ← Previous Month
                        </a>
                        <span class="current-month">
                            <?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>
                        </span>
                        <?php 
                        $nextMonth = $selectedMonth + 1;
                        $nextYear = $selectedYear;
                        if ($nextMonth > 12) {
                            $nextMonth = 1;
                            $nextYear++;
                        }
                        ?>
                        <a href="?room_id=<?php echo urlencode($selectedRoomId); ?>&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn secondary">
                            Next Month →
                        </a>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="calendar-grid">
                        <div class="calendar-header">
                            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                        </div>
                        <div class="calendar-body">
                            <?php
                            $firstDay = mktime(0, 0, 0, $selectedMonth, 1, $selectedYear);
                            $firstDayOfWeek = date('w', $firstDay);
                            $daysInMonth = date('t', $firstDay);
                            
                            // Add empty cells for days before the first of the month
                            for ($i = 0; $i < $firstDayOfWeek; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }
                            
                            // Add days of the month
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $date = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                                $isToday = $date === date('Y-m-d');
                                $isPast = $date < date('Y-m-d');
                                $isAvailable = $roomAvailability['calendar'][$day]['available'] ?? false;
                                $conflictInfo = $roomAvailability['calendar'][$day]['conflict_info'] ?? '';
                                
                                $dayClass = 'calendar-day';
                                if ($isToday) $dayClass .= ' today';
                                if ($isPast) $dayClass .= ' past';
                                if (!$isAvailable) $dayClass .= ' unavailable';
                                if ($isAvailable) $dayClass .= ' available';
                                
                                echo '<div class="' . $dayClass . '" title="' . htmlspecialchars($date . ($conflictInfo ? ' - ' . $conflictInfo : '')) . '">';
                                echo '<span class="day-number">' . $day . '</span>';
                                if (!$isAvailable && $conflictInfo) {
                                    echo '<div class="conflict-info">' . htmlspecialchars($conflictInfo) . '</div>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Quick Booking Form -->
                    <div class="quick-booking">
                        <h5>Quick Book Available Dates</h5>
                        <form method="post" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($selectedRoomId); ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Check-in Date</label>
                                    <input type="date" name="date_from" id="dateFrom" required min="<?php echo date('Y-m-d'); ?>" onchange="updateMinCheckout()">
                                </div>
                                <div class="form-group">
                                    <label>Check-out Date</label>
                                    <input type="date" name="date_to" id="dateTo" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button class="btn" type="submit">Book These Dates</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($selectedRoomId && isset($roomAvailability['error'])): ?>
                <p class="error"><?php echo htmlspecialchars($roomAvailability['error']); ?></p>
            <?php elseif ($selectedRoomId): ?>
                <!-- Simple Room Info Display (for testing) -->
                <div class="room-info">
                    <h4>Room Information</h4>
                    <?php 
                    $selectedRoom = null;
                    foreach ($availableRooms as $room) {
                        if ($room['id'] === $selectedRoomId) {
                            $selectedRoom = $room;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($selectedRoom): ?>
                        <p><strong>Room:</strong> <?php echo htmlspecialchars($selectedRoom['name']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($selectedRoom['description'] ?? 'No description'); ?></p>
                        <p><strong>Capacity:</strong> <?php echo htmlspecialchars((string)($selectedRoom['capacity'])); ?> person<?php echo $selectedRoom['capacity'] > 1 ? 's' : ''; ?></p>
                        
                        <!-- Simple Booking Form -->
                        <div class="simple-booking">
                            <h5>Book This Room</h5>
                            <form method="post" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($selectedRoomId); ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Check-in Date</label>
                                        <input type="date" name="date_from" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Check-out Date</label>
                                        <input type="date" name="date_to" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button class="btn" type="submit">Book Room</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Debug Info -->
                        <div class="debug-info">
                            <h5>Debug: Room Availability Function</h5>
                            <p><strong>Function Result:</strong> <?php echo $roomAvailability ? 'Success' : 'Failed'; ?></p>
                            <p><strong>Raw Result:</strong> <pre><?php echo var_export($roomAvailability, true); ?></pre></p>
                        </div>
                    <?php else: ?>
                        <p class="error">Selected room not found in available rooms list.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="info">Select a room above to see its availability calendar and book your stay.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <p class="error">No rooms are currently available for reservation.</p>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>My Existing Reservations</h3>
        <?php if (empty($reservations)): ?>
            <p>No reservations yet.</p>
        <?php else: ?>
            <table class="reservations-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Nights</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($r['room_name'] ?? 'Unknown Room'); ?></strong>
                                <?php if (!empty($r['room_description']) && $r['room_description'] !== 'Legacy room'): ?>
                                    <br><small><?php echo htmlspecialchars($r['room_description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($r['date_from']))); ?></td>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($r['date_to']))); ?></td>
                            <td><?php echo (strtotime($r['date_to']) - strtotime($r['date_from'])) / (24 * 60 * 60); ?> nights</td>
                            <td>
                                <span class="status-<?php echo htmlspecialchars($r['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($r['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Cancel this reservation?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($r['id']); ?>">
                                        <button class="btn secondary" type="submit">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <em>No actions available</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function changeRoom(roomId) {
    console.log('changeRoom called with:', roomId);
    if (roomId) {
        const url = '?room_id=' + encodeURIComponent(roomId);
        console.log('Redirecting to:', url);
        window.location.href = url;
    }
}

function updateMinCheckout() {
    const checkin = document.getElementById('dateFrom').value;
    if (checkin) {
        const checkout = document.getElementById('dateTo');
        const minCheckout = new Date(checkin);
        minCheckout.setDate(minCheckout.getDate() + 1);
        checkout.min = minCheckout.toISOString().split('T')[0];
        
        // If current checkout date is before new minimum, update it
        if (checkout.value && checkout.value <= checkin) {
            checkout.value = checkout.min;
        }
    }
}

function testRoomSelection() {
    const selectedRoomId = document.getElementById('roomSelector').value;
    const testResultSpan = document.getElementById('testResult');
    if (selectedRoomId) {
        testResultSpan.textContent = 'Selected Room ID: ' + selectedRoomId;
        testResultSpan.style.color = 'green';
    } else {
        testResultSpan.textContent = 'No room selected.';
        testResultSpan.style.color = 'red';
    }
}

// Debug: Log when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded');
    console.log('Current URL:', window.location.href);
    console.log('Room selector value:', document.getElementById('roomSelector').value);
});
</script>

<style>
.container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

.room-selection { margin-bottom: 20px; }
.room-selection select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }

.availability-section { margin-top: 20px; }
.month-navigation { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 20px; }
.current-month { font-size: 18px; font-weight: bold; }

.calendar-grid { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
.calendar-header { display: grid; grid-template-columns: repeat(7, 1fr); background: #f8f9fa; }
.calendar-header > div { padding: 10px; text-align: center; font-weight: bold; border-right: 1px solid #ddd; }
.calendar-header > div:last-child { border-right: none; }

.calendar-body { display: grid; grid-template-columns: repeat(7, 1fr); }
.calendar-day { 
    min-height: 80px; 
    padding: 5px; 
    border-right: 1px solid #ddd; 
    border-bottom: 1px solid #ddd; 
    position: relative;
    cursor: pointer;
}
.calendar-day:nth-child(7n) { border-right: none; }
.calendar-day.empty { background: #f8f9fa; }
.calendar-day.today { background: #e3f2fd; font-weight: bold; }
.calendar-day.past { background: #f5f5f5; color: #999; }
.calendar-day.available { background: #d4edda; }
.calendar-day.unavailable { background: #f8d7da; color: #721c24; }
.calendar-day:hover { opacity: 0.8; }

.day-number { font-size: 14px; font-weight: bold; }
.conflict-info { font-size: 10px; color: #721c24; margin-top: 2px; }

.quick-booking { margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
.quick-booking h5 { margin-top: 0; }
.form-row { display: flex; gap: 15px; align-items: end; }
.form-group { flex: 1; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }

.btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
.btn { background: #007bff; color: white; }
.btn.secondary { background: #6c757d; }

.success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
.error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
.info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 15px; }

.reservations-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.reservations-table th, .reservations-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
.reservations-table th { background: #f8f9fa; font-weight: bold; }

.status-pending { color: #856404; background: #fff3cd; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
.status-approved { color: #155724; background: #d4edda; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
.status-rejected { color: #721c24; background: #f8d7da; padding: 2px 6px; border-radius: 4px; font-size: 12px; }

.debug-info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px; }

@media (max-width: 768px) {
    .form-row { flex-direction: column; }
    .month-navigation { flex-direction: column; gap: 10px; }
    .calendar-day { min-height: 60px; font-size: 12px; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>



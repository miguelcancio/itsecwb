<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/supabase.php';
require_once __DIR__ . '/validation.php';

function list_reservations_for_user(string $userId): array {
    return sb_get('reservations', ['user_id' => $userId], 100, 0, '*');
}

function list_all_reservations(): array {
    return sb_get('reservations', [], 200, 0, '*');
}

function get_room_availability_calendar(string $roomId, int $month = null, int $year = null): array {
    require_once __DIR__ . '/room.php';
    $room = get_room_by_id($roomId);
    if (!$room || empty($room['is_active'])) {
        return ['error' => 'Room not found or inactive'];
    }
    
    // Default to current month if not specified
    if ($month === null) $month = (int)date('n');
    if ($year === null) $year = (int)date('Y');
    
    // Get all reservations for this room in the specified month
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    // Compute last day of selected month robustly
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $reservations = sb_get('reservations', [
        'room_id' => $roomId,
        'status' => ['in' => ['pending', 'approved']],
        'date_from' => ['lte' => $endDate],
        'date_to' => ['gte' => $startDate]
    ], 100, 0, 'date_from,date_to');
    
    // Create calendar array
    $calendar = [];
    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isAvailable = true;
        $conflictInfo = '';
        
        // Check if this date conflicts with any reservation
        foreach ($reservations as $reservation) {
            $resFrom = $reservation['date_from'];
            $resTo = $reservation['date_to'];
            
            if ($date >= $resFrom && $date < $resTo) {
                $isAvailable = false;
                $conflictInfo = "Booked until " . date('M j', strtotime($resTo));
                break;
            }
        }
        
        $calendar[$day] = [
            'date' => $date,
            'available' => $isAvailable,
            'conflict_info' => $conflictInfo,
            'day_of_week' => date('D', strtotime($date))
        ];
    }
    
    return [
        'room' => $room,
        'month' => $month,
        'year' => $year,
        'calendar' => $calendar,
        'reservations' => $reservations
    ];
}

function find_available_dates(string $roomId, int $daysAhead = 30): array {
    require_once __DIR__ . '/room.php';
    $room = get_room_by_id($roomId);
    if (!$room || empty($room['is_active'])) {
        return ['error' => 'Room not found or inactive'];
    }
    
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$daysAhead days"));
    
    // Get all reservations for this room in the date range
    $reservations = sb_get('reservations', [
        'room_id' => $roomId,
        'status' => ['in' => ['pending', 'approved']],
        'date_from' => ['lte' => $endDate],
        'date_to' => ['gte' => $startDate]
    ], 100, 0, 'date_from,date_to');
    
    // Find available date ranges
    $availableRanges = [];
    $currentDate = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    
    while ($currentDate <= $endTimestamp) {
        $dateStr = date('Y-m-d', $currentDate);
        $isAvailable = true;
        
        // Check if this date conflicts with any reservation
        foreach ($reservations as $reservation) {
            if ($dateStr >= $reservation['date_from'] && $dateStr < $reservation['date_to']) {
                $isAvailable = false;
                break;
            }
        }
        
        if ($isAvailable) {
            // Find consecutive available dates
            $rangeStart = $dateStr;
            $rangeEnd = $dateStr;
            
            while ($currentDate <= $endTimestamp) {
                $nextDate = strtotime('+1 day', $currentDate);
                $nextDateStr = date('Y-m-d', $nextDate);
                
                $nextIsAvailable = true;
                foreach ($reservations as $reservation) {
                    if ($nextDateStr >= $reservation['date_from'] && $nextDateStr < $reservation['date_to']) {
                        $nextIsAvailable = false;
                        break;
                    }
                }
                
                if (!$nextIsAvailable) {
                    break;
                }
                
                $rangeEnd = $nextDateStr;
                $currentDate = $nextDate;
            }
            
            $availableRanges[] = [
                'start' => $rangeStart,
                'end' => $rangeEnd,
                'nights' => (strtotime($rangeEnd) - strtotime($rangeStart)) / (24 * 60 * 60)
            ];
        }
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
    return [
        'room' => $room,
        'available_ranges' => $availableRanges,
        'total_available_nights' => array_sum(array_column($availableRanges, 'nights'))
    ];
}

function debug_room_availability(string $roomId, string $dateFrom, string $dateTo): array {
    require_once __DIR__ . '/room.php';
    $room = get_room_by_id($roomId);
    if (!$room) {
        return ['error' => 'Room not found'];
    }
    
    $debug = [
        'room' => $room,
        'requested_dates' => ['from' => $dateFrom, 'to' => $dateTo],
        'conflicts' => []
    ];
    
    // Check new room_id based reservations
    $newConflicts = sb_get('reservations', [
        'room_id' => $roomId,
        'status' => ['in' => ['pending', 'approved']]
    ], 100, 0, '*');
    $debug['conflicts']['by_room_id'] = $newConflicts;
    
    // Check old room text based reservations
    $oldConflicts = sb_get('reservations', [
        'room' => $room['name'],
        'status' => ['in' => ['pending', 'approved']]
    ], 100, 0, '*');
    $debug['conflicts']['by_room_name'] = $oldConflicts;
    
    return $debug;
}

/**
 * Normalize date input into ISO format (Y-m-d) accepting common inputs.
 * Returns null when not parseable.
 */
function normalize_date_input(string $input): ?string {
    $input = trim($input);
    if ($input === '') { return null; }
    // ISO direct
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input) === 1) {
        return $input;
    }
    $formats = ['m/d/Y', 'd/m/Y', 'Y/m/d', 'Y.m.d', 'm-d-Y', 'd-m-Y'];
    foreach ($formats as $fmt) {
        $dt = \DateTime::createFromFormat($fmt, $input);
        if ($dt instanceof \DateTime) {
            $errors = \DateTime::getLastErrors();
            if ((int)($errors['warning_count'] ?? 0) === 0 && (int)($errors['error_count'] ?? 0) === 0) {
                return $dt->format('Y-m-d');
            }
        }
    }
    // Fallback to strtotime for lenient parsing
    $ts = strtotime($input);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }
    return null;
}

function create_reservation(string $userId, string $roomId, string $dateFrom, string $dateTo): ?array {
    // Validate room exists and is active
    require_once __DIR__ . '/room.php';
    $room = get_room_by_id($roomId);
    if (!$room || empty($room['is_active'])) {
        return null;
    }
    
    // Normalize and validate dates
    $dateFromIso = normalize_date_input($dateFrom);
    $dateToIso = normalize_date_input($dateTo);
    if ($dateFromIso === null || $dateToIso === null) { return null; }
    $fromTs = strtotime($dateFromIso);
    $toTs = strtotime($dateToIso);
    if ($fromTs === false || $toTs === false) { return null; }
    // Require at least 1 night: check-out must be strictly after check-in
    if ($fromTs >= $toTs) { return null; }
    // Do not allow past check-in
    $today = strtotime(date('Y-m-d'));
    if ($fromTs < $today) { return null; }
    
    // Check for date conflicts - check both room_id and room columns
    $conflicts = [];
    
    // Check new room_id based reservations
    $newConflicts = sb_get('reservations', [
        'room_id' => $roomId,
        'status' => ['in' => ['pending', 'approved']],
        'date_from' => ['lte' => $dateToIso],
        'date_to' => ['gte' => $dateFromIso]
    ], 100, 0, 'date_from,date_to');
    $conflicts = array_merge($conflicts, $newConflicts);
    
    // Check old room text based reservations (if room name matches)
    $oldConflicts = sb_get('reservations', [
        'room' => $room['name'],
        'status' => ['in' => ['pending', 'approved']],
        'date_from' => ['lte' => $dateToIso],
        'date_to' => ['gte' => $dateFromIso]
    ], 100, 0, 'date_from,date_to');
    $conflicts = array_merge($conflicts, $oldConflicts);
    
    // Check for overlapping dates
    $requestFrom = $fromTs;
    $requestTo = $toTs;
    
    foreach ($conflicts as $conflict) {
        $conflictFrom = strtotime($conflict['date_from']);
        $conflictTo = strtotime($conflict['date_to']);
        
        // Check if dates overlap (any overlap means conflict)
        if (($requestFrom < $conflictTo) && ($requestTo > $conflictFrom)) {
            return null; // Room not available on these dates
        }
    }
    
    $rec = sb_insert('reservations', [
        'user_id' => $userId,
        'room_id' => $roomId,
        // Include legacy text column for backward compatibility with existing constraints/views
        'room' => $room['name'],
        'date_from' => $dateFromIso,
        'date_to' => $dateToIso,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'status' => 'pending'
    ]);
    return $rec ?: null;
}

function update_reservation_status(string $reservationId, string $status): bool {
    $validatedStatus = validate_user_input_text($status, 32);
    if (!$validatedStatus) {
        return false;
    }
    return sb_update('reservations', ['id' => $reservationId], ['status' => $validatedStatus, 'updated_at' => date('c')]);
}

function delete_reservation(string $reservationId, string $userId): bool {
    // Enforce ownership on delete by passing both filters
    return sb_delete('reservations', ['id' => $reservationId, 'user_id' => $userId]);
}

function get_reservation_with_room(string $reservationId): ?array {
    $rows = sb_get('reservations', ['id' => $reservationId], 1);
    if (empty($rows)) { return null; }
    
    $reservation = $rows[0];
    require_once __DIR__ . '/room.php';
    
    // Handle both old room text and new room_id
    if (!empty($reservation['room_id'])) {
        $room = get_room_by_id($reservation['room_id']);
        if ($room) {
            $reservation['room_name'] = $room['name'];
            $reservation['room_description'] = $room['description'];
            $reservation['room_capacity'] = $room['capacity'];
        }
    } elseif (!empty($reservation['room'])) {
        // Fallback for old room text
        $reservation['room_name'] = $reservation['room'];
        $reservation['room_description'] = 'Legacy room';
        $reservation['room_capacity'] = 1;
    }
    
    return $reservation;
}

function list_reservations_with_rooms(): array {
    $reservations = list_all_reservations();
    require_once __DIR__ . '/room.php';
    require_once __DIR__ . '/user.php';
    
    foreach ($reservations as &$reservation) {
        // Handle both old room text and new room_id
        if (!empty($reservation['room_id'])) {
            $room = get_room_by_id($reservation['room_id']);
            if ($room) {
                $reservation['room_name'] = $room['name'];
                $reservation['room_description'] = $room['description'];
                $reservation['room_capacity'] = $room['capacity'];
            }
        } elseif (!empty($reservation['room'])) {
            // Fallback for old room text
            $reservation['room_name'] = $reservation['room'];
            $reservation['room_description'] = 'Legacy room';
            $reservation['room_capacity'] = 1;
        }
        
        // Add user email information
        if (!empty($reservation['user_id'])) {
            $user = get_user_by_id($reservation['user_id']);
            if ($user) {
                $reservation['user_email'] = $user['email'];
            } else {
                $reservation['user_email'] = 'Unknown User';
            }
        } else {
            $reservation['user_email'] = 'No User ID';
        }
    }
    
    return $reservations;
}

function get_user_reservations_with_rooms(string $userId): array {
    $reservations = list_reservations_for_user($userId);
    require_once __DIR__ . '/room.php';
    require_once __DIR__ . '/user.php';
    
    foreach ($reservations as &$reservation) {
        // Handle both old room text and new room_id
        if (!empty($reservation['room_id'])) {
            $room = get_room_by_id($reservation['room_id']);
            if ($room) {
                $reservation['room_name'] = $room['name'];
                $reservation['room_description'] = $room['description'];
                $reservation['room_capacity'] = $room['capacity'];
            }
        } elseif (!empty($reservation['room'])) {
            // Fallback for old room text
            $reservation['room_name'] = $reservation['room'];
            $reservation['room_description'] = 'Legacy room';
            $reservation['room_capacity'] = 1;
        }
        
        // Add user email information
        if (!empty($reservation['user_id'])) {
            $user = get_user_by_id($reservation['user_id']);
            if ($user) {
                $reservation['user_email'] = $user['email'];
            } else {
                $reservation['user_email'] = 'Unknown User';
            }
        } else {
            $reservation['user_email'] = 'No User ID';
        }
    }
    
    return $reservations;
}



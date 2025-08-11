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

function create_reservation(string $userId, string $roomId, string $dateFrom, string $dateTo): ?array {
    // Validate room exists and is active
    require_once __DIR__ . '/room.php';
    $room = get_room_by_id($roomId);
    if (!$room || empty($room['is_active'])) {
        return null;
    }
    
    // Dates as ISO yyyy-mm-dd
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) { return null; }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) { return null; }
    if (strtotime($dateFrom) === false || strtotime($dateTo) === false) { return null; }
    if (strtotime($dateFrom) > strtotime($dateTo)) { return null; }
    
    // Check for date conflicts (room not available on those dates)
    $conflicts = sb_get('reservations', [
        'room_id' => $roomId,
        'status' => ['in' => ['pending', 'approved']]
    ], 1, 0, 'date_from,date_to');
    
    foreach ($conflicts as $conflict) {
        $conflictFrom = strtotime($conflict['date_from']);
        $conflictTo = strtotime($conflict['date_to']);
        $requestFrom = strtotime($dateFrom);
        $requestTo = strtotime($dateTo);
        
        // Check if dates overlap
        if (($requestFrom <= $conflictTo) && ($requestTo >= $conflictFrom)) {
            return null; // Room not available
        }
    }
    
    $rec = sb_insert('reservations', [
        'user_id' => $userId,
        'room_id' => $roomId,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'status' => 'pending'
    ]);
    return $rec ?: null;
}

function update_reservation_status(string $reservationId, string $status): bool {
    $status = sanitize_text($status, 32);
    return sb_update('reservations', ['id' => $reservationId], ['status' => $status, 'updated_at' => gmdate('c')]);
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
    $room = get_room_by_id($reservation['room_id']);
    if ($room) {
        $reservation['room_name'] = $room['name'];
        $reservation['room_description'] = $room['description'];
        $reservation['room_capacity'] = $room['capacity'];
    }
    return $reservation;
}

function list_reservations_with_rooms(): array {
    $reservations = list_all_reservations();
    require_once __DIR__ . '/room.php';
    
    foreach ($reservations as &$reservation) {
        $room = get_room_by_id($reservation['room_id']);
        if ($room) {
            $reservation['room_name'] = $room['name'];
            $reservation['room_description'] = $room['description'];
            $reservation['room_capacity'] = $room['capacity'];
        }
    }
    
    return $reservations;
}



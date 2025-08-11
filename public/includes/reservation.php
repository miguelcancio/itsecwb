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

function create_reservation(string $userId, string $room, string $dateFrom, string $dateTo): ?array {
    $room = sanitize_text($room, 64);
    if ($room === '') { return null; }
    // Dates as ISO yyyy-mm-dd
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) { return null; }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) { return null; }
    if (strtotime($dateFrom) === false || strtotime($dateTo) === false) { return null; }
    if (strtotime($dateFrom) > strtotime($dateTo)) { return null; }
    $rec = sb_insert('reservations', [
        'user_id' => $userId,
        'room' => $room,
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



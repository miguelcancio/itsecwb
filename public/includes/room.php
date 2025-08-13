<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/supabase.php';
require_once __DIR__ . '/validation.php';

function list_rooms(): array {
    return sb_get('rooms', [], 100, 0, '*');
}

function get_room_by_id(string $roomId): ?array {
    $rows = sb_get('rooms', ['id' => $roomId], 1);
    return $rows[0] ?? null;
}

function create_room(string $name, string $description = '', int $capacity = 1): ?array {
    $validatedName = validate_user_input_text($name, 64);
    if (!$validatedName) { return null; }
    $validatedDescription = validate_user_input_text($description, 255);
    $capacity = validate_int_range($capacity, 1, 10) ?? 1;
    
    $room = sb_insert('rooms', [
        'name' => $validatedName,
        'description' => $validatedDescription,
        'capacity' => $capacity,
        'is_active' => true,
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c')
    ]);
    return $room ?: null;
}

function update_room(string $roomId, array $data): bool {
    $updates = [];
    if (isset($data['name'])) {
        $validatedName = validate_user_input_text($data['name'], 64);
        if ($validatedName !== null) { $updates['name'] = $validatedName; }
    }
    if (isset($data['description'])) {
        $validatedDescription = validate_user_input_text($data['description'], 255);
        if ($validatedDescription !== null) { $updates['description'] = $validatedDescription; }
    }
    if (isset($data['capacity'])) {
        $capacity = validate_int_range($data['capacity'], 1, 10);
        if ($capacity !== null) { $updates['capacity'] = $capacity; }
    }
    if (isset($data['is_active'])) {
        $updates['is_active'] = (bool)$data['is_active'];
    }
    if (empty($updates)) { return false; }
    
    $updates['updated_at'] = gmdate('c');
    return sb_update('rooms', ['id' => $roomId], $updates);
}

function delete_room(string $roomId): bool {
    // Check if room has active reservations
    $reservations = sb_get('reservations', ['room_id' => $roomId], 1);
    if (!empty($reservations)) {
        return false; // Cannot delete room with reservations
    }
    return sb_delete('rooms', ['id' => $roomId]);
}

function get_active_rooms(): array {
    return sb_get('rooms', ['is_active' => true], 100, 0, '*');
}

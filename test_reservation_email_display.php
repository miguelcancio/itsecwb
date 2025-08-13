<?php
declare(strict_types=1);

// Test file to verify reservation functions return user email information
require_once __DIR__ . '/public/includes/reservation.php';

echo "Testing reservation functions with user email information...\n\n";

// Test list_reservations_with_rooms function
echo "=== Testing list_reservations_with_rooms() ===\n";
$reservations = list_reservations_with_rooms();

if (empty($reservations)) {
    echo "No reservations found in the system.\n";
} else {
    echo "Found " . count($reservations) . " reservations:\n\n";
    
    foreach ($reservations as $i => $reservation) {
        echo "Reservation " . ($i + 1) . ":\n";
        echo "  Customer ID: " . ($reservation['user_id'] ?? 'N/A') . "\n";
        echo "  Customer Email: " . ($reservation['user_email'] ?? 'N/A') . "\n";
        echo "  Room: " . ($reservation['room_name'] ?? 'N/A') . "\n";
        echo "  Check-in: " . ($reservation['date_from'] ?? 'N/A') . "\n";
        echo "  Check-out: " . ($reservation['date_to'] ?? 'N/A') . "\n";
        echo "  Status: " . ($reservation['status'] ?? 'N/A') . "\n";
        echo "  ---\n";
    }
}

echo "\n=== Testing get_user_reservations_with_rooms() ===\n";
if (!empty($reservations)) {
    $firstUserId = $reservations[0]['user_id'] ?? null;
    if ($firstUserId) {
        echo "Testing with user ID: $firstUserId\n";
        $userReservations = get_user_reservations_with_rooms($firstUserId);
        
        if (empty($userReservations)) {
            echo "No reservations found for this user.\n";
        } else {
            echo "Found " . count($userReservations) . " reservations for user:\n\n";
            
            foreach ($userReservations as $i => $reservation) {
                echo "User Reservation " . ($i + 1) . ":\n";
                echo "  Customer ID: " . ($reservation['user_id'] ?? 'N/A') . "\n";
                echo "  Customer Email: " . ($reservation['user_email'] ?? 'N/A') . "\n";
                echo "  Room: " . ($reservation['room_name'] ?? 'N/A') . "\n";
                echo "  Check-in: " . ($reservation['date_from'] ?? 'N/A') . "\n";
                echo "  Check-out: " . ($reservation['date_to'] ?? 'N/A') . "\n";
                echo "  Status: " . ($reservation['status'] ?? 'N/A') . "\n";
                echo "  ---\n";
            }
        }
    } else {
        echo "No user ID found in first reservation.\n";
    }
} else {
    echo "No reservations available to test user-specific function.\n";
}

echo "\nTest completed.\n"; 
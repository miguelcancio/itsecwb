<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/logger.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'dismiss') {
    // Clear the session flag
    unset($_SESSION['show_login_info']);
    
    // Log the action
    log_event('login_notification_dismissed', 'User dismissed login notification', [
        'user_id' => $_SESSION['user']['id'],
        'email' => $_SESSION['user']['email']
    ]);
    
    // Return success response
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
?> 
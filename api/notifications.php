<?php
/**
 * MEDICQ - Notifications API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$auth = new Auth();
$notifications = $auth->getNotifications($_SESSION['user_id'], 10);

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);

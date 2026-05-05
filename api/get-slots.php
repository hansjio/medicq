<?php
/**
 * MEDICQ - Get Available Time Slots API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/doctor.php';

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$doctorId = (int)($_GET['doctor_id'] ?? 0);
$date = sanitize($_GET['date'] ?? '');

if (!$doctorId || !$date) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate date is not in the past
if (strtotime($date) < strtotime('today')) {
    echo json_encode(['success' => false, 'message' => 'Cannot book appointments in the past']);
    exit;
}

$doctor = new Doctor();
$slots = $doctor->getAvailableSlots($doctorId, $date);

echo json_encode([
    'success' => true,
    'slots' => $slots
]);

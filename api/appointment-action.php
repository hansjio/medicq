<?php
/**
 * MEDICQ - Appointment Action API
 * Place this file at: api/appointment-action.php
 *
 * FIXES applied:
 *   - new Appointment()              (was: new Appointment($pdo))
 *   - removed call to cancelAppointment() which doesn't exist;
 *     replaced with direct updateStatus('cancelled', ...) call
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/appointment.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$appointmentManager = new Appointment();   // FIX: no argument

$action        = $_POST['action'] ?? $_GET['action'] ?? '';
$appointmentId = intval($_POST['appointment_id'] ?? $_GET['appointment_id'] ?? 0);

if (!$appointmentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

$appointment = $appointmentManager->getById($appointmentId);

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit;
}

// Check access based on role
$hasAccess = false;
$userRole  = $_SESSION['user_role'];

if ($userRole === 'patient' && $appointment['patient_id'] == $_SESSION['user_id']) {
    $hasAccess = true;
} elseif ($userRole === 'doctor') {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch();
    if ($doctor && $appointment['doctor_id'] == $doctor['id']) {
        $hasAccess = true;
    }
} elseif ($userRole === 'admin') {
    $hasAccess = true;
}

if (!$hasAccess) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$result = ['success' => false, 'message' => 'Unknown action'];

switch ($action) {
    case 'confirm':
        if ($userRole === 'doctor' || $userRole === 'admin') {
            $result = $appointmentManager->updateStatus($appointmentId, 'confirmed');
        } else {
            $result = ['success' => false, 'message' => 'Only doctors can confirm appointments'];
        }
        break;

    case 'complete':
        if ($userRole === 'doctor' || $userRole === 'admin') {
            $result = $appointmentManager->updateStatus($appointmentId, 'completed');
        } else {
            $result = ['success' => false, 'message' => 'Only doctors can complete appointments'];
        }
        break;

    case 'cancel':
        // FIX: use updateStatus() directly — cancelAppointment() does not exist
        $reason = sanitize($_POST['reason'] ?? '');
        $result = $appointmentManager->updateStatus($appointmentId, 'cancelled', $userRole, $reason);
        break;

    case 'add_notes':
        if ($userRole === 'doctor') {
            $notes = sanitize($_POST['notes'] ?? '');
            $success = $appointmentManager->addNotes($appointmentId, $notes);
            $result = $success
                ? ['success' => true,  'message' => 'Notes saved successfully']
                : ['success' => false, 'message' => 'Failed to save notes'];
        } else {
            $result = ['success' => false, 'message' => 'Only doctors can add notes'];
        }
        break;

    case 'reschedule':
        if ($userRole === 'patient') {
            $newDate = sanitize($_POST['new_date'] ?? '');
            $newTime = sanitize($_POST['new_time'] ?? '');
            if ($newDate && $newTime) {
                $result = $appointmentManager->reschedule($appointmentId, $newDate, $newTime);
            } else {
                $result = ['success' => false, 'message' => 'Please provide new date and time'];
            }
        } else {
            $result = ['success' => false, 'message' => 'Only patients can reschedule appointments'];
        }
        break;
}

echo json_encode($result);

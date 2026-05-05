<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/appointment.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$appointmentManager = new Appointment($pdo);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$appointmentId = intval($_POST['appointment_id'] ?? $_GET['appointment_id'] ?? 0);

if (!$appointmentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

// Get appointment to verify ownership/access
$appointment = $appointmentManager->getById($appointmentId);

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit;
}

// Check access based on role
$hasAccess = false;
$userRole = $_SESSION['user_role'];

if ($userRole === 'patient' && $appointment['patient_id'] == $_SESSION['user_id']) {
    $hasAccess = true;
} elseif ($userRole === 'doctor') {
    // Get doctor ID
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
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
        $reason = $_POST['reason'] ?? '';
        $result = $appointmentManager->cancelAppointment($appointmentId, $reason, $userRole);
        break;
        
    case 'reschedule':
        if ($userRole === 'patient') {
            $newDate = $_POST['new_date'] ?? '';
            $newTime = $_POST['new_time'] ?? '';
            
            if ($newDate && $newTime) {
                // Check if new slot is available
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM appointments 
                    WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                    AND status NOT IN ('cancelled', 'completed') AND id != ?
                ");
                $stmt->execute([$appointment['doctor_id'], $newDate, $newTime, $appointmentId]);
                
                if ($stmt->fetchColumn() > 0) {
                    $result = ['success' => false, 'message' => 'Selected time slot is not available'];
                } else {
                    $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'pending' WHERE id = ?");
                    $stmt->execute([$newDate, $newTime, $appointmentId]);
                    $result = ['success' => true, 'message' => 'Appointment rescheduled successfully'];
                }
            } else {
                $result = ['success' => false, 'message' => 'Please provide new date and time'];
            }
        } else {
            $result = ['success' => false, 'message' => 'Only patients can reschedule appointments'];
        }
        break;
        
    case 'add_notes':
        if ($userRole === 'doctor') {
            $notes = $_POST['notes'] ?? '';
            $stmt = $pdo->prepare("UPDATE appointments SET notes = ? WHERE id = ?");
            $stmt->execute([$notes, $appointmentId]);
            $result = ['success' => true, 'message' => 'Notes saved successfully'];
        } else {
            $result = ['success' => false, 'message' => 'Only doctors can add notes'];
        }
        break;
}

echo json_encode($result);

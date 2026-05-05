<?php
/**
 * MEDICQ - Appointment Management Functions
 */

require_once __DIR__ . '/config.php';

class Appointment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new appointment
     */
    public function create($patientId, $doctorId, $date, $time, $consultationType, $reason = null) {
        // Get slot duration from doctor schedule
        $dayOfWeek = date('l', strtotime($date));
        $stmt = $this->db->prepare("SELECT slot_duration FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?");
        $stmt->bind_param("is", $doctorId, $dayOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        $duration = $schedule ? $schedule['slot_duration'] : 30;
        
        // Calculate end time
        $endTime = date('H:i:s', strtotime($time) + ($duration * 60));
        
        // Check for conflicts
        if ($this->hasConflict($doctorId, $date, $time, $endTime)) {
            return ['success' => false, 'message' => 'This time slot is no longer available'];
        }
        
        // Generate meeting link for video calls
        $meetingLink = null;
        if ($consultationType === 'video-call') {
            $meetingLink = 'https://meet.medicq.com/' . bin2hex(random_bytes(8));
        }
        
        $stmt = $this->db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, end_time, consultation_type, reason_for_visit, meeting_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", $patientId, $doctorId, $date, $time, $endTime, $consultationType, $reason, $meetingLink);
        
        if ($stmt->execute()) {
            $appointmentId = $this->db->insert_id;
            
            // Create notification for doctor
            $this->createNotification($doctorId, 'user', 'New Appointment Request', 
                'You have a new appointment request for ' . formatDate($date) . ' at ' . formatTime($time), 
                'appointment', $appointmentId);
            
            return ['success' => true, 'message' => 'Appointment booked successfully', 'appointment_id' => $appointmentId];
        }
        
        return ['success' => false, 'message' => 'Failed to book appointment'];
    }
    
    /**
     * Check for appointment conflicts
     */
    private function hasConflict($doctorId, $date, $startTime, $endTime, $excludeId = null) {
        $sql = "SELECT id FROM appointments 
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND status NOT IN ('cancelled')
                AND (
                    (appointment_time < ? AND end_time > ?) OR
                    (appointment_time < ? AND end_time > ?) OR
                    (appointment_time >= ? AND end_time <= ?)
                )";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isssssssi", $doctorId, $date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeId);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isssssss", $doctorId, $date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
        }
        
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    /**
     * Get appointment by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone,
                   d.specialization, d.clinic_name, d.clinic_address,
                   du.full_name as doctor_name, du.email as doctor_email
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users du ON d.user_id = du.id
            WHERE a.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get appointments for patient
     */
    public function getForPatient($patientId, $status = null, $upcoming = null) {
        $sql = "
            SELECT a.*, 
                   d.specialization, d.clinic_name,
                   u.full_name as doctor_name
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ?
        ";
        
        $params = [$patientId];
        $types = 'i';
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        if ($upcoming === true) {
            $sql .= " AND a.appointment_date >= CURDATE() AND a.status NOT IN ('cancelled', 'completed')";
        } elseif ($upcoming === false) {
            $sql .= " AND (a.appointment_date < CURDATE() OR a.status IN ('cancelled', 'completed'))";
        }
        
        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get appointments for doctor
     */
    public function getForDoctor($doctorId, $status = null, $date = null) {
        $sql = "
            SELECT a.*, 
                   u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            WHERE a.doctor_id = ?
        ";
        
        $params = [$doctorId];
        $types = 'i';
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        if ($date) {
            $sql .= " AND a.appointment_date = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get all appointments (for admin)
     */
    public function getAll($filters = []) {
        $sql = "
            SELECT a.*, 
                   u.full_name as patient_name,
                   d.specialization,
                   du.full_name as doctor_name
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users du ON d.user_id = du.id
            WHERE 1=1
        ";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['doctor_id'])) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = $filters['doctor_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.appointment_date >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.appointment_date <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Update appointment status
     */
    public function updateStatus($id, $status, $cancelledBy = null, $reason = null) {
        $sql = "UPDATE appointments SET status = ?";
        $types = 's';
        $params = [$status];
        
        if ($status === 'cancelled' && $cancelledBy) {
            $sql .= ", cancelled_by = ?, cancellation_reason = ?";
            $types .= 'ss';
            $params[] = $cancelledBy;
            $params[] = $reason;
        }
        
        $sql .= " WHERE id = ?";
        $types .= 'i';
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Get appointment details for notification
            $appointment = $this->getById($id);
            
            // Create notification based on status
            if ($status === 'confirmed') {
                $this->createNotification($appointment['patient_id'], 'user', 'Appointment Confirmed',
                    'Your appointment with ' . $appointment['doctor_name'] . ' on ' . formatDate($appointment['appointment_date']) . ' has been confirmed.',
                    'confirmation', $id);
            } elseif ($status === 'cancelled') {
                $notifyUserId = ($cancelledBy === 'patient') ? $appointment['doctor_id'] : $appointment['patient_id'];
                $this->createNotification($notifyUserId, 'user', 'Appointment Cancelled',
                    'The appointment on ' . formatDate($appointment['appointment_date']) . ' has been cancelled.',
                    'cancellation', $id);
            }
            
            return ['success' => true, 'message' => 'Appointment ' . $status];
        }
        
        return ['success' => false, 'message' => 'Failed to update appointment'];
    }
    
    /**
     * Add notes to appointment
     */
    public function addNotes($id, $notes) {
        $stmt = $this->db->prepare("UPDATE appointments SET notes = ? WHERE id = ?");
        $stmt->bind_param("si", $notes, $id);
        return $stmt->execute();
    }
    
    /**
     * Get patient statistics
     */
    public function getPatientStats($patientId) {
        $stats = [];
        
        // Total appointments
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $stats['total'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Last visit
        $stmt = $this->db->prepare("SELECT appointment_date FROM appointments WHERE patient_id = ? AND status = 'completed' ORDER BY appointment_date DESC LIMIT 1");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['last_visit'] = $result ? $result['appointment_date'] : null;
        
        // Next scheduled
        $stmt = $this->db->prepare("SELECT appointment_date FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('pending', 'confirmed') ORDER BY appointment_date ASC LIMIT 1");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['next_scheduled'] = $result ? $result['appointment_date'] : null;
        
        return $stats;
    }
    
    /**
     * Get doctor statistics
     */
    public function getDoctorStats($doctorId) {
        $stats = [];
        
        // Today's appointments
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $stats['today'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Pending appointments
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $stats['pending'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // This week
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND YEARWEEK(appointment_date, 1) = YEARWEEK(CURDATE(), 1)");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $stats['this_week'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Total patients
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ?");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $stats['total_patients'] = $stmt->get_result()->fetch_assoc()['count'];
        
        return $stats;
    }
    
    /**
     * Create notification helper
     */
    private function createNotification($targetId, $targetType, $title, $message, $type, $appointmentId = null) {
        // Get user_id based on target type
        if ($targetType === 'doctor') {
            $stmt = $this->db->prepare("SELECT user_id FROM doctors WHERE id = ?");
            $stmt->bind_param("i", $targetId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $userId = $result['user_id'];
        } else {
            $userId = $targetId;
        }
        
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, title, message, type, related_appointment_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $userId, $title, $message, $type, $appointmentId);
        return $stmt->execute();
    }
}
?>

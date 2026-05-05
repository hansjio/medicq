<?php
/**
 * MEDICQ - Doctor Management Functions
 */

require_once __DIR__ . '/config.php';

class Doctor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all doctors
     */
    public function getAll($specialization = null) {
        $sql = "
            SELECT d.*, u.full_name, u.email, u.phone, u.is_active
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE u.is_active = TRUE
        ";
        
        if ($specialization) {
            $sql .= " AND d.specialization = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $specialization);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get doctor by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name, u.email, u.phone, u.date_of_birth, u.address, u.profile_image, u.is_active
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get doctor by user ID
     */
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name, u.email, u.phone, u.date_of_birth, u.address, u.profile_image, u.is_active
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get all specializations
     */
    public function getSpecializations() {
        $stmt = $this->db->prepare("SELECT DISTINCT specialization FROM doctors ORDER BY specialization");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return array_column($result, 'specialization');
    }
    
    /**
     * Get doctor schedule
     */
    public function getSchedule($doctorId) {
        $stmt = $this->db->prepare("
            SELECT * FROM doctor_schedules 
            WHERE doctor_id = ? AND is_available = TRUE
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
        ");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Update doctor schedule
     */
    public function updateSchedule($doctorId, $dayOfWeek, $startTime, $endTime, $slotDuration, $isAvailable) {
        // Check if schedule exists
        $stmt = $this->db->prepare("SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?");
        $stmt->bind_param("is", $doctorId, $dayOfWeek);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        
        if ($exists) {
            $stmt = $this->db->prepare("
                UPDATE doctor_schedules 
                SET start_time = ?, end_time = ?, slot_duration = ?, is_available = ?
                WHERE doctor_id = ? AND day_of_week = ?
            ");
            $stmt->bind_param("ssiiss", $startTime, $endTime, $slotDuration, $isAvailable, $doctorId, $dayOfWeek);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_available)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssii", $doctorId, $dayOfWeek, $startTime, $endTime, $slotDuration, $isAvailable);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots($doctorId, $date) {
        $dayOfWeek = date('l', strtotime($date));
        
        // Get schedule for this day
        $stmt = $this->db->prepare("
            SELECT * FROM doctor_schedules 
            WHERE doctor_id = ? AND day_of_week = ? AND is_available = TRUE
        ");
        $stmt->bind_param("is", $doctorId, $dayOfWeek);
        $stmt->execute();
        $schedule = $stmt->get_result()->fetch_assoc();
        
        if (!$schedule) {
            return []; // Doctor not available on this day
        }
        
        // Check if full day is blocked
        $stmt = $this->db->prepare("
            SELECT * FROM blocked_slots 
            WHERE doctor_id = ? AND blocked_date = ? AND is_full_day = TRUE
        ");
        $stmt->bind_param("is", $doctorId, $date);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return []; // Full day is blocked
        }
        
        // Get blocked time ranges
        $stmt = $this->db->prepare("
            SELECT start_time, end_time FROM blocked_slots 
            WHERE doctor_id = ? AND blocked_date = ? AND is_full_day = FALSE
        ");
        $stmt->bind_param("is", $doctorId, $date);
        $stmt->execute();
        $blockedSlots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get existing appointments
        $stmt = $this->db->prepare("
            SELECT appointment_time, end_time FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('cancelled')
        ");
        $stmt->bind_param("is", $doctorId, $date);
        $stmt->execute();
        $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Generate all possible slots
        $slots = [];
        $startTime = strtotime($schedule['start_time']);
        $endTime = strtotime($schedule['end_time']);
        $duration = $schedule['slot_duration'] * 60; // Convert to seconds
        
        $currentTime = $startTime;
        while ($currentTime + $duration <= $endTime) {
            $slotStart = date('H:i:s', $currentTime);
            $slotEnd = date('H:i:s', $currentTime + $duration);
            
            $isAvailable = true;
            
            // Check blocked slots
            foreach ($blockedSlots as $blocked) {
                if ($slotStart < $blocked['end_time'] && $slotEnd > $blocked['start_time']) {
                    $isAvailable = false;
                    break;
                }
            }
            
            // Check existing appointments
            if ($isAvailable) {
                foreach ($appointments as $appt) {
                    if ($slotStart < $appt['end_time'] && $slotEnd > $appt['appointment_time']) {
                        $isAvailable = false;
                        break;
                    }
                }
            }
            
            // Don't show past slots for today
            if ($date === date('Y-m-d') && $currentTime < time()) {
                $isAvailable = false;
            }
            
            if ($isAvailable) {
                $slots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'display' => date('h:i A', $currentTime)
                ];
            }
            
            $currentTime += $duration;
        }
        
        return $slots;
    }
    
    /**
     * Block a time slot
     */
    public function blockSlot($doctorId, $date, $startTime = null, $endTime = null, $reason = null, $isFullDay = false) {
        $stmt = $this->db->prepare("
            INSERT INTO blocked_slots (doctor_id, blocked_date, start_time, end_time, reason, is_full_day)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssi", $doctorId, $date, $startTime, $endTime, $reason, $isFullDay);
        return $stmt->execute();
    }
    
    /**
     * Remove blocked slot
     */
    public function unblockSlot($id) {
        $stmt = $this->db->prepare("DELETE FROM blocked_slots WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Get blocked slots for doctor
     */
    public function getBlockedSlots($doctorId, $fromDate = null) {
        $sql = "SELECT * FROM blocked_slots WHERE doctor_id = ?";
        $types = 'i';
        $params = [$doctorId];
        
        if ($fromDate) {
            $sql .= " AND blocked_date >= ?";
            $types .= 's';
            $params[] = $fromDate;
        }
        
        $sql .= " ORDER BY blocked_date ASC, start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Update doctor profile
     */
    public function updateProfile($doctorId, $data) {
        $allowedFields = ['specialization', 'clinic_name', 'clinic_address', 'license_number', 'years_experience', 'consultation_fee', 'bio'];
        $updates = [];
        $types = '';
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $types .= ($field === 'years_experience' ? 'i' : ($field === 'consultation_fee' ? 'd' : 's'));
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $types .= 'i';
        $values[] = $doctorId;
        
        $sql = "UPDATE doctors SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
    
    /**
     * Create new doctor (admin function)
     */
    public function create($userData, $doctorData) {
        $this->db->begin_transaction();
        
        try {
            // Create user first
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, full_name, phone, role) 
                VALUES (?, ?, ?, ?, 'doctor')
            ");
            $stmt->bind_param("ssss", $userData['email'], $hashedPassword, $userData['full_name'], $userData['phone']);
            $stmt->execute();
            $userId = $this->db->insert_id;
            
            // Create doctor profile
            $stmt = $this->db->prepare("
                INSERT INTO doctors (user_id, specialization, clinic_name, clinic_address, license_number, years_experience, consultation_fee, bio)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssids", 
                $userId, 
                $doctorData['specialization'], 
                $doctorData['clinic_name'], 
                $doctorData['clinic_address'],
                $doctorData['license_number'],
                $doctorData['years_experience'],
                $doctorData['consultation_fee'],
                $doctorData['bio']
            );
            $stmt->execute();
            $doctorId = $this->db->insert_id;
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Doctor created successfully', 'doctor_id' => $doctorId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create doctor: ' . $e->getMessage()];
        }
    }
}
?>

<?php
/**
 * MEDICQ - Doctor Management Class
 * Place this file at: includes/doctor.php
 */

require_once __DIR__ . '/config.php';

class Doctor {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get doctor record by user ID (joins users + doctors tables)
     */
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name, u.email, u.phone, u.is_active
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all active doctors (for booking wizard)
     */
    public function getAll() {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name, u.email, u.phone
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE u.is_active = 1
            ORDER BY u.full_name
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get a single doctor by their doctors.id
     */
    public function getById($doctorId) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name, u.email, u.phone
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = ?
        ");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all unique specializations (for booking filter)
     */
    public function getSpecializations() {
        $result = $this->db->query("
            SELECT DISTINCT d.specialization
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE u.is_active = 1
            ORDER BY d.specialization
        ");
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        return array_column($rows, 'specialization');
    }

    /**
     * Get available time slots for a doctor on a given date.
     * Returns array of ['start' => 'HH:MM', 'display' => '10:00 AM'] objects.
     */
    public function getAvailableSlots($doctorId, $date) {
        // Get the day name in Title Case to match the ENUM
        $dayOfWeek = date('l', strtotime($date)); // e.g. 'Monday'

        // Load the doctor's schedule for that day
        $stmt = $this->db->prepare("
            SELECT start_time, end_time, slot_duration
            FROM doctor_schedules
            WHERE doctor_id = ? AND day_of_week = ? AND is_available = 1
        ");
        $stmt->bind_param("is", $doctorId, $dayOfWeek);
        $stmt->execute();
        $schedule = $stmt->get_result()->fetch_assoc();

        if (!$schedule) {
            return []; // Doctor not available this day
        }

        // Check for blocked slots on this date
        $stmt = $this->db->prepare("
            SELECT start_time, end_time, is_full_day
            FROM blocked_slots
            WHERE doctor_id = ? AND blocked_date = ?
        ");
        $stmt->bind_param("is", $doctorId, $date);
        $stmt->execute();
        $blocked = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // If the whole day is blocked, return nothing
        foreach ($blocked as $block) {
            if ($block['is_full_day']) return [];
        }

        // Get already-booked appointments for this date
        $stmt = $this->db->prepare("
            SELECT appointment_time, end_time
            FROM appointments
            WHERE doctor_id = ? AND appointment_date = ?
              AND status NOT IN ('cancelled')
        ");
        $stmt->bind_param("is", $doctorId, $date);
        $stmt->execute();
        $booked = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Generate all slots
        $slots        = [];
        $duration     = (int)$schedule['slot_duration'];
        $current      = strtotime($date . ' ' . $schedule['start_time']);
        $end          = strtotime($date . ' ' . $schedule['end_time']);
        $nowTimestamp = time();

        while ($current + ($duration * 60) <= $end) {
            $slotStart = date('H:i:s', $current);
            $slotEnd   = date('H:i:s', $current + ($duration * 60));

            // Skip slots in the past
            if (strtotime($date . ' ' . $slotStart) <= $nowTimestamp) {
                $current += $duration * 60;
                continue;
            }

            // Check against booked appointments
            $isBooked = false;
            foreach ($booked as $appt) {
                if ($slotStart < $appt['end_time'] && $slotEnd > $appt['appointment_time']) {
                    $isBooked = true;
                    break;
                }
            }

            // Check against blocked slots
            $isBlocked = false;
            foreach ($blocked as $block) {
                if ($slotStart < $block['end_time'] && $slotEnd > $block['start_time']) {
                    $isBlocked = true;
                    break;
                }
            }

            if (!$isBooked && !$isBlocked) {
                $slots[] = [
                    'start'   => date('H:i', $current),
                    'end'     => date('H:i', $current + ($duration * 60)),
                    'display' => date('g:i A', $current),
                ];
            }

            $current += $duration * 60;
        }

        return $slots;
    }

    /**
     * Get the doctor's weekly schedule (keyed by Title-Case day name)
     */
    public function getSchedule($doctorId) {
        $stmt = $this->db->prepare("
            SELECT * FROM doctor_schedules WHERE doctor_id = ?
        ");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $schedule = [];
        foreach ($rows as $row) {
            $schedule[$row['day_of_week']] = $row;
        }
        return $schedule;
    }
}
?>

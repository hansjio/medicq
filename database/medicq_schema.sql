-- MEDICQ Medical Appointment System Database Schema
-- Run this file in phpMyAdmin or MySQL command line to create the database

CREATE DATABASE IF NOT EXISTS medicq_db;
USE medicq_db;

-- Drop existing tables if they exist (for fresh install)
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS blocked_slots;
DROP TABLE IF EXISTS doctor_schedules;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS users;

-- ============================================
-- USERS TABLE
-- Stores all users: patients, doctors, admins
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    address TEXT,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DOCTORS TABLE
-- Extended information for doctor users
-- ============================================
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialization VARCHAR(100) NOT NULL,
    clinic_name VARCHAR(255),
    clinic_address TEXT,
    license_number VARCHAR(50),
    years_experience INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_specialization (specialization)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DOCTOR SCHEDULES TABLE
-- Weekly availability schedule for doctors
-- ============================================
CREATE TABLE doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30, -- Duration in minutes
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week),
    INDEX idx_doctor_schedule (doctor_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- BLOCKED SLOTS TABLE
-- For doctor vacations, holidays, breaks
-- ============================================
CREATE TABLE blocked_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    blocked_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    reason VARCHAR(255),
    is_full_day BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    INDEX idx_blocked_date (doctor_id, blocked_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- APPOINTMENTS TABLE
-- Core appointment records
-- ============================================
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    end_time TIME NOT NULL,
    consultation_type ENUM('in-person', 'video-call', 'phone-call') NOT NULL DEFAULT 'in-person',
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no-show') NOT NULL DEFAULT 'pending',
    reason_for_visit TEXT,
    notes TEXT,
    meeting_link VARCHAR(255), -- For video calls
    cancellation_reason TEXT,
    cancelled_by ENUM('patient', 'doctor', 'admin') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    INDEX idx_patient_appointments (patient_id, appointment_date),
    INDEX idx_doctor_appointments (doctor_id, appointment_date),
    INDEX idx_status (status),
    INDEX idx_date (appointment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- NOTIFICATIONS TABLE
-- System notifications for users
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment', 'reminder', 'cancellation', 'confirmation', 'system') NOT NULL DEFAULT 'system',
    related_appointment_id INT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    INDEX idx_user_notifications (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Admin User (password: admin123)
INSERT INTO users (email, password, full_name, phone, role, is_active) VALUES
('admin@medicq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '+63 912 345 6789', 'admin', TRUE);

-- Doctor Users (password: doctor123)
INSERT INTO users (email, password, full_name, phone, date_of_birth, address, role, is_active) VALUES
('sarah.johnson@medicq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sarah Johnson', '+63 917 123 4567', '1980-05-15', 'Heart Care Clinic, 123 Medical Center Drive', 'doctor', TRUE),
('michael.chen@medicq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Michael Chen', '+63 918 234 5678', '1975-08-22', 'Brain & Spine Center, 456 Health Avenue', 'doctor', TRUE),
('james.wilson@medicq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. James Wilson', '+63 919 345 6789', '1982-12-10', 'Joint Care Hospital, 789 Wellness Blvd', 'doctor', TRUE),
('lisa.anderson@medicq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Lisa Anderson', '+63 920 456 7890', '1978-03-28', 'Eye Care Center, 321 Vision Street', 'doctor', TRUE),
('emily.rodriguez@medicq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Emily Rodriguez', '+63 921 567 8901', '1985-07-14', 'Skin & Beauty Clinic, 654 Derma Lane', 'doctor', TRUE);

-- Patient Users (password: patient123)
INSERT INTO users (email, password, full_name, phone, date_of_birth, address, role, is_active) VALUES
('john.doe@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', '+63 912 345 6789', '1990-06-20', '123 Patient Street, Manila', 'patient', TRUE),
('jane.smith@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', '+63 913 456 7890', '1988-11-15', '456 Health Road, Quezon City', 'patient', TRUE),
('robert.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert Garcia', '+63 914 567 8901', '1995-02-28', '789 Care Avenue, Makati', 'patient', TRUE),
('maria.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Santos', '+63 915 678 9012', '1992-09-05', '321 Medical Lane, Pasig', 'patient', TRUE),
('carlos.reyes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Reyes', '+63 916 789 0123', '1987-04-12', '654 Wellness Blvd, Taguig', 'patient', TRUE);

-- Doctor Profiles
INSERT INTO doctors (user_id, specialization, clinic_name, clinic_address, license_number, years_experience, consultation_fee, bio) VALUES
(2, 'Cardiology', 'Heart Care Clinic', '123 Medical Center Drive, Manila', 'PRC-MED-12345', 15, 1500.00, 'Dr. Sarah Johnson is a board-certified cardiologist with over 15 years of experience in treating heart conditions.'),
(3, 'Neurology', 'Brain & Spine Center', '456 Health Avenue, Quezon City', 'PRC-MED-23456', 18, 2000.00, 'Dr. Michael Chen specializes in neurological disorders and has pioneered several treatment methods.'),
(4, 'Orthopedics', 'Joint Care Hospital', '789 Wellness Blvd, Makati', 'PRC-MED-34567', 12, 1800.00, 'Dr. James Wilson is an expert in joint replacement and sports medicine.'),
(5, 'Ophthalmology', 'Eye Care Center', '321 Vision Street, Pasig', 'PRC-MED-45678', 10, 1200.00, 'Dr. Lisa Anderson focuses on comprehensive eye care and laser surgery.'),
(6, 'Dermatology', 'Skin & Beauty Clinic', '654 Derma Lane, Taguig', 'PRC-MED-56789', 8, 1000.00, 'Dr. Emily Rodriguez specializes in skin conditions and cosmetic dermatology.');

-- Doctor Schedules
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_available) VALUES
-- Dr. Sarah Johnson (Cardiology)
(1, 'Monday', '09:00:00', '17:00:00', 30, TRUE),
(1, 'Tuesday', '09:00:00', '17:00:00', 30, TRUE),
(1, 'Wednesday', '09:00:00', '17:00:00', 30, TRUE),
(1, 'Thursday', '09:00:00', '17:00:00', 30, TRUE),
(1, 'Friday', '09:00:00', '12:00:00', 30, TRUE),
-- Dr. Michael Chen (Neurology)
(2, 'Monday', '10:00:00', '18:00:00', 45, TRUE),
(2, 'Tuesday', '10:00:00', '18:00:00', 45, TRUE),
(2, 'Wednesday', '10:00:00', '18:00:00', 45, TRUE),
(2, 'Thursday', '10:00:00', '18:00:00', 45, TRUE),
-- Dr. James Wilson (Orthopedics)
(3, 'Monday', '08:00:00', '16:00:00', 30, TRUE),
(3, 'Tuesday', '08:00:00', '16:00:00', 30, TRUE),
(3, 'Wednesday', '08:00:00', '16:00:00', 30, TRUE),
(3, 'Thursday', '08:00:00', '16:00:00', 30, TRUE),
(3, 'Friday', '08:00:00', '16:00:00', 30, TRUE),
-- Dr. Lisa Anderson (Ophthalmology)
(4, 'Tuesday', '09:00:00', '17:00:00', 30, TRUE),
(4, 'Wednesday', '09:00:00', '17:00:00', 30, TRUE),
(4, 'Thursday', '09:00:00', '17:00:00', 30, TRUE),
(4, 'Saturday', '09:00:00', '13:00:00', 30, TRUE),
-- Dr. Emily Rodriguez (Dermatology)
(5, 'Monday', '10:00:00', '18:00:00', 30, TRUE),
(5, 'Wednesday', '10:00:00', '18:00:00', 30, TRUE),
(5, 'Friday', '10:00:00', '18:00:00', 30, TRUE),
(5, 'Saturday', '10:00:00', '14:00:00', 30, TRUE);

-- Sample Appointments (using dynamic dates)
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, end_time, consultation_type, status, reason_for_visit, notes) VALUES
-- John Doe's appointments
(7, 1, CURDATE() + INTERVAL 2 DAY, '10:00:00', '10:30:00', 'in-person', 'confirmed', 'Regular heart checkup', 'Patient has history of hypertension'),
(7, 2, CURDATE() + INTERVAL 5 DAY, '14:30:00', '15:15:00', 'video-call', 'pending', 'Recurring headaches', NULL),
(7, 3, CURDATE() - INTERVAL 10 DAY, '09:00:00', '09:30:00', 'in-person', 'completed', 'Knee pain evaluation', 'Recommended physical therapy'),
(7, 5, CURDATE() - INTERVAL 30 DAY, '11:00:00', '11:30:00', 'in-person', 'cancelled', 'Skin rash consultation', 'Patient cancelled due to schedule conflict'),
-- Jane Smith's appointments
(8, 1, CURDATE() + INTERVAL 3 DAY, '11:00:00', '11:30:00', 'in-person', 'confirmed', 'Annual heart screening', NULL),
(8, 4, CURDATE() + INTERVAL 7 DAY, '10:00:00', '10:30:00', 'in-person', 'pending', 'Eye examination', NULL),
-- Robert Garcia's appointments
(9, 2, CURDATE() + INTERVAL 1 DAY, '15:00:00', '15:45:00', 'phone-call', 'confirmed', 'Follow-up consultation', 'Previous MRI results review'),
(9, 3, CURDATE() - INTERVAL 5 DAY, '14:00:00', '14:30:00', 'in-person', 'completed', 'Sports injury follow-up', 'Recovery progressing well'),
-- Maria Santos's appointments
(10, 5, CURDATE() + INTERVAL 4 DAY, '13:00:00', '13:30:00', 'in-person', 'pending', 'Acne treatment consultation', NULL),
(10, 4, CURDATE() - INTERVAL 15 DAY, '15:00:00', '15:30:00', 'in-person', 'completed', 'Vision test', 'Prescribed new glasses'),
-- Carlos Reyes's appointments
(11, 1, CURDATE() + INTERVAL 6 DAY, '09:30:00', '10:00:00', 'video-call', 'confirmed', 'Blood pressure monitoring', NULL),
(11, 2, CURDATE() - INTERVAL 20 DAY, '11:00:00', '11:45:00', 'in-person', 'completed', 'Neurological assessment', 'All results normal');

-- Sample Notifications
INSERT INTO notifications (user_id, title, message, type, related_appointment_id, is_read) VALUES
(7, 'Appointment Confirmed', 'Your appointment with Dr. Sarah Johnson has been confirmed for ' || DATE_FORMAT(CURDATE() + INTERVAL 2 DAY, '%M %d, %Y') || ' at 10:00 AM.', 'confirmation', 1, FALSE),
(7, 'Upcoming Reminder', 'You have an appointment tomorrow at 10:00 AM with Dr. Sarah Johnson.', 'reminder', 1, FALSE),
(7, 'New Appointment Pending', 'Your appointment request with Dr. Michael Chen is pending confirmation.', 'appointment', 2, TRUE),
(8, 'Appointment Confirmed', 'Your appointment with Dr. Sarah Johnson has been confirmed.', 'confirmation', 5, FALSE),
(9, 'Appointment Confirmed', 'Your appointment with Dr. Michael Chen has been confirmed.', 'confirmation', 7, FALSE),
(2, 'New Appointment Request', 'You have a new appointment request from John Doe.', 'appointment', 1, FALSE),
(2, 'New Appointment Request', 'You have a new appointment request from Jane Smith.', 'appointment', 5, TRUE);

-- Note: All sample passwords hash to 'password' using PHP's password_hash with PASSWORD_DEFAULT
-- For testing, use these credentials:
-- Admin: admin@medicq.com / password
-- Doctor: sarah.johnson@medicq.com / password
-- Patient: john.doe@email.com / password

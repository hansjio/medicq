<?php
/**
 * MEDICQ - Medical Appointment System
 * Main Entry Point
 */

require_once __DIR__ . '/includes/config.php';

// Redirect based on login status
if (isLoggedIn()) {
    redirect(SITE_URL . '/' . getUserRole() . '/dashboard.php');
} else {
    redirect(SITE_URL . '/login.php');
}

<?php
/**
 * MEDICQ - Medical Appointment System
 * Database Configuration File
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medicq_db');

// Application Configuration
define('SITE_NAME', 'MEDICQ');
define('SITE_URL', 'http://localhost/medicq');
define('SITE_EMAIL', 'noreply@medicq.com');

// Time Zone
date_default_timezone_set('Asia/Manila');

// ─────────────────────────────────────────────
// MySQLi Singleton (used by Auth, Appointment, Doctor classes)
// ─────────────────────────────────────────────
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}

// ─────────────────────────────────────────────
// FIX: PDO connection for admin pages and doctor/schedule.php
// These pages were written with PDO syntax. Rather than rewriting
// every admin file, we define $pdo here so they work immediately.
// ─────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}

// ─────────────────────────────────────────────
// Helper Functions
// ─────────────────────────────────────────────

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function hasRole($role) {
    return getUserRole() === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(SITE_URL . '/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect(SITE_URL . '/unauthorized.php');
    }
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function formatTime($time, $format = 'h:i A') {
    return date($format, strtotime($time));
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getStatusBadge($status) {
    $badges = [
        'pending'   => '<span class="badge badge-warning">Pending</span>',
        'confirmed' => '<span class="badge badge-success">Confirmed</span>',
        'completed' => '<span class="badge badge-info">Completed</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
        'no-show'   => '<span class="badge badge-secondary">No Show</span>',
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

function getConsultationIcon($type) {
    $icons = [
        'in-person'  => '<i class="fas fa-user"></i> In-Person',
        'video-call' => '<i class="fas fa-video"></i> Video Call',
        'phone-call' => '<i class="fas fa-phone"></i> Phone Call',
    ];
    return $icons[$type] ?? $type;
}
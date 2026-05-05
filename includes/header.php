<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$auth = new Auth();
$currentUser = isLoggedIn() ? $auth->getCurrentUser() : null;
$unreadCount = isLoggedIn() ? $auth->getUnreadNotificationsCount($_SESSION['user_id']) : 0;

// Get current page for active nav
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <header class="header">
        <div class="header-inner">
            <a href="<?php echo SITE_URL; ?>/<?php echo getUserRole(); ?>/dashboard.php" class="logo">
                <div class="logo-icon">M</div>
                <span>MEDICQ</span>
            </a>
            
            <nav>
                <ul class="nav-menu">
                    <?php if (hasRole('patient')): ?>
                    <li><a href="<?php echo SITE_URL; ?>/patient/dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/patient/appointments.php" class="<?php echo $currentPage === 'appointments' ? 'active' : ''; ?>">Appointments</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="<?php echo $currentPage === 'book-appointment' ? 'active' : ''; ?>">Book Appointment</a></li>
                    <?php elseif (hasRole('doctor')): ?>
                    <li><a href="<?php echo SITE_URL; ?>/doctor/dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/doctor/appointments.php" class="<?php echo $currentPage === 'appointments' ? 'active' : ''; ?>">Appointments</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/doctor/schedule.php" class="<?php echo $currentPage === 'schedule' ? 'active' : ''; ?>">My Schedule</a></li>
                    <?php elseif (hasRole('admin')): ?>
                    <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/appointments.php" class="<?php echo $currentPage === 'appointments' ? 'active' : ''; ?>">Appointments</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/doctors.php" class="<?php echo $currentPage === 'doctors' ? 'active' : ''; ?>">Doctors</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/patients.php" class="<?php echo $currentPage === 'patients' ? 'active' : ''; ?>">Patients</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="header-actions">
                <div class="notification-bell dropdown">
                    <i class="far fa-bell fa-lg"></i>
                    <?php if ($unreadCount > 0): ?>
                    <span class="notification-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                    <?php endif; ?>
                    
                    <div class="dropdown-menu">
                        <div style="padding: var(--spacing-3) var(--spacing-4); border-bottom: 1px solid var(--gray-200);">
                            <strong>Notifications</strong>
                        </div>
                        <div id="notification-list" style="max-height: 300px; overflow-y: auto;">
                            <!-- Loaded via JS -->
                        </div>
                        <a href="<?php echo SITE_URL; ?>/notifications.php" class="dropdown-item" style="justify-content: center; border-top: 1px solid var(--gray-200);">
                            View All Notifications
                        </a>
                    </div>
                </div>
                
                <div class="user-menu dropdown">
                    <div class="user-menu-toggle">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 12px; color: var(--gray-400);"></i>
                    </div>
                    
                    <div class="dropdown-menu">
                        <a href="<?php echo SITE_URL; ?>/<?php echo getUserRole(); ?>/profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            Profile Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="dropdown-item" style="color: var(--danger);">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>
    
    <main class="main-content">
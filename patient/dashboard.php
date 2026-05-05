<?php
/**
 * MEDICQ - Patient Dashboard
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('patient');

$auth = new Auth();
$appointment = new Appointment();

$user = $auth->getCurrentUser();
$stats = $appointment->getPatientStats($_SESSION['user_id']);
$upcomingAppointments = $appointment->getForPatient($_SESSION['user_id'], null, true);
$notifications = $auth->getNotifications($_SESSION['user_id'], 5);

// Get flash message
$flash = getFlashMessage();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <?php if ($flash): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>" data-dismiss>
        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-8">
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">
            Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!
        </h1>
        <p class="text-muted">Manage your medical appointments and health records</p>
    </div>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: var(--spacing-4); margin-bottom: var(--spacing-8);">
        <a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus"></i>
            Book an Appointment
        </a>
        <a href="<?php echo SITE_URL; ?>/patient/appointments.php" class="btn btn-secondary btn-lg">
            View All Appointments
        </a>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Appointments</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-history"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['last_visit'] ? formatDate($stats['last_visit'], 'M d') : '-'; ?></h3>
                <p>Last Visit</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['next_scheduled'] ? formatDate($stats['next_scheduled'], 'M d') : '-'; ?></h3>
                <p>Next Scheduled</p>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="card mb-6">
        <div class="card-header">
            <h4 style="margin: 0;">Upcoming Appointments</h4>
            <a href="<?php echo SITE_URL; ?>/patient/appointments.php" class="btn btn-link">View all →</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($upcomingAppointments)): ?>
            <div class="empty-state" style="padding: var(--spacing-8);">
                <i class="far fa-calendar-alt"></i>
                <h3>No Upcoming Appointments</h3>
                <p>You don't have any scheduled appointments.</p>
                <a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="btn btn-primary">
                    Book an Appointment
                </a>
            </div>
            <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--spacing-4); padding: var(--spacing-4);">
                <?php foreach (array_slice($upcomingAppointments, 0, 3) as $appt): ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <div class="doctor-info">
                            <h4 style="color: var(--primary);"><?php echo htmlspecialchars($appt['doctor_name']); ?></h4>
                            <p><?php echo htmlspecialchars($appt['specialization']); ?></p>
                        </div>
                        <?php echo getStatusBadge($appt['status']); ?>
                    </div>
                    
                    <div class="appointment-details">
                        <span><i class="far fa-calendar"></i> <?php echo formatDate($appt['appointment_date']); ?></span>
                        <span><i class="far fa-clock"></i> <?php echo formatTime($appt['appointment_time']); ?></span>
                        <span><?php echo getConsultationIcon($appt['consultation_type']); ?></span>
                    </div>
                    
                    <div class="appointment-actions">
                        <a href="<?php echo SITE_URL; ?>/patient/appointment-details.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-secondary">
                            View Details
                        </a>
                        <?php if ($appt['status'] !== 'cancelled'): ?>
                        <a href="<?php echo SITE_URL; ?>/patient/reschedule.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-outline">
                            Reschedule
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Notifications -->
    <div class="card">
        <div class="card-header">
            <h4 style="margin: 0;">Recent Notifications</h4>
            <a href="<?php echo SITE_URL; ?>/notifications.php" class="btn btn-link">View all →</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($notifications)): ?>
            <div class="empty-state" style="padding: var(--spacing-6);">
                <i class="far fa-bell"></i>
                <h3>No Notifications</h3>
                <p>You're all caught up!</p>
            </div>
            <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                    <div class="notification-icon <?php 
                        echo $notif['type'] === 'confirmation' ? 'success' : 
                            ($notif['type'] === 'reminder' ? 'warning' : 
                            ($notif['type'] === 'cancellation' ? 'danger' : 'info')); 
                    ?>">
                        <i class="fas <?php 
                            echo $notif['type'] === 'confirmation' ? 'fa-check-circle' : 
                                ($notif['type'] === 'reminder' ? 'fa-clock' : 
                                ($notif['type'] === 'cancellation' ? 'fa-times-circle' : 'fa-bell')); 
                        ?>"></i>
                    </div>
                    <div class="notification-content">
                        <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <span class="notification-time"><?php echo formatDate($notif['created_at'], 'M d, g:i A'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const SITE_URL = '<?php echo SITE_URL; ?>';
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

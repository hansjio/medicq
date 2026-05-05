<?php
/**
 * MEDICQ - Patient Dashboard
 * Place this file at: patient/dashboard.php
 *
 * FIX: was requireRole('admin') — changed to requireRole('patient')
 */

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('patient');   // ← was 'admin'

$auth             = new Auth();
$appointmentModel = new Appointment();

$user  = $auth->getCurrentUser();
$stats = $appointmentModel->getPatientStats($_SESSION['user_id']);

$upcoming      = $appointmentModel->getForPatient($_SESSION['user_id'], null, true);
$upcoming      = array_slice($upcoming, 0, 3);
$notifications = $auth->getNotifications($_SESSION['user_id'], 5);

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

    <!-- Quick Actions -->
    <div style="display:flex; gap: var(--spacing-3); margin-bottom: var(--spacing-6);">
        <a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Book an Appointment
        </a>
        <a href="<?php echo SITE_URL; ?>/patient/appointments.php" class="btn btn-secondary">
            <i class="fas fa-calendar"></i> View All Appointments
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: var(--spacing-6);">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-content"><h3><?php echo $stats['total']; ?></h3><p>Total Appointments</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-history"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['last_visit'] ? formatDate($stats['last_visit'], 'M d') : '—'; ?></h3>
                <p>Last Visit</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['next_scheduled'] ? formatDate($stats['next_scheduled'], 'M d') : '—'; ?></h3>
                <p>Next Scheduled</p>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap: var(--spacing-6);">
        <!-- Upcoming Appointments -->
        <div class="card">
            <div class="card-header">
                <h4 style="margin:0;">Upcoming Appointments</h4>
                <a href="<?php echo SITE_URL; ?>/patient/appointments.php" class="btn btn-link">View all →</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($upcoming)): ?>
                <div class="empty-state" style="padding: var(--spacing-6);">
                    <i class="far fa-calendar"></i>
                    <h3>No Upcoming Appointments</h3>
                    <a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>
                <?php else: ?>
                <?php foreach ($upcoming as $appt): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding: var(--spacing-4); border-bottom:1px solid var(--gray-100);">
                    <div>
                        <strong><?php echo htmlspecialchars($appt['doctor_name']); ?></strong><br>
                        <span class="text-muted" style="font-size: var(--font-size-sm);"><?php echo htmlspecialchars($appt['specialization']); ?></span><br>
                        <span class="text-muted" style="font-size: var(--font-size-sm);">
                            <i class="far fa-calendar"></i> <?php echo formatDate($appt['appointment_date']); ?>
                            &nbsp;<i class="far fa-clock"></i> <?php echo formatTime($appt['appointment_time']); ?>
                        </span>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap: var(--spacing-2);">
                        <?php echo getStatusBadge($appt['status']); ?>
                        <a href="<?php echo SITE_URL; ?>/patient/appointment-details.php?id=<?php echo $appt['id']; ?>"
                           class="btn btn-sm btn-outline">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="card">
            <div class="card-header">
                <h4 style="margin:0;">Recent Notifications</h4>
                <a href="<?php echo SITE_URL; ?>/notifications.php" class="btn btn-link">View all →</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($notifications)): ?>
                <div class="empty-state" style="padding: var(--spacing-6);">
                    <i class="far fa-bell"></i>
                    <h3>No Notifications</h3>
                </div>
                <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div style="padding: var(--spacing-3) var(--spacing-4); border-bottom:1px solid var(--gray-100); <?php echo !$notif['is_read'] ? 'background:var(--primary-light);' : ''; ?>">
                    <div style="display:flex; justify-content:space-between;">
                        <strong style="font-size: var(--font-size-sm);"><?php echo htmlspecialchars($notif['title']); ?></strong>
                        <?php if (!$notif['is_read']): ?>
                        <span style="width:8px;height:8px;background:var(--primary);border-radius:50%;flex-shrink:0;margin-top:4px;"></span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size: var(--font-size-sm); color:var(--gray-600); margin: var(--spacing-1) 0 0 0;">
                        <?php echo htmlspecialchars($notif['message']); ?>
                    </p>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
/**
 * MEDICQ - Doctor Dashboard
 */

$pageTitle = 'Doctor Dashboard';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/doctor.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('doctor');

$auth = new Auth();
$doctorModel = new Doctor();
$appointmentModel = new Appointment();

$user = $auth->getCurrentUser();
$doctor = $doctorModel->getByUserId($_SESSION['user_id']);
$doctorId = $doctor['id'];

$stats = $appointmentModel->getDoctorStats($doctorId);
$todayAppointments = $appointmentModel->getForDoctor($doctorId, null, date('Y-m-d'));
$pendingAppointments = $appointmentModel->getForDoctor($doctorId, 'pending');

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
            Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!
        </h1>
        <p class="text-muted"><?php echo htmlspecialchars($doctor['specialization']); ?> - <?php echo htmlspecialchars($doctor['clinic_name']); ?></p>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['today']; ?></h3>
                <p>Today's Appointments</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending Requests</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['this_week']; ?></h3>
                <p>This Week</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_patients']; ?></h3>
                <p>Total Patients</p>
            </div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-6);">
        <!-- Today's Appointments -->
        <div class="card">
            <div class="card-header">
                <h4 style="margin: 0;">Today's Appointments</h4>
                <span class="badge badge-info"><?php echo date('M d, Y'); ?></span>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($todayAppointments)): ?>
                <div class="empty-state" style="padding: var(--spacing-6);">
                    <i class="far fa-calendar-check"></i>
                    <h3>No Appointments Today</h3>
                    <p>You have no scheduled appointments for today.</p>
                </div>
                <?php else: ?>
                <div class="appointment-list">
                    <?php foreach ($todayAppointments as $appt): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-4); border-bottom: 1px solid var(--gray-100);">
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-1) 0; font-size: var(--font-size-base);">
                                <?php echo htmlspecialchars($appt['patient_name']); ?>
                            </h4>
                            <p style="color: var(--gray-500); font-size: var(--font-size-sm); margin: 0;">
                                <i class="far fa-clock"></i> <?php echo formatTime($appt['appointment_time']); ?>
                                &nbsp;&bull;&nbsp;
                                <?php echo getConsultationIcon($appt['consultation_type']); ?>
                            </p>
                        </div>
                        <div style="display: flex; align-items: center; gap: var(--spacing-3);">
                            <?php echo getStatusBadge($appt['status']); ?>
                            <a href="<?php echo SITE_URL; ?>/doctor/appointment-details.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-secondary">
                                View
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pending Requests -->
        <div class="card">
            <div class="card-header">
                <h4 style="margin: 0;">Pending Requests</h4>
                <a href="<?php echo SITE_URL; ?>/doctor/appointments.php?status=pending" class="btn btn-link">View all →</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($pendingAppointments)): ?>
                <div class="empty-state" style="padding: var(--spacing-6);">
                    <i class="far fa-check-circle"></i>
                    <h3>All Caught Up!</h3>
                    <p>You have no pending appointment requests.</p>
                </div>
                <?php else: ?>
                <div class="appointment-list">
                    <?php foreach (array_slice($pendingAppointments, 0, 5) as $appt): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-4); border-bottom: 1px solid var(--gray-100);">
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-1) 0; font-size: var(--font-size-base);">
                                <?php echo htmlspecialchars($appt['patient_name']); ?>
                            </h4>
                            <p style="color: var(--gray-500); font-size: var(--font-size-sm); margin: 0;">
                                <i class="far fa-calendar"></i> <?php echo formatDate($appt['appointment_date']); ?>
                                &nbsp;&bull;&nbsp;
                                <i class="far fa-clock"></i> <?php echo formatTime($appt['appointment_time']); ?>
                            </p>
                        </div>
                        <div style="display: flex; gap: var(--spacing-2);">
                            <form method="POST" action="<?php echo SITE_URL; ?>/doctor/appointments.php" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?php echo SITE_URL; ?>/doctor/appointments.php" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const SITE_URL = '<?php echo SITE_URL; ?>';
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

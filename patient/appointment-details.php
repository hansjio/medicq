<?php
/**
 * MEDICQ - Appointment Details
 */

$pageTitle = 'Appointment Details';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('patient');

$appointmentModel = new Appointment();

$appointmentId = (int)($_GET['id'] ?? 0);

if (!$appointmentId) {
    redirect(SITE_URL . '/patient/appointments.php');
}

$appointment = $appointmentModel->getById($appointmentId);

// Verify ownership
if (!$appointment || $appointment['patient_id'] != $_SESSION['user_id']) {
    setFlashMessage('danger', 'Appointment not found.');
    redirect(SITE_URL . '/patient/appointments.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div style="margin-bottom: var(--spacing-6);">
        <a href="<?php echo SITE_URL; ?>/patient/appointments.php" class="btn btn-link" style="padding: 0; margin-bottom: var(--spacing-4);">
            <i class="fas fa-arrow-left"></i> Back to Appointments
        </a>
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Appointment Details</h1>
    </div>
    
    <div class="card mb-6">
        <div class="card-header">
            <h4 style="margin: 0;">Appointment Information</h4>
            <?php echo getStatusBadge($appointment['status']); ?>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-6);">
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Doctor</p>
                    <p style="font-weight: 600; color: var(--primary); margin: 0;"><?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                    <p style="color: var(--gray-600); font-size: var(--font-size-sm); margin: 0;"><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                </div>
                
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Clinic</p>
                    <p style="font-weight: 500; margin: 0;"><?php echo htmlspecialchars($appointment['clinic_name']); ?></p>
                    <p style="color: var(--gray-600); font-size: var(--font-size-sm); margin: 0;"><?php echo htmlspecialchars($appointment['clinic_address']); ?></p>
                </div>
                
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Date & Time</p>
                    <p style="font-weight: 500; margin: 0;">
                        <i class="far fa-calendar" style="color: var(--primary);"></i> 
                        <?php echo formatDate($appointment['appointment_date'], 'l, F d, Y'); ?>
                    </p>
                    <p style="color: var(--gray-600); font-size: var(--font-size-sm); margin: 0;">
                        <i class="far fa-clock" style="color: var(--primary);"></i> 
                        <?php echo formatTime($appointment['appointment_time']); ?> - <?php echo formatTime($appointment['end_time']); ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Consultation Type</p>
                    <p style="font-weight: 500; margin: 0;">
                        <?php echo getConsultationIcon($appointment['consultation_type']); ?>
                    </p>
                </div>
                
                <?php if ($appointment['reason_for_visit']): ?>
                <div style="grid-column: span 2;">
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Reason for Visit</p>
                    <p style="margin: 0;"><?php echo htmlspecialchars($appointment['reason_for_visit']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($appointment['notes']): ?>
                <div style="grid-column: span 2;">
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Doctor's Notes</p>
                    <p style="margin: 0;"><?php echo htmlspecialchars($appointment['notes']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($appointment['consultation_type'] === 'video-call' && $appointment['meeting_link'] && $appointment['status'] === 'confirmed'): ?>
                <div style="grid-column: span 2;">
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Video Call Link</p>
                    <a href="<?php echo htmlspecialchars($appointment['meeting_link']); ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-video"></i> Join Video Call
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($appointment['status'] === 'cancelled' && $appointment['cancellation_reason']): ?>
                <div style="grid-column: span 2;">
                    <div class="alert alert-danger" style="margin: 0;">
                        <strong>Cancellation Reason:</strong> <?php echo htmlspecialchars($appointment['cancellation_reason']); ?>
                        <br>
                        <small>Cancelled by: <?php echo ucfirst($appointment['cancelled_by']); ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed'): ?>
    <div style="display: flex; gap: var(--spacing-4);">
        <a href="<?php echo SITE_URL; ?>/patient/reschedule.php?id=<?php echo $appointment['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-calendar-alt"></i> Reschedule
        </a>
        <button type="button" class="btn btn-outline text-danger" onclick="showCancelModal()">
            <i class="fas fa-times"></i> Cancel Appointment
        </button>
    </div>
    
    <!-- Cancel Modal -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Cancel Appointment</h3>
                <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
            </div>
            <form action="<?php echo SITE_URL; ?>/patient/appointments.php" method="POST">
                <div class="modal-body">
                    <p style="margin-bottom: var(--spacing-4);">Are you sure you want to cancel this appointment?</p>
                    <input type="hidden" name="cancel_id" value="<?php echo $appointment['id']; ?>">
                    <div class="form-group mb-0">
                        <label class="form-label">Reason for Cancellation (Optional)</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Please provide a reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Keep Appointment</button>
                    <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showCancelModal() {
        document.getElementById('cancelModal').classList.add('active');
    }
    </script>
    <?php endif; ?>
</div>

<script>
const SITE_URL = '<?php echo SITE_URL; ?>';
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

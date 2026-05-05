<?php
/**
 * MEDICQ - Patient Appointments List
 * Place this file at: patient/appointments.php
 *
 * FIX: was requireRole('admin') — changed to requireRole('patient')
 *      and scoped all queries to the logged-in patient only.
 */

$pageTitle = 'My Appointments';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('patient');   // ← was 'admin'

$appointmentModel = new Appointment();

// Handle cancellation posted from appointment-details page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancelId = (int)$_POST['cancel_id'];
    $reason   = sanitize($_POST['cancellation_reason'] ?? '');

    // Verify ownership before cancelling
    $appt = $appointmentModel->getById($cancelId);
    if ($appt && $appt['patient_id'] == $_SESSION['user_id']) {
        $appointmentModel->updateStatus($cancelId, 'cancelled', 'patient', $reason);
        setFlashMessage('success', 'Appointment cancelled successfully.');
    }
    redirect(SITE_URL . '/patient/appointments.php');
}

// Filter
$statusFilter = $_GET['status'] ?? '';

// Fetch only THIS patient's appointments
$appointments = $appointmentModel->getForPatient($_SESSION['user_id'], $statusFilter ?: null);

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

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--spacing-6);">
        <div>
            <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-1);">My Appointments</h1>
            <p class="text-muted">View and manage all your medical appointments</p>
        </div>
        <a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Book Appointment
        </a>
    </div>

    <!-- Status filter tabs -->
    <div style="display:flex; gap: var(--spacing-2); margin-bottom: var(--spacing-4); flex-wrap:wrap;">
        <?php
        $tabs = ['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
        foreach ($tabs as $val => $label):
        ?>
        <a href="?status=<?php echo $val; ?>"
           class="btn btn-sm <?php echo $statusFilter === $val ? 'btn-primary' : 'btn-secondary'; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-body" style="padding:0;">
            <?php if (empty($appointments)): ?>
            <div class="empty-state" style="padding: var(--spacing-8);">
                <i class="far fa-calendar" style="font-size:3rem; color:var(--gray-300); display:block; margin-bottom:var(--spacing-4);"></i>
                <h3>No Appointments Found</h3>
                <p>You have no <?php echo $statusFilter ? $statusFilter : ''; ?> appointments.</p>
                <a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="btn btn-primary btn-sm">Book Now</a>
            </div>
            <?php else: ?>
            <?php foreach ($appointments as $apt): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding: var(--spacing-4); border-bottom: 1px solid var(--gray-100);">
                <div style="flex:1;">
                    <div style="display:flex; align-items:center; gap: var(--spacing-3); margin-bottom: var(--spacing-1);">
                        <strong><?php echo htmlspecialchars($apt['doctor_name']); ?></strong>
                        <?php echo getStatusBadge($apt['status']); ?>
                    </div>
                    <div class="text-muted" style="font-size: var(--font-size-sm);">
                        <?php echo htmlspecialchars($apt['specialization']); ?>
                        &nbsp;·&nbsp;
                        <?php echo htmlspecialchars($apt['clinic_name'] ?? ''); ?>
                    </div>
                    <div class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--spacing-1);">
                        <i class="far fa-calendar"></i> <?php echo formatDate($apt['appointment_date']); ?>
                        &nbsp;
                        <i class="far fa-clock"></i> <?php echo formatTime($apt['appointment_time']); ?>
                        &nbsp;·&nbsp;
                        <?php echo getConsultationIcon($apt['consultation_type']); ?>
                    </div>
                </div>
                <div style="display:flex; gap: var(--spacing-2); align-items:center; flex-shrink:0;">
                    <a href="<?php echo SITE_URL; ?>/patient/appointment-details.php?id=<?php echo $apt['id']; ?>"
                       class="btn btn-sm btn-outline">View Details</a>
                    <?php if (in_array($apt['status'], ['pending', 'confirmed'])): ?>
                    <a href="<?php echo SITE_URL; ?>/patient/reschedule.php?id=<?php echo $apt['id']; ?>"
                       class="btn btn-sm btn-secondary">Reschedule</a>
                    <button type="button" class="btn btn-sm btn-link text-danger"
                            onclick="openCancelModal(<?php echo $apt['id']; ?>)">Cancel</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Cancel Appointment</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('cancelModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <input type="hidden" name="cancel_id" id="cancelId">
                <div class="form-group mb-0">
                    <label class="form-label">Reason (optional)</label>
                    <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Reason for cancellation..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('cancelModal').classList.remove('active')">Keep It</button>
                <button type="submit" class="btn btn-danger">Yes, Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(id) {
    document.getElementById('cancelId').value = id;
    document.getElementById('cancelModal').classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
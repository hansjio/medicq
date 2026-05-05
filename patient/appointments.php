<?php
/**
 * MEDICQ - Patient Appointments List
 */

$pageTitle = 'My Appointments';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('patient');

$appointment = new Appointment();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancelId = (int)$_POST['cancel_id'];
    $reason = sanitize($_POST['cancellation_reason'] ?? '');
    
    $result = $appointment->updateStatus($cancelId, 'cancelled', 'patient', $reason);
    
    if ($result['success']) {
        setFlashMessage('success', 'Appointment cancelled successfully.');
    } else {
        setFlashMessage('danger', $result['message']);
    }
    
    redirect(SITE_URL . '/patient/appointments.php');
}

// Get filter
$filter = $_GET['status'] ?? 'all';

// Get appointments based on filter
if ($filter === 'upcoming') {
    $appointments = $appointment->getForPatient($_SESSION['user_id'], null, true);
} elseif ($filter === 'completed') {
    $appointments = $appointment->getForPatient($_SESSION['user_id'], 'completed');
} elseif ($filter === 'cancelled') {
    $appointments = $appointment->getForPatient($_SESSION['user_id'], 'cancelled');
} else {
    $appointments = $appointment->getForPatient($_SESSION['user_id']);
}

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
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">My Appointments</h1>
        <p class="text-muted">View and manage all your medical appointments</p>
    </div>
    
    <!-- Search and Filters -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-6);">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control" id="searchInput" placeholder="Search by doctor, specialty, or clinic...">
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs">
        <a href="?status=all" class="tab-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="?status=upcoming" class="tab-btn <?php echo $filter === 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
        <a href="?status=completed" class="tab-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
        <a href="?status=cancelled" class="tab-btn <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
    </div>
    
    <!-- Appointments List -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <i class="far fa-calendar-alt"></i>
                <h3>No Appointments Found</h3>
                <p>You don't have any <?php echo $filter !== 'all' ? $filter : ''; ?> appointments.</p>
                <a href="<?php echo SITE_URL; ?>/patient/book-appointment.php" class="btn btn-primary">
                    Book an Appointment
                </a>
            </div>
            <?php else: ?>
            <div class="appointment-list" id="appointmentList">
                <?php foreach ($appointments as $appt): ?>
                <div class="appointment-row" data-search="<?php echo strtolower($appt['doctor_name'] . ' ' . $appt['specialization'] . ' ' . $appt['clinic_name']); ?>">
                    <div class="doctor-info">
                        <h4><?php echo htmlspecialchars($appt['doctor_name']); ?></h4>
                        <p style="color: var(--primary); margin: var(--spacing-1) 0;"><?php echo htmlspecialchars($appt['specialization']); ?></p>
                        <p style="color: var(--gray-500); font-size: var(--font-size-sm);"><?php echo htmlspecialchars($appt['clinic_name']); ?></p>
                    </div>
                    
                    <div class="appointment-details" style="flex-direction: column; gap: var(--spacing-2);">
                        <span><i class="far fa-calendar"></i> <?php echo formatDate($appt['appointment_date']); ?></span>
                        <span><i class="far fa-clock"></i> <?php echo formatTime($appt['appointment_time']); ?></span>
                        <span><?php echo getConsultationIcon($appt['consultation_type']); ?></span>
                    </div>
                    
                    <div>
                        <?php echo getStatusBadge($appt['status']); ?>
                    </div>
                    
                    <div class="appointment-actions" style="flex-direction: column; gap: var(--spacing-2); border: none; padding: 0; margin: 0;">
                        <a href="<?php echo SITE_URL; ?>/patient/appointment-details.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-secondary">
                            View Details
                        </a>
                        <?php if ($appt['status'] === 'pending' || $appt['status'] === 'confirmed'): ?>
                        <a href="<?php echo SITE_URL; ?>/patient/reschedule.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-outline">
                            Reschedule
                        </a>
                        <button type="button" class="btn btn-sm btn-link text-danger" onclick="showCancelModal(<?php echo $appt['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Cancel Appointment</h3>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <p style="margin-bottom: var(--spacing-4);">Are you sure you want to cancel this appointment?</p>
                <input type="hidden" name="cancel_id" id="cancelAppointmentId">
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
const SITE_URL = '<?php echo SITE_URL; ?>';

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.appointment-row');
    
    rows.forEach(row => {
        const searchData = row.dataset.search;
        if (searchData.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Cancel modal
function showCancelModal(appointmentId) {
    document.getElementById('cancelAppointmentId').value = appointmentId;
    document.getElementById('cancelModal').classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

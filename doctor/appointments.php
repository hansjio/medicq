<?php
/**
 * MEDICQ - Doctor Appointments List
 */

$pageTitle = 'Manage Appointments';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/doctor.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('doctor');

$doctorModel = new Doctor();
$appointmentModel = new Appointment();

$doctor = $doctorModel->getByUserId($_SESSION['user_id']);
$doctorId = $doctor['id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = (int)$_POST['appointment_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'confirm':
            $result = $appointmentModel->updateStatus($appointmentId, 'confirmed');
            setFlashMessage($result['success'] ? 'success' : 'danger', $result['message']);
            break;
            
        case 'decline':
        case 'cancel':
            $reason = sanitize($_POST['reason'] ?? 'Declined by doctor');
            $result = $appointmentModel->updateStatus($appointmentId, 'cancelled', 'doctor', $reason);
            setFlashMessage($result['success'] ? 'success' : 'danger', $result['message']);
            break;
            
        case 'complete':
            $result = $appointmentModel->updateStatus($appointmentId, 'completed');
            setFlashMessage($result['success'] ? 'success' : 'danger', $result['message']);
            break;
            
        case 'add_notes':
            $notes = sanitize($_POST['notes']);
            $appointmentModel->addNotes($appointmentId, $notes);
            setFlashMessage('success', 'Notes added successfully');
            break;
    }
    
    redirect(SITE_URL . '/doctor/appointments.php');
}

// Get filter
$filter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? null;

// Get appointments
if ($filter === 'pending') {
    $appointments = $appointmentModel->getForDoctor($doctorId, 'pending');
} elseif ($filter === 'confirmed') {
    $appointments = $appointmentModel->getForDoctor($doctorId, 'confirmed');
} elseif ($filter === 'completed') {
    $appointments = $appointmentModel->getForDoctor($doctorId, 'completed');
} else {
    $appointments = $appointmentModel->getForDoctor($doctorId, null, $dateFilter);
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
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Manage Appointments</h1>
        <p class="text-muted">View and manage your patient appointments</p>
    </div>
    
    <!-- Filters -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-6);">
        <div class="tabs" style="margin: 0;">
            <a href="?status=all" class="tab-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="tab-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=confirmed" class="tab-btn <?php echo $filter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
            <a href="?status=completed" class="tab-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>
        
        <div class="form-group mb-0">
            <input type="date" class="form-control" id="dateFilter" value="<?php echo $dateFilter; ?>" onchange="filterByDate(this.value)">
        </div>
    </div>
    
    <!-- Appointments Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <i class="far fa-calendar-alt"></i>
                <h3>No Appointments Found</h3>
                <p>You don't have any <?php echo $filter !== 'all' ? $filter : ''; ?> appointments.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($appt['patient_email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <?php echo formatDate($appt['appointment_date']); ?><br>
                                <small class="text-muted"><?php echo formatTime($appt['appointment_time']); ?></small>
                            </td>
                            <td><?php echo getConsultationIcon($appt['consultation_type']); ?></td>
                            <td><?php echo getStatusBadge($appt['status']); ?></td>
                            <td>
                                <div style="display: flex; gap: var(--spacing-2);">
                                    <?php if ($appt['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                        <input type="hidden" name="action" value="confirm">
                                        <button type="submit" class="btn btn-sm btn-success" title="Confirm">
                                            <i class="fas fa-check"></i> Confirm
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Decline">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php elseif ($appt['status'] === 'confirmed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="btn btn-sm btn-success" title="Mark Complete">
                                            <i class="fas fa-check-double"></i> Complete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="<?php echo SITE_URL; ?>/doctor/appointment-details.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const SITE_URL = '<?php echo SITE_URL; ?>';

function filterByDate(date) {
    if (date) {
        window.location.href = '?date=' + date;
    } else {
        window.location.href = '?status=all';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

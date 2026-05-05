<?php
/**
 * MEDICQ - Patient Reschedule Appointment
 */

$pageTitle = 'Reschedule Appointment';
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

// Verify ownership and that it can be rescheduled
if (!$appointment || $appointment['patient_id'] != $_SESSION['user_id']) {
    setFlashMessage('danger', 'Appointment not found.');
    redirect(SITE_URL . '/patient/appointments.php');
}

if (!in_array($appointment['status'], ['pending', 'confirmed'])) {
    setFlashMessage('danger', 'This appointment cannot be rescheduled.');
    redirect(SITE_URL . '/patient/appointments.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDate = sanitize($_POST['appointment_date'] ?? '');
    $newTime = sanitize($_POST['appointment_time'] ?? '');

    if (empty($newDate)) {
        $errors[] = 'Please select a new date.';
    } elseif ($newDate < date('Y-m-d')) {
        $errors[] = 'Please select a future date.';
    }

    if (empty($newTime)) {
        $errors[] = 'Please select a time slot.';
    }

    if (empty($errors)) {
        $result = $appointmentModel->reschedule($appointmentId, $newDate, $newTime);

        if ($result['success']) {
            setFlashMessage('success', 'Appointment rescheduled successfully!');
            redirect(SITE_URL . '/patient/appointments.php');
        } else {
            $errors[] = $result['message'];
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:700px;">
    <div style="margin-bottom: var(--spacing-6);">
        <a href="<?php echo SITE_URL; ?>/patient/appointment-details.php?id=<?php echo $appointmentId; ?>"
           class="btn btn-link" style="padding:0; margin-bottom: var(--spacing-4);">
            <i class="fas fa-arrow-left"></i> Back to Appointment
        </a>
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Reschedule Appointment</h1>
        <p class="text-muted">Choose a new date and time for your appointment</p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php foreach ($errors as $e): ?>
        <div><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Current Appointment Summary -->
    <div class="card mb-6">
        <div class="card-header"><h4 style="margin:0;">Current Appointment</h4></div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap: var(--spacing-4);">
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Doctor</p>
                    <p style="font-weight:600; color: var(--primary); margin:0;"><?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                    <p style="color: var(--gray-600); font-size: var(--font-size-sm); margin:0;"><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                </div>
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Current Date & Time</p>
                    <p style="font-weight:500; margin:0;"><?php echo formatDate($appointment['appointment_date'], 'l, F d, Y'); ?></p>
                    <p style="color: var(--gray-600); font-size: var(--font-size-sm); margin:0;"><?php echo formatTime($appointment['appointment_time']); ?></p>
                </div>
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Type</p>
                    <p style="margin:0;"><?php echo getConsultationIcon($appointment['consultation_type']); ?></p>
                </div>
                <div>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Status</p>
                    <p style="margin:0;"><?php echo getStatusBadge($appointment['status']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- New Date & Time Selection -->
    <div class="card">
        <div class="card-header"><h4 style="margin:0;">Select New Date & Time</h4></div>
        <div class="card-body">
            <form method="POST" id="rescheduleForm">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap: var(--spacing-6); margin-bottom: var(--spacing-6);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">New Date</label>
                        <input type="date" id="newDate" name="appointment_date" class="form-control"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>"
                               required>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Available Time Slots</label>
                        <div id="timeSlotsContainer">
                            <p class="text-muted">Please select a date first</p>
                        </div>
                        <input type="hidden" name="appointment_time" id="selectedTime"
                               value="<?php echo htmlspecialchars($_POST['appointment_time'] ?? ''); ?>">
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Rescheduling will set your appointment back to <strong>Pending</strong> status until the doctor confirms the new time.
                </div>

                <div style="display:flex; gap: var(--spacing-4); margin-top: var(--spacing-6);">
                    <a href="<?php echo SITE_URL; ?>/patient/appointment-details.php?id=<?php echo $appointmentId; ?>"
                       class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i> Confirm Reschedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const SITE_URL   = '<?php echo SITE_URL; ?>';
const DOCTOR_ID  = <?php echo $appointment['doctor_id']; ?>;
const CURRENT_ID = <?php echo $appointmentId; ?>;

document.getElementById('newDate').addEventListener('change', function () {
    const date = this.value;
    if (!date) return;

    const container = document.getElementById('timeSlotsContainer');
    container.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading slots...</p>';

    fetch(`${SITE_URL}/api/get-slots.php?doctor_id=${DOCTOR_ID}&date=${date}&exclude_id=${CURRENT_ID}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.slots.length > 0) {
                container.innerHTML = `
                    <div class="time-slots-grid">
                        ${data.slots.map(slot => `
                            <div class="time-slot" data-time="${slot.start}">${slot.display}</div>
                        `).join('')}
                    </div>`;
                container.querySelectorAll('.time-slot').forEach(slot => {
                    slot.addEventListener('click', function () {
                        container.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                        this.classList.add('selected');
                        document.getElementById('selectedTime').value = this.dataset.time;
                    });
                });
            } else {
                container.innerHTML = '<p class="text-muted">No available slots for this date. Please try another date.</p>';
            }
        })
        .catch(() => {
            container.innerHTML = '<p class="text-danger">Failed to load slots. Please try again.</p>';
        });
});

document.getElementById('rescheduleForm').addEventListener('submit', function (e) {
    if (!document.getElementById('selectedTime').value) {
        e.preventDefault();
        alert('Please select a time slot.');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
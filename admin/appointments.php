<?php
/**
 * MEDICQ - Admin: Manage Appointments
 * Place this file at: admin/appointments.php
 *
 * FIXES applied:
 *   - new Appointment()  (was: new Appointment($pdo))
 *   - d.specialization   (was: d.specialty — column doesn't exist)
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/appointment.php';

requireLogin();
requireRole('admin');

$appointmentManager = new Appointment();   // FIX: no argument
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $appointmentId = intval($_POST['appointment_id']);
        $newStatus     = $_POST['new_status'];
        $result = $appointmentManager->updateStatus($appointmentId, $newStatus);
        if ($result['success']) { $message = $result['message']; }
        else                    { $error   = $result['message']; }
    }

    if (isset($_POST['delete_appointment'])) {
        $appointmentId = intval($_POST['appointment_id']);
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$appointmentId]);
        $message = 'Appointment deleted successfully!';
    }
}

$statusFilter = $_GET['status']    ?? '';
$doctorFilter = $_GET['doctor_id'] ?? '';
$dateFilter   = $_GET['date']      ?? '';

// FIX: use d.specialization, not d.specialty
$query  = "
    SELECT a.*, u.full_name as patient_name, u.email as patient_email,
           d.specialization, du.full_name as doctor_name
    FROM appointments a
    JOIN users u  ON a.patient_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users du ON d.user_id = du.id
    WHERE 1=1
";
$params = [];

if ($statusFilter) { $query .= " AND a.status = ?";           $params[] = $statusFilter; }
if ($doctorFilter) { $query .= " AND a.doctor_id = ?";        $params[] = $doctorFilter; }
if ($dateFilter)   { $query .= " AND a.appointment_date = ?"; $params[] = $dateFilter;   }

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Doctors for filter dropdown
$doctors = $pdo->query("
    SELECT d.id, d.specialization, u.full_name
    FROM doctors d JOIN users u ON d.user_id = u.id
    ORDER BY u.full_name
")->fetchAll();

$pageTitle = 'Manage Appointments';
require_once '../includes/header.php';
?>

<div class="container">
    <?php if ($message): ?>
    <div class="alert alert-success" data-dismiss><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger" data-dismiss><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="mb-8">
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Manage Appointments</h1>
        <p class="text-muted">View and manage all appointments in the system</p>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" style="display:flex; gap: var(--spacing-4); flex-wrap:wrap; align-items:flex-end;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <?php foreach (['pending','confirmed','completed','cancelled','no-show'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-control">
                        <option value="">All Doctors</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo $doc['id']; ?>" <?php echo $doctorFilter == $doc['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doc['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($dateFilter); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="appointments.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:0;">
            <?php if (empty($appointments)): ?>
            <div class="empty-state"><i class="fas fa-calendar-times"></i><h3>No Appointments Found</h3></div>
            <?php else: ?>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left; font-size: var(--font-size-sm);">Patient</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left; font-size: var(--font-size-sm);">Doctor</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left; font-size: var(--font-size-sm);">Date & Time</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left; font-size: var(--font-size-sm);">Type</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left; font-size: var(--font-size-sm);">Status</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left; font-size: var(--font-size-sm);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $apt): ?>
                    <tr style="border-bottom: 1px solid var(--gray-100);">
                        <td style="padding: var(--spacing-3) var(--spacing-4);">
                            <?php echo htmlspecialchars($apt['patient_name']); ?><br>
                            <span class="text-muted" style="font-size: var(--font-size-sm);"><?php echo htmlspecialchars($apt['patient_email']); ?></span>
                        </td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);">
                            <?php echo htmlspecialchars($apt['doctor_name']); ?><br>
                            <span class="text-muted" style="font-size: var(--font-size-sm);"><?php echo htmlspecialchars($apt['specialization']); ?></span>
                        </td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);">
                            <?php echo formatDate($apt['appointment_date']); ?><br>
                            <span class="text-muted" style="font-size: var(--font-size-sm);"><?php echo formatTime($apt['appointment_time']); ?></span>
                        </td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);"><?php echo getConsultationIcon($apt['consultation_type']); ?></td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);"><?php echo getStatusBadge($apt['status']); ?></td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                <select name="new_status" class="form-control" style="display:inline; width:auto; font-size: var(--font-size-sm);">
                                    <?php foreach (['pending','confirmed','completed','cancelled','no-show'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $apt['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this appointment?')">
                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                <button type="submit" name="delete_appointment" class="btn btn-sm btn-link text-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

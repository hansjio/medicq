<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/appointment.php';

requireLogin();
requireRole('admin');

$appointmentManager = new Appointment($pdo);
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $appointmentId = intval($_POST['appointment_id']);
        $newStatus = $_POST['new_status'];
        
        $result = $appointmentManager->updateStatus($appointmentId, $newStatus);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_POST['delete_appointment'])) {
        $appointmentId = intval($_POST['appointment_id']);
        
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$appointmentId]);
        
        $message = 'Appointment deleted successfully!';
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? '';
$doctorFilter = $_GET['doctor_id'] ?? '';
$patientFilter = $_GET['patient_id'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build query
$query = "
    SELECT a.*, u.full_name as patient_name, u.email as patient_email,
           d.specialty, du.full_name as doctor_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users du ON d.user_id = du.id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $query .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if ($doctorFilter) {
    $query .= " AND a.doctor_id = ?";
    $params[] = $doctorFilter;
}

if ($patientFilter) {
    $query .= " AND a.patient_id = ?";
    $params[] = $patientFilter;
}

if ($dateFilter) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get doctors for filter
$stmt = $pdo->query("SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.full_name");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Appointments';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Manage Appointments</h1>
                <p class="text-muted">View and manage all appointments</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="doctor_id" class="form-control form-control-sm">
                            <option value="">All Doctors</option>
                            <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['id']; ?>" <?php echo $doctorFilter == $doc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doc['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="date" name="date" class="form-control form-control-sm" 
                               value="<?php echo htmlspecialchars($dateFilter); ?>" placeholder="Filter by date">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="appointments.php" class="btn btn-sm btn-secondary">Clear</a>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <p class="text-muted text-center py-4">No appointments found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td>#<?php echo $apt['id']; ?></td>
                                    <td>
                                        <div class="cell-info">
                                            <strong><?php echo htmlspecialchars($apt['patient_name']); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($apt['patient_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cell-info">
                                            <strong><?php echo htmlspecialchars($apt['doctor_name']); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($apt['specialty']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cell-info">
                                            <strong><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></strong>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="consultation-type">
                                            <?php 
                                            $typeIcons = [
                                                'in-person' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
                                                'video' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>',
                                                'phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"></path></svg>'
                                            ];
                                            $type = $apt['consultation_type'] ?? 'in-person';
                                            echo $typeIcons[$type] ?? $typeIcons['in-person'];
                                            echo ' ' . ucfirst($type);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $apt['status']; ?>">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($apt['status'] === 'pending'): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="new_status" value="confirmed">
                                                <button type="submit" class="btn btn-sm btn-success">Confirm</button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($apt['status'] === 'confirmed'): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="new_status" value="completed">
                                                <button type="submit" class="btn btn-sm btn-primary">Complete</button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($apt['status'], ['pending', 'confirmed'])): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="new_status" value="cancelled">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                                <input type="hidden" name="delete_appointment" value="1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
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
</main>

<style>
.filter-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-form .form-group {
    margin-bottom: 0;
}

.cell-info {
    display: flex;
    flex-direction: column;
}

.cell-info small {
    font-size: 0.8rem;
}

.consultation-type {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
</style>

<?php require_once '../includes/footer.php'; ?>

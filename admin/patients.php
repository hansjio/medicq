<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_patient'])) {
        $userId = intval($_POST['user_id']);
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $dateOfBirth = $_POST['date_of_birth'] ?: null;
        $address = trim($_POST['address']);
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, date_of_birth = ?, address = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $dateOfBirth, $address, $userId]);
        
        $message = 'Patient updated successfully!';
    }
    
    if (isset($_POST['delete_patient'])) {
        $userId = intval($_POST['user_id']);
        
        // Check for appointments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status IN ('pending', 'confirmed')");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Cannot delete patient with active appointments.';
        } else {
            // Delete appointments first
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE patient_id = ?");
            $stmt->execute([$userId]);
            
            // Delete notifications
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $message = 'Patient deleted successfully!';
        }
    }
}

// Get search query
$search = $_GET['search'] ?? '';

// Get all patients
$query = "SELECT * FROM users WHERE role = 'patient'";
$params = [];

if ($search) {
    $query .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Patients';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Manage Patients</h1>
                <p class="text-muted">View and manage patient accounts</p>
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
                <form method="GET" action="" class="search-form">
                    <div class="search-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search): ?>
                        <a href="patients.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($patients)): ?>
                    <p class="text-muted text-center py-4">No patients found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Phone</th>
                                    <th>Date of Birth</th>
                                    <th>Registered</th>
                                    <th>Appointments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td>
                                        <div class="patient-info">
                                            <div class="patient-avatar">
                                                <?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong>
                                                <small class="text-muted"><?php echo htmlspecialchars($patient['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($patient['phone'] ?? '-'); ?></td>
                                    <td>
                                        <?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : '-'; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
                                        $stmt->execute([$patient['id']]);
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="editPatient(<?php echo htmlspecialchars(json_encode($patient)); ?>)">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="viewAppointments(<?php echo $patient['id']; ?>)">
                                                View
                                            </button>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                                <input type="hidden" name="delete_patient" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo $patient['id']; ?>">
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

<!-- Edit Patient Modal -->
<div id="editPatientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Patient</h3>
            <button type="button" class="modal-close" onclick="closeModal('editPatientModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="update_patient" value="1">
            <input type="hidden" name="user_id" id="edit_patient_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" id="edit_patient_name" class="form-control" required>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="edit_patient_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="edit_patient_dob" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_patient_address" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editPatientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPatient(patient) {
    document.getElementById('edit_patient_id').value = patient.id;
    document.getElementById('edit_patient_name').value = patient.full_name;
    document.getElementById('edit_patient_phone').value = patient.phone || '';
    document.getElementById('edit_patient_dob').value = patient.date_of_birth || '';
    document.getElementById('edit_patient_address').value = patient.address || '';
    
    openModal('editPatientModal');
}

function viewAppointments(patientId) {
    window.location.href = 'appointments.php?patient_id=' + patientId;
}
</script>

<style>
.search-form {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
}

.search-input-wrapper svg {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.search-input-wrapper input {
    padding-left: 40px;
}

.patient-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.patient-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.patient-info > div {
    display: flex;
    flex-direction: column;
}

.patient-info small {
    font-size: 0.8rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>

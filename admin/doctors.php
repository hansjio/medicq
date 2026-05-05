<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/doctor.php';

requireLogin();
requireRole('admin');

$doctorManager = new Doctor($pdo);
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_doctor'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $specialty = trim($_POST['specialty']);
        $clinic = trim($_POST['clinic']);
        $consultationFee = floatval($_POST['consultation_fee']);
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, role) VALUES (?, ?, ?, ?, 'doctor')");
            $stmt->execute([$email, $hashedPassword, $fullName, $phone]);
            $userId = $pdo->lastInsertId();
            
            // Create doctor record
            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, specialty, clinic_name, consultation_fee) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $specialty, $clinic, $consultationFee]);
            
            $message = 'Doctor added successfully!';
        }
    }
    
    if (isset($_POST['update_doctor'])) {
        $doctorId = intval($_POST['doctor_id']);
        $userId = intval($_POST['user_id']);
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $specialty = trim($_POST['specialty']);
        $clinic = trim($_POST['clinic']);
        $consultationFee = floatval($_POST['consultation_fee']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $userId]);
        
        $stmt = $pdo->prepare("UPDATE doctors SET specialty = ?, clinic_name = ?, consultation_fee = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$specialty, $clinic, $consultationFee, $isActive, $doctorId]);
        
        $message = 'Doctor updated successfully!';
    }
    
    if (isset($_POST['delete_doctor'])) {
        $doctorId = intval($_POST['doctor_id']);
        $userId = intval($_POST['user_id']);
        
        // Check for appointments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status IN ('pending', 'confirmed')");
        $stmt->execute([$doctorId]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Cannot delete doctor with active appointments.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt->execute([$doctorId]);
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $message = 'Doctor deleted successfully!';
        }
    }
}

// Get all doctors
$doctors = $doctorManager->getAll();

$pageTitle = 'Manage Doctors';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Manage Doctors</h1>
                <p class="text-muted">Add, edit, and manage doctor accounts</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openModal('addDoctorModal')">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Doctor
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <?php if (empty($doctors)): ?>
                    <p class="text-muted text-center py-4">No doctors found. Add your first doctor!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Clinic</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th>Appointments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doc): ?>
                                <tr>
                                    <td>
                                        <div class="doctor-info">
                                            <strong><?php echo htmlspecialchars($doc['full_name']); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($doc['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['specialty']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['clinic_name'] ?? '-'); ?></td>
                                    <td>PHP <?php echo number_format($doc['consultation_fee'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $doc['is_active'] ? 'confirmed' : 'cancelled'; ?>">
                                            <?php echo $doc['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
                                        $stmt->execute([$doc['id']]);
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doc)); ?>)">
                                                Edit
                                            </button>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this doctor?');">
                                                <input type="hidden" name="delete_doctor" value="1">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doc['id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $doc['user_id']; ?>">
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

<!-- Add Doctor Modal -->
<div id="addDoctorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Doctor</h3>
            <button type="button" class="modal-close" onclick="closeModal('addDoctorModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="add_doctor" value="1">
            <div class="modal-body">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Specialty</label>
                        <select name="specialty" class="form-control" required>
                            <option value="">Select Specialty</option>
                            <option value="Cardiology">Cardiology</option>
                            <option value="Dermatology">Dermatology</option>
                            <option value="Endocrinology">Endocrinology</option>
                            <option value="Gastroenterology">Gastroenterology</option>
                            <option value="General Practice">General Practice</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Obstetrics & Gynecology">Obstetrics & Gynecology</option>
                            <option value="Oncology">Oncology</option>
                            <option value="Ophthalmology">Ophthalmology</option>
                            <option value="Orthopedics">Orthopedics</option>
                            <option value="Pediatrics">Pediatrics</option>
                            <option value="Psychiatry">Psychiatry</option>
                            <option value="Pulmonology">Pulmonology</option>
                            <option value="Radiology">Radiology</option>
                            <option value="Urology">Urology</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consultation Fee (PHP)</label>
                        <input type="number" name="consultation_fee" class="form-control" value="500" min="0" step="50">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Clinic/Hospital Name</label>
                    <input type="text" name="clinic" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addDoctorModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Doctor</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Doctor Modal -->
<div id="editDoctorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Doctor</h3>
            <button type="button" class="modal-close" onclick="closeModal('editDoctorModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="update_doctor" value="1">
            <input type="hidden" name="doctor_id" id="edit_doctor_id">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="modal-body">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control">
                    </div>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Specialty</label>
                        <select name="specialty" id="edit_specialty" class="form-control" required>
                            <option value="Cardiology">Cardiology</option>
                            <option value="Dermatology">Dermatology</option>
                            <option value="Endocrinology">Endocrinology</option>
                            <option value="Gastroenterology">Gastroenterology</option>
                            <option value="General Practice">General Practice</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Obstetrics & Gynecology">Obstetrics & Gynecology</option>
                            <option value="Oncology">Oncology</option>
                            <option value="Ophthalmology">Ophthalmology</option>
                            <option value="Orthopedics">Orthopedics</option>
                            <option value="Pediatrics">Pediatrics</option>
                            <option value="Psychiatry">Psychiatry</option>
                            <option value="Pulmonology">Pulmonology</option>
                            <option value="Radiology">Radiology</option>
                            <option value="Urology">Urology</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consultation Fee (PHP)</label>
                        <input type="number" name="consultation_fee" id="edit_consultation_fee" class="form-control" min="0" step="50">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Clinic/Hospital Name</label>
                    <input type="text" name="clinic" id="edit_clinic" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <span>Active (can receive appointments)</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editDoctorModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDoctor(doctor) {
    document.getElementById('edit_doctor_id').value = doctor.id;
    document.getElementById('edit_user_id').value = doctor.user_id;
    document.getElementById('edit_full_name').value = doctor.full_name;
    document.getElementById('edit_phone').value = doctor.phone || '';
    document.getElementById('edit_specialty').value = doctor.specialty;
    document.getElementById('edit_clinic').value = doctor.clinic_name || '';
    document.getElementById('edit_consultation_fee').value = doctor.consultation_fee;
    document.getElementById('edit_is_active').checked = doctor.is_active == 1;
    
    openModal('editDoctorModal');
}
</script>

<style>
.doctor-info {
    display: flex;
    flex-direction: column;
}

.doctor-info small {
    font-size: 0.8rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}
</style>

<?php require_once '../includes/footer.php'; ?>

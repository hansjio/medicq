<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/doctor.php';

requireLogin();
requireRole('doctor');

$doctor = new Doctor($pdo);
$doctorInfo = $doctor->getByUserId($_SESSION['user_id']);

if (!$doctorInfo) {
    header('Location: ../logout.php');
    exit;
}

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $specialty = trim($_POST['specialty']);
        $clinic = trim($_POST['clinic']);
        $bio = trim($_POST['bio']);
        $consultationFee = floatval($_POST['consultation_fee']);
        
        // Update user info
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $_SESSION['user_id']]);
        
        // Update doctor info
        $stmt = $pdo->prepare("UPDATE doctors SET specialty = ?, clinic_name = ?, bio = ?, consultation_fee = ? WHERE user_id = ?");
        $stmt->execute([$specialty, $clinic, $bio, $consultationFee, $_SESSION['user_id']]);
        
        $_SESSION['full_name'] = $fullName;
        $message = 'Profile updated successfully!';
        
        // Refresh doctor info
        $doctorInfo = $doctor->getByUserId($_SESSION['user_id']);
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            $message = 'Password changed successfully!';
        }
    }
}

$pageTitle = 'Profile Settings';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Profile Settings</h1>
            <p class="text-muted">Manage your professional information</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Professional Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($doctorInfo['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($doctorInfo['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($doctorInfo['phone'] ?? ''); ?>" 
                                       placeholder="+63 912 345 6789">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Specialty</label>
                                <select name="specialty" class="form-control" required>
                                    <option value="">Select Specialty</option>
                                    <?php
                                    $specialties = ['Cardiology', 'Dermatology', 'Endocrinology', 'Gastroenterology', 
                                                   'General Practice', 'Neurology', 'Obstetrics & Gynecology', 
                                                   'Oncology', 'Ophthalmology', 'Orthopedics', 'Pediatrics', 
                                                   'Psychiatry', 'Pulmonology', 'Radiology', 'Urology'];
                                    foreach ($specialties as $spec):
                                    ?>
                                    <option value="<?php echo $spec; ?>" <?php echo $doctorInfo['specialty'] === $spec ? 'selected' : ''; ?>>
                                        <?php echo $spec; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label">Clinic/Hospital Name</label>
                                <input type="text" name="clinic" class="form-control" 
                                       value="<?php echo htmlspecialchars($doctorInfo['clinic_name'] ?? ''); ?>" 
                                       placeholder="e.g., Heart Care Clinic">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Consultation Fee (PHP)</label>
                                <input type="number" name="consultation_fee" class="form-control" 
                                       value="<?php echo $doctorInfo['consultation_fee'] ?? 500; ?>" 
                                       min="0" step="50">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Professional Bio</label>
                            <textarea name="bio" class="form-control" rows="4" 
                                      placeholder="Brief description of your experience and expertise..."><?php echo htmlspecialchars($doctorInfo['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Change Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

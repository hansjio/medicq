<?php
$pageTitle = 'Profile Settings';
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/doctor.php';

requireRole('doctor');

// FIX: Doctor() takes no arguments — remove $pdo
$auth       = new Auth();
$doctorObj  = new Doctor();
$doctorInfo = $doctorObj->getByUserId($_SESSION['user_id']);

if (!$doctorInfo) {
    redirect(SITE_URL . '/logout.php');
}

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName   = sanitize($_POST['full_name']);
        $phone      = sanitize($_POST['phone']);

        // Update base user fields via Auth class
        $result = $auth->updateProfile($_SESSION['user_id'], [
            'full_name' => $fullName,
            'phone'     => $phone,
        ]);

        // Update doctor-specific fields using PDO (which is now defined in config.php)
        $specialization  = sanitize($_POST['specialization']);
        $clinicName      = sanitize($_POST['clinic_name']);
        $clinicAddress   = sanitize($_POST['clinic_address']);
        $bio             = sanitize($_POST['bio']);
        $consultationFee = floatval($_POST['consultation_fee']);
        $licenseNumber   = sanitize($_POST['license_number']);

        $stmt = $pdo->prepare(
            "UPDATE doctors
             SET specialization = ?, clinic_name = ?, clinic_address = ?,
                 bio = ?, consultation_fee = ?, license_number = ?
             WHERE user_id = ?"
        );
        $stmt->execute([
            $specialization, $clinicName, $clinicAddress,
            $bio, $consultationFee, $licenseNumber,
            $_SESSION['user_id'],
        ]);

        if ($result['success']) {
            $message = 'Profile updated successfully!';
            $doctorInfo = $doctorObj->getByUserId($_SESSION['user_id']); // refresh
        } else {
            $error = $result['message'];
        }
    }

    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword     = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (empty($currentPassword) || empty($newPassword)) {
            $error = 'Please fill in all password fields.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            // FIX: Use Auth class instead of raw $pdo queries
            $result = $auth->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
            if ($result['success']) { $message = $result['message']; }
            else { $error = $result['message']; }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container" style="max-width:900px;">
    <div class="mb-8">
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Profile Settings</h1>
        <p class="text-muted">Manage your personal and professional information</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success" data-dismiss><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger" data-dismiss><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Personal & Professional Information -->
    <div class="profile-section mb-6">
        <h3>Personal & Professional Information</h3>
        <form method="POST">
            <div class="profile-grid">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?php echo htmlspecialchars($doctorInfo['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control"
                           value="<?php echo htmlspecialchars($doctorInfo['email']); ?>" disabled>
                    <span class="form-hint">Email cannot be changed</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?php echo htmlspecialchars($doctorInfo['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control"
                           value="<?php echo htmlspecialchars($doctorInfo['specialization'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Clinic Name</label>
                    <input type="text" name="clinic_name" class="form-control"
                           value="<?php echo htmlspecialchars($doctorInfo['clinic_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">License Number</label>
                    <input type="text" name="license_number" class="form-control"
                           value="<?php echo htmlspecialchars($doctorInfo['license_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Consultation Fee (PHP)</label>
                    <input type="number" name="consultation_fee" class="form-control" step="0.01"
                           value="<?php echo htmlspecialchars($doctorInfo['consultation_fee'] ?? '0'); ?>">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Clinic Address</label>
                    <input type="text" name="clinic_address" class="form-control"
                           value="<?php echo htmlspecialchars($doctorInfo['clinic_address'] ?? ''); ?>">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($doctorInfo['bio'] ?? ''); ?></textarea>
                </div>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="profile-section">
        <h3>Change Password</h3>
        <form method="POST" style="max-width:400px;">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="6">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control">
            </div>
            <button type="submit" name="change_password" class="btn btn-secondary">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
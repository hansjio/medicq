<?php
/**
 * MEDICQ - Admin Profile Settings
 */

$pageTitle = 'Profile Settings';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$auth = new Auth();
$user = $auth->getCurrentUser();

$errors  = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $data = [
            'full_name' => sanitize($_POST['full_name']),
            'phone'     => sanitize($_POST['phone']),
        ];

        $result = $auth->updateProfile($_SESSION['user_id'], $data);

        if ($result['success']) {
            $success = 'Profile updated successfully!';
            $user    = $auth->getCurrentUser();
        } else {
            $errors['profile'] = $result['message'];
        }
    }

    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword     = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (empty($currentPassword) || empty($newPassword)) {
            $errors['password'] = 'Please fill in all password fields';
        } elseif (strlen($newPassword) < 6) {
            $errors['password'] = 'New password must be at least 6 characters';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['password'] = 'New passwords do not match';
        } else {
            $result = $auth->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);

            if ($result['success']) {
                $success = 'Password changed successfully!';
            } else {
                $errors['password'] = $result['message'];
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div class="mb-8">
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Profile Settings</h1>
        <p class="text-muted">Manage your administrator account information</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success" data-dismiss>
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <!-- Personal Information -->
    <div class="profile-section">
        <h3>Personal Information</h3>

        <?php if (isset($errors['profile'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($errors['profile']); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="profile-grid">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control"
                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    <span class="form-hint">Email cannot be changed</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                           placeholder="+63 9XX XXX XXXX">
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="Administrator" disabled>
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

        <?php if (isset($errors['password'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($errors['password']); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="profile-grid">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <button type="submit" name="change_password" class="btn btn-secondary">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
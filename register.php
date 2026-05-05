<?php
/**
 * MEDICQ - Registration Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/' . getUserRole() . '/dashboard.php');
}

$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['phone'] = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Register if no errors
    if (empty($errors)) {
        $auth = new Auth();
        $result = $auth->register($formData['email'], $password, $formData['full_name'], $formData['phone']);
        
        if ($result['success']) {
            // Auto login after registration
            $auth->login($formData['email'], $password);
            setFlashMessage('success', 'Welcome to MEDICQ! Your account has been created.');
            redirect(SITE_URL . '/patient/dashboard.php');
        } else {
            $errors['general'] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-form-section" style="max-width: 480px;">
            <div class="auth-logo">
                <a href="<?php echo SITE_URL; ?>" class="logo" style="justify-content: flex-start;">
                    <svg width="50" height="50" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="100" height="100" rx="20" fill="#1e3a5f"/>
                        <path d="M30 50C30 38.954 38.954 30 50 30C61.046 30 70 38.954 70 50C70 61.046 61.046 70 50 70" stroke="#0891b2" stroke-width="6" stroke-linecap="round"/>
                        <circle cx="50" cy="50" r="8" fill="#0891b2"/>
                        <path d="M50 70C50 70 35 70 35 70C35 70 35 55 50 55" stroke="#22d3ee" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <div>
                        <span style="font-size: 1.5rem; font-weight: 700; color: #1e3a5f;">MEDICQ</span>
                        <span style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 400;">MEDICAL APPOINTMENT</span>
                    </div>
                </a>
            </div>
            
            <h2 style="margin-bottom: 0.5rem;">Create an Account</h2>
            <p style="color: var(--gray-500); margin-bottom: 2rem;">Join MEDICQ to manage your medical appointments</p>
            
            <form method="POST" class="auth-form">
                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" data-dismiss>
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                        required
                    >
                    <?php if (isset($errors['full_name'])): ?>
                    <div class="form-error"><?php echo $errors['full_name']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($formData['email']); ?>"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                    <div class="form-error"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number (Optional)</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-control" 
                        placeholder="Enter your phone number"
                        value="<?php echo htmlspecialchars($formData['phone']); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                        placeholder="Create a password (min. 6 characters)"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                    <div class="form-error"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                        placeholder="Confirm your password"
                        required
                    >
                    <?php if (isset($errors['confirm_password'])): ?>
                    <div class="form-error"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="terms" required>
                        <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Create Account
                </button>
                
                <div class="auth-footer">
                    Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Log in</a>
                </div>
            </form>
        </div>
        
        <div class="auth-image-section" style="background-image: url('<?php echo SITE_URL; ?>/assets/images/login-bg.jpg');"></div>
    </div>
</body>
</html>

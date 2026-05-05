<?php
/**
 * MEDICQ - Login Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    redirect(SITE_URL . '/' . $role . '/dashboard.php');
}

$error = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $auth = new Auth();
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            // Check for redirect
            $redirectUrl = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            
            if ($redirectUrl) {
                redirect($redirectUrl);
            } else {
                redirect(SITE_URL . '/' . $result['role'] . '/dashboard.php');
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
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
        <div class="auth-form-section">
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
            
            <form method="POST" class="auth-form">
                <?php if ($error): ?>
                <div class="alert alert-danger" data-dismiss>
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                    >
                </div>
                
                <div class="auth-options">
                    <label class="form-check">
                        <input type="checkbox" name="remember">
                        <span>Remember for 30 days</span>
                    </label>
                    <a href="<?php echo SITE_URL; ?>/forgot-password.php">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Log In
                </button>
                
                <div class="auth-divider">or</div>
                
                <button type="button" class="btn btn-secondary btn-block btn-lg" disabled>
                    <i class="far fa-envelope"></i>
                    Log in with Email
                </button>
                
                <div class="auth-footer">
                    Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php">Sign up</a>
                </div>
            </form>
            
            <!-- Demo Credentials -->
            <div style="margin-top: 2rem; padding: 1rem; background: #f3f4f6; border-radius: 0.5rem; font-size: 0.875rem;">
                <strong>Demo Credentials:</strong>
                <div style="margin-top: 0.5rem; color: #6b7280;">
                    <div><strong>Patient:</strong> john.doe@email.com</div>
                    <div><strong>Doctor:</strong> sarah.johnson@medicq.com</div>
                    <div><strong>Admin:</strong> admin@medicq.com</div>
                    <div style="margin-top: 0.25rem;"><strong>Password:</strong> password</div>
                </div>
            </div>
        </div>
        
        <div class="auth-image-section" style="background-image: url('<?php echo SITE_URL; ?>/assets/images/login-bg.jpg');"></div>
    </div>
    
    <script>
        // Auto-fill demo credentials on click
        document.querySelectorAll('.auth-footer ~ div div').forEach(div => {
            div.style.cursor = 'pointer';
            div.addEventListener('click', function() {
                const text = this.textContent;
                if (text.includes('@')) {
                    const email = text.match(/[\w.-]+@[\w.-]+/);
                    if (email) {
                        document.getElementById('email').value = email[0];
                    }
                }
                if (text.toLowerCase().includes('password')) {
                    document.getElementById('password').value = 'password';
                }
            });
        });
    </script>
</body>
</html>

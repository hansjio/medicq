<?php
require_once 'includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // In a real system, this would send an email with a reset link
        // For this mock system, we'll just show a success message
        $message = 'If an account exists with this email, you will receive password reset instructions.';
        
        // Log the "reset" request (mock)
        error_log("Password reset requested for: $email");
    } else {
        // Don't reveal if email exists or not
        $message = 'If an account exists with this email, you will receive password reset instructions.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MEDICQ</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-form-section">
            <div class="auth-form-wrapper">
                <div class="auth-logo">
                    <img src="assets/images/logo.svg" alt="MEDICQ Logo">
                </div>
                
                <h2>Reset Password</h2>
                <p class="text-muted mb-4">Enter your email address and we'll send you instructions to reset your password.</p>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               placeholder="Enter your email address">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </form>
                
                <div class="auth-links mt-4">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
        
        <div class="auth-image-section">
            <img src="assets/images/login-bg.jpg" alt="Medical Background">
        </div>
    </div>
</body>
</html>

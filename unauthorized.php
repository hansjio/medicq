<?php
/**
 * MEDICQ - Unauthorized Access Page
 */

$pageTitle = 'Access Denied';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 600px; text-align: center; padding: var(--spacing-16) var(--spacing-4);">
    <div style="font-size: 4rem; color: var(--danger); margin-bottom: var(--spacing-4);">
        <i class="fas fa-lock"></i>
    </div>
    <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-3);">Access Denied</h1>
    <p class="text-muted" style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-8);">
        You don't have permission to view that page.
    </p>

    <?php if (isLoggedIn()): ?>
    <a href="<?php echo SITE_URL; ?>/<?php echo getUserRole(); ?>/dashboard.php" class="btn btn-primary btn-lg">
        <i class="fas fa-home"></i> Go to My Dashboard
    </a>
    <?php else: ?>
    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-lg">
        <i class="fas fa-sign-in-alt"></i> Back to Login
    </a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
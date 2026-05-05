<?php
/**
 * MEDICQ - All Notifications Page
 * Place this file at: notifications.php  (root of project, same level as login.php)
 */

$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$auth = new Auth();

// Mark a notification as read
if (isset($_GET['mark_read'])) {
    $auth->markNotificationRead((int)$_GET['mark_read'], $_SESSION['user_id']);
    redirect(SITE_URL . '/notifications.php');
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    redirect(SITE_URL . '/notifications.php');
}

// Load all notifications for this user
$conn  = Database::getInstance()->getConnection();
$stmt  = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-6);">
        <div>
            <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-1);">Notifications</h1>
            <p class="text-muted">
                <?php echo $unreadCount > 0 ? "$unreadCount unread notification" . ($unreadCount > 1 ? 's' : '') : 'All caught up!'; ?>
            </p>
        </div>
        <?php if ($unreadCount > 0): ?>
        <form method="POST">
            <button type="submit" name="mark_all_read" class="btn btn-secondary">
                <i class="fas fa-check-double"></i> Mark All as Read
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <?php if (empty($notifications)): ?>
            <div class="empty-state" style="padding: var(--spacing-8);">
                <i class="far fa-bell" style="font-size: 3rem; color: var(--gray-300); display: block; margin-bottom: var(--spacing-4);"></i>
                <h3>No Notifications</h3>
                <p>You have no notifications yet.</p>
            </div>
            <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
            <div style="display: flex; gap: var(--spacing-4); padding: var(--spacing-4); border-bottom: 1px solid var(--gray-100); <?php echo !$notif['is_read'] ? 'background: var(--primary-light);' : ''; ?>">
                <!-- Icon -->
                <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                     background: <?php echo [
                         'confirmation' => 'var(--success)',
                         'cancellation' => 'var(--danger)',
                         'reminder'     => 'var(--warning)',
                         'appointment'  => 'var(--primary)',
                         'system'       => 'var(--gray-400)',
                     ][$notif['type']] ?? 'var(--gray-400)'; ?>; color: white;">
                    <i class="fas fa-<?php echo [
                        'confirmation' => 'check',
                        'cancellation' => 'times',
                        'reminder'     => 'bell',
                        'appointment'  => 'calendar',
                        'system'       => 'info',
                    ][$notif['type']] ?? 'info'; ?>"></i>
                </div>

                <!-- Content -->
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <strong style="<?php echo !$notif['is_read'] ? 'color: var(--primary);' : ''; ?>">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </strong>
                        <?php if (!$notif['is_read']): ?>
                        <span style="background: var(--primary); color: white; font-size: 10px; padding: 2px 8px; border-radius: 99px; margin-left: var(--spacing-2);">NEW</span>
                        <?php endif; ?>
                    </div>
                    <p style="margin: var(--spacing-1) 0; color: var(--gray-700); font-size: var(--font-size-sm);">
                        <?php echo htmlspecialchars($notif['message']); ?>
                    </p>
                    <div style="display: flex; gap: var(--spacing-4); align-items: center; margin-top: var(--spacing-2);">
                        <span style="font-size: 12px; color: var(--gray-400);">
                            <i class="far fa-clock"></i>
                            <?php echo formatDate($notif['created_at'], 'M d, Y') . ' at ' . formatTime(date('H:i:s', strtotime($notif['created_at']))); ?>
                        </span>
                        <?php if (!$notif['is_read']): ?>
                        <a href="?mark_read=<?php echo $notif['id']; ?>" style="font-size: 12px; color: var(--primary);">
                            Mark as read
                        </a>
                        <?php endif; ?>
                        <?php if ($notif['related_appointment_id']): ?>
                        <a href="<?php echo SITE_URL; ?>/<?php echo getUserRole(); ?>/appointment-details.php?id=<?php echo $notif['related_appointment_id']; ?>"
                           style="font-size: 12px; color: var(--primary);">
                            View appointment →
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
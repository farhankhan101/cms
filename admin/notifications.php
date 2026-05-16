<?php
$page_title = "My Notifications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['admin', 'agent', 'user']);

$db = getDB();
$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: notifications.php");
    exit();
}

// Fetch all notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$all_notifs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-bell"></i> Notification History</span>
        <form method="POST">
            <button type="submit" name="mark_all_read" class="btn btn-secondary btn-sm">Mark all as read</button>
        </form>
    </div>

    <div class="notif-list">
        <?php if (empty($all_notifs)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-light);">
                <i class="fas fa-bell-slash" style="font-size: 40px; margin-bottom: 15px;"></i>
                <p>No notifications found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($all_notifs as $n): ?>
                <a href="<?php echo BASE_URL; ?>/notif_read.php?id=<?php echo $n['id']; ?>&url=<?php echo urlencode($n['link'] ?? 'admin/dashboard.php'); ?>" 
                   style="display: block; padding: 20px; border-bottom: 1px solid var(--border); text-decoration: none; transition: 0.2s; <?php echo !$n['is_read'] ? 'background: #f0f7ff; border-left: 4px solid var(--primary);' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h4 style="color: var(--text-dark); margin-bottom: 5px;"><?php echo sanitize($n['title']); ?></h4>
                            <p style="color: var(--text-med); font-size: 14px;"><?php echo sanitize($n['message']); ?></p>
                        </div>
                        <span style="font-size: 12px; color: var(--text-light);"><?php echo formatDate($n['created_at'], 'M d, Y H:i'); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

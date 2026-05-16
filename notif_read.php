<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? null;
$redirect = $_GET['url'] ?? 'admin/dashboard.php';

if ($id && isLoggedIn()) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
}

header("Location: " . BASE_URL . "/" . $redirect);
exit();
?>

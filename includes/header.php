<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button id="sidebar-toggle" title="Toggle Sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2><?php echo $page_title ?? 'Dashboard'; ?></h2>
                </div>
                <div class="topbar-right">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <!-- Notifications Dropdown -->
                        <?php 
                        $notifs = getUnreadNotifications($_SESSION['user_id']);
                        $notif_count = count($notifs);
                        ?>
                        <div class="notification-dropdown" style="position: relative;">
                            <div id="notif-btn" style="color: var(--text-med); font-size: 20px; cursor: pointer; position: relative;">
                                <i class="far fa-bell"></i>
                                <?php if ($notif_count > 0): ?>
                                    <span style="position: absolute; top: -5px; right: -5px; width: 18px; height: 18px; background: var(--danger); border-radius: 50%; border: 2px solid #fff; color: #fff; font-size: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                        <?php echo $notif_count; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div id="notif-menu" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 40px; width: 300px; background: #fff; box-shadow: var(--shadow-lg); border-radius: 12px; border: 1px solid var(--border); z-index: 1000; padding: 10px 0;">
                                <div style="padding: 10px 20px; border-bottom: 1px solid var(--border); font-weight: 700; color: var(--primary-dark);">Notifications</div>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php if ($notif_count == 0): ?>
                                        <div style="padding: 20px; text-align: center; color: var(--text-light); font-size: 13px;">No new notifications</div>
                                    <?php else: ?>
                                        <?php foreach ($notifs as $n): ?>
                                            <a href="<?php echo BASE_URL; ?>/notif_read.php?id=<?php echo $n['id']; ?>&url=<?php echo urlencode($n['link'] ?? 'admin/dashboard.php'); ?>" class="notif-item" style="display: block; padding: 12px 20px; text-decoration: none; border-bottom: 1px solid var(--bg-light); transition: 0.2s;">
                                                <div style="font-weight: 700; font-size: 13px; color: var(--text-dark);"><?php echo sanitize($n['title']); ?></div>
                                                <div style="font-size: 12px; color: var(--text-med); margin: 2px 0;"><?php echo sanitize($n['message']); ?></div>
                                                <div style="font-size: 10px; color: var(--text-light);"><?php echo formatDate($n['created_at'], 'M d, H:i'); ?></div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/admin/notifications.php" style="display: block; padding: 10px; text-align: center; font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none;">View All</a>
                            </div>
                        </div>

                        <div class="user-profile"
 style="display: flex; align-items: center; gap: 12px; padding: 5px 15px; border-radius: 50px; background: var(--bg-light); border: 1px solid var(--border);">
                            <div style="text-align: right; line-height: 1.2;">
                                <div style="font-size: 13px; font-weight: 700; color: var(--text-dark);"><?php echo sanitize($_SESSION['name'] ?? 'Admin User'); ?></div>
                                <div style="font-size: 11px; font-weight: 600; color: var(--text-light); text-transform: uppercase;"><?php echo $_SESSION['role'] ?? 'Staff'; ?></div>
                            </div>
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; color: var(--primary); box-shadow: var(--shadow);">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="content-wrapper">
                <?php displayFlash(); ?>

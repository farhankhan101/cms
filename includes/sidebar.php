<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'admin';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header" style="padding: 30px 20px;">
        <h3 style="display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 800; letter-spacing: 1px;">
            <i class="fas fa-paper-plane" style="color: var(--accent);"></i> CMS<span style="color: var(--accent);">PRO</span>
        </h3>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/couriers.php" class="<?php echo $current_page == 'couriers.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Shipments
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/courier_add.php" class="<?php echo $current_page == 'courier_add.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Book New
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/agents.php" class="<?php echo $current_page == 'agents.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-gear"></i> Agents
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/customers.php" class="<?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-group"></i> Customers
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Reports
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/whatsapp_setup.php" class="<?php echo $current_page == 'whatsapp_setup.php' ? 'active' : ''; ?>">
                <i class="fab fa-whatsapp"></i> Connect WhatsApp
            </a>
        <?php elseif ($role === 'agent'): ?>
            <a href="<?php echo BASE_URL; ?>/agent/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/agent/couriers.php" class="<?php echo $current_page == 'couriers.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Manage Shipments
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/courier_add.php" class="<?php echo $current_page == 'courier_add.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Book New Courier
            </a>
            <a href="<?php echo BASE_URL; ?>/agent/report.php" class="<?php echo $current_page == 'report.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-export"></i> Branch Report
            </a>
        <?php elseif ($role === 'user'): ?>
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/user/sent.php" class="<?php echo $current_page == 'sent.php' ? 'active' : ''; ?>">
                <i class="fas fa-paper-plane"></i> Sent Parcels
            </a>
            <a href="<?php echo BASE_URL; ?>/user/received.php" class="<?php echo $current_page == 'received.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Received Parcels
            </a>
            <a href="<?php echo BASE_URL; ?>/user/track.php" class="<?php echo $current_page == 'track.php' ? 'active' : ''; ?>">
                <i class="fas fa-location-crosshairs"></i> Track Shipments
            </a>
        <?php endif; ?>
        
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="<?php echo BASE_URL; ?>/logout.php" style="color: #ff7675;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</aside>

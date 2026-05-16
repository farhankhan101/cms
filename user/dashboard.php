<?php
$page_title = "User Dashboard";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['user']);

$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch User Info including NIC
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$user_nic = $user['nic'] ?? '';

// Get Counts
$stmt = $db->prepare("SELECT COUNT(*) FROM shipments WHERE sender_nic = ?");
$stmt->execute([$user_nic]);
$sent_count = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM shipments WHERE receiver_nic = ?");
$stmt->execute([$user_nic]);
$received_count = $stmt->fetchColumn();

// Fetch Recent Sent
$stmt = $db->prepare("SELECT s.*, tc.city_name as to_city 
                      FROM shipments s 
                      LEFT JOIN cities tc ON s.to_city_id = tc.id
                      WHERE s.sender_nic = ? 
                      ORDER BY s.booked_at DESC LIMIT 5");
$stmt->execute([$user_nic]);
$recent_sent = $stmt->fetchAll();

// Fetch Recent Received
$stmt = $db->prepare("SELECT s.*, fc.city_name as from_city 
                      FROM shipments s 
                      LEFT JOIN cities fc ON s.from_city_id = fc.id
                      WHERE s.receiver_nic = ? 
                      ORDER BY s.booked_at DESC LIMIT 5");
$stmt->execute([$user_nic]);
$recent_received = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="profile-header card" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); color: #fff; padding: 35px; border-radius: 20px; border: none; margin-bottom: 30px; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
    <div style="position: relative; z-index: 10; display: flex; align-items: center; gap: 25px;">
        <div style="width: 85px; height: 85px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 35px; border: 1.5px solid rgba(255,255,255,0.2);">
            <i class="fas fa-user-shield"></i>
        </div>
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 5px; letter-spacing: -0.5px;">Welcome, <?php echo explode(' ', $user['name'])[0]; ?>!</h1>
            <p style="opacity: 0.8; font-size: 14px;"><i class="fas fa-id-card"></i> NIC: <?php echo sanitize($user_nic); ?></p>
        </div>
    </div>
</div>

<!-- Stats Summary -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="display: flex; align-items: center; gap: 20px; border-bottom: 4px solid var(--primary);">
        <div style="width: 50px; height: 50px; background: #eff6ff; color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div>
            <p style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700;">Sent Parcels</p>
            <h2 style="margin: 0;"><?php echo $sent_count; ?></h2>
        </div>
    </div>
    <div class="card" style="display: flex; align-items: center; gap: 20px; border-bottom: 4px solid var(--success);">
        <div style="width: 50px; height: 50px; background: #ecfdf5; color: var(--success); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="fas fa-box"></i>
        </div>
        <div>
            <p style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700;">Received Parcels</p>
            <h2 style="margin: 0;"><?php echo $received_count; ?></h2>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px;">
    <!-- Recent Sent -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="font-weight: 800;">Recent Sent</h4>
            <a href="sent.php" style="font-size: 12px; font-weight: 700; color: var(--primary); text-decoration: none;">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php if (empty($recent_sent)): ?>
            <p style="text-align: center; color: #94a3b8; padding: 20px;">No sent parcels yet.</p>
        <?php else: ?>
            <?php foreach ($recent_sent as $p): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <p style="font-weight: 700; font-size: 14px; margin-bottom: 2px;">#<?php echo sanitize($p['tracking_no']); ?></p>
                        <p style="font-size: 11px; color: #64748b;">To: <?php echo sanitize($p['to_city']); ?></p>
                    </div>
                    <span class="badge badge-<?php echo $p['status']; ?>" style="font-size: 9px;"><?php echo str_replace('_', ' ', $p['status']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Recent Received -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="font-weight: 800;">Recent Received</h4>
            <a href="received.php" style="font-size: 12px; font-weight: 700; color: var(--primary); text-decoration: none;">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php if (empty($recent_received)): ?>
            <p style="text-align: center; color: #94a3b8; padding: 20px;">No received parcels yet.</p>
        <?php else: ?>
            <?php foreach ($recent_received as $p): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <p style="font-weight: 700; font-size: 14px; margin-bottom: 2px;">#<?php echo sanitize($p['tracking_no']); ?></p>
                        <p style="font-size: 11px; color: #64748b;">From: <?php echo sanitize($p['from_city']); ?></p>
                    </div>
                    <span class="badge badge-<?php echo $p['status']; ?>" style="font-size: 9px;"><?php echo str_replace('_', ' ', $p['status']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

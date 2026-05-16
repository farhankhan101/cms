<?php
$page_title = "Update Shipment";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - admin and agent allowed
protectPage(['admin', 'agent']);

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: couriers.php");
    exit();
}

// Fetch Shipment
$stmt = $db->prepare("SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
                      FROM shipments s 
                      LEFT JOIN cities fc ON s.from_city_id = fc.id
                      LEFT JOIN cities tc ON s.to_city_id = tc.id
                      WHERE s.id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    header("Location: couriers.php");
    exit();
}

// Fetch Status History
$stmt = $db->prepare("SELECT sh.*, u.name as updated_by_name 
                      FROM shipment_status sh 
                      JOIN users u ON sh.updated_by = u.id 
                      WHERE sh.shipment_id = ? 
                      ORDER BY sh.updated_at DESC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $status = $_POST['status'];
        $note = trim($_POST['note']);

        try {
            $db->beginTransaction();

            // 1. Update Shipment Status
            $stmt = $db->prepare("UPDATE shipments SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            // 2. Insert Status History
            $stmt = $db->prepare("INSERT INTO shipment_status (shipment_id, status, note, updated_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $status, $note, $_SESSION['user_id']]);

            // --- NOTIFICATION LOGIC ---
            $tracking_no = $s['tracking_no'];
            $msg = "Shipment #$tracking_no status updated to " . str_replace('_', ' ', $status) . ". Note: $note";

            // A. Notify Admin (if updated by agent)
            if ($_SESSION['role'] === 'agent') {
                $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
                foreach ($admins as $admin) {
                    addNotification($admin['id'], 'Status Update', "Agent {$_SESSION['name']} updated #$tracking_no", "admin/courier_edit.php?id=$id");
                }
            }

            // B. Notify Origin Agent (if current user is not the origin agent)
            if ($s['agent_id']) {
                $stmt = $db->prepare("SELECT user_id FROM agents WHERE id = ?");
                $stmt->execute([$s['agent_id']]);
                $origin_user_id = $stmt->fetchColumn();
                if ($origin_user_id && $origin_user_id != $_SESSION['user_id']) {
                    addNotification($origin_user_id, 'Status Update', $msg, "admin/courier_edit.php?id=$id");
                }
            }

            // C. Notify Destination Agent(s)
            $dest_agents = $db->prepare("SELECT user_id FROM agents WHERE city_id = ?");
            $dest_agents->execute([$s['to_city_id']]);
            foreach ($dest_agents->fetchAll() as $agent) {
                if ($agent['user_id'] != $_SESSION['user_id']) {
                    addNotification($agent['user_id'], 'Incoming Update', $msg, "admin/courier_edit.php?id=$id");
                }
            }

            // D. Notify Customer (if linked to a user_id)
            if ($s['sender_id']) {
                addNotification($s['sender_id'], 'Shipment Update', "Your shipment #$tracking_no is now $status", "user/track.php?no=$tracking_no");
            }
            // ---------------------------

            $db->commit();
            setFlash('success', "Status updated successfully.");
            header("Location: courier_edit.php?id=$id");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Update Error: " . $e->getMessage();
        }
    }

    // WhatsApp Notification Logic (Using wa.me)
    if (isset($_POST['send_whatsapp'])) {
        $type = $_POST['whatsapp_type']; 
        $phone = preg_replace('/[^0-9]/', '', $s['receiver_phone']);
        // Ensure phone has country code (defaulting to 92 for Pakistan if missing)
        if (strlen($phone) == 11 && $phone[0] == '0') {
            $phone = '92' . substr($phone, 1);
        }

        $msg = "";
        if ($type === 'from_to') {
            $msg = "Dear Customer, your shipment #{$s['tracking_no']} from {$s['from_city']} to {$s['to_city']} has been booked. Track here: " . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}" . BASE_URL . "/user/track.php?no={$s['tracking_no']}";
        } else {
            $msg = "Dear Customer, your shipment #{$s['tracking_no']} has been delivered successfully. Thank you for choosing " . APP_NAME;
        }

        $wa_link = "https://wa.me/{$phone}?text=" . urlencode($msg);
        
        // Log the notification
        try {
            $stmt = $db->prepare("INSERT INTO sms_logs (shipment_id, phone, message, status) VALUES (?, ?, ?, 'whatsapp_sent')");
            $stmt->execute([$id, $s['receiver_phone'], $msg]);
            
            // Redirect to WhatsApp
            echo "<script>window.open('$wa_link', '_blank'); window.location.href='courier_edit.php?id=$id';</script>";
            exit();
        } catch (Exception $e) {
            $error = "WhatsApp Error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
    <!-- Main Edit/Details Card -->
    <div>
        <div class="card">
            <div class="card-title" style="display: flex; justify-content: space-between;">
                <span>Shipment Details (#<?php echo sanitize($s['tracking_no']); ?>)</span>
                <?php if (getUserRole() === 'admin'): ?>
                    <form action="couriers.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this shipment?');" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                        <button type="submit" name="delete_shipment" class="btn btn-danger" style="padding: 5px 10px; font-size: 11px;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div>
                    <h5 style="color: var(--text-med); margin-bottom: 5px;">Sender Info</h5>
                    <p style="font-weight: 600;"><?php echo sanitize($s['sender_name']); ?></p>
                    <p style="font-size: 13px; color: var(--text-dark);">NIC: <?php echo sanitize($s['sender_nic']); ?></p>
                    <p style="font-size: 14px; color: var(--text-med);"><?php echo sanitize($s['from_city']); ?></p>
                </div>
                <div>
                    <h5 style="color: var(--text-med); margin-bottom: 5px;">Receiver Info</h5>
                    <p style="font-weight: 600;"><?php echo sanitize($s['receiver_name']); ?></p>
                    <p style="font-size: 13px; color: var(--text-dark);">NIC: <?php echo sanitize($s['receiver_nic']); ?></p>
                    <p style="font-size: 14px;"><?php echo sanitize($s['receiver_phone']); ?></p>
                    <p style="font-size: 14px; color: var(--text-med);"><?php echo sanitize($s['receiver_address']); ?></p>
                    <p style="font-size: 14px; color: var(--text-med);"><?php echo sanitize($s['to_city']); ?></p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; border-top: 1px solid #eee; padding-top: 20px;">
                <div>
                    <h5 style="color: var(--text-med); margin-bottom: 5px;">Weight</h5>
                    <p><?php echo $s['weight']; ?> kg</p>
                </div>
                <div>
                    <h5 style="color: var(--text-med); margin-bottom: 5px;">Total Amount</h5>
                    <p style="font-weight: 700; color: var(--primary);">$<?php echo number_format($s['amount'], 2); ?></p>
                </div>
                <div>
                    <h5 style="color: var(--text-med); margin-bottom: 5px;">Booked Date</h5>
                    <p><?php echo formatDate($s['booked_at']); ?></p>
                </div>
            </div>
        </div>

        <!-- Status Update Form -->
        <div class="card">
            <div class="card-title">Update Status</div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="courier_edit.php?id=<?php echo $id; ?>" method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">New Status</label>
                    <select name="status" class="form-control" style="width: 100%; padding: 10px; border: 1.5px solid #ddd; border-radius: 8px;">
                        <option value="booked" <?php echo $s['status'] == 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="in_transit" <?php echo $s['status'] == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                        <option value="out_for_delivery" <?php echo $s['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $s['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $s['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Note / Update Comment</label>
                    <textarea name="note" class="form-control" rows="3" placeholder="e.g. Package arrived at Karachi hub" style="width: 100%; padding: 10px; border: 1.5px solid #ddd; border-radius: 8px;"></textarea>
                </div>
                <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">Update Shipment Status</button>
            </form>
        </div>
    </div>

        <!-- Status History Timeline -->
        <div class="card">
            <div class="card-title">Status History</div>
            <div class="timeline" style="position: relative; padding-left: 20px; border-left: 2px solid var(--bg-light);">
                <?php foreach ($history as $h): ?>
                    <div style="margin-bottom: 25px; position: relative;">
                        <div style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--primary); border: 2px solid #fff;"></div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <span class="badge badge-<?php echo $h['status']; ?>" style="font-size: 10px;">
                                <?php echo str_replace('_', ' ', $h['status']); ?>
                            </span>
                            <span style="font-size: 11px; color: var(--text-med);"><?php echo formatDate($h['updated_at'], 'M d, H:i'); ?></span>
                        </div>
                        <p style="font-size: 13px; margin-top: 5px; font-weight: 500;"><?php echo sanitize($h['note']); ?></p>
                        <p style="font-size: 11px; color: var(--text-med);">By: <?php echo sanitize($h['updated_by_name']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- WhatsApp Notifications (New Feature) -->
        <div class="card">
            <div class="card-title"><i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp Alerts</div>
            <form action="courier_edit.php?id=<?php echo $id; ?>" method="POST" target="_blank">
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="submit" name="send_whatsapp" value="1" class="btn btn-secondary" style="text-align: left; font-size: 13px; border-color: #25D366; color: #128C7E;">
                        <input type="hidden" name="whatsapp_type" value="from_to">
                        <i class="fab fa-whatsapp"></i> Send Booking Alert
                    </button>
                    <button type="submit" name="send_whatsapp" value="1" class="btn btn-secondary" style="text-align: left; font-size: 13px; border-color: #25D366; color: #128C7E;">
                        <input type="hidden" name="whatsapp_type" value="delivery">
                        <i class="fab fa-whatsapp"></i> Send Delivery Alert
                    </button>
                </div>
            </form>
            <div style="margin-top: 15px; font-size: 11px; color: var(--text-med);">
                <i class="fas fa-info-circle"></i> Opens WhatsApp Web/App with pre-filled message for: <?php echo sanitize($s['receiver_phone']); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

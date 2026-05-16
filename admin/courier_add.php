<?php
$page_title = "Book New Shipment";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - admin and agent allowed
protectPage(['admin', 'agent']);

$db = getDB();
$error = '';
$success = '';

// Fetch Cities and Courier Types for dropdowns
$cities = $db->query("SELECT * FROM cities ORDER BY city_name ASC")->fetchAll();
$types = $db->query("SELECT * FROM courier_types ORDER BY type_name ASC")->fetchAll();

// Get Agent's City if applicable
$agent_city_id = null;
if ($_SESSION['role'] === 'agent') {
    $stmt = $db->prepare("SELECT city_id FROM agents WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $agent_city_id = $stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_name = trim($_POST['sender_name']);
    $sender_nic = trim($_POST['sender_nic']);
    $receiver_name = trim($_POST['receiver_name']);
    $receiver_nic = trim($_POST['receiver_nic']);
    $receiver_phone = trim($_POST['receiver_phone']);
    $receiver_address = trim($_POST['receiver_address']);
    $from_city_id = $_POST['from_city_id'];
    
    // Safety: Forces agent to use their own city as origin
    if ($_SESSION['role'] === 'agent' && $agent_city_id) {
        $from_city_id = $agent_city_id;
    }
    
    $to_city_id = $_POST['to_city_id'];
    $courier_type_id = $_POST['courier_type_id'];
    $weight = floatval($_POST['weight']);
    $expected_delivery = $_POST['expected_delivery'];

    if (empty($sender_name) || empty($sender_nic) || empty($receiver_name) || empty($receiver_nic) || empty($receiver_phone) || empty($from_city_id) || empty($to_city_id)) {
        $error = "Please fill all required fields (including NICs).";
    } else {
        try {
            $db->beginTransaction();

            // Calculate Amount
            $stmt = $db->prepare("SELECT base_rate FROM courier_types WHERE id = ?");
            $stmt->execute([$courier_type_id]);
            $base_rate = $stmt->fetchColumn();
            $amount = $base_rate * $weight;

            // Generate Tracking No
            $tracking_no = generateTrackingNo();

            // Insert Shipment
            $sql = "INSERT INTO shipments (tracking_no, sender_name, sender_nic, receiver_name, receiver_nic, receiver_phone, receiver_address, from_city_id, to_city_id, courier_type_id, weight, amount, expected_delivery, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'booked')";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $tracking_no, $sender_name, $sender_nic, $receiver_name, $receiver_nic, $receiver_phone, $receiver_address, 
                $from_city_id, $to_city_id, $courier_type_id, $weight, $amount, $expected_delivery
            ]);

            $shipment_id = $db->lastInsertId();

            // --- NOTIFICATION LOGIC ---
            // 1. Notify Admin (if agent booked it)
            if ($_SESSION['role'] === 'agent') {
                $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
                foreach ($admins as $admin) {
                    addNotification($admin['id'], 'New Shipment Booked', "Agent {$_SESSION['name']} booked shipment #$tracking_no", "admin/courier_edit.php?id=$shipment_id");
                }
            }

            // 2. Notify Destination Agent(s)
            $dest_agents = $db->prepare("SELECT user_id FROM agents WHERE city_id = ?");
            $dest_agents->execute([$to_city_id]);
            foreach ($dest_agents->fetchAll() as $agent) {
                addNotification($agent['user_id'], 'Incoming Shipment', "New shipment #$tracking_no heading to your city.", "admin/courier_edit.php?id=$shipment_id");
            }

            // 3. Notify Registered Sender (by NIC)
            $stmt_u = $db->prepare("SELECT id FROM users WHERE nic = ? AND role = 'user'");
            $stmt_u->execute([$sender_nic]);
            $sender_user_id = $stmt_u->fetchColumn();
            if ($sender_user_id) {
                addNotification($sender_user_id, 'Shipment Booked', "Your parcel #$tracking_no has been booked successfully.", "user/track.php?no=$tracking_no");
            }

            // 4. Notify Registered Receiver (by NIC)
            $stmt_u->execute([$receiver_nic]);
            $receiver_user_id = $stmt_u->fetchColumn();
            if ($receiver_user_id) {
                addNotification($receiver_user_id, 'Incoming Shipment', "A parcel #$tracking_no is being sent to you by $sender_name.", "user/track.php?no=$tracking_no");
            }
            // 5. Send WhatsApp Alert to Receiver
            $stmt_c = $db->prepare("SELECT city_name FROM cities WHERE id = ?");
            $stmt_c->execute([$from_city_id]);
            $from_city_name = $stmt_c->fetchColumn();

            $full_url = "http://localhost" . BASE_URL; // Construct full URL for clickable links
            
            $wa_message = "📦 *New Parcel Booked!*\n\n" .
                          "Hello *$receiver_name*,\n" .
                          "A parcel has been booked for you from *$sender_name*.\n\n" .
                          "🔢 *Tracking ID:* `$tracking_no` \n" .
                          "⚖️ *Weight:* $weight kg\n" .
                          "📍 *From:* $from_city_name\n" .
                          "🚚 *Est. Delivery:* " . date('d M, Y', strtotime($expected_delivery)) . "\n\n" .
                          "--- \n" .
                          "📱 *Live Tracking & Dashboard:*\n" .
                          "To track your parcel live and manage your shipments, please register or login to your account using your CNIC (*$receiver_nic*).\n\n" .
                          "🔗 *Register:* " . $full_url . "/user/register.php\n" .
                          "🔗 *Login:* " . $full_url . "/login.php\n\n" .
                          "Thank you for choosing *" . APP_NAME . "*!";
            
            sendWhatsAppAlert($receiver_phone, $wa_message);
            // ---------------------------

            // Insert Initial Status
            $stmt = $db->prepare("INSERT INTO shipment_status (shipment_id, status, note, updated_by) VALUES (?, 'booked', 'Shipment booked successfully', ?)");
            $stmt->execute([$shipment_id, $_SESSION['user_id']]);

            $db->commit();
            setFlash('success', "Shipment booked successfully! Tracking No: $tracking_no");
            header("Location: couriers.php");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-title">Booking Details</div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="courier_add.php" method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Sender & Receiver Info -->
            <div>
                <h4 style="margin-bottom: 15px; color: var(--primary);">Sender & Receiver</h4>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Sender Name *</label>
                    <input type="text" name="sender_name" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Sender CNIC *</label>
                    <input type="text" name="sender_nic" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" placeholder="42101-XXXXXXX-X" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Receiver Name *</label>
                    <input type="text" name="receiver_name" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Receiver CNIC *</label>
                    <input type="text" name="receiver_nic" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" placeholder="42101-XXXXXXX-X" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Receiver Phone *</label>
                    <input type="text" name="receiver_phone" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" required>
                </div>
                <div class="form-group">
                    <label>Receiver Address</label>
                    <textarea name="receiver_address" class="form-control" rows="3" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;"></textarea>
                </div>
            </div>

            <!-- Shipment Details -->
            <div>
                <h4 style="margin-bottom: 15px; color: var(--primary);">Package Details</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label>From City *</label>
                        <select name="from_city_id" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" required>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo $city['id']; ?>"><?php echo sanitize($city['city_name']); ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($cities as $city): ?>
                                    <?php if ($city['id'] == $agent_city_id): ?>
                                        <option value="<?php echo $city['id']; ?>" selected><?php echo sanitize($city['city_name']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>To City *</label>
                        <select name="to_city_id" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" required>
                            <option value="">Select City</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>"><?php echo sanitize($city['city_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Courier Type *</label>
                        <select name="courier_type_id" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" required>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo sanitize($type['type_name']); ?> (Rate: <?php echo $type['base_rate']; ?>/kg)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Weight (kg) *</label>
                        <input type="number" step="0.01" name="weight" class="form-control" value="1.00" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Expected Delivery Date</label>
                    <input type="date" name="expected_delivery" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;">
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
            <button type="reset" class="btn btn-secondary" style="margin-right: 10px;">Reset Form</button>
            <button type="submit" class="btn btn-primary" style="padding: 10px 30px;">Confirm Booking</button>
        </div>
    </form>
    <script>
        // CNIC Input Masking Function
        function applyNICMask(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                let formattedValue = '';
                if (value.length > 0) {
                    formattedValue = value.substring(0, 5);
                    if (value.length > 5) formattedValue += '-' + value.substring(5, 12);
                    if (value.length > 12) formattedValue += '-' + value.substring(12, 13);
                }
                e.target.value = formattedValue;
            });
        }
        applyNICMask('sender_nic');
        applyNICMask('receiver_nic');
    </script>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

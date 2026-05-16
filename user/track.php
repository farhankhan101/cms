<?php
$page_title = "Track Shipments";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['user']);

$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch User NIC
$stmt = $db->prepare("SELECT nic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_nic = $stmt->fetchColumn();

// Fetch all parcels related to this NIC (Sent or Received)
$parcels = [];
if ($user_nic) {
    $stmt = $db->prepare("SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
                          FROM shipments s 
                          LEFT JOIN cities fc ON s.from_city_id = fc.id
                          LEFT JOIN cities tc ON s.to_city_id = tc.id
                          WHERE s.sender_nic = ? OR s.receiver_nic = ?
                          ORDER BY s.booked_at DESC");
    $stmt->execute([$user_nic, $user_nic]);
    $parcels = $stmt->fetchAll();
}

// Define the standard journey steps
$all_steps = [
    'booked' => 'Shipment Booked',
    'in_transit' => 'In Transit',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered'
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom: 30px; border-bottom: 4px solid var(--primary);">
    <h2 style="font-weight: 800; color: var(--primary-dark); margin-bottom: 10px;">Shipment Journey Tracker</h2>
    <p style="color: var(--text-med); font-size: 14px;">Track completed and upcoming milestones for your parcels.</p>
</div>

<?php if (empty($parcels)): ?>
    <div class="card" style="text-align: center; padding: 60px;">
        <i class="fas fa-route" style="font-size: 50px; color: #eee; margin-bottom: 20px;"></i>
        <h3>No Shipments Found</h3>
        <p>You don't have any active or past shipments linked to your NIC.</p>
    </div>
<?php else: ?>
    <?php foreach ($parcels as $p): ?>
        <?php
        // Fetch history for this specific parcel
        $stmt = $db->prepare("SELECT * FROM shipment_status WHERE shipment_id = ? ORDER BY updated_at ASC");
        $stmt->execute([$p['id']]);
        $history_raw = $stmt->fetchAll();
        
        // Map history by status for easy lookup
        $history_map = [];
        foreach ($history_raw as $h) {
            $history_map[$h['status']] = $h;
        }

        $current_status = $p['status'];
        $is_cancelled = ($current_status === 'cancelled');
        ?>
        <div class="accordion-item card" style="padding: 0; margin-bottom: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <div class="accordion-header" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #fff; transition: 0.2s;" onclick="toggleAccordion(<?php echo $p['id']; ?>)">
                <div style="display: flex; gap: 20px; align-items: center;">
                    <div style="width: 45px; height: 45px; background: <?php echo $is_cancelled ? '#fee2e2' : '#eff6ff'; ?>; color: <?php echo $is_cancelled ? '#ef4444' : 'var(--primary)'; ?>; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fas <?php echo $is_cancelled ? 'fa-times-circle' : 'fa-truck-fast'; ?>"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-weight: 800; color: var(--primary-dark);">#<?php echo sanitize($p['tracking_no']); ?></h4>
                        <p style="font-size: 12px; color: var(--text-med); margin-top: 2px;">
                            <?php echo sanitize($p['from_city']); ?> &rarr; <?php echo sanitize($p['to_city']); ?>
                        </p>
                    </div>
                </div>
                <div style="text-align: right; display: flex; align-items: center; gap: 15px;">
                    <span class="badge badge-<?php echo $p['status']; ?>" style="text-transform: uppercase; font-size: 10px;"><?php echo str_replace('_', ' ', $p['status']); ?></span>
                    <i class="fas fa-chevron-down accordion-icon" id="icon-<?php echo $p['id']; ?>" style="color: var(--text-light); transition: 0.3s;"></i>
                </div>
            </div>

            <div class="accordion-content" id="content-<?php echo $p['id']; ?>" style="max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; background: #fff;">
                <div style="padding: 30px; border-top: 1px solid #f1f5f9;">
                    
                    <!-- Progress Bar View -->
                    <div class="journey-steps" style="display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; padding: 0 10px;">
                        <?php 
                        $reached_current = false;
                        foreach ($all_steps as $status_key => $status_label): 
                            $is_done = isset($history_map[$status_key]);
                            $is_active = ($current_status === $status_key);
                            $reached_current = $reached_current || $is_active;
                        ?>
                            <div style="flex: 1; text-align: center; position: relative; z-index: 5;">
                                <div class="step-dot <?php echo $is_done ? 'done' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>" 
                                     style="width: 30px; height: 30px; border-radius: 50%; background: #fff; border: 3px solid <?php echo $is_done ? 'var(--primary)' : '#e2e8f0'; ?>; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; transition: 0.3s;">
                                    <?php if ($is_done): ?>
                                        <i class="fas fa-check" style="font-size: 12px; color: var(--primary);"></i>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size: 11px; font-weight: 700; color: <?php echo $is_done ? 'var(--primary-dark)' : '#94a3b8'; ?>; text-transform: uppercase;"><?php echo $status_label; ?></p>
                                <?php if (isset($history_map[$status_key])): ?>
                                    <p style="font-size: 9px; color: #94a3b8; margin-top: 2px;"><?php echo formatDate($history_map[$status_key]['updated_at'], 'M d'); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Connecting Line -->
                        <div style="position: absolute; top: 13px; left: 10%; right: 10%; height: 3px; background: #e2e8f0; z-index: 1;">
                            <div style="width: <?php 
                                $keys = array_keys($all_steps);
                                $idx = array_search($current_status, $keys);
                                echo ($idx !== false) ? ($idx / (count($keys)-1)) * 100 : 0;
                            ?>%; height: 100%; background: var(--primary); transition: 1s;"></div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                        <!-- Parcel Info -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #f1f5f9;">
                            <h5 style="margin-bottom: 15px; font-weight: 800; font-size: 13px; text-transform: uppercase; color: #64748b;">Package Info</h5>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div>
                                    <p style="font-size: 11px; color: #94a3b8;">Weight</p>
                                    <p style="font-weight: 600; font-size: 14px;"><?php echo $p['weight']; ?> kg</p>
                                </div>
                                <div>
                                    <p style="font-size: 11px; color: #94a3b8;">Estimated Delivery</p>
                                    <p style="font-weight: 600; font-size: 14px; color: #10b981;"><?php echo formatDate($p['expected_delivery']); ?></p>
                                </div>
                                <div>
                                    <p style="font-size: 11px; color: #94a3b8;">Your Role</p>
                                    <p style="font-weight: 600; font-size: 14px;"><?php echo ($p['sender_nic'] == $user_nic) ? 'Sender' : 'Receiver'; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Full History Timeline -->
                        <div>
                            <h5 style="margin-bottom: 15px; font-weight: 800; font-size: 13px; text-transform: uppercase; color: #64748b;">Activity Log</h5>
                            <div style="position: relative; padding-left: 20px; border-left: 2px solid #f1f5f9; margin-left: 10px;">
                                <?php 
                                // Show actual history in reverse for activity log
                                $history_rev = array_reverse($history_raw);
                                foreach ($history_rev as $h): ?>
                                    <div style="margin-bottom: 20px; position: relative;">
                                        <div style="position: absolute; left: -31px; top: 0; width: 20px; height: 20px; background: #fff; border: 4px solid var(--primary); border-radius: 50%; z-index: 1;"></div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <div>
                                                <p style="font-weight: 700; color: var(--primary-dark); font-size: 13px; margin-bottom: 3px;"><?php echo str_replace('_', ' ', $h['status']); ?></p>
                                                <p style="font-size: 12px; color: #64748b;"><?php echo sanitize($h['note']); ?></p>
                                            </div>
                                            <span style="font-size: 10px; color: #94a3b8; font-weight: 600;"><?php echo formatDate($h['updated_at'], 'M d, H:i'); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
    .step-dot.active { border-color: var(--primary) !important; box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.1); }
    .accordion-header:hover { background: #f8fafc !important; }
    .accordion-item.active .accordion-icon { transform: rotate(180deg); }
    .accordion-item.active .accordion-header { border-bottom: 1px solid #f1f5f9; }
</style>

<script>
    function toggleAccordion(id) {
        const content = document.getElementById('content-' + id);
        const icon = document.getElementById('icon-' + id);
        const item = content.parentElement;

        if (content.style.maxHeight === '0px' || content.style.maxHeight === '') {
            content.style.maxHeight = content.scrollHeight + 'px';
            item.classList.add('active');
        } else {
            content.style.maxHeight = '0px';
            item.classList.remove('active');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

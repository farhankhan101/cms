<?php
$page_title = "Received Parcels";
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

// Fetch Received Parcels (matched by NIC)
$received_parcels = [];
if ($user_nic) {
    $stmt = $db->prepare("SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
                          FROM shipments s 
                          LEFT JOIN cities fc ON s.from_city_id = fc.id
                          LEFT JOIN cities tc ON s.to_city_id = tc.id
                          WHERE s.receiver_nic = ? 
                          ORDER BY s.booked_at DESC");
    $stmt->execute([$user_nic]);
    $received_parcels = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom: 30px; border-top: 4px solid var(--success);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="font-weight: 800; color: var(--primary-dark);">Incoming Shipments</h2>
            <p style="color: var(--text-med); font-size: 14px;">Showing all parcels being sent to NIC: <strong><?php echo sanitize($user_nic); ?></strong></p>
        </div>
        <div style="background: var(--bg-light); padding: 10px 20px; border-radius: 12px; font-weight: 700;">
            Total: <?php echo count($received_parcels); ?>
        </div>
    </div>

    <?php if (empty($received_parcels)): ?>
        <div style="text-align: center; padding: 60px;">
            <i class="fas fa-box" style="font-size: 50px; color: #eee; margin-bottom: 20px;"></i>
            <h3>No Shipments Found</h3>
            <p>There are no parcels heading your way currently.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tracking No</th>
                        <th>Sender Name</th>
                        <th>Origin</th>
                        <th>Status</th>
                        <th>Booked At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($received_parcels as $p): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">#<?php echo sanitize($p['tracking_no']); ?></td>
                            <td><?php echo sanitize($p['sender_name']); ?></td>
                            <td><?php echo sanitize($p['from_city']); ?></td>
                            <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo str_replace('_', ' ', $p['status']); ?></span></td>
                            <td style="font-size: 13px;"><?php echo formatDate($p['booked_at'], 'M d, Y'); ?></td>
                            <td>
                                <a href="track.php?no=<?php echo $p['tracking_no']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-location-arrow"></i> Track</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

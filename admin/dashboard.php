<?php
$page_title = "Admin Dashboard";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - only admin allowed
protectPage(['admin']);

$db = getDB();
$role = getUserRole();

// Handle Delete Shipment (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_shipment']) && $role === 'admin') {
    $delete_id = $_POST['delete_id'];
    try {
        // Double check status is booked
        $stmt = $db->prepare("SELECT status FROM shipments WHERE id = ?");
        $stmt->execute([$delete_id]);
        $status = $stmt->fetchColumn();
        
        if ($status !== 'booked') {
            setFlash('danger', "Only booked shipments can be deleted.");
        } else {
            $stmt = $db->prepare("DELETE FROM shipments WHERE id = ?");
            $stmt->execute([$delete_id]);
            setFlash('success', "Shipment deleted successfully.");
        }
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        setFlash('danger', "Error deleting shipment: " . $e->getMessage());
    }
}

// 1. Fetch KPI Stats
$stats = [
    'total' => 0,
    'in_transit' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

try {
    // Total Shipments
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM shipments");
    $stats['total'] = $stmt->fetch()['cnt'];

    // Grouped by status
    $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM shipments GROUP BY status");
    while ($row = $stmt->fetch()) {
        if ($row['status'] == 'in_transit') $stats['in_transit'] = $row['cnt'];
        if ($row['status'] == 'delivered') $stats['delivered'] = $row['cnt'];
        if ($row['status'] == 'cancelled') $stats['cancelled'] = $row['cnt'];
    }
} catch (PDOException $e) {
    // If table doesn't exist, we'll keep stats at 0 for demo purposes
}

// 2. Fetch Recent Shipments
$recent_shipments = [];
try {
    $sql = "SELECT s.*, 
                   fc.city_name as from_city, 
                   tc.city_name as to_city 
            FROM shipments s
            LEFT JOIN cities fc ON s.from_city_id = fc.id
            LEFT JOIN cities tc ON s.to_city_id = tc.id
            ORDER BY s.booked_at DESC 
            LIMIT 10";
    $recent_shipments = $db->query($sql)->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- KPI Cards -->
<div class="kpi-grid">
    <a href="couriers.php" class="kpi-card">
        <div class="kpi-icon"><i class="fas fa-boxes-stacked"></i></div>
        <div class="kpi-data">
            <span class="number"><?php echo number_format($stats['total']); ?></span>
            <span class="label">Total Shipments</span>
        </div>
    </a>
    
    <a href="couriers.php?status=in_transit" class="kpi-card" style="border-left-color: var(--warning);">
        <div class="kpi-icon" style="color: var(--warning);"><i class="fas fa-truck-fast"></i></div>
        <div class="kpi-data">
            <span class="number"><?php echo number_format($stats['in_transit']); ?></span>
            <span class="label">In Transit</span>
        </div>
    </a>
    
    <a href="couriers.php?status=delivered" class="kpi-card" style="border-left-color: var(--success);">
        <div class="kpi-icon" style="color: var(--success);"><i class="fas fa-clipboard-check"></i></div>
        <div class="kpi-data">
            <span class="number"><?php echo number_format($stats['delivered']); ?></span>
            <span class="label">Delivered</span>
        </div>
    </a>
    
    <a href="couriers.php?status=cancelled" class="kpi-card" style="border-left-color: var(--danger);">
        <div class="kpi-icon" style="color: var(--danger);"><i class="fas fa-ban"></i></div>
        <div class="kpi-data">
            <span class="number"><?php echo number_format($stats['cancelled']); ?></span>
            <span class="label">Cancelled</span>
        </div>
    </a>
</div>

<!-- Recent Shipments Table -->
<div class="card">
    <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
        <span>Recent Shipments</span>
        <a href="couriers.php" class="btn btn-secondary" style="padding: 5px 12px; font-size: 12px;">View All</a>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tracking No</th>
                    <th>Sender</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>Booked At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_shipments)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-med);">
                            <i class="fas fa-box-open" style="font-size: 40px; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                            No shipments found. <a href="courier_add.php">Book your first courier!</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_shipments as $shipment): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary);">
                                #<?php echo sanitize($shipment['tracking_no']); ?>
                            </td>
                            <td><?php echo sanitize($shipment['sender_name'] ?? 'N/A'); ?></td>
                            <td><?php echo sanitize($shipment['from_city'] ?? 'Unknown'); ?></td>
                            <td><?php echo sanitize($shipment['to_city'] ?? 'Unknown'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo sanitize($shipment['status']); ?>">
                                    <?php echo str_replace('_', ' ', sanitize($shipment['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($shipment['booked_at']); ?></td>
                            <td>
                                <a href="courier_edit.php?id=<?php echo $shipment['id']; ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="courier_view.php?id=<?php echo $shipment['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this shipment?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $shipment['id']; ?>">
                                    <input type="hidden" name="delete_shipment" value="1">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" <?php echo $shipment['status'] !== 'booked' ? 'disabled title="Cannot delete after booking stage"' : ''; ?>>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

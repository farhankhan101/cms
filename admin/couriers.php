<?php
$page_title = "Manage Shipments";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - admin and agent allowed
protectPage(['admin', 'agent']);

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
        header("Location: couriers.php");
        exit();
    } catch (Exception $e) {
        setFlash('danger', "Error deleting shipment: " . $e->getMessage());
    }
}

// Filtering
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
        FROM shipments s
        LEFT JOIN cities fc ON s.from_city_id = fc.id
        LEFT JOIN cities tc ON s.to_city_id = tc.id
        WHERE 1=1";

$params = [];

if ($status_filter) {
    $sql .= " AND s.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (s.tracking_no LIKE ? OR s.sender_name LIKE ? OR s.receiver_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY s.booked_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$shipments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom: 20px;">
    <form action="couriers.php" method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex: 1;">
            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Search Tracking/Name</label>
            <input type="text" name="search" class="form-control" value="<?php echo sanitize($search); ?>" placeholder="Enter tracking no or name..." style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <div style="width: 200px;">
            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Status Filter</label>
            <select name="status" class="form-control" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
                <option value="">All Status</option>
                <option value="booked" <?php echo $status_filter == 'booked' ? 'selected' : ''; ?>>Booked</option>
                <option value="in_transit" <?php echo $status_filter == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                <option value="out_for_delivery" <?php echo $status_filter == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="couriers.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tracking No</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Route</th>
                    <th>Weight</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px;">No shipments found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($shipments as $s): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary);">#<?php echo sanitize($s['tracking_no']); ?></td>
                            <td><?php echo sanitize($s['sender_name']); ?></td>
                            <td><?php echo sanitize($s['receiver_name']); ?></td>
                            <td>
                                <div style="font-size: 12px; color: var(--text-med);">
                                    <?php echo sanitize($s['from_city']); ?> <i class="fas fa-arrow-right" style="font-size: 10px;"></i> <?php echo sanitize($s['to_city']); ?>
                                </div>
                            </td>
                            <td><?php echo $s['weight']; ?> kg</td>
                            <td style="font-weight: 600;">$<?php echo number_format($s['amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $s['status']; ?>">
                                    <?php echo str_replace('_', ' ', $s['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="courier_edit.php?id=<?php echo $s['id']; ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;" title="Edit/Update Status">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="courier_view.php?id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;" title="View Full Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this shipment?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $s['id']; ?>">
                                    <input type="hidden" name="delete_shipment" value="1">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" <?php echo $s['status'] !== 'booked' ? 'disabled title="Cannot delete after booking stage"' : ''; ?>>
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

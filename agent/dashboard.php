<?php
$page_title = "Agent Dashboard";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - only agent allowed
protectPage(['agent']);

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get Agent Info (to find city)
$stmt = $db->prepare("SELECT city_id FROM agents WHERE user_id = ?");
$stmt->execute([$user_id]);
$agent_city_id = $stmt->fetchColumn();
$role = getUserRole();

// Handle Delete Shipment (Agent restricted to their branch and status booked)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_shipment'])) {
    $delete_id = $_POST['delete_id'];
    try {
        // Double check status is booked and belongs to agent's city
        $stmt = $db->prepare("SELECT s.status, s.from_city_id, a.city_id as agent_city 
                              FROM shipments s 
                              JOIN agents a ON a.user_id = ?
                              WHERE s.id = ?");
        $stmt->execute([$user_id, $delete_id]);
        $shipment = $stmt->fetch();
        
        if (!$shipment) {
            setFlash('danger', "Shipment not found.");
        } elseif ($shipment['status'] !== 'booked') {
            setFlash('danger', "Only booked shipments can be deleted.");
        } elseif ($shipment['from_city_id'] != $shipment['agent_city']) {
            setFlash('danger', "You can only delete shipments from your own branch.");
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

// Fetch KPI Stats (Scoped to branch city)
$stats = [
    'total' => 0,
    'in_transit' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

try {
    // Total branch shipments
    $stmt = $db->prepare("SELECT COUNT(*) FROM shipments WHERE from_city_id = ? OR to_city_id = ?");
    $stmt->execute([$agent_city_id, $agent_city_id]);
    $stats['total'] = $stmt->fetchColumn();

    // Grouped by status
    $stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM shipments WHERE from_city_id = ? OR to_city_id = ? GROUP BY status");
    $stmt->execute([$agent_city_id, $agent_city_id]);
    while ($row = $stmt->fetch()) {
        if ($row['status'] == 'in_transit') $stats['in_transit'] = $row['cnt'];
        if ($row['status'] == 'delivered') $stats['delivered'] = $row['cnt'];
        if ($row['status'] == 'cancelled') $stats['cancelled'] = $row['cnt'];
    }
} catch (PDOException $e) {}

// Recent branch shipments
$stmt = $db->prepare("SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
                      FROM shipments s
                      LEFT JOIN cities fc ON s.from_city_id = fc.id
                      LEFT JOIN cities tc ON s.to_city_id = tc.id
                      WHERE s.from_city_id = ? OR s.to_city_id = ?
                      ORDER BY s.booked_at DESC 
                      LIMIT 5");
$stmt->execute([$agent_city_id, $agent_city_id]);
$recent_shipments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="alert alert-info" style="background: #EBF5FB; border-color: #AED6F1; color: #2E86C1;">
    <i class="fas fa-building"></i> Welcome to the Agent Portal. You are managing shipments for your assigned city.
</div>

<div class="kpi-grid">
    <a href="couriers.php" class="kpi-card">
        <div class="kpi-icon"><i class="fas fa-boxes-stacked"></i></div>
        <div class="kpi-data">
            <span class="number"><?php echo number_format($stats['total']); ?></span>
            <span class="label">Total Branch Shipments</span>
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
</div>

<div class="card">
    <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
        <span>Recent Activity</span>
        <div>
            <a href="../admin/courier_add.php" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px; margin-right: 5px;">
                <i class="fas fa-plus"></i> New Courier
            </a>
            <a href="couriers.php" class="btn btn-secondary" style="padding: 5px 12px; font-size: 12px;">View All</a>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tracking No</th>
                    <th>Route</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_shipments)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px;">No recent shipments.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_shipments as $s): ?>
                        <tr>
                            <td style="font-weight: 600;">#<?php echo sanitize($s['tracking_no']); ?></td>
                            <td style="font-size: 13px;"><?php echo sanitize($s['from_city']); ?> &rarr; <?php echo sanitize($s['to_city']); ?></td>
                            <td><span class="badge badge-<?php echo $s['status']; ?>"><?php echo $s['status']; ?></span></td>
                            <td>
                                <a href="../admin/courier_view.php?id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 11px;" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="../admin/courier_edit.php?id=<?php echo $s['id']; ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;" title="Update">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this shipment?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $s['id']; ?>">
                                    <input type="hidden" name="delete_shipment" value="1">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" <?php echo $s['status'] !== 'booked' ? 'disabled title="Cannot delete after booking stage"' : ''; ?>>
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

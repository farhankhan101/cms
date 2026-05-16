<?php
// Just a simple redirect to the shared admin/couriers.php but it should ideally be city-scoped
// However, to keep it simple and runnable, I will modify admin/couriers.php to be role-aware
// For now, let's create a city-scoped view for agents.

$page_title = "Branch Shipments";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['agent']);

$db = getDB();
$user_id = $_SESSION['user_id'];
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
        header("Location: couriers.php");
        exit();
    } catch (Exception $e) {
        setFlash('danger', "Error deleting shipment: " . $e->getMessage());
    }
}

// Get Agent City
$stmt = $db->prepare("SELECT city_id FROM agents WHERE user_id = ?");
$stmt->execute([$user_id]);
$agent_city_id = $stmt->fetchColumn();

$sql = "SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
        FROM shipments s
        LEFT JOIN cities fc ON s.from_city_id = fc.id
        LEFT JOIN cities tc ON s.to_city_id = tc.id
        WHERE s.from_city_id = ? OR s.to_city_id = ?
        ORDER BY s.booked_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$agent_city_id, $agent_city_id]);
$shipments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-title">My Branch Shipments</div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tracking No</th>
                    <th>Sender</th>
                    <th>Route</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shipments as $s): ?>
                    <tr>
                        <td style="font-weight: 600;">#<?php echo sanitize($s['tracking_no']); ?></td>
                        <td><?php echo sanitize($s['sender_name']); ?></td>
                        <td><?php echo sanitize($s['from_city']); ?> &rarr; <?php echo sanitize($s['to_city']); ?></td>
                        <td><span class="badge badge-<?php echo $s['status']; ?>"><?php echo $s['status']; ?></span></td>
                        <td>
                            <a href="../admin/courier_view.php?id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;"><i class="fas fa-eye"></i></a>
                            <a href="../admin/courier_edit.php?id=<?php echo $s['id']; ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;"><i class="fas fa-edit"></i></a>
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
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

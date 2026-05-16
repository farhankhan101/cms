<?php
$page_title = "Manage Agents";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - only admin allowed to manage agents
protectPage(['admin']);

$db = getDB();

// Handle Delete Agent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_agent'])) {
    $delete_id = $_POST['agent_id'];
    try {
        $db->beginTransaction();
        
        // Get user_id first
        $stmt = $db->prepare("SELECT user_id FROM agents WHERE id = ?");
        $stmt->execute([$delete_id]);
        $user_id = $stmt->fetchColumn();
        
        if ($user_id) {
            // 1. Nullify agent references in shipments
            $stmt = $db->prepare("UPDATE shipments SET agent_id = NULL WHERE agent_id = ?");
            $stmt->execute([$delete_id]);
            
            // 2. Delete agent record
            $stmt = $db->prepare("DELETE FROM agents WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            // 3. Delete user record
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $db->commit();
            setFlash('success', "Agent and associated user account deleted successfully.");
        } else {
            $db->rollBack();
            setFlash('danger', "Agent not found.");
        }
        header("Location: agents.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', "Error deleting agent: " . $e->getMessage());
    }
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $agent_id = $_POST['agent_id'];
    $new_status = $_POST['status'];
    try {
        $stmt = $db->prepare("SELECT user_id FROM agents WHERE id = ?");
        $stmt->execute([$agent_id]);
        $user_id = $stmt->fetchColumn();
        
        if ($user_id) {
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            setFlash('success', "Agent status updated successfully.");
        }
        header("Location: agents.php");
        exit();
    } catch (Exception $e) {
        setFlash('danger', "Error updating status: " . $e->getMessage());
    }
}

$search = $_GET['search'] ?? '';

// Fetch Agents with User and City details
$sql = "SELECT a.id, u.name, u.email, u.is_active, a.branch_code, c.city_name, u.created_at 
        FROM agents a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN cities c ON a.city_id = c.id 
        WHERE 1=1";

$params = [];
if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR a.branch_code LIKE ? OR c.city_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$agents = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; font-size: 1.5rem; color: var(--primary-dark);">Registered Agents</h2>
        <a href="agent_add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Agent
        </a>
    </div>

    <form action="agents.php" method="GET" style="display: flex; gap: 10px; align-items: flex-end;">
        <div style="flex: 1;">
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Search Agents (Name/Email/Branch/City)</label>
            <input type="text" name="search" class="form-control" value="<?php echo sanitize($search); ?>" placeholder="Search..." style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="agents.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Branch Code</th>
                    <th>Status</th>
                    <th>Joined At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agents)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 20px;">No agents found.</td></tr>
                <?php else: ?>
                    <?php foreach ($agents as $a): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo sanitize($a['name']); ?></td>
                            <td><?php echo sanitize($a['email']); ?></td>
                            <td><?php echo sanitize($a['city_name'] ?: 'N/A'); ?></td>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);"><?php echo sanitize($a['branch_code']); ?></td>
                            <td>
                                <?php if ($a['is_active']): ?>
                                    <span class="badge" style="background: #e6fcf5; color: #0ca678; border: 1px solid #c3fae8;">Active</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #fff5f5; color: #f03e3e; border: 1px solid #ffe3e3;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($a['created_at']); ?></td>
                            <td style="display: flex; gap: 5px;">
                                <a href="agent_edit.php?id=<?php echo $a['id']; ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;" title="Edit Agent">
                                    <i class="fas fa-user-edit"></i>
                                </a>
                                
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="agent_id" value="<?php echo $a['id']; ?>">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="status" value="<?php echo $a['is_active'] ? 0 : 1; ?>">
                                    <button type="submit" class="btn <?php echo $a['is_active'] ? 'btn-warning' : 'btn-success'; ?>" style="padding: 4px 8px; font-size: 12px;" title="<?php echo $a['is_active'] ? 'Deactivate Agent' : 'Activate Agent'; ?>">
                                        <i class="fas <?php echo $a['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    </button>
                                </form>

                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this agent? Their user account and history will be affected.');">
                                    <input type="hidden" name="agent_id" value="<?php echo $a['id']; ?>">
                                    <input type="hidden" name="delete_agent" value="1">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" title="Delete Agent">
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

<?php
$page_title = "Manage Customers";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - admin and agent allowed
protectPage(['admin', 'agent']);

$db = getDB();
$search = $_GET['search'] ?? '';

// Fetch Customers (Users with role='user')
$sql = "SELECT u.name, u.email, u.nic, c.phone, c.address, u.created_at 
        FROM users u 
        LEFT JOIN customers c ON u.id = c.user_id 
        WHERE u.role = 'user'";

$params = [];
if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR c.phone LIKE ? OR u.nic LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom: 20px;">
    <form action="customers.php" method="GET" style="display: flex; gap: 10px; align-items: flex-end;">
        <div style="flex: 1;">
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Search Customer (Name/Email/Phone)</label>
            <input type="text" name="search" class="form-control" value="<?php echo sanitize($search); ?>" placeholder="Search..." style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="customers.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<div class="card">
    <div class="card-title">Registered Customers</div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>CNIC</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Joined At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 20px;">No registered customers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo sanitize($c['name']); ?></td>
                            <td><?php echo sanitize($c['email']); ?></td>
                            <td style="font-size: 13px; color: var(--primary-dark); font-weight: 600;"><?php echo sanitize($c['nic'] ?: 'N/A'); ?></td>
                            <td><?php echo sanitize($c['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo sanitize($c['address'] ?? 'N/A'); ?></td>
                            <td><?php echo formatDate($c['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

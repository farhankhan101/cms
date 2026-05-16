<?php
$page_title = "Edit Agent";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - only admin allowed
protectPage(['admin']);

$db = getDB();
$error = '';
$success = '';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: agents.php");
    exit();
}

// Fetch Agent Details
$stmt = $db->prepare("SELECT a.*, u.name, u.email, u.is_active, u.id as user_id 
                      FROM agents a 
                      JOIN users u ON a.user_id = u.id 
                      WHERE a.id = ?");
$stmt->execute([$id]);
$agent = $stmt->fetch();

if (!$agent) {
    setFlash('danger', "Agent not found.");
    header("Location: agents.php");
    exit();
}

// Fetch Cities
$cities = $db->query("SELECT * FROM cities ORDER BY city_name ASC")->fetchAll();

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $city_id = $_POST['city_id'];
    $branch_code = trim($_POST['branch_code']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($city_id) || empty($branch_code)) {
        $error = "All fields except password are required.";
    } else {
        // Check if email already exists for another user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $agent['user_id']]);
        if ($stmt->fetch()) {
            $error = "This email is already registered with another account.";
        } else {
            try {
                $db->beginTransaction();

                // 1. Update User
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $hashed_password, $is_active, $agent['user_id']]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $is_active, $agent['user_id']]);
                }

                // 2. Update Agent record
                $stmt = $db->prepare("UPDATE agents SET city_id = ?, branch_code = ? WHERE id = ?");
                $stmt->execute([$city_id, $branch_code, $id]);

                $db->commit();
                setFlash('success', "Agent updated successfully!");
                header("Location: agents.php");
                exit();
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-title">
        <i class="fas fa-user-edit"></i> Edit Agent: <?php echo sanitize($agent['name']); ?>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="agent_edit.php?id=<?php echo $id; ?>" method="POST">
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo sanitize($agent['name']); ?>" required>
        </div>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" value="<?php echo sanitize($agent['email']); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div class="form-group">
                <label>Assigned City</label>
                <select name="city_id" class="form-control" required>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city['id']; ?>" <?php echo $agent['city_id'] == $city['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($city['city_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Branch Code</label>
                <input type="text" name="branch_code" class="form-control" value="<?php echo sanitize($agent['branch_code']); ?>" required>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>Update Password (leave blank to keep current)</label>
            <input type="password" name="password" class="form-control" placeholder="New password...">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="is_active" <?php echo $agent['is_active'] ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                <span>Account Active</span>
            </label>
        </div>

        <div style="display: flex; gap: 10px; border-top: 1px solid #eee; padding-top: 20px; flex-wrap: wrap;">
            <a href="agents.php" class="btn btn-secondary" style="flex: 1; min-width: 120px;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="flex: 2; min-width: 200px;">Update Agent Account</button>
        </div>
    </form>
    
    <div style="margin-top: 20px; border-top: 1px dashed #eee; padding-top: 20px;">
        <form action="agents.php" method="POST" onsubmit="return confirm('CRITICAL: Are you sure you want to permanently delete this agent and their user account?');">
            <input type="hidden" name="agent_id" value="<?php echo $id; ?>">
            <input type="hidden" name="delete_agent" value="1">
            <button type="submit" class="btn btn-danger" style="width: 100%;">
                <i class="fas fa-trash-alt"></i> Delete Agent Account Permanently
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

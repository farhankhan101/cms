<?php
$page_title = "Add New Agent";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - only admin allowed
protectPage(['admin']);

$db = getDB();
$error = '';

// Fetch Cities
$cities = $db->query("SELECT * FROM cities ORDER BY city_name ASC")->fetchAll();

// Handle Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $city_id = $_POST['city_id'];
    $branch_code = trim($_POST['branch_code']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($email) || empty($password) || empty($city_id) || empty($branch_code)) {
        $error = "All fields are required.";
    } else {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "This email is already registered.";
        } else {
            try {
                $db->beginTransaction();

                // 1. Create User
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'agent', ?)");
                $stmt->execute([$name, $email, $hashed_password, $is_active]);
                $user_id = $db->lastInsertId();

                // 2. Create Agent record
                $stmt = $db->prepare("INSERT INTO agents (user_id, city_id, branch_code) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $city_id, $branch_code]);

                $db->commit();
                setFlash('success', "New agent account created successfully!");
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
        <i class="fas fa-user-plus"></i> Register New Agent
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="agent_add.php" method="POST">
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>" required>
        </div>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label>Initial Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div class="form-group">
                <label>Assigned City</label>
                <select name="city_id" class="form-control" required>
                    <option value="">Select City...</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city['id']; ?>" <?php echo (isset($_POST['city_id']) && $_POST['city_id'] == $city['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($city['city_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Branch Code</label>
                <input type="text" name="branch_code" class="form-control" value="<?php echo isset($_POST['branch_code']) ? sanitize($_POST['branch_code']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="is_active" checked style="width: 18px; height: 18px;">
                <span>Account Active (Allow Login)</span>
            </label>
        </div>

        <div style="display: flex; gap: 10px; border-top: 1px solid #eee; padding-top: 20px;">
            <a href="agents.php" class="btn btn-secondary" style="flex: 1;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="flex: 2;">Register Agent</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

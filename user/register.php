<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $nic = trim($_POST['nic']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name) || empty($email) || empty($password) || empty($nic)) {
        $error = "Name, Email, NIC and Password are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            // Also check if NIC is already registered
            $stmt = $db->prepare("SELECT id FROM users WHERE nic = ?");
            $stmt->execute([$nic]);
            if ($stmt->fetch()) {
                $error = "NIC already registered.";
            } else {
                try {
                    $db->beginTransaction();

                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, nic) VALUES (?, ?, ?, 'user', ?)");
                    $stmt->execute([$name, $email, $hashed_password, $nic]);
                    $user_id = $db->lastInsertId();

                    $stmt = $db->prepare("INSERT INTO customers (user_id, phone, address) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $phone, $address]);

                    // --- NOTIFICATION LOGIC ---
                    // 1. Welcome Notification for User
                    addNotification($user_id, 'Welcome to CMSPRO!', "Hi $name, thank you for joining us. You can now track your shipments using your NIC.", "user/dashboard.php");

                    // 2. Notify Admins
                    $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
                    foreach ($admins as $admin) {
                        addNotification($admin['id'], 'New User Registration', "New customer registered: $name ($email)", "admin/customers.php");
                    }
                    // ---------------------------

                    $db->commit();
                    $success = "Registration successful! You can now login.";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CMSPRO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 40px 20px;
        }
        .reg-container {
            width: 100%;
            max-width: 550px;
        }
        .reg-brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .reg-brand a {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }
        .reg-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        .reg-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .reg-header h2 {
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }
        .reg-header p {
            color: var(--text-med);
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-dark);
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            transition: 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
    </style>
</head>
<body>
    <div class="reg-container">
        <div class="reg-brand">
            <a href="../index.php"><i class="fas fa-paper-plane"></i> CMS<span>PRO</span></a>
        </div>

        <div class="reg-card">
            <div class="reg-header">
                <h2>Create Account</h2>
                <p>Join Pakistan's most reliable courier network</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="text-align: center; font-size: 14px; padding: 10px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="text-align: center; padding: 20px;">
                    <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 15px; display: block;"></i>
                    <h3 style="margin-bottom: 10px;">Awesome!</h3>
                    <p><?php echo $success; ?></p>
                    <a href="../login.php" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Sign In Now</a>
                </div>
            <?php else: ?>
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                    </div>
                    <div class="form-group">
                        <label>CNIC Number</label>
                        <input type="text" name="nic" id="nic" class="form-control" placeholder="42101-XXXXXXX-X" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+92 3XX XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Full Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="House #, Street, City"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; padding: 14px; font-weight: 700; border-radius: 10px;">Create My Account</button>
                </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 25px; font-size: 14px; color: var(--text-med);">
                Already have an account? <a href="../login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign In</a>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 25px;">
            <a href="../index.php" style="color: var(--text-med); text-decoration: none; font-size: 14px; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
    <script>
        // CNIC Input Masking (XXXXX-XXXXXXX-X)
        const nicInput = document.getElementById('nic');
        if (nicInput) {
            nicInput.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                let formattedValue = '';
                
                if (value.length > 0) {
                    formattedValue = value.substring(0, 5);
                    if (value.length > 5) {
                        formattedValue += '-' + value.substring(5, 12);
                    }
                    if (value.length > 12) {
                        formattedValue += '-' + value.substring(12, 13);
                    }
                }
                
                e.target.value = formattedValue;
            });
        }
    </script>
</body>
</html>

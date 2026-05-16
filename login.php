<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'admin') header("Location: admin/dashboard.php");
    elseif ($role === 'agent') header("Location: agent/dashboard.php");
    else header("Location: user/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] === 'agent') {
                header("Location: agent/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CMSPRO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        .login-brand {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-brand a {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        .login-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .login-header h2 {
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }
        .login-header p {
            color: var(--text-med);
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
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
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            transition: 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 700;
            margin-top: 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-brand">
            <a href="index.php"><i class="fas fa-paper-plane"></i> CMS<span>PRO</span></a>
        </div>
        
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please enter your details to sign in</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="text-align: center; font-size: 14px; padding: 10px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required autofocus>
                </div>
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label for="password" style="margin-bottom: 0;">Password</label>
                        <a href="#" style="font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 600;">Forgot password?</a>
                    </div>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 25px;">
                    <input type="checkbox" id="remember" style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="remember" style="font-size: 14px; color: var(--text-med); margin-bottom: 0; cursor: pointer; font-weight: 500;">Remember for 30 days</label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">Sign In</button>
            </form>

            <div style="text-align: center; margin-top: 30px; font-size: 14px; color: var(--text-med);">
                Don't have an account? <a href="user/register.php" style="color: var(--primary); text-decoration: none; font-weight: 700;">Create one for free</a>
            </div>
        </div>

        <div style="text-align: center; margin-top: 25px;">
            <a href="index.php" style="color: var(--text-med); text-decoration: none; font-size: 14px; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>

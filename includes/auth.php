<?php
require_once __DIR__ . '/../config.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Role guard: Redirect if not logged in or role mismatch
 */
function protectPage($allowed_roles = []) {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }

    if (!empty($allowed_roles) && !in_array(getUserRole(), $allowed_roles)) {
        // Redirect to their own dashboard if they have the wrong role
        $role = getUserRole();
        if ($role === 'admin') {
            header("Location: " . BASE_URL . "/admin/dashboard.php");
        } elseif ($role === 'agent') {
            header("Location: " . BASE_URL . "/agent/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "/user/track.php");
        }
        exit();
    }
}

/**
 * CSRF Protection: Generate token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Protection: Verify token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
<?php
// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'courier_cms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App settings
define('APP_NAME', 'Courier Management System');
define('BASE_URL', '/cms'); // Adjust to your local path

// Error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
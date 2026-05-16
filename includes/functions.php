<?php

/**
 * Sanitize output
 */
function sanitize($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Generate tracking number
 */
function generateTrackingNo(): string {
    return 'CMS' . strtoupper(uniqid()) . rand(100, 999);
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, warning, danger, info
        'message' => $message
    ];
}

/**
 * Display flash message
 */
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">
                ' . sanitize($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

/**
 * Add a notification for a user
 */
function addNotification($user_id, $title, $message, $link = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $link]);
}

/**
 * Get unread notifications for the current user
 */
function getUnreadNotifications($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
/**
 * Send a WhatsApp message via the Node.js bridge
 */
function sendWhatsAppAlert($phone, $message) {
    $url = 'http://localhost:3005/send-message';
    
    $data = [
        'phone' => $phone,
        'message' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Don't hang the UI
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // DEBUG LOGGING
    $log_msg = "[" . date('Y-m-d H:i:s') . "] To: $phone | Status: $http_code | Response: $response\n";
    file_put_contents(__DIR__ . '/../whatsapp_bridge/debug_send.log', $log_msg, FILE_APPEND);

    if ($http_code === 200) {
        return true;
    }
    return false;
}
?>
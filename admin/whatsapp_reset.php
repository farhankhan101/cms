<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['admin']);

// Reset commands
// 1. Kill any existing node process for the server
// 2. Remove the session folder (sessions_final)
// 3. Remove the old QR code image
// 4. Start the server again in the background
$bridge_dir = realpath(__DIR__ . '/../whatsapp_bridge');
$sessions_dir = $bridge_dir . '/sessions_final';
$qr_image = realpath(__DIR__ . '/../assets/img') . '/wa_qr.png';

// Build command
$cmd = "pkill -f 'node server.js' || true; rm -rf " . escapeshellarg($sessions_dir) . " " . escapeshellarg($qr_image) . "; cd " . escapeshellarg($bridge_dir) . " && nohup node server.js > server.log 2>&1 &";

exec($cmd);

setFlash('success', 'WhatsApp has been disconnected and reset to default state. Please wait a few seconds for a new QR code.');
header("Location: whatsapp_setup.php");
exit();

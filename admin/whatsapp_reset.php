<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['admin']);

// Reset commands
$cmd = 'pkill -f "node server.js" && rm -rf ' . escapeshellarg(__DIR__ . '/../whatsapp_bridge/sessions') . ' && rm -f ' . escapeshellarg(__DIR__ . '/../assets/img/wa_qr.png') . ' && nohup node ' . escapeshellarg(__DIR__ . '/../whatsapp_bridge/server.js') . ' > ' . escapeshellarg(__DIR__ . '/../whatsapp_bridge/server.log') . ' 2>&1 &';

exec($cmd);

setFlash('success', 'WhatsApp connection has been reset. Please wait a few seconds for the new QR code.');
header("Location: whatsapp_setup.php");
exit();

<?php
$page_title = "WhatsApp Configuration";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['admin']);

$status_file = __DIR__ . '/../whatsapp_bridge/status.json';
$status_data = ['status' => 'initializing'];
if (file_exists($status_file)) {
    $status_data = json_decode(file_get_contents($status_file), true);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-title"><i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp Web Integration</div>
    
    <div style="text-align: center; padding: 40px;">
        <div id="qr-container" style="margin-bottom: 30px;">
            <div id="wa-status-badge" style="margin-bottom: 20px;">
                <span class="badge <?php 
                    echo ($status_data['status'] == 'connected') ? 'badge-success' : 
                         (($status_data['status'] == 'qr_ready') ? 'badge-primary' : 'badge-secondary'); 
                ?>" id="status-text">
                    <?php echo ucfirst(str_replace('_', ' ', $status_data['status'])); ?>
                </span>
            </div>
            
            <div id="status-content">
                <?php if ($status_data['status'] == 'connected'): ?>
                    <div style="color: #25D366; font-size: 60px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>WhatsApp Connected!</h3>
                    <p>Your device is successfully linked and ready to send alerts.</p>
                    
                    <div style="margin-top: 30px; padding: 20px; border-top: 1px solid #eee;">
                        <form method="POST" action="whatsapp_reset.php">
                            <button type="submit" class="btn btn-danger" style="padding: 10px 25px;" onclick="return confirm('Are you sure you want to disconnect WhatsApp?')">
                                <i class="fas fa-sign-out-alt"></i> Logout / Disconnect
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <?php if (file_exists('../assets/img/wa_qr.png') && $status_data['status'] == 'qr_ready'): ?>
                        <img src="../assets/img/wa_qr.png?t=<?php echo time(); ?>" id="qr-image" style="width: 250px; border: 10px solid #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 10px;">
                        <h3 style="margin-top: 20px;">Scan QR Code</h3>
                        <p>Please scan this code with your WhatsApp app.</p>
                    <?php else: ?>
                        <div id="loading-wa">
                            <i class="fas fa-circle-notch fa-spin" style="font-size: 50px; color: var(--primary); margin-bottom: 20px;"></i>
                            <h3>Initializing...</h3>
                            <p style="color: var(--text-med);">Please wait while the WhatsApp bridge starts up.</p>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 30px; padding: 20px; border-top: 1px solid #eee;">
                        <form method="POST" action="whatsapp_reset.php">
                            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('This will restart the server and generate a new QR. Continue?')">
                                <i class="fas fa-sync-alt"></i> Force Reset / Get New QR
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    async function checkStatus() {
        try {
            const response = await fetch('http://localhost:3005/status');
            const data = await response.json();
            const statusBadge = document.getElementById('status-text');
            const content = document.getElementById('status-content');
            
            const isCurrentlyConnected = content.innerHTML.includes('Connected!');

            statusBadge.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('_', ' ');
            if (data.status === 'connected') statusBadge.className = 'badge badge-success';
            else if (data.status === 'qr_ready') statusBadge.className = 'badge badge-primary';
            else statusBadge.className = 'badge badge-secondary';

            if (data.status === 'connected' && !isCurrentlyConnected) {
                location.reload();
            } else if (data.status !== 'connected' && isCurrentlyConnected) {
                location.reload();
            }

        } catch (e) {
            document.getElementById('status-text').innerText = 'Bridge Offline';
            document.getElementById('status-text').className = 'badge badge-danger';
        }
    }

    setInterval(checkStatus, 3000);
</script>

<div class="card" style="background: #f0fdf4; border: 1px solid #bbf7d0; margin-top: 20px;">
    <h4 style="color: #166534; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> WhatsApp Instructions</h4>
    <p style="font-size: 14px; color: #166534;">
        1. Open WhatsApp on your phone.<br>
        2. Tap <b>Menu</b> or <b>Settings</b> and select <b>Linked Devices</b>.<br>
        3. Tap on <b>Link a Device</b>.<br>
        4. Point your phone to this screen to capture the code.
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

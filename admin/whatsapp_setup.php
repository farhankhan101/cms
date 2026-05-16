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
                <?php elseif (file_exists('../assets/img/wa_qr.png')): ?>
                    <img src="../assets/img/wa_qr.png?t=<?php echo time(); ?>" id="qr-image" style="width: 250px; border: 10px solid #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 10px;">
                    <h3 style="margin-top: 20px;">Scan QR Code</h3>
                    <p>Please scan this code with your WhatsApp app.</p>
                <?php else: ?>
                    <div id="loading-wa">
                        <i class="fas fa-circle-notch fa-spin" style="font-size: 50px; color: var(--primary); margin-bottom: 20px;"></i>
                        <h3>Initializing...</h3>
                        <p style="color: var(--text-med);">Please wait while the WhatsApp bridge refreshes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; display: inline-block; border: 1.5px dashed #ccc;">
            <p style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Connection Settings:</p>
            
            <form method="POST" action="whatsapp_reset.php" style="margin-top: 10px;">
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('This will clear your current WhatsApp session. Continue?')">
                    <i class="fas fa-sync-alt"></i> Reset/Disconnect WhatsApp
                </button>
            </form>
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
            
            // Update badge
            statusBadge.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('_', ' ');
            if (data.status === 'connected') statusBadge.className = 'badge badge-success';
            else if (data.status === 'qr_ready') statusBadge.className = 'badge badge-primary';
            else statusBadge.className = 'badge badge-secondary';

            // If status changed to connected and we were not showing connected UI
            if (data.status === 'connected' && !content.innerHTML.includes('Connected!')) {
                content.innerHTML = `
                    <div style="color: #25D366; font-size: 60px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>WhatsApp Connected!</h3>
                    <p>Your device is successfully linked and ready to send alerts.</p>
                `;
            }
            
            // If it was connected but now it's disconnected/qr_ready (meaning we should reload for QR)
            if (data.status !== 'connected' && content.innerHTML.includes('Connected!')) {
                location.reload();
            }

        } catch (e) {
            document.getElementById('status-text').innerText = 'Bridge Offline';
            document.getElementById('status-text').className = 'badge badge-danger';
        }
    }

    setInterval(checkStatus, 3000);
</script>

<div class="card" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
    <h4 style="color: #166534; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> WhatsApp Status</h4>
    <p style="font-size: 14px; color: #166534;">
        Jab aapka mobile connect ho jayega, toh aapko yahan <b>"Connected"</b> ka status nazar aayega. Uske baad aap kisi bhi shipment ki status update karte waqt WhatsApp alert bhej sakenge.
    </p>
</div>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

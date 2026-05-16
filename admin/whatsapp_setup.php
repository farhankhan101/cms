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

        <div style="background: #fff; padding: 25px; border-radius: 15px; display: inline-block; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 500px;">
            <p style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--primary-dark);">Connection Management:</p>
            
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <form method="POST" action="whatsapp_reset.php" style="flex: 1;">
                    <button type="submit" class="btn btn-danger" style="width: 100%; white-space: nowrap;" onclick="return confirm('This will disconnect WhatsApp and reset all session data. Continue?')">
                        <i class="fas fa-power-off"></i> Disconnect & Reset
                    </button>
                </form>
                
                <a href="whatsapp_setup.php" class="btn btn-secondary" style="flex: 1;">
                    <i class="fas fa-sync-alt"></i> Refresh Status
                </a>
            </div>

            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; text-align: left;">
                <p style="font-size: 13px; font-weight: 600; color: #666; margin-bottom: 8px;">
                    <i class="fas fa-terminal"></i> Server Start Command:
                </p>
                <div style="background: #2d3436; color: #fab1a0; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px; position: relative; overflow-x: auto;">
                    <code>cd /opt/lampp/htdocs/cms/whatsapp_bridge && node server.js</code>
                </div>
                <p style="font-size: 11px; color: #999; margin-top: 8px;">
                    * Run this command in your terminal if the bridge status shows "Offline".
                </p>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

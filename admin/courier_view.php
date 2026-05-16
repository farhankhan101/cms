<?php
$page_title = "Shipment Details";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - admin and agent allowed
protectPage(['admin', 'agent']);

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: couriers.php");
    exit();
}

// Fetch shipment details
$stmt = $db->prepare("SELECT s.*, fc.city_name as from_city, tc.city_name as to_city, ct.type_name
                      FROM shipments s
                      LEFT JOIN cities fc ON s.from_city_id = fc.id
                      LEFT JOIN cities tc ON s.to_city_id = tc.id
                      LEFT JOIN courier_types ct ON s.courier_type_id = ct.id
                      WHERE s.id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    setFlash('danger', "Shipment not found.");
    header("Location: couriers.php");
    exit();
}

// Fetch status history
$stmt = $db->prepare("SELECT sh.*, u.name as updated_by_name 
                      FROM shipment_status sh 
                      LEFT JOIN users u ON sh.updated_by = u.id 
                      WHERE sh.shipment_id = ? 
                      ORDER BY sh.updated_at DESC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="no-print" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <a href="couriers.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Label
        </button>
        <a href="courier_edit.php?id=<?php echo $s['id']; ?>" class="btn btn-outline">
            <i class="fas fa-edit"></i> Edit Shipment
        </a>
    </div>
</div>

<div class="grid no-print" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
    <!-- Details Column -->
    <div>
        <div class="card" style="margin-bottom: 25px;">
            <div class="card-title">
                <i class="fas fa-info-circle"></i> Shipment Information
                <span class="badge badge-<?php echo $s['status']; ?>" style="margin-left: auto;">
                    <?php echo str_replace('_', ' ', $s['status']); ?>
                </span>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <h4 style="font-size: 14px; color: var(--text-med); margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 5px;">SENDER DETAILS</h4>
                    <div style="margin-bottom: 10px;">
                        <div style="font-size: 12px; color: var(--text-light);">Name</div>
                        <div style="font-weight: 700;"><?php echo sanitize($s['sender_name']); ?></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <div style="font-size: 12px; color: var(--text-light);">Origin City</div>
                        <div style="font-weight: 600;"><?php echo sanitize($s['from_city']); ?></div>
                    </div>
                </div>
                <div>
                    <h4 style="font-size: 14px; color: var(--text-med); margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 5px;">RECEIVER DETAILS</h4>
                    <div style="margin-bottom: 10px;">
                        <div style="font-size: 12px; color: var(--text-light);">Name</div>
                        <div style="font-weight: 700;"><?php echo sanitize($s['receiver_name']); ?></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <div style="font-size: 12px; color: var(--text-light);">Phone</div>
                        <div style="font-weight: 600;"><?php echo sanitize($s['receiver_phone']); ?></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <div style="font-size: 12px; color: var(--text-light);">Address</div>
                        <div style="font-weight: 500; font-size: 13px;"><?php echo nl2br(sanitize($s['receiver_address'])); ?></div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <div style="font-size: 12px; color: var(--text-light);">Destination City</div>
                        <div style="font-weight: 600;"><?php echo sanitize($s['to_city']); ?></div>
                    </div>
                </div>
            </div>

            <h4 style="font-size: 14px; color: var(--text-med); margin-top: 20px; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 5px;">PARCEL DETAILS</h4>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div>
                    <div style="font-size: 12px; color: var(--text-light);">Tracking No</div>
                    <div style="font-weight: 700; color: var(--primary);">#<?php echo sanitize($s['tracking_no']); ?></div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--text-light);">Type</div>
                    <div style="font-weight: 600;"><?php echo sanitize($s['type_name']); ?></div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--text-light);">Weight</div>
                    <div style="font-weight: 600;"><?php echo $s['weight']; ?> kg</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--text-light);">Amount</div>
                    <div style="font-weight: 700;">$<?php echo number_format($s['amount'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Column -->
    <div>
        <div class="card">
            <div class="card-title"><i class="fas fa-history"></i> Tracking History</div>
            <div class="timeline">
                <?php if (empty($history)): ?>
                    <div class="timeline-item active">
                        <div class="timeline-content">
                            <div class="timeline-time"><?php echo formatDate($s['booked_at'], 'M d, Y H:i'); ?></div>
                            <div class="timeline-title">Shipment Booked</div>
                            <div class="timeline-note">Parcel received at <?php echo sanitize($s['from_city']); ?> branch.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($history as $index => $h): ?>
                        <div class="timeline-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="timeline-content">
                                <div class="timeline-time"><?php echo formatDate($h['updated_at'], 'M d, Y H:i'); ?></div>
                                <div class="timeline-title"><?php echo str_replace('_', ' ', strtoupper($h['status'])); ?></div>
                                <?php if ($h['note']): ?>
                                    <div class="timeline-note"><?php echo sanitize($h['note']); ?></div>
                                <?php endif; ?>
                                <div style="font-size: 10px; color: var(--text-light); margin-top: 5px;">Updated by: <?php echo sanitize($h['updated_by_name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="timeline-time"><?php echo formatDate($s['booked_at'], 'M d, Y H:i'); ?></div>
                            <div class="timeline-title">SHIPMENT BOOKED</div>
                            <div class="timeline-note">Initial booking at <?php echo sanitize($s['from_city']); ?>.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PRINTABLE LABEL -->
<div class="shipping-label-container">
    <div class="shipping-label">
        <div class="label-header">
            <div class="label-logo" style="color: #000; font-weight: 800; font-size: 20px;">
                <i class="fas fa-box-open"></i> CMS LOGISTICS
            </div>
            <div style="text-align: right; font-size: 10px; font-weight: 700;">
                DATE: <?php echo date('d-m-Y'); ?><br>
                REF: <?php echo $s['id']; ?>
            </div>
        </div>

        <div class="label-tracking" style="text-align: center; margin: 20px 0;">
            <img src="https://barcodeapi.org/api/128/<?php echo $s['tracking_no']; ?>" style="max-width: 100%; height: 60px;" alt="Barcode">
            <div style="font-size: 18px; font-weight: 800; letter-spacing: 2px; margin-top: 5px;">
                <?php echo sanitize($s['tracking_no']); ?>
            </div>
        </div>

        <div class="label-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 12px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 15px 0;">
            <div style="border-right: 1px solid #000; padding-right: 10px;">
                <div style="font-weight: 700; text-transform: uppercase; font-size: 10px; margin-bottom: 5px; color: #666;">FROM (SENDER)</div>
                <div style="font-weight: 600; line-height: 1.4;">
                    <?php echo strtoupper(sanitize($s['sender_name'])); ?><br>
                    <?php echo strtoupper(sanitize($s['from_city'])); ?>
                </div>
            </div>
            <div style="padding-left: 5px;">
                <div style="font-weight: 700; text-transform: uppercase; font-size: 10px; margin-bottom: 5px; color: #666;">TO (RECEIVER)</div>
                <div style="font-weight: 600; line-height: 1.4;">
                    <?php echo strtoupper(sanitize($s['receiver_name'])); ?><br>
                    <?php echo sanitize($s['receiver_phone']); ?><br>
                    <?php echo strtoupper(sanitize($s['receiver_address'])); ?><br>
                    <strong><?php echo strtoupper(sanitize($s['to_city'])); ?></strong>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: flex-end;">
            <div style="flex: 1;">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <div>
                        <span style="font-weight: 700; text-transform: uppercase; font-size: 10px; color: #666;">WEIGHT:</span>
                        <span style="font-weight: 700; font-size: 14px;"><?php echo $s['weight']; ?> KG</span>
                    </div>
                    <div>
                        <span style="font-weight: 700; text-transform: uppercase; font-size: 10px; color: #666;">SERVICE:</span>
                        <span style="font-weight: 700; font-size: 14px;"><?php echo strtoupper(sanitize($s['type_name'])); ?></span>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($s['tracking_no']); ?>" style="width: 80px; height: 80px;" alt="QR Code">
            </div>
        </div>

        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc; display: flex; justify-content: space-between; font-size: 11px;">
            <div>CMS Logistics - Tracking: cms-logistics.com/track</div>
            <div style="font-weight: 700;">HANDLED WITH CARE</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

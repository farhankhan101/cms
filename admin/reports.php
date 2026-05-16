<?php
$page_title = "System Reports";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect this page - only admin allowed for full reports
protectPage(['admin']);

$db = getDB();

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$city_id = $_GET['city_id'] ?? '';
$status = $_GET['status'] ?? '';
$nic = $_GET['nic'] ?? '';

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="shipment_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Tracking No', 'Sender', 'Sender NIC', 'Receiver', 'Receiver NIC', 'From', 'To', 'Weight', 'Amount', 'Status', 'Date']);
    
    $sql = "SELECT s.tracking_no, s.sender_name, s.sender_nic, s.receiver_name, s.receiver_nic, fc.city_name as from_city, tc.city_name as to_city, s.weight, s.amount, s.status, s.booked_at 
            FROM shipments s
            LEFT JOIN cities fc ON s.from_city_id = fc.id
            LEFT JOIN cities tc ON s.to_city_id = tc.id
            WHERE DATE(s.booked_at) BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    
    if ($city_id) { $sql .= " AND (s.from_city_id = ? OR s.to_city_id = ?)"; $params[] = $city_id; $params[] = $city_id; }
    if ($status) { $sql .= " AND s.status = ?"; $params[] = $status; }
    if ($nic) { $sql .= " AND (s.sender_nic = ? OR s.receiver_nic = ?)"; $params[] = $nic; $params[] = $nic; }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Fetch Cities for Filter
$cities = $db->query("SELECT * FROM cities ORDER BY city_name ASC")->fetchAll();

// Fetch Data for Preview
$sql = "SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
        FROM shipments s
        LEFT JOIN cities fc ON s.from_city_id = fc.id
        LEFT JOIN cities tc ON s.to_city_id = tc.id
        WHERE DATE(s.booked_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($city_id) { $sql .= " AND (s.from_city_id = ? OR s.to_city_id = ?)"; $params[] = $city_id; $params[] = $city_id; }
if ($status) { $sql .= " AND s.status = ?"; $params[] = $status; }
if ($nic) { $sql .= " AND (s.sender_nic = ? OR s.receiver_nic = ?)"; $params[] = $nic; $params[] = $nic; }

$stmt = $db->prepare($sql);
$stmt->execute($params);
$report_data = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-title">Filter Report</div>
    <form action="reports.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px; align-items: flex-end;">
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">City</label>
            <select name="city_id" class="form-control" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
                <option value="">All Cities</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $city_id == $c['id'] ? 'selected' : ''; ?>><?php echo sanitize($c['city_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Status</label>
            <select name="status" class="form-control" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
                <option value="">All Status</option>
                <option value="booked" <?php echo $status == 'booked' ? 'selected' : ''; ?>>Booked</option>
                <option value="delivered" <?php echo $status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">CNIC Number</label>
            <input type="text" name="nic" class="form-control" placeholder="XXXXX-XXXXXXX-X" value="<?php echo sanitize($nic); ?>" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Preview</button>
            <button type="submit" name="export" value="csv" class="btn btn-secondary" title="Export CSV"><i class="fas fa-file-csv"></i></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title">Report Preview</div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Tracking</th>
                    <th>Route</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report_data)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px;">No data found for the selected filters.</td></tr>
                <?php else: ?>
                    <?php $total_rev = 0; foreach ($report_data as $row): $total_rev += $row['amount']; ?>
                        <tr>
                            <td><?php echo formatDate($row['booked_at'], 'M d'); ?></td>
                            <td style="font-weight: 600;">#<?php echo sanitize($row['tracking_no']); ?></td>
                            <td style="font-size: 12px;"><?php echo sanitize($row['from_city']); ?> &rarr; <?php echo sanitize($row['to_city']); ?></td>
                            <td>$<?php echo number_format($row['amount'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #f8f9fa; font-weight: 700;">
                        <td colspan="3" style="text-align: right;">Total Revenue:</td>
                        <td>$<?php echo number_format($total_rev, 2); ?></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

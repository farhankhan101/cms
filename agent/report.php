<?php
// Agent report is similar to admin but forced city filter
$page_title = "Branch Report";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(['agent']);

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get Agent City
$stmt = $db->prepare("SELECT city_id FROM agents WHERE user_id = ?");
$stmt->execute([$user_id]);
$agent_city_id = $stmt->fetchColumn();

// Fetch Cities for Filter (Restricted to Agent's own city)
$stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
$stmt->execute([$agent_city_id]);
$cities = $stmt->fetchAll();

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$to_city_id = $_GET['to_city_id'] ?? $agent_city_id;

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="branch_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Tracking No', 'Sender', 'Receiver', 'From', 'To', 'Weight', 'Amount', 'Status', 'Date']);
    
    $sql = "SELECT s.tracking_no, s.sender_name, s.receiver_name, fc.city_name as from_city, tc.city_name as to_city, s.weight, s.amount, s.status, s.booked_at 
            FROM shipments s
            LEFT JOIN cities fc ON s.from_city_id = fc.id
            LEFT JOIN cities tc ON s.to_city_id = tc.id
            WHERE s.to_city_id = ? AND DATE(s.booked_at) BETWEEN ? AND ?";
    
    $params = [$to_city_id, $start_date, $end_date];

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Fetch Data for Preview
$sql = "SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
        FROM shipments s
        LEFT JOIN cities fc ON s.from_city_id = fc.id
        LEFT JOIN cities tc ON s.to_city_id = tc.id
        WHERE s.to_city_id = ? AND DATE(s.booked_at) BETWEEN ? AND ?";

$params = [$to_city_id, $start_date, $end_date];

$stmt = $db->prepare($sql);
$stmt->execute($params);
$report_data = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-title">Filter Branch Report</div>
    <form action="report.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: flex-end;">
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
        </div>
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 5px;">My Branch City (Destination)</label>
            <select name="to_city_id" class="form-control" style="width: 100%; padding: 8px; border: 1.5px solid #ddd; border-radius: 8px;">
                <?php foreach ($cities as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $to_city_id == $c['id'] ? 'selected' : ''; ?>><?php echo sanitize($c['city_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Preview</button>
            <button type="submit" name="export" value="csv" class="btn btn-secondary"><i class="fas fa-file-csv"></i> Export CSV</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title">Branch Performance Preview</div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Tracking</th>
                    <th>Route</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report_data)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px;">No branch data found.</td></tr>
                <?php else: ?>
                    <?php foreach ($report_data as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['booked_at'], 'M d'); ?></td>
                            <td style="font-weight: 600;">#<?php echo sanitize($row['tracking_no']); ?></td>
                            <td style="font-size: 12px;"><?php echo sanitize($row['from_city']); ?> &rarr; <?php echo sanitize($row['to_city']); ?></td>
                            <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

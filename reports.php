<?php
require_once 'session.php';
require_login();

if (!is_admin()) {
    header("Location: booking.php");
    exit();
}

// Handle download/export
if (isset($_GET['download'])) {
    $export_type = $_GET['download'] ?? '';
    $format = $_GET['format'] ?? 'csv';
    
    if ($export_type == 'bookings') {
        $bookings = get_all_bookings();
        
        if ($format == 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="bookings_' . date('Y-m-d_H-i-s') . '.csv"');
            
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['ID', 'User', 'Email', 'Facility', 'Date', 'Time', 'Purpose', 'Status', 'Created']);
            
            foreach ($bookings as $booking) {
                fputcsv($fp, [
                    $booking['booking_id'],
                    $booking['user_name'],
                    $booking['email'],
                    $booking['facility_name'],
                    $booking['booking_date'],
                    $booking['time_slot'],
                    $booking['purpose'],
                    $booking['status'],
                    $booking['created_at']
                ]);
            }
            fclose($fp);
            exit;
        }
    }
}

// Get date range for reports
$report_type = $_GET['report_type'] ?? 'monthly';
$report_year = (int)($_GET['report_year'] ?? date('Y'));
$report_month = (int)($_GET['report_month'] ?? date('m'));

// Calculate date range
if ($report_type == 'daily') {
    $report_date = $_GET['report_date'] ?? date('Y-m-d');
    $date_start = $report_date;
    $date_end = $report_date;
} elseif ($report_type == 'weekly') {
    $week_of = $_GET['week_of'] ?? date('Y-W');
    $date_start = date('Y-m-d', strtotime($week_of . '-1'));
    $date_end = date('Y-m-d', strtotime($week_of . '-0'));
} else { // monthly
    $date_start = date('Y-m-01', mktime(0, 0, 0, $report_month, 1, $report_year));
    $date_end = date('Y-m-t', mktime(0, 0, 0, $report_month, 1, $report_year));
}

// Get bookings for report
global $conn;
$date_start = mysqli_real_escape_string($conn, $date_start);
$date_end = mysqli_real_escape_string($conn, $date_end);
$query = "SELECT b.*, u.name, u.email, f.facility_name FROM bookings b
          JOIN users u ON b.user_id = u.user_id
          JOIN facilities f ON b.facility_id = f.facility_id
          WHERE b.booking_date >= '$date_start' AND b.booking_date <= '$date_end'
          ORDER BY b.booking_date DESC";
$result = mysqli_query($conn, $query);
$report_bookings = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM bookings WHERE booking_date >= '$date_start' AND booking_date <= '$date_end'";
$stats_result = mysqli_query($conn, $stats_query);
$report_stats = mysqli_fetch_assoc($stats_result);

// Facility wise stats
$facility_query = "SELECT f.facility_name, COUNT(*) as count
                   FROM bookings b
                   JOIN facilities f ON b.facility_id = f.facility_id
                   WHERE b.booking_date >= '$date_start' AND b.booking_date <= '$date_end'
                   AND b.status IN ('Approved', 'Pending')
                   GROUP BY f.facility_id
                   ORDER BY count DESC";
$facility_result = mysqli_query($conn, $facility_query);
$facility_stats = mysqli_fetch_all($facility_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Advanced Features - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .report-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .report-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        body.dark-mode .report-card {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(148, 163, 184, 0.2);
        }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: rgba(99, 102, 241, 0.1); border-left: 4px solid #6366f1; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-box .number { font-size: 28px; font-weight: bold; color: #6366f1; }
        .stat-box .label { color: #64748b; font-size: 12px; margin-top: 5px; }
        .export-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0; }
        .export-btn { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <a href="admin.php" class="nav-brand">📅 Reports</a>
            <ul class="nav-links">
                <li><a href="admin.php">← Back to Admin</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn">🌙</button></li>
            </ul>
        </div>
    </nav>

    <div class="report-container">
        <h1>📊 Reports & Advanced Features</h1>

        <!-- Report Selector -->
        <div class="report-card">
            <h2>Generate Report</h2>
            <form method="GET" action="reports.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div>
                    <label>Report Type</label>
                    <select name="report_type" onchange="this.form.submit()" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo $report_type == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    </select>
                </div>
                <?php if ($report_type == 'daily'): ?>
                    <div>
                        <label>Select Date</label>
                        <input type="date" name="report_date" value="<?php echo $_GET['report_date'] ?? date('Y-m-d'); ?>" onchange="this.form.submit()" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                <?php elseif ($report_type == 'weekly'): ?>
                    <div>
                        <label>Week Of</label>
                        <input type="week" name="week_of" value="<?php echo $_GET['week_of'] ?? date('Y-W'); ?>" onchange="this.form.submit()" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                <?php else: ?>
                    <div>
                        <label>Month</label>
                        <select name="report_month" onchange="this.form.submit()" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $report_month == $m ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label>Year</label>
                        <input type="number" name="report_year" value="<?php echo $report_year; ?>" min="2020" max="2099" onchange="this.form.submit()" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Statistics -->
        <div class="report-card">
            <h2>📈 Report Summary</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="label">Total Bookings</div>
                    <div class="number"><?php echo $report_stats['total'] ?? 0; ?></div>
                </div>
                <div class="stat-box" style="border-left-color: #10b981; background: rgba(16, 185, 129, 0.1);">
                    <div class="label">Approved</div>
                    <div class="number" style="color: #10b981;"><?php echo $report_stats['approved'] ?? 0; ?></div>
                </div>
                <div class="stat-box" style="border-left-color: #f59e0b; background: rgba(245, 158, 11, 0.1);">
                    <div class="label">Pending</div>
                    <div class="number" style="color: #f59e0b;"><?php echo $report_stats['pending'] ?? 0; ?></div>
                </div>
                <div class="stat-box" style="border-left-color: #ef4444; background: rgba(239, 68, 68, 0.1);">
                    <div class="label">Rejected</div>
                    <div class="number" style="color: #ef4444;"><?php echo $report_stats['rejected'] ?? 0; ?></div>
                </div>
            </div>

            <!-- Facility Stats -->
            <h3 style="margin-top: 30px;">Facility Usage</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Facility</th>
                        <th>Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facility_stats as $fac): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fac['facility_name']); ?></td>
                            <td><strong><?php echo $fac['count']; ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Export Options -->
            <div class="export-buttons">
                <a href="?download=bookings&format=csv&report_type=<?php echo $report_type; ?>&report_date=<?php echo $_GET['report_date'] ?? ''; ?>&report_month=<?php echo $report_month; ?>&report_year=<?php echo $report_year; ?>" class="export-btn">📥 Export as CSV</a>
                <button class="export-btn" onclick="window.print()">🖨️ Print Report</button>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="report-card">
            <h2>Booking Details</h2>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['booking_id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['facility_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo $booking['time_slot']; ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($booking['status']); ?>"><?php echo $booking['status']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Online Booking System.</p>
    </footer>

    <button class="dark-mode-btn" onclick="toggleDarkMode()">🌙</button>
    <script src="js/script.js"></script>
</body>
</html>

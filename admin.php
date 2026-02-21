<?php
require_once 'session.php';
require_login();

if (!is_admin()) {
    header("Location: booking.php");
    exit();
}

// Handle logout first (before any output)
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    logout_user();
}

$message = '';
$error = '';
$filter_status = $_GET['status'] ?? 'all';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($action == 'approve') {
        if (update_booking_status($booking_id, 'Approved')) {
            $message = 'Booking approved successfully';
        } else {
            $error = 'Error approving booking';
        }
    } elseif ($action == 'reject') {
        if (update_booking_status($booking_id, 'Rejected')) {
            $message = 'Booking rejected successfully';
        } else {
            $error = 'Error rejecting booking';
        }
    }
}

// Get statistics
$stats = get_booking_stats();
$all_bookings = get_all_bookings($filter_status == 'all' ? null : $filter_status);
$facilities = get_facilities();

// Get user count
$user_count_query = "SELECT COUNT(*) as count FROM users";
$user_count_result = mysqli_query($GLOBALS['conn'], $user_count_query);
$user_count = mysqli_fetch_assoc($user_count_result)['count'];

// Get facility bookings
$facility_stats = [];
foreach ($facilities as $facility) {
    $query = "SELECT COUNT(*) as count FROM bookings WHERE facility_id = " . $facility['facility_id'] . " AND status = 'Approved'";
    $result = mysqli_query($GLOBALS['conn'], $query);
    $facility_stats[$facility['facility_id']] = mysqli_fetch_assoc($result)['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Booking System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="admin.php" class="nav-brand">📅 Admin Dashboard</a>
            <ul class="nav-links">
                <li>Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></li>
                <li><a href="admin.php">Dashboard</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
                <li><a href="index.php?logout=true" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Administrator Dashboard</h1>
            <p>Manage bookings, facilities, and user accounts</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="dashboard-grid">
            <div class="stat-card" style="border-left-color: #3498db;">
                <h3>Total Bookings</h3>
                <div class="number"><?php echo $stats['total_bookings'] ?? 0; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #27ae60;">
                <h3>Approved</h3>
                <div class="number"><?php echo $stats['approved'] ?? 0; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #f39c12;">
                <h3>Pending</h3>
                <div class="number"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #e74c3c;">
                <h3>Rejected</h3>
                <div class="number"><?php echo $stats['rejected'] ?? 0; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #95a5a6;">
                <h3>Total Users</h3>
                <div class="number"><?php echo $user_count; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #9b59b6;">
                <h3>Facilities</h3>
                <div class="number"><?php echo count($facilities); ?></div>
            </div>
        </div>

        <!-- Facility Bookings -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-body">
            <h2 style="margin-bottom: 20px;">Facility Booking Summary</h2>
            <div class="dashboard-grid">
                <?php foreach ($facilities as $facility): ?>
                    <div class="stat-card" style="border-left-color: #3498db;">
                        <h3><?php echo htmlspecialchars($facility['facility_name']); ?></h3>
                        <div class="number"><?php echo $facility_stats[$facility['facility_id']] ?? 0; ?></div>
                        <p style="color: #7f8c8d; font-size: 12px; margin-top: 10px;">Confirmed Bookings</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bookings Management -->
        <div class="card">
            <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Booking Management</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="admin.php?status=all" class="btn btn-primary btn-sm <?php echo $filter_status == 'all' ? 'active' : ''; ?>" style="<?php echo $filter_status == 'all' ? 'opacity: 1;' : 'opacity: 0.7;'; ?>">All</a>
                    <a href="admin.php?status=Pending" class="btn btn-warning btn-sm <?php echo $filter_status == 'Pending' ? 'active' : ''; ?>" style="<?php echo $filter_status == 'Pending' ? 'opacity: 1;' : 'opacity: 0.7;'; ?>">Pending</a>
                    <a href="admin.php?status=Approved" class="btn btn-success btn-sm <?php echo $filter_status == 'Approved' ? 'active' : ''; ?>" style="<?php echo $filter_status == 'Approved' ? 'opacity: 1;' : 'opacity: 0.7;'; ?>">Approved</a>
                    <a href="admin.php?status=Rejected" class="btn btn-danger btn-sm <?php echo $filter_status == 'Rejected' ? 'active' : ''; ?>" style="<?php echo $filter_status == 'Rejected' ? 'opacity: 1;' : 'opacity: 0.7;'; ?>">Rejected</a>
                </div>
            </div>

            <?php if (empty($all_bookings)): ?>
                <p class="text-muted" style="text-align: center; padding: 40px 0;">
                    No bookings found.
                </p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['facility_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                    <td><?php echo $booking['time_slot']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['purpose'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo $booking['status']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($booking['status'] == 'Pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- Reports Section -->
        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px;">
            <div class="card">
                <div class="card-body">
                <h3 style="margin-bottom: 15px;">📊 Quick Actions</h3>
                <button class="btn btn-primary btn-block" style="margin-bottom: 10px;" onclick="alert('Report generation feature coming soon!')">Generate Daily Report</button>
                <button class="btn btn-primary btn-block" style="margin-bottom: 10px;" onclick="alert('Report generation feature coming soon!')">Generate Weekly Report</button>
                <button class="btn btn-primary btn-block" onclick="alert('Report generation feature coming soon!')">Generate Monthly Report</button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                <h3 style="margin-bottom: 15px;">ℹ️ System Info</h3>
                <p class="text-muted" style="margin: 8px 0;"><strong>Current Time:</strong> <?php echo date('h:i A'); ?></p>
                <p class="text-muted" style="margin: 8px 0;"><strong>Today:</strong> <?php echo date('l, M d, Y'); ?></p>
                <p class="text-muted" style="margin: 8px 0;"><strong>Total Facilities:</strong> <?php echo count($facilities); ?></p>
                <p class="text-muted" style="margin: 8px 0;"><strong>Active Users:</strong> <?php echo $user_count; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Online Booking System. All rights reserved.</p>
        <p>School Facilities Management</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>

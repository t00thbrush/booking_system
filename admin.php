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

// Search/filter parameters
$search_facility = $_GET['search_facility'] ?? '';
$search_user = $_GET['search_user'] ?? '';
$search_date_from = $_GET['search_date_from'] ?? '';
$search_date_to = $_GET['search_date_to'] ?? '';
$search_text = $_GET['search_text'] ?? '';

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
$facilities = get_facilities();

// Get filtered bookings
if (!empty($search_facility) || !empty($search_user) || !empty($search_date_from) || !empty($search_date_to) || !empty($search_text)) {
    $all_bookings = search_all_bookings(
        !empty($search_facility) ? $search_facility : null,
        $filter_status == 'all' ? null : $filter_status,
        !empty($search_user) ? $search_user : null,
        !empty($search_date_from) ? $search_date_from : null,
        !empty($search_date_to) ? $search_date_to : null,
        !empty($search_text) ? $search_text : null
    );
} else {
    $all_bookings = get_all_bookings($filter_status == 'all' ? null : $filter_status);
}

// Get user count
$user_count_query = "SELECT COUNT(*) as count FROM users";
$user_count_result = mysqli_query($GLOBALS['conn'], $user_count_query);
$user_count = mysqli_fetch_assoc($user_count_result)['count'];

// Get all users for dropdown
$all_users = get_all_users();

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
                <li><a href="admin_db.php">Database</a></li>
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
            <h2 style="margin-bottom: 20px;">Booking Management</h2>

            <!-- Advanced Search & Filter Form -->
            <div style="background: rgba(99, 102, 241, 0.05); padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 3px solid #6366f1;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">🔍 Advanced Search & Filter</h3>
                <form method="GET" action="admin.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px;">Facility</label>
                        <select name="search_facility" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                            <option value="">All Facilities</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['facility_id']; ?>" <?php echo $search_facility == $facility['facility_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($facility['facility_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px;">User</label>
                        <select name="search_user" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                            <option value="">All Users</option>
                            <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>" <?php echo $search_user == $user['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px;">Status</label>
                        <select name="status" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="Pending" <?php echo $filter_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $filter_status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $filter_status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Cancelled" <?php echo $filter_status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px;">From Date</label>
                        <input type="date" name="search_date_from" value="<?php echo htmlspecialchars($search_date_from); ?>" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px;">To Date</label>
                        <input type="date" name="search_date_to" value="<?php echo htmlspecialchars($search_date_to); ?>" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px;">Search Text</label>
                        <input type="text" name="search_text" placeholder="Search purpose, email..." value="<?php echo htmlspecialchars($search_text); ?>" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; flex: 1;">🔍 Search</button>
                        <a href="admin.php" style="background: #e2e8f0; color: #1e293b; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; text-align: center;">Reset</a>
                    </div>
                </form>
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

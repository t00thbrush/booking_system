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
$active_tab = $_GET['tab'] ?? 'bookings';

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
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $facility_id = (int)($_POST['facility_id'] ?? 0);

    // Booking actions
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
    // Teacher approval
    elseif ($action == 'approve_teacher') {
        if (approve_user($teacher_id)) {
            $message = 'Teacher account approved successfully';
        } else {
            $error = 'Error approving teacher';
        }
    }
    // Teacher facility assignment
    elseif ($action == 'assign_facility') {
        if (assign_facility_to_teacher($teacher_id, $facility_id)) {
            $message = 'Facility assigned to teacher successfully';
        } else {
            $error = 'Error assigning facility to teacher';
        }
    }
    // Teacher facility revocation
    elseif ($action == 'revoke_facility') {
        if (revoke_facility_from_teacher($teacher_id, $facility_id)) {
            $message = 'Facility access revoked successfully';
        } else {
            $error = 'Error revoking facility access';
        }
    }
}

// Get statistics
$stats = get_booking_stats();
$facilities = get_facilities();
$pending_teachers = get_pending_teachers();
$approved_teachers = get_approved_teachers();

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
    <title>Admin Dashboard - Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
        <!-- Animated Water Bubbles Background -->
    <div class="bubble-container">
        <div class="bubble bubble-1"></div>
        <div class="bubble bubble-2"></div>
        <div class="bubble bubble-3"></div>
        <div class="bubble bubble-4"></div>
        <div class="bubble bubble-5"></div>
        <div class="bubble bubble-6"></div>
        <div class="bubble bubble-7"></div>
        <div class="bubble bubble-8"></div>
    </div>
    <style>
        .tab-navigation {
            display: flex;
            gap: 1rem;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            position: relative;
            bottom: -1rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tab-btn:hover {
        
            color: var(--primary);
        }
        .tab-btn.active {
        
            color: var(--primary);
            border-color: var(--primary);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .status-approved {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .status-cancelled {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .pagination button, .pagination a {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .pagination button:hover, .pagination a:hover {
            background: rgba(163, 255, 204, 0.1);
            border-color: var(--primary);
        }
        .pagination .active {
            background: var(--primary);
            color: #000;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="admin.php" class="nav-brand">📊 Admin Dashboard</a>
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
            <p>Manage bookings, facilities, teachers, and user accounts for 5000+ students and 300+ teachers</p>
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

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stat-label">Total Bookings</div>
                <div class="stat-value"><?php echo $stats['total_bookings'] ?? 0; ?></div>
            </div>
            <div class="stats-card">
                <div class="stat-label">Pending Approvals</div>
                <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stats-card">
                <div class="stat-label">Approved</div>
                <div class="stat-value"><?php echo $stats['approved'] ?? 0; ?></div>
            </div>
            <div class="stats-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo $user_count; ?></div>
            </div>
            <div class="stats-card">
                <div class="stat-label">Active Facilities</div>
                <div class="stat-value"><?php echo count($facilities); ?></div>
            </div>
            <div class="stats-card">
                <div class="stat-label">Pending Teachers</div>
                <div class="stat-value"><?php echo count($pending_teachers); ?></div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn <?php echo $active_tab === 'bookings' ? 'active' : ''; ?>" onclick="switchTab(event, 'bookings')">📅 Bookings</button>
            <button class="tab-btn <?php echo $active_tab === 'teachers' ? 'active' : ''; ?>" onclick="switchTab(event, 'teachers')">👨‍🏫 Teachers</button>
            <button class="tab-btn <?php echo $active_tab === 'facilities' ? 'active' : ''; ?>" onclick="switchTab(event, 'facilities')">🏛️ Facilities</button>
            <button class="tab-btn <?php echo $active_tab === 'summary' ? 'active' : ''; ?>" onclick="switchTab(event, 'summary')">📊 Summary</button>
        </div>

        <!-- Bookings Tab -->
        <div id="bookings-tab" class="tab-content <?php echo $active_tab === 'bookings' || $active_tab === '' ? 'active' : ''; ?>">
            <div class="admin-section">
                <h2 class="section-title">Booking Management</h2>

                <!-- Advanced Search & Filter Form -->
                <div class="card">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px;">🔍 Advanced Search & Filter</h3>
                    <form method="GET" action="admin.php" class="search-filters">
                        <select name="search_facility">
                            <option value="">All Facilities</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['facility_id']; ?>" <?php echo $search_facility == $facility['facility_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($facility['facility_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="status">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="Pending" <?php echo $filter_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $filter_status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $filter_status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Cancelled" <?php echo $filter_status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>

                        <input type="date" name="search_date_from" value="<?php echo htmlspecialchars($search_date_from); ?>" placeholder="From Date">
                        <input type="date" name="search_date_to" value="<?php echo htmlspecialchars($search_date_to); ?>" placeholder="To Date">
                        <input type="text" name="search_text" placeholder="Search by purpose, email..." value="<?php echo htmlspecialchars($search_text); ?>">
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary btn-sm">🔍 Search</button>
                            <a href="admin.php?tab=bookings" class="btn btn-sm" style="background: rgba(255, 255, 255, 0.1); color: var(--text-secondary); padding: 1rem 1rem;">Reset</a>
                        </div>
                    </form>
                </div>

                <br>

                <!-- Bookings Table -->
                <?php if (empty($all_bookings)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <p style="color: var(--text-secondary);">No bookings found.</p>
                    </div>
                <?php else: ?>
                    <div class="card" style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Facility</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['facility_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td><?php echo $booking['time_slot']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                                <?php echo $booking['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($booking['status'] == 'Pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm">✓ Approve</button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger btn-sm">✗ Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: var(--text-light);">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- End Bookings Tab -->

        <!-- Teachers Tab -->
        <div id="teachers-tab" class="tab-content <?php echo $active_tab === 'teachers' ? 'active' : ''; ?>">
            <div class="admin-section">
                <h2 class="section-title">Teacher Management</h2>

                <!-- Pending Teachers -->
                <div class="card">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px;">⏳ Pending Approvals (<?php echo count($pending_teachers); ?>)</h3>
                    <?php if (empty($pending_teachers)): ?>
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No pending teacher approvals.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Applied Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($teacher['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['user_id']; ?>">
                                                    <input type="hidden" name="action" value="approve_teacher">
                                                    <button type="submit" class="btn btn-success btn-sm">✓ Approve</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Approved Teachers -->
                <div class="card" style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px;">✅ Approved Teachers (<?php echo count($approved_teachers); ?>)</h3>
                    <?php if (empty($approved_teachers)): ?>
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No approved teachers yet.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Assigned Facilities</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_teachers as $teacher): ?>
                                        <?php 
                                        $teacher_facilities = get_teacher_facilities($teacher['user_id']);
                                        $facility_names = array_map(function($f) { return $f['facility_name']; }, $teacher_facilities);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td><?php echo !empty($facility_names) ? implode(', ', $facility_names) : 'None'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- End Teachers Tab -->

        <!-- Facilities Tab -->
        <div id="facilities-tab" class="tab-content <?php echo $active_tab === 'facilities' ? 'active' : ''; ?>">
            <div class="admin-section">
                <h2 class="section-title">Facility Management</h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($facilities as $facility): ?>
                        <div class="card">
                            <h3 style="color: var(--primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 1px;">
                                <?php echo htmlspecialchars($facility['facility_name']); ?>
                            </h3>
                            <div style="margin-bottom: 1rem;">
                                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                                    <strong>Capacity:</strong> <?php echo $facility['capacity']; ?> people
                                </p>
                                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($facility['location'] ?? 'N/A'); ?>
                                </p>
                                <p style="color: var(--primary); font-weight: 600;">
                                    📅 <?php echo $facility_stats[$facility['facility_id']] ?? 0; ?> Confirmed Bookings
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div><!-- End Facilities Tab -->
        
        <!-- Summary Tab -->
        <div id="summary-tab" class="tab-content <?php echo $active_tab === 'summary' ? 'active' : ''; ?>">
            <div class="admin-section">
                <h2 class="section-title">Facility Booking Summary</h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($facilities as $facility): ?>
                        <div class="card">
                            <h3 style="color: var(--primary); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 1px;">
                                <?php echo htmlspecialchars($facility['facility_name']); ?>
                            </h3>
                            <div style="text-align: center;">
                                <div class="stat-value" style="color: var(--primary); margin-bottom: 0.5rem;">
                                    <?php echo $facility_stats[$facility['facility_id']] ?? 0; ?>
                                </div>
                                <p style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Confirmed Bookings
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div><!-- End Summary Tab -->
    </div>

    <script src="js/script.js"></script>
    <script>
        function switchTab(event, tabName) {
            event.preventDefault();
            
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Update URL
            window.history.pushState(null, '', `admin.php?tab=${tabName}`);
        }
    </script>
</body>
</html>

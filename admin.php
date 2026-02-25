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
    <title>Admin Dashboard - Online Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tab-navigation {
            display: flex;
            gap: 1rem;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
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
            margin-bottom: -1rem;
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
    </style>
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
            <p>Manage bookings, facilities, teachers, and user accounts</p>
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

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn <?php echo $active_tab === 'bookings' ? 'active' : ''; ?>" onclick="switchTab(event, 'bookings')">📅 Bookings</button>
            <button class="tab-btn <?php echo $active_tab === 'teachers' ? 'active' : ''; ?>" onclick="switchTab(event, 'teachers')">👨‍🏫 Teachers <?php echo count($pending_teachers) > 0 ? '(' . count($pending_teachers) . ')' : ''; ?></button>
            <button class="tab-btn <?php echo $active_tab === 'facilities' ? 'active' : ''; ?>" onclick="switchTab(event, 'facilities')">🏛️ Facilities</button>
        </div>

        <!-- Bookings Tab -->
        <div id="bookings-tab" class="tab-content <?php echo $active_tab === 'bookings' || $active_tab === '' ? 'active' : ''; ?>">

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
        </div><!-- End Bookings Tab -->

        <!-- Teachers Tab -->
        <div id="teachers-tab" class="tab-content <?php echo $active_tab === 'teachers' ? 'active' : ''; ?>">
            <!-- Pending Teachers Section -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-body">
                    <h2 style="margin-bottom: 20px;">⏳ Pending Teacher Approvals (<?php echo count($pending_teachers); ?>)</h2>
                    <?php if (empty($pending_teachers)): ?>
                        <p class="text-muted" style="text-align: center; padding: 40px 0;">No pending teacher approvals.</p>
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
                                            <td style="text-align: right;">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['user_id']; ?>">
                                                    <input type="hidden" name="action" value="approve_teacher">
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approved Teachers Section -->
            <div class="card">
                <div class="card-body">
                    <h2 style="margin-bottom: 20px;">✅ Approved Teachers (<?php echo count($approved_teachers); ?>)</h2>
                    <?php if (empty($approved_teachers)): ?>
                        <p class="text-muted" style="text-align: center; padding: 40px 0;">No approved teachers yet.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Assigned Facilities</th>
                                        <th>Manage</th>
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
                                            <td>
                                                <?php if (empty($facility_names)): ?>
                                                    <span class="text-muted">No facilities assigned</span>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars(implode(', ', $facility_names)); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <button class="btn btn-primary btn-sm" onclick="showFacilityModal(<?php echo $teacher['user_id']; ?>, '<?php echo htmlspecialchars($teacher['name']); ?>')">Edit Access</button>
                                            </td>
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
            <div class="card">
                <div class="card-body">
                    <h2 style="margin-bottom: 20px;">🏛️ School Facilities</h2>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Facility Name</th>
                                    <th>Capacity</th>
                                    <th>Confirmed Bookings</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($facilities as $facility): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($facility['facility_name']); ?></strong></td>
                                        <td><?php echo $facility['capacity']; ?> people</td>
                                        <td><?php echo $facility_stats[$facility['facility_id']] ?? 0; ?></td>
                                        <td><?php echo htmlspecialchars(substr($facility['description'] ?? '', 0, 60)); ?>...</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- End Facilities Tab -->

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

    <!-- Facility Assignment Modal -->
    <div id="facilityModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <h2 id="modalTitle" style="margin-bottom: 1.5rem; color: #1e293b;">Assign Facilities</h2>
            <form method="POST" id="facilityForm" style="display: flex; flex-direction: column; gap: 1rem;">
                <input type="hidden" name="teacher_id" id="modalTeacherId" value="">
                
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Select Facility</label>
                    <select name="facility_id" id="facilitySelect" style="width: 100%; padding: 0.875rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                        <option value="">-- Choose a facility --</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?php echo $facility['facility_id']; ?>"><?php echo htmlspecialchars($facility['facility_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger" onclick="closeFacilityModal()">Close</button>
                    <button type="submit" class="btn btn-primary" name="action" value="assign_facility">Assign Facility</button>
                </div>
            </form>

            <div id="assignedFacilitiesList" style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                <h3 style="margin-bottom: 1rem; color: #1e293b;">Currently Assigned Facilities</h3>
                <div id="assignedFacilitiesContent"></div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function switchTab(event, tabName) {
            event.preventDefault();
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show the selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Update URL parameter
            window.history.pushState(null, '', '?tab=' + tabName);
        }

        function showFacilityModal(teacherId, teacherName) {
            document.getElementById('modalTeacherId').value = teacherId;
            document.getElementById('modalTitle').textContent = 'Manage Facilities for ' + teacherName;
            document.getElementById('facilityModal').style.display = 'flex';
            
            // Load assigned facilities via AJAX
            loadAssignedFacilities(teacherId);
        }

        function closeFacilityModal() {
            document.getElementById('facilityModal').style.display = 'none';
        }

        function loadAssignedFacilities(teacherId) {
            // Display placeholder
            document.getElementById('assignedFacilitiesContent').innerHTML = '<p class="text-muted">Loading...</p>';
            
            // In a real application, you would fetch this via AJAX
            // For now, we'll just show a message
            document.getElementById('assignedFacilitiesContent').innerHTML = '<p class="text-muted">Refresh the page to see updated facility assignments</p>';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('facilityModal');
            if (event.target == modal) {
                closeFacilityModal();
            }
        }
    </script>
</body>
</html>

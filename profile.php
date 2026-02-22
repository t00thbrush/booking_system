<?php
require_once 'session.php';
require_login();

// Handle logout first (before any output)
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    logout_user();
}

$user = get_user($_SESSION['user_id']);
$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'profile';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name)) {
            $error = 'Name cannot be empty';
        } elseif (empty($email)) {
            $error = 'Email cannot be empty';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            // Check if email already exists (for other users)
            global $conn;
            $email_check = mysqli_real_escape_string($conn, $email);
            $query = "SELECT user_id FROM users WHERE email = '$email_check' AND user_id != " . (int)$_SESSION['user_id'];
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0) {
                $error = 'This email is already used by another account';
            } else {
                // Update profile
                $name = mysqli_real_escape_string($conn, $name);
                $query = "UPDATE users SET name = '$name', email = '$email_check' WHERE user_id = " . (int)$_SESSION['user_id'];
                
                if (mysqli_query($conn, $query)) {
                    $_SESSION['name'] = $name;
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $message = 'Profile updated successfully';
                    $tab = 'profile';
                } else {
                    $error = 'Error updating profile';
                }
            }
        }
    } elseif ($action == 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirmation do not match';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            // Update password
            global $conn;
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = '$hashed_password' WHERE user_id = " . (int)$_SESSION['user_id'];
            
            if (mysqli_query($conn, $query)) {
                $message = 'Password changed successfully';
                $tab = 'security';
            } else {
                $error = 'Error changing password';
            }
        }
    }
}

// Get user bookings for statistics
$user_bookings = get_user_bookings($_SESSION['user_id']);
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$cancelled_count = 0;

foreach ($user_bookings as $booking) {
    if ($booking['status'] == 'Pending') $pending_count++;
    elseif ($booking['status'] == 'Approved') $approved_count++;
    elseif ($booking['status'] == 'Rejected') $rejected_count++;
    elseif ($booking['status'] == 'Cancelled') $cancelled_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Online Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 15px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            flex-wrap: wrap;
        }

        body.dark-mode .profile-tabs {
            border-bottom-color: #334155;
        }

        .tab-link {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .tab-link:hover {
            color: #1e293b;
        }

        body.dark-mode .tab-link:hover {
            color: #f1f5f9;
        }

        .tab-link.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
        }

        body.dark-mode .form-section {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(148, 163, 184, 0.2);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 14px;
        }

        body.dark-mode .form-group label {
            color: #f1f5f9;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #1e293b;
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group textarea {
            background: #1e293b;
            color: #f1f5f9;
            border-color: #475569;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .booking-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: rgba(99, 102, 241, 0.1);
            border-left: 4px solid #6366f1;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        body.dark-mode .stat-box {
            background: rgba(99, 102, 241, 0.2);
        }

        .stat-box .number {
            font-size: 32px;
            font-weight: bold;
            color: #6366f1;
            margin: 10px 0;
        }

        .stat-box .label {
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 600;
        }

        body.dark-mode .stat-box .label {
            color: #cbd5e1;
        }

        .info-list {
            list-style: none;
            padding: 0;
        }

        .info-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
        }

        body.dark-mode .info-list li {
            border-bottom-color: #334155;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
        }

        body.dark-mode .info-label {
            color: #cbd5e1;
        }

        .info-value {
            color: #1e293b;
            font-weight: 500;
        }

        body.dark-mode .info-value {
            color: #f1f5f9;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-group button {
            flex: 1;
        }

        .danger-zone {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .danger-zone h3 {
            color: #dc2626;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="booking.php" class="nav-brand">📅 Booking System</a>
            <ul class="nav-links">
                <li>Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></li>
                <li><a href="booking.php">Book</a></li>
                <li><a href="profile.php" style="color: #6366f1; font-weight: 600;">Profile</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
                <li><a href="index.php?logout=true" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">👤</div>
        <h1><?php echo htmlspecialchars($user['name']); ?></h1>
        <p style="margin: 5px 0; opacity: 0.9;"><?php echo htmlspecialchars($user['email']); ?></p>
        <p style="margin: 5px 0; opacity: 0.8; font-size: 12px;">Member since <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?></p>
    </div>

    <!-- Main Container -->
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

        <!-- Booking Statistics -->
        <div class="booking-stats">
            <div class="stat-box">
                <div class="label">Total Bookings</div>
                <div class="number"><?php echo count($user_bookings); ?></div>
            </div>
            <div class="stat-box" style="border-left-color: #10b981; background: rgba(16, 185, 129, 0.1);">
                <div class="label">Approved</div>
                <div class="number" style="color: #10b981;"><?php echo $approved_count; ?></div>
            </div>
            <div class="stat-box" style="border-left-color: #f59e0b; background: rgba(245, 158, 11, 0.1);">
                <div class="label">Pending</div>
                <div class="number" style="color: #f59e0b;"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-box" style="border-left-color: #ef4444; background: rgba(239, 68, 68, 0.1);">
                <div class="label">Rejected</div>
                <div class="number" style="color: #ef4444;"><?php echo $rejected_count; ?></div>
            </div>
        </div>

        <!-- Profile Tabs -->
        <div class="profile-tabs">
            <button class="tab-link <?php echo $tab == 'profile' ? 'active' : ''; ?>" onclick="switchTab('profile')">
                📋 Profile Information
            </button>
            <button class="tab-link <?php echo $tab == 'security' ? 'active' : ''; ?>" onclick="switchTab('security')">
                🔒 Security & Password
            </button>
            <button class="tab-link <?php echo $tab == 'activity' ? 'active' : ''; ?>" onclick="switchTab('activity')">
                📊 Activity Overview
            </button>
        </div>

        <!-- Profile Tab -->
        <div id="profile-tab" class="tab-content <?php echo $tab == 'profile' ? 'active' : ''; ?>">
            <div class="form-section">
                <h2 style="margin-top: 0;">Personal Information</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Account Role</label>
                            <input type="text" id="role" name="role" value="<?php echo htmlspecialchars($user['role']); ?>" disabled style="background: #f1f5f9; color: #64748b;">
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: #f1f5f9; color: #64748b;">
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">💾 Save Changes</button>
                    </div>
                </form>
            </div>

            <div class="form-section">
                <h2 style="margin-top: 0;">Account Details</h2>
                <ul class="info-list">
                    <li>
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </li>
                    <li>
                        <span class="info-label">Role</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['role']); ?></span>
                    </li>
                    <li>
                        <span class="info-label">Account Status</span>
                        <span class="info-value" style="color: #10b981;">✓ Active</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Security Tab -->
        <div id="security-tab" class="tab-content <?php echo $tab == 'security' ? 'active' : ''; ?>">
            <div class="form-section">
                <h2 style="margin-top: 0;">Change Password</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                    </div>
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">🔄 Update Password</button>
                    </div>
                </form>

                <div class="danger-zone">
                    <h3>🔐 Account Security Tips</h3>
                    <ul style="color: #64748b; margin: 0; padding-left: 20px;">
                        <li>Use a strong password with mixed characters</li>
                        <li>Never share your password with others</li>
                        <li>Change your password regularly (every 3 months)</li>
                        <li>Log out from devices you don't use often</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Activity Tab -->
        <div id="activity-tab" class="tab-content <?php echo $tab == 'activity' ? 'active' : ''; ?>">
            <div class="form-section">
                <h2 style="margin-top: 0;">Your Booking Activity</h2>
                
                <div class="booking-stats">
                    <div class="stat-box">
                        <div class="label">Total Bookings</div>
                        <div class="number"><?php echo count($user_bookings); ?></div>
                    </div>
                    <div class="stat-box" style="border-left-color: #10b981; background: rgba(16, 185, 129, 0.1);">
                        <div class="label">✓ Approved</div>
                        <div class="number" style="color: #10b981;"><?php echo $approved_count; ?></div>
                    </div>
                    <div class="stat-box" style="border-left-color: #f59e0b; background: rgba(245, 158, 11, 0.1);">
                        <div class="label">⏳ Pending</div>
                        <div class="number" style="color: #f59e0b;"><?php echo $pending_count; ?></div>
                    </div>
                    <div class="stat-box" style="border-left-color: #ef4444; background: rgba(239, 68, 68, 0.1);">
                        <div class="label">✗ Rejected</div>
                        <div class="number" style="color: #ef4444;"><?php echo $rejected_count; ?></div>
                    </div>
                </div>

                <h3 style="margin-top: 30px;">Recent Bookings</h3>
                <?php if (empty($user_bookings)): ?>
                    <p class="text-muted" style="text-align: center; padding: 40px 0;">
                        No bookings yet. <a href="booking.php" style="color: #6366f1;">Start booking now!</a>
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Facility</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($user_bookings, 0, 10) as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['facility_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td><?php echo $booking['time_slot']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                                <?php echo $booking['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Online Booking System. All rights reserved.</p>
        <p>School Facilities Management</p>
    </footer>

    <button class="dark-mode-btn" onclick="toggleDarkMode()">🌙</button>
    <script src="js/script.js"></script>
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-link').forEach(link => {
                link.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');

            // Update URL
            window.history.replaceState(null, null, '?tab=' + tabName);
        }
    </script>
</body>
</html>

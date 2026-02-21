<?php
require_once 'db.php';
require_once 'session.php';

$check_users = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $check_users);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $user_password = password_hash('user123', PASSWORD_DEFAULT);
    
    $insert_users = "INSERT INTO users (name, username, email, password, role) VALUES
        ('Administrator', 'admin', 'admin@booking.system', '$admin_password', 'Admin'),
        ('John User', 'user1', 'user1@booking.system', '$user_password', 'External'),
        ('Jane Staff', 'staff1', 'staff1@booking.system', '$user_password', 'Staff'),
        ('Bob External', 'user2', 'user2@booking.system', '$user_password', 'External'),
        ('Alice Student', 'user3', 'user3@booking.system', '$user_password', 'External')";
    
    if (mysqli_query($conn, $insert_users)) {
        echo "<h2>✓ Demo users created successfully!</h2>";
    }
}

$check_bookings = "SELECT COUNT(*) as count FROM bookings";
$result = mysqli_query($conn, $check_bookings);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $next_week = date('Y-m-d', strtotime('+7 days'));
    
    $insert_bookings = "INSERT INTO bookings (user_id, facility_id, booking_date, time_slot, status, purpose) VALUES
        (2, 1, '$tomorrow', '09:00', 'Pending', 'Basketball Practice'),
        (2, 2, '$next_week', '14:00', 'Approved', 'Annual Conference'),
        (3, 1, '$tomorrow', '15:00', 'Approved', 'Volleyball Tournament'),
        (4, 2, '$next_week', '10:00', 'Rejected', 'Event Planning'),
        (5, 3, '$next_week', '13:00', 'Pending', 'Study Session')";
    
    if (mysqli_query($conn, $insert_bookings)) {
        echo "<h2>✓ Demo bookings created successfully!</h2>";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Initialization</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-brand">📅 Booking System</a>
        </div>
    </nav>
    
    <div class="page-header">
        <div class="container">
            <h1>System Initialization</h1>
        </div>
    </div>
    
    <div class="container" style="max-width: 600px; margin: 40px auto;">
        <div class="alert alert-success" style="padding: 20px;">
            <h2 style="margin-bottom: 10px;">✓ System Setup Complete!</h2>
            <p style="margin-bottom: 20px;">Database and demo data have been initialized successfully.</p>
            
            <h3 style="margin-top: 30px; margin-bottom: 15px;">Demo Credentials:</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Username</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Admin</td>
                        <td><code>admin</code></td>
                        <td><code>admin123</code></td>
                    </tr>
                    <tr>
                        <td>User</td>
                        <td><code>user1</code></td>
                        <td><code>user123</code></td>
                    </tr>
                    <tr>
                        <td>Staff</td>
                        <td><code>staff1</code></td>
                        <td><code>user123</code></td>
                    </tr>
                </tbody>
            </table>
            
            <br>
            <a href="index.php" class="btn btn-primary btn-block">Go to Login</a>
        </div>
    </div>
</body>
</html>

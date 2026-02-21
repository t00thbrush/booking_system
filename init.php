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
        <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
            <h2 style="color: #155724;">✓ System Setup Complete!</h2>
            <p style="color: #155724;">Database and demo data have been initialized successfully.</p>
            
            <h3 style="margin-top: 30px; color: #155724;">Demo Credentials:</h3>
            <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                <tr style="background-color: rgba(0,0,0,0.05);">
                    <td style="padding: 10px; border-bottom: 1px solid #155724;"><strong>Role</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #155724;"><strong>Username</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #155724;"><strong>Password</strong></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #d4edda;">Admin</td>
                    <td style="padding: 10px; border-bottom: 1px solid #d4edda;"><code>admin</code></td>
                    <td style="padding: 10px; border-bottom: 1px solid #d4edda;"><code>admin123</code></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #d4edda;">User</td>
                    <td style="padding: 10px; border-bottom: 1px solid #d4edda;"><code>user1</code></td>
                    <td style="padding: 10px; border-bottom: 1px solid #d4edda;"><code>user123</code></td>
                </tr>
                <tr>
                    <td style="padding: 10px;">Staff</td>
                    <td style="padding: 10px;"><code>staff1</code></td>
                    <td style="padding: 10px;"><code>user123</code></td>
                </tr>
            </table>
            
            <br>
            <a href="index.php" class="btn btn-primary btn-block">Go to Login</a>
        </div>
    </div>
</body>
</html>

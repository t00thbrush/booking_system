<?php
/* ============================
   EMAIL TESTING & VERIFICATION PAGE
   ============================ */

session_start();
require_once 'session.php';

// Only allow admin access
if (!is_admin()) {
    header("Location: index.php");
    exit();
}

// Get test email log
$email_log = '';
$log_file = '/var/www/html/booking_system/logs/emails.log';
if (file_exists($log_file)) {
    $email_log = file_get_contents($log_file);
}

// Get recent bookings for testing
$recent_bookings = get_all_bookings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings & Testing</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .email-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .email-section {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode .email-section {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(148, 163, 184, 0.2);
        }

        .email-section h2 {
            color: #1e293b;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        body.dark-mode .email-section h2 {
            color: #f1f5f9;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .config-item {
            background: rgba(99, 102, 241, 0.05);
            padding: 15px;
            border-left: 3px solid #6366f1;
            border-radius: 6px;
        }

        body.dark-mode .config-item {
            background: rgba(99, 102, 241, 0.1);
        }

        .config-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        body.dark-mode .config-label {
            color: #cbd5e1;
        }

        .config-value {
            font-family: 'Courier New', monospace;
            color: #1e293b;
            font-weight: 600;
        }

        body.dark-mode .config-value {
            color: #f1f5f9;
        }

        .email-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }

        .status-development {
            background: rgba(59, 130, 246, 0.2);
            color: #1e40af;
        }

        body.dark-mode .status-development {
            background: rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }

        .email-log {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.6;
            margin: 20px 0;
            border: 1px solid #404040;
        }

        .log-entry {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #404040;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .test-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .test-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .test-btn:active {
            transform: translateY(0);
        }

        .clear-log-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .info-alert {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            color: #1e40af;
        }

        body.dark-mode .info-alert {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        .booking-test-list {
            margin: 20px 0;
        }

        .booking-item {
            background: rgba(255, 255, 255, 0.4);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 1fr 150px;
            gap: 15px;
            align-items: center;
        }

        body.dark-mode .booking-item {
            background: rgba(30, 41, 59, 0.4);
        }

        .booking-info {
            color: #64748b;
            font-size: 14px;
        }

        body.dark-mode .booking-info {
            color: #cbd5e1;
        }

        .send-test-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 8px 16px;
            font-size: 13px;
        }

        .email-count {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .no-emails {
            color: #64748b;
            padding: 20px;
            text-align: center;
            font-style: italic;
        }

        body.dark-mode .no-emails {
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">📧 Email Settings & Testing</div>
            <a href="admin.php" class="nav-link">← Back to Admin</a>
        </div>
    </nav>

    <div class="email-container">
        <!-- Configuration Section -->
        <div class="email-section">
            <h2>⚙️ Email Configuration</h2>
            <div class="info-alert">
                <strong>Development Mode:</strong> Emails are currently logged to file instead of being sent. Check the log below to see what would be sent.
            </div>

            <div class="config-grid">
                <div class="config-item">
                    <div class="config-label">Sender Email</div>
                    <div class="config-value">noreply@schoolbooking.local</div>
                </div>
                <div class="config-item">
                    <div class="config-label">Sender Name</div>
                    <div class="config-value">School Booking System</div>
                </div>
                <div class="config-item">
                    <div class="config-label">Admin Email</div>
                    <div class="config-value">admin@schoolbooking.local</div>
                </div>
                <div class="config-item">
                    <div class="config-label">Email Mode</div>
                    <div class="config-value"><span class="email-status status-development">Development</span></div>
                </div>
            </div>

            <h3>📨 Automated Email Triggers</h3>
            <ul style="color: #64748b; line-height: 1.8;">
                <li><strong>✓ Booking Confirmation:</strong> Sent to user when creating a new booking</li>
                <li><strong>✓ Admin Notification:</strong> Sent to admin when new booking is submitted</li>
                <li><strong>✓ Approval Email:</strong> Sent to user when booking is approved</li>
                <li><strong>✓ Rejection Email:</strong> Sent to user when booking is rejected</li>
            </ul>
        </div>

        <!-- Email Log Section -->
        <div class="email-section">
            <h2>📋 Email Log</h2>
            <?php
            if (!empty($email_log)) {
                // Count emails
                $email_count = substr_count($email_log, 'TO:');
                echo '<div class="info-alert">';
                echo '<strong>' . $email_count . '</strong> emails logged so far. Newest entries at bottom.';
                echo '</div>';
                echo '<div class="email-log">';
                // Show only last 20 entries for readability
                $log_lines = explode("\n", trim($email_log));
                $display_lines = array_slice($log_lines, -200);
                echo htmlspecialchars(implode("\n", $display_lines));
                echo '</div>';
            } else {
                echo '<div class="no-emails">No emails logged yet. Create a booking to trigger email notifications.</div>';
            }
            ?>

            <div class="test-buttons">
                <form method="POST" action="email_test.php" style="display: inline;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="test-btn clear-log-btn" onclick="return confirm('Clear email log? This cannot be undone.')">🗑️ Clear Email Log</button>
                </form>
            </div>
        </div>

        <!-- Test Bookings Section -->
        <div class="email-section">
            <h2>🧪 Test Email Trigger</h2>
            <p style="color: #64748b;">Select a booking below to send test emails:</p>

            <div class="booking-test-list">
                <?php
                if (!empty($recent_bookings)) {
                    foreach (array_slice($recent_bookings, 0, 5) as $booking) {
                        echo '<div class="booking-item">';
                        echo '<div class="booking-info">';
                        echo '<strong>' . htmlspecialchars($booking['user_name']) . '</strong> - ';
                        echo htmlspecialchars($booking['facility_name']) . ' | ';
                        echo date('M d, Y', strtotime($booking['booking_date'])) . ' ' . $booking['time_slot'] . ' | ';
                        echo '<span style="';
                        if ($booking['status'] == 'Approved') echo 'color: #10b981;';
                        elseif ($booking['status'] == 'Pending') echo 'color: #f59e0b;';
                        elseif ($booking['status'] == 'Rejected') echo 'color: #ef4444;';
                        echo '"><strong>' . $booking['status'] . '</strong></span>';
                        echo '</div>';
                        echo '<div>';
                        echo '<form method="POST" action="email_test.php" style="display: inline;">';
                        echo '<input type="hidden" name="action" value="send">';
                        echo '<input type="hidden" name="booking_id" value="' . $booking['booking_id'] . '">';
                        echo '<button type="submit" class="send-test-btn">📧 Send Email</button>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-emails">No bookings available. Create a booking first.</div>';
                }
                ?>
            </div>
        </div>

        <!-- Integration Status -->
        <div class="email-section">
            <h2>✅ Integration Status</h2>
            <ul style="color: #64748b; line-height: 2;">
                <li>✓ <strong>email.php:</strong> Email template functions created</li>
                <li>✓ <strong>session.php:</strong> Email sending integrated into booking functions</li>
                <li>✓ <strong>logs/:</strong> Email log directory created</li>
                <li>✓ <strong>Trigger points:</strong> Confirmation, admin notification, approval, rejection</li>
            </ul>
        </div>

    </div>

    <button class="dark-mode-btn" onclick="toggleDarkMode()">🌙</button>
    <script src="js/script.js"></script>
</body>
</html>

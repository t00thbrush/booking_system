<?php
require_once 'session.php';
require_login();

// Handle logout first
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    logout_user();
}

$user = get_user($_SESSION['user_id']);

// Get current month/year from query params or use current date
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));

// Validate month/year
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Get first and last day of month
$first_day = mktime(0, 0, 0, $month, 1, $year);
$last_day = mktime(23, 59, 59, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year), $year);
$calendar_start = strtotime('Monday this week', $first_day);
$calendar_end = strtotime('Sunday this week', $last_day);

// Get all bookings for this month
global $conn;
$start_date = date('Y-m-d', $calendar_start);
$end_date = date('Y-m-d', $calendar_end);

$query = "SELECT DISTINCT DATE(booking_date) as booking_date, COUNT(*) as count 
          FROM bookings 
          WHERE booking_date >= '$start_date' 
          AND booking_date <= '$end_date'
          AND status != 'Cancelled'
          GROUP BY DATE(booking_date)";
$result = mysqli_query($conn, $query);
$bookings_by_date = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookings_by_date[$row['booking_date']] = $row['count'];
}

// Get user's bookings for this month
$user_query = "SELECT DISTINCT DATE(booking_date) as booking_date, booking_id, facility_id, time_slot, status
               FROM bookings
               WHERE user_id = " . (int)$_SESSION['user_id'] . "
               AND booking_date >= '$start_date'
               AND booking_date <= '$end_date'
               ORDER BY booking_date";
$user_result = mysqli_query($conn, $user_query);
$user_bookings_by_date = [];
while ($row = mysqli_fetch_assoc($user_result)) {
    if (!isset($user_bookings_by_date[$row['booking_date']])) {
        $user_bookings_by_date[$row['booking_date']] = [];
    }
    $user_bookings_by_date[$row['booking_date']][] = $row;
}

// Get facilities
$facilities = get_facilities();

// Get selected date details
$selected_date = $_GET['date'] ?? '';
$selected_date_bookings = [];
if (!empty($selected_date) && isset($user_bookings_by_date[$selected_date])) {
    $selected_date_bookings = $user_bookings_by_date[$selected_date];
}

// Month names
$month_names = ['January', 'February', 'March', 'April', 'May', 'June', 
                'July', 'August', 'September', 'October', 'November', 'December'];
$day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Calendar - Online Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .calendar-nav a,
        .calendar-nav button {
            padding: 8px 15px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s;
        }

        .calendar-nav a:hover,
        .calendar-nav button:hover {
            transform: translateY(-2px);
        }

        .month-display {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            min-width: 200px;
            text-align: center;
        }

        body.dark-mode .month-display {
            color: #f1f5f9;
        }

        .calendar-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 1024px) {
            .calendar-container {
                grid-template-columns: 1fr;
            }
        }

        .calendar-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode .calendar-card {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(148, 163, 184, 0.2);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 20px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            color: #64748b;
            padding: 10px;
            font-size: 13px;
            text-transform: uppercase;
        }

        body.dark-mode .calendar-day-header {
            color: #cbd5e1;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            font-weight: 600;
            position: relative;
            min-height: 60px;
            padding: 5px;
        }

        body.dark-mode .calendar-day {
            background: #1e293b;
            border-color: #334155;
        }

        .calendar-day:hover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }

        body.dark-mode .calendar-day:hover {
            background: rgba(99, 102, 241, 0.1);
        }

        .calendar-day.other-month {
            color: #cbd5e1;
            background: #f8fafc;
            cursor: default;
        }

        body.dark-mode .calendar-day.other-month {
            background: rgba(51, 65, 85, 0.5);
            color: #64748b;
        }

        .calendar-day.today {
            border: 2px solid #6366f1;
            font-weight: 700;
        }

        .calendar-day.selected {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-color: #6366f1;
        }

        .calendar-day.has-booking {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%);
            border-color: #10b981;
        }

        .calendar-day.has-booking.selected {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }

        .booking-indicator {
            position: absolute;
            bottom: 2px;
            height: 3px;
            background: #10b981;
            border-radius: 2px;
            width: 100%;
        }

        .calendar-day.selected .booking-indicator {
            background: rgba(255, 255, 255, 0.8);
        }

        .day-number {
            font-size: 16px;
        }

        .booking-count {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        body.dark-mode .booking-count {
            color: #cbd5e1;
        }

        .calendar-day.selected .booking-count {
            color: rgba(255, 255, 255, 0.9);
        }

        .sidebar-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        body.dark-mode .sidebar-card {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(148, 163, 184, 0.2);
        }

        .legend {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 13px;
        }

        body.dark-mode .legend-item {
            color: #cbd5e1;
        }

        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .legend-box.today {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: 1px solid #6366f1;
        }

        .legend-box.has-booking {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.3) 0%, rgba(5, 150, 105, 0.3) 100%);
            border: 1px solid #10b981;
        }

        .legend-box.pending {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.3) 0%, rgba(217, 119, 6, 0.3) 100%);
            border: 1px solid #f59e0b;
        }

        .booking-details {
            max-height: 400px;
            overflow-y: auto;
        }

        .booking-item {
            background: rgba(99, 102, 241, 0.05);
            border-left: 3px solid #6366f1;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            font-size: 12px;
        }

        body.dark-mode .booking-item {
            background: rgba(99, 102, 241, 0.1);
        }

        .booking-item.rejected {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        body.dark-mode .booking-item.rejected {
            background: rgba(239, 68, 68, 0.1);
        }

        .booking-item.pending {
            border-left-color: #f59e0b;
            background: rgba(245, 158, 11, 0.05);
        }

        body.dark-mode .booking-item.pending {
            background: rgba(245, 158, 11, 0.1);
        }

        .booking-facility {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        body.dark-mode .booking-facility {
            color: #f1f5f9;
        }

        .booking-time {
            color: #64748b;
            margin-bottom: 4px;
        }

        body.dark-mode .booking-time {
            color: #cbd5e1;
        }

        .booking-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .booking-status.approved {
            background: #10b981;
            color: white;
        }

        .booking-status.pending {
            background: #f59e0b;
            color: white;
        }

        .booking-status.rejected {
            background: #ef4444;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        body.dark-mode .empty-state {
            color: #cbd5e1;
        }

        .empty-state p {
            margin: 0;
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
                <li><a href="calendar.php" style="color: #6366f1; font-weight: 600;">Calendar</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
                <li><a href="index.php?logout=true" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Booking Calendar</h1>
            <p>View your bookings and facility availability</p>
        </div>
    </div>

    <div class="container">
        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="calendar-nav">
                <a href="?year=<?php echo $year; ?>&month=<?php echo ($month == 1 ? 12 : $month-1); ?>&year=<?php echo ($month == 1 ? $year-1 : $year); ?>">← Previous</a>
                <a href="?year=<?php echo date('Y'); ?>&month=<?php echo date('m'); ?>">Today</a>
                <a href="?year=<?php echo $year; ?>&month=<?php echo ($month == 12 ? 1 : $month+1); ?>&year=<?php echo ($month == 12 ? $year+1 : $year); ?>">Next →</a>
            </div>
            <div class="month-display">
                <?php echo $month_names[$month-1]; ?> <?php echo $year; ?>
            </div>
        </div>

        <!-- Calendar Main Content -->
        <div class="calendar-container">
            <!-- Calendar -->
            <div class="calendar-card">
                <div class="calendar-grid">
                    <!-- Day headers -->
                    <?php foreach ($day_names as $day): ?>
                        <div class="calendar-day-header"><?php echo $day; ?></div>
                    <?php endforeach; ?>

                    <!-- Calendar days -->
                    <?php
                    $current_date = $calendar_start;
                    while ($current_date <= $calendar_end) {
                        $date_str = date('Y-m-d', $current_date);
                        $day_num = date('d', $current_date);
                        $day_month = date('m', $current_date);
                        $day_year = date('Y', $current_date);
                        $is_other_month = ($day_month != $month || $day_year != $year);
                        $is_today = ($date_str == date('Y-m-d'));
                        $is_selected = ($selected_date == $date_str);
                        $has_booking = isset($user_bookings_by_date[$date_str]);
                        $is_past = (strtotime($date_str) < strtotime(date('Y-m-d')));
                        
                        $classes = 'calendar-day';
                        if ($is_other_month) $classes .= ' other-month';
                        if ($is_today && !$is_other_month) $classes .= ' today';
                        if ($is_selected) $classes .= ' selected';
                        if ($has_booking && !$is_other_month) $classes .= ' has-booking';
                        
                        ?>
                        <a href="?year=<?php echo $year; ?>&month=<?php echo $month; ?>&date=<?php echo $date_str; ?>" class="<?php echo $classes; ?>">
                            <span class="day-number"><?php echo $day_num; ?></span>
                            <?php if ($has_booking && !$is_other_month): ?>
                                <span class="booking-count"><?php echo count($user_bookings_by_date[$date_str]); ?> booking<?php echo count($user_bookings_by_date[$date_str]) != 1 ? 's' : ''; ?></span>
                                <div class="booking-indicator"></div>
                            <?php endif; ?>
                        </a>
                        <?php
                        $current_date = strtotime('+1 day', $current_date);
                    }
                    ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Legend -->
                <div class="sidebar-card">
                    <h3 style="margin-top: 0; margin-bottom: 15px;">Legend</h3>
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-box today"></div>
                            <span>Today</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box has-booking"></div>
                            <span>Has Booking</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box pending"></div>
                            <span>Overall Bookings</span>
                        </div>
                    </div>
                </div>

                <!-- Selected Date Info -->
                <?php if (!empty($selected_date)): ?>
                    <div class="sidebar-card">
                        <h3 style="margin-top: 0; margin-bottom: 15px;">
                            📅 <?php echo date('l, M d, Y', strtotime($selected_date)); ?>
                        </h3>
                        
                        <?php if (empty($selected_date_bookings)): ?>
                            <p class="text-muted" style="margin: 0; text-align: center; padding: 20px 0;">
                                No bookings on this date.<br>
                                <a href="booking.php?booking_date=<?php echo $selected_date; ?>" style="color: #6366f1; font-size: 12px;">Make a booking →</a>
                            </p>
                        <?php else: ?>
                            <div class="booking-details">
                                <?php foreach ($selected_date_bookings as $booking): 
                                    $facility = get_facility($booking['facility_id']);
                                    $status_lower = strtolower($booking['status']);
                                ?>
                                    <div class="booking-item <?php echo $status_lower; ?>">
                                        <div class="booking-facility"><?php echo htmlspecialchars($facility['facility_name']); ?></div>
                                        <div class="booking-time">⏰ <?php echo $booking['time_slot']; ?></div>
                                        <span class="booking-status <?php echo $status_lower; ?>"><?php echo $booking['status']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="sidebar-card">
                        <div class="empty-state">
                            <p style="font-size: 24px; margin-bottom: 10px;">📅</p>
                            <p><strong>Select a date</strong> to view your bookings</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="sidebar-card">
                    <h3 style="margin-top: 0; margin-bottom: 15px;">📊 This Month</h3>
                    <ul style="list-style: none; padding: 0; margin: 0; color: #64748b;">
                        <li style="padding: 8px 0; display: flex; justify-content: space-between;">
                            <span>Total Days</span>
                            <strong style="color: #1e293b;"><?php echo cal_days_in_month(CAL_GREGORIAN, $month, $year); ?></strong>
                        </li>
                        <li style="padding: 8px 0; display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0;">
                            <span>Booked Days</span>
                            <strong style="color: #10b981;"><?php echo count($user_bookings_by_date); ?></strong>
                        </li>
                        <li style="padding: 8px 0; display: flex; justify-content: space-between;">
                            <span>Available Days</span>
                            <strong style="color: #6366f1;"><?php echo (cal_days_in_month(CAL_GREGORIAN, $month, $year) - count($user_bookings_by_date)); ?></strong>
                        </li>
                    </ul>
                </div>
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
</body>
</html>

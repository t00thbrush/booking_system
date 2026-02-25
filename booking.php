<?php
require_once 'session.php';
require_login();

// Handle logout first (before any output)
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    logout_user();
}

$user = get_user($_SESSION['user_id']);
$facilities = get_facilities();
$message = '';
$error = '';

// Handle search/filter
$search_facility = $_GET['search_facility'] ?? '';
$search_status = $_GET['search_status'] ?? '';
$search_date_from = $_GET['search_date_from'] ?? '';
$search_date_to = $_GET['search_date_to'] ?? '';
$search_text = $_GET['search_text'] ?? '';

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'book') {
        $facility_id = (int)($_POST['facility_id'] ?? 0);
        $booking_date = $_POST['booking_date'] ?? '';
        $time_slot = $_POST['time_slot'] ?? '';
        $purpose = $_POST['purpose'] ?? '';

        if (empty($facility_id) || empty($booking_date) || empty($time_slot)) {
            $error = 'Please select all required fields';
        } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
            $error = 'Cannot book a past date';
        } else {
            if (create_booking($_SESSION['user_id'], $facility_id, $booking_date, $time_slot, $purpose)) {
                $message = 'Booking created successfully! Booking ID: ' . mysqli_insert_id($GLOBALS['conn']);
            } else {
                $error = 'This time slot is already booked. Please select another.';
            }
        }
    } elseif ($action == 'cancel') {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $booking = get_booking($booking_id);

        if ($booking && $booking['user_id'] == $_SESSION['user_id']) {
            if (cancel_booking($booking_id)) {
                $message = 'Booking cancelled successfully';
            } else {
                $error = 'Error cancelling booking';
            }
        } else {
            $error = 'Unauthorized action';
        }
    } elseif ($action == 'add_comment') {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $comment_text = trim($_POST['comment_text'] ?? '');
        $booking = get_booking($booking_id);

        if ($booking && $booking['user_id'] == $_SESSION['user_id']) {
            if (!empty($comment_text)) {
                if (add_comment($booking_id, $_SESSION['user_id'], $comment_text)) {
                    $message = 'Comment added successfully';
                } else {
                    $error = 'Error adding comment';
                }
            } else {
                $error = 'Comment cannot be empty';
            }
        } else {
            $error = 'Unauthorized action';
        }
    }
}

// Get filtered bookings
if (!empty($search_facility) || !empty($search_status) || !empty($search_date_from) || !empty($search_date_to) || !empty($search_text)) {
    $user_bookings = search_user_bookings(
        $_SESSION['user_id'],
        !empty($search_facility) ? $search_facility : null,
        !empty($search_status) ? $search_status : null,
        !empty($search_date_from) ? $search_date_from : null,
        !empty($search_date_to) ? $search_date_to : null,
        !empty($search_text) ? $search_text : null
    );
} else {
    $user_bookings = get_user_bookings($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Facilities - Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .time-input-wrapper {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        .time-input-wrapper input {
            flex: 1;
        }
        .facilities-horizontal {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        @media (max-width: 1024px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }
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
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
                <li><a href="index.php?logout=true" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Book a Facility</h1>
            <p>Reserve school facilities for your events and activities</p>
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

        <!-- New Booking Section -->
        <div class="admin-section">
            <h2 class="section-title">Create New Booking</h2>
            
            <div class="booking-grid">
                <!-- Booking Form -->
                <div class="booking-form">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="book">

                        <div class="form-group">
                            <label for="facility_id">Select Facility *</label>
                            <select id="facility_id" name="facility_id" required>
                                <option value="">-- Choose a facility --</option>
                                <?php foreach ($facilities as $facility): ?>
                                    <option value="<?php echo $facility['facility_id']; ?>">
                                        <?php echo htmlspecialchars($facility['facility_name']); ?> 
                                        (Capacity: <?php echo $facility['capacity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="booking_date">Booking Date *</label>
                            <input 
                                type="date" 
                                id="booking_date" 
                                name="booking_date" 
                                required
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="time_slot">Time Slot (e.g., 8AM to 12PM) *</label>
                            <input 
                                type="text" 
                                id="time_slot" 
                                name="time_slot" 
                                placeholder="e.g., 8AM to 12PM or 09:00-13:00"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="purpose">Purpose / Event Name</label>
                            <textarea 
                                id="purpose" 
                                name="purpose" 
                                placeholder="Describe the purpose of booking"
                                rows="3"
                            ></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">Create Booking</button>
                    </form>
                </div>

                <!-- Quick Info -->
                <div class="card">
                    <h3 style="color: var(--primary); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 1px;">📋 Booking Info</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Total Facilities</p>
                            <p style="color: var(--primary); font-size: 1.8rem; font-weight: 900;"><?php echo count($facilities); ?></p>
                        </div>
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Your Bookings</p>
                            <p style="color: var(--primary); font-size: 1.8rem; font-weight: 900;"><?php echo count($user_bookings); ?></p>
                        </div>
                        <div style="padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">
                                <strong>Note:</strong> Bookings are subject to admin approval. You will receive a notification once your booking is reviewed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Facilities Section -->
        <div class="admin-section">
            <h2 class="section-title">Available Facilities</h2>
            
            <div class="facilities-horizontal">
                <?php foreach ($facilities as $facility): ?>
                    <div class="facility-card">
                        <div class="facility-card-header">
                            <h3><?php echo htmlspecialchars($facility['facility_name']); ?></h3>
                        </div>
                        <div class="facility-card-body">
                            <p><?php echo htmlspecialchars($facility['description'] ?? 'No description available'); ?></p>
                            <div class="facility-capacity">
                                <strong>👥 Capacity:</strong> <?php echo htmlspecialchars($facility['capacity']); ?> people
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">
                                <strong>📍 Location:</strong> <?php echo htmlspecialchars($facility['location'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- My Bookings Section -->
        <div class="admin-section">
            <h2 class="section-title">My Bookings</h2>

            <!-- Search & Filter Form -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px;">🔍 Search & Filter</h3>
                <form method="GET" action="booking.php" class="search-filters">
                    <select name="search_facility">
                        <option value="">All Facilities</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?php echo $facility['facility_id']; ?>" <?php echo $search_facility == $facility['facility_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($facility['facility_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="search_status">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $search_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $search_status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $search_status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="Cancelled" <?php echo $search_status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>

                    <input type="date" name="search_date_from" value="<?php echo htmlspecialchars($search_date_from); ?>" placeholder="From Date">
                    <input type="date" name="search_date_to" value="<?php echo htmlspecialchars($search_date_to); ?>" placeholder="To Date">
                    <input type="text" name="search_text" placeholder="Search purpose, facility..." value="<?php echo htmlspecialchars($search_text); ?>">
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">🔍 Search</button>
                        <a href="booking.php" class="btn btn-sm" style="background: rgba(255, 255, 255, 0.1); color: var(--text-secondary);">Reset</a>
                    </div>
                </form>
            </div>

            <?php if (empty($user_bookings)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">No bookings found. Create your first booking above!</p>
                </div>
            <?php else: ?>
                <div class="card" style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['facility_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['time_slot']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['purpose'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo $booking['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] == 'Pending' || $booking['status'] == 'Approved'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Cancel</button>
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
    </div>

    <style>
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
    </style>

    <script src="js/script.js"></script>
</body>
</html>

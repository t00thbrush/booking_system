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

// Time slots available
$time_slots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Facilities - Online Booking System</title>
    <link rel="stylesheet" href="css/style.css">
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
            <p>Reserve school facilities for your events</p>
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

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
            <!-- Booking Form -->
            <div>
                <div class="booking-form">
                    <h2 style="margin-bottom: 20px;">New Booking</h2>

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
                            <label>Available Time Slots *</label>
                            <div class="time-slots">
                                <?php foreach ($time_slots as $slot): ?>
                                    <input 
                                        type="radio" 
                                        id="slot_<?php echo $slot; ?>"
                                        name="time_slot" 
                                        value="<?php echo $slot; ?>"
                                        required
                                    >
                                    <label for="slot_<?php echo $slot; ?>">
                                        <?php echo $slot; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="purpose">Purpose / Event Name</label>
                            <textarea 
                                id="purpose" 
                                name="purpose" 
                                placeholder="Describe the purpose of booking"
                                rows="4"
                            ></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-block">Create Booking</button>
                    </form>
                </div>
            </div>

            <!-- Available Facilities -->
            <div>
                <h2 style="margin-bottom: 20px;">Available Facilities</h2>
                <div class="facilities-grid" style="grid-template-columns: 1fr;">
                    <?php foreach ($facilities as $facility): ?>
                        <div class="facility-card">
                            <div class="facility-card-header">
                                <h3><?php echo htmlspecialchars($facility['facility_name']); ?></h3>
                            </div>
                            <div class="facility-card-body">
                                <p><?php echo htmlspecialchars($facility['description']); ?></p>
                                <div class="facility-capacity">
                                    <strong>Capacity:</strong> <?php echo htmlspecialchars($facility['capacity']); ?> people
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- My Bookings -->
        <div class="card">
            <div class="card-body">
            <h2 style="margin-bottom: 20px;">My Bookings</h2>

            <!-- Search & Filter Form -->
            <div style="background: rgba(99, 102, 241, 0.05); padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 3px solid #6366f1;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">🔍 Search & Filter</h3>
                <form method="GET" action="booking.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
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
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px;">Status</label>
                        <select name="search_status" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo $search_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $search_status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $search_status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Cancelled" <?php echo $search_status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                        <input type="text" name="search_text" placeholder="Search purpose, facility..." value="<?php echo htmlspecialchars($search_text); ?>" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; flex: 1;">🔍 Search</button>
                        <a href="booking.php" style="background: #e2e8f0; color: #1e293b; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; text-align: center;">Reset</a>
                    </div>
                </form>
            </div>

            <?php if (empty($user_bookings)): ?>
                <p class="text-muted" style="text-align: center; padding: 40px 0;">
                    No bookings found. <a href="#" onclick="document.querySelector('[name=facility_id]').focus();" style="color: var(--primary-color);">Book a facility now!</a>
                </p>
            <?php else: ?>
                <div style="overflow-x: auto;">
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
                                    <td><?php echo $booking['time_slot']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['purpose'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo $booking['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="openModal('commentModal<?php echo $booking['booking_id']; ?>')" title="Add/View Comments">💬</button>
                                            <?php if ($booking['status'] != 'Cancelled'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this booking?')">Cancel</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Comment Modal for this booking -->
                                <div id="commentModal<?php echo $booking['booking_id']; ?>" class="modal">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2>Comments & Notes</h2>
                                            <button type="button" class="modal-close" onclick="closeModal('commentModal<?php echo $booking['booking_id']; ?>')">×</button>
                                        </div>
                                        <div class="modal-body">
                                            <p style="color: #64748b; margin: 0 0 15px 0; font-size: 13px;">
                                                <strong><?php echo htmlspecialchars($booking['facility_name']); ?></strong><br>
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> at <?php echo $booking['time_slot']; ?>
                                            </p>

                                            <!-- Comments List -->
                                            <div class="comments-list">
                                                <?php 
                                                $comments = get_booking_comments($booking['booking_id']);
                                                if (empty($comments)): 
                                                ?>
                                                    <p class="text-muted" style="text-align: center; padding: 20px 0; margin: 0;">
                                                        No comments yet. Be the first to add one!
                                                    </p>
                                                <?php else: 
                                                    foreach ($comments as $comment):
                                                ?>
                                                    <div class="comment-item">
                                                        <div class="comment-author">
                                                            <?php echo htmlspecialchars($comment['name']); ?>
                                                            <span class="comment-time"><?php echo date('M d, h:i A', strtotime($comment['created_at'])); ?></span>
                                                        </div>
                                                        <div class="comment-text"><?php echo htmlspecialchars($comment['comment_text']); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Add Comment Form -->
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="add_comment">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <textarea name="comment_text" placeholder="Add a comment or note..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; resize: vertical; min-height: 80px;" required></textarea>
                                                <div class="modal-footer" style="margin-top: 15px;">
                                                    <button type="button" class="btn btn-secondary" onclick="closeModal('commentModal<?php echo $booking['booking_id']; ?>')">Close</button>
                                                    <button type="submit" class="btn btn-primary">Add Comment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
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

    <script src="js/script.js"></script>
</body>
</html>

<?php
session_start();
require_once 'db.php';
require_once 'email.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['role'] == 'Admin';
}

function is_staff() {
    return is_logged_in() && $_SESSION['role'] == 'Staff';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

function redirect_if_logged_in() {
    if (is_logged_in()) {
        if (is_admin()) {
            header("Location: admin.php");
        } else {
            header("Location: booking.php");
        }
        exit();
    }
}

function login_user($username, $password) {
    global $conn;
    $username = mysqli_real_escape_string($conn, $username);
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Ensure account is approved before allowing login
        if (!empty($user['is_approved']) && $user['is_approved'] == 0) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    return false;
}

function register_user($name, $username, $email, $password, $role = 'External') {
    global $conn;
    $name = mysqli_real_escape_string($conn, $name);
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prevent public creation of Admin accounts
    if ($role === 'Admin') $role = 'External';

    // Teacher accounts require admin approval
    $is_approved = 1;
    if ($role === 'Teacher') $is_approved = 0;

    $query = "INSERT INTO users (name, username, email, password, role, is_approved) VALUES ('$name', '$username', '$email', '$hashed_password', '$role', $is_approved)";
    return mysqli_query($conn, $query);
}

function approve_user($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "UPDATE users SET is_approved = 1 WHERE user_id = $user_id";
    return mysqli_query($conn, $query);
}

function get_pending_teachers() {
    global $conn;
    $query = "SELECT user_id, name, username, email, role, created_at FROM users WHERE role = 'Teacher' AND is_approved = 0 ORDER BY created_at ASC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function logout_user() {
    session_destroy();
    header("Location: index.php");
    exit();
}

function get_user($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "SELECT * FROM users WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function get_facilities() {
    global $conn;
    $query = "SELECT * FROM facilities ORDER BY facility_name";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function get_facility($facility_id) {
    global $conn;
    $facility_id = (int)$facility_id;
    $query = "SELECT * FROM facilities WHERE facility_id = $facility_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function is_slot_available($facility_id, $booking_date, $time_slot) {
    global $conn;
    $facility_id = (int)$facility_id;
    $booking_date = mysqli_real_escape_string($conn, $booking_date);
    $time_slot = mysqli_real_escape_string($conn, $time_slot);
    $query = "SELECT COUNT(*) as count FROM bookings WHERE facility_id = $facility_id AND booking_date = '$booking_date' AND time_slot = '$time_slot' AND status != 'Cancelled'";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['count'] == 0;
}

function create_booking($user_id, $facility_id, $booking_date, $time_slot, $purpose = '') {
    global $conn;
    if (!is_slot_available($facility_id, $booking_date, $time_slot)) {
        return false;
    }
    $user_id = (int)$user_id;
    $facility_id = (int)$facility_id;
    $booking_date = mysqli_real_escape_string($conn, $booking_date);
    $time_slot = mysqli_real_escape_string($conn, $time_slot);
    $purpose = mysqli_real_escape_string($conn, $purpose);
    $query = "INSERT INTO bookings (user_id, facility_id, booking_date, time_slot, purpose) VALUES ($user_id, $facility_id, '$booking_date', '$time_slot', '$purpose')";
    
    if (mysqli_query($conn, $query)) {
        // Get the booking ID
        $booking_id = mysqli_insert_id($conn);
        
        // Get user and facility details for email
        $user = get_user($user_id);
        $facility = get_facility($facility_id);
        
        // Send confirmation email to user
        send_booking_confirmation_email(
            $user['email'],
            $user['name'],
            $booking_id,
            $facility['facility_name'],
            $booking_date,
            $time_slot,
            $purpose
        );
        
        // Send admin notification
        send_new_booking_admin_email(
            $user['name'],
            $user['email'],
            $booking_id,
            $facility['facility_name'],
            $booking_date,
            $time_slot,
            $purpose
        );
        
        return true;
    }
    return false;
}

function get_user_bookings($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "SELECT b.*, f.facility_name FROM bookings b JOIN facilities f ON b.facility_id = f.facility_id WHERE b.user_id = $user_id ORDER BY b.booking_date DESC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function get_all_bookings($status = null) {
    global $conn;
    $query = "SELECT b.*, u.name as user_name, u.email, f.facility_name FROM bookings b JOIN users u ON b.user_id = u.user_id JOIN facilities f ON b.facility_id = f.facility_id";
    if ($status) {
        $status = mysqli_real_escape_string($conn, $status);
        $query .= " WHERE b.status = '$status'";
    }
    $query .= " ORDER BY b.booking_date DESC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function update_booking_status($booking_id, $status) {
    global $conn;
    $booking_id = (int)$booking_id;
    $status = mysqli_real_escape_string($conn, $status);
    $query = "UPDATE bookings SET status = '$status' WHERE booking_id = $booking_id";
    
    if (mysqli_query($conn, $query)) {
        // Get booking, user and facility details for email
        $booking = get_booking($booking_id);
        $user = get_user($booking['user_id']);
        $facility = get_facility($booking['facility_id']);
        
        // Send appropriate email based on status
        if ($status == 'Approved') {
            send_booking_approval_email(
                $user['email'],
                $user['name'],
                $booking_id,
                $facility['facility_name'],
                $booking['booking_date'],
                $booking['time_slot']
            );
        } elseif ($status == 'Rejected') {
            send_booking_rejection_email(
                $user['email'],
                $user['name'],
                $booking_id,
                $facility['facility_name'],
                $booking['booking_date'],
                $booking['time_slot']
            );
        }
        
        return true;
    }
    return false;
}

function cancel_booking($booking_id) {
    return update_booking_status($booking_id, 'Cancelled');
}

function get_booking($booking_id) {
    global $conn;
    $booking_id = (int)$booking_id;
    $query = "SELECT * FROM bookings WHERE booking_id = $booking_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function get_booking_stats() {
    global $conn;
    $query = "SELECT COUNT(*) as total_bookings, SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled FROM bookings";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// ============================
// Search & Filter Functions
// ============================

function search_user_bookings($user_id, $facility_id = null, $status = null, $date_from = null, $date_to = null, $search_text = null) {
    global $conn;
    $user_id = (int)$user_id;
    
    $query = "SELECT b.*, f.facility_name FROM bookings b 
              JOIN facilities f ON b.facility_id = f.facility_id 
              WHERE b.user_id = $user_id";
    
    if (!empty($facility_id) && $facility_id != '') {
        $facility_id = (int)$facility_id;
        $query .= " AND b.facility_id = $facility_id";
    }
    
    if (!empty($status) && $status != '') {
        $status = mysqli_real_escape_string($conn, $status);
        $query .= " AND b.status = '$status'";
    }
    
    if (!empty($date_from)) {
        $date_from = mysqli_real_escape_string($conn, $date_from);
        $query .= " AND b.booking_date >= '$date_from'";
    }
    
    if (!empty($date_to)) {
        $date_to = mysqli_real_escape_string($conn, $date_to);
        $query .= " AND b.booking_date <= '$date_to'";
    }
    
    if (!empty($search_text)) {
        $search_text = '%' . mysqli_real_escape_string($conn, $search_text) . '%';
        $query .= " AND (b.purpose LIKE '$search_text' OR f.facility_name LIKE '$search_text')";
    }
    
    $query .= " ORDER BY b.booking_date DESC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function search_all_bookings($facility_id = null, $status = null, $user_id = null, $date_from = null, $date_to = null, $search_text = null) {
    global $conn;
    
    $query = "SELECT b.*, u.name as user_name, u.email, f.facility_name 
              FROM bookings b 
              JOIN users u ON b.user_id = u.user_id 
              JOIN facilities f ON b.facility_id = f.facility_id 
              WHERE 1=1";
    
    if (!empty($facility_id) && $facility_id != '') {
        $facility_id = (int)$facility_id;
        $query .= " AND b.facility_id = $facility_id";
    }
    
    if (!empty($status) && $status != '') {
        $status = mysqli_real_escape_string($conn, $status);
        $query .= " AND b.status = '$status'";
    }
    
    if (!empty($user_id) && $user_id != '') {
        $user_id = (int)$user_id;
        $query .= " AND b.user_id = $user_id";
    }
    
    if (!empty($date_from)) {
        $date_from = mysqli_real_escape_string($conn, $date_from);
        $query .= " AND b.booking_date >= '$date_from'";
    }
    
    if (!empty($date_to)) {
        $date_to = mysqli_real_escape_string($conn, $date_to);
        $query .= " AND b.booking_date <= '$date_to'";
    }
    
    if (!empty($search_text)) {
        $search_text = '%' . mysqli_real_escape_string($conn, $search_text) . '%';
        $query .= " AND (b.purpose LIKE '$search_text' OR f.facility_name LIKE '$search_text' OR u.name LIKE '$search_text' OR u.email LIKE '$search_text')";
    }
    
    $query .= " ORDER BY b.booking_date DESC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function get_all_users() {
    global $conn;
    $query = "SELECT user_id, name, email FROM users WHERE role IN ('External', 'Staff') ORDER BY name";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// ============================
// Booking Comments Functions
// ============================

function add_comment($booking_id, $user_id, $comment_text) {
    global $conn;
    $booking_id = (int)$booking_id;
    $user_id = (int)$user_id;
    $comment_text = mysqli_real_escape_string($conn, $comment_text);
    
    $query = "INSERT INTO booking_comments (booking_id, user_id, comment_text) VALUES ($booking_id, $user_id, '$comment_text')";
    return mysqli_query($conn, $query);
}

function get_booking_comments($booking_id) {
    global $conn;
    $booking_id = (int)$booking_id;
    $query = "SELECT bc.*, u.name, u.username FROM booking_comments bc 
              JOIN users u ON bc.user_id = u.user_id 
              WHERE bc.booking_id = $booking_id 
              ORDER BY bc.created_at DESC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function get_comment_count($booking_id) {
    global $conn;
    $booking_id = (int)$booking_id;
    $query = "SELECT COUNT(*) as count FROM booking_comments WHERE booking_id = $booking_id";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['count'];
}

// ============================
// Audit Logging Functions
// ============================

function log_action($action, $resource_type = null, $resource_id = null, $details = null) {
    global $conn;
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';
    $action = mysqli_real_escape_string($conn, $action);
    $resource_type = $resource_type ? mysqli_real_escape_string($conn, $resource_type) : 'NULL';
    $resource_id = $resource_id ? (int)$resource_id : 'NULL';
    $details = $details ? mysqli_real_escape_string($conn, $details) : 'NULL';
    $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
    
    $query = "INSERT INTO audit_log (user_id, action, resource_type, resource_id, details, ip_address) 
              VALUES ($user_id, '$action', $resource_type, $resource_id, $details, '$ip')";
    mysqli_query($conn, $query);
}

function get_audit_logs($limit = 100) {
    global $conn;
    $limit = (int)$limit;
    $query = "SELECT al.*, u.name FROM audit_log al 
              LEFT JOIN users u ON al.user_id = u.user_id 
              ORDER BY al.created_at DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// ============================
// Facility Amenities Functions
// ============================

function get_facility_amenities($facility_id) {
    global $conn;
    $facility_id = (int)$facility_id;
    $query = "SELECT amenities FROM facilities WHERE facility_id = $facility_id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row ? json_decode($row['amenities'], true) ?? [] : [];
}

function update_facility_amenities($facility_id, $amenities) {
    global $conn;
    $facility_id = (int)$facility_id;
    $amenities_json = mysqli_real_escape_string($conn, json_encode($amenities));
    $query = "UPDATE facilities SET amenities = '$amenities_json' WHERE facility_id = $facility_id";
    return mysqli_query($conn, $query);
}

// ============================
// Recurring Bookings Functions
// ============================

function create_recurring_booking($user_id, $facility_id, $booking_date, $time_slot, $pattern, $end_date, $purpose = '') {
    global $conn;
    $user_id = (int)$user_id;
    $facility_id = (int)$facility_id;
    $booking_date = mysqli_real_escape_string($conn, $booking_date);
    $time_slot = mysqli_real_escape_string($conn, $time_slot);
    $pattern = mysqli_real_escape_string($conn, $pattern);
    $end_date = mysqli_real_escape_string($conn, $end_date);
    $purpose = mysqli_real_escape_string($conn, $purpose);
    
    $query = "INSERT INTO bookings (user_id, facility_id, booking_date, time_slot, purpose, is_recurring, recurring_pattern, recurring_end_date) 
              VALUES ($user_id, $facility_id, '$booking_date', '$time_slot', '$purpose', TRUE, '$pattern', '$end_date')";
    return mysqli_query($conn, $query);
}

function generate_recurring_bookings($parent_booking_id) {
    global $conn;
    $parent_booking_id = (int)$parent_booking_id;
    $query = "SELECT * FROM bookings WHERE booking_id = $parent_booking_id";
    $result = mysqli_query($conn, $query);
    $parent = mysqli_fetch_assoc($result);
    
    if (!$parent || !$parent['is_recurring']) return 0;
    
    $current_date = strtotime($parent['booking_date']);
    $end_date = strtotime($parent['recurring_end_date']);
    $count = 0;
    
    while ($current_date <= $end_date) {
        if ($parent['recurring_pattern'] == 'daily') {
            $current_date = strtotime('+1 day', $current_date);
        } elseif ($parent['recurring_pattern'] == 'weekly') {
            $current_date = strtotime('+7 days', $current_date);
        } elseif ($parent['recurring_pattern'] == 'monthly') {
            $current_date = strtotime('+1 month', $current_date);
        }
        
        $date_str = date('Y-m-d', $current_date);
        $query = "INSERT INTO bookings (user_id, facility_id, booking_date, time_slot, purpose, parent_booking_id) 
                  VALUES ({$parent['user_id']}, {$parent['facility_id']}, '$date_str', '{$parent['time_slot']}', '{$parent['purpose']}', $parent_booking_id)";
        if (mysqli_query($conn, $query)) $count++;
    }
    
    return $count;
}

// ============================
// Waiting List Functions
// ============================

function add_to_waiting_list($user_id, $facility_id, $booking_date, $time_slot) {
    global $conn;
    $user_id = (int)$user_id;
    $facility_id = (int)$facility_id;
    $booking_date = mysqli_real_escape_string($conn, $booking_date);
    $time_slot = mysqli_real_escape_string($conn, $time_slot);
    
    // Get next waiting list position
    $query = "SELECT MAX(waiting_list_position) as max_pos FROM bookings 
              WHERE facility_id = $facility_id AND booking_date = '$booking_date' AND time_slot = '$time_slot' 
              AND on_waiting_list = TRUE";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $position = ($row['max_pos'] ?? 0) + 1;
    
    $query = "INSERT INTO bookings (user_id, facility_id, booking_date, time_slot, status, on_waiting_list, waiting_list_position)
              VALUES ($user_id, $facility_id, '$booking_date', '$time_slot', 'Pending', TRUE, $position)";
    return mysqli_query($conn, $query);
}

function get_waiting_list($facility_id, $booking_date, $time_slot) {
    global $conn;
    $facility_id = (int)$facility_id;
    $booking_date = mysqli_real_escape_string($conn, $booking_date);
    $time_slot = mysqli_real_escape_string($conn, $time_slot);
    
    $query = "SELECT b.*, u.name, u.email FROM bookings b
              JOIN users u ON b.user_id = u.user_id
              WHERE b.facility_id = $facility_id AND b.booking_date = '$booking_date' 
              AND b.time_slot = '$time_slot' AND b.on_waiting_list = TRUE
              ORDER BY b.waiting_list_position ASC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// ============================
// Two-Factor Authentication
// ============================

function generate_2fa_code() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function send_2fa_code($email, $code) {
    global $conn;
    $code_stored = password_hash($code, PASSWORD_DEFAULT);
    // Store code with expiration in session or temp storage
    $_SESSION['2fa_code'] = $code_stored;
    $_SESSION['2fa_expire'] = time() + 600; // 10 minutes
    
    // Email the code
    $subject = '2FA Verification Code - ' . date('H:i:s');
    $message = "Your 2FA code is: <strong>$code</strong><br>Valid for 10 minutes.";
    send_email($email, $subject, $message);
}

function verify_2fa_code($code) {
    return isset($_SESSION['2fa_code']) && 
           $_SESSION['2fa_expire'] > time() && 
           password_verify($code, $_SESSION['2fa_code']);
}
?>

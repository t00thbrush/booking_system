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
    return is_logged_in() && in_array($_SESSION['role'], ['Staff','Teacher']);
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

// ============================
// Teacher Facility Permissions
// ============================

function grant_teacher_facility($user_id, $facility_id) {
    global $conn;
    $user_id = (int)$user_id;
    $facility_id = (int)$facility_id;
    $query = "INSERT IGNORE INTO teacher_facility_permissions (user_id, facility_id) VALUES ($user_id, $facility_id)";
    return mysqli_query($conn, $query);
}

function revoke_teacher_facility($user_id, $facility_id) {
    global $conn;
    $user_id = (int)$user_id;
    $facility_id = (int)$facility_id;
    $query = "DELETE FROM teacher_facility_permissions WHERE user_id = $user_id AND facility_id = $facility_id";
    return mysqli_query($conn, $query);
}

function set_teacher_facilities($user_id, $facility_ids = []) {
    global $conn;
    $user_id = (int)$user_id;
    // Remove existing
    mysqli_query($conn, "DELETE FROM teacher_facility_permissions WHERE user_id = $user_id");
    // Insert new
    foreach ($facility_ids as $fid) {
        $fid = (int)$fid;
        if ($fid > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO teacher_facility_permissions (user_id, facility_id) VALUES ($user_id, $fid)");
        }
    }
    return true;
}

function get_teacher_facilities($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "SELECT tfp.facility_id, f.facility_name FROM teacher_facility_permissions tfp JOIN facilities f ON tfp.facility_id = f.facility_id WHERE tfp.user_id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function teacher_has_facility($user_id, $facility_id) {
    global $conn;
    $user_id = (int)$user_id;
    $facility_id = (int)$facility_id;
    $query = "SELECT COUNT(*) as cnt FROM teacher_facility_permissions WHERE user_id = $user_id AND facility_id = $facility_id";
    $res = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($res);
    return ($row['cnt'] ?? 0) > 0;
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
    // Return all non-admin users
    $query = "SELECT user_id, name, email FROM users WHERE role != 'Admin' ORDER BY name";
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
?>

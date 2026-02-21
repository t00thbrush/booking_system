<?php
session_start();
require_once 'db.php';

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
    $query = "INSERT INTO users (name, username, email, password, role) VALUES ('$name', '$username', '$email', '$hashed_password', '$role')";
    return mysqli_query($conn, $query);
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
    return mysqli_query($conn, $query);
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
    return mysqli_query($conn, $query);
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
?>

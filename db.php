<?php
// Database connection file
define('DB_SERVER', 'localhost');
define('DB_USER', 'booking_user');
define('DB_PASSWORD', 'booking_password');
define('DB_NAME', 'booking_system');

// Connect to database
$conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Database created or already exists
}

// Select the database
mysqli_select_db($conn, DB_NAME);

// Create Users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Teacher', 'Student', 'External') DEFAULT 'External',
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $users_table)) {
    echo "Error creating users table: " . mysqli_error($conn);
}

// Create Facilities table
$facilities_table = "CREATE TABLE IF NOT EXISTS facilities (
    facility_id INT AUTO_INCREMENT PRIMARY KEY,
    facility_name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $facilities_table)) {
    echo "Error creating facilities table: " . mysqli_error($conn);
}

// Create Bookings table
$bookings_table = "CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    facility_id INT NOT NULL,
    booking_date DATE NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    purpose VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(facility_id) ON DELETE CASCADE,
    UNIQUE KEY unique_booking (facility_id, booking_date, time_slot)
)";

if (!mysqli_query($conn, $bookings_table)) {
    echo "Error creating bookings table: " . mysqli_error($conn);
}

// Create Comments table for booking discussions
$comments_table = "CREATE TABLE IF NOT EXISTS booking_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if (!mysqli_query($conn, $comments_table)) {
    echo "Error creating booking_comments table: " . mysqli_error($conn);
}

// Create Audit Log table
$audit_table = "CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
)";

if (!mysqli_query($conn, $audit_table)) {
    echo "Error creating audit_log table: " . mysqli_error($conn);
}

// Create teacher-facility permissions table
$teacher_perm = "CREATE TABLE IF NOT EXISTS teacher_facility_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    facility_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(facility_id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_facility (user_id, facility_id)
)";

if (!mysqli_query($conn, $teacher_perm)) {
    echo "Error creating teacher_facility_permissions table: " . mysqli_error($conn);
}

// Add amenities column to facilities if it doesn't exist
$check_amenities = "SHOW COLUMNS FROM facilities LIKE 'amenities'";
$result = mysqli_query($conn, $check_amenities);
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE facilities ADD COLUMN amenities TEXT DEFAULT NULL";
    mysqli_query($conn, $alter_query);
}

// Add recurring booking columns if they don't exist
$check_recurring = "SHOW COLUMNS FROM bookings LIKE 'is_recurring'";
$result = mysqli_query($conn, $check_recurring);
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE bookings ADD COLUMN is_recurring BOOLEAN DEFAULT FALSE,
                    ADD COLUMN recurring_pattern VARCHAR(50),
                    ADD COLUMN recurring_end_date DATE,
                    ADD COLUMN parent_booking_id INT,
                    ADD COLUMN on_waiting_list BOOLEAN DEFAULT FALSE,
                    ADD COLUMN waiting_list_position INT DEFAULT 0";
    mysqli_query($conn, $alter_query);
}

// Add 2FA column to users if it doesn't exist
$check_2fa = "SHOW COLUMNS FROM users LIKE 'two_factor_enabled'";
$result = mysqli_query($conn, $check_2fa);
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE,
                    ADD COLUMN two_factor_secret VARCHAR(100),
                    ADD COLUMN backup_codes JSON";
    mysqli_query($conn, $alter_query);
}

// Insert sample facilities if not exist
$check_facilities = "SELECT COUNT(*) as count FROM facilities";
$result = mysqli_query($conn, $check_facilities);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    $insert_facilities = "INSERT INTO facilities (facility_name, description, capacity) VALUES
        ('Indoor Stadium', 'Large indoor sports facility with basketball and volleyball courts', 500),
        ('Main Hall', 'Main auditorium for events, presentations, and gatherings', 300),
        ('Library', 'Study center with reading rooms and meeting spaces', 100),
        ('Conference Room A', 'Professional conference room with AV equipment', 50),
        ('Conference Room B', 'Smaller conference room for team meetings', 30)";
    
    mysqli_query($conn, $insert_facilities);
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");
?>

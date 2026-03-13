<?php
// Database connection file - Production Version
define('DB_SERVER', 'sql101.ezyro.com');
define('DB_USER', 'ezyro_41338953');
define('DB_PASSWORD', 'alohomora');
define('DB_NAME', 'ezyro_41338953_booking_system');

// Connect to database directly with the database name
$conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");

// --- TABLE INITIALIZATION LOGIC ---

// Create Users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Teacher', 'Student', 'External') DEFAULT 'External',
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(100),
    backup_codes JSON
)";
mysqli_query($conn, $users_table);

// Create Facilities table
$facilities_table = "CREATE TABLE IF NOT EXISTS facilities (
    facility_id INT AUTO_INCREMENT PRIMARY KEY,
    facility_name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 50,
    amenities TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $facilities_table);

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
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_pattern VARCHAR(50),
    recurring_end_date DATE,
    parent_booking_id INT,
    on_waiting_list BOOLEAN DEFAULT FALSE,
    waiting_list_position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(facility_id) ON DELETE CASCADE,
    UNIQUE KEY unique_booking (facility_id, booking_date, time_slot)
)";
mysqli_query($conn, $bookings_table);

// Create Comments table
$comments_table = "CREATE TABLE IF NOT EXISTS booking_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
mysqli_query($conn, $comments_table);

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
mysqli_query($conn, $audit_table);

// Create Teacher Facilities table
$teacher_facilities_table = "CREATE TABLE IF NOT EXISTS teacher_facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    facility_id INT NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(facility_id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (teacher_id, facility_id)
)";
mysqli_query($conn, $teacher_facilities_table);

// Insert sample facilities if none exist
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
?>
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
    role ENUM('Admin', 'Staff', 'External') DEFAULT 'External',
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
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

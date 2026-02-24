<?php
// Script to create a single admin user if none exists.
require_once __DIR__ . '/../session.php';

global $conn;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='Admin'");
if (!$res) {
    echo "DB_QUERY_ERROR: " . mysqli_error($conn) . "\n";
    exit(1);
}
$row = mysqli_fetch_assoc($res);
if ($row && $row['cnt'] > 0) {
    echo "ADMIN_EXISTS: " . $row['cnt'] . "\n";
    exit(0);
}

$username = 'admin';
$password = bin2hex(random_bytes(6));
$hash = password_hash($password, PASSWORD_DEFAULT);
$name = 'Principal';
$email = '';
$role = 'Admin';
$is_approved = 1;

$stmt = mysqli_prepare($conn, "INSERT INTO users (name, username, email, password, role, is_approved) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo "PREPARE_ERROR: " . mysqli_error($conn) . "\n";
    exit(1);
}
mysqli_stmt_bind_param($stmt, 'sssssi', $name, $username, $email, $hash, $role, $is_approved);
if (mysqli_stmt_execute($stmt)) {
    $id = mysqli_insert_id($conn);
    echo 'CREATED: username=' . $username . ' password=' . $password . ' id=' . $id . "\n";
} else {
    echo "EXECUTE_ERROR: " . mysqli_stmt_error($stmt) . "\n";
}
mysqli_stmt_close($stmt);

?>

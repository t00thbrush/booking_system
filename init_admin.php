<?php
require_once 'session.php';

// Only allow creating admin if none exists
global $conn;
$check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'Admin'");
$row = mysqli_fetch_assoc($check);
if ($row && $row['cnt'] > 0) {
    die('An admin account already exists. Initialization is disabled.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? 'Admin');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $u = mysqli_real_escape_string($conn, $username);
        $n = mysqli_real_escape_string($conn, $name);
        $e = mysqli_real_escape_string($conn, $email);
        $query = "INSERT INTO users (name, username, email, password, role, is_approved) VALUES ('$n', '$u', '$e', '$hashed', 'Admin', 1)";
        if (mysqli_query($conn, $query)) {
            echo 'Admin account created successfully. You may now login as admin.';
            exit();
        } else {
            $error = 'Error creating admin: ' . mysqli_error($conn);
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Initialize Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container" style="max-width:600px;margin:40px auto;">
    <h2>Create Admin Account</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label for="name">Full name</label>
            <input id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
        </div>
        <button class="btn btn-primary">Create Admin</button>
    </form>
</div>
</body>
</html>

<?php
require_once 'session.php';

// Handle logout first (before any output)
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
}

redirect_if_logged_in();

$error = '';
$success = '';

// Check if user just registered
if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $success = 'Registration successful! Please login with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        $error = 'Enter the username.';
    } elseif (empty($password)) {
        $error = 'Enter the password.';
    } else {
        if (login_user($username, $password)) {
            if (is_admin()) {
                header("Location: admin.php");
            } else {
                header("Location: booking.php");
            }
            exit();
        } else {
            $error = 'Username or Password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/loot_theme.css">
</head>
<body>
    <div class="cinematic-bg">
        <div class="float-blob blue"></div>
        <div class="float-blob purple"></div>
    </div>
    <div class="universe-background"></div>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="index.php" class="nav-brand">📅 Booking System</a>
            <ul class="nav-links">
                <li><a href="register.php">Register</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Welcome Back</h1>
            <p>Login to book school facilities</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <h2 class="text-center" style="margin-bottom: 30px; display:flex; align-items:center; gap:10px; justify-content:center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="#8b5cf6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Login
            </h2>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group input-with-icon">
                    <label for="username">Username *</label>
                    <svg class="input-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" stroke="#6366f1" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="#8b5cf6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        placeholder="Enter your username"
                        autofocus
                    >
                </div>

                <div class="form-group input-with-icon">
                    <label for="password">Password *</label>
                    <svg class="input-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#06b6d4" stroke-width="1.2" fill="none"/><path d="M7 11V8a5 5 0 0110 0v3" stroke="#06b6d4" stroke-width="1.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
                <p class="text-muted" style="margin-bottom: 10px;">No account?</p>
                <a href="register.php" class="btn btn-success btn-block">Register Now</a>
            </div>

            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h4>📝 Demo users and Admins.</h4>
                <p>
                    <strong>Admin Account:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>admin123</code>
                </p>
                <p>
                    <strong>User Account:</strong><br>
                    Username: <code>user1</code><br>
                    Password: <code>user123</code>
                </p>
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

<?php
require_once 'session.php';

redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        $error = 'Username is required';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } else {
        if (login_user($username, $password)) {
            if (is_admin()) {
                header("Location: admin.php");
            } else {
                header("Location: booking.php");
            }
            exit();
        } else {
            $error = 'Invalid username or password';
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
</head>
<body>
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
            <h2 class="text-center" style="margin-bottom: 30px;">Login</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        placeholder="Enter your username"
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
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
                <p class="text-muted" style="margin-bottom: 10px;">Don't have an account?</p>
                <a href="register.php" class="btn btn-success btn-block">Create an Account</a>
            </div>

            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h4>📝 Demo Credentials</h4>
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

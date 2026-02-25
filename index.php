<?php
require_once 'session.php';

// Handle logout first (before any output)
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
}

redirect_if_logged_in();

$error = '';
$success = '';
$teacher_pending = false;

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
        // Check if teacher account is pending approval
        $teacher_status = check_teacher_login_status($username);
        if ($teacher_status === 'pending') {
            $error = 'Your teacher account is pending admin approval. Please wait for verification.';
            $teacher_pending = true;
        } elseif (login_user($username, $password)) {
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
    <title>Login - School Booking System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <!-- Animated Water Bubbles Background -->
    <div class="bubble-container">
        <div class="bubble bubble-1"></div>
        <div class="bubble bubble-2"></div>
        <div class="bubble bubble-3"></div>
        <div class="bubble bubble-4"></div>
        <div class="bubble bubble-5"></div>
        <div class="bubble bubble-6"></div>
        <div class="bubble bubble-7"></div>
        <div class="bubble bubble-8"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar-transparent">
        <div class="container">
            <a href="index.php" class="nav-brand-logo">School Booking</a>
            <ul class="nav-links">
                <li><a href="register.php">Register</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="login-hero">
        <div class="container">
            <div class="login-grid">
                <!-- Left Section -->
                <div class="login-hero-section">
                    <h1 class="hero-title">WE BUILD DIGITAL</h1>
                    <p class="hero-subtitle">School Facility Management System</p>
                    <p class="hero-description">Modern booking system for managing school facilities efficiently</p>
                    
                    <div class="hero-features">
                        <div class="feature-item">
                            <span class="feature-icon">📅</span>
                            <span>Easy Booking</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">✅</span>
                            <span>Quick Approval</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">👥</span>
                            <span>Multi-role Access</span>
                        </div>
                    </div>
                </div>

                <!-- Right Section - Login Form -->
                <div class="login-form-section">
                    <div class="form-card">
                        <h2 class="form-title">Welcome Back</h2>
                        <p class="form-subtitle">Login to your account</p>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <span class="alert-icon">✓</span>
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-error">
                                <span class="alert-icon"><?php echo $teacher_pending ? '!' : '✗'; ?></span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="loginForm" class="login-form">
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    required 
                                    placeholder="Enter your username"
                                    autofocus
                                    class="form-input"
                                >
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    placeholder="Enter your password"
                                    class="form-input"
                                >
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
                        </form>

                        <div class="form-footer">
                            <p>Don't have an account? <a href="register.php" class="link-primary">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>

<?php
require_once 'session.php';

redirect_if_logged_in();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $error = 'Full name is required';
    } elseif (empty($username)) {
        $error = 'Username is required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } elseif (empty($email)) {
        $error = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username already exists
        $username_check = mysqli_real_escape_string($conn, $username);
        $query = "SELECT user_id FROM users WHERE username = '$username_check' OR email = '" . mysqli_real_escape_string($conn, $email) . "'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $error = 'Username or email already exists';
        } else {
            // Register new user
            if (register_user($name, $username, $email, $password)) {
                // Redirect to login page after successful registration
                header("Location: index.php?registered=true");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Booking System</title>
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
                <li><a href="index.php">Login</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Create Account</h1>
            <p>Register to book school facilities</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br><br>
                    <a href="index.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
            <form method="POST" action="" id="registerForm">
                <div class="form-group input-with-icon">
                    <label for="name">Full Name *</label>
                    <svg class="input-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" stroke="#6366f1" stroke-width="1.2" fill="none"/><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="#8b5cf6" stroke-width="1.2" fill="none"/></svg>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?php echo htmlspecialchars($name ?? ''); ?>"
                        required 
                        placeholder="John Doe"
                    >
                </div>

                <div class="form-group input-with-icon">
                    <label for="username">Username *</label>
                    <svg class="input-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" stroke="#6366f1" stroke-width="1.2" fill="none"/><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="#8b5cf6" stroke-width="1.2" fill="none"/></svg>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($username ?? ''); ?>"
                        required 
                        placeholder="johndoe"
                        minlength="3"
                    >
                    <small class="text-muted">Minimum 3 characters, letters, numbers, and underscores only</small>
                </div>

                <div class="form-group input-with-icon">
                    <label for="email">Email *</label>
                    <svg class="input-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 8l9 6 9-6" stroke="#06b6d4" stroke-width="1.2" fill="none"/></svg>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required 
                        placeholder="john@example.com"
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
                        placeholder="••••••"
                        minlength="6"
                    >
                    <small class="text-muted">Minimum 6 characters</small>
                </div>

                <div class="form-group input-with-icon">
                    <label for="confirm_password">Confirm Password *</label>
                    <svg class="input-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#06b6d4" stroke-width="1.2" fill="none"/></svg>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        placeholder="••••••"
                        minlength="6"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>

            <p class="text-center" style="margin-top: 20px;">
                Already have an account? <a href="index.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Login here</a>
            </p>
            <?php endif; ?>
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

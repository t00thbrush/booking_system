<?php
require_once 'session.php';

redirect_if_logged_in();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'External';
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
                // Prevent users from registering as Admin via public form
                if ($role === 'Admin') $role = 'External';

                if (register_user($name, $username, $email, $password, $role)) {
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
    <title>Register - School Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body class="register-page">
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
            <a href="index.php" class="nav-brand-logo">Booking System</a>
            <ul class="nav-links">
                <li><a href="index.php">Login</a></li>
                <li><button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">🌙</button></li>
            </ul>
        </div>
    </nav>

    <img src="logo.png" class="main-logo">

    <!-- Main Content -->
    <div class="login-hero">
        <div class="container">
            <div class="login-grid">
                <!-- Left Section -->
                <div class="login-hero-section">
                    <h1 class="hero-title">JOIN OUR COMMUNITY</h1>
                    <p class="hero-subtitle">Create Your Account</p>
                    <p class="hero-description">Register as a Student, Teacher, or External user to access our facility booking system</p>
                    
                    <div class="hero-features">
                        <div class="feature-item">
                            <span class="feature-icon">🎓</span>
                            <span>Student Access</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">👨‍🏫</span>
                            <span>Teacher Control</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">👥</span>
                            <span>External Access</span>
                        </div>
                    </div>
                </div>

                <!-- Right Section - Registration Form -->
                <div class="login-form-section">
                    <div class="form-card">
                        <h2 class="form-title">Create Account</h2>
                        <p class="form-subtitle">Register to book school facilities</p>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-error">
                                <span class="alert-icon">✗</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <span class="alert-icon">✓</span>
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($success)): ?>
                        <form method="POST" action="" id="registerForm" class="login-form">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                    required 
                                    placeholder="John Doe"
                                    class="form-input"
                                >
                            </div>

                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo htmlspecialchars($username ?? ''); ?>"
                                    required 
                                    placeholder="johndoe"
                                    minlength="3"
                                    class="form-input"
                                >
                                <small class="form-hint">Min 3 chars, letters, numbers, underscores only</small>
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                    required 
                                    placeholder="john@example.com"
                                    class="form-input"
                                >
                            </div>

                            <div class="form-group">
                                <label for="role" class="form-label">Account Type</label>
                                <select id="role" name="role" required class="form-input">
                                    <option value="Student" <?php if (($role ?? '')=='Student') echo 'selected'; ?>>Student</option>
                                    <option value="Teacher" <?php if (($role ?? '')=='Teacher') echo 'selected'; ?>>Teacher (requires approval)</option>
                                    <option value="External" <?php if (($role ?? '')=='External') echo 'selected'; ?>>External</option>
                                </select>
                                <small class="form-hint">Teachers require admin approval before login</small>
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    placeholder="••••••"
                                    minlength="6"
                                    class="form-input"
                                >
                                <small class="form-hint">Minimum 6 characters</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required 
                                    placeholder="••••••"
                                    minlength="6"
                                    class="form-input"
                                >
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
                        </form>

                        <div class="form-footer">
                            <p>Already have an account? <a href="index.php" class="link-primary">Login here</a></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>

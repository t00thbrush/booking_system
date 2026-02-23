<?php
require_once 'session.php';
require_login();
if (!is_admin()) {
    header('Location: booking.php'); exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'approve_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid && approve_user($uid)) {
            $message = 'User approved successfully.';
        } else {
            $error = 'Failed to approve user.';
        }
    } elseif ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $q = "DELETE FROM users WHERE user_id = $uid";
            if (mysqli_query($conn, $q)) $message = 'User deleted.'; else $error = 'Delete failed.';
        }
    }
}

$users_q = "SELECT user_id, name, username, email, role, is_approved, created_at FROM users ORDER BY created_at DESC";
$users_res = mysqli_query($conn, $users_q);
$users = mysqli_fetch_all($users_res, MYSQLI_ASSOC);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Database - Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav>
        <div class="container">
            <a href="admin.php" class="nav-brand">📅 Admin Dashboard</a>
            <ul class="nav-links">
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="admin_db.php">Database</a></li>
                <li><a href="index.php?logout=true">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2>Database Management</h2>
        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <h3>Users</h3>
        <table class="table">
            <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Approved</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['role']); ?></td>
                    <td><?php echo $u['is_approved'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <?php if (!$u['is_approved'] && $u['role'] === 'Teacher'): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="approve_user">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <button class="btn btn-primary btn-sm">Approve</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline; margin-left:6px;" onsubmit="return confirm('Delete user?')">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="margin-top:30px;">Facilities</h3>
        <p>Use existing admin tools for facilities and bookings management in the dashboard.</p>
    </div>
</body>
</html>

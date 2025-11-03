<?php
// Check if installed first
if (!file_exists(__DIR__ . '/data/xshow.db')) {
    header('Location: install.php');
    exit;
}

require_once 'auth.php';

// Handle logout BEFORE requiring admin
if (isset($_GET['logout'])) {
    Auth::logout();
    exit; // This should never be reached, but just in case
}

Auth::requireAdmin();

require_once 'config.php';
Config::init();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];

        if (strlen($username) < 3 || strlen($password) < 6) {
            $message = 'Username must be at least 3 chars, password at least 6 chars';
        } else {
            $db = Config::getDB();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");

            if ($stmt->execute([$username, $hash, $email, $role])) {
                $message = 'User added successfully';
            } else {
                $message = 'Failed to add user';
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];

        if ($userId != $_SESSION['user_id']) { // Don't delete yourself
            $db = Config::getDB();
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $message = 'User deleted successfully';
            } else {
                $message = 'Failed to delete user';
            }
        } else {
            $message = 'Cannot delete your own account';
        }
    }
}

$users = Config::getUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - XShow</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { margin: 0; color: #2c3e50; }
        .nav { display: flex; gap: 15px; }
        .nav a {
            color: #007bff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .nav a:hover { background: #f8f9fa; }

        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h2 { margin-top: 0; color: #2c3e50; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        input, select {
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th { background: #f8f9fa; font-weight: 600; }
        .role-admin { color: #dc3545; font-weight: bold; }
        .role-user { color: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <h1>👑 XShow Admin Panel</h1>
        <div class="nav">
            <a href="index.php">📁 File Manager</a>
            <a href="?logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Add New User</h2>
            <form method="post">
                <div class="form-grid">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email (optional)">
                    <input type="password" name="password" placeholder="Password" required>
                    <select name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn">Add User</button>
            </form>
        </div>

        <div class="card">
            <h2>User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger"
                                                onclick="return confirm('Delete user <?php echo htmlspecialchars($user['username']); ?>?')">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <em>(You)</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
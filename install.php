<?php
// Error handling for installation
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create logs directory for error logging
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/install.log');

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

session_start();

require_once 'config.php';
Config::init();

// Server requirements check
$requirements = [
    'php_version' => version_compare(PHP_VERSION, '7.4', '>='),
    'pdo' => extension_loaded('pdo'),
    'pdo_sqlite' => extension_loaded('pdo_sqlite'),
    'writable' => is_writable(__DIR__)
];

$allMet = !in_array(false, $requirements);

// Check if already installed
if (Config::isInstalled()) {
    header('Location: index.php');
    exit;
}

$message = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Validation
    if (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $message = 'Password must contain uppercase, lowercase, and number';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address';
    } else {
        // Create admin user
        if (Config::createAdmin($username, $password, $email)) {
            // Create uploads directory
            $uploadsDir = __DIR__ . '/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Set session and redirect
            require_once 'auth.php';
            Auth::login($username, $password);
            header('Location: index.php');
            exit;
        } else {
            $message = 'Failed to create admin user';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install XShow</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        h1 { text-align: center; color: #2c3e50; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
        }
        input:focus { outline: none; border-color: #007bff; }
        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Install XShow</h1>

        <?php if (!$allMet): ?>
            <div class="error">
                <h3>Server Requirements Not Met</h3>
                <ul>
                    <li>PHP 7.4+: <?php echo $requirements['php_version'] ? '✓' : '✗'; ?> (Current: <?php echo PHP_VERSION; ?>)</li>
                    <li>PDO Extension: <?php echo $requirements['pdo'] ? '✓' : '✗'; ?></li>
                    <li>PDO SQLite Extension: <?php echo $requirements['pdo_sqlite'] ? '✓' : '✗'; ?></li>
                    <li>Writable Directory: <?php echo $requirements['writable'] ? '✓' : '✗'; ?></li>
                </ul>
                <p>Please contact your server administrator to fix these issues.</p>
            </div>
        <?php elseif ($message): ?>
            <div class="error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($allMet): ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Admin Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="password-hint">At least 8 characters with uppercase, lowercase, and numbers</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">Install XShow</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
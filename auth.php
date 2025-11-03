<?php
// Simple authentication functions
class Auth {
    public static function requireLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    public static function requireAdmin() {
        self::requireLogin();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: index.php');
            exit;
        }
    }

    public static function login($username, $password) {
        try {
            require_once 'config.php';
            Config::init();

            $userId = Config::verifyUser($username, $password);
            if ($userId) {
                $user = Config::getUser($userId);
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
        }
        return false;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        header('Location: login.php');
        exit;
    }

    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;

        try {
            require_once 'config.php';
            Config::init();
            return Config::getUser($_SESSION['user_id']);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
?>
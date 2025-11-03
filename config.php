<?php
// XShow Configuration
class Config {
    private static $db;

    public static function init() {
        self::initDatabase();
    }

    private static function initDatabase() {
        if (self::$db) return;

        $dbPath = __DIR__ . '/data/xshow.db';

        // Create data directory
        $dataDir = dirname($dbPath);
        if (!is_dir($dataDir)) {
            if (!mkdir($dataDir, 0755, true)) {
                throw new Exception("Cannot create data directory: $dataDir");
            }
        }

        // Check if directory is writable
        if (!is_writable($dataDir)) {
            throw new Exception("Data directory is not writable: $dataDir");
        }

        try {
            self::$db = new PDO('sqlite:' . $dbPath);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }

        // Create tables
        self::$db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                email TEXT,
                role TEXT DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            )
        ");

        self::$db->exec("
            CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                original_name TEXT NOT NULL,
                path TEXT NOT NULL,
                size INTEGER,
                mime_type TEXT,
                uploaded_by INTEGER,
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (uploaded_by) REFERENCES users(id)
            )
        ");

        self::$db->exec("
            CREATE TABLE IF NOT EXISTS folders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                path TEXT NOT NULL,
                parent_id INTEGER,
                created_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_id) REFERENCES folders(id),
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
        ");
    }

    public static function getDB() {
        if (!self::$db) {
            self::initDatabase();
        }
        return self::$db;
    }

    public static function isInstalled() {
        $dbPath = __DIR__ . '/data/xshow.db';
        if (!file_exists($dbPath)) {
            return false;
        }

        try {
            $db = self::getDB();
            $stmt = $db->query("SELECT COUNT(*) FROM users");
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function createAdmin($username, $password, $email = '') {
        $db = self::getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, 'admin')");
        return $stmt->execute([$username, $hash, $email]);
    }

    public static function verifyUser($username, $password) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user['id']]);
            return $user['id'];
        }
        return false;
    }

    public static function getUser($userId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getUsers() {
        $db = self::getDB();
        $stmt = $db->query("SELECT id, username, email, role, created_at, last_login FROM users ORDER BY username");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
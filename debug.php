<?php
// Debug script for XShow 500 error
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>XShow Debug Information</h1>";
echo "<pre>";

// PHP Version
echo "PHP Version: " . phpversion() . "\n";

// Extensions
$required = ['pdo', 'pdo_sqlite', 'session'];
echo "\nExtensions:\n";
foreach ($required as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✓" : "✗") . "\n";
}

// Current directory
echo "\nCurrent Directory: " . __DIR__ . "\n";

// Directory permissions
$dirs = ['data', 'uploads', 'config'];
echo "\nDirectory Check:\n";
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $writable = is_writable($path);
    echo "$dir: " . ($exists ? "exists" : "missing") . " - " . ($writable ? "writable" : "not writable") . "\n";
}

// Try to create directories
echo "\nCreating directories:\n";
foreach (['data', 'uploads'] as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        $created = mkdir($path, 0755, true);
        echo "Created $dir: " . ($created ? "✓" : "✗") . "\n";
    } else {
        echo "$dir already exists\n";
    }
}

// Test database creation
echo "\nTesting database:\n";
try {
    $dbPath = __DIR__ . '/data/xshow.db';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create a simple test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, data TEXT)");
    $pdo->exec("INSERT INTO test (data) VALUES ('test_data')");

    $stmt = $pdo->query("SELECT * FROM test");
    $result = $stmt->fetchAll();

    echo "Database test: ✓ (" . count($result) . " rows)\n";

    // Clean up test
    unlink($dbPath);

} catch (Exception $e) {
    echo "Database test: ✗ - " . $e->getMessage() . "\n";
}

// Test file operations
echo "\nTesting file operations:\n";
$testFile = __DIR__ . '/test.txt';
$written = file_put_contents($testFile, 'test content');
$read = file_get_contents($testFile);
$deleted = unlink($testFile);

echo "File write: " . ($written !== false ? "✓" : "✗") . "\n";
echo "File read: " . ($read === 'test content' ? "✓" : "✗") . "\n";
echo "File delete: " . ($deleted ? "✓" : "✗") . "\n";

echo "</pre>";
echo "<p><a href='index.php'>← Back to XShow</a></p>";
?>
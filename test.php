<?php
// Simple test to check if application loads
echo "<h1>XShow Load Test</h1>";

try {
    echo "<p>✓ PHP Version: " . phpversion() . "</p>";

    // Test config loading
    echo "<p>Testing config.php...</p>";
    require_once 'config.php';
    echo "<p>✓ Config loaded</p>";

    // Test database initialization
    echo "<p>Testing database...</p>";
    Config::init();
    echo "<p>✓ Database initialized</p>";

    // Test if installed
    $installed = Config::isInstalled();
    echo "<p>✓ Is installed: " . ($installed ? 'Yes' : 'No') . "</p>";

    // Test auth loading
    echo "<p>Testing auth.php...</p>";
    require_once 'auth.php';
    echo "<p>✓ Auth loaded</p>";

    // Test file manager
    echo "<p>Testing file_manager.php...</p>";
    require_once 'file_manager.php';
    $fm = new FileManager();
    echo "<p>✓ File manager loaded</p>";

    echo "<h2>✅ All components loaded successfully!</h2>";
    echo "<p><a href='install.php'>Go to Install</a> | <a href='index.php'>Go to App</a></p>";

} catch (Exception $e) {
    echo "<h2>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
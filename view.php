<?php
// Secure file viewer - serves files from home directory
session_start();

require_once 'auth.php';
Auth::requireLogin();

require_once 'file_manager.php';

if (!isset($_GET['file'])) {
    http_response_code(404);
    die('File not specified');
}

$fileManager = new FileManager($_SESSION['user_id']);
$requestedFile = $_GET['file'];

// Construct full path
$baseDir = dirname(__DIR__); // home directory
$filePath = $baseDir . '/' . $requestedFile;

// Security checks
$realPath = realpath($filePath);
$realBase = realpath($baseDir);
$appDir = realpath(__DIR__);

// Check if file exists
if (!$realPath || !file_exists($realPath)) {
    http_response_code(404);
    die('File not found');
}

// Check if within allowed directory
if (strpos($realPath, $realBase) !== 0) {
    http_response_code(403);
    die('Access denied: Outside allowed directory');
}

// Check if NOT trying to access xshow directory
if ($realPath === $appDir || strpos($realPath, $appDir . DIRECTORY_SEPARATOR) === 0) {
    http_response_code(403);
    die('Access denied: Protected directory');
}

// Check if it's a file (not directory)
if (!is_file($realPath)) {
    http_response_code(403);
    die('Not a file');
}

// Get file info
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $realPath);
finfo_close($finfo);

$filename = basename($realPath);
$filesize = filesize($realPath);

// Set headers for file download/viewing
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $filesize);

// For common viewable types, display inline; otherwise force download
$viewableTypes = ['image/', 'text/', 'application/pdf', 'video/', 'audio/'];
$isViewable = false;

foreach ($viewableTypes as $type) {
    if (strpos($mimeType, $type) === 0) {
        $isViewable = true;
        break;
    }
}

if ($isViewable) {
    header('Content-Disposition: inline; filename="' . $filename . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

// Output file
readfile($realPath);
exit;
?>

<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if installed - simple file check first
$dbFile = __DIR__ . '/data/xshow.db';
if (!file_exists($dbFile)) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>XShow - Not Installed</title>";
    echo "<style>body{font-family:sans-serif;text-align:center;padding:50px;background:#f5f5f5;}";
    echo "a{color:#007bff;text-decoration:none;margin:10px;display:inline-block;}</style></head><body>";
    echo "<h1>XShow - Not Installed</h1>";
    echo "<p><a href='install.php'>Click here to install</a></p>";
    echo "<p><a href='test.php'>Run tests</a></p>";
    echo "</body></html>";
    exit;
}

// Now load the application
require_once 'config.php';
require_once 'auth.php';

// Handle logout BEFORE requiring login
if (isset($_GET['logout'])) {
    Auth::logout();
    exit; // This should never be reached, but just in case
}

Auth::requireLogin();

require_once 'file_manager.php';
$fileManager = new FileManager($_SESSION['user_id']);

// Handle directory navigation
if (isset($_GET['dir'])) {
    $fileManager->setCurrentPath($_GET['dir']);
    header('Location: index.php');
    exit;
}

// Handle various actions
$message = '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$editingFile = isset($_GET['edit']) ? basename($_GET['edit']) : null;
$fileContent = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $result = $fileManager->uploadFiles($_FILES['files']);
    if ($result['uploaded'] > 0) {
        $message = "Uploaded {$result['uploaded']} file(s) successfully";
        if (!empty($result['errors'])) {
            $message .= ". Errors: " . implode(', ', $result['errors']);
        }
    } else {
        $message = "Upload failed: " . implode(', ', $result['errors']);
    }
}

// Handle file save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_file']) && isset($_POST['filename'])) {
    $filename = basename($_POST['filename']);
    $content = $_POST['file_content'] ?? '';

    if ($fileManager->saveFile($filename, $content)) {
        $message = "File saved successfully";
        $editingFile = null; // Exit edit mode
    } else {
        $message = "Failed to save file";
    }
}

// Handle search
$searchResults = [];
if (!empty($searchQuery)) {
    $searchResults = $fileManager->searchFiles($searchQuery);
}

// Load file for editing
if ($editingFile) {
    $fileData = $fileManager->readFile($editingFile);
    if ($fileData) {
        $fileContent = $fileData;
    } else {
        $message = "Cannot edit this file type or file not found";
        $editingFile = null;
    }
}

// Handle folder creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folder_name'])) {
    if ($fileManager->createFolder($_POST['folder_name'])) {
        $message = "Folder created successfully";
    } else {
        $message = "Failed to create folder";
    }
}

// Handle .md file creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['md_filename'])) {
    $filename = trim($_POST['md_filename']);
    
    // Auto-add .md extension if not present
    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'md') {
        $filename .= '.md';
    }
    
    if ($fileManager->createFile($filename, '')) {
        $message = "Markdown file created successfully";
        // Redirect to edit mode
        header('Location: index.php?edit=' . urlencode($filename));
        exit;
    } else {
        $message = "Failed to create file - file may already exist";
    }
}

// Handle file/folder deletion
if (isset($_GET['delete'])) {
    $item = basename($_GET['delete']);
    if ($fileManager->deleteItem($item)) {
        $message = "Item deleted successfully";
    } else {
        $message = "Failed to delete item";
    }
    header('Location: index.php');
    exit;
}

// Handle file/folder rename
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_item']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $oldName = basename($_POST['old_name']);
    $newName = basename($_POST['new_name']);
    
    if ($fileManager->renameItem($oldName, $newName)) {
        $message = "Item renamed successfully";
    } else {
        $message = "Failed to rename item";
    }
    header('Location: index.php');
    exit;
}

// Handle file/folder move
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_item']) && isset($_POST['item_name']) && isset($_POST['destination'])) {
    $itemName = basename($_POST['item_name']);
    $destination = $_POST['destination'];
    
    if ($fileManager->moveItem($itemName, $destination)) {
        $message = "Item moved successfully";
    } else {
        $message = "Failed to move item";
    }
    header('Location: index.php');
    exit;
}

$files = $fileManager->listFiles($searchQuery);
$breadcrumb = $fileManager->getBreadcrumb();
$currentUser = Auth::getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XShow File Manager</title>
    <?php if ($editingFile && strtolower(pathinfo($editingFile, PATHINFO_EXTENSION)) === 'md'): ?>
    <link rel="stylesheet" href="assets/js/lib/simplemde.min.css">
    <script src="assets/js/lib/simplemde.min.js"></script>
    <?php endif; ?>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        /* Header */
        .header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h1 { margin: 0; color: #2c3e50; font-size: 2em; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .logout-btn:hover { background: #c0392b; }

        /* Messages */
        .message {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .message.success { border-left: 4px solid #28a745; color: #155724; }
        .message.error { border-left: 4px solid #dc3545; color: #721c24; }

        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .breadcrumb a:hover { 
            background: #e7f3ff;
            text-decoration: none;
        }
        .breadcrumb-separator {
            color: #6c757d;
            margin: 0 5px;
        }

        /* Actions Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .action-card h3 { margin-top: 0; color: #2c3e50; font-size: 1.2em; }

        /* Forms */
        .upload-form input[type="file"],
        .folder-form input[type="text"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
        }
        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,123,255,0.3); }
        .btn-secondary {
            background: linear-gradient(45deg, #28a745, #1e7e34);
            color: white;
        }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(40,167,69,0.3); }

        /* Files Section */
        .files-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .files-section h3 { margin-top: 0; color: #2c3e50; font-size: 1.3em; }

        /* Search Section */
        .search-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-form input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
        }
        .btn-search {
            padding: 12px 20px;
            background: linear-gradient(45deg, #17a2b8, #138496);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-clear {
            color: #6c757d;
            text-decoration: none;
            padding: 12px;
        }

        /* Editor Section */
        .editor-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .file-editor {
            width: 100%;
            height: 400px;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            line-height: 1.5;
            resize: vertical;
        }
        .editor-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        /* Search Results */
        .search-results {
            background: #e7f3ff;
            color: #004085;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #0066cc;
        }

        /* Files Grid */
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .file-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }
        .file-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            background: white;
            border-color: #007bff;
        }
        .file-icon {
            font-size: 2.5em;
            margin-right: 15px;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }
        .file-card:hover .file-icon {
            transform: scale(1.1);
        }
        .file-info {
            flex: 1;
            min-width: 0;
        }
        .file-name {
            display: block;
            font-weight: 600;
            color: #007bff;
            text-decoration: none;
            margin-bottom: 5px;
            word-break: break-all;
            transition: color 0.3s ease;
        }
        .file-name:hover { 
            text-decoration: underline;
            color: #0056b3;
        }
        .file-meta {
            font-size: 12px;
            color: #6c757d;
        }
        .file-actions {
            display: flex;
            gap: 5px;
            margin-left: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .file-card:hover .file-actions {
            opacity: 1;
        }
        .file-action {
            font-size: 18px;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 6px;
            background: rgba(0,0,0,0.05);
        }
        .file-action:hover { 
            transform: scale(1.2);
            background: rgba(0,123,255,0.1);
        }
        .file-action.delete:hover { 
            background: rgba(220, 53, 69, 0.1);
            transform: scale(1.2);
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
        }

        /* Admin Link */
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(220,53,69,0.3);
            transition: transform 0.3s;
        }
        .admin-link:hover { transform: translateY(-2px); }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .actions-grid { grid-template-columns: 1fr; }
            .files-grid { grid-template-columns: 1fr; }
            .file-card { padding: 12px; }
            .tree-sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 1001;
            }
            .tree-sidebar.active {
                left: 0;
            }
            .tree-toggle-btn {
                display: block !important;
            }
            .main-wrapper {
                flex-direction: column;
            }
            .content-area {
                margin-left: 0 !important;
            }
        }

        /* Main Wrapper */
        .main-wrapper {
            display: flex;
            gap: 20px;
            position: relative;
        }

        /* Tree Sidebar */
        .tree-sidebar {
            width: 280px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 0;
            max-height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
            position: sticky;
            top: 20px;
            flex-shrink: 0;
        }

        .tree-header {
            padding: 20px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            border-radius: 12px 12px 0 0;
        }

        .tree-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.1em;
        }

        .tree-toggle {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
            display: none;
            padding: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .tree-toggle:hover {
            background: #f0f0f0;
            color: #333;
        }

        .tree-content {
            padding: 10px 0;
        }

        .tree-item {
            user-select: none;
        }

        .tree-node {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tree-node:hover {
            background: #f8f9fa;
        }

        .tree-expander {
            cursor: pointer;
            margin-right: 5px;
            font-size: 12px;
            color: #6c757d;
            width: 16px;
            display: inline-block;
            transition: transform 0.2s ease;
        }

        .tree-expander.expanded {
            transform: rotate(90deg);
        }

        .tree-expander-placeholder {
            width: 16px;
            display: inline-block;
            margin-right: 5px;
        }

        .tree-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            flex: 1;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .tree-link:hover {
            background: #e7f3ff;
            color: #007bff;
        }

        .tree-icon {
            margin-right: 8px;
            font-size: 16px;
        }

        .tree-label {
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tree-children {
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .tree-toggle-btn {
            display: none;
            position: fixed;
            bottom: 80px;
            left: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #28a745, #1e7e34);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(40,167,69,0.4);
            z-index: 1000;
            transition: all 0.3s;
        }

        .tree-toggle-btn:hover {
            transform: scale(1.1);
        }

        /* Content Area */
        .content-area {
            flex: 1;
            min-width: 0;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .modal-body {
            margin-bottom: 20px;
        }
        .modal-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 10px;
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-cancel {
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>📁 XShow File Manager</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</span>
                <a href="?logout" class="logout-btn">Logout</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'success') !== false || strpos($message, 'Uploaded') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb">
            <?php
            foreach ($breadcrumb as $index => $crumb) {
                echo "<a href='?dir=" . urlencode($crumb['path']) . "'>" . htmlspecialchars($crumb['name']) . "</a>";
                if ($index < count($breadcrumb) - 1) echo "<span class='breadcrumb-separator'>›</span>";
            }
            ?>
        </nav>

        <!-- Search Bar -->
        <div class="search-section">
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="Search files and folders..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="btn-search">🔍 Search</button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="index.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Main Content Wrapper with Sidebar -->
        <div class="main-wrapper">
            <!-- Folder Tree Sidebar -->
            <div class="tree-sidebar" id="treeSidebar">
                <div class="tree-header">
                    <h3>📁 Folders</h3>
                    <button class="tree-toggle" id="treeSidebarClose">×</button>
                </div>
                <div class="tree-content">
                    <div class="tree-item">
                        <a href="?dir=" class="tree-link">
                            <span class="tree-icon">🏠</span>
                            <span class="tree-label">Home</span>
                        </a>
                    </div>
                    <?php
                    $folderTree = $fileManager->getFolderTree();
                    
                    function renderTreeNode($node, $level = 0) {
                        $hasChildren = !empty($node['children']);
                        $indent = $level * 20;
                        
                        echo '<div class="tree-item" data-path="' . htmlspecialchars($node['path']) . '">';
                        echo '<div class="tree-node" style="padding-left: ' . $indent . 'px;">';
                        
                        if ($hasChildren) {
                            echo '<span class="tree-expander" onclick="toggleNode(this, event)">▶</span>';
                        } else {
                            echo '<span class="tree-expander-placeholder"></span>';
                        }
                        
                        echo '<a href="?dir=' . urlencode($node['path']) . '" class="tree-link" onclick="expandFolderOnClick(event, this)">';
                        echo '<span class="tree-icon">📁</span>';
                        echo '<span class="tree-label">' . htmlspecialchars($node['name']) . '</span>';
                        echo '</a>';
                        echo '</div>';
                        
                        if ($hasChildren) {
                            echo '<div class="tree-children" style="display: none;">';
                            foreach ($node['children'] as $child) {
                                renderTreeNode($child, $level + 1);
                            }
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    }
                    
                    foreach ($folderTree as $node) {
                        renderTreeNode($node);
                    }
                    ?>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="content-area" id="contentArea">

        <div class="actions-grid">
            <!-- Upload Files -->
            <div class="action-card">
                <h3>📤 Upload Files</h3>
                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <input type="file" name="files[]" multiple required>
                    <button type="submit" class="btn-primary">Upload Files</button>
                </form>
            </div>

            <!-- Create Folder -->
            <div class="action-card">
                <h3>📁 Create Folder</h3>
                <form method="post" class="folder-form">
                    <input type="text" name="folder_name" placeholder="Folder name" required>
                    <button type="submit" class="btn-secondary">Create Folder</button>
                </form>
            </div>

            <!-- Create Markdown File -->
            <div class="action-card">
                <h3>📝 Create Markdown File</h3>
                <form method="post" class="folder-form">
                    <input type="text" name="md_filename" placeholder="Filename (e.g., notes.md)" required>
                    <button type="submit" class="btn-secondary">Create File</button>
                </form>
            </div>
        </div>

        <!-- File Editor -->
        <?php if ($editingFile && $fileContent): ?>
            <div class="editor-section">
                <h3>📝 Editing: <?php echo htmlspecialchars($editingFile); ?></h3>
                <form method="post" class="editor-form">
                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($editingFile); ?>">
                    <textarea name="file_content" class="file-editor"><?php echo htmlspecialchars($fileContent['content']); ?></textarea>
                    <div class="editor-actions">
                        <button type="submit" name="save_file" class="btn-primary">💾 Save File</button>
                        <a href="index.php" class="btn-secondary">❌ Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Files & Folders List -->
        <div class="files-section">
            <h3><?php echo !empty($searchQuery) ? 'Search Results' : 'Files & Folders'; ?></h3>

            <?php if (!empty($searchQuery) && !empty($searchResults)): ?>
                <div class="search-results">
                    <p>Found <?php echo count($searchResults); ?> result(s) for "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                </div>
            <?php endif; ?>

            <div class="files-grid">
                <?php
                $displayFiles = !empty($searchQuery) ? $searchResults : $files;

                if (empty($displayFiles)): ?>
                    <div class="empty-state">
                        <?php if (!empty($searchQuery)): ?>
                            No files found matching "<?php echo htmlspecialchars($searchQuery); ?>"
                        <?php else: ?>
                            This folder is empty
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($displayFiles as $file): ?>
                        <div class="file-card">
                            <div class="file-icon">
                                <?php 
                                if (isset($file['is_dir']) && $file['is_dir']) {
                                    echo '📁';
                                } else {
                                    echo $fileManager->getFileIcon($file['name']);
                                }
                                ?>
                            </div>
                            <div class="file-info">
                                <?php if (isset($file['is_dir']) && $file['is_dir']): ?>
                                    <?php 
                                    $currentPath = isset($_SESSION['current_path']) ? trim($_SESSION['current_path'], '/') : '';
                                    $newPath = $currentPath ? $currentPath . '/' . $file['name'] : $file['name'];
                                    ?>
                                    <a href="?dir=<?php echo urlencode($newPath); ?>" class="file-name">
                                        <?php echo htmlspecialchars($file['name']); ?>
                                    </a>
                                    <div class="file-meta">Folder • <?php echo date('M d, Y H:i', $file['modified']); ?></div>
                                <?php else: ?>
                                    <?php if (!empty($searchQuery)): ?>
                                        <?php if (isset($file['is_dir']) && $file['is_dir']): ?>
                                            <a href="?dir=<?php echo urlencode($file['path']); ?>" class="file-name">
                                                <?php echo htmlspecialchars($file['name']); ?>
                                            </a>
                                            <div class="file-meta">📍 Folder in <?php echo htmlspecialchars(dirname($file['path'])); ?> • <?php echo date('M d, Y H:i', $file['modified']); ?></div>
                                        <?php else: ?>
                                            <a href="<?php echo $fileManager->getFileUrl($file['path']); ?>" class="file-name" target="_blank">
                                                <?php echo htmlspecialchars($file['name']); ?>
                                            </a>
                                            <div class="file-meta">📍 <?php echo htmlspecialchars(dirname($file['path'])); ?> • <?php echo $fileManager->formatFileSize($file['size']); ?> • <?php echo date('M d, Y H:i', $file['modified']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?php echo $fileManager->getFileUrl($file['path']); ?>" class="file-name" target="_blank">
                                            <?php echo htmlspecialchars($file['name']); ?>
                                        </a>
                                        <div class="file-meta"><?php echo $fileManager->formatFileSize($file['size']); ?> • <?php echo date('M d, Y H:i', $file['modified']); ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="file-actions">
                                <?php if (!isset($file['is_dir']) || !$file['is_dir']): ?>
                                    <?php
                                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                    $canEdit = in_array($ext, ['txt', 'md', 'html', 'css', 'js', 'php', 'json', 'xml', 'csv']);
                                    if ($canEdit):
                                    ?>
                                        <a href="?edit=<?php echo urlencode($file['name']); ?>" class="file-action" title="Edit">✏️</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="#" class="file-action rename-btn" data-name="<?php echo htmlspecialchars($file['name']); ?>" title="Rename">✎</a>
                                <a href="#" class="file-action move-btn" data-name="<?php echo htmlspecialchars($file['name']); ?>" title="Move">📂</a>
                                <a href="?delete=<?php echo urlencode($file['name']); ?>" class="file-action delete"
                                   onclick="return confirm('Delete <?php echo htmlspecialchars($file['name']); ?>?')" title="Delete">🗑️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Toggle Button for Tree Sidebar (Mobile) -->
        <button class="tree-toggle-btn" id="treeSidebarToggle">📂</button>
    </div>

    <!-- Rename Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Rename Item</div>
            <form method="post" id="renameForm">
                <div class="modal-body">
                    <label for="newNameInput">New name:</label>
                    <input type="hidden" name="old_name" id="oldNameInput">
                    <input type="text" name="new_name" id="newNameInput" class="modal-input" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeRenameModal()">Cancel</button>
                    <button type="submit" name="rename_item" class="btn-primary">Rename</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Move Modal -->
    <div id="moveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Move Item</div>
            <form method="post" id="moveForm">
                <div class="modal-body">
                    <p>Move <strong id="moveItemName"></strong> to:</p>
                    <input type="hidden" name="item_name" id="moveItemInput">
                    <select name="destination" id="destinationSelect" class="modal-input" required>
                        <?php
                        $allFolders = $fileManager->getAllFolders();
                        foreach ($allFolders as $folder):
                        ?>
                            <option value="<?php echo htmlspecialchars($folder['path']); ?>">
                                <?php echo htmlspecialchars($folder['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeMoveModal()">Cancel</button>
                    <button type="submit" name="move_item" class="btn-primary">Move</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (Auth::isAdmin()): ?>
        <a href="admin.php" class="admin-link" title="Admin Panel">👑 Admin</a>
    <?php endif; ?>

    <script>
        // Save confirmation for file editor
        document.addEventListener('DOMContentLoaded', function() {
            var editorForm = document.querySelector('.editor-form');
            if (editorForm) {
                editorForm.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to save this file?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }

            // Rename functionality
            var renameButtons = document.querySelectorAll('.rename-btn');
            renameButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var itemName = this.getAttribute('data-name');
                    openRenameModal(itemName);
                });
            });

            // Move functionality
            var moveButtons = document.querySelectorAll('.move-btn');
            moveButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var itemName = this.getAttribute('data-name');
                    openMoveModal(itemName);
                });
            });
        });

        function openRenameModal(itemName) {
            document.getElementById('oldNameInput').value = itemName;
            document.getElementById('newNameInput').value = itemName;
            document.getElementById('renameModal').style.display = 'block';
            document.getElementById('newNameInput').focus();
            document.getElementById('newNameInput').select();
        }

        function closeRenameModal() {
            document.getElementById('renameModal').style.display = 'none';
        }

        function openMoveModal(itemName) {
            document.getElementById('moveItemInput').value = itemName;
            document.getElementById('moveItemName').textContent = itemName;
            document.getElementById('moveModal').style.display = 'block';
        }

        function closeMoveModal() {
            document.getElementById('moveModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var renameModal = document.getElementById('renameModal');
            var moveModal = document.getElementById('moveModal');
            if (event.target == renameModal) {
                closeRenameModal();
            }
            if (event.target == moveModal) {
                closeMoveModal();
            }
        }

        // Tree sidebar functionality
        function toggleNode(expander, event) {
            if (event) {
                event.stopPropagation();
            }
            
            var treeItem = expander.closest('.tree-item');
            var children = treeItem.querySelector('.tree-children');
            
            if (children) {
                var isExpanded = children.style.display !== 'none';
                
                if (isExpanded) {
                    children.style.display = 'none';
                    expander.classList.remove('expanded');
                } else {
                    children.style.display = 'block';
                    expander.classList.add('expanded');
                }
                
                // Save state
                saveTreeState();
            }
        }

        // Expand folder when clicking on folder link
        function expandFolderOnClick(event, linkElement) {
            var treeItem = linkElement.closest('.tree-item');
            var expander = treeItem.querySelector('.tree-expander');
            var children = treeItem.querySelector('.tree-children');
            
            // If folder has children and is not expanded, expand it
            if (expander && children && children.style.display === 'none') {
                children.style.display = 'block';
                expander.classList.add('expanded');
                
                // Save state before navigation
                saveTreeState();
            }
            
            // Let the link navigate normally
            return true;
        }

        // Save tree state to sessionStorage
        function saveTreeState() {
            var expandedPaths = [];
            var expandedItems = document.querySelectorAll('.tree-item .tree-expander.expanded');
            
            expandedItems.forEach(function(expander) {
                var treeItem = expander.closest('.tree-item');
                var path = treeItem.getAttribute('data-path');
                if (path) {
                    expandedPaths.push(path);
                }
            });
            
            sessionStorage.setItem('xshow-tree-expanded', JSON.stringify(expandedPaths));
        }

        // Restore tree state from sessionStorage
        function restoreTreeState() {
            var savedState = sessionStorage.getItem('xshow-tree-expanded');
            if (!savedState) return;
            
            try {
                var expandedPaths = JSON.parse(savedState);
                
                expandedPaths.forEach(function(path) {
                    var treeItem = document.querySelector('.tree-item[data-path="' + path + '"]');
                    if (treeItem) {
                        var expander = treeItem.querySelector('.tree-expander');
                        var children = treeItem.querySelector('.tree-children');
                        
                        if (expander && children) {
                            children.style.display = 'block';
                            expander.classList.add('expanded');
                        }
                    }
                });
            } catch (e) {
                console.error('Error restoring tree state:', e);
            }
        }

        // Restore tree state on page load
        document.addEventListener('DOMContentLoaded', function() {
            restoreTreeState();
        });

        // Tree sidebar toggle for mobile
        var treeSidebarToggle = document.getElementById('treeSidebarToggle');
        var treeSidebar = document.getElementById('treeSidebar');
        var treeSidebarClose = document.getElementById('treeSidebarClose');

        if (treeSidebarToggle) {
            treeSidebarToggle.addEventListener('click', function() {
                treeSidebar.classList.add('active');
            });
        }

        if (treeSidebarClose) {
            treeSidebarClose.addEventListener('click', function() {
                treeSidebar.classList.remove('active');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (treeSidebar && treeSidebar.classList.contains('active')) {
                    if (!treeSidebar.contains(event.target) && event.target !== treeSidebarToggle) {
                        treeSidebar.classList.remove('active');
                    }
                }
            }
        });

        // Show close button on mobile
        if (window.innerWidth <= 768) {
            var treeToggle = document.querySelector('.tree-toggle');
            if (treeToggle) {
                treeToggle.style.display = 'block';
            }
        }

        window.addEventListener('resize', function() {
            var treeToggle = document.querySelector('.tree-toggle');
            if (window.innerWidth <= 768) {
                if (treeToggle) {
                    treeToggle.style.display = 'block';
                }
            } else {
                if (treeToggle) {
                    treeToggle.style.display = 'none';
                }
                if (treeSidebar) {
                    treeSidebar.classList.remove('active');
                }
            }
        });
    </script>

    <?php if ($editingFile && strtolower(pathinfo($editingFile, PATHINFO_EXTENSION)) === 'md'): ?>
    <script>
        var simplemde = new SimpleMDE({ 
            element: document.querySelector('.file-editor'),
            spellChecker: false,
            autosave: {
                enabled: true,
                uniqueId: "xshow-md-<?php echo md5($editingFile . $fileManager->getCurrentPath()); ?>",
                delay: 1000,
            },
            toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "image", "|", "preview", "side-by-side", "fullscreen", "|", "guide"]
        });
    </script>
    <?php endif; ?>
</body>
</html>
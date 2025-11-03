<?php
// File Manager Class
class FileManager {
    private $baseDir;
    private $currentUser;
    private $appDir; // XShow application directory
    private $protectedDirs = []; // Directories that cannot be accessed/deleted

    public function __construct($userId = null) {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set base directory to parent directory (home folder)
        $this->appDir = __DIR__; // xshow directory
        $this->baseDir = dirname(__DIR__); // parent directory (home)
        $this->currentUser = $userId;
        
        // Protect the xshow application directory from being accessed/deleted
        $this->protectedDirs = [
            basename($this->appDir), // 'xshow' folder name
            '.git',
            '.htaccess',
            'node_modules',
            'vendor'
        ];

        // Ensure base directory is writable
        if (!is_writable($this->baseDir)) {
            throw new Exception("Home directory is not writable");
        }
    }
    
    // Check if a directory or file is protected
    private function isProtected($name) {
        $basename = basename($name);
        return in_array($basename, $this->protectedDirs);
    }
    
    // Check if path is within allowed boundaries
    private function isPathAllowed($path) {
        $realPath = realpath($path);
        $realBase = realpath($this->baseDir);
        $realApp = realpath($this->appDir);
        
        // Path must exist and be within base directory
        if (!$realPath || strpos($realPath, $realBase) !== 0) {
            return false;
        }
        
        // Path must NOT be within or equal to app directory
        if ($realPath === $realApp || strpos($realPath, $realApp . DIRECTORY_SEPARATOR) === 0) {
            return false;
        }
        
        return true;
    }

    public function getCurrentPath() {
        $path = isset($_SESSION['current_path']) ? $_SESSION['current_path'] : '';
        // Clean the path
        $path = trim($path, '/');
        return $this->baseDir . ($path ? '/' . $path : '');
    }

    public function setCurrentPath($path) {
        // Clean and normalize path
        $path = trim($path, '/');
        $fullPath = $this->baseDir . '/' . $path;
        
        // Security: check if path is allowed
        if ($this->isPathAllowed($fullPath)) {
            $_SESSION['current_path'] = $path;
        }
    }

    public function getBreadcrumb() {
        $path = isset($_SESSION['current_path']) ? $_SESSION['current_path'] : '';
        $parts = explode('/', trim($path, '/'));
        $breadcrumb = [['name' => 'Home', 'path' => '']];
        $currentPath = '';

        foreach ($parts as $part) {
            if (!empty($part)) {
                $currentPath .= '/' . $part;
                $breadcrumb[] = ['name' => $part, 'path' => $currentPath];
            }
        }

        return $breadcrumb;
    }

    public function listFiles($search = '') {
        $currentPath = $this->getCurrentPath();
        $files = [];

        if (is_dir($currentPath)) {
            $items = scandir($currentPath);

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                
                // Skip protected directories
                if ($this->isProtected($item)) continue;

                // Apply search filter
                if (!empty($search) && stripos($item, $search) === false) {
                    continue;
                }

                $fullPath = $currentPath . '/' . $item;
                $isDir = is_dir($fullPath);

                $files[] = [
                    'name' => $item,
                    'is_dir' => $isDir,
                    'size' => $isDir ? 0 : filesize($fullPath),
                    'modified' => filemtime($fullPath),
                    'path' => str_replace($this->baseDir . '/', '', $fullPath)
                ];
            }
        }

        return $files;
    }

    public function searchFiles($query, $path = '') {
        $searchPath = empty($path) ? $this->baseDir : $this->baseDir . '/' . $path;
        $results = [];

        if (is_dir($searchPath)) {
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $file) {
                    // Skip protected directories
                    if ($this->isProtected($file->getPathname())) continue;
                    
                    // Skip if inside app directory
                    if (!$this->isPathAllowed($file->getPathname())) continue;
                    
                    $filename = $file->getFilename();
                    if (stripos($filename, $query) !== false) {
                        $relativePath = str_replace($this->baseDir . '/', '', $file->getPathname());
                        $results[] = [
                            'name' => $filename,
                            'path' => $relativePath,
                            'is_dir' => $file->isDir(),
                            'size' => $file->isFile() ? $file->getSize() : 0,
                            'modified' => $file->getMTime()
                        ];
                    }
                }
            } catch (Exception $e) {
                // Handle permission errors silently
            }
        }

        return $results;
    }

    public function readFile($filename) {
        $currentPath = $this->getCurrentPath();
        $filePath = $currentPath . '/' . basename($filename);

        if (file_exists($filePath) && is_file($filePath)) {
            return [
                'content' => file_get_contents($filePath),
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'mime' => mime_content_type($filePath)
            ];
        }

        return false;
    }

    public function saveFile($filename, $content) {
        $currentPath = $this->getCurrentPath();
        $filePath = $currentPath . '/' . basename($filename);

        // Basic security: only allow text files
        $allowedExtensions = ['txt', 'md', 'html', 'css', 'js', 'php', 'json', 'xml', 'csv'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        return file_put_contents($filePath, $content) !== false;
    }

    public function uploadFiles($files) {
        $currentPath = $this->getCurrentPath();
        $uploaded = 0;
        $errors = [];

        foreach ($files['name'] as $key => $filename) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $filename = basename($filename);
                $target = $currentPath . '/' . $filename;

                if (move_uploaded_file($files['tmp_name'][$key], $target)) {
                    // Save to database
                    $this->saveFileToDB($filename, $filename, str_replace($this->baseDir . '/', '', $target), $files['size'][$key], $files['type'][$key]);
                    $uploaded++;
                } else {
                    $errors[] = "Failed to upload: $filename";
                }
            } else {
                $errors[] = "Upload error for: $filename";
            }
        }

        return ['uploaded' => $uploaded, 'errors' => $errors];
    }

    public function createFolder($name) {
        $currentPath = $this->getCurrentPath();
        $folderPath = $currentPath . '/' . basename($name);

        if (!file_exists($folderPath) && mkdir($folderPath, 0755)) {
            // Save to database
            $this->saveFolderToDB($name, str_replace($this->baseDir . '/', '', $folderPath));
            return true;
        }
        return false;
    }

    public function createFile($filename, $content = '') {
        $currentPath = $this->getCurrentPath();
        $filePath = $currentPath . '/' . basename($filename);

        // Basic security: only allow text files
        $allowedExtensions = ['txt', 'md', 'html', 'css', 'js', 'php', 'json', 'xml', 'csv'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        // Check if file already exists
        if (file_exists($filePath)) {
            return false;
        }

        // Create the file
        if (file_put_contents($filePath, $content) !== false) {
            // Save to database
            $relativePath = str_replace($this->baseDir . '/', '', $filePath);
            $this->saveFileToDB($filename, $filename, $relativePath, filesize($filePath), mime_content_type($filePath));
            return true;
        }

        return false;
    }

    public function deleteItem($name) {
        $currentPath = $this->getCurrentPath();
        $itemPath = $currentPath . '/' . basename($name);
        
        // Security: prevent deletion of protected items
        if ($this->isProtected($name)) {
            return false;
        }
        
        // Security: check if path is allowed
        if (!$this->isPathAllowed($itemPath)) {
            return false;
        }

        if (is_dir($itemPath)) {
            // Delete directory recursively
            $this->deleteDirectory($itemPath);
            // Remove from database
            $this->deleteFolderFromDB(str_replace($this->baseDir . '/', '', $itemPath));
        } else {
            unlink($itemPath);
            // Remove from database
            $this->deleteFileFromDB(str_replace($this->baseDir . '/', '', $itemPath));
        }

        return true;
    }

    public function renameItem($oldName, $newName) {
        $currentPath = $this->getCurrentPath();
        $oldPath = $currentPath . '/' . basename($oldName);
        $newPath = $currentPath . '/' . basename($newName);
        
        // Security: prevent renaming protected items
        if ($this->isProtected($oldName)) {
            return false;
        }
        
        // Security: check if old path is allowed
        if (!$this->isPathAllowed($oldPath)) {
            return false;
        }
        
        // Check if old item exists
        if (!file_exists($oldPath)) {
            return false;
        }
        
        // Check if new name already exists
        if (file_exists($newPath)) {
            return false;
        }
        
        // Rename the file/folder
        if (rename($oldPath, $newPath)) {
            // Update database if needed
            $oldRelPath = str_replace($this->baseDir . '/', '', $oldPath);
            $newRelPath = str_replace($this->baseDir . '/', '', $newPath);
            
            if (is_dir($newPath)) {
                $this->updateFolderPathInDB($oldRelPath, $newRelPath);
            } else {
                $this->updateFilePathInDB($oldRelPath, $newRelPath);
            }
            
            return true;
        }
        
        return false;
    }

    public function moveItem($itemName, $destinationPath) {
        $currentPath = $this->getCurrentPath();
        $sourcePath = $currentPath . '/' . basename($itemName);
        
        // Construct destination path
        $destPath = empty($destinationPath) ? $this->baseDir : $this->baseDir . '/' . $destinationPath;
        $destinationFullPath = $destPath . '/' . basename($itemName);
        
        // Security: prevent moving protected items
        if ($this->isProtected($itemName)) {
            return false;
        }
        
        // Security: check if source path is allowed
        if (!$this->isPathAllowed($sourcePath)) {
            return false;
        }
        
        // Security: check if destination path is allowed
        if (!$this->isPathAllowed($destPath)) {
            return false;
        }
        
        // Check if source exists
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        // Check if destination already has item with same name
        if (file_exists($destinationFullPath)) {
            return false;
        }
        
        // Move the file/folder
        if (rename($sourcePath, $destinationFullPath)) {
            // Update database
            $oldRelPath = str_replace($this->baseDir . '/', '', $sourcePath);
            $newRelPath = str_replace($this->baseDir . '/', '', $destinationFullPath);
            
            if (is_dir($destinationFullPath)) {
                $this->updateFolderPathInDB($oldRelPath, $newRelPath);
            } else {
                $this->updateFilePathInDB($oldRelPath, $newRelPath);
            }
            
            return true;
        }
        
        return false;
    }

    public function getAllFolders() {
        $folders = [['name' => 'Home', 'path' => '']];
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    // Skip protected directories
                    if ($this->isProtected($file->getPathname())) continue;
                    
                    // Skip if inside app directory
                    if (!$this->isPathAllowed($file->getPathname())) continue;
                    
                    $relativePath = str_replace($this->baseDir . '/', '', $file->getPathname());
                    $folders[] = [
                        'name' => $relativePath,
                        'path' => $relativePath
                    ];
                }
            }
        } catch (Exception $e) {
            // Handle permission errors silently
        }
        
        return $folders;
    }

    public function getFolderTree() {
        $tree = [];
        
        try {
            $currentPath = $this->baseDir;
            $items = scandir($currentPath);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                
                // Skip protected directories
                if ($this->isProtected($item)) continue;
                
                $fullPath = $currentPath . '/' . $item;
                
                if (is_dir($fullPath) && $this->isPathAllowed($fullPath)) {
                    $tree[] = $this->buildTreeNode($item, $item);
                }
            }
        } catch (Exception $e) {
            // Handle errors silently
        }
        
        return $tree;
    }
    
    private function buildTreeNode($path, $name) {
        $fullPath = $this->baseDir . '/' . $path;
        $node = [
            'name' => basename($name),
            'path' => $path,
            'children' => []
        ];
        
        try {
            if (is_dir($fullPath)) {
                $items = scandir($fullPath);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    if ($this->isProtected($item)) continue;
                    
                    $itemPath = $fullPath . '/' . $item;
                    
                    if (is_dir($itemPath) && $this->isPathAllowed($itemPath)) {
                        $node['children'][] = $this->buildTreeNode($path . '/' . $item, $item);
                    }
                }
            }
        } catch (Exception $e) {
            // Handle errors silently
        }
        
        return $node;
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function saveFileToDB($filename, $originalName, $path, $size, $mimeType) {
        require_once 'config.php';
        Config::init();
        $db = Config::getDB();

        $stmt = $db->prepare("INSERT INTO files (filename, original_name, path, size, mime_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$filename, $originalName, $path, $size, $mimeType, $this->currentUser]);
    }

    private function saveFolderToDB($name, $path) {
        require_once 'config.php';
        Config::init();
        $db = Config::getDB();

        $stmt = $db->prepare("INSERT INTO folders (name, path, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $path, $this->currentUser]);
    }

    private function deleteFileFromDB($path) {
        require_once 'config.php';
        Config::init();
        $db = Config::getDB();

        $stmt = $db->prepare("DELETE FROM files WHERE path = ?");
        $stmt->execute([$path]);
    }

    private function deleteFolderFromDB($path) {
        require_once 'config.php';
        Config::init();
        $db = Config::getDB();

        $stmt = $db->prepare("DELETE FROM folders WHERE path = ?");
        $stmt->execute([$path]);
    }

    private function updateFilePathInDB($oldPath, $newPath) {
        require_once 'config.php';
        Config::init();
        $db = Config::getDB();

        $stmt = $db->prepare("UPDATE files SET path = ?, filename = ? WHERE path = ?");
        $stmt->execute([$newPath, basename($newPath), $oldPath]);
    }

    private function updateFolderPathInDB($oldPath, $newPath) {
        require_once 'config.php';
        Config::init();
        $db = Config::getDB();

        $stmt = $db->prepare("UPDATE folders SET path = ?, name = ? WHERE path = ?");
        $stmt->execute([$newPath, basename($newPath), $oldPath]);
    }

    public function getFileIcon($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match($ext) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico' => '🖼️',
            'pdf' => '📕',
            'txt' => '📄',
            'md', 'markdown' => '📝',
            'log' => '📋',
            'zip', 'rar', '7z', 'tar', 'gz', 'bz2' => '📦',
            'mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a' => '🎵',
            'mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm' => '🎬',
            'php' => '🐘',
            'js' => '📜',
            'css' => '🎨',
            'html', 'htm' => '🌐',
            'py' => '🐍',
            'java' => '☕',
            'c', 'cpp', 'h' => '⚙️',
            'json' => '{ }',
            'xml' => '📰',
            'sql' => '🗄️',
            'csv', 'xls', 'xlsx' => '📊',
            'doc', 'docx' => '📘',
            'ppt', 'pptx' => '📙',
            default => '📄'
        };
    }

    public function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
    }
    
    // Get the relative path from base directory
    public function getRelativePath($fullPath) {
        return str_replace($this->baseDir . '/', '', $fullPath);
    }
    
    // Get download/view URL for a file
    public function getFileUrl($relativePath) {
        // Create a simple file viewer script path
        return 'view.php?file=' . urlencode($relativePath);
    }
}
?>
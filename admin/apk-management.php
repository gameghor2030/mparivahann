<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle APK actions (delete, set latest)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['filename'])) {
        $filename = $_POST['filename'];
        $file_path = '../apk_files/' . $filename;
        
        if (file_exists($file_path) && unlink($file_path)) {
            // Log the action
            $log_entry = date('Y-m-d H:i:s') . " | APK Deleted | File: $filename\n";
            file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
            
            $message = "APK file deleted: $filename";
            $message_type = 'success';
        } else {
            $message = 'Failed to delete APK file';
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'set_latest' && isset($_POST['filename'])) {
        $filename = $_POST['filename'];
        $file_path = '../apk_files/' . $filename;
        
        if (file_exists($file_path)) {
            // Extract version from filename
            preg_match('/v?(\d+\.\d+\.\d+)/', $filename, $matches);
            $version = $matches[1] ?? '1.0.0';
            
            // Update config
            $config = [
                'latest_version' => $version,
                'latest_version_code' => intval(str_replace('.', '', $version)),
                'file_path' => $filename,
                'file_size' => filesize($file_path),
                'download_url' => '/apk/download',
                'last_updated' => date('c'),
                'release_notes' => 'Set as latest version'
            ];
            
            file_put_contents('../apk_config.json', json_encode($config, JSON_PRETTY_PRINT));
            
            // Log the action
            $log_entry = date('Y-m-d H:i:s') . " | APK Set as Latest | File: $filename | Version: $version\n";
            file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
            
            $message = "APK set as latest version: $filename";
            $message_type = 'success';
        } else {
            $message = 'APK file not found';
            $message_type = 'error';
        }
    }
}

// Get APK files
$apk_files = glob('../apk_files/*.apk');
$apk_list = [];
foreach ($apk_files as $apk_file) {
    $filename = basename($apk_file);
    $file_size = filesize($apk_file);
    $modified_time = filemtime($apk_file);
    
    // Check if this is the latest version
    $config = json_decode(file_get_contents('../apk_config.json'), true) ?? [];
    $is_latest = ($config['file_path'] ?? '') === $filename;
    
    $apk_list[] = [
        'filename' => $filename,
        'size' => $file_size,
        'size_formatted' => formatFileSize($file_size),
        'modified' => $modified_time,
        'modified_formatted' => date('Y-m-d H:i:s', $modified_time),
        'is_latest' => $is_latest
    ];
}

// Sort by modification time (newest first)
usort($apk_list, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

function formatFileSize($bytes) {
    if ($bytes === 0) return '0B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 1) . $sizes[$i];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APK Management Dashboard</title>
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-dashboard">
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="nav-brand">
            <i class="fas fa-shield-alt"></i>
            <span>APK Admin Panel</span>
        </div>

        <div class="menu-toggle" id="mobile-menu-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="apk-management.php" class="nav-item active">
                <i class="fas fa-mobile-alt"></i>
                APK Management
            </a>
            <a href="debug_upload.php" class="nav-item">
                <i class="fas fa-upload"></i>
                Upload APK
            </a>
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </div>
        
        <div class="nav-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="page-header">
            <h1><i class="fas fa-mobile-alt"></i> APK Management</h1>
            <p>Upload, manage, and configure APK files for your app</p>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
                <?php endif; ?>
        
        <!-- Upload New APK -->
        <div class="upload-section">
            <h2><i class="fas fa-upload"></i> Upload New APK</h2>
            <div class="upload-info" style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                <i class="fas fa-info-circle" style="color: #2196f3; margin-right: 8px;"></i>
                <strong>Upload System Status:</strong> Current limit: <strong><?php echo ini_get('upload_max_filesize'); ?></strong>. 
                System configured for 100MB+ APK uploads. Large files will be processed efficiently.
            </div>
            <div style="text-align: center; padding: 30px;">
                <a href="debug_upload.php" class="btn btn-primary" style="font-size: 18px; padding: 15px 30px;">
                    <i class="fas fa-upload"></i>
                    Go to Upload Page
                </a>
                <p style="margin-top: 15px; color: #666;">
                    Use the dedicated upload page for the best upload experience
                </p>
            </div>
        </div>
        
        <!-- APK Files List -->
        <div class="apk-list-section">
            <h2><i class="fas fa-list"></i> APK Files</h2>
            
            <?php if (empty($apk_list)): ?>
                <div class="no-files">
                    <i class="fas fa-folder-open"></i>
                    <p>No APK files found</p>
                </div>
            <?php else: ?>
                <div class="apk-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Modified</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apk_list as $apk): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-mobile-alt"></i>
                                        <?php echo htmlspecialchars($apk['filename']); ?>
                                    </td>
                                    <td><?php echo $apk['size_formatted']; ?></td>
                                    <td><?php echo $apk['modified_formatted']; ?></td>
                                    <td>
                                        <?php if ($apk['is_latest']): ?>
                                            <span class="badge badge-success">Latest</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Archive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!$apk['is_latest']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="set_latest">
                                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($apk['filename']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Set this as latest version?')">
                                                        <i class="fas fa-star"></i>
                                                        Set Latest
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this APK?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($apk['filename']); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Current Configuration -->
        <div class="config-section">
            <h2><i class="fas fa-cog"></i> Current Configuration</h2>
            <?php
            $config = json_decode(file_get_contents('../apk_config.json'), true) ?? [];
            if (!empty($config)):
            ?>
                <div class="config-display">
                    <div class="config-item">
                        <span class="config-label">Latest Version:</span>
                        <span class="config-value"><?php echo htmlspecialchars($config['latest_version'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">APK File:</span>
                        <span class="config-value"><?php echo htmlspecialchars($config['file_path'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">File Size:</span>
                        <span class="config-value"><?php echo isset($config['file_size']) ? formatFileSize($config['file_size']) : 'Not set'; ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Last Updated:</span>
                        <span class="config-value"><?php echo htmlspecialchars($config['last_updated'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Release Notes:</span>
                        <span class="config-value"><?php echo htmlspecialchars($config['release_notes'] ?? 'No notes'); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-config">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No APK configuration found</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="admin-script.js"></script>
    <script>
        // Simple file info display
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('apk_file');
            
            // Add file change listener for info display
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        console.log('File selected:', file.name, 'Size:', file.size, 'Type:', file.type);
                        
                        // Show file info
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'file-info';
                        fileInfo.style.cssText = 'margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;';
                        fileInfo.innerHTML = `
                            <strong>Selected File:</strong> ${file.name}<br>
                            <strong>Size:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                            <strong>Type:</strong> ${file.type || 'Unknown'}
                        `;
                        
                        // Remove existing file info
                        const existingInfo = this.parentNode.querySelector('.file-info');
                        if (existingInfo) {
                            existingInfo.remove();
                        }
                        
                        this.parentNode.appendChild(fileInfo);
                    }
                });
            }
        });
    </script>
</body>
</html>

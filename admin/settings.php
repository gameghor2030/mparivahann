<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Simple password validation (in production, use proper hashing)
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            // Update password in a config file (in production, use database)
            $password_config = [
                'admin_username' => 'admin',
                'admin_password' => $new_password,
                'last_changed' => date('c')
            ];
            
            if (file_put_contents('../admin_password.json', json_encode($password_config, JSON_PRETTY_PRINT))) {
                // Log the action
                $log_entry = date('Y-m-d H:i:s') . " | Password Changed | Username: " . $_SESSION['admin_username'] . "\n";
                file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
                
                $message = 'Password changed successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update password';
                $message_type = 'error';
            }
        } else {
            $message = 'New passwords do not match or are too short (minimum 6 characters)';
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'update_settings') {
        $site_title = $_POST['site_title'] ?? '';
        $site_description = $_POST['site_description'] ?? '';
        $max_upload_size = $_POST['max_upload_size'] ?? '';
        $allowed_file_types = $_POST['allowed_file_types'] ?? '';
        
        // Update site settings
        $site_settings = [
            'site_title' => $site_title,
            'site_description' => $site_description,
            'max_upload_size' => $max_upload_size,
            'allowed_file_types' => explode(',', $allowed_file_types),
            'last_updated' => date('c')
        ];
        
        if (file_put_contents('../site_settings.json', json_encode($site_settings, JSON_PRETTY_PRINT))) {
            // Log the action
            $log_entry = date('Y-m-d H:i:s') . " | Site Settings Updated | Username: " . $_SESSION['admin_username'] . "\n";
            file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
            
            $message = 'Site settings updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update site settings';
            $message_type = 'error';
        }
    }
}

// Load current settings
$site_settings = [];
if (file_exists('../site_settings.json')) {
    $site_settings = json_decode(file_get_contents('../site_settings.json'), true) ?? [];
}

$password_config = [];
if (file_exists('../admin_password.json')) {
    $password_config = json_decode(file_get_contents('../admin_password.json'), true) ?? [];
}

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'disk_free_space' => disk_free_space('../'),
    'disk_total_space' => disk_total_space('../')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-dashboard">
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="nav-brand">
            <i class="fas fa-shield-alt"></i>
            <span>Admin Panel</span>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="apk-management.php" class="nav-item">
                <i class="fas fa-mobile-alt"></i>
                APK Management
            </a>
            <a href="video-management.php" class="nav-item">
                <i class="fas fa-video"></i>
                Video Management
            </a>
            <a href="upload.php" class="nav-item">
                <i class="fas fa-upload"></i>
                Upload Content
            </a>
            <a href="settings.php" class="nav-item active">
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
            <h1><i class="fas fa-cog"></i> Settings</h1>
            <p>Configure your system settings and security preferences</p>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-btn active" onclick="showTab('security')">
                <i class="fas fa-shield-alt"></i>
                Security
            </button>
            <button class="tab-btn" onclick="showTab('site')">
                <i class="fas fa-globe"></i>
                Site Settings
            </button>
            <button class="tab-btn" onclick="showTab('system')">
                <i class="fas fa-server"></i>
                System Info
            </button>
        </div>

        <!-- Security Tab -->
        <div id="security" class="tab-content active">
            <div class="settings-section">
                <h2><i class="fas fa-key"></i> Change Admin Password</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <small>Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i>
                        Change Password
                    </button>
                </form>
            </div>

            <div class="settings-section">
                <h2><i class="fas fa-history"></i> Recent Admin Activity</h2>
                <div class="activity-log">
                    <?php
                    $admin_logs = file_exists('../admin_logs.txt') ? array_slice(file('../admin_logs.txt'), -20) : [];
                    if (!empty($admin_logs)) {
                        foreach (array_reverse($admin_logs) as $log) {
                            $log = trim($log);
                            if (!empty($log)) {
                                echo '<div class="log-entry">';
                                echo '<i class="fas fa-info-circle"></i>';
                                echo '<span>' . htmlspecialchars($log) . '</span>';
                                echo '</div>';
                            }
                        }
                    } else {
                        echo '<div class="no-logs">No admin activity logged</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Site Settings Tab -->
        <div id="site" class="tab-content">
            <div class="settings-section">
                <h2><i class="fas fa-globe"></i> Site Configuration</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="site_title">Site Title</label>
                            <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($site_settings['site_title'] ?? 'Premium HD Videos'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description">Site Description</label>
                            <input type="text" id="site_description" name="site_description" value="<?php echo htmlspecialchars($site_settings['site_description'] ?? 'Premium HD Videos Platform'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_upload_size">Maximum Upload Size</label>
                            <input type="text" id="max_upload_size" name="max_upload_size" value="<?php echo htmlspecialchars($site_settings['max_upload_size'] ?? '100MB'); ?>">
                            <small>Current server limit: <?php echo ini_get('upload_max_filesize'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="allowed_file_types">Allowed File Types</label>
                            <input type="text" id="allowed_file_types" name="allowed_file_types" value="<?php echo htmlspecialchars(implode(',', $site_settings['allowed_file_types'] ?? ['mp4', 'gif', 'jpg', 'png'])); ?>">
                            <small>Comma-separated list</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- System Info Tab -->
        <div id="system" class="tab-content">
            <div class="settings-section">
                <h2><i class="fas fa-server"></i> System Information</h2>
                <div class="system-grid">
                    <div class="system-item">
                        <span class="system-label">PHP Version:</span>
                        <span class="system-value"><?php echo $system_info['php_version']; ?></span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Server Software:</span>
                        <span class="system-value"><?php echo htmlspecialchars($system_info['server_software']); ?></span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Upload Max Filesize:</span>
                        <span class="system-value"><?php echo $system_info['upload_max_filesize']; ?></span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Post Max Size:</span>
                        <span class="system-value"><?php echo $system_info['post_max_size']; ?></span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Memory Limit:</span>
                        <span class="system-value"><?php echo $system_info['memory_limit']; ?></span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Max Execution Time:</span>
                        <span class="system-value"><?php echo $system_info['max_execution_time']; ?>s</span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Disk Free Space:</span>
                        <span class="system-value"><?php echo formatFileSize($system_info['disk_free_space']); ?></span>
                    </div>
                    
                    <div class="system-item">
                        <span class="system-label">Disk Total Space:</span>
                        <span class="system-value"><?php echo formatFileSize($system_info['disk_total_space']); ?></span>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2><i class="fas fa-database"></i> File System Status</h2>
                <div class="file-status">
                    <div class="status-item">
                        <i class="fas fa-folder"></i>
                        <div class="status-content">
                            <h4>APK Files Directory</h4>
                            <p><?php echo count(glob('../apk_files/*')); ?> files</p>
                        </div>
                    </div>
                    
                    <div class="status-item">
                        <i class="fas fa-video"></i>
                        <div class="status-content">
                            <h4>Content Directory</h4>
                            <p><?php echo count(glob('../gifs/*')); ?> files</p>
                        </div>
                    </div>
                    
                    <div class="status-item">
                        <i class="fas fa-file-code"></i>
                        <div class="status-content">
                            <h4>Configuration Files</h4>
                            <p><?php echo count(glob('../*.json')); ?> files</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="admin-script.js"></script>
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + sizes[i];
        }
    </script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes === 0) return '0B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 1) . $sizes[$i];
}
?>

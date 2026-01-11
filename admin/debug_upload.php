<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle upload response
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status === 'success') {
        $message = $_GET['message'] ?? 'APK uploaded successfully!';
        $message_type = 'success';
    } else {
        $message = $_GET['message'] ?? 'Upload failed';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload APK - APK Management</title>
    <link rel="stylesheet" href="./admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-dashboard">
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
            <a href="apk-management.php" class="nav-item">
                <i class="fas fa-mobile-alt"></i>
                APK Management
            </a>
            <a href="debug_upload.php" class="nav-item active">
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

    <main class="admin-main">
        <div class="page-header">
            <h1><i class="fas fa-upload"></i> Upload APK</h1>
            <p>Upload new APK files to your application</p>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?php echo $message_type; ?>">
                <pre><?php echo htmlspecialchars($message); ?></pre>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-section">
            <h2><i class="fas fa-upload"></i> Upload New APK</h2>
            <div class="upload-info" style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                <i class="fas fa-info-circle" style="color: #2196f3; margin-right: 8px;"></i>
                <strong>Current Upload Limit:</strong> <strong><?php echo ini_get('upload_max_filesize'); ?></strong>. 
                System configured for 100MB+ APK uploads. Large files will be processed efficiently.
            </div>
            <form method="POST" enctype="multipart/form-data" class="upload-form" action="upload_handler.php">
                <div class="form-group">
                    <label for="apk_file">APK File</label>
                    <input type="file" id="apk_file" name="apk_file" accept=".apk" required>
                </div>
                
                <div class="form-group">
                    <label for="version">Version</label>
                    <input type="text" id="version" name="version" value="test.0.0" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i>
                    Upload APK
                </button>
            </form>
        </div>

        <!-- Current Files -->
        <div class="upload-section">
            <h2><i class="fas fa-list"></i> Current Files in apk_files/</h2>
            <?php
            $files = glob('../apk_files/*');
            if (empty($files)) {
                echo '<p>No files found</p>';
            } else {
                echo '<ul>';
                foreach ($files as $file) {
                    $size = filesize($file);
                    $modified = filemtime($file);
                    echo '<li>' . basename($file) . ' - ' . $size . ' bytes - ' . date('Y-m-d H:i:s', $modified) . '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
    </main>
</body>
</html>

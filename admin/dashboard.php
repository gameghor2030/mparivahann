<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get system statistics
$apk_files = glob('../apk_files/*.apk');
$total_downloads = file_exists('../download_logs.txt') ? count(file('../download_logs.txt')) : 0;

// Get latest APK info
$apk_config = json_decode(file_get_contents('../apk_config.json'), true) ?? [];
$latest_apk = $apk_config['file_path'] ?? 'None';
$latest_version = $apk_config['latest_version'] ?? 'Unknown';
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
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="apk-management.php" class="nav-item">
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
            <a href="../index.html" class="nav-item" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                View Site
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
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> APK Management Dashboard</h1>
            <p>Manage your application's APK files</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($apk_files); ?></h3>
                    <p>APK Files</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-download"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_downloads; ?></h3>
                    <p>APK Downloads</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-code-branch"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $latest_version; ?></h3>
                    <p>Latest Version</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo date("Y-m-d", strtotime($apk_config['last_updated'] ?? 'now')); ?></h3>
                    <p>Last Updated</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-buttons">
                <a href="debug_upload.php" class="action-btn">
                    <i class="fas fa-plus"></i>
                    Upload New APK
                </a>
                <a href="apk-management.php" class="action-btn">
                    <i class="fas fa-edit"></i>
                    Manage APK Files
                </a>
                <a href="/apk_download.php?download=true" class="action-btn" target="_blank">
                    <i class="fas fa-download"></i>
                    Test Download
                </a>
                <a href="../index.html" class="action-btn" target="_blank">
                    <i class="fas fa-eye"></i>
                    View Frontend
                </a>
                <a href="settings.php" class="action-btn">
                    <i class="fas fa-cog"></i>
                    System Settings
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <div class="activity-list">
                <?php
                $admin_logs = file_exists('../admin_logs.txt') ? array_slice(file('../admin_logs.txt'), -10) : [];
                if (!empty($admin_logs)) {
                    foreach (array_reverse($admin_logs) as $log) {
                        $log = trim($log);
                        if (!empty($log)) {
                            echo '<div class="activity-item">';
                            echo '<i class="fas fa-info-circle"></i>';
                            echo '<span>' . htmlspecialchars($log) . '</span>';
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="no-activity">No recent activity</div>';
                }
                ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <h2><i class="fas fa-server"></i> System Status</h2>
            <div class="status-grid">
                <div class="status-item">
                    <span class="status-label">PHP Version:</span>
                    <span class="status-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Server:</span>
                    <span class="status-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Upload Max:</span>
                    <span class="status-value"><?php echo ini_get('upload_max_filesize'); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Memory Limit:</span>
                    <span class="status-value"><?php echo ini_get('memory_limit'); ?></span>
                </div>
            </div>
        </div>
    </main>

    <script src="admin-script.js"></script>
</body>
</html>

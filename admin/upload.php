<?php
// Set PHP upload limits for this script
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Helper function to format views
function formatViews($views) {
    $views = trim($views);
    if (empty($views) || $views === '0') {
        return '0';
    }
    
    // Handle k (thousands) format
    if (preg_match('/^(\d+(?:\.\d+)?)k$/i', $views, $matches)) {
        $number = (float)$matches[1] * 1000;
        return number_format($number);
    }
    
    // Handle M (millions) format
    if (preg_match('/^(\d+(?:\.\d+)?)m$/i', $views, $matches)) {
        $number = (float)$matches[1] * 1000000;
        return number_format($number);
    }
    
    // Handle plain numbers
    if (is_numeric($views)) {
        return number_format((int)$views);
    }
    
    return '0';
}

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video_file'])) {
    $upload_dir = '../gifs/';
    $allowed_types = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 'image/gif'];
    $max_file_size = 100 * 1024 * 1024; // 100MB
    
    $file = $_FILES['video_file'];
    $filename = $file['name'];
    $tmp_path = $file['tmp_name'];
    $file_size = $file['size'];
    $file_type = $file['type'];
    $upload_error = $file['error'];
    
    // Check for upload errors first
    if ($upload_error !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize (Current limit: ' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        $message = $error_messages[$upload_error] ?? 'Upload error: ' . $upload_error;
        $message_type = 'error';
    } elseif ($file_size > $max_file_size) {
        $message = 'File size exceeds 100MB limit (Current: ' . number_format($file_size) . ' bytes)';
        $message_type = 'error';
    } elseif ($file_size === 0) {
        $message = 'File size is 0 - possible upload configuration issue';
        $message_type = 'error';
    } else {
        // Generate unique filename
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        // Validate file type
        $allowed_extensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'gif'];
        if (!in_array($file_extension, $allowed_extensions)) {
            $message = 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions);
            $message_type = 'error';
        } else {
            // Move uploaded file
            if (move_uploaded_file($tmp_path, $upload_path)) {
                // Get form data
                $title = $_POST['title'] ?? 'Untitled Video';
                $category = $_POST['category'] ?? 'general';
                $description = $_POST['description'] ?? '';
                $views = formatViews($_POST['views'] ?? '0');
                $duration = (int)($_POST['duration'] ?? 0);
                
                // Add to gifs.json
                $gifs_file = '../gifs.json';
                $gifs_data = [];
                
                if (file_exists($gifs_file)) {
                    $gifs_data = json_decode(file_get_contents($gifs_file), true) ?? [];
                }
                
                // Create new video entry
                $new_video = [
                    'id' => (string)(count($gifs_data) + 1),
                    'filename' => $unique_filename,
                    'title' => $title,
                    'category' => $category,
                    'description' => $description,
                    'views' => $views . ' views',
                    'duration' => $duration,
                    'video' => 'gifs/' . $unique_filename,
                    'last_updated' => date('c'),
                    'file_size' => $file_size,
                    'original_name' => $filename
                ];
                
                $gifs_data[] = $new_video;
                file_put_contents($gifs_file, json_encode($gifs_data, JSON_PRETTY_PRINT));
                
                // Log the action
                $log_entry = date('Y-m-d H:i:s') . " | Video Uploaded | File: $unique_filename | Title: $title\n";
                file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
                
                $message = "Video uploaded successfully: $title";
                $message_type = 'success';
                
                // Clear form data
                $title = $category = $description = '';
            } else {
                $message = 'Failed to upload video file';
                $message_type = 'error';
            }
        }
    }
}

// Get current categories for the dropdown
$gifs_file = '../gifs.json';
$existing_categories = ['general'];
if (file_exists($gifs_file)) {
    $gifs_data = json_decode(file_get_contents($gifs_file), true) ?? [];
    foreach ($gifs_data as $gif) {
        if (!in_array($gif['category'], $existing_categories)) {
            $existing_categories[] = $gif['category'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload APK - Admin Panel</title>
    <link rel="stylesheet" href="./admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-dashboard">
    <nav class="admin-nav">
        <div class="nav-brand">
            <i class="fas fa-shield-alt"></i>
            <span>APK Admin Panel</span>
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
            <h1><i class="fas fa-upload"></i> Upload Video</h1>
            <p>Upload new video content to your platform</p>
        </div>
        
        <!-- Upload System Status -->
        <div class="upload-section" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border: 1px solid var(--border-color); margin-bottom: 20px;">
            <h3><i class="fas fa-info-circle"></i> Upload System Status</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px; color: #e2e8f0;">
                <div>
                    <strong>Upload Max Filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?>
                </div>
                <div>
                    <strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?>
                </div>
                <div>
                    <strong>Max File Uploads:</strong> <?php echo ini_get('max_file_uploads'); ?>
                </div>
                <div>
                    <strong>File Uploads:</strong> <?php echo ini_get('file_uploads') ? 'Enabled' : 'Disabled'; ?>
                </div>
                <div>
                    <strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?>s
                </div>
                <div>
                    <strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-section">
            <h2><i class="fas fa-upload"></i> Upload New Video</h2>
            <div class="upload-info" style="background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); padding: 20px; border-radius: var(--border-radius); margin-bottom: 20px; border-left: 4px solid var(--primary-color); box-shadow: var(--shadow);">
                <i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 8px;"></i>
                <strong>Video Upload System:</strong> Support for MP4, AVI, MOV, WMV, FLV, and GIF files up to 100MB. 
                Videos will be automatically organized and added to your content library.
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="video_file">Video File</label>
                        <input type="file" id="video_file" name="video_file" accept="video/*,image/gif" required>
                        <small>Maximum file size: 100MB. Supported formats: MP4, AVI, MOV, WMV, FLV, GIF</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Video Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" placeholder="Enter video title" required>
                        <small>Give your video a descriptive title</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category ?? '') === $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($cat)); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new_category">+ Add New Category</option>
                        </select>
                        <small>Choose existing category or add a new one</small>
                    </div>
                    
                    <div class="form-group" id="new_category_group" style="display: none;">
                        <label for="new_category_name">New Category Name</label>
                        <input type="text" id="new_category_name" name="new_category_name" placeholder="Enter new category name">
                        <small>Create a new category for your content</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="views">Initial Views</label>
                        <input type="text" id="views" name="views" value="<?php echo htmlspecialchars($views ?? '0'); ?>" placeholder="0" pattern="[0-9]+[kKmM]?" title="Enter number or number with k (thousands) or M (millions)">
                        <small>Set initial view count (e.g., 0, 1.2k, 5M)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration (seconds)</label>
                        <input type="number" id="duration" name="duration" value="<?php echo htmlspecialchars($duration ?? '0'); ?>" placeholder="0" min="0" step="1">
                        <small>Video duration in seconds (e.g., 120 for 2 minutes)</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe your video content" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    <small>Provide a brief description of the video content</small>
                </div>
                
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
            <i class="fas fa-upload"></i>
            <span id="uploadBtnText">Upload Video</span>
        </button>
        
        <!-- Upload Progress -->
        <div id="uploadProgress" style="display: none; margin-top: 15px;">
            <div style="background: var(--bg-tertiary); border-radius: 8px; padding: 15px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div class="spinner" style="width: 20px; height: 20px; border: 2px solid var(--border-color); border-top: 2px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <span>Uploading video...</span>
                </div>
                <div style="background: var(--bg-secondary); height: 4px; border-radius: 2px; overflow: hidden;">
                    <div id="progressBar" style="height: 100%; background: var(--primary-color); width: 0%; transition: width 0.3s ease;"></div>
                </div>
            </div>
        </div>
            </form>
        </div>

        <!-- Recent Uploads -->
        <div class="upload-section">
            <h2><i class="fas fa-history"></i> Recent Video Uploads</h2>
            <?php
            $gifs_file = '../gifs.json';
            if (file_exists($gifs_file)) {
                $gifs_data = json_decode(file_get_contents($gifs_file), true) ?? [];
                $recent_videos = array_slice(array_reverse($gifs_data), 0, 5);
                
                if (!empty($recent_videos)) {
                    echo '<div class="video-grid">';
                    foreach ($recent_videos as $video) {
                        echo '<div class="video-card">';
                        echo '<div class="video-thumbnail">';
                        echo '<i class="fas fa-video"></i>';
                        echo '<span class="file-extension">' . strtoupper(pathinfo($video['filename'], PATHINFO_EXTENSION)) . '</span>';
                        echo '</div>';
                        echo '<div class="video-info">';
                        echo '<h3 class="video-title">' . htmlspecialchars($video['title']) . '</h3>';
                        echo '<p class="video-filename">' . htmlspecialchars($video['filename']) . '</p>';
                        echo '<div class="video-meta">';
                        echo '<span class="category">' . htmlspecialchars($video['category']) . '</span>';
                        echo '<span class="size">' . number_format($video['file_size'] ?? 0) . ' bytes</span>';
                        echo '</div>';
                        echo '<p class="video-date">' . date('M j, Y', strtotime($video['last_updated'])) . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="no-uploads">';
                    echo '<i class="fas fa-video"></i>';
                    echo '<p>No videos uploaded yet</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-uploads">';
                echo '<i class="fas fa-video"></i>';
                echo '<p>No video data found</p>';
                echo '</div>';
            }
            ?>
        </div>
    </main>

    <script src="admin-script.js"></script>
    <script>
        // Handle new category input
        document.getElementById('category').addEventListener('change', function() {
            const newCategoryGroup = document.getElementById('new_category_group');
            if (this.value === 'new_category') {
                newCategoryGroup.style.display = 'block';
                document.getElementById('new_category_name').required = true;
            } else {
                newCategoryGroup.style.display = 'none';
                document.getElementById('new_category_name').required = false;
            }
        });

        // Update category value when new category is entered
        document.getElementById('new_category_name').addEventListener('input', function() {
            if (this.value.trim()) {
                document.getElementById('category').value = this.value.trim();
            }
        });

        // File size validation
        document.getElementById('video_file').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 100 * 1024 * 1024; // 100MB
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                
                if (file.size > maxSize) {
                    alert(`File size (${fileSizeMB}MB) exceeds 100MB limit. Please choose a smaller file.`);
                    this.value = '';
                } else {
                    // Show file size info
                    const fileInfo = document.createElement('div');
                    fileInfo.style.cssText = 'margin-top: 8px; padding: 8px; background: var(--bg-tertiary); border-radius: 4px; font-size: 14px; color: var(--text-secondary);';
                    fileInfo.innerHTML = `<i class="fas fa-info-circle"></i> File selected: ${file.name} (${fileSizeMB}MB)`;
                    
                    // Remove previous file info if exists
                    const prevInfo = this.parentNode.querySelector('.file-info');
                    if (prevInfo) prevInfo.remove();
                    
                    fileInfo.className = 'file-info';
                    this.parentNode.appendChild(fileInfo);
                }
            }
        });

        // Views field validation and formatting
        document.getElementById('views').addEventListener('input', function() {
            let value = this.value;
            
            // Allow only numbers, dots, k, K, m, M
            value = value.replace(/[^0-9.kKmM]/g, '');
            
            // Ensure only one dot
            const dots = value.split('.').length - 1;
            if (dots > 1) {
                value = value.replace(/\.+$/, '');
            }
            
            // Ensure k or M is at the end
            if (value.includes('k') || value.includes('K')) {
                value = value.replace(/[kK]/g, 'k');
                if (!value.endsWith('k')) {
                    value = value.replace(/k/g, '') + 'k';
                }
            }
            
            if (value.includes('m') || value.includes('M')) {
                value = value.replace(/[mM]/g, 'M');
                if (!value.endsWith('M')) {
                    value = value.replace(/M/g, '') + 'M';
                }
            }
            
            this.value = value;
        });

        // Duration field validation
        document.getElementById('duration').addEventListener('input', function() {
            let value = parseInt(this.value) || 0;
            if (value < 0) {
                this.value = 0;
            }
        });

        // Upload form handling with progress
        document.querySelector('.upload-form').addEventListener('submit', function(e) {
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadBtnText = document.getElementById('uploadBtnText');
            const uploadProgress = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            
            // Show progress
            uploadBtn.disabled = true;
            uploadBtnText.textContent = 'Uploading...';
            uploadProgress.style.display = 'block';
            
            // Simulate progress (since we can't get real progress with regular form submit)
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 200);
            
            // Reset after form submission
            setTimeout(() => {
                clearInterval(progressInterval);
                uploadBtn.disabled = false;
                uploadBtnText.textContent = 'Upload Video';
                uploadProgress.style.display = 'none';
                progressBar.style.width = '0%';
            }, 3000);
        });
    </script>
    
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</body>
</html>

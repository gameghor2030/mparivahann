<?php
// Note: Upload limits are set in php.ini file
// These runtime settings don't affect upload limits
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    if (!isset($_FILES['apk_file']) || $_FILES['apk_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = $_FILES['apk_file']['error'] ?? 'Unknown error';
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize (Current limit: ' . ini_get('upload_max_filesize') . '). Please check server configuration.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        $error_text = $error_messages[$upload_error] ?? "Upload error code: $upload_error";
        throw new Exception("Upload failed: $error_text");
    }

    $file = $_FILES['apk_file'];
    $filename = $file['name'];
    $tmp_path = $file['tmp_name'];
    $file_size = $file['size'];
    
    // Validate file type - simple extension check (no fileinfo dependency)
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($file_extension !== 'apk') {
        throw new Exception('Invalid file type. Only APK files (.apk extension) are allowed.');
    }
    
    // Generate unique filename
    $version = $_POST['version'] ?? '1.0.0';
    $new_filename = 'mParivahan' . $version . '.apk';
    $upload_path = '../apk_files/' . $new_filename;
    
    // Ensure directory exists and is writable
    if (!is_dir('../apk_files/')) {
        mkdir('../apk_files/', 0755, true);
    }
    
    if (!is_writable('../apk_files/')) {
        throw new Exception('APK files directory is not writable');
    }
    
            // Debug: Check if file exists and is readable
        if (!file_exists($tmp_path)) {
            throw new Exception("Temporary file not found: $tmp_path");
        }
        
        if (!is_readable($tmp_path)) {
            throw new Exception("Temporary file not readable: $tmp_path");
        }
        
        // Debug: Log the upload attempt
        error_log("Upload attempt - Temp: $tmp_path, Dest: $upload_path, Size: $file_size");
        
                if (move_uploaded_file($tmp_path, $upload_path)) {
            // Debug: Verify file was moved successfully
            if (!file_exists($upload_path)) {
                throw new Exception("File move failed - destination file not found: $upload_path");
            }
            
            $actual_size = filesize($upload_path);
            if ($actual_size !== $file_size) {
                throw new Exception("File size mismatch - Expected: $file_size, Actual: $actual_size");
            }
            
            error_log("File moved successfully - Size: $actual_size, Path: $upload_path");
            
            // Update config
        $config = [
            'latest_version' => $version,
            'latest_version_code' => intval(str_replace('.', '', $version)),
            'file_path' => $new_filename,
            'file_size' => $file_size,
            'download_url' => '/apk/download',
            'last_updated' => date('c'),
            'release_notes' => $_POST['release_notes'] ?? 'New version uploaded'
        ];
        
        file_put_contents('../apk_config.json', json_encode($config, JSON_PRETTY_PRINT));
        
        // Log the action
        $log_entry = date('Y-m-d H:i:s') . " | APK Uploaded | Version: $version | Size: " . formatFileSize($file_size) . "\n";
        file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
        
        // Check if this is a form submission (not AJAX)
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
            // Determine which page to redirect back to
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referer, 'debug_upload.php') !== false) {
                header('Location: debug_upload.php?status=success&message=' . urlencode("APK uploaded successfully! Version: $version"));
            } else {
                header('Location: apk-management.php?status=success&message=' . urlencode("APK uploaded successfully! Version: $version"));
            }
            exit;
        }
        
        $response = [
            'status' => 'success',
            'message' => "APK uploaded successfully! Version: $version",
            'data' => [
                'filename' => $new_filename,
                'version' => $version,
                'size' => $file_size,
                'size_formatted' => formatFileSize($file_size)
            ]
        ];
    } else {
        throw new Exception('Failed to move uploaded file to destination');
    }
    
} catch (Exception $e) {
            // Check if this is a form submission (not AJAX)
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
            // Determine which page to redirect back to
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referer, 'debug_upload.php') !== false) {
                header('Location: debug_upload.php?status=error&message=' . urlencode($e->getMessage()));
            } else {
                header('Location: apk-management.php?status=error&message=' . urlencode($e->getMessage()));
            }
            exit;
        }
    
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'file_uploads' => ini_get('file_uploads') ? 'On' : 'Off'
        ]
    ];
}

echo json_encode($response);

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

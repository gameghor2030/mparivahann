<?php
/**
 * APK Download Handler for Premium HD Videos
 * This script handles APK file downloads and serves the latest version
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$APK_STORAGE_PATH = './apk_files/';
$CONFIG_FILE = './apk_config.json';
$DOWNLOAD_LOG_FILE = './download_logs.txt';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Function to log downloads
function logDownload($ip, $userAgent, $version, $status) {
    global $DOWNLOAD_LOG_FILE;
    
    $logEntry = date('Y-m-d H:i:s') . " | IP: $ip | UA: $userAgent | Version: $version | Status: $status\n";
    
    if (file_put_contents($DOWNLOAD_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        error_log("Failed to write to download log: $DOWNLOAD_LOG_FILE");
    }
}

// Function to get latest APK info
function getLatestApkInfo() {
    global $CONFIG_FILE, $APK_STORAGE_PATH;
    
    // Try to read config file first
    if (file_exists($CONFIG_FILE)) {
        $config = json_decode(file_get_contents($CONFIG_FILE), true);
        if ($config && isset($config['file_path'])) {
            $apkPath = $APK_STORAGE_PATH . $config['file_path'];
            if (file_exists($apkPath)) {
                return [
                    'path' => $apkPath,
                    'filename' => $config['file_path'],
                    'version' => $config['latest_version'] ?? 'Unknown',
                    'size' => filesize($apkPath)
                ];
            }
        }
    }
    
    // Fallback: find the most recent APK file
    $apkFiles = glob($APK_STORAGE_PATH . '*.apk');
    if (empty($apkFiles)) {
        return null;
    }
    
    // Sort by modification time (newest first)
    usort($apkFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestApk = $apkFiles[0];
    $filename = basename($latestApk);
    
    // Try to extract version from filename
    $version = 'Unknown';
    if (preg_match('/v?(\d+\.\d+\.\d+)/', $filename, $matches)) {
        $version = $matches[1];
    }
    
    return [
        'path' => $latestApk,
        'filename' => $filename,
        'version' => $version,
        'size' => filesize($latestApk)
    ];
}

// Function to serve APK file
function serveApkFile($apkInfo) {
    $filePath = $apkInfo['path'];
    $filename = $apkInfo['filename'];
    $fileSize = $apkInfo['size'];
    
    // Check if file exists and is readable
    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'APK file not found']);
        return false;
    }
    
    // Get file info
    $fileInfo = pathinfo($filePath);
    $mimeType = 'application/vnd.android.package-archive';
    
    // Clean filename for mobile browsers (remove any path components)
    $cleanFilename = basename($filename);
    
    // Detect mobile browser for better header formatting
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isMobile = preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
    
    // Set headers for download with proper encoding for mobile browsers
    header('Content-Type: ' . $mimeType);
    
    // Use simpler format for mobile browsers
    if ($isMobile) {
        // Mobile browsers prefer simpler filename format
        header('Content-Disposition: attachment; filename=' . $cleanFilename);
    } else {
        // Desktop browsers can handle quoted filenames
        header('Content-Disposition: attachment; filename="' . $cleanFilename . '"');
    }
    
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('X-Content-Type-Options: nosniff');
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output file in chunks to handle large files
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to open APK file']);
        return false;
    }
    
    // Output file content
    while (!feof($handle)) {
        $chunk = fread($handle, 8192); // 8KB chunks
        if ($chunk === false) {
            break;
        }
        echo $chunk;
        
        // Flush output buffer
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    fclose($handle);
    return true;
}

// Main execution
try {
    // Get client information
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Debug: Log the request
    error_log("APK Download Request: " . $_SERVER['REQUEST_URI']);
    
    // Check if this is a download request
    if (isset($_GET['download']) || strpos($_SERVER['REQUEST_URI'], '/apk/download') !== false || $_SERVER['REQUEST_URI'] === '/apk/download') {
        
        // Get latest APK information
        $apkInfo = getLatestApkInfo();
        
        if (!$apkInfo) {
            http_response_code(404);
            echo json_encode([
                'error' => 'No APK files available',
                'message' => 'Please contact administrator to upload APK files'
            ]);
            
            logDownload($clientIP, $userAgent, 'None', 'No APK available');
            exit;
        }
        
        // Override filename if provided in URL (for mobile browser compatibility)
        if (isset($_GET['file']) && !empty($_GET['file'])) {
            $requestedFilename = basename($_GET['file']);
            // Only use requested filename if it matches the actual file pattern
            if (strpos($requestedFilename, 'mParivahan') !== false || strpos($requestedFilename, '.apk') !== false) {
                $apkInfo['filename'] = $requestedFilename;
            }
        }
        
        // Log the download attempt
        logDownload($clientIP, $userAgent, $apkInfo['version'], 'Started');
        
        // Serve the APK file
        if (serveApkFile($apkInfo)) {
            logDownload($clientIP, $userAgent, $apkInfo['version'], 'Completed');
        } else {
            logDownload($clientIP, $userAgent, $apkInfo['version'], 'Failed');
        }
        
    } else {
        // API endpoint to get APK information
        header('Content-Type: application/json');
        
        $apkInfo = getLatestApkInfo();
        
        if ($apkInfo) {
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'version' => $apkInfo['version'],
                    'filename' => $apkInfo['filename'],
                    'size' => $apkInfo['size'],
                    'size_formatted' => formatFileSize($apkInfo['size']),
                    'download_url' => '/apk/download',
                    'last_updated' => date('c', filemtime($apkInfo['path']))
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No APK files available'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("APK Download Error: " . $e->getMessage());
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'Failed to process download request'
    ]);
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes === 0) return '0B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 1) . $sizes[$i];
}
?>

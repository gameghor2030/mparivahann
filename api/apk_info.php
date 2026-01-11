<?php
/**
 * APK Information API Endpoint
 * Provides APK information for frontend consumption
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$CONFIG_FILE = '../apk_config.json';
$APK_STORAGE_PATH = '../apk_files/';

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
                    'size' => filesize($apkPath),
                    'last_updated' => $config['last_updated'] ?? date('c', filemtime($apkPath)),
                    'release_notes' => $config['release_notes'] ?? 'Latest version'
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
        'size' => filesize($latestApk),
        'last_updated' => date('c', filemtime($latestApk)),
        'release_notes' => 'Latest version'
    ];
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes === 0) return '0B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 1) . $sizes[$i];
}

try {
    $apkInfo = getLatestApkInfo();
    
    if ($apkInfo) {
        $response = [
            'status' => 'success',
            'data' => [
                'version' => $apkInfo['version'],
                'filename' => $apkInfo['filename'],
                'size' => $apkInfo['size'],
                'size_formatted' => formatFileSize($apkInfo['size']),
                'download_url' => '/apk_download.php?download=true',
                'last_updated' => $apkInfo['last_updated'],
                'release_notes' => $apkInfo['release_notes'],
                'app_name' => 'mParivahan NextGen',
                'app_description' => 'Official digital vehicle & license management app by the Government of India',
                'download_count' => getDownloadCount()
            ]
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'No APK files available',
            'data' => null
        ]);
    }
    
} catch (Exception $e) {
    error_log("APK Info API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'data' => null
    ]);
}

// Function to get download count
function getDownloadCount() {
    $logFile = '../download_logs.txt';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return count($lines);
    }
    return 0;
}
?>

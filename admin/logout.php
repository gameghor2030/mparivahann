<?php
session_start();

// Log the logout
if (isset($_SESSION['admin_username'])) {
    $log_entry = date('Y-m-d H:i:s') . " | Admin Logout | Username: " . $_SESSION['admin_username'] . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>

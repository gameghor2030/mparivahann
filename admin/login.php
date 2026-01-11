<?php
session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Admin credentials (in production, use database and hashed passwords)
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'admin123'; // Change this to a strong password

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        
        // Log successful login
        $log_entry = date('Y-m-d H:i:s') . " | Admin Login Success | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
        file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error_message = 'Invalid username or password';
        
        // Log failed login attempt
        $log_entry = date('Y-m-d H:i:s') . " | Admin Login Failed | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . " | Username: $username\n";
        file_put_contents('../admin_logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Premium HD Videos</title>
    <link rel="stylesheet" href="./admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-login">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-shield-alt"></i>
                <h1>Admin Panel</h1>
                <p>Premium HD Videos Management</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            <div class="login-footer">
                <p><i class="fas fa-info-circle"></i> Default: admin / admin123</p>
                <p><i class="fas fa-exclamation-triangle"></i> Change password in production!</p>
            </div>
        </div>
    </div>
</body>
</html>

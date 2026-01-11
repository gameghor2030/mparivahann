<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Function to parse download logs
function parseDownloadLogs() {
    $logFile = '../download_logs.txt';
    $analytics = [
        'total_downloads' => 0,
        'today_downloads' => 0,
        'this_week_downloads' => 0,
        'this_month_downloads' => 0,
        'downloads_by_version' => [],
        'downloads_by_ip' => [],
        'recent_downloads' => [],
        'hourly_stats' => array_fill(0, 24, 0),
        'daily_stats' => []
    ];
    
    if (!file_exists($logFile)) {
        return $analytics;
    }
    
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $analytics['total_downloads'] = count($lines);
    
    $today = date('Y-m-d');
    $thisWeek = date('Y-m-d', strtotime('-7 days'));
    $thisMonth = date('Y-m', strtotime('now'));
    
    foreach ($lines as $line) {
        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \| IP: ([^|]+) \| UA: ([^|]+) \| Version: ([^|]+) \| Status: ([^\n]+)/', $line, $matches)) {
            $timestamp = $matches[1];
            $ip = trim($matches[2]);
            $version = trim($matches[3]);
            $status = trim($matches[4]);
            
            if ($status === 'Completed') {
                $date = date('Y-m-d', strtotime($timestamp));
                $hour = (int)date('H', strtotime($timestamp));
                
                // Today's downloads
                if ($date === $today) {
                    $analytics['today_downloads']++;
                }
                
                // This week's downloads
                if (strtotime($date) >= strtotime($thisWeek)) {
                    $analytics['this_week_downloads']++;
                }
                
                // This month's downloads
                if (date('Y-m', strtotime($timestamp)) === $thisMonth) {
                    $analytics['this_month_downloads']++;
                }
                
                // Downloads by version
                if (!isset($analytics['downloads_by_version'][$version])) {
                    $analytics['downloads_by_version'][$version] = 0;
                }
                $analytics['downloads_by_version'][$version]++;
                
                // Downloads by IP
                if (!isset($analytics['downloads_by_ip'][$ip])) {
                    $analytics['downloads_by_ip'][$ip] = 0;
                }
                $analytics['downloads_by_ip'][$ip]++;
                
                // Hourly stats
                $analytics['hourly_stats'][$hour]++;
                
                // Daily stats
                if (!isset($analytics['daily_stats'][$date])) {
                    $analytics['daily_stats'][$date] = 0;
                }
                $analytics['daily_stats'][$date]++;
                
                // Recent downloads (last 10)
                if (count($analytics['recent_downloads']) < 10) {
                    $analytics['recent_downloads'][] = [
                        'timestamp' => $timestamp,
                        'ip' => $ip,
                        'version' => $version
                    ];
                }
            }
        }
    }
    
    // Sort recent downloads by timestamp (newest first)
    usort($analytics['recent_downloads'], function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Sort versions by download count
    arsort($analytics['downloads_by_version']);
    
    // Sort IPs by download count
    arsort($analytics['downloads_by_ip']);
    
    // Sort daily stats by date
    ksort($analytics['daily_stats']);
    
    return $analytics;
}

$analytics = parseDownloadLogs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Analytics - Admin Panel</title>
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Download Analytics</h1>
            <p>Track and analyze APK download statistics</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-download"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($analytics['total_downloads']); ?></h3>
                    <p>Total Downloads</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($analytics['today_downloads']); ?></h3>
                    <p>Today</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($analytics['this_week_downloads']); ?></h3>
                    <p>This Week</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($analytics['this_month_downloads']); ?></h3>
                    <p>This Month</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="upload-section">
            <h2><i class="fas fa-chart-bar"></i> Download Trends</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div style="background: var(--bg-secondary); padding: 20px; border-radius: var(--border-radius);">
                    <h3>Downloads by Hour</h3>
                    <canvas id="hourlyChart" width="400" height="200"></canvas>
                </div>
                <div style="background: var(--bg-secondary); padding: 20px; border-radius: var(--border-radius);">
                    <h3>Downloads by Version</h3>
                    <canvas id="versionChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Downloads by Version -->
        <div class="upload-section">
            <h2><i class="fas fa-code-branch"></i> Downloads by Version</h2>
            <div class="apk-table">
                <table>
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Downloads</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['downloads_by_version'] as $version => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($version); ?></td>
                                <td><?php echo number_format($count); ?></td>
                                <td><?php echo $analytics['total_downloads'] > 0 ? round(($count / $analytics['total_downloads']) * 100, 1) : 0; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Downloads -->
        <div class="upload-section">
            <h2><i class="fas fa-history"></i> Recent Downloads</h2>
            <div class="apk-table">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>IP Address</th>
                            <th>Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['recent_downloads'] as $download): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($download['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($download['ip']); ?></td>
                                <td><?php echo htmlspecialchars($download['version']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="admin-script.js"></script>
    <script>
        // Hourly Downloads Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + ':00'),
                datasets: [{
                    label: 'Downloads',
                    data: <?php echo json_encode(array_values($analytics['hourly_stats'])); ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#cbd5e1'
                        },
                        grid: {
                            color: '#475569'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#cbd5e1'
                        },
                        grid: {
                            color: '#475569'
                        }
                    }
                }
            }
        });

        // Version Downloads Chart
        const versionCtx = document.getElementById('versionChart').getContext('2d');
        new Chart(versionCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($analytics['downloads_by_version'])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($analytics['downloads_by_version'])); ?>,
                    backgroundColor: [
                        '#8b5cf6',
                        '#ec4899',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#6366f1'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

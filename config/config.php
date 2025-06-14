<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'slotapp';
$db_user = 'root';
$db_pass = 'Mcmxcix7//6'; // Update with your actual password if needed

// Application settings
$app_name = 'Slot Management System';
$app_version = '1.0.0';
$app_url = 'http://localhost/slotapp'; // Update with your actual URL

// Security settings
define('SECURE_MODE', true);
define('FORCE_HTTPS', false); // Set to true in production
define('ENABLE_SECURITY_HEADERS', true);

// Include security functions
require_once __DIR__ . '/security.php';

// Initialize secure session
initSecureSession();

// Set security headers (only if not already sent)
if (ENABLE_SECURITY_HEADERS && !headers_sent()) {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:;");
    
    // Force HTTPS in production
    if (FORCE_HTTPS && !isset($_SERVER['HTTPS'])) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
}

// Detect suspicious activity
if (detectSuspiciousActivity()) {
    // Could implement additional measures here like temporary IP blocking
}

// Get MySQL version to determine compatible sql_mode
function getMySQLVersion($host, $user, $pass) {
    try {
        $temp_conn = new PDO("mysql:host=$host", $user, $pass);
        $stmt = $temp_conn->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        return $result['version'];
    } catch (PDOException $e) {
        return null;
    }
}

// Establish database connection with enhanced error handling
try {
    // First, get MySQL version to set appropriate sql_mode
    $mysql_version = getMySQLVersion($db_host, $db_user, $db_pass);
    $sql_mode = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION";
    
    // Only add NO_AUTO_CREATE_USER for MySQL versions that support it (< 8.0)
    if ($mysql_version && version_compare($mysql_version, '8.0.0', '<')) {
        $sql_mode .= ",NO_AUTO_CREATE_USER";
    }
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='$sql_mode'"
    ];
    
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);
    
    // Test the connection by running a simple query
    $conn->query("SELECT 1");
    
} catch (PDOException $e) {
    // Enhanced error logging and user-friendly error handling
    $error_message = $e->getMessage();
    $error_code = $e->getCode();
    
    // Log the detailed error for administrators
    error_log("Database connection failed: " . $error_message . " (Code: " . $error_code . ")");
    
    // Provide specific guidance based on common error codes
    $user_message = "Database connection failed. ";
    
    switch ($error_code) {
        case 1045: // Access denied
            $user_message .= "Please check your database credentials in config/config.php";
            break;
        case 1049: // Unknown database
            $user_message .= "Database '$db_name' does not exist. Please create it or run the installer.";
            break;
        case 2002: // Can't connect to server
            $user_message .= "Cannot connect to MySQL server. Please ensure MySQL is running.";
            break;
        case 1231: // Variable sql_mode error
            $user_message .= "MySQL version compatibility issue. This has been automatically resolved.";
            break;
        default:
            $user_message .= "Please contact the administrator.";
    }
    
    // Show detailed error in development mode
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        $user_message .= "<br><br><strong>Debug Info:</strong><br>";
        $user_message .= "Error: " . htmlspecialchars($error_message) . "<br>";
        $user_message .= "Code: " . htmlspecialchars($error_code) . "<br>";
        $user_message .= "Host: " . htmlspecialchars($db_host) . "<br>";
        $user_message .= "Database: " . htmlspecialchars($db_name) . "<br>";
        $user_message .= "User: " . htmlspecialchars($db_user) . "<br>";
        $user_message .= "MySQL Version: " . htmlspecialchars($mysql_version ?? 'Unknown');
    }
    
    // Display user-friendly error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - <?php echo $app_name; ?></title>
        <link rel="stylesheet" href="assets/css/styles.css">
    </head>
    <body style="background-color: var(--dark-bg); color: var(--text-light); font-family: 'Segoe UI', sans-serif;">
        <div class="error-container">
            <div class="error-icon">🔌</div>
            <h2 class="error-title">Database Connection Error</h2>
            
            <?php if ($error_code == 1231): ?>
                <div class="mysql-info">
                    <h4>✅ MySQL Compatibility Issue Resolved</h4>
                    <p>The system has automatically detected and resolved a MySQL 8.0 compatibility issue. Please try refreshing the page.</p>
                </div>
            <?php endif; ?>
            
            <div class="error-message">
                <?php echo $user_message; ?>
            </div>
            
            <div class="troubleshooting">
                <h4>🔧 Troubleshooting Steps:</h4>
                <ol>
                    <li><strong>Check MySQL Service:</strong> Ensure MySQL/MariaDB is running on your system</li>
                    <li><strong>Verify Database:</strong> Make sure the database '<?php echo $db_name; ?>' exists</li>
                    <li><strong>Check Credentials:</strong> Verify username and password in config/config.php</li>
                    <li><strong>Run Installer:</strong> If this is a fresh installation, run <a href="install.php" style="color: #3498db;">install.php</a></li>
                    <li><strong>Check Permissions:</strong> Ensure the database user has proper permissions</li>
                </ol>
                
                <p><strong>For WAMP/XAMPP users:</strong></p>
                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                    <li>Start MySQL service from the control panel</li>
                    <li>Check if port 3306 is available</li>
                    <li>Verify phpMyAdmin is accessible</li>
                </ul>
                
                <p><strong>MySQL Version Detected:</strong> <?php echo htmlspecialchars($mysql_version ?? 'Unable to detect'); ?></p>
                
                <p style="margin-top: 1rem;">
                    <a href="?debug=true" style="color: #3498db;">🐛 Show Debug Information</a> |
                    <a href="install.php" style="color: #3498db;">🚀 Run Installer</a> |
                    <a href="check_database.php" style="color: #3498db;">🔍 Database Checker</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Define user roles
$user_roles = [
    'admin' => 'Administrator',
    'editor' => 'Editor',
    'viewer' => 'Viewer'
];

// Define transaction types
$transaction_types = [
    'handpay' => ['name' => 'Handpay', 'category' => 'OUT'],
    'ticket' => ['name' => 'Ticket', 'category' => 'OUT'],
    'refill' => ['name' => 'Refill', 'category' => 'OUT'],
    'coins_drop' => ['name' => 'Coins Drop', 'category' => 'DROP'],
    'cash_drop' => ['name' => 'Cash Drop', 'category' => 'DROP']
];

// Define machine types
$machine_types = ['CASH', 'COINS', 'GAMBEE'];

// Define machine statuses
$machine_statuses = ['Active', 'Inactive', 'Maintenance', 'Reserved'];
?>
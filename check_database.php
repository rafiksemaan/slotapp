<?php
/**
 * Database Connection Checker
 * Use this script to diagnose database connection issues
 */

// Database configuration (same as config.php)
$db_host = 'localhost';
$db_name = 'slotapp';
$db_user = 'root';
$db_pass = '';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Connection Checker</title>
</head>
<body>
    <div class='container'>
        <h1>🔍 Database Connection Checker</h1>";

echo "<h2>1. Testing MySQL Extension</h2>";
if (extension_loaded('pdo_mysql')) {
    echo "<div class='success'>✅ PDO MySQL extension is loaded</div>";
} else {
    echo "<div class='error'>❌ PDO MySQL extension is NOT loaded</div>";
    echo "<div class='step'>
        <strong>Solution:</strong> Enable PDO MySQL extension in your PHP configuration:
        <ul>
            <li>For WAMP: Enable php_pdo_mysql in PHP extensions</li>
            <li>For XAMPP: Should be enabled by default</li>
            <li>For Linux: Install php-mysql package</li>
        </ul>
    </div>";
}

echo "<h2>2. Testing Database Connection</h2>";
try {
    $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    echo "<div class='success'>✅ Successfully connected to MySQL server</div>";
    
    // Test if database exists
    $stmt = $conn->query("SHOW DATABASES LIKE '$db_name'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Database '$db_name' exists</div>";
        
        // Test connection to specific database
        try {
            $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            echo "<div class='success'>✅ Successfully connected to database '$db_name'</div>";
            
            // Test if tables exist
            $tables = ['users', 'machines', 'transactions', 'brands', 'machine_types'];
            $existing_tables = [];
            $missing_tables = [];
            
            foreach ($tables as $table) {
                $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existing_tables[] = $table;
                } else {
                    $missing_tables[] = $table;
                }
            }
            
            if (!empty($existing_tables)) {
                echo "<div class='success'>✅ Found tables: " . implode(', ', $existing_tables) . "</div>";
            }
            
            if (!empty($missing_tables)) {
                echo "<div class='warning'>⚠️ Missing tables: " . implode(', ', $missing_tables) . "</div>";
                echo "<div class='step'>
                    <strong>Solution:</strong> Run the installer or import the database schema:
                    <br><a href='install.php' class='btn'>Run Installer</a>
                    <br>Or import the SQL file manually in phpMyAdmin
                </div>";
            } else {
                echo "<div class='success'>✅ All required tables exist</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Cannot connect to database '$db_name': " . $e->getMessage() . "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Database '$db_name' does not exist</div>";
        echo "<div class='step'>
            <strong>Solution:</strong> Create the database manually or run the installer:
            <br>1. Open phpMyAdmin
            <br>2. Create a new database named '$db_name'
            <br>3. Or <a href='install.php' class='btn'>Run Installer</a>
        </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Cannot connect to MySQL server: " . $e->getMessage() . "</div>";
    
    $error_code = $e->getCode();
    switch ($error_code) {
        case 1045:
            echo "<div class='step'>
                <strong>Access Denied (Error 1045):</strong>
                <br>• Check username and password in config/config.php
                <br>• Default WAMP/XAMPP credentials: username='root', password=''
                <br>• Make sure MySQL user has proper permissions
            </div>";
            break;
        case 2002:
            echo "<div class='step'>
                <strong>Cannot Connect to Server (Error 2002):</strong>
                <br>• Make sure MySQL/MariaDB service is running
                <br>• For WAMP: Start MySQL service from WAMP control panel
                <br>• For XAMPP: Start MySQL from XAMPP control panel
                <br>• Check if port 3306 is available
            </div>";
            break;
        default:
            echo "<div class='step'>
                <strong>General Connection Error:</strong>
                <br>• Check if MySQL service is running
                <br>• Verify host, username, and password settings
                <br>• Check firewall settings
            </div>";
    }
}

echo "<h2>3. System Information</h2>";
echo "<div class='info'>
    <strong>PHP Version:</strong> " . PHP_VERSION . "<br>
    <strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>
    <strong>Host:</strong> $db_host<br>
    <strong>Database:</strong> $db_name<br>
    <strong>User:</strong> $db_user<br>
    <strong>Password:</strong> " . (empty($db_pass) ? 'Empty' : 'Set') . "
</div>";

echo "<h2>4. Quick Actions</h2>";
echo "<a href='install.php' class='btn'>🚀 Run Installer</a>";
echo "<a href='index.php' class='btn'>🏠 Go to Application</a>";
echo "<a href='check_database.php' class='btn'>🔄 Refresh Check</a>";

echo "<h2>5. Manual Database Setup</h2>";
echo "<div class='step'>
    If the automatic installer doesn't work, you can set up the database manually:
    <ol>
        <li>Open phpMyAdmin (usually at <code>http://localhost/phpmyadmin</code>)</li>
        <li>Create a new database named <code>$db_name</code></li>
        <li>Import the <code>database_setup.sql</code> file</li>
        <li>Or copy and paste the SQL commands from the file</li>
    </ol>
</div>";

echo "</div></body></html>";
?>
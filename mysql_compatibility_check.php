<?php
/**
 * MySQL Compatibility Fix Script
 * Run this script to fix MySQL 8.0 compatibility issues
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>MySQL Compatibility Fix</title>
</head>
<body>
    <div class='container'>
        <h1>🔧 MySQL Compatibility Fix</h1>";

try {
    // Connect to MySQL server
    $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get MySQL version
    $stmt = $conn->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    $mysql_version = $result['version'];
    
    echo "<div class='info'><strong>MySQL Version:</strong> $mysql_version</div>";
    
    // Check if this is MySQL 8.0 or higher
    if (version_compare($mysql_version, '8.0.0', '>=')) {
        echo "<div class='success'>✅ MySQL 8.0+ detected - NO_AUTO_CREATE_USER is not needed</div>";
        
        // Set compatible sql_mode
        $sql_mode = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION";
        $conn->exec("SET GLOBAL sql_mode = '$sql_mode'");
        $conn->exec("SET SESSION sql_mode = '$sql_mode'");
        
        echo "<div class='success'>✅ SQL mode updated for MySQL 8.0 compatibility</div>";
        echo "<div class='info'>New SQL mode: $sql_mode</div>";
        
    } else {
        echo "<div class='info'>MySQL version is older than 8.0 - NO_AUTO_CREATE_USER is supported</div>";
        
        // Set sql_mode with NO_AUTO_CREATE_USER for older versions
        $sql_mode = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";
        $conn->exec("SET GLOBAL sql_mode = '$sql_mode'");
        $conn->exec("SET SESSION sql_mode = '$sql_mode'");
        
        echo "<div class='success'>✅ SQL mode set for MySQL < 8.0</div>";
        echo "<div class='info'>SQL mode: $sql_mode</div>";
    }
    
    // Test the connection with the new settings
    $test_conn = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='$sql_mode'"
    ]);
    
    echo "<div class='success'>✅ Connection test successful with new settings</div>";
    
    echo "<h2>✅ Fix Applied Successfully!</h2>";
    echo "<p>The MySQL compatibility issue has been resolved. You can now:</p>";
    echo "<a href='index.php' class='btn'>🏠 Go to Application</a>";
    echo "<a href='install.php' class='btn'>🚀 Run Installer</a>";
    echo "<a href='check_database.php' class='btn'>🔍 Check Database</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    
    if ($e->getCode() == 1045) {
        echo "<div class='info'>Please check your database credentials in the script.</div>";
    } elseif ($e->getCode() == 2002) {
        echo "<div class='info'>Please make sure MySQL service is running.</div>";
    }
    
    echo "<a href='check_database.php' class='btn'>🔍 Run Database Checker</a>";
}

echo "</div></body></html>";
?>
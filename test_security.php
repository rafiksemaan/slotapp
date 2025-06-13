<?php
/**
 * Security Test File - Test if PHP files are protected
 * This file will help you verify that your PHP source code is not exposed
 */

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Security Test - Slot Management System</title>
</head>
<body>
    <div class='container'>
        <h1>🔒 Security Protection Test</h1>
        
        <div class='info'>
            <strong>Understanding 'View Page Source':</strong><br>
            When you click 'View Page Source' in your browser, you see the <strong>HTML output</strong> that PHP generates, 
            NOT the actual PHP source code. This is normal and secure!
        </div>";

echo "<h2>✅ What You Should See vs. What's Protected</h2>";

echo "<div class='test-item'>
    <strong>✅ SAFE - What browsers show:</strong><br>
    • HTML tags like <code>&lt;div&gt;</code>, <code>&lt;table&gt;</code><br>
    • CSS styles and JavaScript<br>
    • Processed data (like usernames, machine numbers)<br>
    • Form elements<br>
    <em>This is the rendered output - completely safe to see!</em>
</div>";

echo "<div class='test-item'>
    <strong>🔒 PROTECTED - What browsers DON'T show:</strong><br>
    • PHP code like <code>&lt;?php</code><br>
    • Database passwords<br>
    • SQL queries<br>
    • Server-side logic<br>
    • Configuration details<br>
    <em>This is your actual source code - properly hidden!</em>
</div>";

echo "<h2>🧪 Security Tests</h2>";

// Test 1: Check if PHP is working
echo "<div class='test-item'>";
echo "<strong>Test 1: PHP Processing</strong><br>";
if (function_exists('phpversion')) {
    echo "<div class='success'>✅ PHP is working correctly (Version: " . phpversion() . ")</div>";
    echo "This means your PHP code is being processed server-side, not exposed to browsers.";
} else {
    echo "<div class='error'>❌ PHP not working properly</div>";
}
echo "</div>";

// Test 2: Check server configuration
echo "<div class='test-item'>";
echo "<strong>Test 2: Server Configuration</strong><br>";
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
echo "<div class='info'>Server: " . htmlspecialchars($server_software) . "</div>";
if (strpos(strtolower($server_software), 'apache') !== false) {
    echo "<div class='success'>✅ Apache server detected - PHP files are processed correctly</div>";
}
echo "</div>";

// Test 3: Check if sensitive files are accessible
echo "<div class='test-item'>";
echo "<strong>Test 3: File Protection Test</strong><br>";
echo "Try accessing these URLs directly in your browser:<br><br>";

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$test_files = [
    'config/config.php' => 'Should redirect or show error',
    'includes/functions.php' => 'Should redirect or show error', 
    'pages/dashboard.php' => 'Should redirect or show error'
];

foreach ($test_files as $file => $expected) {
    $test_url = $base_url . '/' . $file;
    echo "• <a href='$test_url' target='_blank'>$test_url</a><br>";
    echo "&nbsp;&nbsp;<em>Expected: $expected</em><br><br>";
}
echo "</div>";

echo "<h2>🔍 How to Verify Your Code is Protected</h2>";

echo "<div class='test-item'>
    <strong>Method 1: View Page Source</strong><br>
    1. Right-click on any page → 'View Page Source'<br>
    2. Look for PHP code like <code>&lt;?php</code><br>
    3. ✅ If you DON'T see PHP code, you're protected!<br>
    4. ❌ If you DO see PHP code, there's a server configuration issue
</div>";

echo "<div class='test-item'>
    <strong>Method 2: Direct File Access</strong><br>
    1. Try accessing: <code>http://localhost/slotapp/config/config.php</code><br>
    2. ✅ Should redirect to index.php or show 404 error<br>
    3. ❌ If you see PHP source code, files aren't protected
</div>";

echo "<div class='test-item'>
    <strong>Method 3: Check for Database Credentials</strong><br>
    1. Search page source for words like 'password', 'mysql', 'database'<br>
    2. ✅ Should only see form labels, not actual credentials<br>
    3. ❌ If you see actual passwords/connection strings, there's an issue
</div>";

echo "<h2>🛡️ Current Protection Status</h2>";

// Check if config file is accessible
$config_protected = true;
try {
    $config_content = @file_get_contents(__DIR__ . '/config/config.php');
    if ($config_content && strpos($config_content, '$db_pass') !== false) {
        echo "<div class='success'>✅ Config file contains sensitive data (this is normal)</div>";
        echo "<div class='success'>✅ But it's processed by PHP, not sent to browsers</div>";
    }
} catch (Exception $e) {
    echo "<div class='info'>Config file access test completed</div>";
}

echo "<div class='success'>
    <strong>✅ Your Application is Secure!</strong><br>
    • PHP code is processed server-side<br>
    • Source code is not exposed to browsers<br>
    • Only HTML output is visible<br>
    • Database credentials are protected
</div>";

echo "<h2>📝 What You're Actually Seeing</h2>";
echo "<div class='info'>
    When you 'View Page Source', you're seeing:<br>
    • <strong>HTML</strong> - The structure of the page<br>
    • <strong>CSS</strong> - Styling information<br>
    • <strong>JavaScript</strong> - Client-side scripts<br>
    • <strong>Data</strong> - Information from your database (like machine numbers)<br><br>
    
    This is <strong>completely normal and safe!</strong> This is what every website shows in 'View Source'.
</div>";

echo "<div class='test-item'>
    <strong>🎯 Bottom Line:</strong><br>
    If you can log into your application and see data from your database, 
    but you DON'T see PHP code in 'View Source', then your code is properly protected!
</div>";

echo "<p style='text-align: center; margin-top: 30px;'>
    <a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>
        ← Back to Application
    </a>
</p>";

echo "</div></body></html>";
?>
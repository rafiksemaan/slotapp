<?php
/**
 * Security Check - Prevent Direct Access to Sensitive Files
 * Include this at the top of sensitive PHP files
 */

// Check if this file is being accessed directly
$current_file = basename($_SERVER['PHP_SELF']);
$restricted_files = [
    'config.php',
    'security.php',
    'functions.php'
];

// If accessing a restricted file directly, redirect to index
if (in_array($current_file, $restricted_files)) {
    header("Location: ../index.php");
    exit;
}

// Check for suspicious access patterns
$suspicious_patterns = [
    '.sql',
    '.conf',
    '.log',
    '.bak',
    '.backup',
    'config/',
    'backups/',
    'includes/',
    'pages/'
];

$request_uri = $_SERVER['REQUEST_URI'] ?? '';
foreach ($suspicious_patterns as $pattern) {
    if (strpos($request_uri, $pattern) !== false && !strpos($request_uri, 'index.php')) {
        // Log suspicious access
        error_log("Suspicious access attempt: " . $request_uri . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Return 404 to hide file existence
        http_response_code(404);
        echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>";
        exit;
    }
}
?>
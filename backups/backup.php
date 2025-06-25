<?php
/**
 * Daily Database Backup Script for WAMP
 * Usage: Run this via Windows Task Scheduler
 */

// Database credentials
$host = 'localhost';
$user = 'root'; // Default WAMP MySQL user
$pass = 'Mcmxcix7//6'; // Default WAMP MySQL password is empty
$dbname = 'slotapp'; // Your database name

// Backup directory (outside web root for security)
$backupDir = 'C:/wamp64/www/slotapp/backups/db_daily_backups/';
$date = date('Y-m-d');
$filename = $backupDir . "db_backup_{$date}.sql";

// Full path to mysqldump (adjust based on your WAMP version)
$mysqldump_path = 'C:/wamp64/bin/mysql/mysql8.0.31/bin/mysqldump.exe'; // Verify this path

// Build command (Windows-safe)
$command = "\"{$mysqldump_path}\" -h {$host} -u {$user} " . 
           (!empty($pass) ? "-p\"{$pass}\" " : "") . 
           "{$dbname} > \"{$filename}\"";

// Execute command (requires exec() enabled in PHP)
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Backup successful: $filename\n";
} else {
    die("Backup failed with error code: $return_var\n");
}

// Delete backups older than 8 days
$files = glob($backupDir . "db_backup_*.sql");
foreach ($files as $file) {
    $fileDate = substr(basename($file), 11, 10); // Extract date from filename
    $backupTime = strtotime($fileDate);
    if (time() - $backupTime > 8 * 24 * 60 * 60) {
        unlink($file);
        echo "Deleted old backup: " . basename($file) . "\n";
    }
}
?>
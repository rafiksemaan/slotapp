<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'slotapp';
$db_user = 'root';
$db_pass = ''; // Update with your actual password if needed

// Application settings
$app_name = 'Slot Management System';
$app_version = '1.0.0';
$app_url = 'http://localhost/slotapp'; // Update with your actual URL

// Establish database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
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
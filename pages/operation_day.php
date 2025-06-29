<?php
/**
 * Operation Day management page - Admin only
 */

$page = $_GET['page'] ?? 'operation_day';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    include 'pages/access_denied.php';
    exit;
}

// Define allowed actions
$allowed_actions = ['view', 'set'];
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

// Validate action
if (!in_array($action, $allowed_actions)) {
    $action = 'view';
}

// Process based on action
switch ($action) {
    case 'set':
        include 'operation_day/set.php';
        break;
        
    case 'view':
    default:
        include 'operation_day/view.php';
        break;
}
?>
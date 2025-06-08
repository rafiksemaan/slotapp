<?php
/**
 * Machine Groups management page
 */

$page = $_GET['page'] ?? 'machine_groups';

// Define allowed actions
$allowed_actions = ['list', 'create', 'edit', 'delete', 'view'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Validate action
if (!in_array($action, $allowed_actions)) {
    $action = 'list';
}

// Check permissions - only admin can access machine groups
if ($_SESSION['user_role'] !== 'admin') {
    include 'access_denied.php';
    exit;
}

// Process based on action
switch ($action) {
    case 'create':
        include 'machine_groups/create.php';
        break;
        
    case 'edit':
        include 'machine_groups/edit.php';
        break;
        
    case 'delete':
        include 'machine_groups/delete.php';
        break;
        
    case 'view':
        include 'machine_groups/view.php';
        break;
        
    case 'list':
    default:
        include 'machine_groups/list.php';
        break;
}
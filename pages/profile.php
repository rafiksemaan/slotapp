<?php
/**
 * User Profile Management Page
 */

$page = $_GET['page'] ?? 'profile';

// Define allowed actions
$allowed_actions = ['view', 'edit'];
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

// Validate action
if (!in_array($action, $allowed_actions)) {
    $action = 'view';
}

// Process based on action
switch ($action) {
    case 'edit':
        include 'profile/edit.php';
        break;
        
    case 'view':
    default:
        include 'profile/view.php';
        break;
}
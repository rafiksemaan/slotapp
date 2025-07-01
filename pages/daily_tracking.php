<?php
/**
 * Daily Tracking management page
 */

$page = $_GET['page'] ?? 'daily_tracking';

// Define allowed actions
$allowed_actions = ['list', 'create', 'edit', 'delete', 'view'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Validate action
if (!in_array($action, $allowed_actions)) {
    $action = 'list';
}

// Check permissions
$can_edit = has_permission('editor');
$can_view = has_permission('viewer');

// If user doesn't have permission, show access denied
if (!$can_view) {
    include 'access_denied.php';
    exit;
}

// Process based on action
switch ($action) {
    case 'create':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'daily_tracking/create.php';
        break;
        
    case 'edit':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'daily_tracking/edit.php';
        break;
        
    case 'delete':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'daily_tracking/delete.php';
        break;
        
    case 'view':
        include 'daily_tracking/view.php';
        break;
        
    case 'list':
    default:
        include 'daily_tracking/list.php';
        break;
}
?>
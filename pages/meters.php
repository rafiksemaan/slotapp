<?php
/**
 * Meters management page
 */

$page = $_GET['page'] ?? 'meters';

// Define allowed actions
$allowed_actions = ['list', 'create', 'edit', 'delete', 'view', 'upload'];
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
        include 'meters/create.php';
        break;
        
    case 'upload':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'meters/upload.php';
        break;
        
    case 'edit':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'meters/edit.php'; // Include the edit page
        break;
        
    case 'delete':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'meters/delete.php'; // Include the delete page
        break;
        
    case 'view':
        // View action might require an ID
        // Example: include 'meters/view.php';
        echo "<h2>View Meter Entry</h2><p>This page will display details of a specific meter entry.</p>";
        break;
        
    case 'list':
    default:
        include 'meters/list.php';
        break;
}
?>

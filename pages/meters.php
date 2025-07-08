<?php
/**
 * Meters management page
 */

$page = $_GET['page'] ?? 'meters';

// Define allowed actions
$allowed_actions = ['list', 'create', 'edit', 'delete', 'view', 'upload', 'machine_entries'];
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
        // For GET requests, display the upload form.
        // POST requests are now handled directly by pages/meters/upload.php
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'meters/upload.php'; // This will now only render the HTML form
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
        include 'meters/view.php';
        break;

    case 'machine_entries':
        include 'meters/machine_entries.php';
        break;
        
    case 'list':
    default:
        include 'meters/list.php';
        break;
}
?>

<?php
/**
 * Guest Tracking management page
 */

$page = $_GET['page'] ?? 'guest_tracking';

// Define allowed actions
$allowed_actions = ['list', 'upload', 'view', 'delete_upload', 'export'];
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
    case 'upload':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'guest_tracking/upload.php';
        break;
        
    case 'view':
        include 'guest_tracking/view.php';
        break;
        
    case 'delete_upload':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'guest_tracking/delete_upload.php';
        break;
        
    case 'export':
        include 'guest_tracking/export.php';
        break;
        
    case 'list':
    default:
        include 'guest_tracking/list.php';
        break;
}
?>
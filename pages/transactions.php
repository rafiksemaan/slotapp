<?php
/**
 * Transactions management page
 */

$page = $_GET['page'] ?? 'transactions';

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
        include 'transactions/create.php';
        break;
        
    case 'edit':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'transactions/edit.php';
        break;
        
    case 'delete':
        if (!$can_edit) {
            include 'access_denied.php';
            exit;
        }
        include 'transactions/delete.php';
        break;
        
    case 'view':
        include 'transactions/view.php';
        break;
        
    case 'list':
    default:
        include 'transactions/list.php';
        break;
}
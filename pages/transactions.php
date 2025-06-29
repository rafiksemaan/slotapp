<?php
/**
 * Transactions management page
 */

$page = $_GET['page'] ?? 'transactions';

// Handle export requests first
if (isset($_GET['export'])) {
    $export_type = $_GET['export']; // 'pdf' or 'excel'
    
    // Get all the same parameters as the main report
    $date_range_type = $_GET['date_range_type'] ?? 'month';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-t');
    $month = $_GET['month'] ?? date('Y-m');
    $machine = $_GET['machine'] ?? 'all';
    $category = $_GET['category'] ?? '';
    $transaction_type = $_GET['transaction_type'] ?? 'all';
    $sort_column = $_GET['sort'] ?? 'timestamp';
    $sort_order = $_GET['order'] ?? 'DESC';
    
    // Define the export handler constant
    define('EXPORT_HANDLER', true);
    
    // Include the export handler
    if ($export_type === 'pdf') {
        include 'transactions/export_pdf.php';
    } elseif ($export_type === 'excel') {
        include 'transactions/export_excel.php';
    } else {
        header("Location: index.php?page=transactions&error=Invalid export type");
    }
    exit;
}

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
?>
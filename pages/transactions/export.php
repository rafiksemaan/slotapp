<?php
/**
 * Export handler for transactions
 * Handles both PDF and Excel exports
 */

// Handle export requests
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
    
    // Include the export handler
    if ($export_type === 'pdf') {
        include 'export_pdf.php';
    } elseif ($export_type === 'excel') {
        include 'export_excel.php';
    } else {
        header("Location: index.php?page=transactions&error=Invalid export type");
    }
    exit;
}

// If no export type specified, redirect back
header("Location: index.php?page=transactions");
exit;
?>
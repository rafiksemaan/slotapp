<?php
/**
 * Export handler for guest tracking data
 * Handles both PDF and Excel exports
 */

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export']; // 'pdf' or 'excel'
    
    // Get all the same parameters as the main report
    $date_range_type = $_GET['date_range_type'] ?? 'all_time';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-t');
    $month = $_GET['month'] ?? date('Y-m');
    $guest_search = $_GET['guest_search'] ?? '';
    $sort_column = $_GET['sort'] ?? 'total_drop';
    $sort_order = $_GET['order'] ?? 'DESC';
    
    // Include the export handler
    if ($export_type === 'pdf') {
        include 'export_pdf.php';
    } elseif ($export_type === 'excel') {
        include 'export_excel.php';
    } else {
        header("Location: index.php?page=guest_tracking&error=Invalid export type");
    }
    exit;
}

// If no export type specified, redirect back
header("Location: index.php?page=guest_tracking");
exit;
?>
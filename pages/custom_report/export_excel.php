<?php
/**
 * Excel Export for Custom Reports
 * Uses CSV format for Excel compatibility
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    header("Location: index.php?page=custom_report");
    exit;
}

// Clear any output buffers completely
while (ob_get_level()) {
    ob_end_clean();
}

// Ensure no whitespace or HTML is output before headers
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line");
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 (helps with Excel encoding)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write report header information
fputcsv($output, ['Slot Management System - Custom Report']);
fputcsv($output, ['Report Title', $report_title]);
fputcsv($output, ['Scope', $report_subtitle]);
fputcsv($output, ['Period', $date_subtitle]);
fputcsv($output, ['Generated', cairo_time('d M Y – H:i:s')]);
fputcsv($output, ['Total Records', count($results)]);
fputcsv($output, []); // Empty row

// Write column headers
if (!empty($selected_columns)) {
    $headers = [];
    foreach ($selected_columns as $column) {
        if (isset($available_columns[$column])) {
            $headers[] = $available_columns[$column];
        }
    }
    fputcsv($output, $headers);
    
    // Write data rows
    if (!empty($results)) {
        foreach ($results as $row) {
            $csv_row = [];
            foreach ($selected_columns as $column) {
                $value = $row[$column] ?? 'N/A';
                
                // Format specific columns for Excel
                if (in_array($column, ['credit_value', 'total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'])) {
                    // For Excel, we want numeric values without currency symbols
                    $csv_row[] = is_numeric($value) ? number_format((float)$value, 2, '.', '') : '0.00';
                } else {
                    $csv_row[] = $value;
                }
            }
            fputcsv($output, $csv_row);
        }
        
        // Add empty row before totals
        fputcsv($output, []);
        
        // Add totals row (excluding credit_value)
        if (!empty($totals)) {
            $totals_row = [];
            foreach ($selected_columns as $column) {
                if ($column === 'machine_number') {
                    $totals_row[] = 'TOTALS';
                } elseif ($column === 'credit_value') {
                    // Skip credit value in totals - just add empty cell
                    $totals_row[] = '';
                } elseif (isset($totals[$column])) {
                    $totals_row[] = number_format($totals[$column], 2, '.', '');
                } else {
                    $totals_row[] = '';
                }
            }
            fputcsv($output, $totals_row);
        }
    } else {
        fputcsv($output, ['No data found for the selected criteria.']);
    }
} else {
    fputcsv($output, ['No columns selected for the report.']);
}

// Add summary section
fputcsv($output, []); // Empty row
fputcsv($output, ['Summary Information']);

// Calculate totals if applicable (excluding credit_value)
if (!empty($results) && !empty($selected_columns)) {
    // Write totals summary
    if (!empty($totals)) {
        fputcsv($output, []); // Empty row
        fputcsv($output, ['Column Totals']);
        foreach ($totals as $column => $total) {
            // Skip credit_value from summary totals
            if ($column !== 'credit_value' && isset($available_columns[$column])) {
                fputcsv($output, [$available_columns[$column], number_format($total, 2, '.', '')]);
            }
        }
    }
}

// Close output stream
fclose($output);

// Ensure no additional output
exit;
?>
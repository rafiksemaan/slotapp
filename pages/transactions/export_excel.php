<?php
/**
 * Excel Export for Transactions
 * Uses CSV format for Excel compatibility
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    define('EXPORT_HANDLER', true);
}

// Clear any output buffers completely
while (ob_get_level()) {
    ob_end_clean();
}

// Ensure no whitespace or HTML is output before headers
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line");
}

// Calculate start and end dates
if ($date_range_type === 'range') {
    $start_date = $date_from;
    $end_date = $date_to;
} else {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Build query for export data
try {
    $params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];
    
    $query = "SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username,
                     b.name as brand_name
              FROM transactions t
              JOIN machines m ON t.machine_id = m.id
              LEFT JOIN brands b ON m.brand_id = b.id
              JOIN transaction_types tt ON t.transaction_type_id = tt.id
              JOIN users u ON t.user_id = u.id
              WHERE t.timestamp BETWEEN ? AND ?";
    
    // Apply filters
    if ($machine !== 'all') {
        $query .= " AND t.machine_id = ?";
        $params[] = $machine;
    }
    if ($category === 'OUT') {
        $query .= " AND tt.category = 'OUT'";
    } elseif ($category === 'DROP') {
        $query .= " AND tt.category = 'DROP'";
    }
    if ($transaction_type !== 'all') {
        $query .= " AND t.transaction_type_id = ?";
        $params[] = $transaction_type;
    }
    
    // Add sorting
    $sort_map = [
        'timestamp' => 't.timestamp',
        'machine_number' => 'm.machine_number',
        'transaction_type' => 'tt.name',
        'amount' => 't.amount',
        'username' => 'u.username'
    ];
    
    $actual_sort_column = $sort_map[$sort_column] ?? 't.timestamp';
    $query .= " ORDER BY $actual_sort_column $sort_order";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get machines for display
    $machines_stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name 
        FROM machines m 
        LEFT JOIN brands b ON m.brand_id = b.id 
        ORDER BY m.machine_number
    ");
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get transaction types for display
    $types_stmt = $conn->query("SELECT id, name FROM transaction_types ORDER BY category, name");
    $transaction_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Clean any output buffer
    ob_clean();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Generate filename
$date_suffix = '';
if ($date_range_type === 'range') {
    $date_suffix = '_' . $date_from . '_to_' . $date_to;
} else {
    $date_suffix = '_' . $month;
}

$filename = 'transactions_export' . $date_suffix . '_' . date('Y-m-d_H-i-s') . '.csv';

// Generate report titles
$report_title = "Transactions Export";

if ($machine !== 'all') {
    $selected_machine = null;
    foreach ($machines as $m) {
        if ($m['id'] == $machine) {
            $selected_machine = $m;
            break;
        }
    }
    $report_subtitle = "Machine #" . ($selected_machine['machine_number'] ?? 'N/A');
    if ($selected_machine['brand_name']) {
        $report_subtitle .= " (" . $selected_machine['brand_name'] . ")";
    }
} else {
    $report_subtitle = "All Machines";
}

if ($transaction_type !== 'all') {
    $selected_type = null;
    foreach ($transaction_types as $type) {
        if ($type['id'] == $transaction_type) {
            $selected_type = $type;
            break;
        }
    }
    $report_subtitle .= " - " . ($selected_type['name'] ?? 'Unknown Type');
} elseif (!empty($category)) {
    $report_subtitle .= " - " . ucfirst($category);
}

if ($date_range_type === 'range') {
    $date_subtitle = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
} else {
    $date_subtitle = date('F Y', strtotime($month));
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
fputcsv($output, ['Slot Management System - Transactions Export']);
fputcsv($output, ['Report Title', $report_title]);
fputcsv($output, ['Scope', $report_subtitle]);
fputcsv($output, ['Period', $date_subtitle]);
fputcsv($output, ['Generated', cairo_time('d M Y – H:i:s')]);
fputcsv($output, ['Total Records', count($transactions)]);
fputcsv($output, []); // Empty row

// Write column headers
$headers = [
    'Date & Time',
    'Machine Number',
    'Transaction Type',
    'Amount',
    'Category',
    'User',
    'Notes'
];
fputcsv($output, $headers);

// Write data rows
if (!empty($transactions)) {
    foreach ($transactions as $t) {
        $csv_row = [
            format_datetime($t['timestamp'], 'd M Y - H:i:s'),
            $t['machine_number'],
            $t['transaction_type'],
            is_numeric($t['amount']) ? number_format((float)$t['amount'], 2, '.', '') : '0.00',
            $t['category'],
            $t['username'],
            $t['notes'] ?? ''
        ];
        fputcsv($output, $csv_row);
    }
    
    // Add empty row before totals
    fputcsv($output, []);
    
    // Calculate and add totals
    $total_out = 0;
    $total_drop = 0;
    foreach ($transactions as $t) {
        if ($t['category'] === 'OUT') {
            $total_out += (float)$t['amount'];
        } elseif ($t['category'] === 'DROP') {
            $total_drop += (float)$t['amount'];
        }
    }
    $total_result = $total_drop - $total_out;
    
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total DROP', number_format($total_drop, 2, '.', '')]);
    fputcsv($output, ['Total OUT', number_format($total_out, 2, '.', '')]);
    fputcsv($output, ['Result', number_format($total_result, 2, '.', '')]);
} else {
    fputcsv($output, ['No transactions found for the selected criteria.']);
}

// Close output stream
fclose($output);

// Ensure no additional output
exit;
?>
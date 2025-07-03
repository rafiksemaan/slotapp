<?php
/**
 * Excel Export for Guest Tracking Data
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

// Calculate date range for filtering
$date_filter = '';
$date_params = [];

if ($date_range_type === 'range') {
    $date_filter = " AND gd.upload_date BETWEEN ? AND ?";
    $date_params = [$date_from, $date_to];
} elseif ($date_range_type === 'month') {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
    $date_filter = " AND gd.upload_date BETWEEN ? AND ?";
    $date_params = [$start_date, $end_date];
} elseif ($date_range_type === 'latest_upload') {
    // Apply filter for only the latest upload date
    $date_filter = " AND gd.upload_date = ?";
    $date_params = [$latest_upload_date];
}

// Build query for export data
try {
    $query = "
        SELECT 
            g.guest_code_id,
            g.guest_name,
            SUM(gd.drop_amount) as total_drop,
            SUM(gd.result_amount) as total_result,
            SUM(gd.visits) as total_visits,
            MAX(gd.upload_date) as last_visit,
            COUNT(DISTINCT gd.upload_date) as upload_count
        FROM guests g
        LEFT JOIN guest_data gd ON g.guest_code_id = gd.guest_code_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add date filter
    if (!empty($date_filter)) {
        $query .= $date_filter;
        $params = array_merge($params, $date_params);
    }
    
    // Add guest search filter
    if (!empty($guest_search)) {
        $query .= " AND (g.guest_code_id LIKE ? OR g.guest_name LIKE ?)";
        $params[] = "%$guest_search%";
        $params[] = "%$guest_search%";
    }
    
    $query .= " GROUP BY g.guest_code_id, g.guest_name";
    
    // Add sorting
    $allowed_columns = ['guest_code_id', 'guest_name', 'total_drop', 'total_result', 'total_visits', 'last_visit'];
    if (in_array($sort_column, $allowed_columns)) {
        $query .= " ORDER BY $sort_column $sort_order";
    } else {
        $query .= " ORDER BY total_drop DESC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
} elseif ($date_range_type === 'month') {
    $date_suffix = '_' . $month;
}

$filename = 'guest_tracking_export' . $date_suffix . '_' . date('Y-m-d_H-i-s') . '.csv';

// Generate report titles
$report_title = "Guest Tracking Export";
if (!empty($guest_search)) {
    $report_subtitle = "Search: " . $guest_search;
} else {
    $report_subtitle = "All Guests";
}

if ($date_range_type === 'range') {
    $date_subtitle = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
} elseif ($date_range_type === 'month') {
    $date_subtitle = date('F Y', strtotime($month));
} else {
    $date_subtitle = "All Time";
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
fputcsv($output, ['Slot Management System - Guest Tracking Export']);
fputcsv($output, ['Report Title', $report_title]);
fputcsv($output, ['Scope', $report_subtitle]);
fputcsv($output, ['Period', $date_subtitle]);
fputcsv($output, ['Generated', cairo_time('d M Y – H:i:s')]);
fputcsv($output, ['Total Records', count($guests)]);
fputcsv($output, []); // Empty row

// Write column headers
$headers = [
    'Guest Code ID',
    'Guest Name', 
    'Total Drop',
    'Total Result',
    'Total Visits',
    'Last Visit',
    'Upload Count'
];
fputcsv($output, $headers);

// Write data rows
if (!empty($guests)) {
    foreach ($guests as $guest) {
        $csv_row = [
            $guest['guest_code_id'],
            $guest['guest_name'],
            is_numeric($guest['total_drop']) ? number_format((float)$guest['total_drop'], 2, '.', '') : '0.00',
            is_numeric($guest['total_result']) ? number_format((float)$guest['total_result'], 2, '.', '') : '0.00',
            $guest['total_visits'],
            $guest['last_visit'] ? date('Y-m-d', strtotime($guest['last_visit'])) : '',
            $guest['upload_count']
        ];
        fputcsv($output, $csv_row);
    }
    
    // Add empty row before totals
    fputcsv($output, []);
    
    // Add totals row
    $total_drop = array_sum(array_column($guests, 'total_drop'));
    $total_result = array_sum(array_column($guests, 'total_result'));
    $total_visits = array_sum(array_column($guests, 'total_visits'));
    
    $totals_row = [
        'TOTALS',
        '',
        number_format($total_drop, 2, '.', ''),
        number_format($total_result, 2, '.', ''),
        $total_visits,
        '',
        ''
    ];
    fputcsv($output, $totals_row);
} else {
    fputcsv($output, ['No data found for the selected criteria.']);
}

// Add summary section
fputcsv($output, []); // Empty row
fputcsv($output, ['Summary Information']);

// Calculate summary statistics
if (!empty($guests)) {
    fputcsv($output, []); // Empty row
    fputcsv($output, ['Summary Statistics']);
    fputcsv($output, ['Total Guests', count($guests)]);
    fputcsv($output, ['Total Drop Amount', number_format($total_drop, 2, '.', '')]);
    fputcsv($output, ['Total Result Amount', number_format($total_result, 2, '.', '')]);
    fputcsv($output, ['Total Visits', $total_visits]);
    fputcsv($output, ['Average Drop per Guest', count($guests) > 0 ? number_format($total_drop / count($guests), 2, '.', '') : '0.00']);
    fputcsv($output, ['Average Result per Guest', count($guests) > 0 ? number_format($total_result / count($guests), 2, '.', '') : '0.00']);
    fputcsv($output, ['Average Visits per Guest', count($guests) > 0 ? number_format($total_visits / count($guests), 1, '.', '') : '0.0']);
}

// Close output stream
fclose($output);

// Ensure no additional output
exit;
?>
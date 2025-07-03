<?php
/**
 * Excel Export for Reports
 * Uses CSV format for Excel compatibility
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    header("Location: index.php?page=reports");
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

// Generate filename
$date_suffix = '';
if ($date_range_type === 'range') {
    $date_suffix = '_' . $date_from . '_to_' . $date_to;
} else {
    $date_suffix = '_' . $month;
}

$filename = 'reports_export' . $date_suffix . '_' . date('Y-m-d_H-i-s') . '.csv';

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
fputcsv($output, ['Slot Management System - Reports Export']);
fputcsv($output, ['Report Title', $report_title]);
fputcsv($output, ['Scope', $report_subtitle]);
fputcsv($output, ['Period', $date_subtitle]);
fputcsv($output, ['Generated', cairo_time('d M Y – H:i:s')]);
fputcsv($output, ['Total Records', count($filtered_data)]);
fputcsv($output, []); // Empty row

// Write column headers
$headers = [
    'Machine #',
    'Type',
    'Coins Drop',
    'Cash Drop',
    'Total DROP',
    'Handpay',
    'Ticket',
    'Refill',
    'Total OUT',
    'Result'
];
fputcsv($output, $headers);

// Write data rows
if (!empty($filtered_data)) {
    foreach ($filtered_data as $data) {
        $handpay = $ticket = $refill = $coins_drop = $cash_drop = 0;
        if (!empty($data['transactions']) && is_array($data['transactions'])) {
            foreach ($data['transactions'] as $t) {
                switch ($t['type']) {
                    case 'Handpay': $handpay = $t['amount']; break;
                    case 'Ticket': $ticket = $t['amount']; break;
                    case 'Refill': $refill = $t['amount']; break;
                    case 'Coins Drop': $coins_drop = $t['amount']; break;
                    case 'Cash Drop': $cash_drop = $t['amount']; break;
                }
            }
        }

        $csv_row = [
            $data['machine_number'],
            $data['type'],
            number_format($coins_drop, 2, '.', ''),
            number_format($cash_drop, 2, '.', ''),
            number_format($data['total_drop'], 2, '.', ''),
            number_format($handpay, 2, '.', ''),
            number_format($ticket, 2, '.', ''),
            number_format($refill, 2, '.', ''),
            number_format($data['total_out'], 2, '.', ''),
            number_format($data['result'], 2, '.', '')
        ];
        fputcsv($output, $csv_row);
    }
    
    // Add empty row before totals
    fputcsv($output, []);
    
    // Add totals row
    fputcsv($output, [
        'TOTALS',
        '',
        '',
        '',
        number_format($grand_total_drop, 2, '.', ''),
        '',
        '',
        '',
        number_format($grand_total_out, 2, '.', ''),
        number_format($grand_total_result, 2, '.', '')
    ]);
} else {
    fputcsv($output, ['No data found for the selected criteria.']);
}

// Close output stream
fclose($output);

// Ensure no additional output
exit;
?>

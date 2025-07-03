<?php
/**
 * PDF Export for Guest Tracking Data
 * Uses direct HTML output for browser printing with auto-save functionality
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    define('EXPORT_HANDLER', true);
}

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
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
    echo "Error: " . $e->getMessage();
    exit;
}

// Generate custom filename
$date_part = cairo_time('d-M-Y_H-i-s');
$custom_filename = "Guest_Tracking_Export_{$date_part}";

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

// Calculate totals
$total_drop = array_sum(array_column($guests, 'total_drop'));
$total_result = array_sum(array_column($guests, 'total_result'));
$total_visits = array_sum(array_column($guests, 'total_visits'));

// Set content type for HTML
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($custom_filename) ?></title>
    <link rel="stylesheet" href="assets/css/export_pdf_common.css">
    <script src="assets/js/export_pdf_common.js"></script>
</head>
<body>
    <div id="printable-content">
        <div class="report-header">
			<img src="<?= icon('sgc') ?>" alt="Logo" class="header-logo">
            <h1><?= htmlspecialchars($report_title) ?></h1>
            <h2><?= htmlspecialchars($report_subtitle) ?></h2>
            <div class="date-range"><?= htmlspecialchars($date_subtitle) ?></div>
            <div class="generated-at">Generated at: <?= cairo_time('d M Y – H:i:s') ?></div>
        </div>

        <!-- Summary Statistics -->
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-title">Total Guests</div>
                <div class="stat-value"><?= number_format(count($guests)) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Total Drop</div>
                <div class="stat-value"><?= format_currency($total_drop) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Total Result</div>
                <div class="stat-value <?= $total_result >= 0 ? 'positive' : 'negative' ?>"><?= format_currency($total_result) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Total Visits</div>
                <div class="stat-value"><?= number_format($total_visits) ?></div>
            </div>
        </div>

        <?php if (!empty($guests)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Guest Code ID</th>
                        <th>Guest Name</th>
                        <th class="currency">Total Drop</th>
                        <th class="currency">Total Result</th>
                        <th class="currency">Total Visits</th>
                        <th>Last Visit</th>
                        <th class="currency">Upload Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guests as $guest): ?>
                        <tr>
                            <td><?= htmlspecialchars($guest['guest_code_id']) ?></td>
                            <td><?= htmlspecialchars($guest['guest_name']) ?></td>
                            <td class="currency"><?= format_currency($guest['total_drop']) ?></td>
                            <td class="currency <?= $guest['total_result'] >= 0 ? 'positive' : 'negative' ?>">
                                <?= format_currency($guest['total_result']) ?>
                            </td>
                            <td class="currency"><?= number_format($guest['total_visits']) ?></td>
                            <td><?= $guest['last_visit'] ? format_date($guest['last_visit']) : 'N/A' ?></td>
                            <td class="currency"><?= number_format($guest['upload_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Totals Row -->
                    <tr class="totals-row">
                        <td><strong>TOTALS</strong></td>
                        <td></td>
                        <td class="currency"><strong><?= format_currency($total_drop) ?></strong></td>
                        <td class="currency"><strong><?= format_currency($total_result) ?></strong></td>
                        <td class="currency"><strong><?= number_format($total_visits) ?></strong></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                No guest data found for the selected criteria.
            </div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Slot Management System - Guest Tracking Export</strong></p>
            <p>Report generated on <?= cairo_time('d M Y - H:i:s') ?> | Total records: <?= count($guests) ?></p>
        </div>
    </div>
</body>
</html>
<?php
// Ensure no additional output after HTML
exit;
?>
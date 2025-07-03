<?php
/**
 * PDF Export for Custom Reports
 * Uses direct HTML output for browser printing with auto-save functionality
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    header("Location: index.php?page=custom_report");
    exit;
}

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Generate custom filename
$date_part = cairo_time('d-M-Y_H-i-s');
$custom_filename = "Custom_Report_{$date_part}";

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

        <?php if (!empty($results) && !empty($selected_columns)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($selected_columns as $column): ?>
                            <?php if (isset($available_columns[$column])): ?>
                                <th><?= htmlspecialchars($available_columns[$column]) ?></th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <?php foreach ($selected_columns as $column): ?>
                                <td <?php 
                                    // Add currency class for monetary columns
                                    if (in_array($column, ['credit_value', 'total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'])) {
                                        echo 'class="currency"';
                                    }
                                ?>>
                                    <?php
                                    $value = $row[$column] ?? 'N/A';
                                    
                                    // Format specific columns
                                    if (in_array($column, ['credit_value', 'total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'])) {
                                        echo format_currency($value);
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Totals Row (excluding credit_value) -->
                    <?php if (!empty($totals)): ?>
                        <tr class="totals-row">
                            <?php foreach ($selected_columns as $column): ?>
                                <td <?php 
                                    if (in_array($column, ['credit_value', 'total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'])) {
                                        echo 'class="currency"';
                                    }
                                ?>>
                                    <?php if ($column === 'machine_number'): ?>
                                        <strong>TOTALS</strong>
                                    <?php elseif ($column === 'credit_value'): ?>
                                        <!-- Skip credit value in totals - empty cell -->
                                        <strong>-</strong>
                                    <?php elseif (isset($totals[$column])): ?>
                                        <strong><?= format_currency($totals[$column]) ?></strong>
                                    <?php else: ?>
                                        <!-- Empty cell for non-monetary columns -->
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <?php if (empty($selected_columns)): ?>
                    No columns selected for the report.
                <?php else: ?>
                    No data found for the selected criteria.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Slot Management System - Custom Report</strong></p>
            <p>Report generated on <?= cairo_time('d M Y - H:i:s') ?> | Total records: <?= count($results) ?></p>
        </div>
    </div>
</body>
</html>
<?php
// Ensure no additional output after HTML
exit;
?>
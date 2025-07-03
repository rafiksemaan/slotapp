<?php
/**
 * PDF Export for Reports
 * Uses direct HTML output for browser printing with auto-save functionality
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    header("Location: index.php?page=reports");
    exit;
}

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Generate custom filename
$date_part = cairo_time('d-M-Y_H-i-s');
$custom_filename = "Reports_Export_{$date_part}";

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

        <!-- Summary Statistics - Professional Number-Focused Layout -->
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-title">Total DROP</div>
                <div class="stat-value"><?= '$' . number_format($grand_total_drop, 0) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Total OUT</div>
                <div class="stat-value"><?= '$' . number_format($grand_total_out, 0) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Result</div>
                <div class="stat-value <?= $grand_total_result >= 0 ? 'positive' : 'negative' ?>"><?= '$' . number_format($grand_total_result, 0) ?></div>
            </div>
        </div>

        <?php if (!empty($filtered_data)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Machine #</th>
                        <th>Type</th>
                        <th>Coins Drop</th>
                        <th>Cash Drop</th>
                        <th>Total DROP</th>
                        <th>Handpay</th>
                        <th>Ticket</th>
                        <th>Refill</th>
                        <th>Total OUT</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered_data as $data): ?>
                        <tr>
                            <td><?= htmlspecialchars($data['machine_number']) ?></td>
                            <td><?= htmlspecialchars($data['type']) ?></td>
                            <?php
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
                            ?>
                            <td class="currency"><?= format_currency($coins_drop) ?></td>
                            <td class="currency"><?= format_currency($cash_drop) ?></td>
                            <td class="currency category-drop"><strong><?= format_currency($data['total_drop']) ?></strong></td>
                            <td class="currency"><?= format_currency($handpay) ?></td>
                            <td class="currency"><?= format_currency($ticket) ?></td>
                            <td class="currency"><?= format_currency($refill) ?></td>
                            <td class="currency category-out"><strong><?= format_currency($data['total_out']) ?></strong></td>
                            <td class="currency <?= $data['result'] >= 0 ? 'positive' : 'negative' ?>"><strong><?= format_currency($data['result']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Totals Row -->
                    <tr class="totals-row">
                        <td colspan="4"><strong>TOTALS</strong></td>
                        <td class="currency"><strong><?= format_currency($grand_total_drop) ?></strong></td>
                        <td colspan="3"></td>
                        <td class="currency"><strong><?= format_currency($grand_total_out) ?></strong></td>
                        <td class="currency <?= $grand_total_result >= 0 ? 'positive' : 'negative' ?>"><strong><?= format_currency($grand_total_result) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                No data found for the selected criteria.
            </div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Slot Management System - Reports Export</strong></p>
            <p>Report generated on <?= cairo_time('d M Y - H:i:s') ?> | Total records: <?= count($filtered_data) ?></p>
        </div>
    </div>
</body>
</html>
<?php
// Ensure no additional output after HTML
exit;
?>

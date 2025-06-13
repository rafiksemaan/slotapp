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
    <script>
        window.onload = function() {
            // Show loading message
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'loading-message';
            loadingDiv.innerHTML = '📄 Preparing PDF...<br><small>Save dialog will open shortly</small>';
            document.body.appendChild(loadingDiv);
            
            // Auto-print with save as PDF after a short delay
            setTimeout(function() {
                // Set the document title to the custom filename for the save dialog
                document.title = '<?= $custom_filename ?>';
                
                // Trigger print dialog (user can choose "Save as PDF")
                window.print();
                
                // Remove loading message
                loadingDiv.remove();
            }, 1000);
        }
        
        // Auto-close window after printing/saving
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 500);
        }
        
        // Fallback: close window if user cancels print dialog
        window.addEventListener('beforeunload', function() {
            // This will trigger if user closes the tab/window
        });
        
        // Additional method to detect print dialog cancellation
        let printDialogOpen = false;
        window.addEventListener('focus', function() {
            if (printDialogOpen) {
                // User likely cancelled the print dialog, close window
                setTimeout(function() {
                    window.close();
                }, 1000);
            }
        });
        
        window.addEventListener('blur', function() {
            printDialogOpen = true;
        });
    </script>
</head>
<body>
    <div id="printable-content">
        <div class="report-header">
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
            <p>Slot Management System - Custom Report</p>
            <p>Report generated on <?= cairo_time('d M Y - H:i:s') ?> | Total records: <?= count($results) ?></p>
        </div>
    </div>
</body>
</html>
<?php
// Ensure no additional output after HTML
exit;
?>
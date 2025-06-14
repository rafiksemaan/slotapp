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
    <style>
        /* Professional PDF Export Styles */
        :root {
            --primary-color: #1a2b4d;
            --secondary-color: #c4933f;
            --accent-color: #e74c3c;
            --dark-bg: #121212;
            --light-bg: #1e1e1e;
            --text-light: #ffffff;
            --text-dark: #333333;
            --text-muted: #666666;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --border-color: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            color: var(--text-dark);
            line-height: 1.6;
            padding: 20px;
        }

        /* Loading Message Styles */
        .loading-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            border: 2px solid var(--secondary-color);
        }

        .loading-message small {
            display: block;
            margin-top: 10px;
            font-size: 14px;
            opacity: 0.9;
            font-weight: normal;
        }

        /* Report Header Styles */
        .report-header {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 3px solid var(--secondary-color);
        }

        .report-header h1 {
            font-size: 28px;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .report-header h2 {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .report-header .date-range {
            font-size: 18px;
            font-weight: 500;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .report-header .generated-at {
            font-size: 14px;
            color: #cccccc;
            font-style: italic;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        thead {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
        }

        th {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            border-bottom: 2px solid var(--secondary-color);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e8f4f8;
        }

        .currency {
            text-align: right;
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Totals Row */
        .totals-row {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50) !important;
            color: white !important;
            font-weight: bold;
        }

        .totals-row td {
            border-top: 3px solid var(--secondary-color);
            padding: 15px 12px;
            font-size: 14px;
        }

        .totals-row .currency {
            color: white;
        }

        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            font-size: 16px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #ddd;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
        }

        .footer p {
            margin-bottom: 5px;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
                background: white;
                font-size: 11px;
            }

            .loading-message {
                display: none !important;
            }

            .report-header {
                background: var(--primary-color) !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                page-break-inside: avoid;
            }

            .report-header h1 {
                color: var(--secondary-color) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table {
                page-break-inside: auto;
                font-size: 10px;
            }

            thead {
                background: var(--primary-color) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            th {
                background-color: var(--primary-color) !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .totals-row {
                background: var(--primary-color) !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tr {
                page-break-inside: avoid;
            }

            .footer {
                page-break-inside: avoid;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .report-header {
                padding: 20px;
            }

            .report-header h1 {
                font-size: 22px;
            }

            .report-header h2 {
                font-size: 16px;
            }

            table {
                font-size: 11px;
            }

            th, td {
                padding: 8px 6px;
            }
        }
    </style>
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
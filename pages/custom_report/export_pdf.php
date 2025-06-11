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

// Generate custom filename
$date_part = cairo_time('d-M-Y - H:i:s');
$custom_filename = "Custom_Report_{$date_part}";

// Set content type for HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($custom_filename) ?></title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: white;
            color: black;
            font-size: 12px;
            line-height: 1.4;
        }
        
        /* Hide everything except printable content during print */
        @media print {
            body * {
                visibility: hidden;
            }
            
            #printable-content, #printable-content * {
                visibility: visible;
            }
            
            #printable-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 10px;
            }
        }
        
        /* Report header */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .report-header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #333;
        }
        
        .report-header h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #666;
        }
        
        .report-header .date-range {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .report-header .generated-at {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }
        
        /* Table styles with improved spacing and borders */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        
        th, td {
            border: 1px solid #e0e0e0; /* Lighter grey borders */
            padding: 4px 6px; /* Reduced padding: 4px vertical, 6px horizontal */
            text-align: left;
            vertical-align: middle;
        }
        
        th {
            background-color: #f8f9fa; /* Very light grey background */
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .currency {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .totals-row {
            background-color: #f0f0f0; /* Light grey background for totals */
            font-weight: bold;
            border-top: 2px solid #333;
        }
        
        .totals-row td {
            border-top: 2px solid #333;
            padding: 6px;
            font-weight: bold;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        /* Loading message */
        .loading-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 20px 40px;
            border-radius: 8px;
            font-size: 18px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            z-index: 1000;
            text-align: center;
        }
        
        /* Print-specific styles for the selected div */
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .loading-message {
                display: none !important;
            }
            
            .report-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }
            
            table {
                font-size: 9px;
                margin-top: 15px;
            }
            
            th, td {
                padding: 2px 4px; /* Even more compact for print */
                border: 1px solid #d0d0d0; /* Slightly lighter for print */
                font-size: 9px;
            }
            
            th {
                background-color: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .totals-row {
                background-color: #eeeeee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .footer {
                margin-top: 20px;
                font-size: 8px;
            }
            
            /* Ensure page breaks work well */
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
        }
        
        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            table {
                font-size: 10px;
            }
            
            th, td {
                padding: 3px 4px;
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
                    
                    <!-- Totals Row -->
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
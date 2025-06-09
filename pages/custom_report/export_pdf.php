<?php
/**
 * PDF Export for Custom Reports
 * Uses HTML to PDF conversion
 */

// Prevent direct access
if (!defined('EXPORT_HANDLER')) {
    header("Location: index.php?page=custom_report");
    exit;
}

// Set content type for PDF
header('Content-Type: text/html; charset=utf-8');

// Generate filename
$filename = 'custom_report_' . date('Y-m-d_H-i-s') . '.pdf';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($report_title) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: white;
            color: black;
        }
        
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .currency {
            text-align: right;
        }
        
        .totals-row {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        
        .totals-row td {
            border-top: 2px solid #333;
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
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .report-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }
            
            table {
                font-size: 10px;
            }
            
            th, td {
                padding: 6px;
            }
        }
    </style>
    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
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
                                    TOTALS
                                <?php elseif (isset($totals[$column])): ?>
                                    <?= format_currency($totals[$column]) ?>
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
        <p>Report generated on <?= date('Y-m-d H:i:s') ?> | Total records: <?= count($results) ?></p>
    </div>
</body>
</html>
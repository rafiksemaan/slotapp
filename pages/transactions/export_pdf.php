<?php
/**
 * PDF Export for Transactions
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
    
    $query = "SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category,
                     b.name as brand_name
              FROM transactions t
              JOIN machines m ON t.machine_id = m.id
              LEFT JOIN brands b ON m.brand_id = b.id
              JOIN transaction_types tt ON t.transaction_type_id = tt.id
              WHERE t.operation_date BETWEEN ? AND ?";
    
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
        'operation_date' => 't.operation_date',
        'machine_number' => 'm.machine_number',
        'transaction_type' => 'tt.name',
        'amount' => 't.amount',
        'username' => 'u.username'
    ];
    
    $actual_sort_column = $sort_map[$sort_column] ?? 't.operation_date';
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
    ob_clean();
    echo "Error: " . $e->getMessage();
    exit;
}

// Generate custom filename
$date_part = cairo_time('d-M-Y_H-i-s');
$custom_filename = "Transactions_Export_{$date_part}";

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

// Calculate totals
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
        /* Professional PDF Export Styles - Ultra Compact */
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
            line-height: 1.2;
            padding: 15px;
            font-size: 11px;
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

        /* Report Header Styles - Compact */
        .report-header {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
            color: white;
            padding: 15px;
            margin-bottom: 12px;
            text-align: center;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--secondary-color);
        }

        .report-header h1 {
            font-size: 20px;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 6px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .report-header h2 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            color: #ffffff;
        }

        .report-header .date-range {
            font-size: 13px;
            font-weight: 500;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .report-header .generated-at {
            font-size: 10px;
            color: #cccccc;
            font-style: italic;
        }

        /* Summary Stats - Ultra Compact Single Line Layout */
        .summary-stats {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 12px;
            padding: 8px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid var(--secondary-color);
            border-radius: 4px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-box {
            flex: 1;
            text-align: center;
            padding: 4px 3px;
            background: white;
            border-radius: 3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            min-width: 0;
        }

        .stat-box .stat-title {
            font-size: 8px;
            color: var(--text-muted);
            margin-bottom: 2px;
            text-transform: uppercase;
            font-weight: 600;
            line-height: 1.1;
        }

        .stat-box .stat-value {
            font-size: 11px;
            font-weight: bold;
            color: var(--primary-color);
            line-height: 1.1;
        }

        /* Table Styles - Ultra Compact */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background-color: white;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
            font-size: 9px;
        }

        thead {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
        }

        th {
            background-color: var(--primary-color);
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border-bottom: 1px solid var(--secondary-color);
            line-height: 1.1;
        }

        td {
            padding: 3px 4px;
            border-bottom: 1px solid #eee;
            font-size: 8px;
            line-height: 1.2;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f0f0f0;
        }

        .currency {
            text-align: right;
            font-weight: 500;
            color: var(--text-dark);
        }

        .positive {
            color: var(--success-color);
        }

        .negative {
            color: var(--danger-color);
        }

        /* Totals Row - Compact */
        .totals-row {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50) !important;
            color: white !important;
            font-weight: bold;
        }

        .totals-row td {
            border-top: 2px solid var(--secondary-color);
            padding: 6px 4px;
            font-size: 9px;
        }

        .totals-row .currency {
            color: white;
        }

        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 12px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px dashed #ddd;
        }

        /* Footer - Compact */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-muted);
            font-size: 8px;
        }

        .footer p {
            margin-bottom: 3px;
        }

        /* Print Styles - Ultra Compact */
        @media print {
            body {
                padding: 8px;
                background: white;
                font-size: 8px;
                line-height: 1.1;
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
                margin-bottom: 8px;
                padding: 10px;
            }

            .report-header h1 {
                color: var(--secondary-color) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 16px;
            }

            .summary-stats {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin-bottom: 8px;
                padding: 4px;
                gap: 3px;
            }

            .stat-box {
                background: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 3px 2px;
            }

            .stat-box .stat-title {
                font-size: 7px;
            }

            .stat-box .stat-value {
                font-size: 9px;
            }

            table {
                page-break-inside: auto;
                font-size: 7px;
                margin-bottom: 8px;
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
                padding: 4px 3px;
                font-size: 7px;
            }

            td {
                padding: 2px 3px;
                font-size: 7px;
                line-height: 1.1;
            }

            .totals-row {
                background: var(--primary-color) !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 4px 3px;
                font-size: 7px;
            }

            tr {
                page-break-inside: avoid;
            }

            .footer {
                page-break-inside: avoid;
                font-size: 6px;
                margin-top: 10px;
                padding-top: 5px;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 8px;
                font-size: 10px;
            }

            .report-header {
                padding: 12px;
            }

            .report-header h1 {
                font-size: 16px;
            }

            .report-header h2 {
                font-size: 12px;
            }

            .summary-stats {
                flex-direction: column;
                gap: 4px;
            }

            .stat-box {
                padding: 6px;
            }

            .stat-box .stat-title {
                font-size: 9px;
            }

            .stat-box .stat-value {
                font-size: 12px;
            }

            table {
                font-size: 8px;
            }

            th, td {
                padding: 4px 3px;
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

        <!-- Summary Statistics - Ultra Compact Single Line Layout -->
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-title">Transactions</div>
                <div class="stat-value"><?= number_format(count($transactions)) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Total DROP</div>
                <div class="stat-value"><?= '$' . number_format($total_drop, 0) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Total OUT</div>
                <div class="stat-value"><?= '$' . number_format($total_out, 0) ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Result</div>
                <div class="stat-value <?= $total_result >= 0 ? 'positive' : 'negative' ?>"><?= '$' . number_format($total_result, 0) ?></div>
            </div>
        </div>

        <?php if (!empty($transactions)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Machine</th>
                        <th>Transaction Type</th>
                        <th class="currency">Amount</th>
                        <th>Category</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars(format_date($t['operation_date'])) ?></td>
                            <td><?= htmlspecialchars($t['machine_number']) ?></td>
                            <td><?= htmlspecialchars($t['transaction_type']) ?></td>
                            <td class="currency"><?= '$' . number_format($t['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($t['category']) ?></td>
                            <td><?= htmlspecialchars($t['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Totals Row -->
                    <tr class="totals-row">
                        <td colspan="3"><strong>TOTALS</strong></td>
                        <td class="currency"><strong><?= '$' . number_format($total_drop + $total_out, 2) ?></strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                No transactions found for the selected criteria.
            </div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Slot Management System - Transactions Export</strong></p>
            <p>Report generated on <?= cairo_time('d M Y - H:i:s') ?> | Total records: <?= count($transactions) ?></p>
        </div>
    </div>
</body>
</html>
<?php
// Ensure no additional output after HTML
exit;
?>
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
        /* Professional Light Color Scheme - Number-Focused Design */
        :root {
            /* Professional Light Color Palette */
            --primary-blue: #2563eb;
            --primary-blue-light: #3b82f6;
            --primary-blue-dark: #1d4ed8;
            
            --secondary-gray: #64748b;
            --secondary-gray-light: #94a3b8;
            --secondary-gray-dark: #475569;
            
            --accent-green: #059669;
            --accent-green-light: #10b981;
            --accent-red: #dc2626;
            --accent-red-light: #ef4444;
            
            --neutral-50: #f8fafc;
            --neutral-100: #f1f5f9;
            --neutral-200: #e2e8f0;
            --neutral-300: #cbd5e1;
            --neutral-400: #94a3b8;
            --neutral-500: #64748b;
            --neutral-600: #475569;
            --neutral-700: #334155;
            --neutral-800: #1e293b;
            --neutral-900: #0f172a;
            
            /* Semantic Colors */
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-accent: #f1f5f9;
            
            --border-light: #e2e8f0;
            --border-medium: #cbd5e1;
            --border-dark: #94a3b8;
            
            /* Number Emphasis Colors */
            --number-primary: #1e293b;
            --number-positive: #059669;
            --number-negative: #dc2626;
            --number-neutral: #475569;
            --number-highlight: #2563eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
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
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-light));
            color: white;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.3);
            z-index: 10000;
            border: 1px solid var(--primary-blue-light);
        }

        .loading-message small {
            display: block;
            margin-top: 10px;
            font-size: 14px;
            opacity: 0.9;
            font-weight: normal;
        }

        /* Report Header - Clean Professional Design */
        .report-header {
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            border: 2px solid var(--primary-blue);
            color: var(--text-primary);
            padding: 20px;
            margin-bottom: 15px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .report-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .report-header h2 {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text-secondary);
        }

        .report-header .date-range {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .report-header .generated-at {
            font-size: 11px;
            color: var(--text-muted);
            font-style: italic;
        }

        /* Summary Stats - Professional Number-Focused Design */
        .summary-stats {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 15px;
            padding: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-box {
            flex: 1;
            text-align: center;
            padding: 8px 6px;
            background: var(--bg-primary);
            border: 1px solid var(--border-light);
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            min-width: 0;
        }

        .stat-box .stat-title {
            font-size: 9px;
            color: var(--text-muted);
            margin-bottom: 3px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .stat-box .stat-value {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.1;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
        }

        /* Number-specific colors */
        .stat-box:nth-child(1) .stat-value { color: var(--text-secondary); } /* Transactions */
        .stat-box:nth-child(2) .stat-value { color: var(--accent-green); } /* DROP */
        .stat-box:nth-child(3) .stat-value { color: var(--accent-red); } /* OUT */
        .stat-box:nth-child(4) .stat-value { color: var(--number-highlight); } /* Result */

        /* Table Styles - Clean Professional Design */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-medium);
            border-radius: 8px;
            overflow: hidden;
            font-size: 9px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        thead {
            background: linear-gradient(135deg, var(--neutral-100), var(--neutral-200));
        }

        th {
            background-color: var(--neutral-100);
            color: var(--text-primary);
            padding: 8px 6px;
            text-align: left;
            font-weight: 700;
            font-size: 9px;
            border-bottom: 2px solid var(--primary-blue);
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Right-align number columns */
        th:nth-child(4) { text-align: right; } /* Amount */

        td {
            padding: 4px 6px;
            border-bottom: 1px solid var(--border-light);
            font-size: 8px;
            line-height: 1.2;
            vertical-align: top;
        }

        /* Alternating row colors for better readability */
        tr:nth-child(even) {
            background-color: var(--bg-secondary);
        }

        tr:hover {
            background-color: var(--bg-accent);
        }

        /* Number formatting and emphasis */
        .currency {
            text-align: right;
            font-weight: 600;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            color: var(--number-primary);
        }

        /* Category-specific styling */
        .category-drop {
            color: var(--accent-green);
            font-weight: 600;
        }

        .category-out {
            color: var(--accent-red);
            font-weight: 600;
        }

        /* Positive/Negative number styling */
        .positive {
            color: var(--number-positive);
            font-weight: 700;
        }

        .negative {
            color: var(--number-negative);
            font-weight: 700;
        }

        /* Totals Row - Professional Emphasis */
        .totals-row {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-light)) !important;
            color: white !important;
            font-weight: 700;
        }

        .totals-row td {
            border-top: 3px solid var(--primary-blue-dark);
            padding: 8px 6px;
            font-size: 9px;
            font-weight: 700;
        }

        .totals-row .currency {
            color: white;
            font-weight: 700;
        }

        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            font-size: 14px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            border: 2px dashed var(--border-medium);
        }

        /* Footer - Clean Design */
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid var(--border-light);
            text-align: center;
            color: var(--text-muted);
            font-size: 9px;
        }

        .footer p {
            margin-bottom: 4px;
        }

        /* Print Styles - Optimized for Professional Printing */
        @media print {
            body {
                padding: 10px;
                background: white;
                font-size: 8px;
                line-height: 1.1;
            }

            .loading-message {
                display: none !important;
            }

            .report-header {
                background: var(--bg-primary) !important;
                border: 2px solid var(--primary-blue) !important;
                color: var(--text-primary) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                page-break-inside: avoid;
                margin-bottom: 10px;
                padding: 15px;
            }

            .report-header h1 {
                color: var(--primary-blue) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 20px;
            }

            .summary-stats {
                background: var(--bg-secondary) !important;
                border: 1px solid var(--border-light) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin-bottom: 10px;
                padding: 8px;
                gap: 4px;
            }

            .stat-box {
                background: var(--bg-primary) !important;
                border: 1px solid var(--border-light) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 6px 4px;
            }

            .stat-box .stat-title {
                font-size: 7px;
            }

            .stat-box .stat-value {
                font-size: 10px;
            }

            /* Ensure number colors print correctly */
            .stat-box:nth-child(2) .stat-value { 
                color: var(--accent-green) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .stat-box:nth-child(3) .stat-value { 
                color: var(--accent-red) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .stat-box:nth-child(4) .stat-value { 
                color: var(--number-highlight) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table {
                page-break-inside: auto;
                font-size: 7px;
                margin-bottom: 10px;
                border: 1px solid var(--border-medium) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            thead {
                background: var(--neutral-100) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            th {
                background-color: var(--neutral-100) !important;
                color: var(--text-primary) !important;
                border-bottom: 2px solid var(--primary-blue) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 6px 4px;
                font-size: 7px;
            }

            td {
                padding: 3px 4px;
                font-size: 7px;
                line-height: 1.1;
            }

            tr:nth-child(even) {
                background-color: var(--bg-secondary) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .currency {
                color: var(--number-primary) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .category-drop {
                color: var(--accent-green) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .category-out {
                color: var(--accent-red) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .totals-row {
                background: var(--primary-blue) !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .totals-row td {
                border-top: 3px solid var(--primary-blue-dark) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 6px 4px;
                font-size: 7px;
            }

            .totals-row .currency {
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tr {
                page-break-inside: avoid;
            }

            .footer {
                page-break-inside: avoid;
                font-size: 7px;
                margin-top: 15px;
                padding-top: 10px;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
                font-size: 10px;
            }

            .report-header {
                padding: 15px;
            }

            .report-header h1 {
                font-size: 20px;
            }

            .report-header h2 {
                font-size: 14px;
            }

            .summary-stats {
                flex-direction: column;
                gap: 6px;
            }

            .stat-box {
                padding: 8px;
            }

            .stat-box .stat-title {
                font-size: 10px;
            }

            .stat-box .stat-value {
                font-size: 14px;
            }

            table {
                font-size: 8px;
            }

            th, td {
                padding: 6px 4px;
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

        <!-- Summary Statistics - Professional Number-Focused Layout -->
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
                        <th>Amount</th>
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
                            <td class="category-<?= strtolower($t['category']) ?>"><?= htmlspecialchars($t['category']) ?></td>
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
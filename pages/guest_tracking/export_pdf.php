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

        /* Summary Stats */
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-box .stat-title {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-box .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
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

        .positive {
            color: var(--success-color);
        }

        .negative {
            color: var(--danger-color);
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

            .stat-box {
                background: #f8f9fa !important;
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

            .summary-stats {
                grid-template-columns: 1fr;
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
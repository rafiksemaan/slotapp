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
    <style><link rel="stylesheet" href="assets/css/export_pdf_common.css"></style>
    <script src="assets/js/export_pdf_common.js"></script>
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
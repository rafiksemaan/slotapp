<?php
/**
 * Reports page
 * Shows comprehensive reports with filtering options
 */

$page = $_GET['page'] ?? 'reports';

// Handle export requests first
if (isset($_GET['export'])) {
    $export_type = $_GET['export']; // 'pdf' or 'excel'
    
    // Get all the same parameters as the main report
    $date_range_type = $_GET['date_range_type'] ?? 'month';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-t');
    $month = $_GET['month'] ?? date('Y-m');
    $machine_id = $_GET['machine_id'] ?? 'all'; // Changed from $machine to $machine_id for consistency
    $brand_id = $_GET['brand_id'] ?? 'all';
    $machine_group_id = $_GET['machine_group_id'] ?? 'all'; // New filter parameter
    $category = $_GET['category'] ?? ''; // This filter is not used in this report, but kept for consistency if needed
    $transaction_type = $_GET['transaction_type'] ?? 'all'; // This filter is not used in this report, but kept for consistency if needed
    $sort_column = $_GET['sort'] ?? 'timestamp'; // This report doesn't have a sortable table, but kept for consistency if needed
    $sort_order = $_GET['order'] ?? 'DESC'; // This report doesn't have a sortable table, but kept for consistency if needed
    
    // Define the export handler constant
    define('EXPORT_HANDLER', true);
    
    // Include the export handler
    if ($export_type === 'pdf') {
        include 'reports/export_pdf.php';
    } elseif ($export_type === 'excel') {
        include 'reports/export_excel.php';
    } else {
        header("Location: index.php?page=reports&error=Invalid export type");
    }
    exit;
}

// Get filter values from URL
$machine_id = $_GET['machine_id'] ?? 'all';
$brand_id = $_GET['brand_id'] ?? 'all';
$machine_group_id = $_GET['machine_group_id'] ?? 'all'; // New filter parameter
$date_range_type = $_GET['date_range_type'] ?? 'month';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$month = $_GET['month'] ?? date('Y-m');

// Calculate start and end dates
if ($date_range_type === 'range') {
    $start_date = $date_from;
    $end_date = $date_to;
} else {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

$params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];

// Initialize variables
$report_data = [];
$filtered_data = [];
$grand_total_out = 0;
$grand_total_drop = 0;
$grand_total_result = 0;
$machines = [];

// Get brands for filter dropdown
try {
    $brands_stmt = $conn->query("SELECT id, name FROM brands ORDER BY name");
    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $brands = [];
}

// Get machine groups for filter dropdown
try {
    $groups_stmt = $conn->query("SELECT id, name FROM machine_groups ORDER BY name");
    $machine_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $machine_groups = [];
}

// Build query for report data
$query = "
    SELECT 
        m.id AS machine_id,
        m.machine_number,
        mt.name AS machine_type,
        tt.id AS transaction_type_id,
        tt.name AS transaction_type,
        tt.category,
        COALESCE(SUM(t.amount), 0) AS total_amount
    FROM 
        machines m
    LEFT JOIN 
        machine_types mt ON m.type_id = mt.id
    LEFT JOIN 
        transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?
    LEFT JOIN 
        transaction_types tt ON t.transaction_type_id = tt.id
";

$where_clauses = [];
$query_params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];

if ($machine_id != 'all') {
    $where_clauses[] = "m.id = ?";
    $query_params[] = $machine_id;
}

if ($brand_id != 'all') {
    $where_clauses[] = "m.brand_id = ?";
    $query_params[] = $brand_id;
}

if ($machine_group_id != 'all') {
    $query .= " JOIN machine_group_members mgm ON m.id = mgm.machine_id";
    $where_clauses[] = "mgm.group_id = ?";
    $query_params[] = $machine_group_id;
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= "
    GROUP BY 
        m.id, tt.id
    ORDER BY 
        m.machine_number
";

try {
    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params); // Use $query_params here
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get machines for filter dropdown with brand information
    $query = "SELECT m.id, m.machine_number, b.name as brand_name, mt.name as type FROM machines m 
              LEFT JOIN brands b ON m.brand_id = b.id 
              LEFT JOIN machine_types mt ON m.type_id = mt.id";
    $params = [];

    if ($brand_id !== 'all') {
        $query .= " WHERE m.brand_id = ?";
        $params[] = $brand_id;
    }

    $query .= " ORDER BY CAST(m.machine_number AS UNSIGNED)";

    $machines_stmt = $conn->prepare($query);
    $machines_stmt->execute($params);
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize report_data with default values for all machines
    foreach ($machines as $machine) {
        $report_data[$machine['id']] = [
            'machine_id' => $machine['id'],
            'machine_number' => $machine['machine_number'],
            'type' => $machine['type'] ?? 'N/A',
            'transactions' => [],
            'total_out' => 0,
            'total_drop' => 0,
            'result' => 0
        ];
    }

    // Fill in transaction data where it exists
    foreach ($results as $row) {
        if (!isset($report_data[$row['machine_id']])) {
            continue; // Skip unknown machines
        }

        if (!is_null($row['transaction_type_id'])) {
            $amount = floatval($row['total_amount']);
            $report_data[$row['machine_id']]['transactions'][] = [
                'type' => $row['transaction_type'],
                'category' => $row['category'],
                'amount' => $amount
            ];

            if ($row['category'] == 'OUT') {
                $report_data[$row['machine_id']]['total_out'] += $amount;
            } elseif ($row['category'] == 'DROP') {
                $report_data[$row['machine_id']]['total_drop'] += $amount;
            }
        }
    }

    // Calculate result for each machine
    foreach ($report_data as &$machine) {
        $machine['result'] = $machine['total_drop'] - $machine['total_out'];
    }
    unset($machine); // Break the reference

    // Set filtered data based on machine selection
    if ($machine_id !== 'all') {
        $filtered_data = isset($report_data[$machine_id]) ? [$report_data[$machine_id]] : [];
    } else {
        $filtered_data = array_values($report_data);
    }

    // Calculate grand totals
    $grand_total_out = array_sum(array_column($filtered_data, 'total_out'));
    $grand_total_drop = array_sum(array_column($filtered_data, 'total_drop'));
    $grand_total_result = $grand_total_drop - $grand_total_out;

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $filtered_data = [];
    $grand_total_out = 0;
    $grand_total_drop = 0;
    $grand_total_result = 0;
}

// Check if we have filter parameters (indicating a report was generated)
$has_filters = !empty($_GET['machine_id']) || !empty($_GET['brand_id']) || !empty($_GET['machine_group_id']) || !empty($_GET['date_range_type']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['month']);

// Build export URLs
$export_params = [
    'page' => 'reports',
    'date_range_type' => $date_range_type,
    'machine_id' => $machine_id,
    'brand_id' => $brand_id,
    'machine_group_id' => $machine_group_id // Include new filter
];

if ($date_range_type === 'range') {
    $export_params['date_from'] = $date_from;
    $export_params['date_to'] = $date_to;
} else {
    $export_params['month'] = $month;
}

$export_url_base = 'index.php?' . http_build_query($export_params);
$pdf_export_url = $export_url_base . '&export=pdf';
$excel_export_url = $export_url_base . '&export=excel';
?>

<div class="reports-page fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header" style="cursor: pointer;" onclick="toggleFilters()">
            <div class="filter-header-content">
                <h4 style="margin: 0;">Report Filters</h4>
                <span id="filter-toggle-icon" class="filter-toggle-icon">
                    <?php echo $has_filters ? '▼' : '▲'; ?>
                </span>
            </div>
        </div>
        <div class="card-body" id="filters-body" style="<?php echo $has_filters ? 'display: none;' : ''; ?>">
            <form id="report-filters" action="index.php" method="GET">
                <input type="hidden" name="page" value="reports">

                <!-- Date Range Section -->
                <div class="form-section">
                    <h4>Date Range</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="date_range_type">Date Range Type</label>
                                <select name="date_range_type" id="date_range_type" class="form-control">
                                    <option value="month" <?= $date_range_type === 'month' ? 'selected' : '' ?>>Full Month</option>
                                    <option value="range" <?= $date_range_type === 'range' ? 'selected' : '' ?>>Custom Range</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="month">Select Month</label>
                                <input type="month" name="month" id="month" class="form-control"
                                       value="<?= $month ?>" <?= $date_range_type !== 'month' ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control"
                                       value="<?= $date_from ?>" <?= $date_range_type !== 'range' ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control"
                                       value="<?= $date_to ?>" <?= $date_range_type !== 'range' ? 'disabled' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Machine Selection -->
                <div class="form-section">
                    <h4>Machine Selection</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="brand_id">Brand</label>
                                <select name="brand_id" id="brand_id" class="form-control">
                                    <option value="all" <?= $brand_id === 'all' ? 'selected' : '' ?>>All Brands</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?= $brand['id'] ?>" <?= $brand_id == $brand['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($brand['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="machine_group_id">Machine Group</label>
                                <select name="machine_group_id" id="machine_group_id" class="form-control">
                                    <option value="all" <?= $machine_group_id === 'all' ? 'selected' : '' ?>>All Groups</option>
                                    <?php foreach ($machine_groups as $group): ?>
                                        <option value="<?= $group['id'] ?>" <?= $machine_group_id == $group['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($group['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="machine_id">Specific Machine</label>
                                <select name="machine_id" id="machine_id" class="form-control">
                                    <option value="all" <?= $machine_id === 'all' ? 'selected' : '' ?>>All Machines</option>
                                    <?php foreach ($machines as $machine): ?>
                                        <option value="<?= $machine['id'] ?>" <?= $machine_id == $machine['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($machine['machine_number']) ?>
                                            <?php if ($machine['brand_name']): ?>
                                                (<?= htmlspecialchars($machine['brand_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Generate</button>
                    <a href="index.php?page=reports" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Report for:</h3>

        <!-- Main Title Line -->
        <p class="date-range text-lg font-medium text-white mt-2 mb-1">
            <?php
            // Show Machine Number if selected
            if ($machine_id !== 'all') {
                $selected_machine = null;
                foreach ($machines as $m) {
                    if ($m['id'] == $machine_id) {
                        $selected_machine = $m;
                        break;
                    }
                }
                echo "Machine #" . htmlspecialchars($selected_machine['machine_number'] ?? 'N/A');
                if ($selected_machine['brand_name']) {
                    echo " (" . htmlspecialchars($selected_machine['brand_name']) . ")";
                }
            } elseif ($machine_group_id !== 'all') { // New condition for machine group
                $selected_group = null;
                foreach ($machine_groups as $g) {
                    if ($g['id'] == $machine_group_id) {
                        $selected_group = $g;
                        break;
                    }
                }
                echo "Group: " . htmlspecialchars($selected_group['name'] ?? 'N/A');
            } elseif ($brand_id !== 'all') {
                $selected_brand = null;
                foreach ($brands as $b) {
                    if ($b['id'] == $brand_id) {
                        $selected_brand = $b;
                        break;
                    }
                }
                echo "Brand: " . htmlspecialchars($selected_brand['name'] ?? 'N/A');
            } else {
                echo "All Machines";
            }
            ?>
            |
            <?php
            // Show Date Range or Month
            if ($date_range_type === 'range') {
                echo htmlspecialchars(date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to)));
            } else {
                echo htmlspecialchars(date('F Y', strtotime($month)));
            }
            ?>
        </p>

        <!-- Generated Timestamp -->
        <p class="generated-at text-sm italic text-gray-400">
            Generated at: <?php echo cairo_time('d M Y - H:i:s'); ?>
        </p>
    </div>

    <!-- Summary Stats -->
    <div class="stats-container grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card in p-4 rounded bg-opacity-10 bg-success-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total DROP</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_drop); ?></div>
        </div>
        <div class="stat-card out p-4 rounded bg-opacity-10 bg-danger-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total OUT</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_out); ?></div>
        </div>
        <div class="stat-card <?php echo $grand_total_result >= 0 ? 'in' : 'out'; ?> p-4 rounded text-center bg-opacity-10 <?php echo $grand_total_result >= 0 ? 'bg-success-color' : 'bg-danger-color'; ?>">
            <div class="stat-title uppercase text-sm text-muted">Result</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_result); ?></div>
        </div>
    </div>

    <!-- Export Buttons -->
    <?php if (!empty($filtered_data) && empty($error) === false): ?>
        <div class="export-actions mb-4">
            <div class="card">
                <div class="card-header">
                    <h4>Export Options</h4>
                </div>
                <div class="card-body">
                    <div class="export-buttons">
                        <a href="<?= htmlspecialchars($pdf_export_url) ?>" class="btn btn-secondary" target="_blank">
                            📄 Export to PDF
                        </a>
                        <a href="<?= htmlspecialchars($excel_export_url) ?>" class="btn btn-secondary">
                            📊 Export to Excel
                        </a>
                    </div>
                    <p class="export-note">
                        <small>PDF will open in a new tab. Excel file will be downloaded automatically.</small>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Detailed Report Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Detailed Report</h3>
        </div>
        <div class="card-body p-6">
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700 separated-columns">
                    <thead>
                        <tr class="bg-gray-800 text-white">
                            <th class="px-4 py-2 text-left">Machine #</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-right">Coins Drop</th>
                            <th class="px-4 py-2 text-right">Cash Drop</th>
                            <th class="highlight-drop px-4 py-2 text-right">Total DROP</th>
                            <th class="px-4 py-2 text-right">Handpay</th>
                            <th class="px-4 py-2 text-right">Ticket</th>
                            <th class="px-4 py-2 text-right">Refill</th>
                            <th class="highlight-out px-4 py-2 text-right">Total OUT</th>
                            <th class="highlight-result px-4 py-2 text-right">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($filtered_data)): ?>
                            <tr>
                                <td colspan="10" class="text-center px-4 py-6">No matching data found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_data as $data): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($data['machine_number']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($data['type']); ?></td>

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

                                    <td class="px-4 py-2 text-right"><?php echo format_currency($coins_drop); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($cash_drop); ?></td>
                                    <td class="highlight-drop px-4 py-2 text-right"><strong><?php echo format_currency($data['total_drop']); ?></strong></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($handpay); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($ticket); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($refill); ?></td>
                                    <td class="highlight-out px-4 py-2 text-right"><strong><?php echo format_currency($data['total_out']); ?></strong></td>
                                    <td class="highlight-result px-4 py-2 text-right <?php echo $data['result'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <strong><?php echo format_currency($data['result']); ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Totals Row -->
                            <tr class="totals-row bg-gray-800 text-white">
                                <td colspan="4" class="px-4 py-2 font-bold">TOTALS</td>
                                <td class="highlight-drop px-4 py-2 text-right"><strong><?php echo format_currency($grand_total_drop); ?></strong></td>
                                <td colspan="3"></td>
                                <td class="highlight-out px-4 py-2 text-right"><strong><?php echo format_currency($grand_total_out); ?></strong></td>
                                <td class="highlight-result px-4 py-2 text-right <?php echo $grand_total_result >= 0 ? 'positive' : 'negative'; ?>">
                                    <strong><?php echo format_currency($grand_total_result); ?></strong>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/common_utils.js"></script>

<?php
/**
 * General Report Page
 * Shows machine type statistics with drop, out, and result data
 * Includes pie chart visualization
 */

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'general_report';

// Get filter values
$date_range_type = $_GET['date_range_type'] ?? 'month';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$month = $_GET['month'] ?? date('Y-m');
$machine_id = $_GET['machine_id'] ?? 'all';
$brand_id = $_GET['brand_id'] ?? 'all';
$machine_group_id = $_GET['machine_group_id'] ?? 'all';

// Calculate start/end dates
if ($date_range_type === 'range') {
    $start_date = $date_from;
    $end_date = $date_to;
} else {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Get machines for dropdown
try {
    $machines_query = "SELECT m.id, m.machine_number, b.name AS brand_name 
                       FROM machines m
                       LEFT JOIN brands b ON m.brand_id = b.id
                       ORDER BY m.machine_number";
    $machines_stmt = $conn->query($machines_query);
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $machines = [];
}

// Get brands for dropdown
try {
    $brands_stmt = $conn->query("SELECT id, name FROM brands ORDER BY name");
    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $brands = [];
}

// Get machine groups for dropdown
try {
    $groups_stmt = $conn->query("SELECT id, name FROM machine_groups ORDER BY name");
    $machine_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $machine_groups = [];
}

// Build query for machine type statistics
$results = [];
$error = '';
try {
    // Start building the query
    $query = "
        SELECT 
            mt.name AS machine_type,
            COUNT(DISTINCT m.id) AS machine_count,
            COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) AS total_drop,
            COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS total_out,
            COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS result
        FROM machines m
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        LEFT JOIN transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?
        LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE 1=1
    ";

    $params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];

    // Apply filters
    if ($machine_id !== 'all') {
        $query .= " AND m.id = ?";
        $params[] = $machine_id;
    }
    if ($brand_id !== 'all') {
        $query .= " AND m.brand_id = ?";
        $params[] = $brand_id;
    }
    if ($machine_group_id !== 'all') {
        $query .= " AND m.id IN (SELECT machine_id FROM machine_group_members WHERE group_id = ?)";
        $params[] = $machine_group_id;
    }

    $query .= " GROUP BY mt.id, mt.name ORDER BY mt.name";

    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $results = [];
    $error = "Database error: " . $e->getMessage();
}

// Calculate totals
$total_machines = 0;
$grand_total_drop = 0;
$grand_total_out = 0;
$grand_total_result = 0;

foreach ($results as $row) {
    $total_machines += (int)$row['machine_count'];
    $grand_total_drop += (float)$row['total_drop'];
    $grand_total_out += (float)$row['total_out'];
    $grand_total_result += (float)$row['result'];
}

// Calculate percentages for chart legend
$chart_data = [];
$total_abs_result = 0;
foreach ($results as $row) {
    if ($row['result'] != 0) {
        $abs_result = abs($row['result']);
        $chart_data[] = [
            'type' => $row['machine_type'] ?? 'Unknown',
            'result' => $row['result'],
            'abs_result' => $abs_result
        ];
        $total_abs_result += $abs_result;
    }
}

// Add percentages to chart data
foreach ($chart_data as &$item) {
    $item['percentage'] = $total_abs_result > 0 ? round(($item['abs_result'] / $total_abs_result) * 100, 1) : 0;
}
unset($item);

// Generate report title
$report_title = "General Report";
if ($machine_id !== 'all') {
    $selected_machine = null;
    foreach ($machines as $m) {
        if ($m['id'] == $machine_id) {
            $selected_machine = $m;
            break;
        }
    }
    $report_subtitle = "Machine #" . ($selected_machine['machine_number'] ?? 'N/A');
} elseif ($machine_group_id !== 'all') {
    $selected_group = null;
    foreach ($machine_groups as $g) {
        if ($g['id'] == $machine_group_id) {
            $selected_group = $g;
            break;
        }
    }
    $report_subtitle = "Group: " . ($selected_group['name'] ?? 'N/A');
} elseif ($brand_id !== 'all') {
    $selected_brand = null;
    foreach ($brands as $b) {
        if ($b['id'] == $brand_id) {
            $selected_brand = $b;
            break;
        }
    }
    $report_subtitle = "Brand: " . ($selected_brand['name'] ?? 'N/A');
} else {
    $report_subtitle = "All Machines";
}

if ($date_range_type === 'range') {
    $date_subtitle = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
} else {
    $date_subtitle = date('F Y', strtotime($month));
}

// Check if we have filter parameters (indicating a report was generated)
$has_filters = !empty($_GET['machine_id']) || !empty($_GET['brand_id']) || !empty($_GET['machine_group_id']) || !empty($_GET['date_range_type']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['month']);
?>

<div class="general-report-page fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header">
            <div class="filter-header-content">
                <h4 style="margin: 0;">Report Configuration</h4>
                <span id="filter-toggle-icon" class="filter-toggle-icon">
                    <?php echo $has_filters ? '▼' : '▲'; ?>
                </span>
            </div>
        </div>
        <div class="card-body" id="filters-body" style="<?php echo $has_filters ? 'display: none;' : ''; ?>">
            <form action="index.php" method="GET">
                <input type="hidden" name="page" value="general_report">

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
                    <a href="index.php?page=general_report" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Header -->
    <div class="report-header">
        <h3><?= $report_title ?></h3>
        <p class="date-range">
            <?= htmlspecialchars($report_subtitle) ?> | <?= htmlspecialchars($date_subtitle) ?>
        </p>
        <p class="generated-at">
            Generated at: <?= cairo_time('d M Y – H:i:s') ?>
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($results)): ?>
        <!-- Report Cards -->
        <div class="equal-height-cards">
            <!-- Machine Type Statistics Card -->
            <div class="equal-height-card">
                <div class="card">
                    <div class="card-header">
                        <h3>Machine Type Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left">Machine Type</th>
                                        <th class="px-4 py-2 text-center">Count</th>
                                        <th class="px-4 py-2 text-right">Total DROP</th>
                                        <th class="px-4 py-2 text-right">Total OUT</th>
                                        <th class="px-4 py-2 text-right">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $row): ?>
                                        <tr class="hover:bg-gray-800 transition duration-150">
                                            <td class="px-4 py-2 font-medium"><?= htmlspecialchars($row['machine_type'] ?? 'Unknown') ?></td>
                                            <td class="px-4 py-2 text-center"><?= $row['machine_count'] ?></td>
                                            <td class="px-4 py-2 text-right highlight-drop">
                                                <strong><?= format_currency($row['total_drop']) ?></strong>
                                            </td>
                                            <td class="px-4 py-2 text-right highlight-out">
                                                <strong><?= format_currency($row['total_out']) ?></strong>
                                            </td>
                                            <td class="px-4 py-2 text-right highlight-result <?= $row['result'] >= 0 ? 'positive' : 'negative' ?>">
                                                <strong><?= format_currency($row['result']) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Totals Row -->
                                    <tr class="totals-row bg-gray-800 text-white font-bold">
                                        <td class="px-4 py-2"><strong>TOTALS</strong></td>
                                        <td class="px-4 py-2 text-center"><strong><?= $total_machines ?></strong></td>
                                        <td class="px-4 py-2 text-right highlight-drop">
                                            <strong><?= format_currency($grand_total_drop) ?></strong>
                                        </td>
                                        <td class="px-4 py-2 text-right highlight-out">
                                            <strong><?= format_currency($grand_total_out) ?></strong>
                                        </td>
                                        <td class="px-4 py-2 text-right highlight-result <?= $grand_total_result >= 0 ? 'positive' : 'negative' ?>">
                                            <strong><?= format_currency($grand_total_result) ?></strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pie Chart Card -->
            <div class="equal-height-card">
                <div class="card">
                    <div class="card-header">
                        <h3>Result Distribution by Machine Type</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" data-chart-data='<?php echo json_encode($chart_data); ?>'>
                            <canvas id="results-pie-chart"></canvas>
                        </div>
                        
                        <!-- Chart Legend -->
                        <div class="chart-legend">
                            <?php foreach ($chart_data as $index => $item): ?>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: <?= getChartColor($index) ?>"></span>
                                    <span class="legend-label">
                                        <?= htmlspecialchars($item['type']) ?>: 
                                        <?= format_currency($item['result']) ?> <strong>(<?= $item['percentage'] ?>%)</strong>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No data found for the selected criteria.</div>
    <?php endif; ?>
</div>

<script src="assets/js/common_utils.js"></script>
<script src="assets/js/general_report_charts.js"></script>

<?php
// Helper function to generate chart colors
function getChartColor($index) {
    $colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];
    return $colors[$index % count($colors)];
}
?>

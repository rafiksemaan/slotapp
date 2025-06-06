<?php
// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get sorting parameters
$sort_column = $_GET['sort'] ?? 'machine_number';
$sort_order = $_GET['order'] ?? 'ASC';

// Validate sort column
$allowed_columns = ['machine_number', 'brand_name', 'model', 'machine_type', 'total_out', 'total_drop', 'result'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'machine_number';
}

// Validate sort order
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Toggle sort order for links
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Get filter values
$date_range_type = $_GET['date_range_type'] ?? 'month';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$month = $_GET['month'] ?? date('Y-m');
$machine_id = $_GET['machine_id'] ?? 'all';
$brand_id = $_GET['brand_id'] ?? 'all';
$sort_column = $_GET['sort'] ?? 'machine_number';
$sort_order = $_GET['order'] ?? 'ASC';

// Validate sort column
$allowed_columns = ['machine_number', 'brand_name', 'model', 'machine_type', 'total_out', 'total_drop', 'result'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'machine_number';
}

// Validate sort order
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Toggle sort order for links
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Calculate start/end dates
if ($date_range_type === 'range') {
    $start_date = $date_from;
    $end_date = $date_to;
} else {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Build base URL with current filters
$base_url = "index.php?page=custom_report&sort=$sort_column&order=$sort_order";
$base_url .= "&date_range_type=$date_range_type";
if ($date_range_type === 'range') {
    $base_url .= "&date_from=$date_from&date_to=$date_to";
} else {
    $base_url .= "&month=$month";
}
if ($machine_id !== 'all') {
    $base_url .= "&machine_id=$machine_id";
}
if ($brand_id !== 'all') {
    $base_url .= "&brand_id=$brand_id";
}

// Get selected columns from URL
$selected_columns = $_GET['columns'] ?? [];

// Define available columns
$available_columns = [
    'machine_number' => 'Machine #',
    'brand_name' => 'Brand',
    'model' => 'Model',
    'machine_type' => 'Machine Type',
    'total_out' => 'Total OUT',
    'total_drop' => 'Total DROP',
    'result' => 'Result'
];

// Get transaction types for checkboxes
try {
    $types_stmt = $conn->query("SELECT id, name FROM transaction_types ORDER BY category, name");
    $transaction_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $transaction_types = [];
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

// Build SQL query
try {
    // Base SELECT columns
    $select_columns = ["m.id AS machine_id", "m.machine_number"];
    
    // Join statements
    $join_clauses = [
        "LEFT JOIN brands b ON m.brand_id = b.id",
        "LEFT JOIN machine_types mt ON m.type_id = mt.id"
    ];
    
    // Dynamic column selection
    $group_by = "m.id";
    if (in_array('brand_name', $selected_columns)) {
        $select_columns[] = "b.name AS brand_name";
        $group_by .= ", b.id";
    }
    
    if (in_array('model', $selected_columns)) {
        $select_columns[] = "m.model";
    }
    
    if (in_array('machine_type', $selected_columns)) {
        $select_columns[] = "mt.name AS machine_type";
        $group_by .= ", mt.id";
    }
    
    // Add transaction type columns if selected
    $has_transactions = false;
    foreach ($transaction_types as $tt) {
        $col_key = "tt_{$tt['id']}";
        if (in_array($col_key, $selected_columns)) {
            $has_transactions = true;
            $select_columns[] = "SUM(CASE WHEN t.transaction_type_id = {$tt['id']} THEN t.amount ELSE 0 END) AS tt_{$tt['id']}";
            $join_clauses[] = "LEFT JOIN transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?";
            $join_clauses[] = "LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.id";
            $group_by = "m.id, t.transaction_type_id";
        }
    }

    // Add totals if selected
    if (in_array('total_out', $selected_columns) || in_array('total_drop', $selected_columns)) {
        $has_transactions = true;
        $select_columns[] = "SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END) AS total_out";
        $select_columns[] = "SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END) AS total_drop";
        $join_clauses[] = "LEFT JOIN transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?";
        $join_clauses[] = "LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.id";
    }

    // Finalize query
    $query = "SELECT " . implode(", ", $select_columns ?: ["m.id AS machine_id", "m.machine_number"]);
    $query .= " FROM machines m";
    $query .= " " . implode(" ", $join_clauses);
    $query .= " WHERE 1=1"; // Base WHERE to build from

// Initialize params array
$params = [];

    // Apply date filter
    if ($has_transactions) {
        $params[] = "{$start_date} 00:00:00";
        $params[] = "{$end_date} 23:59:59";
    }

    // Apply machine filter
    if ($machine_id !== 'all') {
        $query .= " AND m.id = ?";
        $params[] = $machine_id;
    }

    // Apply brand filter
    if ($brand_id !== 'all') {
        $query .= " AND b.id = ?";
        $params[] = $brand_id;
    }

    // Grouping and ordering
    $query .= " GROUP BY $group_by";
    $query .= " ORDER BY `$sort_column` $sort_order";

    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $results = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="custom-report-page fade-in">
    <!-- Filters -->
    <div class="filters-container card mb-6">
        <form action="<?= $base_url ?>" method="GET">
            <input type="hidden" name="page" value="custom_report">
            <input type="hidden" name="sort" value="<?= $sort_column ?>">
            <input type="hidden" name="order" value="<?= $sort_order ?>">

            <!-- Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="filter-group">
                    <label for="date_range_type">Date Range</label>
                    <select name="date_range_type" id="date_range_type" class="form-control">
                        <option value="month" <?= $date_range_type === 'month' ? 'selected' : '' ?>>Full Month</option>
                        <option value="range" <?= $date_range_type === 'range' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control"
                           value="<?= $date_from ?>" <?= $date_range_type !== 'range' ? 'disabled' : '' ?>>
                </div>
                
                <div class="filter-group">
                    <label for="date_to">To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control"
                           value="<?= $date_to ?>" <?= $date_range_type !== 'range' ? 'disabled' : '' ?>>
                </div>
                
                <div class="filter-group">
                    <label for="month">Select Month</label>
                    <input type="month" name="month" id="month" class="form-control"
                           value="<?= $month ?>" <?= $date_range_type !== 'month' ? 'disabled' : '' ?>>
                </div>
                
                <div class="filter-group">
                    <label for="machine_id">Machine</label>
                    <select name="machine_id" id="machine_id" class="form-control">
                        <option value="all" <?= $machine_id === 'all' ? 'selected' : '' ?>>All Machines</option>
                        <?php foreach ($machines as $machine): ?>
                            <option value="<?= $machine['id'] ?>" <?= $machine_id == $machine['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($machine['machine_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
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

            <!-- Column Selection -->
            <div class="filter-group mt-4">
                <label>Report Columns</label>
                <div class="checkbox-group">
                    <?php foreach ($available_columns as $key => $label): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="<?= $key ?>" 
                                   <?= in_array($key, $selected_columns) ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                    
                    <?php foreach ($transaction_types as $tt): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="tt_<?= $tt['id'] ?>" 
                                   <?= in_array("tt_{$tt['id']}", $selected_columns) ? 'checked' : '' ?>>
                            <?= ucfirst($tt['category']) ?>: <?= htmlspecialchars($tt['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="form-actions mt-6 flex gap-4">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <a href="<?= $base_url ?>" class="btn btn-danger">Reset</a>
                <a href="<?= $base_url ?>&export=1" class="btn btn-secondary">Export to CSV</a>
            </div>
        </form>
    </div>

    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Custom Report for:</h3>
        <p class="text-lg md:text-xl font-medium text-white mt-2 mb-1">
            <?php if ($machine_id === 'all' && $brand_id === 'all'): ?>
                All Machines
            <?php elseif ($machine_id !== 'all'): ?>
                <?php
                $selected_machine = null;
                foreach ($machines as $m) {
                    if ($m['id'] == $machine_id) {
                        $selected_machine = $m;
                        break;
                    }
                }
                echo "Machine #" . htmlspecialchars($selected_machine['machine_number'] ?? 'N/A');
                ?>
            <?php else: ?>
                <?php
                $selected_brand = null;
                foreach ($brands as $b) {
                    if ($b['id'] == $brand_id) {
                        $selected_brand = $b;
                        break;
                    }
                }
                echo "Brand: " . htmlspecialchars($selected_brand['name'] ?? 'N/A');
                ?>
            <?php endif; ?>
            |
            <?php if ($date_range_type === 'range'): ?>
                <?= htmlspecialchars(date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to))) ?>
            <?php else: ?>
                <?= htmlspecialchars(date('F Y', strtotime($month))) ?>
            <?php endif; ?>
        </p>
        <p class="text-sm italic text-gray-400">
            Generated at: <?= cairo_time('d M Y – H:i:s') ?>
        </p>
    </div>

    <!-- Custom Report Table -->
    <?php if (!empty($results) && !empty($selected_columns)): ?>
        <div class="card overflow-hidden">
            <div class="card-body p-6">
                <div class="table-container overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <?php foreach ($available_columns as $key => $label): ?>
                                    <?php if (in_array($key, $selected_columns)): ?>
                                        <th class="px-4 py-2 text-left">
											<a href="<?= "$base_url&sort=machine_number&order=" . ($sort_column === 'machine_number' ? $toggle_order : 'ASC') ?>">
												Machine #
												<?php if ($sort_column === 'machine_number'): ?>
													<?= $sort_order === 'ASC' ? '▲' : '▼' ?>
												<?php endif; ?>
											</a>
										</th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php foreach ($transaction_types as $tt): ?>
                                    <?php if (in_array("tt_{$tt['id']}", $selected_columns)): ?>
                                        <th class="px-4 py-2 text-left">
                                            <a href="<?= "$base_url&sort=tt_{$tt['id']}&order=" . ($sort_column === "tt_{$tt['id']}" ? $toggle_order : 'ASC') ?>">
                                                <?= htmlspecialchars("{$tt['category']}: {$tt['name']}") ?>
                                                <?php if ($sort_column === "tt_{$tt['id']}"): ?>
                                                    <?= $sort_order === 'ASC' ? '▲' : '▼' ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach ($results as $row): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <?php foreach ($available_columns as $key => $label): ?>
                                        <?php if (in_array($key, $selected_columns)): ?>
                                            <td><?= htmlspecialchars($row[$key] ?? 'N/A') ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($transaction_types as $tt): ?>
                                        <?php if (in_array("tt_{$tt['id']}", $selected_columns)): ?>
                                            <td><?= format_currency($row["tt_{$tt['id']}"] ?? 0) ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif (!empty($results)): ?>
        <div class="alert alert-warning text-center py-6">No columns selected</div>
    <?php else: ?>
        <div class="alert alert-danger text-center py-6">No data found for selected filters</div>
    <?php endif; ?>
</div>
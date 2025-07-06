<?php
/**
 * Meters List Page
 * Shows meter entries with filtering and sorting options
 */

// Get sorting parameters
$sort_column = $_GET['sort'] ?? 'operation_date';
$sort_order = $_GET['order'] ?? 'DESC';
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Get filter parameters
$filter_machine = $_GET['machine'] ?? 'all';
$date_range_type = $_GET['date_range_type'] ?? 'month';
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
$filter_date_to = $_GET['date_to'] ?? date('Y-m-t');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_meter_type = $_GET['meter_type'] ?? 'all'; // e.g., 'online', 'coins', 'offline'

// Calculate start and end dates based on filter
if ($date_range_type === 'range') {
    $start_date = $filter_date_from;
    $end_date = $filter_date_to;
} else {
    list($year, $month_num) = explode('-', $filter_month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Build query for fetching meter data with LAG for previous readings
$query = "
    SELECT
        me.*,
        m.machine_number,
        m.credit_value,
        mt.name AS machine_type_name,
        u.username AS created_by_username,
        LAG(me.bills_in, 1, NULL) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_bills_in,
        LAG(me.handpay, 1, NULL) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_handpay,
        LAG(me.coins_drop, 1, NULL) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_coins_drop
    FROM meters me
    JOIN machines m ON me.machine_id = m.id
    LEFT JOIN machine_types mt ON m.type_id = mt.id
    LEFT JOIN users u ON me.created_by = u.id
    WHERE me.operation_date BETWEEN ? AND ?
";

$params = [$start_date, $end_date];

// Apply filters
if ($filter_machine !== 'all') {
    $query .= " AND me.machine_id = ?";
    $params[] = $filter_machine;
}
if ($filter_meter_type !== 'all') {
    $query .= " AND me.meter_type = ?";
    $params[] = $filter_meter_type;
}

// Add sorting
$sort_map = [
    'operation_date' => 'me.operation_date',
    'machine_number' => 'CAST(m.machine_number AS UNSIGNED)', // Sort numerically
    'meter_type' => 'me.meter_type',
    'total_in' => 'me.total_in',
    'total_out' => 'me.total_out',
    'bills_in' => 'me.bills_in',
    'coins_in' => 'me.coins_in',
    'coins_out' => 'me.coins_out',
    'coins_drop' => 'me.coins_drop',
    'bets' => 'me.bets',
    'handpay' => 'me.handpay',
    'jp' => 'me.jp',
    'created_by_username' => 'u.username'
];

$actual_sort_column = $sort_map[$sort_column] ?? 'me.operation_date';
$query .= " ORDER BY $actual_sort_column $sort_order";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $meters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch transaction sums for anomaly calculation
    $transaction_sums_query = "
        SELECT
            t.machine_id,
            t.operation_date,
            SUM(CASE WHEN tt.name = 'Cash Drop' THEN t.amount ELSE 0 END) AS cash_drop_sum,
            SUM(CASE WHEN tt.name = 'Coins Drop' THEN t.amount ELSE 0 END) AS coins_drop_sum,
            SUM(CASE WHEN tt.name = 'Handpay' THEN t.amount ELSE 0 END) AS handpay_sum
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE t.operation_date BETWEEN ? AND ?
    ";
    // Add machine filter to transaction sums query if applicable
    $transaction_sums_params = [$start_date, $end_date];
    if ($filter_machine !== 'all') {
        $transaction_sums_query .= " AND t.machine_id = ?";
        $transaction_sums_params[] = $filter_machine;
    }
    $transaction_sums_query .= " GROUP BY t.machine_id, t.operation_date";

    $sums_stmt = $conn->prepare($transaction_sums_query);
    $sums_stmt->execute($transaction_sums_params);
    $transaction_sums = [];
    foreach ($sums_stmt->fetchAll(PDO::FETCH_ASSOC) as $sum_row) {
        $transaction_sums[$sum_row['machine_id'] . '_' . $sum_row['operation_date']] = $sum_row;
    }

    // Process meters data to calculate variance and anomaly
    foreach ($meters as &$meter) {
        $credit_value = (float)$meter['credit_value'];
        $operation_date = $meter['operation_date'];
        $machine_id = $meter['machine_id'];

        // Initialize variance and anomaly fields
        $meter['bills_in_variance'] = '---';
        $meter['handpay_variance'] = '---';
        $meter['coins_drop_variance'] = '---';
        $meter['bills_in_anomaly'] = '---';
        $meter['handpay_anomaly'] = '---';
        $meter['coins_drop_anomaly'] = '---';

        // Calculate Variance
        if (!$meter['is_initial_reading']) { // Only calculate if not an initial reading
            if ($meter['bills_in'] !== null && $meter['prev_bills_in'] !== null) {
                $meter['bills_in_variance'] = ($meter['bills_in'] - $meter['prev_bills_in']) * $credit_value;
            }
            if ($meter['handpay'] !== null && $meter['prev_handpay'] !== null) {
                $meter['handpay_variance'] = ($meter['handpay'] - $meter['prev_handpay']) * $credit_value;
            }
            if ($meter['coins_drop'] !== null && $meter['prev_coins_drop'] !== null) {
                $meter['coins_drop_variance'] = ($meter['coins_drop'] - $meter['prev_coins_drop']) * $credit_value;
            }
        }

        // Calculate Anomaly
        $sum_key = $machine_id . '_' . $operation_date;
        if (isset($transaction_sums[$sum_key])) {
            $current_transaction_sums = $transaction_sums[$sum_key];

            // Bills In Anomaly (vs Cash Drop Transaction)
            if ($meter['bills_in_variance'] !== '---' && $current_transaction_sums['cash_drop_sum'] !== null) {
                $meter['bills_in_anomaly'] = $meter['bills_in_variance'] - (float)$current_transaction_sums['cash_drop_sum'];
            }

            // Handpay Anomaly (vs Handpay Transaction)
            if ($meter['handpay_variance'] !== '---' && $current_transaction_sums['handpay_sum'] !== null) {
                $meter['handpay_anomaly'] = $meter['handpay_variance'] - (float)$current_transaction_sums['handpay_sum'];
            }

            // Coins Drop Anomaly (vs Coins Drop Transaction)
            if ($meter['coins_drop_variance'] !== '---' && $current_transaction_sums['coins_drop_sum'] !== null) {
                $meter['coins_drop_anomaly'] = $meter['coins_drop_variance'] - (float)$current_transaction_sums['coins_drop_sum'];
            }
        }
    }
    unset($meter); // Break the reference

    // Get machines for filter dropdown
    $machines_stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        ORDER BY CAST(m.machine_number AS UNSIGNED) ASC
    ");
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . htmlspecialchars($e->getMessage()));
    $meters = [];
    $machines = [];
    $transaction_sums = [];
}

// Meter types for dropdown (from database ENUM or fixed list)
$meter_types_options = ['online', 'coins', 'offline'];

// Check if we have filter parameters (indicating filters were applied)
$has_filters = $filter_machine !== 'all' || $date_range_type !== 'month' || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['month']) || $filter_meter_type !== 'all';

?>

<div class="meters-list fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header">
            <div class="filter-header-content">
                <h4 style="margin: 0;">Meter Filters</h4>
                <span id="filter-toggle-icon" class="filter-toggle-icon">
                    <?php echo $has_filters ? '▼' : '▲'; ?>
                </span>
            </div>
        </div>
        <div class="card-body" id="filters-body" style="<?php echo $has_filters ? 'display: none;' : ''; ?>">
            <form action="index.php" method="GET">
                <input type="hidden" name="page" value="meters">
                <input type="hidden" name="sort" value="<?php echo $sort_column; ?>">
                <input type="hidden" name="order" value="<?php echo $sort_order; ?>">

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
                                       value="<?= $filter_month ?>" <?= $date_range_type !== 'month' ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control"
                                       value="<?= $filter_date_from ?>" <?= $date_range_type !== 'range' ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control"
                                       value="<?= $filter_date_to ?>" <?= $date_range_type !== 'range' ? 'disabled' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Options Section -->
                <div class="form-section">
                    <h4>Filter Options</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="machine">Machine</label>
                                <select name="machine" id="machine" class="form-control">
                                    <option value="all" <?= $filter_machine === 'all' ? 'selected' : '' ?>>All Machines</option>
                                    <?php foreach ($machines as $m): ?>
                                        <option value="<?= $m['id'] ?>" <?= $filter_machine == $m['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['machine_number']) ?>
                                            <?php if ($m['brand_name']): ?>
                                                (<?= htmlspecialchars($m['brand_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="meter_type">Meter Type</label>
                                <select name="meter_type" id="meter_type" class="form-control">
                                    <option value="all" <?= $filter_meter_type === 'all' ? 'selected' : '' ?>>All Types</option>
                                    <?php foreach ($meter_types_options as $type): ?>
                                        <option value="<?= $type ?>" <?= $filter_meter_type === $type ? 'selected' : '' ?>>
                                            <?= ucfirst($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="index.php?page=meters" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons mb-6 flex justify-between">
        <div>
            <?php if ($can_edit): ?>
                <a href="index.php?page=meters&action=create" class="btn btn-primary">
                    Add New Meter Entry (Offline Machines)
                </a>
                <a href="index.php?page=meters&action=upload" class="btn btn-secondary">
                    Upload Online Meters
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Meters Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Meter Entries</h3>
        </div>
        <div class="card-body p-6">
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left sortable-header" data-sort-column="operation_date" data-sort-order="<?php echo $sort_column == 'operation_date' ? $toggle_order : 'ASC'; ?>">
                                Date <?php if ($sort_column == 'operation_date') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                            </th>
                            <th class="px-4 py-2 text-left sortable-header" data-sort-column="machine_number" data-sort-order="<?php echo $sort_column == 'machine_number' ? $toggle_order : 'ASC'; ?>">
                                Machine <?php if ($sort_column == 'machine_number') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                            </th>
                            <th class="px-4 py-2 text-left sortable-header" data-sort-column="meter_type" data-sort-order="<?php echo $sort_column == 'meter_type' ? $toggle_order : 'ASC'; ?>">
                                Meter Type <?php if ($sort_column == 'meter_type') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                            </th>
                            <th class="px-4 py-2 text-right">Total In</th>
                            <th class="px-4 py-2 text-right">Total Out</th>
                            <th class="px-4 py-2 text-right">Bills In</th>
                            <th class="px-4 py-2 text-right">Coins In</th>
                            <th class="px-4 py-2 text-right">Coins Out</th>
                            <th class="px-4 py-2 text-right">Coins Drop</th>
                            <th class="px-4 py-2 text-right">Bets</th>
                            <th class="px-4 py-2 text-right">Handpay</th>
                            <th class="px-4 py-2 text-right">JP</th>
                            <th class="px-4 py-2 text-right">Bills In Variance</th>
                            <th class="px-4 py-2 text-right">Handpay Variance</th>
                            <th class="px-4 py-2 text-right">Coins Drop Variance</th>
                            <th class="px-4 py-2 text-right">Bills In Anomaly</th>
                            <th class="px-4 py-2 text-right">Handpay Anomaly</th>
                            <th class="px-4 py-2 text-right">Coins Drop Anomaly</th>
                            <th class="px-4 py-2 text-left">Notes</th>
                            <th class="px-4 py-2 text-left sortable-header" data-sort-column="created_by_username" data-sort-order="<?php echo $sort_column == 'created_by_username' ? '▲' : '▼'; ?>">
                                Created By <?php if ($sort_column == 'created_by_username') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                            </th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($meters)): ?>
                            <tr>
                                <td colspan="21" class="text-center px-4 py-6">No meter entries found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meters as $meter): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(format_date($meter['operation_date'])); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['machine_number']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(ucfirst($meter['meter_type'])); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['total_in'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['total_out'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['bills_in'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['coins_in'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['coins_out'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['coins_drop'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['bets'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['handpay'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency(($meter['jp'] ?? 0) * $meter['credit_value']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo is_numeric($meter['bills_in_variance']) ? format_currency($meter['bills_in_variance']) : $meter['bills_in_variance']; ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo is_numeric($meter['handpay_variance']) ? format_currency($meter['handpay_variance']) : $meter['handpay_variance']; ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo is_numeric($meter['coins_drop_variance']) ? format_currency($meter['coins_drop_variance']) : $meter['coins_drop_variance']; ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo is_numeric($meter['bills_in_anomaly']) ? format_currency($meter['bills_in_anomaly']) : $meter['bills_in_anomaly']; ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo is_numeric($meter['handpay_anomaly']) ? format_currency($meter['handpay_anomaly']) : $meter['handpay_anomaly']; ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo is_numeric($meter['coins_drop_anomaly']) ? format_currency($meter['coins_drop_anomaly']) : $meter['coins_drop_anomaly']; ?></td>
                                    <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($meter['notes'] ?? ''); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['created_by_username'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="index.php?page=meters&action=view&id=<?php echo $meter['id']; ?>" class="action-btn view-btn" data-tooltip="View Details"><span class="menu-icon"><img src="<?= icon('view2') ?>"/></span></a>
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=meters&action=edit&id=<?php echo $meter['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                                            <a href="index.php?page=meters&action=delete&id=<?php echo $meter['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this meter entry?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/common_utils.js"></script>

<?php
/**
 * Transactions List Page
 * Shows transactions with filters and totals
 */

// Sorting parameters
$sort_column = $_GET['sort'] ?? 'timestamp';
$sort_order = $_GET['order'] ?? 'DESC';

// Validate sort column
$allowed_columns = ['timestamp', 'machine_number', 'transaction_type', 'amount', 'username'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'timestamp';
}

// Validate sort order
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Filter parameters
$filter_machine = $_GET['machine'] ?? 'all';
$date_range_type = $_GET['date_range_type'] ?? 'month'; // 'range' or 'month'
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
$filter_date_to = $_GET['date_to'] ?? date('Y-m-t');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_category = $_GET['category'] ?? ''; // 'OUT' or 'DROP'

// Calculate start and end dates
if ($date_range_type === 'range') {
    $start_date = $filter_date_from;
    $end_date = $filter_date_to;
} else {
    list($year, $month_num) = explode('-', $filter_month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}


// Build base URL with filters
$base_url = "index.php?page=transactions&sort=$sort_column&order=$sort_order";
$base_url .= "&date_range_type=$date_range_type";
if ($date_range_type === 'range') {
    $base_url .= "&date_from=$filter_date_from&date_to=$filter_date_to";
} else {
    $base_url .= "&month=$filter_month";
}
if ($filter_machine !== 'all') $base_url .= "&machine=$filter_machine";
if (!empty($filter_category)) $base_url .= "&category=$filter_category";

// Build query with filtering
try {
    $params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];
    
    // Base query
    $query = "SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username
              FROM transactions t
              JOIN machines m ON t.machine_id = m.id
              JOIN transaction_types tt ON t.transaction_type_id = tt.id
              JOIN users u ON t.user_id = u.id
              WHERE t.timestamp BETWEEN ? AND ?";
    
    // Apply filters
    if ($filter_machine !== 'all') {
        $query .= " AND t.machine_id = ?";
        $params[] = $filter_machine;
    }
    if ($filter_category === 'OUT') {
        $query .= " AND tt.category = 'OUT'";
    } elseif ($filter_category === 'DROP') {
        $query .= " AND tt.category = 'DROP'";
    }

    // Finalize query
    $query .= " ORDER BY $sort_column $sort_order ";

    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total transactions (for infinite scroll detection)
    $total_query = str_replace("SELECT t.*", "SELECT COUNT(*)", $query);
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->execute($params);
    $total_transactions = $total_stmt->fetchColumn();

} catch (PDOException $e) {
    $transactions = [];
    $total_transactions = 0;
    $total_pages = 1;
}

// Build query
try {
    $params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];

    // Base query with joins
    $query = "
        SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username
        FROM transactions t
        JOIN machines m ON t.machine_id = m.id
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        JOIN users u ON t.user_id = u.id
        WHERE t.timestamp BETWEEN ? AND ?
    ";

    // Apply machine filter
    if ($filter_machine !== 'all') {
        $query .= " AND t.machine_id = ?";
        $params[] = $filter_machine;
    }

    // Apply category filter
    if ($filter_category === 'OUT') {
        $query .= " AND tt.category = 'OUT'";
    } elseif ($filter_category === 'DROP') {
        $query .= " AND tt.category = 'DROP'";
    }

    // Finalize query
    $query .= " ORDER BY $sort_column $sort_order";

    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get machines for dropdown
    $machines_stmt = $conn->query("SELECT id, machine_number FROM machines ORDER BY machine_number");
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get transaction types for category dropdown
    $types_stmt = $conn->query("SELECT DISTINCT category FROM transaction_types WHERE category IN ('OUT', 'DROP')");
    $categories = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $transactions = [];
    $machines = [];
    $categories = [];
    $total_out = $total_drop = $total_result = 0;
}

// Calculate totals
$total_out = $total_drop = 0;

foreach ($transactions as $t) {
    if (($t['category'] ?? '') === 'OUT') {
        $total_out += (float)($t['amount'] ?? 0);
    } elseif (($t['category'] ?? '') === 'DROP') {
        $total_drop += (float)($t['amount'] ?? 0);
    }
}

$total_result = $total_drop - $total_out;
?>

<div class="transactions-page fade-in">
    <!-- Filters -->
    <div class="filters-container card mb-6">
    <form action="index.php" method="GET" class="filters-form">
        <input type="hidden" name="page" value="transactions">
        <input type="hidden" name="sort" value="<?php echo $sort_column; ?>">
        <input type="hidden" name="order" value="<?php echo $sort_order; ?>">

        <!-- Machine Filter -->
        <div class="filter-group">
            <label for="machine">Machine</label>
            <select name="machine" id="machine" class="form-control">
                <option value="all" <?php echo ($filter_machine === 'all') ? 'selected' : ''; ?>>All Machines</option>
                <?php foreach ($machines as $m): ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo ($filter_machine == $m['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m['machine_number']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Date Range Type -->
        <div class="filter-group">
            <label for="date_range_type">Date Range</label>
            <select name="date_range_type" id="date_range_type" class="form-control">
                <option value="month" <?php echo ($date_range_type === 'month') ? 'selected' : ''; ?>>Full Month</option>
                <option value="range" <?php echo ($date_range_type === 'range') ? 'selected' : ''; ?>>Custom Range</option>
            </select>
        </div>

        <!-- From Date -->
        <div class="filter-group">
            <label for="date_from">From</label>
            <input type="date" name="date_from" id="date_from" class="form-control"
                   value="<?php echo $filter_date_from; ?>"
                   <?php echo ($date_range_type !== 'range') ? 'disabled' : ''; ?>>
        </div>

        <!-- To Date -->
        <div class="filter-group">
            <label for="date_to">To</label>
            <input type="date" name="date_to" id="date_to" class="form-control"
                   value="<?php echo $filter_date_to; ?>"
                   <?php echo ($date_range_type !== 'range') ? 'disabled' : ''; ?>>
        </div>

        <!-- Month Picker -->
        <div class="filter-group">
            <label for="month">Select Month</label>
            <input type="month" name="month" id="month" class="form-control"
                   value="<?php echo $filter_month; ?>"
                   <?php echo ($date_range_type !== 'month') ? 'disabled' : ''; ?>>
        </div>

        <!-- Category Filter -->
        <div class="filter-group">
            <label for="category">Category</label>
            <select name="category" id="category" class="form-control">
                <option value="" <?php echo ($filter_category === '') ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category']; ?>" <?php echo ($filter_category === $cat['category']) ? 'selected' : ''; ?>>
                        <?php echo ucfirst($cat['category']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
		
		<!-- Submit Button -->
        <div class="filter-group">
            <button type="submit" class="btn btn-primary w-full">Apply Filters</button>
            <a href="index.php?page=transactions" class="btn btn-danger">Reset</a>
        </div>
    </form>
</div>
<!-- Action Buttons -->
<?php if ($can_edit): ?>
    <div class="action-buttons mb-6 flex justify-end">
        <!-- Add New Transaction Button -->
        <a href="index.php?page=transactions&action=create<?php 
            // Preserve current filters in URL
            echo $filter_machine !== 'all' ? '&machine=' . $filter_machine : '';
            echo $date_range_type === 'range' ? '&date_from=' . $filter_date_from . '&date_to=' . $filter_date_to : '&month=' . $filter_month;
            echo '&sort=' . $sort_column . '&order=' . $sort_order;
        ?>" class="btn btn-primary">
            Add New Transaction
        </a>
    </div>
<?php endif; ?>
    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Transactions for:</h3>
        <p class="date-range text-lg font-medium text-white mt-2 mb-1">
            <?php if ($filter_machine === 'all'): ?>
                All Machines <?php echo $filter_category = $_GET['category'] ?? ''; ?>
            <?php else: ?>
                <?php
                $selected_machine = null;
                foreach ($machines as $m) {
                    if ($m['id'] == $filter_machine) {
                        $selected_machine = $m;
                        break;
                    }
                }
                echo "Machine #" . htmlspecialchars($selected_machine['machine_number'] ?? 'Unknown')." " . $filter_category = $_GET['category'] ?? '';
                ?>
            <?php endif; ?>
            |
            <?php if ($date_range_type === 'range'): ?>
                <?php echo htmlspecialchars(date('d M Y', strtotime($filter_date_from)) . ' – ' . date('d M Y', strtotime($filter_date_to))); ?>
            <?php else: ?>
                <?php echo htmlspecialchars(date('F Y', strtotime($filter_month))); ?>
            <?php endif; ?>
        </p>
        <p class="generated-at text-sm italic text-gray-400">
            Generated at: <?php echo cairo_time('d M Y - H:i:s'); ?>
        </p>
    </div>

    <!-- Summary Stats -->
    <div class="stats-container grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card in p-4 rounded bg-opacity-10 bg-success-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total DROP</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_drop); ?></div>
        </div>
        <div class="stat-card out p-4 rounded bg-opacity-10 bg-danger-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total OUT</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_out); ?></div>
        </div>

        <div class="stat-card <?php echo $total_result >= 0 ? 'in' : 'out'; ?> p-4 rounded text-center bg-opacity-10 <?php echo $total_result >= 0 ? 'bg-success-color' : 'bg-danger-color'; ?>">
            <div class="stat-title uppercase text-sm text-muted">Result</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_result); ?></div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Transactions</h3>
        </div>
        <div class="card-body p-6">
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=transactions&sort=timestamp&order=<?php echo $toggle_order; ?>">
                                    Date & Time <?php if ($sort_column == 'timestamp') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=transactions&sort=machine_number&order=<?php echo $toggle_order; ?>">
                                    Machine <?php if ($sort_column == 'machine_number') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=transactions&sort=transaction_type&order=<?php echo $toggle_order; ?>">
                                    Transaction <?php if ($sort_column == 'transaction_type') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="index.php?page=transactions&sort=amount&order=<?php echo $toggle_order; ?>">
                                    Amount <?php if ($sort_column == 'amount') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">Category</th>
                            <th class="px-4 py-2 text-left">User</th>
                            <th class="px-4 py-2 text-left">Notes</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8" class="text-center px-4 py-6">No transactions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(date('d M Y - H:i:s', strtotime($t['timestamp']))); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['machine_number']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['transaction_type']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($t['amount']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['category'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['username']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['notes'] ?? ''); ?></td>
                                    <td class="px-4 py-2 text-right">
										<a href="index.php?page=transactions&action=view&id=<?php echo $t['id']; ?>" class="action-btn view-btn" data-tooltip="View Details">👁️</a>
                                        <a href="index.php?page=transactions&action=edit&id=<?php echo $t['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit">✏️</a>
                                        <a href="index.php?page=transactions&action=delete&id=<?php echo $t['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this transaction?">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
	<!-- Sentinel for infinite scroll -->
<div id="infinite-scroll-sentinel" class="h-10"></div>
</div>

<!-- JavaScript: Toggle inputs based on date range type -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rangeType = document.getElementById('date_range_type');
    const fromDate = document.getElementById('date_from');
    const toDate = document.getElementById('date_to');
    const monthInput = document.getElementById('month');

    function toggleInputs() {
        const isRange = rangeType.value === 'range';

        fromDate.disabled = !isRange;
        toDate.disabled = !isRange;
        monthInput.disabled = isRange;
    }

    rangeType.addEventListener('change', toggleInputs);
    toggleInputs(); // Initial call
});
</script>
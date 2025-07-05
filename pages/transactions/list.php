<?php
/**
 * Transactions List Page
 * Shows transactions with filters and AJAX pagination
 */

// Pagination parameters
$page_num = 1; // Always start with page 1 for initial load
$per_page = 500;

// Sorting parameters
$sort_column = $_GET['sort'] ?? 'operation_date';
$sort_order = $_GET['order'] ?? 'DESC';

// Validate sort column
$allowed_columns = ['operation_date', 'machine_number', 'transaction_type', 'amount', 'username'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'operation_date';
}

// Validate sort order
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Filter parameters
$filter_machine = $_GET['machine'] ?? 'all';
$date_range_type = $_GET['date_range_type'] ?? 'month';
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
$filter_date_to = $_GET['date_to'] ?? date('Y-m-t');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_category = $_GET['category'] ?? '';
$filter_transaction_type = $_GET['transaction_type'] ?? 'all';

// Calculate start and end dates
if ($date_range_type === 'range') {
    $start_date = $filter_date_from;
    $end_date = $filter_date_to;
} else {
    list($year, $month_num) = explode('-', $filter_month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Build query with filtering and pagination
try {
    $params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];
    
    // Base query - using operation_date for filtering
    $query = "SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username
              FROM transactions t
              JOIN machines m ON t.machine_id = m.id
              JOIN transaction_types tt ON t.transaction_type_id = tt.id
              JOIN users u ON t.user_id = u.id
              WHERE t.operation_date BETWEEN ? AND ?";
    
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
    if ($filter_transaction_type !== 'all') {
        $query .= " AND t.transaction_type_id = ?";
        $params[] = $filter_transaction_type;
    }

    // Get total count for pagination
    $count_query = str_replace("SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username", "SELECT COUNT(*)", $query);
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_transactions = $count_stmt->fetchColumn();
    $total_pages = ceil($total_transactions / $per_page);
    $has_more = $page_num < $total_pages;

// Prepare current filter parameters for passing to edit/view links
    $current_filters_array = [
        'machine' => $filter_machine,
        'date_range_type' => $date_range_type,
        'date_from' => $filter_date_from,
        'date_to' => $filter_date_to,
        'month' => $filter_month,
        'category' => $filter_category,
        'transaction_type' => $filter_transaction_type,
        'sort' => $sort_column,
        'order' => $sort_order
    ];
    $filter_query_string = http_build_query($current_filters_array);

    // Map sort columns to actual database columns
    $sort_map = [
        'operation_date' => 't.operation_date',
		'machine_number' => 'CAST(m.machine_number AS UNSIGNED)',
        'transaction_type' => 'tt.name',
        'amount' => 't.amount',
        'username' => 'u.username'
    ];
    
    $actual_sort_column = $sort_map[$sort_column] ?? 't.operation_date';

    // Add sorting and pagination to main query
    $query .= " ORDER BY $actual_sort_column $sort_order LIMIT $per_page OFFSET 0";

    // Execute main query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get machines for dropdown with brand information
    $machines_stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name 
        FROM machines m 
        LEFT JOIN brands b ON m.brand_id = b.id 
        ORDER BY m.machine_number
    ");
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get transaction types for category dropdown
    $types_stmt = $conn->query("SELECT DISTINCT category FROM transaction_types WHERE category IN ('OUT', 'DROP')");
    $categories = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all transaction types for transaction type filter
    $all_types_stmt = $conn->query("SELECT id, name, category FROM transaction_types ORDER BY category, name");
    $all_transaction_types = $all_types_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    set_flash_message('danger', "Database error: " . htmlspecialchars($e->getMessage()));
    $transactions = [];
    $machines = [];
    $categories = [];
    $all_transaction_types = [];
    $total_out = $total_drop = $total_result = 0;
    $has_more = false;
}

// Calculate totals and breakdown by transaction type
$total_out = $total_drop = 0;
$transaction_breakdown = [
    'DROP' => [],
    'OUT' => []
];

// Get totals for all transactions (not just current page) - using operation_date
try {
    $totals_query = "SELECT t.*, tt.name AS transaction_type, tt.category
                     FROM transactions t
                     JOIN transaction_types tt ON t.transaction_type_id = tt.id
                     WHERE t.operation_date BETWEEN ? AND ?";
    
    $totals_params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];
    
    if ($filter_machine !== 'all') {
        $totals_query .= " AND t.machine_id = ?";
        $totals_params[] = $filter_machine;
    }
    if ($filter_category === 'OUT') {
        $totals_query .= " AND tt.category = 'OUT'";
    } elseif ($filter_category === 'DROP') {
        $totals_query .= " AND tt.category = 'DROP'";
    }
    if ($filter_transaction_type !== 'all') {
        $totals_query .= " AND t.transaction_type_id = ?";
        $totals_params[] = $filter_transaction_type;
    }
    
    $totals_stmt = $conn->prepare($totals_query);
    $totals_stmt->execute($totals_params);
    $all_transactions = $totals_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_transactions as $t) {
        $category = $t['category'] ?? '';
        $type = $t['transaction_type'] ?? '';
        $amount = (float)($t['amount'] ?? 0);
        
        if ($category === 'OUT') {
            $total_out += $amount;
            if (!isset($transaction_breakdown['OUT'][$type])) {
                $transaction_breakdown['OUT'][$type] = 0;
            }
            $transaction_breakdown['OUT'][$type] += $amount;
        } elseif ($category === 'DROP') {
            $total_drop += $amount;
            if (!isset($transaction_breakdown['DROP'][$type])) {
                $transaction_breakdown['DROP'][$type] = 0;
            }
            $transaction_breakdown['DROP'][$type] += $amount;
        }
    }
} catch (PDOException $e) {
    // Handle error silently for totals
}

$total_result = $total_drop - $total_out;

// Build export URLs
$export_params = [
    'page' => 'transactions',
    'sort' => $sort_column,
    'order' => $sort_order,
    'date_range_type' => $date_range_type,
    'machine' => $filter_machine,
    'category' => $filter_category,
    'transaction_type' => $filter_transaction_type
];

if ($date_range_type === 'range') {
    $export_params['date_from'] = $filter_date_from;
    $export_params['date_to'] = $filter_date_to;
} else {
    $export_params['month'] = $filter_month;
}

$export_url_base = 'index.php?' . http_build_query($export_params);
$pdf_export_url = $export_url_base . '&export=pdf';
$excel_export_url = $export_url_base . '&export=excel';

// Check if we have filter parameters (indicating a report was generated)
$has_filters = $filter_machine !== 'all' || $date_range_type !== 'month' || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['month']) || !empty($filter_category) || $filter_transaction_type !== 'all';
?>

<div class="transactions-page fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header">
            <div class="filter-header-content">
                <h4 style="margin: 0;">Transaction Filters</h4>
                <span id="filter-toggle-icon" class="filter-toggle-icon">
                    <?php echo $has_filters ? '▼' : '▲'; ?>
                </span>
            </div>
        </div>
        <div class="card-body" id="filters-body" style="<?php echo $has_filters ? 'display: none;' : ''; ?>">
            <form action="index.php" method="GET" id="filters-form">
                <input type="hidden" name="page" value="transactions">
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
                                <label for="category">Category</label>
                                <select name="category" id="category" class="form-control">
                                    <option value="" <?= $filter_category === '' ? 'selected' : '' ?>>All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category'] ?>" <?= $filter_category === $cat['category'] ? 'selected' : '' ?>>
                                            <?= ucfirst($cat['category']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="transaction_type">Transaction Type</label>
                                <select name="transaction_type" id="transaction_type" class="form-control">
                                    <option value="all" <?= $filter_transaction_type === 'all' ? 'selected' : '' ?>>All Types</option>
                                    <optgroup label="OUT">
                                        <?php foreach ($all_transaction_types as $type): ?>
                                            <?php if ($type['category'] === 'OUT'): ?>
                                                <option value="<?= $type['id'] ?>" <?= $filter_transaction_type == $type['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="DROP">
                                        <?php foreach ($all_transaction_types as $type): ?>
                                            <?php if ($type['category'] === 'DROP'): ?>
                                                <option value="<?= $type['id'] ?>" <?= $filter_transaction_type == $type['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="index.php?page=transactions" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons mb-6 flex justify-between">
        <div>
            <?php if ($can_edit): ?>
                <a href="index.php?page=transactions&action=create" class="btn btn-primary">
                    Add New Transaction
                </a>
            <?php endif; ?>
        </div>
        <div class="export-buttons">
            <a href="<?= htmlspecialchars($pdf_export_url) ?>" class="btn btn-secondary" target="_blank">
                📄 Export to PDF
            </a>
            <a href="<?= htmlspecialchars($excel_export_url) ?>" class="btn btn-secondary">
                📊 Export to Excel
            </a>
        </div>
    </div>

    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Transactions for:</h3>
        <p class="date-range text-lg font-medium text-white mt-2 mb-1">
            <?php if ($filter_machine === 'all'): ?>
                All Machines
            <?php else: ?>
                <?php
                $selected_machine = null;
                foreach ($machines as $m) {
                    if ($m['id'] == $filter_machine) {
                        $selected_machine = $m;
                        break;
                    }
                }
                echo "Machine #" . htmlspecialchars($selected_machine['machine_number'] ?? 'Unknown');
                if ($selected_machine['brand_name']) {
                    echo " (" . htmlspecialchars($selected_machine['brand_name']) . ")";
                }
                ?>
            <?php endif; ?>
            
            <?php if ($filter_transaction_type !== 'all'): ?>
                <?php
                $selected_type = null;
                foreach ($all_transaction_types as $type) {
                    if ($type['id'] == $filter_transaction_type) {
                        $selected_type = $type;
                        break;
                    }
                }
                echo " - " . htmlspecialchars($selected_type['name'] ?? 'Unknown Type');
            ?>
            <?php elseif (!empty($filter_category)): ?>
                - <?= ucfirst($filter_category) ?>
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

    <!-- Summary Stats with Breakdown -->
    <div class="stats-container grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card in p-4 rounded bg-opacity-10 bg-success-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total DROP</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_drop); ?></div>
            <!-- DROP Breakdown -->
            <div class="stat-breakdown">
                <?php foreach ($transaction_breakdown['DROP'] as $type => $amount): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-type"><?php echo htmlspecialchars($type); ?></span>
                        <span class="breakdown-amount"><?php echo format_currency($amount); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="stat-card out p-4 rounded bg-opacity-10 bg-danger-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total OUT</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_out); ?></div>
            <!-- OUT Breakdown -->
            <div class="stat-breakdown">
                <?php foreach ($transaction_breakdown['OUT'] as $type => $amount): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-type"><?php echo htmlspecialchars($type); ?></span>
                        <span class="breakdown-amount"><?php echo format_currency($amount); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stat-card <?php echo $total_result >= 0 ? 'in' : 'out'; ?> p-4 rounded text-center bg-opacity-10 <?php echo $total_result >= 0 ? 'bg-success-color' : 'bg-danger-color'; ?>">
            <div class="stat-title uppercase text-sm text-muted">Result</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_result); ?></div>
            <div class="stat-breakdown">
                <div class="breakdown-item">
                    <span class="breakdown-type">DROP - OUT</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Note -->
    <?php if (!empty($transactions)): ?>
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

    <!-- Transactions Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">
                Transactions 
                <span class="text-sm font-normal" id="transaction-count">
                    (Showing <?php echo count($transactions); ?> of <?php echo $total_transactions; ?>)
                </span>
            </h3>
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
                            <th class="px-4 py-2 text-left sortable-header" data-sort-column="transaction_type" data-sort-order="<?php echo $sort_column == 'transaction_type' ? $toggle_order : 'ASC'; ?>">
                                Transaction <?php if ($sort_column == 'transaction_type') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                            </th>
                            <th class="px-4 py-2 text-right sortable-header" data-sort-column="amount" data-sort-order="<?php echo $sort_column == 'amount' ? $toggle_order : 'ASC'; ?>">
                                Amount <?php if ($sort_column == 'amount') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                            </th>
                            <th class="px-4 py-2 text-left">Category</th>
                            <th class="px-4 py-2 text-left">User</th>
                            <th class="px-4 py-2 text-left">Notes</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="transactions-tbody">
                        <?php if (empty($transactions)): ?>
                            <tr id="no-transactions-row">
                                <td colspan="8" class="text-center px-4 py-6">No transactions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(format_date($t['operation_date'])); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['machine_number']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['transaction_type']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($t['amount']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['category'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['username']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['notes'] ?? ''); ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="index.php?page=transactions&action=view&id=<?php echo $t['id']; ?>" class="action-btn view-btn" data-tooltip="View Details"><span class="menu-icon"><img src="<?= icon('view2') ?>"/></span></a>
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=transactions&action=edit&id=<?php echo $t['id']; ?>&<?php echo $filter_query_string; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                                            <a href="index.php?page=transactions&action=delete&id=<?php echo $t['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this transaction?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Load More Button -->
            <?php if ($has_more): ?>
                <div class="text-center mt-6">
                    <button id="load-more-btn" class="btn btn-primary" >
                        Load More Transactions
                    </button>
                    <div id="loading-indicator" class="hidden mt-2">
                        <span class="text-gray-400">Loading...</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Pagination Info -->
            <div class="text-center mt-4 text-gray-400 text-sm" id="pagination-info"
     data-total-pages="<?php echo htmlspecialchars($total_pages); ?>"
     data-total-transactions="<?php echo htmlspecialchars($total_transactions); ?>">
    Page <?php echo $page_num; ?> of <?php echo $total_pages; ?>
    (<?php echo $total_transactions; ?> total transactions)
</div>

        </div>
    </div>
</div>
<script src="assets/js/transactions_list.js"></script>

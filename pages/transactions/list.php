<?php
/**
 * Transactions List Page
 * Shows transactions with filters and AJAX pagination
 */

// Pagination parameters
$page_num = 1; // Always start with page 1 for initial load
$per_page = 5;

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
$date_range_type = $_GET['date_range_type'] ?? 'month';
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
$filter_date_to = $_GET['date_to'] ?? date('Y-m-t');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_category = $_GET['category'] ?? '';

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

    // Get total count for pagination
    $count_query = str_replace("SELECT t.*, m.machine_number, tt.name AS transaction_type, tt.category, u.username", "SELECT COUNT(*)", $query);
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_transactions = $count_stmt->fetchColumn();
    $total_pages = ceil($total_transactions / $per_page);
    $has_more = $page_num < $total_pages;

    // Map sort columns to actual database columns
    $sort_map = [
        'timestamp' => 't.timestamp',
        'machine_number' => 'm.machine_number',
        'transaction_type' => 'tt.name',
        'amount' => 't.amount',
        'username' => 'u.username'
    ];
    
    $actual_sort_column = $sort_map[$sort_column] ?? 't.timestamp';

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

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $transactions = [];
    $machines = [];
    $categories = [];
    $total_out = $total_drop = $total_result = 0;
    $has_more = false;
}

// Calculate totals and breakdown by transaction type
$total_out = $total_drop = 0;
$transaction_breakdown = [
    'DROP' => [],
    'OUT' => []
];

// Get totals for all transactions (not just current page)
try {
    $totals_query = "SELECT t.*, tt.name AS transaction_type, tt.category
                     FROM transactions t
                     JOIN transaction_types tt ON t.transaction_type_id = tt.id
                     WHERE t.timestamp BETWEEN ? AND ?";
    
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

// Check if we have filter parameters (indicating a report was generated)
$has_filters = $filter_machine !== 'all' || $date_range_type !== 'month' || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['month']) || !empty($filter_category);
?>

<div class="transactions-page fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header" style="cursor: pointer;" onclick="toggleFilters()">
            <div style="display: flex; justify-content: space-between; align-items: center;">
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

                <!-- Machine & Category Selection -->
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
    <?php if ($can_edit): ?>
        <div class="action-buttons mb-6 flex justify-end">
            <a href="index.php?page=transactions&action=create" class="btn btn-primary">
                Add New Transaction
            </a>
        </div>
    <?php endif; ?>

    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Transactions for:</h3>
        <p class="date-range text-lg font-medium text-white mt-2 mb-1">
            <?php if ($filter_machine === 'all'): ?>
                All Machines <?php echo $filter_category; ?>
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
                echo " " . $filter_category;
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
                            <th class="px-4 py-2 text-left">
                                <a href="#" onclick="sortTransactions('timestamp', '<?php echo $toggle_order; ?>')">
                                    Date & Time <?php if ($sort_column == 'timestamp') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="#" onclick="sortTransactions('machine_number', '<?php echo $toggle_order; ?>')">
                                    Machine <?php if ($sort_column == 'machine_number') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="#" onclick="sortTransactions('transaction_type', '<?php echo $toggle_order; ?>')">
                                    Transaction <?php if ($sort_column == 'transaction_type') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="#" onclick="sortTransactions('amount', '<?php echo $toggle_order; ?>')">
                                    Amount <?php if ($sort_column == 'amount') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
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
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(format_datetime($t['timestamp'], 'd M Y - H:i:s')); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['machine_number']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['transaction_type']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($t['amount']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['category'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['username']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($t['notes'] ?? ''); ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="index.php?page=transactions&action=view&id=<?php echo $t['id']; ?>" class="action-btn view-btn" data-tooltip="View Details"><span class="menu-icon"><img src="<?= icon('view2') ?>"/></span></a>
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=transactions&action=edit&id=<?php echo $t['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
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
                    <button id="load-more-btn" class="btn btn-primary" onclick="loadMoreTransactions()">
                        Load More Transactions
                    </button>
                    <div id="loading-indicator" class="hidden mt-2">
                        <span class="text-gray-400">Loading...</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Pagination Info -->
            <div class="text-center mt-4 text-gray-400 text-sm" id="pagination-info">
                Page <?php echo $page_num; ?> of <?php echo $total_pages; ?> 
                (<?php echo $total_transactions; ?> total transactions)
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for AJAX pagination, sorting, and toggle filters -->
<script>
let currentPage = <?php echo $page_num; ?>;
let totalPages = <?php echo $total_pages; ?>;
let isLoading = false;

// Current filter parameters
const currentFilters = {
    machine: '<?php echo $filter_machine; ?>',
    date_range_type: '<?php echo $date_range_type; ?>',
    date_from: '<?php echo $filter_date_from; ?>',
    date_to: '<?php echo $filter_date_to; ?>',
    month: '<?php echo $filter_month; ?>',
    category: '<?php echo $filter_category; ?>',
    sort: '<?php echo $sort_column; ?>',
    order: '<?php echo $sort_order; ?>'
};

function loadMoreTransactions() {
    if (isLoading || currentPage >= totalPages) return;
    
    isLoading = true;
    document.getElementById('loading-indicator').classList.remove('hidden');
    document.getElementById('load-more-btn').disabled = true;
    
    const nextPage = currentPage + 1;
    
    // Build the URL parameters for the separate AJAX endpoint
    const params = new URLSearchParams();
    params.set('page_num', nextPage);
    
    // Add all current filters
    Object.keys(currentFilters).forEach(key => {
        params.set(key, currentFilters[key]);
    });
    
    const url = 'pages/transactions/ajax_transactions.php?' + params.toString();
    console.log('Loading URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.log('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Response is not JSON. Got: ' + contentType);
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.error) {
                alert('Error loading transactions: ' + data.error);
                return;
            }
            
            if (data.success && data.transactions) {
                appendTransactions(data.transactions);
                currentPage = data.current_page;
                totalPages = data.total_pages;
                
                // Hide load more button if no more pages
                if (!data.has_more) {
                    document.getElementById('load-more-btn').style.display = 'none';
                }
                
                // Update pagination info
                updatePaginationInfo(data);
            } else {
                console.error('Invalid response format:', data);
                alert('Invalid response format received');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error loading transactions: ' + error.message);
        })
        .finally(() => {
            isLoading = false;
            document.getElementById('loading-indicator').classList.add('hidden');
            document.getElementById('load-more-btn').disabled = false;
        });
}

function appendTransactions(transactions) {
    const tbody = document.getElementById('transactions-tbody');
    
    // Remove "no transactions" row if it exists
    const noTransactionsRow = document.getElementById('no-transactions-row');
    if (noTransactionsRow) {
        noTransactionsRow.remove();
    }
    
    transactions.forEach(t => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-800 transition duration-150';
        row.innerHTML = `
            <td class="px-4 py-2">${t.timestamp}</td>
            <td class="px-4 py-2">${t.machine_number}</td>
            <td class="px-4 py-2">${t.transaction_type}</td>
            <td class="px-4 py-2 text-right">${t.amount}</td>
            <td class="px-4 py-2">${t.category}</td>
            <td class="px-4 py-2">${t.username}</td>
            <td class="px-4 py-2">${t.notes}</td>
            <td class="px-4 py-2 text-right">
                <a href="index.php?page=transactions&action=view&id=${t.id}" class="action-btn view-btn" data-tooltip="View Details"><span class="menu-icon"><img src="<?= icon('view2') ?>"/></span></a>
                ${t.can_edit ? `
                    <a href="index.php?page=transactions&action=edit&id=${t.id}" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                    <a href="index.php?page=transactions&action=delete&id=${t.id}" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this transaction?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
                ` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updatePaginationInfo(data) {
    const countElement = document.getElementById('transaction-count');
    if (countElement) {
        const currentCount = document.querySelectorAll('#transactions-tbody tr').length;
        countElement.textContent = `(Showing ${currentCount} of ${data.total_transactions})`;
    }
    
    const paginationInfo = document.getElementById('pagination-info');
    if (paginationInfo) {
        paginationInfo.innerHTML = `Page ${data.current_page} of ${data.total_pages} (${data.total_transactions} total transactions)`;
    }
}

function sortTransactions(column, order) {
    // Update current filters
    currentFilters.sort = column;
    currentFilters.order = order;
    
    // Reset to first page
    currentPage = 1;
    
    // Build URL parameters
    const params = new URLSearchParams();
    params.set('page', 'transactions');
    
    Object.keys(currentFilters).forEach(key => {
        params.set(key, currentFilters[key]);
    });
    
    window.location.href = 'index.php?' + params.toString();
}

// Toggle filters function
function toggleFilters() {
    const filtersBody = document.getElementById('filters-body');
    const toggleIcon = document.getElementById('filter-toggle-icon');
    
    if (filtersBody.style.display === 'none') {
        filtersBody.style.display = 'block';
        toggleIcon.textContent = '▲';
        // Add smooth animation
        filtersBody.style.opacity = '0';
        filtersBody.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            filtersBody.style.transition = 'all 0.3s ease';
            filtersBody.style.opacity = '1';
            filtersBody.style.transform = 'translateY(0)';
        }, 10);
    } else {
        filtersBody.style.transition = 'all 0.3s ease';
        filtersBody.style.opacity = '0';
        filtersBody.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            filtersBody.style.display = 'none';
            toggleIcon.textContent = '▼';
        }, 300);
    }
}

// Date range toggle functionality
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
    
    // Handle form submission to reset pagination
    document.getElementById('filters-form').addEventListener('submit', function() {
        currentPage = 1;
    });
});
</script>
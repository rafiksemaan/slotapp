<?php
/**
 * Daily Tracking List Page
 * Shows daily tracking data with filtering and sorting options
 */

// Get sorting parameters
$sort_column = $_GET['sort'] ?? 'tracking_date';
$sort_order = $_GET['order'] ?? 'DESC';

// Validate sort column
$allowed_columns = ['tracking_date', 'total_drop', 'total_out', 'total_result', 'total_result_percentage'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'tracking_date';
}

// Validate sort order
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Get filter values
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

// Build query for daily tracking data
try {
    $params = [$start_date, $end_date];
    
    $query = "SELECT dt.*, 
                     u.username as created_by_username,
                     eu.username as updated_by_username
              FROM daily_tracking dt
              LEFT JOIN users u ON dt.created_by = u.id
              LEFT JOIN users eu ON dt.updated_by = eu.id
              WHERE dt.tracking_date BETWEEN ? AND ?
              ORDER BY $sort_column $sort_order";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tracking_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_slots_drop = array_sum(array_column($tracking_data, 'slots_drop'));
    $total_slots_out = array_sum(array_column($tracking_data, 'slots_out'));
    $total_gambee_drop = array_sum(array_column($tracking_data, 'gambee_drop'));
    $total_gambee_out = array_sum(array_column($tracking_data, 'gambee_out'));
    $total_coins_drop = array_sum(array_column($tracking_data, 'coins_drop'));
    $total_coins_out = array_sum(array_column($tracking_data, 'coins_out'));
    
    $grand_total_drop = $total_slots_drop + $total_gambee_drop + $total_coins_drop;
    $grand_total_out = $total_slots_out + $total_gambee_out + $total_coins_out;
    $grand_total_result = $grand_total_drop - $grand_total_out;
    $grand_total_percentage = $grand_total_drop > 0 ? (($grand_total_result / $grand_total_drop) * 100) : 0;
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $tracking_data = [];
    $grand_total_drop = $grand_total_out = $grand_total_result = $grand_total_percentage = 0;
}

// Check if we have filter parameters
$has_filters = $date_range_type !== 'month' || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['month']);

// Generate report title
if ($date_range_type === 'range') {
    $date_subtitle = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
} else {
    $date_subtitle = date('F Y', strtotime($month));
}
?>

<div class="daily-tracking-page fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header" style="cursor: pointer;" onclick="toggleFilters()">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0;">Date Range Filters</h4>
                <span id="filter-toggle-icon" class="filter-toggle-icon">
                    <?php echo $has_filters ? '▼' : '▲'; ?>
                </span>
            </div>
        </div>
        <div class="card-body" id="filters-body" style="<?php echo $has_filters ? 'display: none;' : ''; ?>">
            <form action="index.php" method="GET">
                <input type="hidden" name="page" value="daily_tracking">
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

                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="index.php?page=daily_tracking" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Action Buttons -->
    <?php if ($can_edit): ?>
        <div class="action-buttons mb-6">
            <a href="index.php?page=daily_tracking&action=create" class="btn btn-primary">Add Daily Entry</a>
        </div>
    <?php endif; ?>

    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Daily Tracking Report</h3>
        <p class="date-range text-lg font-medium text-white mt-2 mb-1">
            <?= htmlspecialchars($date_subtitle) ?>
        </p>
        <p class="generated-at text-sm italic text-gray-400">
            Generated at: <?= cairo_time('d M Y – H:i:s') ?>
        </p>
    </div>

    <!-- Summary Stats -->
    <div class="stats-container grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card in p-4 rounded bg-opacity-10 bg-success-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total DROP</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_drop); ?></div>
        </div>
        <div class="stat-card out p-4 rounded bg-opacity-10 bg-danger-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total OUT</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_out); ?></div>
        </div>
        <div class="stat-card <?php echo $grand_total_result >= 0 ? 'in' : 'out'; ?> p-4 rounded text-center bg-opacity-10 <?php echo $grand_total_result >= 0 ? 'bg-success-color' : 'bg-danger-color'; ?>">
            <div class="stat-title uppercase text-sm text-muted">Total Result</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_result); ?></div>
        </div>
        <div class="stat-card p-4 rounded bg-opacity-10 bg-warning-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Result %</div>
            <div class="stat-value text-lg font-bold"><?php echo number_format($grand_total_percentage, 2); ?>%</div>
        </div>
    </div>

    <!-- Daily Tracking Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Daily Tracking Data</h3>
        </div>
        <div class="card-body p-6">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700 separated-columns">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">
                                <a href="?page=daily_tracking&sort=tracking_date&order=<?php echo $sort_column == 'tracking_date' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Date <?php if ($sort_column == 'tracking_date') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <!-- Slots -->
                            <th class="px-4 py-2 text-right">Slots Drop</th>
                            <th class="px-4 py-2 text-right">Slots Out</th>
                            <th class="px-4 py-2 text-right">Slots Result</th>
                            <th class="px-4 py-2 text-right">Slots %</th>
                            <!-- Gambee -->
                            <th class="px-4 py-2 text-right">Gambee Drop</th>
                            <th class="px-4 py-2 text-right">Gambee Out</th>
                            <th class="px-4 py-2 text-right">Gambee Result</th>
                            <th class="px-4 py-2 text-right">Gambee %</th>
                            <!-- Coins -->
                            <th class="px-4 py-2 text-right">Coins Drop</th>
                            <th class="px-4 py-2 text-right">Coins Out</th>
                            <th class="px-4 py-2 text-right">Coins Result</th>
                            <th class="px-4 py-2 text-right">Coins %</th>
                            <!-- Totals -->
                            <th class="px-4 py-2 text-right highlight-drop">
                                <a href="?page=daily_tracking&sort=total_drop&order=<?php echo $sort_column == 'total_drop' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Total Drop <?php if ($sort_column == 'total_drop') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right highlight-out">
                                <a href="?page=daily_tracking&sort=total_out&order=<?php echo $sort_column == 'total_out' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Total Out <?php if ($sort_column == 'total_out') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right highlight-result">
                                <a href="?page=daily_tracking&sort=total_result&order=<?php echo $sort_column == 'total_result' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Total Result <?php if ($sort_column == 'total_result') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="?page=daily_tracking&sort=total_result_percentage&order=<?php echo $sort_column == 'total_result_percentage' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Total % <?php if ($sort_column == 'total_result_percentage') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($tracking_data)): ?>
                            <tr>
                                <td colspan="18" class="text-center px-4 py-6">No daily tracking data found for the selected period</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tracking_data as $data): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2 font-medium"><?php echo format_date($data['tracking_date']); ?></td>
                                    
                                    <!-- Slots -->
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($data['slots_drop']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($data['slots_out']); ?></td>
                                    <td class="px-4 py-2 text-right <?php echo $data['slots_result'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo format_currency($data['slots_result']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo number_format($data['slots_percentage'], 2); ?>%</td>
                                    
                                    <!-- Gambee -->
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($data['gambee_drop']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($data['gambee_out']); ?></td>
                                    <td class="px-4 py-2 text-right <?php echo $data['gambee_result'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo format_currency($data['gambee_result']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo number_format($data['gambee_percentage'], 2); ?>%</td>
                                    
                                    <!-- Coins -->
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($data['coins_drop']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($data['coins_out']); ?></td>
                                    <td class="px-4 py-2 text-right <?php echo $data['coins_result'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo format_currency($data['coins_result']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo number_format($data['coins_percentage'], 2); ?>%</td>
                                    
                                    <!-- Totals -->
                                    <td class="px-4 py-2 text-right highlight-drop"><strong><?php echo format_currency($data['total_drop']); ?></strong></td>
                                    <td class="px-4 py-2 text-right highlight-out"><strong><?php echo format_currency($data['total_out']); ?></strong></td>
                                    <td class="px-4 py-2 text-right highlight-result <?php echo $data['total_result'] >= 0 ? 'positive' : 'negative'; ?>"><strong><?php echo format_currency($data['total_result']); ?></strong></td>
                                    <td class="px-4 py-2 text-right"><strong><?php echo number_format($data['total_result_percentage'], 2); ?>%</strong></td>
                                    
                                    <td class="px-4 py-2 text-right">
                                        <a href="index.php?page=daily_tracking&action=edit&id=<?php echo $data['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                                        <a href="index.php?page=daily_tracking&action=delete&id=<?php echo $data['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this daily tracking entry?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
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

<!-- JavaScript for form interactions and toggle filters -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateRangeType = document.getElementById('date_range_type');
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const monthSelect = document.getElementById('month');

    function toggleDateInputs() {
        const type = dateRangeType.value;
        
        dateFrom.disabled = type !== 'range';
        dateTo.disabled = type !== 'range';
        monthSelect.disabled = type !== 'month';
    }

    dateRangeType.addEventListener('change', toggleDateInputs);
    toggleDateInputs(); // Initial call
});

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
</script>

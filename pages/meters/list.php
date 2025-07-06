<?php
/**
 * Meters List Page
 */

// Get sorting parameters
$sort_column = $_GET['sort'] ?? 'operation_date';
$sort_order = $_GET['order'] ?? 'DESC';

// Validate sort column
$allowed_columns = ['operation_date', 'machine_number', 'meter_type', 'total_in', 'total_out', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'operation_date';
}

// Validate sort order
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Get filter values
$filter_machine = $_GET['machine'] ?? 'all';
$date_range_type = $_GET['date_range_type'] ?? 'month';
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
$filter_date_to = $_GET['date_to'] ?? date('Y-m-t');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_meter_type = $_GET['meter_type'] ?? 'all';

// Calculate start and end dates
if ($date_range_type === 'range') {
    $start_date = $filter_date_from;
    $end_date = $filter_date_to;
} else {
    list($year, $month_num) = explode('-', $filter_month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
}

// Build query
try {
    $params = ["{$start_date} 00:00:00", "{$end_date} 23:59:59"];
    
    // MODIFIED: Explicitly alias mtr.id as meter_entry_id
    $query = "SELECT mtr.*, mtr.id AS meter_entry_id, m.machine_number, m.id AS machine_id, m.system_comp, mt.name AS machine_type, u.username
              FROM meters mtr
              JOIN machines m ON mtr.machine_id = m.id
              LEFT JOIN machine_types mt ON m.type_id = mt.id
              JOIN users u ON mtr.created_by = u.id
              WHERE mtr.operation_date BETWEEN ? AND ?";
    
    // Apply filters
    if ($filter_machine !== 'all') {
        $query .= " AND mtr.machine_id = ?";
        $params[] = $filter_machine;
    }
    if ($filter_meter_type !== 'all') {
        $query .= " AND mtr.meter_type = ?";
        $params[] = $filter_meter_type;
    }

    // Map sort columns to actual database columns
    $sort_map = [
        'operation_date' => 'mtr.operation_date',
        'machine_number' => 'm.machine_number',
        'meter_type' => 'mtr.meter_type',
        'total_in' => 'mtr.total_in',
        'total_out' => 'mtr.total_out',
        'created_at' => 'mtr.created_at'
    ];
    
    $actual_sort_column = $sort_map[$sort_column] ?? 'mtr.operation_date';

    // Add sorting
    $query .= " ORDER BY $actual_sort_column $sort_order";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $meters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get machines for filter dropdown
    $machines_stmt = $conn->query("SELECT id, machine_number, system_comp, mt.name AS machine_type FROM machines m LEFT JOIN machine_types mt ON m.type_id = mt.id ORDER BY CAST(machine_number AS UNSIGNED)");
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get meter types for filter dropdown
    $meter_types_filter = [
        'total_in' => 'Total In',
        'total_out' => 'Total Out',
        'bills_in' => 'Bills In',
        'coins_in' => 'Coins In',
        'coins_out' => 'Coins Out',
        'coins_drop' => 'Coins Drop',
        'bets_handpay' => 'Bets Handpay',
        'handpay' => 'Handpay',
        'jp' => 'JP'
    ];

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $meters = [];
    $machines = [];
    $meter_types_filter = [];
}

// Check if we have filter parameters
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
                                            <?php if ($m['machine_type']): ?>
                                                (<?= htmlspecialchars($m['machine_type']) ?>)
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
                                    <?php foreach ($meter_types_filter as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $filter_meter_type === $key ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <!-- Empty for layout balance -->
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
    <?php if ($can_edit): ?>
        <div class="action-buttons mb-6">
            <a href="index.php?page=meters&action=create" class="btn btn-primary">Add New Meter Entry</a>
        </div>
    <?php endif; ?>

    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Meter Entries for:</h3>
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
                if ($selected_machine['machine_type']) {
                    echo " (" . htmlspecialchars($selected_machine['machine_type']) . ")";
                }
                ?>
            <?php endif; ?>
            
            <?php if ($filter_meter_type !== 'all'): ?>
                - <?= htmlspecialchars($meter_types_filter[$filter_meter_type] ?? 'Unknown Type') ?>
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
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=meters&sort=operation_date&order=<?php echo $sort_column == 'operation_date' ? $toggle_order : 'ASC'; ?>">
                                    Operation Date <?php if ($sort_column == 'operation_date') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=meters&sort=machine_number&order=<?php echo $sort_column == 'machine_number' ? $toggle_order : 'ASC'; ?>">
                                    Machine # <?php if ($sort_column == 'machine_number') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">Machine Type</th>
                            <th class="px-4 py-2 text-left">System Comp.</th>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=meters&sort=meter_type&order=<?php echo $sort_column == 'meter_type' ? $toggle_order : 'ASC'; ?>">
                                    Meter Type <?php if ($sort_column == 'meter_type') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="index.php?page=meters&sort=total_in&order=<?php echo $sort_column == 'total_in' ? $toggle_order : 'ASC'; ?>">
                                    Total In <?php if ($sort_column == 'total_in') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="index.php?page=meters&sort=total_out&order=<?php echo $sort_column == 'total_out' ? $toggle_order : 'ASC'; ?>">
                                    Total Out <?php if ($sort_column == 'total_out') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">Created By</th>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=meters&sort=created_at&order=<?php echo $sort_column == 'created_at' ? $toggle_order : 'ASC'; ?>">
                                    Created At <?php if ($sort_column == 'created_at') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($meters)): ?>
                            <tr>
                                <td colspan="10" class="text-center px-4 py-6">No meter entries found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meters as $meter): ?>
                                <tr class="hover:bg-gray-800 transition duration-150 clickable-row" data-machine-id="<?php echo htmlspecialchars($meter['machine_id']); ?>">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(format_date($meter['operation_date'])); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['machine_number']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['machine_type'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['system_comp']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter_types_filter[$meter['meter_type']] ?? $meter['meter_type']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($meter['total_in']); ?></td>
                                    <td class="px-4 py-2 text-right"><?php echo format_currency($meter['total_out']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['username']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(format_datetime($meter['created_at'], 'd M Y H:i')); ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <!-- MODIFIED: Use meter_entry_id for view, edit, delete actions -->
                                        <a href="index.php?page=meters&action=view&id=<?php echo $meter['meter_entry_id']; ?>" class="action-btn view-btn" data-tooltip="View Details"><span class="menu-icon"><img src="<?= icon('view2') ?>"/></span></a>
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=meters&action=edit&id=<?php echo $meter['meter_entry_id']; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                                            <a href="index.php?page=meters&action=delete&id=<?php echo $meter['meter_entry_id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this meter entry?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
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


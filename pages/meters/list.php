<?php
/**
 * Meters List Page
 * Shows meter entries with filtering and sorting options
 */

// Placeholder for sorting parameters
$sort_column = $_GET['sort'] ?? 'operation_date';
$sort_order = $_GET['order'] ?? 'DESC';
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Placeholder for filter parameters
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

// Placeholder for fetching machines for dropdown
// In a real scenario, you would fetch this from your 'machines' table
$machines = [
    ['id' => 1, 'machine_number' => 'M001', 'brand_name' => 'Brand A'],
    ['id' => 2, 'machine_number' => 'M002', 'brand_name' => 'Brand B'],
    ['id' => 3, 'machine_number' => 'M003', 'brand_name' => 'Brand A'],
];

// Placeholder for meter types dropdown
$meter_types = ['Online', 'Coins', 'Offline'];

// Placeholder for meter data (assuming a 'meters' table exists)
// This data would be fetched from the database based on filters and sorting
$meters = [
    [
        'id' => 101,
        'machine_id' => 1,
        'machine_number' => 'M001',
        'operation_date' => '2025-06-01',
        'meter_type' => 'Online',
        'total_in' => 1500.00,
        'total_out' => 1200.00,
        'notes' => 'Daily online meter reading'
    ],
    [
        'id' => 102,
        'machine_id' => 2,
        'machine_number' => 'M002',
        'operation_date' => '2025-06-01',
        'meter_type' => 'Coins',
        'coins_in' => 500.00,
        'coins_out' => 450.00,
        'notes' => 'Manual coins meter reading'
    ],
    [
        'id' => 103,
        'machine_id' => 3,
        'machine_number' => 'M003',
        'operation_date' => '2025-06-02',
        'meter_type' => 'Offline',
        'total_in' => 1000.00,
        'total_out' => 800.00,
        'notes' => 'Offline machine reading'
    ],
];

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
                                    <?php foreach ($meter_types as $type): ?>
                                        <option value="<?= strtolower($type) ?>" <?= $filter_meter_type === strtolower($type) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type) ?>
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
                    Add New Meter Entry
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
                            <th class="px-4 py-2 text-left">Notes</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($meters)): ?>
                            <tr>
                                <td colspan="7" class="text-center px-4 py-6">No meter entries found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meters as $meter): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(format_date($meter['operation_date'])); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['machine_number']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['meter_type']); ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <?php
                                        // Display specific 'in' field based on meter type
                                        if ($meter['meter_type'] === 'Coins') {
                                            echo format_currency($meter['coins_in'] ?? 0);
                                        } else {
                                            echo format_currency($meter['total_in'] ?? 0);
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <?php
                                        // Display specific 'out' field based on meter type
                                        if ($meter['meter_type'] === 'Coins') {
                                            echo format_currency($meter['coins_out'] ?? 0);
                                        } else {
                                            echo format_currency($meter['total_out'] ?? 0);
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($meter['notes'] ?? ''); ?></td>
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

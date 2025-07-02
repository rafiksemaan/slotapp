<?php
/**
 * Guest Tracking List Page
 * Shows aggregated guest data with filtering options
 * Default view shows only the latest uploaded file data
 */

// Get filter values
$date_range_type = $_GET['date_range_type'] ?? 'latest_upload';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$month = $_GET['month'] ?? date('Y-m');
$guest_search = $_GET['guest_search'] ?? '';

// Get sorting parameters
$sort_column = $_GET['sort'] ?? 'total_drop';
$sort_order = $_GET['order'] ?? 'DESC';

// Validate sort column
$allowed_columns = ['guest_code_id', 'guest_name', 'total_drop', 'total_result', 'total_visits', 'last_visit'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'total_drop';
}

// Validate sort order
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Get the latest upload date for default filtering
try {
    $latest_upload_stmt = $conn->query("
        SELECT MAX(upload_date) as latest_date 
        FROM guest_uploads 
        ORDER BY upload_date DESC 
        LIMIT 1
    ");
    $latest_upload = $latest_upload_stmt->fetch(PDO::FETCH_ASSOC);
    $latest_upload_date = $latest_upload['latest_date'] ?? date('Y-m-d');
} catch (PDOException $e) {
    $latest_upload_date = date('Y-m-d');
}

// Calculate date range for filtering
$date_filter = '';
$date_params = [];

if ($date_range_type === 'latest_upload') {
    // Show only data from the latest upload
    $date_filter = " AND gd.upload_date = ?";
    $date_params = [$latest_upload_date];
} elseif ($date_range_type === 'range') {
    $date_filter = " AND gd.upload_date BETWEEN ? AND ?";
    $date_params = [$date_from, $date_to];
} elseif ($date_range_type === 'month') {
    list($year, $month_num) = explode('-', $month);
    $start_date = "$year-$month_num-01";
    $end_date = date("Y-m-t", strtotime($start_date));
    $date_filter = " AND gd.upload_date BETWEEN ? AND ?";
    $date_params = [$start_date, $end_date];
}
// If 'all_time' is selected, no date filter is applied

// Build query for aggregated guest data
try {
    $query = "
        SELECT 
            g.guest_code_id,
            g.guest_name,
            SUM(gd.drop_amount) as total_drop,
            SUM(gd.result_amount) as total_result,
            SUM(gd.visits) as total_visits,
            MAX(gd.upload_date) as last_visit,
            COUNT(DISTINCT gd.upload_date) as upload_count
        FROM guests g
        LEFT JOIN guest_data gd ON g.guest_code_id = gd.guest_code_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add date filter
    if (!empty($date_filter)) {
        $query .= $date_filter;
        $params = array_merge($params, $date_params);
    }
    
    // Add guest search filter
    if (!empty($guest_search)) {
        $query .= " AND (g.guest_code_id LIKE ? OR g.guest_name LIKE ?)";
        $params[] = "%$guest_search%";
        $params[] = "%$guest_search%";
    }
    
    $query .= " GROUP BY g.guest_code_id, g.guest_name";
    $query .= " ORDER BY $sort_column $sort_order";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_drop = array_sum(array_column($guests, 'total_drop'));
    $total_result = array_sum(array_column($guests, 'total_result'));
    $total_visits = array_sum(array_column($guests, 'total_visits'));
    $total_guests = count($guests);
    
    // Get upload history for dropdown
    $uploads_stmt = $conn->query("
        SELECT DISTINCT upload_date, upload_filename 
        FROM guest_uploads 
        ORDER BY upload_date DESC
    ");
    $uploads = $uploads_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $guests = [];
    $uploads = [];
    $total_drop = $total_result = $total_visits = $total_guests = 0;
}

// Build export URLs
$export_params = [
    'page' => 'guest_tracking',
    'action' => 'export',
    'sort' => $sort_column,
    'order' => $sort_order,
    'date_range_type' => $date_range_type,
    'guest_search' => $guest_search
];

if ($date_range_type === 'range') {
    $export_params['date_from'] = $date_from;
    $export_params['date_to'] = $date_to;
} elseif ($date_range_type === 'month') {
    $export_params['month'] = $month;
}

$export_url_base = 'index.php?' . http_build_query($export_params);
$pdf_export_url = $export_url_base . '&export=pdf';
$excel_export_url = $export_url_base . '&export=excel';

// Check if we have filter parameters
$has_filters = $date_range_type !== 'latest_upload' || !empty($guest_search);

// Generate filter description for display
$filter_description = '';
if ($date_range_type === 'latest_upload') {
    $filter_description = "Latest Upload (" . format_date($latest_upload_date) . ")";
} elseif ($date_range_type === 'range') {
    $filter_description = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
} elseif ($date_range_type === 'month') {
    $filter_description = date('F Y', strtotime($month));
} else {
    $filter_description = "All Time";
}
?>

<div class="guest-tracking-page fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header" style="cursor: pointer;" onclick="toggleFilters()">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0;">Guest Tracking Filters</h4>
                <span id="filter-toggle-icon" class="filter-toggle-icon">
                    <?php echo $has_filters ? '▼' : '▲'; ?>
                </span>
            </div>
        </div>
        <div class="card-body" id="filters-body" style="<?php echo $has_filters ? 'display: none;' : ''; ?>">
            <form action="index.php" method="GET">
                <input type="hidden" name="page" value="guest_tracking">
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
                                    <option value="latest_upload" <?= $date_range_type === 'latest_upload' ? 'selected' : '' ?>>Latest Upload Only</option>
                                    <option value="all_time" <?= $date_range_type === 'all_time' ? 'selected' : '' ?>>All Time</option>
                                    <option value="month" <?= $date_range_type === 'month' ? 'selected' : '' ?>>Specific Month</option>
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

                <!-- Search Section -->
                <div class="form-section">
                    <h4>Search Options</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="guest_search">Guest Search</label>
                                <input type="text" name="guest_search" id="guest_search" class="form-control"
                                       value="<?= htmlspecialchars($guest_search) ?>" 
                                       placeholder="Search by Guest Code ID or Name">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="index.php?page=guest_tracking" class="btn btn-danger">Reset to Latest</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons mb-6 flex justify-between">
        <div>
            <?php if ($can_edit): ?>
                <a href="index.php?page=guest_tracking&action=upload" class="btn btn-primary">Upload Excel Data</a>
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

    <!-- Current View Info -->
    <div class="alert alert-info mb-4">
        <strong>📊 Current View:</strong> Showing data for <?= htmlspecialchars($filter_description) ?>
        <?php if (!empty($guest_search)): ?>
            | Search: "<?= htmlspecialchars($guest_search) ?>"
        <?php endif; ?>
        <?php if ($date_range_type === 'latest_upload' && !empty($uploads)): ?>
            <br><small>Latest upload contains data from: <?= htmlspecialchars($uploads[0]['upload_filename'] ?? 'Unknown file') ?></small>
        <?php endif; ?>
    </div>

    <!-- Summary Stats -->
    <div class="stats-container grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card p-4 rounded bg-opacity-10 bg-primary-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total Guests</div>
            <div class="stat-value text-lg font-bold"><?php echo number_format($total_guests); ?></div>
        </div>
        <div class="stat-card in p-4 rounded bg-opacity-10 bg-success-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total Drop</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_drop); ?></div>
        </div>
        <div class="stat-card <?php echo $total_result >= 0 ? 'in' : 'out'; ?> p-4 rounded text-center bg-opacity-10 <?php echo $total_result >= 0 ? 'bg-success-color' : 'bg-danger-color'; ?>">
            <div class="stat-title uppercase text-sm text-muted">Total Result</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($total_result); ?></div>
        </div>
        <div class="stat-card p-4 rounded bg-opacity-10 bg-warning-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total Visits</div>
            <div class="stat-value text-lg font-bold"><?php echo number_format($total_visits); ?></div>
        </div>
    </div>

    <!-- Export Note -->
    <?php if (!empty($guests)): ?>
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

    <!-- Guests Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Guest Tracking Data</h3>
        </div>
        <div class="card-body p-6">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">
                                <a href="?page=guest_tracking&sort=guest_code_id&order=<?php echo $sort_column == 'guest_code_id' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Guest Code ID <?php if ($sort_column == 'guest_code_id') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="?page=guest_tracking&sort=guest_name&order=<?php echo $sort_column == 'guest_name' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Guest Name <?php if ($sort_column == 'guest_name') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="?page=guest_tracking&sort=total_drop&order=<?php echo $sort_column == 'total_drop' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Total Drop <?php if ($sort_column == 'total_drop') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="?page=guest_tracking&sort=total_result&order=<?php echo $sort_column == 'total_result' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Total Result <?php if ($sort_column == 'total_result') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">
                                <a href="?page=guest_tracking&sort=total_visits&order=<?php echo $sort_column == 'total_visits' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Total Visits <?php if ($sort_column == 'total_visits') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="?page=guest_tracking&sort=last_visit&order=<?php echo $sort_column == 'last_visit' ? $toggle_order : 'ASC'; ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => '', 'order' => ''])) ?>">
                                    Last Visit <?php if ($sort_column == 'last_visit') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($guests)): ?>
                            <tr>
                                <td colspan="7" class="text-center px-4 py-6">
                                    <?php if ($date_range_type === 'latest_upload'): ?>
                                        No guest data found in the latest upload
                                    <?php else: ?>
                                        No guest data found for the selected criteria
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($guests as $guest): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($guest['guest_code_id']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($guest['guest_name']); ?></td>
                                    <td class="px-4 py-2 text-right font-bold text-success-color"><?php echo format_currency($guest['total_drop']); ?></td>
                                    <td class="px-4 py-2 text-right font-bold <?php echo $guest['total_result'] >= 0 ? 'text-success-color' : 'text-danger-color'; ?>">
                                        <?php echo format_currency($guest['total_result']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-right"><?php echo number_format($guest['total_visits']); ?></td>
                                    <td class="px-4 py-2"><?php echo $guest['last_visit'] ? format_date($guest['last_visit']) : 'N/A'; ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="index.php?page=guest_tracking&action=view&guest_code_id=<?php echo urlencode($guest['guest_code_id']); ?>" 
                                           class="action-btn view-btn" data-tooltip="View Details">
                                            <span class="menu-icon"><img src="<?= icon('view2') ?>"/></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Upload History -->
    <?php if (!empty($uploads) && $can_edit): ?>
        <div class="card mt-6">
            <div class="card-header">
                <h3>Upload History</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Upload Date</th>
                                <th class="px-4 py-2 text-left">Filename</th>
                                <th class="px-4 py-2 text-center">Status</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uploads as $index => $upload): ?>
                                <tr>
                                    <td class="px-4 py-2"><?php echo format_date($upload['upload_date']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($upload['upload_filename']); ?></td>
                                    <td class="px-4 py-2 text-center">
                                        <?php if ($index === 0): ?>
                                            <span class="status status-active">Latest</span>
                                        <?php else: ?>
                                            <span class="status status-inactive">Historical</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="index.php?page=guest_tracking&action=delete_upload&upload_date=<?php echo urlencode($upload['upload_date']); ?>" 
                                           class="action-btn delete-btn" data-tooltip="Delete Upload" 
                                           data-confirm="Are you sure you want to delete this upload and all its data?">
                                            <span class="menu-icon"><img src="<?= icon('delete') ?>"/></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/guest_tracking_list.js"></script>
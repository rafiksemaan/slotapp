<?php
	// Start session early
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// Handle export requests
	if (isset($_GET['export'])) {
		$export_type = $_GET['export']; // 'pdf' or 'excel'
		
		// Get all the same parameters as the main report
		$date_range_type = $_GET['date_range_type'] ?? 'month';
		$date_from = $_GET['date_from'] ?? date('Y-m-01');
		$date_to = $_GET['date_to'] ?? date('Y-m-t');
		$month = $_GET['month'] ?? date('Y-m');
		$machine_id = $_GET['machine_id'] ?? 'all';
		$brand_id = $_GET['brand_id'] ?? 'all';
		$machine_group_id = $_GET['machine_group_id'] ?? 'all';
		$selected_columns = $_GET['columns'] ?? [];
		$sort_column = $_GET['sort'] ?? 'machine_number';
		$sort_order = $_GET['order'] ?? 'ASC';
		
		if (!is_array($selected_columns)) {
			$selected_columns = [];
		}
		
		// Include the export handler
		include 'custom_report/export.php';
		exit;
	}

	// Get sorting parameters
	$sort_column = $_GET['sort'] ?? 'machine_number';
	$sort_order = $_GET['order'] ?? 'ASC';

	// Validate sort column
	$allowed_columns = ['machine_number', 'brand_name', 'model', 'machine_type', 'credit_value', 'serial_number', 'manufacturing_year', 'total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'];
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

	// Get selected columns from URL - FIXED HANDLING
	$selected_columns = $_GET['columns'] ?? [];
	if (!is_array($selected_columns)) {
		$selected_columns = [];
	}

	// Define available columns
	$available_columns = [
		'machine_number' => 'Machine #',
		'brand_name' => 'Brand',
		'model' => 'Model',
		'machine_type' => 'Machine Type',
		'credit_value' => 'Credit Value',
		'serial_number' => 'Serial Number',
		'manufacturing_year' => 'Manufacturing Year',
		'total_handpay' => 'Total Handpay',
		'total_ticket' => 'Total Ticket',
		'total_refill' => 'Total Refill',
		'total_coins_drop' => 'Total Coins Drop',
		'total_cash_drop' => 'Total Cash Drop',
		'total_out' => 'Total OUT',
		'total_drop' => 'Total DROP',
		'result' => 'Result'
	];

	// Get transaction types for individual columns
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

	// Get machine groups for dropdown
	try {
		$groups_stmt = $conn->query("SELECT id, name FROM machine_groups ORDER BY name");
		$machine_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		$machine_groups = [];
	}

// Build SQL query
$results = [];
$error = '';
try {
    // Start building the query
    $select_parts = [];
    $join_parts = [];
    $group_by_parts = ['m.id'];
    
    // Always include machine ID and number
    $select_parts[] = "m.id AS machine_id";
    $select_parts[] = "m.machine_number";
    
    // Add selected columns
    if (in_array('brand_name', $selected_columns)) {
        $select_parts[] = "b.name AS brand_name";
        $join_parts[] = "LEFT JOIN brands b ON m.brand_id = b.id";
    }
    if (in_array('model', $selected_columns)) {
        $select_parts[] = "m.model";
    }
    if (in_array('machine_type', $selected_columns)) {
        $select_parts[] = "mt.name AS machine_type";
        $join_parts[] = "LEFT JOIN machine_types mt ON m.type_id = mt.id";
    }
    if (in_array('credit_value', $selected_columns)) {
        $select_parts[] = "m.credit_value";
    }
    if (in_array('serial_number', $selected_columns)) {
        $select_parts[] = "m.serial_number";
    }
    if (in_array('manufacturing_year', $selected_columns)) {
        $select_parts[] = "m.manufacturing_year";
    }

    // Always include transaction-related columns (for display and sorting)
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Handpay' THEN t.amount ELSE 0 END), 0) AS total_handpay";
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Ticket' THEN t.amount ELSE 0 END), 0) AS total_ticket";
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Refill' THEN t.amount ELSE 0 END), 0) AS total_refill";
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Coins Drop' THEN t.amount ELSE 0 END), 0) AS total_coins_drop";
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Cash Drop' THEN t.amount ELSE 0 END), 0) AS total_cash_drop";
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS total_out";
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) AS total_drop";
    $select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS result";

    // Join with transactions and transaction_types
    $join_parts[] = "LEFT JOIN transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?";
    $join_parts[] = "LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.id";

    // Build the complete query
    $query = "SELECT " . implode(", ", $select_parts);
    $query .= " FROM machines m";
    $query .= " " . implode(" ", array_unique($join_parts));
    $query .= " WHERE 1=1";

    // Initialize params array
    $params = [];
    // Add date filter
    $params[] = "{$start_date} 00:00:00";
    $params[] = "{$end_date} 23:59:59";

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

    // GROUP BY
    $query .= " GROUP BY " . implode(", ", $group_by_parts);

    // ORDER BY (ensure the column exists in SELECT)
    $order_column = $sort_column;

    switch ($sort_column) {
        case 'brand_name':
            $order_column = 'b.name';
            break;
        case 'machine_type':
            $order_column = 'mt.name';
            break;
        case 'machine_number':
        case 'model':
        case 'credit_value':
        case 'serial_number':
        case 'manufacturing_year':
            $order_column = "m.$sort_column";
            break;
        default:
            // Use computed columns directly
            if (in_array($sort_column, ['total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'])) {
                $order_column = $sort_column;
            } else {
                $order_column = 'm.machine_number';
            }
    }

    $query .= " ORDER BY $order_column $sort_order";

    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $results = [];
    $error = "Database error: " . $e->getMessage();
}

	// Calculate totals for selected columns (excluding credit_value)
	$totals = [];
	if (!empty($results) && !empty($selected_columns)) {
		$monetary_columns = ['total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'];
		
		foreach ($selected_columns as $column) {
			// Skip credit_value from totals calculation
			if (in_array($column, $monetary_columns) && $column !== 'credit_value') {
				$total = 0;
				foreach ($results as $row) {
					$total += (float)($row[$column] ?? 0);
				}
				$totals[$column] = $total;
			}
		}
	}

	// Function to build sort URL with all current parameters - FIXED VERSION
	function buildSortUrl($column, $order, $current_params) {
		$url = 'index.php?page=custom_report';
		$url .= '&sort=' . urlencode($column);
		$url .= '&order=' . urlencode($order);
		$url .= '&date_range_type=' . urlencode($current_params['date_range_type']);
		$url .= '&machine_id=' . urlencode($current_params['machine_id']);
		$url .= '&brand_id=' . urlencode($current_params['brand_id']);
		$url .= '&machine_group_id=' . urlencode($current_params['machine_group_id']);

		// Add date range parameters
		if ($current_params['date_range_type'] === 'range') {
			$url .= '&date_from=' . urlencode($current_params['date_from']);
			$url .= '&date_to=' . urlencode($current_params['date_to']);
		} else {
			$url .= '&month=' . urlencode($current_params['month']);
		}

		// Add selected columns - FIXED: Use proper array syntax
		if (!empty($current_params['selected_columns'])) {
			foreach ($current_params['selected_columns'] as $col) {
				$url .= '&columns[]=' . urlencode($col);
			}
		}

		return $url;
	}

	// Current parameters for URL building
	$current_params = [
		'date_range_type' => $date_range_type,
		'date_from' => $date_from,
		'date_to' => $date_to,
		'month' => $month,
		'machine_id' => $machine_id,
		'brand_id' => $brand_id,
		'machine_group_id' => $machine_group_id,
		'selected_columns' => $selected_columns
	];

	// Build export URLs
	$export_base_params = $current_params;
	$export_base_params['sort'] = $sort_column;
	$export_base_params['order'] = $sort_order;

	// Build export URLs manually to ensure proper array handling
	$export_params = [
		'page' => 'custom_report',
		'sort' => $sort_column,
		'order' => $sort_order,
		'date_range_type' => $date_range_type,
		'machine_id' => $machine_id,
		'brand_id' => $brand_id,
		'machine_group_id' => $machine_group_id
	];

	if ($date_range_type === 'range') {
		$export_params['date_from'] = $date_from;
		$export_params['date_to'] = $date_to;
	} else {
		$export_params['month'] = $month;
	}

	$export_url_base = 'index.php?' . http_build_query($export_params);
	
	// Add columns to export URLs
	$columns_query = '';
	foreach ($selected_columns as $col) {
		$columns_query .= '&columns[]=' . urlencode($col);
	}

	$pdf_export_url = $export_url_base . $columns_query . '&export=pdf';
	$excel_export_url = $export_url_base . $columns_query . '&export=excel';

	// Check if we have filter parameters (indicating a report was generated)
	$has_filters = !empty($selected_columns) || $machine_id !== 'all' || $brand_id !== 'all' || $machine_group_id !== 'all' || $date_range_type !== 'month' || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['month']);
	?>

	<div class="custom-report-page fade-in">
		<!-- Collapsible Filters -->
		<div class="filters-container card mb-6">
			<div class="card-header" style="cursor: pointer;" onclick="toggleFilters()">
				<div style="display: flex; justify-content: space-between; align-items: center;">
					<h4 style="margin: 0;">Report Configuration</h4>
					<span id="filter-toggle-icon" class="filter-toggle-icon">
						<?php echo $has_filters ? '▼' : '▲'; ?>
					</span>
				</div>
			</div>
			<div class="card-body" id="filters-body" style="<?php echo $has_filters ? 'display: none;' : ''; ?>">
				<form action="index.php" method="GET">
					<input type="hidden" name="page" value="custom_report">

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

					<!-- Machine/Brand Selection -->
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

					<!-- Column Selection -->
					<div class="form-section">
						<h4>Report Columns</h4>
						<div class="checkbox-grid">
							<div class="checkbox-section">
								<h5>Machine Details</h5>
								<?php 
								$machine_columns = ['machine_number', 'brand_name', 'model', 'machine_type', 'credit_value', 'serial_number', 'manufacturing_year'];
								foreach ($machine_columns as $key): 
									if (isset($available_columns[$key])):
								?>
									<label class="checkbox-label">
										<input type="checkbox" name="columns[]" value="<?= $key ?>" 
											   <?= in_array($key, $selected_columns) ? 'checked' : '' ?>>
										<?= $available_columns[$key] ?>
									</label>
								<?php 
									endif;
								endforeach; 
								?>
							</div>
							
							<div class="checkbox-section">
								<h5>Transaction Details</h5>
								<?php 
								$transaction_columns = ['total_coins_drop', 'total_cash_drop', 'total_handpay', 'total_ticket', 'total_refill'];
								foreach ($transaction_columns as $key): 
									if (isset($available_columns[$key])):
								?>
									<label class="checkbox-label">
										<input type="checkbox" name="columns[]" value="<?= $key ?>" 
											   <?= in_array($key, $selected_columns) ? 'checked' : '' ?>>
										<?= $available_columns[$key] ?>
									</label>
								<?php 
									endif;
								endforeach; 
								?>
							</div>
							
							<div class="checkbox-section">
								<h5>Summary Totals</h5>
								<?php 
								$summary_columns = ['total_drop', 'total_out', 'result'];
								foreach ($summary_columns as $key): 
									if (isset($available_columns[$key])):
								?>
									<label class="checkbox-label">
										<input type="checkbox" name="columns[]" value="<?= $key ?>" 
											   <?= in_array($key, $selected_columns) ? 'checked' : '' ?>>
										<?= $available_columns[$key] ?>
									</label>
								<?php 
									endif;
								endforeach; 
								?>
							</div>
						</div>
					</div>

					<!-- Submit Buttons -->
					<div class="form-actions">
						<button type="submit" class="btn btn-primary">Generate</button>
						<a href="index.php?page=custom_report" class="btn btn-danger">Reset</a>
					</div>
				</form>
			</div>
		</div>

		<?php if (!empty($selected_columns)): ?>
			<!-- Report Header -->
			<div class="report-header">
				<h3>Custom Report</h3>
				<p class="date-range">
					<?php
					// Show selection details
					if ($machine_id !== 'all') {
						$selected_machine = null;
						foreach ($machines as $m) {
							if ($m['id'] == $machine_id) {
								$selected_machine = $m;
								break;
							}
						}
						echo "Machine #" . htmlspecialchars($selected_machine['machine_number'] ?? 'N/A');
					} elseif ($machine_group_id !== 'all') {
						$selected_group = null;
						foreach ($machine_groups as $g) {
							if ($g['id'] == $machine_group_id) {
								$selected_group = $g;
								break;
							}
						}
						echo "Group: " . htmlspecialchars($selected_group['name'] ?? 'N/A');
					} elseif ($brand_id !== 'all') {
						$selected_brand = null;
						foreach ($brands as $b) {
							if ($b['id'] == $brand_id) {
								$selected_brand = $b;
								break;
							}
						}
						echo "Brand: " . htmlspecialchars($selected_brand['name'] ?? 'N/A');
					} else {
						echo "All Machines";
					}
					?>
					|
					<?php
					if ($date_range_type === 'range') {
						echo htmlspecialchars(date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to)));
					} else {
						echo htmlspecialchars(date('F Y', strtotime($month)));
					}
					?>
				</p>
				<p class="generated-at">
					Generated at: <?= cairo_time('d M Y – H:i:s') ?>
				</p>
			</div>

			<!-- Export Buttons -->
			<?php if (!empty($results) && !empty($error) === false): ?>
				<div class="export-actions">
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

			<!-- Results Table -->
			<?php if (!empty($error)): ?>
				<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
			<?php elseif (!empty($results)): ?>
				<div class="card">
					<div class="card-header">
						<h3>Report Results</h3>
					</div>
					<div class="card-body">
						<div class="table-container">
							<table class="min-w-full divide-y divide-gray-700 separated-columns">
								<thead>
									<tr>
										<?php foreach ($selected_columns as $column): ?>
											<?php if (isset($available_columns[$column])): ?>
												<th>
													<a href="<?= htmlspecialchars(buildSortUrl($column, ($sort_column === $column ? $toggle_order : 'ASC'), $current_params)) ?>">
														<?= $available_columns[$column] ?>
														<?php if ($sort_column === $column): ?>
															<?= $sort_order === 'ASC' ? '▲' : '▼' ?>
														<?php endif; ?>
													</a>
												</th>
											<?php endif; ?>
										<?php endforeach; ?>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($results as $row): ?>
										<tr>
											<?php foreach ($selected_columns as $column): ?>
												<td<?php 
													// Apply special classes for specific columns
													if ($column === 'result') {
														$result_value = (float)($row[$column] ?? 0);
														echo ' class="highlight-result ' . ($result_value >= 0 ? 'positive' : 'negative') . '"';
													} elseif ($column === 'total_out') {
														echo ' class="highlight-out-table"';
													} elseif ($column === 'total_drop') {
														echo ' class="highlight-drop-table"';
													}
												?>>
													<?php
													$value = $row[$column] ?? 'N/A';
													
													// Format specific columns
													if (in_array($column, ['credit_value', 'total_coins_drop', 'total_cash_drop', 'total_handpay', 'total_ticket', 'total_refill', 'total_drop', 'total_out', 'result'])) {
														echo format_currency($value);
													} else {
														echo htmlspecialchars($value);
													}
													?>
												</td>
											<?php endforeach; ?>
										</tr>
									<?php endforeach; ?>
									
									<!-- Totals Row (excluding credit_value) -->
									<?php if (!empty($totals)): ?>
										<tr class="totals-row bg-gray-800 text-white font-bold">
											<?php foreach ($selected_columns as $column): ?>
												<td<?php 
													// Apply special classes for totals row
													if ($column === 'result' && isset($totals[$column])) {
														$result_value = (float)$totals[$column];
														echo ' class="highlight-result ' . ($result_value >= 0 ? 'positive' : 'negative') . '"';
													} elseif ($column === 'total_out' && isset($totals[$column])) {
														echo ' class="highlight-out-table"';
													} elseif ($column === 'total_drop' && isset($totals[$column])) {
														echo ' class="highlight-drop-table"';
													}
												?>>
													<?php if ($column === 'machine_number'): ?>
														<strong>TOTALS</strong>
													<?php elseif ($column === 'credit_value'): ?>
														<!-- Skip credit value in totals - show dash -->
														<strong>-</strong>
													<?php elseif (isset($totals[$column])): ?>
														<strong><?= format_currency($totals[$column]) ?></strong>
													<?php else: ?>
														<!-- Empty cell for non-monetary columns -->
													<?php endif; ?>
												</td>
											<?php endforeach; ?>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php else: ?>
				<div class="alert alert-warning">No data found for the selected criteria.</div>
			<?php endif; ?>
		<?php else: ?>
			<div class="alert alert-info">Please select at least one column to display in the report.</div>
		<?php endif; ?>
	</div>

	<!-- JavaScript for form interactions and toggle filters -->
	<script src="assets/js/custom_report_filters.js"></script>
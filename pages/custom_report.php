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

	// Calculate start/end dates
	if ($date_range_type === 'range') {
		$start_date = $date_from;
		$end_date = $date_to;
	} else {
		list($year, $month_num) = explode('-', $month);
		$start_date = "$year-$month_num-01";
		$end_date = date("Y-m-t", strtotime($start_date));
	}

	// Get selected columns from URL
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
		
		// Add transaction-related columns
		$has_transactions = false;
		
		if (in_array('total_handpay', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Handpay' THEN t.amount ELSE 0 END), 0) AS total_handpay";
			$has_transactions = true;
		}
		
		if (in_array('total_ticket', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Ticket' THEN t.amount ELSE 0 END), 0) AS total_ticket";
			$has_transactions = true;
		}
		
		if (in_array('total_refill', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Refill' THEN t.amount ELSE 0 END), 0) AS total_refill";
			$has_transactions = true;
		}
		
		if (in_array('total_coins_drop', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Coins Drop' THEN t.amount ELSE 0 END), 0) AS total_coins_drop";
			$has_transactions = true;
		}
		
		if (in_array('total_cash_drop', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.name = 'Cash Drop' THEN t.amount ELSE 0 END), 0) AS total_cash_drop";
			$has_transactions = true;
		}
		
		if (in_array('total_out', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS total_out";
			$has_transactions = true;
		}
		
		if (in_array('total_drop', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) AS total_drop";
			$has_transactions = true;
		}
		
		if (in_array('result', $selected_columns)) {
			$select_parts[] = "COALESCE(SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END), 0) AS result";
			$has_transactions = true;
		}
		
		// Add transaction joins if needed
		if ($has_transactions) {
			$join_parts[] = "LEFT JOIN transactions t ON m.id = t.machine_id AND t.timestamp BETWEEN ? AND ?";
			$join_parts[] = "LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.id";
		}
		
		// Build the complete query
		$query = "SELECT " . implode(", ", $select_parts);
		$query .= " FROM machines m";
		$query .= " " . implode(" ", array_unique($join_parts));
		$query .= " WHERE 1=1";
		
		// Initialize params array
		$params = [];
		
		// Add date filter if transactions are involved
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
			$query .= " AND m.brand_id = ?";
			$params[] = $brand_id;
		}
		
		// Add GROUP BY
		$query .= " GROUP BY " . implode(", ", $group_by_parts);
		
		// Add ORDER BY
		$query .= " ORDER BY `$sort_column` $sort_order";
		
		// Execute query
		$stmt = $conn->prepare($query);
		$stmt->execute($params);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

	} catch (PDOException $e) {
		$results = [];
		$error = "Database error: " . $e->getMessage();
	}

	// Calculate totals for selected columns
	$totals = [];
	if (!empty($results) && !empty($selected_columns)) {
		$monetary_columns = ['credit_value', 'total_handpay', 'total_ticket', 'total_refill', 'total_coins_drop', 'total_cash_drop', 'total_out', 'total_drop', 'result'];
		
		foreach ($selected_columns as $column) {
			if (in_array($column, $monetary_columns)) {
				$total = 0;
				foreach ($results as $row) {
					$total += (float)($row[$column] ?? 0);
				}
				$totals[$column] = $total;
			}
		}
	}

	// Build base URL with current filters
	$base_url = "index.php?page=custom_report";
	$filter_params = [
		'date_range_type' => $date_range_type,
		'machine_id' => $machine_id,
		'brand_id' => $brand_id
	];

	if ($date_range_type === 'range') {
		$filter_params['date_from'] = $date_from;
		$filter_params['date_to'] = $date_to;
	} else {
		$filter_params['month'] = $month;
	}

	foreach ($selected_columns as $col) {
		$filter_params['columns[]'] = $col;
	}

	$query_string = http_build_query($filter_params);

	// Build export URLs
	$export_base_params = $filter_params;
	$export_base_params['sort'] = $sort_column;
	$export_base_params['order'] = $sort_order;

	$pdf_export_url = $base_url . '&' . http_build_query($export_base_params) . '&export=pdf';
	$excel_export_url = $base_url . '&' . http_build_query($export_base_params) . '&export=excel';
	?>

	<div class="custom-report-page fade-in">
		<!-- Filters -->
		<div class="filters-container card mb-6">
			<div class="card-header">
				<h3>Report Configuration</h3>
			</div>
			<div class="card-body">
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
						<button type="submit" class="btn btn-primary">Generate Report</button>
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
													<a href="<?= $base_url ?>&<?= $query_string ?>&sort=<?= $column ?>&order=<?= $sort_column === $column ? $toggle_order : 'ASC' ?>">
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
												<td>
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
									
									<!-- Totals Row -->
									<?php if (!empty($totals)): ?>
										<tr class="totals-row bg-gray-800 text-white font-bold">
											<?php foreach ($selected_columns as $column): ?>
												<td>
													<?php if ($column === 'machine_number'): ?>
														<strong>TOTALS</strong>
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

	<!-- JavaScript for form interactions -->
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const dateRangeType = document.getElementById('date_range_type');
		const dateFrom = document.getElementById('date_from');
		const dateTo = document.getElementById('date_to');
		const monthSelect = document.getElementById('month');

		function toggleDateInputs() {
			const isRange = dateRangeType.value === 'range';
			
			dateFrom.disabled = !isRange;
			dateTo.disabled = !isRange;
			monthSelect.disabled = isRange;
		}

		dateRangeType.addEventListener('change', toggleDateInputs);
		toggleDateInputs(); // Initial call
	});
	</script>

	<style>
	.form-section {
		margin-bottom: 2rem;
		padding: 1rem;
		border: 1px solid var(--border-color);
		border-radius: var(--border-radius);
	}

	.form-section h4 {
		margin-bottom: 1rem;
		color: var(--secondary-color);
		border-bottom: 1px solid var(--border-color);
		padding-bottom: 0.5rem;
	}

	.form-section h5 {
		margin-bottom: 0.5rem;
		color: var(--text-light);
		font-size: 0.9rem;
	}

	.checkbox-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 1rem;
	}

	.checkbox-section {
		padding: 1rem;
		background-color: rgba(255, 255, 255, 0.05);
		border-radius: var(--border-radius);
	}

	.checkbox-label {
		display: block;
		margin-bottom: 0.5rem;
		cursor: pointer;
		padding: 0.25rem;
		border-radius: 3px;
		transition: background-color 0.2s;
	}

	.checkbox-label:hover {
		background-color: rgba(255, 255, 255, 0.1);
	}

	.checkbox-label input[type="checkbox"] {
		margin-right: 0.5rem;
	}

	.form-actions {
		margin-top: 2rem;
		padding-top: 1rem;
		border-top: 1px solid var(--border-color);
		display: flex;
		gap: 1rem;
	}

	.export-actions {
		margin-bottom: 2rem;
	}

	.export-buttons {
		display: flex;
		gap: 1rem;
		margin-bottom: 1rem;
	}

	.export-note {
		margin: 0;
		color: var(--text-muted);
	}

	@media (max-width: 768px) {
		.checkbox-grid {
			grid-template-columns: 1fr;
		}
		
		.form-actions,
		.export-buttons {
			flex-direction: column;
		}
	}
	
	/* Add column separator */
	.separated-columns td,
	.separated-columns th {
		border-right: 1px solid #374151; /* Tailwind's gray-700 */
		padding-right: 1rem;
	}

	.separated-columns td:last-child,
	.separated-columns th:last-child {
		border-right: none;
		padding-right: 0.75rem;
	}

	/* Optional: Highlight entire column on hover */
	.separated-columns tr:hover td {
		background-color: rgba(255, 255, 255, 0.05);
	}

	/* Totals row styling */
	.totals-row {
		background-color: var(--primary-color) !important;
		color: var(--text-light) !important;
		font-weight: bold;
	}

	.totals-row td {
		border-top: 2px solid var(--secondary-color);
		padding: 0.75rem;
	}

	/* Add space between column groups */
	.machine-column { max-width: 10px; }
	.transaction-column { max-width: 15px; }
	</style>
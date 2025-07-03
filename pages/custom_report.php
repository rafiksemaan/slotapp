<div class="custom-report-page fade-in">
    <!-- Collapsible Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header">
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

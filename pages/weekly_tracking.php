<?php
/**
 * Weekly Tracking List Page
 * Shows aggregated weekly tracking data from daily entries
 */

// Get filter values
$filter_year = $_GET['year'] ?? date('Y');

// Validate year
if (!is_numeric($filter_year) || $filter_year < 2000 || $filter_year > 2100) {
    $filter_year = date('Y');
}
$filter_week_number = $_GET['week_number'] ?? ''; // This line should be outside the if block


// Get all daily tracking data for the selected year
$daily_data = [];
try {
    $query = "SELECT * FROM daily_tracking WHERE YEAR(tracking_date) = ?";
    $params = [(int)$filter_year]; // Cast year to int for safety

    if (!empty($filter_week_number)) {
        // Calculate the start and end dates of the selected ISO week
        $dt_start = new DateTime();
        $dt_start->setISODate((int)$filter_year, (int)$filter_week_number, 1); // Monday of the ISO week
        $start_of_week = $dt_start->format('Y-m-d');

        $dt_end = new DateTime();
        $dt_end->setISODate((int)$filter_year, (int)$filter_week_number, 7); // Sunday of the ISO week
        $end_of_week = $dt_end->format('Y-m-d');

        $query .= " AND tracking_date BETWEEN ? AND ?";
        $params[] = $start_of_week;
        $params[] = $end_of_week;
    }

    $query .= " ORDER BY tracking_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $daily_data = [];
}





$weekly_data = [];
$grand_total_drop = 0;
$grand_total_out = 0;
$grand_total_result = 0;

foreach ($daily_data as $day_entry) {
    $week_info = get_monday_sunday_week_info($day_entry['tracking_date']);
    $week_key = $week_info['week_label']; // Use week label as key for aggregation

    if (!isset($weekly_data[$week_key])) {
        $weekly_data[$week_key] = [
            'week_label' => $week_info['week_label'],
            'start_date' => $week_info['start_date'],
            'end_date' => $week_info['end_date'],
            'is_cross_year' => $week_info['is_cross_year'], // Capture the new flag here
            'slots_drop' => 0,
            'slots_out' => 0,
            'gambee_drop' => 0,
            'gambee_out' => 0,
            'coins_drop' => 0,
            'coins_out' => 0,
            'total_drop' => 0,
            'total_out' => 0,
            'total_result' => 0,
            'total_result_percentage' => 0,
        ];
    }

    $weekly_data[$week_key]['slots_drop'] += (float)$day_entry['slots_drop'];
    $weekly_data[$week_key]['slots_out'] += (float)$day_entry['slots_out'];
    $weekly_data[$week_key]['gambee_drop'] += (float)$day_entry['gambee_drop'];
    $weekly_data[$week_key]['gambee_out'] += (float)$day_entry['gambee_out'];
    $weekly_data[$week_key]['coins_drop'] += (float)$day_entry['coins_drop'];
    $weekly_data[$week_key]['coins_out'] += (float)$day_entry['coins_out'];
}



// Calculate results and percentages for each week
foreach ($weekly_data as $week_key => &$week_entry) {
	
	// Assuming $week_entry is being populated with weekly data
$week_entry['week_number'] = date('W', strtotime($week_entry['start_date']));

    $week_entry['slots_result'] = $week_entry['slots_drop'] - $week_entry['slots_out'];
    $week_entry['slots_percentage'] = $week_entry['slots_drop'] > 0 ? (($week_entry['slots_result'] / $week_entry['slots_drop']) * 100) : 0;

    $week_entry['gambee_result'] = $week_entry['gambee_drop'] - $week_entry['gambee_out'];
    $week_entry['gambee_percentage'] = $week_entry['gambee_drop'] > 0 ? (($week_entry['gambee_result'] / $week_entry['gambee_drop']) * 100) : 0;

    $week_entry['coins_result'] = $week_entry['coins_drop'] - $week_entry['coins_out'];
    $week_entry['coins_percentage'] = $week_entry['coins_drop'] > 0 ? (($week_entry['coins_result'] / $week_entry['coins_drop']) * 100) : 0;

    $week_entry['total_drop'] = $week_entry['slots_drop'] + $week_entry['gambee_drop'] + $week_entry['coins_drop'];
    $week_entry['total_out'] = $week_entry['slots_out'] + $week_entry['gambee_out'] + $week_entry['coins_out'];
    $week_entry['total_result'] = $week_entry['total_drop'] - $week_entry['total_out'];
    $week_entry['total_result_percentage'] = $week_entry['total_drop'] > 0 ? (($week_entry['total_result'] / $week_entry['total_drop']) * 100) : 0;

    // Accumulate grand totals
    $grand_total_drop += $week_entry['total_drop'];
    $grand_total_out += $week_entry['total_out'];
    $grand_total_result += $week_entry['total_result'];
}
unset($week_entry); // Break the reference

// Sort weekly data by week label (which includes year and week number)
ksort($weekly_data);

// Get available years from daily_tracking data
$available_years = [];
try {
    $years_stmt = $conn->query("SELECT DISTINCT YEAR(tracking_date) as year FROM daily_tracking WHERE YEAR(tracking_date) IS NOT NULL AND YEAR(tracking_date) > 0 ORDER BY year DESC");
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Handle error silently
}
if (empty($available_years)) {
    $available_years[] = date('Y'); // Add current year if no data
}

// Calculate grand total percentage
$grand_total_percentage = $grand_total_drop > 0 ? (($grand_total_result / $grand_total_drop) * 100) : 0;

// --- NEW CODE START ---
// Get current ISO week and year
$current_iso_week = date('W');
$current_iso_year = date('Y');
// --- NEW CODE END ---

?>

<div class="weekly-tracking-page fade-in">
    <!-- Filters -->
    <div class="filters-container card mb-6">
        <div class="card-header">
            <h4 style="margin: 0;">Filter by Year</h4>
        </div>
        <div class="card-body">
            <form action="index.php" method="GET">
                <input type="hidden" name="page" value="weekly_tracking">
               <div class="form-group">
    <label for="year">Select Year</label>
    <select name="year" id="year" class="form-control">
        <?php foreach ($available_years as $year_option): ?>
            <option value="<?= $year_option ?>" <?= $filter_year == $year_option ? 'selected' : '' ?>>
                <?= $year_option ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-group">
    <label for="week_number">Select Week Number</label>
    <select name="week_number" id="week_number" class="form-control">
        <option value="">All Weeks</option>
        <?php for ($i = 1; $i <= 53; $i++): ?>
            <option value="<?= $i ?>" <?= (isset($_GET['week_number']) && $_GET['week_number'] == $i) ? 'selected' : '' ?>>
                Week <?= $i ?>
            </option>
        <?php endfor; ?>
    </select>
</div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    <a href="index.php?page=weekly_tracking" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Header -->
    <div class="report-header text-center py-4 px-6 rounded-lg bg-gradient-to-r from-gray-800 to-black shadow-md mb-6">
        <h3 class="text-xl font-bold text-secondary-color">Weekly Tracking Report for <?= htmlspecialchars($filter_year) ?></h3>
        <p class="generated-at text-sm italic text-gray-400">
            Generated at: <?= cairo_time('d M Y – H:i:s') ?>
        </p>
    </div>

    <!-- Summary Stats -->
    <div class="stats-container grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card in p-4 rounded bg-opacity-10 bg-success-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total DROP (Year)</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_drop); ?></div>
        </div>
        <div class="stat-card out p-4 rounded bg-opacity-10 bg-danger-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Total OUT (Year)</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_out); ?></div>
        </div>
        <div class="stat-card <?php echo $grand_total_result >= 0 ? 'in' : 'out'; ?> p-4 rounded text-center bg-opacity-10 <?php echo $grand_total_result >= 0 ? 'bg-success-color' : 'bg-danger-color'; ?>">
            <div class="stat-title uppercase text-sm text-muted">Total Result (Year)</div>
            <div class="stat-value text-lg font-bold"><?php echo format_currency($grand_total_result); ?></div>
        </div>
        <div class="stat-card p-4 rounded bg-opacity-10 bg-warning-color text-center">
            <div class="stat-title uppercase text-sm text-muted">Result % (Year)</div>
            <div class="stat-value text-lg font-bold"><?php echo number_format($grand_total_percentage, 2); ?>%</div>
        </div>
    </div>

    <!-- Weekly Tracking Table -->
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Weekly Tracking Data</h3>
        </div>
        <div class="card-body p-6">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700 separated-columns">
                    <thead class="bg-gray-800 text-white">
    <tr>
        <th class="px-4 py-2 text-left">Week</th>
        <th class="px-4 py-2 text-left">Start Date</th>
        <th class="px-4 py-2 text-left category-section-border-right">End Date</th> <!-- Added class -->
        <!-- Slots -->
        <th class="px-4 py-2 text-right">Slots Drop</th>
        <th class="px-4 py-2 text-right">Slots Result</th>
        <th class="px-4 py-2 text-right category-section-border-right">Slots %</th> <!-- Added class -->
        <!-- Gambee -->
        <th class="px-4 py-2 text-right">Gambee Drop</th>
        <th class="px-4 py-2 text-right">Gambee Result</th>
        <th class="px-4 py-2 text-right category-section-border-right">Gambee %</th> <!-- Added class -->
        <!-- Coins -->
        <th class="px-4 py-2 text-right">Coins Drop</th>
        <th class="px-4 py-2 text-right">Coins Result</th>
        <th class="px-4 py-2 text-right category-section-border-right">Coins %</th> <!-- Added class -->
        <!-- Totals -->
        <th class="px-4 py-2 text-right highlight-drop">Total Drop</th>
        <th class="px-4 py-2 text-right highlight-out">Total Out</th>
        <th class="px-4 py-2 text-right highlight-result">Total Result</th>
        <th class="px-4 py-2 text-right">Total %</th>
    </tr>
</thead>

                    <tbody class="divide-y divide-gray-700">
    <?php if (empty($weekly_data)): ?>
        <tr>
            <td colspan="16" class="text-center px-4 py-6">No weekly tracking data found for <?= htmlspecialchars($filter_year) ?></td>
        </tr>
    <?php else: ?>
        <?php foreach ($weekly_data as $week_entry): ?>
            <tr class="hover:bg-gray-800 transition duration-150">
                <td class="px-4 py-2 font-medium<?php echo $week_entry['is_cross_year'] ? ' cross-year-week' : ''; ?>">
                    <?php echo htmlspecialchars($week_entry['week_number']); ?>
                </td>
                <td class="px-4 py-2"><?php echo format_date($week_entry['start_date']); ?></td>
                <td class="px-4 py-2 category-section-border-right"><?php echo format_date($week_entry['end_date']); ?></td> <!-- Added class -->
                
                <!-- Slots -->
                <td class="px-4 py-2 text-right"><?php echo format_currency($week_entry['slots_drop']); ?></td>
                <td class="px-4 py-2 text-right <?php echo $week_entry['slots_result'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo format_currency($week_entry['slots_result']); ?></td>
                <td class="px-4 py-2 text-right category-section-border-right"><?php echo number_format($week_entry['slots_percentage'], 2); ?>%</td> <!-- Added class -->
                
                <!-- Gambee -->
                <td class="px-4 py-2 text-right"><?php echo format_currency($week_entry['gambee_drop']); ?></td>
                <td class="px-4 py-2 text-right <?php echo $week_entry['gambee_result'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo format_currency($week_entry['gambee_result']); ?></td>
                <td class="px-4 py-2 text-right category-section-border-right"><?php echo number_format($week_entry['gambee_percentage'], 2); ?>%</td> <!-- Added class -->
                
                <!-- Coins -->
                <td class="px-4 py-2 text-right"><?php echo format_currency($week_entry['coins_drop']); ?></td>
                <td class="px-4 py-2 text-right <?php echo $week_entry['coins_result'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo format_currency($week_entry['coins_result']); ?></td>
                <td class="px-4 py-2 text-right category-section-border-right"><strong><?php echo number_format($week_entry['coins_percentage'], 2); ?>%</strong></td> <!-- Added class -->
                
                <!-- Totals -->
                <td class="px-4 py-2 text-right highlight-drop"><strong><?php echo format_currency($week_entry['total_drop']); ?></strong></td>
                <td class="px-4 py-2 text-right highlight-out"><strong><?php echo format_currency($week_entry['total_out']); ?></strong></td>
                <td class="px-4 py-2 text-right highlight-result <?php echo $week_entry['total_result'] >= 0 ? 'positive' : 'negative'; ?>"><strong><?php echo format_currency($week_entry['total_result']); ?></strong></td>
                <td class="px-4 py-2 text-right"><strong><?php echo number_format($week_entry['total_result_percentage'], 2); ?>%</strong></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>

                </table>
            </div>
        </div>
    </div>
</div>


<?php
/**
 * Meters List Page
 * Shows meter entries with filtering and sorting options
 */

// Get sorting parameters
$sort_column = $_GET['sort'] ?? 'machine_number'; // Changed default sort column
$sort_order = $_GET['order'] ?? 'ASC'; // Changed default sort order
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Set a wide date range to effectively show all entries if no specific date filter is applied
// This ensures all machines are listed as requested, without date filtering.
$start_date = '1900-01-01'; // Very old date
$end_date = '2100-12-31';   // Very future date

// Build query for fetching meter data with LAG for previous readings
$query = "
    SELECT
        me.*,
        m.machine_number,
        m.credit_value,
        mt.name AS machine_type_name,
        u.username AS created_by_username,
        LAG(me.bills_in, 1, NULL) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_bills_in,
        LAG(me.handpay, 1, NULL) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_handpay,
        LAG(me.coins_drop, 1, NULL) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_coins_drop
    FROM meters me
    JOIN machines m ON me.machine_id = m.id
    LEFT JOIN machine_types mt ON m.type_id = mt.id
    LEFT JOIN users u ON me.created_by = u.id
    WHERE 1=1
";

$params = []; // No initial parameters as date filtering is removed from query

// Add sorting
$sort_map = [
    'operation_date' => 'me.operation_date',
    'machine_number' => 'CAST(m.machine_number AS UNSIGNED)', // Sort numerically
    'meter_type' => 'me.meter_type',
    'total_in' => 'me.total_in',
    'total_out' => 'me.total_out',
    'bills_in' => 'me.bills_in',
    'coins_in' => 'me.coins_in',
    'coins_out' => 'me.coins_out',
    'coins_drop' => 'me.coins_drop',
    'bets' => 'me.bets',
    'handpay' => 'me.handpay',
    'jp' => 'me.jp',
    'created_by_username' => 'u.username'
];

$actual_sort_column = $sort_map[$sort_column] ?? 'me.machine_number'; // Default to machine_number
$query .= " ORDER BY $actual_sort_column $sort_order";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $meters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch transaction sums for anomaly calculation
    $transaction_sums_query = "
        SELECT
            t.machine_id,
            t.operation_date,
            SUM(CASE WHEN tt.name = 'Cash Drop' THEN t.amount ELSE 0 END) AS cash_drop_sum,
            SUM(CASE WHEN tt.name = 'Coins Drop' THEN t.amount ELSE 0 END) AS coins_drop_sum,
            SUM(CASE WHEN tt.name = 'Handpay' THEN t.amount ELSE 0 END) AS handpay_sum
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE t.operation_date BETWEEN ? AND ?
    ";
    // Use the wide date range for transaction sums as well
    $transaction_sums_params = [$start_date, $end_date];
    
    $transaction_sums_query .= " GROUP BY t.machine_id, t.operation_date";

    $sums_stmt = $conn->prepare($transaction_sums_query);
    $sums_stmt->execute($transaction_sums_params);
    $transaction_sums = [];
    foreach ($sums_stmt->fetchAll(PDO::FETCH_ASSOC) as $sum_row) {
        $transaction_sums[$sum_row['machine_id'] . '_' . $sum_row['operation_date']] = $sum_row;
    }

    // Process meters data to calculate variance and anomaly
    foreach ($meters as &$meter) {
        $credit_value = (float)$meter['credit_value'];
        $operation_date = $meter['operation_date'];
        $machine_id = $meter['machine_id'];

        // Initialize variance and anomaly fields
        $meter['bills_in_variance'] = '---';
        $meter['handpay_variance'] = '---';
        $meter['coins_drop_variance'] = '---';
        $meter['bills_in_anomaly'] = '---';
        $meter['handpay_anomaly'] = '---';
        $meter['coins_drop_anomaly'] = '---';

        // Calculate Variance
        if (!$meter['is_initial_reading']) { // Only calculate if not an initial reading
            if ($meter['bills_in'] !== null && $meter['prev_bills_in'] !== null) {
                $meter['bills_in_variance'] = ($meter['bills_in'] - $meter['prev_bills_in']);
            }
            if ($meter['handpay'] !== null && $meter['prev_handpay'] !== null) {
                $meter['handpay_variance'] = ($meter['handpay'] - $meter['prev_handpay']);
            }
            if ($meter['coins_drop'] !== null && $meter['prev_coins_drop'] !== null) {
                $meter['coins_drop_variance'] = ($meter['coins_drop'] - $meter['prev_coins_drop']);
            }
        }

        // Calculate Anomaly
        $sum_key = $machine_id . '_' . $operation_date;
        if (isset($transaction_sums[$sum_key])) {
            $current_transaction_sums = $transaction_sums[$sum_key];

            // Bills In Anomaly (vs Cash Drop Transaction)
            if ($meter['bills_in_variance'] !== '---' && $current_transaction_sums['cash_drop_sum'] !== null) {
                $meter['bills_in_anomaly'] = $meter['bills_in_variance'] - (float)$current_transaction_sums['cash_drop_sum'];
            }

            // Handpay Anomaly (vs Handpay Transaction)
            if ($meter['handpay_variance'] !== '---' && $current_transaction_sums['handpay_sum'] !== null) {
                $meter['handpay_anomaly'] = $meter['handpay_variance'] - (float)$current_transaction_sums['handpay_sum'];
            }

            // Coins Drop Anomaly (vs Coins Drop Transaction)
            if ($meter['coins_drop_variance'] !== '---' && $current_transaction_sums['coins_drop_sum'] !== null) {
                $meter['coins_drop_anomaly'] = $meter['coins_drop_variance'] - (float)$current_transaction_sums['coins_drop_sum'];
            }
        }
    }
    unset($meter); // Break the reference

    // Get machines for filter dropdown (still needed for machine_entries link)
    $machines_stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        ORDER BY CAST(m.machine_number AS UNSIGNED) ASC
    ");
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . htmlspecialchars($e->getMessage()));
    $meters = [];
    $machines = [];
    $transaction_sums = [];
}

// Meter types for dropdown (from database ENUM or fixed list)
$meter_types_options = ['online', 'coins', 'offline'];

?>

<div class="meters-list fade-in">
    <!-- Action Buttons -->
    <div class="action-buttons mb-6 flex justify-between">
        <div>
            <?php if ($can_edit): ?>
                <a href="index.php?page=meters&action=create" class="btn btn-primary">
                    Add New Meter Entry (Offline Machines)
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
                            <th class="px-4 py-2 text-center">Total In</th>
                            <th class="px-4 py-2 text-center">Total Out</th>
                            <th class="px-4 py-2 text-center">Bills In</th>
                            <th class="px-4 py-2 text-center variance-anomaly-header">Bills In Variance</th>
                            <th class="px-4 py-2 text-center variance-anomaly-header">Bills In Anomaly</th>
                            <th class="px-4 py-2 text-center">Coins In</th>
                            <th class="px-4 py-2 text-center">Coins Out</th>
                            <th class="px-4 py-2 text-center">Coins Drop</th>
                            <th class="px-4 py-2 text-center variance-anomaly-header">Coins Drop Variance</th>
                            <th class="px-4 py-2 text-center variance-anomaly-header">Coins Drop Anomaly</th>
                            <th class="px-4 py-2 text-center">Bets</th>
                            <th class="px-4 py-2 text-center">Handpay</th>
                            <th class="px-4 py-2 text-center variance-anomaly-header">Handpay Variance</th>
                            <th class="px-4 py-2 text-center variance-anomaly-header">Handpay Anomaly</th>
                            <th class="px-4 py-2 text-center">JP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($meters)): ?>
                            <tr>
                                <td colspan="18" class="text-center px-4 py-6">No meter entries found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meters as $meter): ?>
                                <tr class="hover:bg-gray-800 transition duration-150 clickable-row" data-machine-id="<?php echo $meter['machine_id']; ?>">
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo htmlspecialchars(format_date($meter['operation_date'])); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo htmlspecialchars($meter['machine_number']); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo htmlspecialchars(ucfirst($meter['meter_type'])); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['total_in'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['total_out'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['bills_in'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['bills_in_variance']) ? number_format($meter['bills_in_variance']) : $meter['bills_in_variance']; ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['bills_in_anomaly']) ? number_format($meter['bills_in_anomaly']) : $meter['bills_in_anomaly']; ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['coins_in'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['coins_out'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['coins_drop'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['coins_drop_variance']) ? number_format($meter['coins_drop_variance']) : $meter['coins_drop_variance']; ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['coins_drop_anomaly']) ? number_format($meter['coins_drop_anomaly']) : $meter['coins_drop_anomaly']; ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['bets'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['handpay'] ?? 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['handpay_variance']) ? number_format($meter['handpay_variance']) : $meter['handpay_variance']; ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['handpay_anomaly']) ? number_format($meter['handpay_anomaly']) : $meter['handpay_anomaly']; ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['jp'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-group mt-6">
                <a href="index.php?page=meters" class="btn btn-primary">Back to All Meters</a>
            </div>
        </div>
    </div>
</div>


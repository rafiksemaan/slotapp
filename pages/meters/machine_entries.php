<?php
/**
 * Machine Entries List Page
 * Shows all meter entries for a specific machine, with variance and anomaly calculations.
 */

// Check if machine ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', "Machine ID not provided.");
    header("Location: index.php?page=meters");
    exit;
}

$machine_id = $_GET['id'];

// Check permissions
$can_edit = has_permission('editor');
$can_view = has_permission('viewer');

// Get machine details
try {
    $machine_stmt = $conn->prepare("
        SELECT m.machine_number, b.name as brand_name, mt.name as machine_type_name, m.credit_value
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        WHERE m.id = ?
    ");
    $machine_stmt->execute([$machine_id]);
    $machine_details = $machine_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine_details) {
        set_flash_message('danger', "Machine not found.");
        header("Location: index.php?page=meters");
        exit;
    }

    // Fetch all meter data for this machine, ordered by operation_date
    $query = "
        SELECT
            me.*,
            u.username AS created_by_username,
            LAG(me.bills_in, 1, 0) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_bills_in,
            LAG(me.handpay, 1, 0) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_handpay,
            LAG(me.coins_drop, 1, 0) OVER (PARTITION BY me.machine_id ORDER BY me.operation_date) AS prev_coins_drop
        FROM meters me
        LEFT JOIN users u ON me.created_by = u.id
        WHERE me.machine_id = ?
        ORDER BY me.operation_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([$machine_id]);
    $meters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch transaction sums for anomaly calculation for this machine
    $transaction_sums_query = "
        SELECT
            t.operation_date,
            SUM(CASE WHEN tt.name = 'Cash Drop' THEN t.amount ELSE 0 END) AS cash_drop_sum,
            SUM(CASE WHEN tt.name = 'Coins Drop' THEN t.amount ELSE 0 END) AS coins_drop_sum,
            SUM(CASE WHEN tt.name = 'Handpay' THEN t.amount ELSE 0 END) AS handpay_sum
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE t.machine_id = ?
        GROUP BY t.operation_date
    ";

    $sums_stmt = $conn->prepare($transaction_sums_query);
    $sums_stmt->execute([$machine_id]);
    $transaction_sums = [];
    foreach ($sums_stmt->fetchAll(PDO::FETCH_ASSOC) as $sum_row) {
        $transaction_sums[$sum_row['operation_date']] = $sum_row;
    }

    // Process meters data to calculate variance and anomaly
    foreach ($meters as &$meter) {
        $credit_value = (float)$machine_details['credit_value'];
        $operation_date = $meter['operation_date'];

        // Initialize variance and anomaly fields
        $meter['bills_in_variance'] = '---';
        $meter['handpay_variance'] = '---';
        $meter['coins_drop_variance'] = '---';
        $meter['bills_in_anomaly'] = '---';
        $meter['handpay_anomaly'] = '---';
        $meter['coins_drop_anomaly'] = '---';

        // Calculate Variance
        // Always calculate variance, treating null/empty previous values as 0
        $meter['bills_in_variance'] = (float)($meter['bills_in'] ?? 0) - (float)($meter['prev_bills_in'] ?? 0);
        $meter['handpay_variance'] = (float)($meter['handpay'] ?? 0) - (float)($meter['prev_handpay'] ?? 0);
        $meter['coins_drop_variance'] = (float)($meter['coins_drop'] ?? 0) - (float)($meter['prev_coins_drop'] ?? 0);

        // Calculate Anomaly
        if (isset($transaction_sums[$operation_date])) {
            $current_transaction_sums = $transaction_sums[$operation_date];

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

} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . htmlspecialchars($e->getMessage()));
    $meters = [];
    $machine_details = null;
}

?>

<div class="meters-list fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Meter Entries for Machine #<?php echo htmlspecialchars($machine_details['machine_number'] ?? 'N/A'); ?></h3>
            <p class="text-muted">
                Brand: <?php echo htmlspecialchars($machine_details['brand_name'] ?? 'N/A'); ?> |
                Type: <?php echo htmlspecialchars($machine_details['machine_type_name'] ?? 'N/A'); ?> |
                Credit Value: <?php echo format_currency($machine_details['credit_value'] ?? 0); ?>
            </p>
        </div>
        <div class="card-body p-6">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-center">Date</th>
                            <th class="px-4 py-2 text-center">Meter Type</th>
                            <th class="px-4 py-2 text-center">Total In</th>
                            <th class="px-4 py-2 text-center">Total Out</th>
                            <th class="px-4 py-2 text-center">Bills In</th>
                            <th class="px-4 py-2 text-center">Bills In Variance</th>
                            <th class="px-4 py-2 text-center">Bills In Anomaly</th>
                            <th class="px-4 py-2 text-center">Coins In</th>
                            <th class="px-4 py-2 text-center">Coins Out</th>
                            <th class="px-4 py-2 text-center">Coins Drop</th>
                            <th class="px-4 py-2 text-center">Coins Drop Variance</th>
                            <th class="px-4 py-2 text-center">Coins Drop Anomaly</th>
                            <th class="px-4 py-2 text-center">Bets</th>
                            <th class="px-4 py-2 text-center">Handpay</th>
                            <th class="px-4 py-2 text-center">Handpay Variance</th>
                            <th class="px-4 py-2 text-center">Handpay Anomaly</th>
                            <th class="px-4 py-2 text-center">JP</th>
                            <th class="px-4 py-2 text-center">Notes</th>
                            <th class="px-4 py-2 text-center">Created By</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($meters)): ?>
                            <tr>
                                <td colspan="20" class="text-center px-4 py-6">No meter entries found for this machine.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meters as $meter): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo htmlspecialchars(format_date($meter['operation_date'])); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo htmlspecialchars(ucfirst($meter['meter_type'])); ?></td>
      								<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['total_in'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['total_out'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['bills_in'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['bills_in_variance']) ? number_format($meter['bills_in_variance'], 0) : $meter['bills_in_variance']; ?></td>
									<td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['bills_in_anomaly']) ? number_format($meter['bills_in_anomaly'], 0) : $meter['bills_in_anomaly']; ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['coins_in'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['coins_out'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['coins_drop'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['coins_drop_variance']) ? number_format($meter['coins_drop_variance'], 0) : $meter['coins_drop_variance']; ?></td>
									<td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['coins_drop_anomaly']) ? number_format($meter['coins_drop_anomaly'], 0) : $meter['coins_drop_anomaly']; ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['bets'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['handpay'] ?? 0, 0); ?></td>
									<td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['handpay_variance']) ? number_format($meter['handpay_variance'], 0) : $meter['handpay_variance']; ?></td>
									<td class="px-4 py-2 table-cell-nowrap variance-anomaly-cell"><?php echo is_numeric($meter['handpay_anomaly']) ? number_format($meter['handpay_anomaly'], 0) : $meter['handpay_anomaly']; ?></td>
									<td class="px-4 py-2 table-cell-nowrap"><?php echo number_format($meter['jp'] ?? 0, 0); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap text-sm"><?php echo htmlspecialchars($meter['notes'] ?? ''); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap"><?php echo htmlspecialchars($meter['created_by_username'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2 table-cell-nowrap">
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
            
            <div class="form-group mt-6">
                <a href="index.php?page=meters" class="btn btn-primary">Back to All Meters</a>
            </div>
        </div>
    </div>
</div>

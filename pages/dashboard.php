<?php
/**
 * Dashboard page
 * Shows overview of slot machine statistics and monthly transactions
 */
$page = $_GET['page'] ?? 'dashboard';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get current Cairo time and calculate month range
$cairo_now = new DateTime('now', new DateTimeZone('Africa/Cairo'));
$current_month = $cairo_now->format('Y-m');
$month_start = $current_month . '-01 00:00:00';
$month_end = $cairo_now->format('Y-m-t') . ' 23:59:59';

// Get current month's transaction breakdown
try {
    // OUT transactions (type category = 'OUT')
    $out_query = "
        SELECT tt.name, SUM(t.amount) as total 
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        JOIN machines m ON t.machine_id = m.id
        WHERE tt.category = 'OUT'
        AND t.timestamp BETWEEN ? AND ?
        GROUP BY tt.name
    ";
    $out_stmt = $conn->prepare($out_query);
    $out_stmt->execute([$month_start, $month_end]);
    $out_transactions = $out_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // DROP transactions (category = 'DROP')
    $drop_query = "
        SELECT tt.name, SUM(t.amount) as total 
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        JOIN machines m ON t.machine_id = m.id
        WHERE tt.category = 'DROP'
        AND t.timestamp BETWEEN ? AND ?
        GROUP BY tt.name
    ";
    $drop_stmt = $conn->prepare($drop_query);
    $drop_stmt->execute([$month_start, $month_end]);
    $drop_transactions = $drop_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $out_transactions = [];
    $drop_transactions = [];
}

// Get total counts
try {
    // Count machines by status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM machines GROUP BY status");
    $machine_stats = $stmt->fetchAll();

    // Count machines by machine type
    $stmt = $conn->query("
        SELECT mt.name AS machine_type, COUNT(*) AS count 
        FROM machines m
        JOIN machine_types mt ON m.type_id = mt.id
        GROUP BY mt.id
    ");
    $type_stats = $stmt->fetchAll();
    
    // Calculate totals for current month
    $total_out = array_sum(array_column($out_transactions, 'total')) ?? 0;
    $total_drop = array_sum(array_column($drop_transactions, 'total')) ?? 0;
    
    // Calculate result
    $result = $total_drop - $total_out;
    
    // Get current month's transaction type breakdown for chart
    $stmt = $conn->prepare("
        SELECT tt.name, tt.category, SUM(t.amount) as total 
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE t.timestamp BETWEEN ? AND ?
        GROUP BY tt.name, tt.category
    ");
    $stmt->execute([$month_start, $month_end]);
    $type_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}

// Prepare breakdown data for display
$breakdown_data = [
    'DROP' => [],
    'OUT' => []
];

foreach ($drop_transactions as $transaction) {
    $breakdown_data['DROP'][$transaction['name']] = $transaction['total'];
}

foreach ($out_transactions as $transaction) {
    $breakdown_data['OUT'][$transaction['name']] = $transaction['total'];
}
?>

<div class="dashboard fade-in">
    <!-- Stats Overview with Detailed Breakdown -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-title">Total Machines</div>
            <div class="stat-value"><?php 
                $total_machines = 0;
                foreach ($machine_stats as $stat) {
                    $total_machines += $stat['count'];
                }
                echo $total_machines;
            ?></div>
            <div class="stat-info">Registered Slot Machines</div>
        </div>
        
        <!-- Machines by Type -->
        <div class="stat-card">
            <div class="stat-title">Machines by Type</div>
            <div class="stat-value"><?php
                foreach ($type_stats as $type) {
                    echo "{$type['machine_type']}: {$type['count']}<br>";
                }
            ?></div>
        </div>
        
        <div class="stat-card in">
            <div class="stat-title">This Month's DROP</div>
            <div class="stat-value"><?php echo format_currency($total_drop); ?></div>
            <!--<div class="stat-info">Total Coins & Cash drops</div> -->
            <!-- DROP Breakdown -->
            <div class="stat-breakdown">
                <?php foreach ($breakdown_data['DROP'] as $type => $amount): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-type"><?php echo htmlspecialchars($type); ?></span>
                        <span class="breakdown-amount"><?php echo format_currency($amount); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="stat-card out">
            <div class="stat-title">This Month's OUT</div>
            <div class="stat-value"><?php echo format_currency($total_out); ?></div>
          <!--  <div class="stat-info">Total Handpays, Tickets, Refills</div> -->
            <!-- OUT Breakdown -->
            <div class="stat-breakdown">
                <?php foreach ($breakdown_data['OUT'] as $type => $amount): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-type"><?php echo htmlspecialchars($type); ?></span>
                        <span class="breakdown-amount"><?php echo format_currency($amount); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="stat-card <?php echo $result >= 0 ? 'in' : 'out'; ?>">
            <div class="stat-title">This Month's Result</div>
            <div class="stat-value"><?php echo format_currency($result); ?></div>
         <!--   <div class="stat-info">DROP - OUT</div> -->
            <div class="stat-breakdown">
                <div class="breakdown-item">
                    <span class="breakdown-type">DROP - OUT</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row with Fixed Layout -->
    <div class="dashboard-charts-row">
        <div class="dashboard-chart-card">
            <div class="card">
                <div class="chart-container" 
                         data-stats='<?php echo json_encode($machine_stats); ?>'
                         data-out-data='<?php echo json_encode(array_column($out_transactions, 'total')); ?>'
                         data-out-labels='<?php echo json_encode(array_column($out_transactions, 'name')); ?>'>
                    <h3>Machine Distribution</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container"
                         data-stats='<?php echo json_encode($machine_stats); ?>'>
                        <canvas id="machines-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-chart-card">
            <div class="card">
                <div class="card-header">
                    <h3>This Month's Transactions</h3>
                </div>
                <div class="card-body">
                    <?php if ($total_out == 0 && $total_drop == 0): ?>
                        <div class="no-transactions">
                            <p>No transactions recorded this month</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-container"
                             data-out-data='<?php echo json_encode(array_column($out_transactions, 'total')); ?>'
                             data-out-labels='<?php echo json_encode(array_column($out_transactions, 'name')); ?>'
                             data-drop-data='<?php echo json_encode(array_column($drop_transactions, 'total')); ?>'
                             data-drop-labels='<?php echo json_encode(array_column($drop_transactions, 'name')); ?>'>

                            <canvas id="transactions-chart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/dashboard_charts.js"></script>
<?php
/**
 * Dashboard page
 * Shows overview of slot machine statistics and yearly transactions
 */
$page = $_GET['page'] ?? 'dashboard';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get current Cairo time and calculate year range
$cairo_now = new DateTime('now', new DateTimeZone('Africa/Cairo'));
$current_year = $cairo_now->format('Y');
$year_start = $current_year . '-01-01 00:00:00';
$year_end = $current_year . '-12-31 23:59:59';

// Get current year's transaction breakdown
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
    $out_stmt->execute([$year_start, $year_end]);
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
    $drop_stmt->execute([$year_start, $year_end]);
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
	
	$stmt = $conn->prepare("SELECT COUNT(*) FROM machines WHERE status = 'Active'");
	$stmt->execute();
	$active_machines_count = $stmt->fetchColumn();

	$stmt = $conn->prepare("SELECT COUNT(*) FROM machines WHERE status = 'Inactive'");
	$stmt->execute();
	$inactive_machines_count = $stmt->fetchColumn();

	$stmt = $conn->prepare("SELECT COUNT(*) FROM machines WHERE status = 'Maintenance'");
	$stmt->execute();
	$maintenance_machines_count = $stmt->fetchColumn();
	
	

    // Count machines by machine type
    $stmt = $conn->query("
        SELECT mt.name AS machine_type, COUNT(*) AS count 
        FROM machines m
        JOIN machine_types mt ON m.type_id = mt.id
        GROUP BY mt.id
    ");
    $type_stats = $stmt->fetchAll();
    
    // Calculate totals for current year
    $total_out = array_sum(array_column($out_transactions, 'total')) ?? 0;
    $total_drop = array_sum(array_column($drop_transactions, 'total')) ?? 0;
    
    // Calculate result
    $result = $total_drop - $total_out;
    
    // Get current year's transaction type breakdown for chart
    $stmt = $conn->prepare("
        SELECT tt.name, tt.category, SUM(t.amount) as total 
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE t.timestamp BETWEEN ? AND ?
        GROUP BY tt.name, tt.category
    ");
    $stmt->execute([$year_start, $year_end]);
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
        <div class="stat-card card-body-centered">
            <div class="stat-title">Total Machines</div>
            <div class="stat-value"><?php 
                $total_machines = 0;
                foreach ($machine_stats as $stat) {
                    $total_machines += $stat['count'];
                }
                echo $total_machines;
            ?></div>
            <div class="stat-info">Registered Slot Machines</div>
			<div class="stat-breakdown">
			<div class="breakdown-item"><span class="breakdown-type">Active: <span class="breakdown-amount"><?php echo $active_machines_count ?></span></span></div>
			<div class="breakdown-item"><span class="breakdown-type">Inactive: <span class="breakdown-amount"><?php echo $inactive_machines_count ?></span></span></div>
			<div class="breakdown-item"><span class="breakdown-type">Maintenance: <span class="breakdown-amount"><?php echo $maintenance_machines_count ?></span></span></div>
			</div>
        </div>
        
        <!-- Machines by Type -->
        <div class="stat-card card-body-centered">
            <div class="stat-title">Machines by Type</div>
            <div class="stat-value"><?php
                foreach ($type_stats as $type) {
                    echo "{$type['machine_type']}: {$type['count']}<br>";
                }
            ?></div>
        </div>
        
        <div class="stat-card card-body-centered in">
            <div class="stat-title">This Year's DROP</div>
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
        
        <div class="stat-card card-body-centered out">
            <div class="stat-title">This Year's OUT</div>
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
        
        <div class="stat-card card-body-centered <?php echo $result >= 0 ? 'in' : 'out'; ?>">
            <div class="stat-title">This Year's Result</div>
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
            <div class="card-header">
                <h3>Machine Distribution</h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="machines-chart" data-stats='<?php echo json_encode($machine_stats); ?>'></canvas>
                </div>
            </div>
        </div>
        </div>
        
        <div class="dashboard-chart-card">
            <div class="card">
                <div class="card-header">
                    <h3>This Year's Transactions</h3>
                </div>
                <div class="card-body">
                    <?php if ($total_out == 0 && $total_drop == 0): ?>
                        <div class="no-transactions">
                            <p>No transactions recorded this year</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-container">
                            <canvas id="transactions-chart" data-out-data='<?php echo json_encode(array_column($out_transactions, 'total')); ?>'
                             data-out-labels='<?php echo json_encode(array_column($out_transactions, 'name')); ?>'
                             data-drop-data='<?php echo json_encode(array_column($drop_transactions, 'total')); ?>'
                             data-drop-labels='<?php echo json_encode(array_column($drop_transactions, 'name')); ?>'></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/dashboard_charts.js"></script>

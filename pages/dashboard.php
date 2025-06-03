<?php
/**
 * Dashboard page
 * Shows overview of slot machine statistics and recent transactions
 */
$page = $_GET['page'] ?? 'dashboard';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get current Cairo time
$cairo_now = new DateTime('now', new DateTimeZone('Africa/Cairo'));
$today_start = $cairo_now->format('Y-m-d') . ' 00:00:00';
$today_end = $cairo_now->format('Y-m-d') . ' 23:59:59';

// Get total counts
try {
    // Count machines by status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM machines GROUP BY status");
    $machine_stats = $stmt->fetchAll();
    
    // Count machines by type
    $stmt = $conn->query("SELECT type, COUNT(*) as count FROM machines GROUP BY type");
    $type_stats = $stmt->fetchAll();
    
    // Get total amounts for today (OUT)
    $stmt = $conn->prepare("
        SELECT tt.name, SUM(t.amount) as total 
        FROM transactions t 
        JOIN transaction_types tt ON t.transaction_type_id = tt.id 
        WHERE tt.category = 'OUT' AND t.timestamp BETWEEN ? AND ?
        GROUP BY tt.name
    ");
    $stmt->execute([$today_start, $today_end]);
    $out_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total amounts for today (DROP)
    $stmt = $conn->prepare("
        SELECT tt.name, SUM(t.amount) as total 
        FROM transactions t 
        JOIN transaction_types tt ON t.transaction_type_id = tt.id 
        WHERE tt.category = 'DROP' AND t.timestamp BETWEEN ? AND ?
        GROUP BY tt.name
    ");
    $stmt->execute([$today_start, $today_end]);
    $drop_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_out = array_sum(array_column($out_transactions, 'total')) ?? 0;
    $total_drop = array_sum(array_column($drop_transactions, 'total')) ?? 0;
    
    // Calculate result
    $result = $total_drop - $total_out;
    
    // Get recent transactions
    $stmt = $conn->prepare("
        SELECT t.id, m.machine_number, tt.name as transaction_type, tt.category,
               t.amount, t.timestamp, u.username 
        FROM transactions t 
        JOIN machines m ON t.machine_id = m.id 
        JOIN transaction_types tt ON t.transaction_type_id = tt.id 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.timestamp DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}

// Get transaction type breakdown for today
$stmt = $conn->prepare("
    SELECT tt.name, SUM(t.amount) as total 
    FROM transactions t
    JOIN transaction_types tt ON t.transaction_type_id = tt.id
    WHERE DATE(t.timestamp) = ?
    GROUP BY tt.name
");
$stmt->execute([$cairo_now->format('Y-m-d')]);
$type_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="dashboard fade-in">
    <!-- Stats Overview -->
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
                    echo "{$type['type']}: {$type['count']}<br>";
                }
            ?></div>
        </div>
        
        <div class="stat-card in">
            <div class="stat-title">Today's DROP</div>
            <div class="stat-value"><?php echo format_currency($total_drop); ?></div>
            <div class="stat-info">Total Coins & Cash drops</div>
        </div>
        
        <div class="stat-card out">
            <div class="stat-title">Today's OUT</div>
            <div class="stat-value"><?php echo format_currency($total_out); ?></div>
            <div class="stat-info">Total Handpays, Tickets, Refills</div>
        </div>
        
        <div class="stat-card <?php echo $result >= 0 ? 'in' : 'out'; ?>">
            <div class="stat-title">Today's Result</div>
            <div class="stat-value"><?php echo format_currency($result); ?></div>
            <div class="stat-info">DROP - OUT</div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h3>Machine Distribution</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="machines-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transaction Type Breakdown -->
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h3>Transaction Type</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <?php if (empty($type_breakdown)): ?>
                            <div class="no-transactions">
                                <p>No transactions recorded today</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-unstyled">
                                <?php foreach ($type_breakdown as $item): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>: 
                                        <?php echo format_currency($item['total']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h3>Today's Transactions</h3>
                </div>
                <div class="card-body">
                    <?php if ($total_out == 0 && $total_drop == 0): ?>
                        <div class="no-transactions">
                            <p>No transactions recorded today</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-container">
                            <canvas id="transactions-chart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Transactions</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Machine</th>
                            <th>Transaction</th>
                            <th>Amount</th>
                            <th>Time</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No recent transactions</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['machine_number']); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($transaction['category']); ?>">
                                            <?php echo htmlspecialchars($transaction['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo format_currency($transaction['amount']); ?></td>
                                    <td><?php echo format_datetime($transaction['timestamp'], 'M d, Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Machine distribution chart
    const machinesCtx = document.getElementById('machines-chart');
    if (machinesCtx) {
        new Chart(machinesCtx, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach ($machine_stats as $stat): ?>
                        '<?php echo $stat['status']; ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($machine_stats as $stat): ?>
                            <?php echo $stat['count']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(231, 76, 60, 0.7)',
                        'rgba(243, 156, 18, 0.7)',
                        'rgba(52, 152, 219, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Machines by Status',
                        color: '#ffffff'
                    }
                }
            }
        });
    }
    
    // Today's transactions chart
    const transactionsCtx = document.getElementById('transactions-chart');
    if (transactionsCtx) {
        const outData = <?php echo json_encode(array_column($out_transactions, 'total')); ?>;
        const outLabels = <?php echo json_encode(array_column($out_transactions, 'name')); ?>;
        const dropData = <?php echo json_encode(array_column($drop_transactions, 'total')); ?>;
        const dropLabels = <?php echo json_encode(array_column($drop_transactions, 'name')); ?>;

        new Chart(transactionsCtx, {
            type: 'bar',
            data: {
                labels: [...outLabels, ...dropLabels],
                datasets: [{
                    label: 'Amount',
                    data: [...outData, ...dropData],
                    backgroundColor: [
                        ...Array(outLabels.length).fill('rgba(231, 76, 60, 0.7)'),
                        ...Array(dropLabels.length).fill('rgba(46, 204, 113, 0.7)')
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0a0',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0a0'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Today\'s Transactions',
                        color: '#ffffff'
                    }
                }
            }
        });
    }
});
</script>
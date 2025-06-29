<?php
/**
 * View Current Operation Day
 */

// Get current operation day setting
try {
    $stmt = $conn->prepare("SELECT * FROM operation_day ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $current_operation_day = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no operation day is set, use current date
    if (!$current_operation_day) {
        $operation_date = date('Y-m-d');
        $is_default = true;
    } else {
        $operation_date = $current_operation_day['operation_date'];
        $is_default = false;
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $operation_date = date('Y-m-d');
    $is_default = true;
}

// Get some statistics for the current operation day
try {
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN tt.category = 'DROP' THEN t.amount ELSE 0 END) as total_drop,
            SUM(CASE WHEN tt.category = 'OUT' THEN t.amount ELSE 0 END) as total_out
        FROM transactions t
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE DATE(t.operation_date) = ?
    ");
    $stats_stmt->execute([$operation_date]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        $stats = ['total_transactions' => 0, 'total_drop' => 0, 'total_out' => 0];
    }
    
} catch (PDOException $e) {
    $stats = ['total_transactions' => 0, 'total_drop' => 0, 'total_out' => 0];
}

$total_result = $stats['total_drop'] - $stats['total_out'];
?>

<div class="operation-day-view fade-in">
    <div class="row">
        <!-- Current Operation Day Card -->
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h3>Current Operation Day</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="operation-day-info">
                        <div class="detail-group">
                            <strong>Operation Date:</strong>
                            <span class="operation-date-display">
                                <?php echo format_date($operation_date); ?>
                                <?php if ($is_default): ?>
                                    <span class="badge badge-warning">Default (Today)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if (!$is_default): ?>
                            <div class="detail-group">
                                <strong>Set By:</strong>
                                <span><?php echo htmlspecialchars($current_operation_day['set_by_username'] ?? 'System'); ?></span>
                            </div>
                            
                            <div class="detail-group">
                                <strong>Set At:</strong>
                                <span><?php echo format_datetime($current_operation_day['created_at'], 'd M Y H:i:s'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-group">
                            <strong>Status:</strong>
                            <span class="status-badge <?php echo date('Y-m-d') === $operation_date ? 'status-current' : 'status-past'; ?>">
                                <?php echo date('Y-m-d') === $operation_date ? 'Current Day' : 'Past Day'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 2rem;">
                        <a href="index.php?page=operation_day&action=set" class="btn btn-primary">Set New Operation Day</a>
                        <a href="index.php?page=transactions&operation_date=<?php echo $operation_date; ?>" class="btn btn-secondary">View Transactions</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Operation Day Statistics -->
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h3>Operation Day Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($stats['total_transactions']); ?></div>
                            <div class="stat-label">Total Transactions</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value text-success"><?php echo format_currency($stats['total_drop']); ?></div>
                            <div class="stat-label">Total DROP</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value text-danger"><?php echo format_currency($stats['total_out']); ?></div>
                            <div class="stat-label">Total OUT</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value <?php echo $total_result >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo format_currency($total_result); ?>
                            </div>
                            <div class="stat-label">Result</div>
                        </div>
                    </div>
                    
                    <!-- Recent Operation Days -->
                    <?php
                    try {
                        $recent_stmt = $conn->prepare("
                            SELECT operation_date, set_by_username, created_at 
                            FROM operation_day 
                            ORDER BY created_at DESC 
                            LIMIT 5
                        ");
                        $recent_stmt->execute();
                        $recent_days = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $recent_days = [];
                    }
                    ?>
                    
                    <?php if (!empty($recent_days)): ?>
                        <div class="recent-operation-days" style="margin-top: 2rem;">
                            <h4>Recent Operation Days</h4>
                            <div class="operation-days-list">
                                <?php foreach ($recent_days as $day): ?>
                                    <div class="operation-day-item">
                                        <div class="day-info">
                                            <strong><?php echo format_date($day['operation_date']); ?></strong>
                                            <br>
                                            <small>Set by <?php echo htmlspecialchars($day['set_by_username']); ?></small>
                                        </div>
                                        <div class="day-meta">
                                            <small><?php echo format_datetime($day['created_at'], 'd M Y H:i'); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.operation-date-display {
    font-size: 1.2em;
    font-weight: bold;
    color: var(--secondary-color);
}

.badge {
    display: inline-block;
    padding: 0.25em 0.6em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    margin-left: 0.5rem;
}

.badge-warning {
    color: #212529;
    background-color: #ffc107;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: bold;
    text-transform: uppercase;
}

.status-current {
    background-color: rgba(46, 204, 113, 0.2);
    color: var(--success-color);
}

.status-past {
    background-color: rgba(243, 156, 18, 0.2);
    color: var(--warning-color);
}

.operation-days-list {
    max-height: 200px;
    overflow-y: auto;
}

.operation-day-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: var(--border-radius);
    transition: background-color var(--transition-speed);
}

.operation-day-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.day-info {
    display: flex;
    flex-direction: column;
}

.day-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}
</style>
<?php
/**
 * View User Profile
 */

// Get current user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: logout.php");
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get user activity stats
try {
    // Get transaction count created by this user
    $stmt = $conn->prepare("SELECT COUNT(*) as transaction_count FROM transactions WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent transactions by this user
    $stmt = $conn->prepare("
        SELECT t.*, m.machine_number, tt.name as transaction_type, tt.category
        FROM transactions t
        JOIN machines m ON t.machine_id = m.id
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE t.user_id = ?
        ORDER BY t.timestamp DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $stats = ['transaction_count' => 0];
    $recent_transactions = [];
}
?>

<div class="profile-view fade-in">
    <div class="row">
        <!-- Profile Information -->
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h3>Profile Information</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php else: ?>
                        <div class="profile-details">
                            <div class="detail-group">
                                <strong>Username:</strong>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            
                            <div class="detail-group">
                                <strong>Full Name:</strong>
                                <span><?php echo htmlspecialchars($user['name']); ?></span>
                            </div>
                            
                            <div class="detail-group">
                                <strong>Email:</strong>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            
                            <div class="detail-group">
                                <strong>Role:</strong>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-group">
                                <strong>Account Created:</strong>
                                <span><?php echo format_datetime($user['created_at'], 'd M Y H:i'); ?></span>
                            </div>
                            
                            <div class="detail-group">
                                <strong>Last Updated:</strong>
                                <span><?php echo format_datetime($user['updated_at'], 'd M Y H:i'); ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 2rem;">
                            <a href="index.php?page=profile&action=edit" class="btn btn-primary">Edit Profile</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Activity Stats -->
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h3>Activity Summary</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['transaction_count']; ?></div>
                            <div class="stat-label">Transactions Created</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo ucfirst($user['role']); ?></div>
                            <div class="stat-label">Access Level</div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <?php if (!empty($recent_transactions)): ?>
                        <div class="recent-activity" style="margin-top: 2rem;">
                            <h4>Recent Transactions</h4>
                            <div class="activity-list">
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <div class="activity-item">
                                        <div class="activity-info">
                                            <strong><?php echo htmlspecialchars($transaction['transaction_type']); ?></strong>
                                            <span class="activity-machine">Machine #<?php echo htmlspecialchars($transaction['machine_number']); ?></span>
                                        </div>
                                        <div class="activity-meta">
                                            <span class="activity-amount"><?php echo format_currency($transaction['amount']); ?></span>
                                            <span class="activity-date"><?php echo format_datetime($transaction['timestamp'], 'd M Y'); ?></span>
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
.detail-group {
    margin-bottom: 1rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-group:last-child {
    border-bottom: none;
}

.detail-group strong {
    display: inline-block;
    width: 140px;
    color: var(--text-muted);
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: bold;
    text-transform: uppercase;
}

.role-admin {
    background-color: rgba(231, 76, 60, 0.2);
    color: var(--danger-color);
}

.role-editor {
    background-color: rgba(243, 156, 18, 0.2);
    color: var(--warning-color);
}

.role-viewer {
    background-color: rgba(46, 204, 113, 0.2);
    color: var(--success-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: var(--border-radius);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--secondary-color);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.recent-activity h4 {
    color: var(--secondary-color);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: var(--border-radius);
    transition: background-color var(--transition-speed);
}

.activity-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.activity-info {
    display: flex;
    flex-direction: column;
}

.activity-machine {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.activity-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.activity-amount {
    font-weight: bold;
    color: var(--text-light);
}

.activity-date {
    font-size: 0.875rem;
    color: var(--text-muted);
}
</style>
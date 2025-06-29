<?php
/**
 * View transaction details
 */

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?page=transactions");
    exit;
}

$transaction_id = $_GET['id'];

try {
    // Get transaction details with related information including edited_by user
    $stmt = $conn->prepare("
        SELECT t.*, 
               m.machine_number, m.model,
               mt.name as machine_type,
               b.name as brand_name,
               tt.name as transaction_type, tt.category,
               u.username, u.name as user_name,
               eu.username as edited_by_username, eu.name as edited_by_name
        FROM transactions t
        JOIN machines m ON t.machine_id = m.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        LEFT JOIN brands b ON m.brand_id = b.id
        JOIN transaction_types tt ON t.transaction_type_id = tt.id
        JOIN users u ON t.user_id = u.id
        LEFT JOIN users eu ON t.edited_by = eu.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        header("Location: index.php?page=transactions&message=Transaction not found");
        exit;
    }
    
} catch (PDOException $e) {
    header("Location: index.php?page=transactions&message=Error retrieving transaction: " . urlencode($e->getMessage()));
    exit;
}
?>

<div class="transaction-view fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Transaction Details</h3>
        </div>
        <div class="card-body">
            <!-- Grid Layout -->
            <div class="row">
                <!-- Transaction Info -->
                <div class="col">
                    <h4 class="section-title">Transaction Information</h4>
                    <dl class="detail-list">
                        <dt>Transaction Type</dt>
                        <dd>
                            <span <?php echo strtolower($transaction['category']); ?>">
                                <?php echo htmlspecialchars($transaction['transaction_type']); ?>
                            </span>
                        </dd>

                        <dt>Amount</dt>
                        <dd><?php echo format_currency($transaction['amount']); ?></dd>

                        <dt>Date & Time</dt>
                        <dd><?php echo htmlspecialchars(format_datetime($transaction['timestamp'], 'd M Y H:i:s')); ?></dd>

                        <dt>Notes</dt>
                        <dd><?php echo nl2br(htmlspecialchars($transaction['notes'] ?? 'No notes')); ?></dd>
                    </dl>
                </div>

                <!-- Machine Info -->
                <div class="col">
                    <h4 class="section-title">Machine Information</h4>
                    <dl class="detail-list">
                        <dt>Machine Number</dt>
                        <dd><?php echo htmlspecialchars($transaction['machine_number']); ?></dd>

                        <dt>Brand</dt>
                        <dd><?php echo htmlspecialchars($transaction['brand_name'] ?? 'N/A'); ?></dd>

                        <dt>Model</dt>
                        <dd><?php echo htmlspecialchars($transaction['model']); ?></dd>

                        <dt>Type</dt>
                        <dd><?php echo htmlspecialchars($transaction['machine_type']); ?></dd>
                    </dl>
                </div>

                <!-- User Info -->
                <div class="col">
                    <h4 class="section-title">User Information</h4>
                    <dl class="detail-list">
                        <dt>Created By</dt>
                        <dd><?php echo htmlspecialchars($transaction['username']); ?></dd>

                        <dt>Creator Name</dt>
                        <dd><?php echo htmlspecialchars($transaction['user_name']); ?></dd>

                        <?php if ($transaction['edited_by']): ?>
                            <dt>Last Edited By</dt>
                            <dd><?php echo htmlspecialchars($transaction['edited_by_username']); ?></dd>

                            <dt>Editor Name</dt>
                            <dd><?php echo htmlspecialchars($transaction['edited_by_name']); ?></dd>
                        <?php endif; ?>

                        <dt>Created At</dt>
                        <dd><?php echo format_datetime($transaction['created_at'], 'd M Y H:i:s'); ?></dd>

                        <dt>Updated At</dt>
                        <dd><?php echo format_datetime($transaction['updated_at'], 'd M Y H:i:s'); ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-group" style="margin-top: 2rem;">
                <a href="index.php?page=transactions" class="btn btn-primary">Back to Transactions</a>
                <?php if ($can_edit): ?>
                    <a href="index.php?page=transactions&action=edit&id=<?php echo $transaction_id; ?>" class="btn btn-primary">Edit Transaction</a>
                    <a href="index.php?page=transactions&action=delete&id=<?php echo $transaction_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this transaction?')">Delete Transaction</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
/**
 * Edit Transaction
 */

// Ensure user has edit permissions
if (!$can_edit) {
    header("Location: index.php?page=transactions&error=Access denied");
    exit;
}

// Check if an ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=transactions");
    exit;
}

$transaction_id = $_GET['id'];
$error = '';
$success = false;

// Get current transaction data
try {
    $stmt = $conn->prepare("
    SELECT t.*, 
           m.machine_number, m.model, mt.name as machine_type,
           b.name as brand_name,
           tt.name as transaction_type, tt.category,
           u.username, u.name as user_name
    FROM transactions t
    JOIN machines m ON t.machine_id = m.id
    LEFT JOIN brands b ON m.brand_id = b.id
	LEFT JOIN machine_types mt ON m.type_id = mt.id
    JOIN transaction_types tt ON t.transaction_type_id = tt.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        header("Location: index.php?page=transactions&error=Transaction not found");
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php?page=transactions&error=Database error");
    exit;
}



// Get all transaction types
try {
    $types_stmt = $conn->query("SELECT id, name, category FROM transaction_types ORDER BY category, name");
    $transaction_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $transaction_types = [];
}

// Get all machines for dropdown with brand information
try {
    $machines_stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name 
        FROM machines m 
        LEFT JOIN brands b ON m.brand_id = b.id 
        ORDER BY m.machine_number
    ");
    $machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $machines = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $machine_id = sanitize_input($_POST['machine_id'] ?? '');
    $transaction_type_id = sanitize_input($_POST['transaction_type_id'] ?? '');
    $amount = sanitize_input($_POST['amount'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    $timestamp = sanitize_input($_POST['timestamp'] ?? '');

    // Validate required fields
    if (empty($machine_id) || empty($transaction_type_id) || empty($amount) || empty($timestamp)) {
        $error = "Please fill out all required fields.";
    } else {
        try {
            // Update transaction
            $stmt = $conn->prepare("
                UPDATE transactions SET
                    machine_id = ?,
                    transaction_type_id = ?,
                    amount = ?,
                    notes = ?,
                    timestamp = ?
                WHERE id = ?
            ");

            $result = $stmt->execute([
                $machine_id,
                $transaction_type_id,
                $amount,
                $notes ?: null,
                $timestamp,
                $transaction_id
            ]);

            if ($result) {
                log_action('update_transaction', "Updated transaction ID: {$transaction_id}");
                $success = true;

                // Redirect after successful update
                if (!headers_sent()) {
                    header("Location: index.php?page=transactions&message=Transaction updated successfully");
                    exit;
                }
            } else {
                $error = "Failed to update transaction.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="transaction-edit fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Edit Transaction</h3>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">Transaction updated successfully!</div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="transaction-form">
                <!-- Transaction Details Section -->
                <div class="form-section">
                    <h4>Transaction Details</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="timestamp">Date & Time *</label>
                                <input type="datetime-local" id="timestamp" name="timestamp" class="form-control"
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($transaction['timestamp'])); ?>" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="machine_id">Machine *</label>
                                <select id="machine_id" name="machine_id" class="form-control" required>
                                    <option value="">Select Machine</option>
                                    <?php foreach ($machines as $machine): ?>
                                        <option value="<?php echo $machine['id']; ?>" <?php echo $machine['id'] == $transaction['machine_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($machine['machine_number']); ?>
                                            <?php if ($machine['brand_name']): ?>
                                                (<?php echo htmlspecialchars($machine['brand_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="transaction_type_id">Transaction Type *</label>
                                <select id="transaction_type_id" name="transaction_type_id" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($transaction_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $type['id'] == $transaction['transaction_type_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?> (<?php echo $type['category']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="amount">Amount *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0" class="form-control" value="<?php echo number_format((float)$transaction['amount'], 2, '.', ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="form-section">
                    <h4>Additional Information</h4>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Optional notes about this transaction..."><?php echo htmlspecialchars($transaction['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Transaction</button>
                    <a href="index.php?page=transactions" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
/**
 * Create new transaction
 */

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize transaction data
$transaction = [
    'machine_id' => '',
    'transaction_type_id' => $_POST['transaction_type_id'] ?? '', // ✅ Preserve transaction type
    'amount' => '',
    'timestamp' => cairo_time('Y-m-d H:i:s'),
    'notes' => ''
];

$message = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $transaction['machine_id'] = sanitize_input($_POST['machine_id'] ?? '');
    $transaction['transaction_type_id'] = sanitize_input($_POST['transaction_type_id'] ?? '');
    $transaction['amount'] = sanitize_input($_POST['amount'] ?? '');
    $transaction['timestamp'] = sanitize_input($_POST['timestamp'] ?? date('Y-m-d H:i:s'));
    $transaction['notes'] = sanitize_input($_POST['notes'] ?? '');
    
    // Validate required fields
    if (empty($transaction['machine_id']) || empty($transaction['transaction_type_id']) || 
        empty($transaction['amount']) || empty($transaction['timestamp'])) {
        $error = "Please fill out all required fields.";
    }
    // Validate amount is positive
    else if (!is_numeric($transaction['amount']) || $transaction['amount'] <= 0) {
        $error = "Amount must be a positive number.";
    }
    else {
        try {
            // Insert new transaction
            $stmt = $conn->prepare("
                INSERT INTO transactions (machine_id, transaction_type_id, amount, timestamp, user_id, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $transaction['machine_id'], 
                $transaction['transaction_type_id'], 
                $transaction['amount'], 
                $transaction['timestamp'],
                $_SESSION['user_id'],
                $transaction['notes'] ?: null
            ]);
            
            // Get transaction type details for logging
            $type_stmt = $conn->prepare("SELECT name FROM transaction_types WHERE id = ?");
            $type_stmt->execute([$transaction['transaction_type_id']]);
            $type_name = $type_stmt->fetch()['name'] ?? 'Unknown';
            
            // Get machine number for logging
            $machine_stmt = $conn->prepare("SELECT machine_number FROM machines WHERE id = ?");
            $machine_stmt->execute([$transaction['machine_id']]);
            $machine_number = $machine_stmt->fetch()['machine_number'] ?? 'Unknown';
            
            // Log action
            log_action('create_transaction', "Created {$type_name} transaction for machine {$machine_number}: " . format_currency($transaction['amount']));
            
            // Set success message
            $message = "Transaction created successfully";
            
            // Clear only machine and amount for re-entry
            $transaction['machine_id'] = '';
            $transaction['amount'] = '';
            
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get machines for dropdown
try {
    $stmt = $conn->query("SELECT id, machine_number FROM machines ORDER BY machine_number");
    $machines = $stmt->fetchAll();
} catch (PDOException $e) {
    $machines = [];
    $error = "Database error: " . $e->getMessage();
}

// Get transaction types for dropdown
try {
    $stmt = $conn->query("SELECT id, name, category FROM transaction_types ORDER BY category, name");
    $transaction_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $transaction_types = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="transaction-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add New Transaction</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo $_SERVER['PHP_SELF'] ?>?page=transactions&action=create" method="POST" onsubmit="return validateForm(this)">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="machine_id">Machine *</label>
                            <select id="machine_id" name="machine_id" class="form-control" required>
                                <option value="">Select Machine</option>
                                <?php foreach ($machines as $machine): ?>
                                    <option value="<?php echo $machine['id']; ?>" 
                                        <?php echo $transaction['machine_id'] == $machine['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($machine['machine_number']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="form-group">
                            <label for="transaction_type_id">Transaction Type *</label>
                            <select id="transaction_type_id" name="transaction_type_id" class="form-control" required>
                                <option value="">Select Transaction Type</option>
                                <optgroup label="OUT">
                                    <?php foreach ($transaction_types as $type): ?>
                                        <?php if ($type['category'] == 'OUT'): ?>
                                            <option value="<?php echo $type['id']; ?>" 
                                                <?php echo $transaction['transaction_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="DROP">
                                    <?php foreach ($transaction_types as $type): ?>
                                        <?php if ($type['category'] == 'DROP'): ?>
                                            <option value="<?php echo $type['id']; ?>" 
                                                <?php echo $transaction['transaction_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="amount">Amount *</label>
                            <input type="number" id="amount" name="amount" class="form-control" 
                                   value="<?php echo htmlspecialchars($transaction['amount']); ?>" 
                                   step="0.01" min="0.01" required>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="form-group">
                            <label for="timestamp">Date *</label>
                            <input type="datetime-local" id="timestamp" name="timestamp" class="form-control" 
                                   value="<?php echo htmlspecialchars($transaction['timestamp'] ?? cairo_time('d M Y – H:i:s')); ?>" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3">
                        <?php echo htmlspecialchars($transaction['notes']); ?>
                    </textarea>
                </div>
                
                <div class="form-actions mt-6 flex gap-4">
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                    <a href="index.php?page=transactions" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateForm(form) {
    const amountInput = form.amount;
    if (amountInput.value <= 0) {
        alert("Amount must be a positive number.");
        return false;
    }
    return true;
}

// Optional: Auto-focus first field
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('machine_id').focus();
});
</script>
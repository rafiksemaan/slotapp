<?php
/**
 * Create new transaction
 */

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current operation day
try {
    $op_stmt = $conn->prepare("SELECT operation_date FROM operation_day ORDER BY id DESC LIMIT 1");
    $op_stmt->execute();
    $current_operation_day = $op_stmt->fetch(PDO::FETCH_ASSOC);
    $operation_date = $current_operation_day ? $current_operation_day['operation_date'] : date('Y-m-d');
} catch (PDOException $e) {
    $operation_date = date('Y-m-d');
}

// Initialize transaction data
$transaction = [
    'machine_id' => '',
    'transaction_type_id' => $_POST['transaction_type_id'] ?? '', // ✅ Preserve transaction type
    'amount' => '',
    'timestamp' => cairo_time('Y-m-d H:i:s'),
    'operation_date' => $operation_date,
    'notes' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $transaction['machine_id'] = get_input(INPUT_POST, 'machine_id', 'int');
    $transaction['transaction_type_id'] = get_input(INPUT_POST, 'transaction_type_id', 'int');
    $transaction['amount'] = get_input(INPUT_POST, 'amount', 'float');
    $transaction['timestamp'] = get_input(INPUT_POST, 'timestamp', 'string', cairo_time('Y-m-d H:i:s'));
    $transaction['operation_date'] = get_input(INPUT_POST, 'operation_date', 'string', $operation_date);
    $transaction['notes'] = get_input(INPUT_POST, 'notes', 'string');

    // Validate required fields
    if (empty($transaction['machine_id']) || empty($transaction['transaction_type_id']) ||
        empty($transaction['amount']) || empty($transaction['timestamp']) || empty($transaction['operation_date'])) {
        set_flash_message('danger', "Please fill out all required fields.");
        header("Location: index.php?page=transactions&action=create");
        exit;
    }
    // Validate amount is positive
    else if (!is_numeric($transaction['amount']) || $transaction['amount'] <= 0) {
        set_flash_message('danger', "Amount must be a positive number.");
        header("Location: index.php?page=transactions&action=create");
        exit;
    }
    else {
        try {
            // Insert new transaction with operation_date
            $stmt = $conn->prepare("
                INSERT INTO transactions (machine_id, transaction_type_id, amount, timestamp, operation_date, user_id, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $transaction['machine_id'],
                $transaction['transaction_type_id'],
                $transaction['amount'],
                $transaction['timestamp'],
                $transaction['operation_date'],
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
            log_action('create_transaction', "Created {$type_name} transaction for machine {$machine_number}: " . format_currency($transaction['amount']) . " (Operation Date: {$transaction['operation_date']})");

            // Set success message
            set_flash_message('success', "Transaction created successfully for operation date: " . format_date($transaction['operation_date']));
            header("Location: index.php?page=transactions");
            exit;

            // Clear only machine and amount for re-entry, keep operation date
            $transaction['machine_id'] = '';
            $transaction['amount'] = '';

        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
            header("Location: index.php?page=transactions&action=create");
            exit;
        }
    }
}

// Get machines for dropdown with brand information
try {
    $stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        WHERE m.status IN ('Active', 'Maintenance')
        ORDER BY CAST(m.machine_number AS UNSIGNED)
    ");
    $machines = $stmt->fetchAll();
} catch (PDOException $e) {
    $machines = [];
    // No need to set $error here, as flash messages handle display
}

// Get transaction types for dropdown
try {
    $stmt = $conn->query("SELECT id, name, category FROM transaction_types ORDER BY category, name");
    $transaction_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $transaction_types = [];
    // No need to set $error here, as flash messages handle display
}
?>

<div class="transaction-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add New Transaction</h3>
        </div>
        <div class="card-body">

            <!-- Operation Day Notice -->
            <div class="alert alert-info">
                <strong>📅 Current Operation Day:</strong> <?php echo format_date($operation_date); ?>
                <br><small>This transaction will be recorded for the above operation day. Only administrators can change the operation day.</small>
            </div>

            <form action="<?php echo $_SERVER['PHP_SELF'] ?>?page=transactions&action=create" method="POST" id="transactionCreateForm">
                <!-- Transaction Details Section -->
                <div class="form-section">
                    <h4>Transaction Details</h4>
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
                                            <?php if ($machine['brand_name']): ?>
                                                (<?php echo htmlspecialchars($machine['brand_name']); ?>)
                                            <?php endif; ?>
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
                                <label for="timestamp">Date & Time *</label>
                                <input type="datetime-local" id="timestamp" name="timestamp" class="form-control"
                                       value="<?php echo htmlspecialchars($transaction['timestamp'] ?? cairo_time('Y-m-d\TH:i')); ?>"
                                       required>
                                <small class="form-text">Actual timestamp when transaction occurred</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="operation_date">Operation Date *</label>
                                <input type="date" id="operation_date" name="operation_date" class="form-control"
                                       value="<?php echo htmlspecialchars($transaction['operation_date']); ?>"
                                       required readonly>
                                <small class="form-text">Casino operation day (set by administrator)</small>
                            </div>
                        </div>
                        <div class="col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="form-section">
                    <h4>Additional Information</h4>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional notes about this transaction..."><?php echo htmlspecialchars($transaction['notes']); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                    <a href="index.php?page=transactions" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/transactions_create.js"></script>

<?php
/**
 * Edit Transaction
 */

// Capture messages from URL
$display_message = '';
$display_error = '';

if (isset($_GET['message'])) {
    $display_message = htmlspecialchars($_GET['message']);
}
if (isset($_GET['error'])) {
    $display_error = htmlspecialchars($_GET['error']);
}

// Ensure user has edit permissions
if (!$can_edit) {
    header("Location: index.php?page=transactions&error=" . urlencode("Access denied"));
    exit;
}

// Check if an ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=transactions");
    exit;
}

$transaction_id = $_GET['id'];
$error = ''; // This variable will no longer be used for display, but might be for internal logic
$success = false; // This variable will no longer be used for display, but might be for internal logic

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
        header("Location: index.php?page=transactions&error=" . urlencode("Transaction not found"));
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php?page=transactions&error=" . urlencode("Database error"));
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

// Check if user is admin (for operation date editing)
$is_admin = ($_SESSION['user_role'] === 'admin');

// Capture filter parameters from the URL for redirection
$redirect_params = [];
$allowed_filter_keys = ['machine', 'date_range_type', 'date_from', 'date_to', 'month', 'category', 'transaction_type', 'sort', 'order'];
foreach ($allowed_filter_keys as $key) {
    if (isset($_GET[$key])) {
        $redirect_params[$key] = $_GET[$key];
    }
}
$redirect_query_string = http_build_query($redirect_params);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $machine_id = sanitize_input($_POST['machine_id'] ?? '');
    $transaction_type_id = sanitize_input($_POST['transaction_type_id'] ?? '');
    $amount = sanitize_input($_POST['amount'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    $timestamp = sanitize_input($_POST['timestamp'] ?? '');
    $operation_date = sanitize_input($_POST['operation_date'] ?? '');

    // Validate required fields
    if (empty($machine_id) || empty($transaction_type_id) || empty($amount) || empty($timestamp)) {
        $error = "Please fill out all required fields.";
    } elseif ($is_admin && empty($operation_date)) {
        $error = "Operation date is required.";
    } else {
        try {
            // Prepare update query based on user role
            if ($is_admin) {
                // Admin can update operation_date
                $stmt = $conn->prepare("
                    UPDATE transactions SET
                        machine_id = ?,
                        transaction_type_id = ?,
                        amount = ?,
                        notes = ?,
                        timestamp = ?,
                        operation_date = ?,
                        edited_by = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $machine_id,
                    $transaction_type_id,
                    $amount,
                    $notes ?: null,
                    $timestamp,
                    $operation_date,
                    $_SESSION['user_id'], // Set edited_by to current user
                    $transaction_id
                ]);
            } else {
                // Non-admin cannot update operation_date
                $stmt = $conn->prepare("
                    UPDATE transactions SET
                        machine_id = ?,
                        transaction_type_id = ?,
                        amount = ?,
                        notes = ?,
                        timestamp = ?,
                        edited_by = ?,
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $machine_id,
                    $transaction_type_id,
                    $amount,
                    $notes ?: null,
                    $timestamp,
                    $_SESSION['user_id'], // Set edited_by to current user
                    $transaction_id
                ]);
            }

            if ($result) {
                $log_details = "Updated transaction ID: {$transaction_id}";
                if ($is_admin && $operation_date !== $transaction['operation_date']) {
                    $log_details .= " (Operation date changed from {$transaction['operation_date']} to {$operation_date})";
                }
                log_action('update_transaction', $log_details);
                $success = true;

                // Redirect after successful update
                if (!headers_sent()) { // Ensure headers haven't been sent already
                    header("Location: index.php?page=transactions&message=" . urlencode("Transaction updated successfully") . "&{$redirect_query_string}");
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
            <?php if (!empty($display_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($display_message); ?></div>
            <?php elseif (!empty($display_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($display_error); ?></div>
            <?php endif; ?>

            <?php if ($is_admin): ?>
                <div class="alert alert-info">
                    <strong>👑 Admin Privileges:</strong> You can modify both the timestamp and operation date for this transaction.
                </div>
            <?php endif; ?>

            <form method="POST" class="transaction-form" id="transactionEditForm">
                <!-- Transaction Details Section -->
                <div class="form-section">
                    <h4>Transaction Details</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="timestamp">Date & Time *</label>
                                <input type="datetime-local" id="timestamp" name="timestamp" class="form-control"
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($transaction['timestamp'])); ?>" required>
                                <small class="form-text">Actual timestamp when transaction occurred</small>
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
                                    <optgroup label="OUT">
                                        <?php foreach ($transaction_types as $type): ?>
                                            <?php if ($type['category'] == 'OUT'): ?>
                                                <option value="<?php echo $type['id']; ?>" <?php echo $type['id'] == $transaction['transaction_type_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="DROP">
                                        <?php foreach ($transaction_types as $type): ?>
                                            <?php if ($type['category'] == 'DROP'): ?>
                                                <option value="<?php echo $type['id']; ?>" <?php echo $type['id'] == $transaction['transaction_type_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
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
                    
                    <!-- Operation Date Section - Only for Admins -->
                    <?php if ($is_admin): ?>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="operation_date">Operation Date *</label>
                                    <input type="date" id="operation_date" name="operation_date" class="form-control" data-original-date="<?php echo htmlspecialchars($transaction['operation_date'] ?? date('Y-m-d')); ?>" 
                                           value="<?php echo htmlspecialchars($transaction['operation_date'] ?? date('Y-m-d')); ?>" required>
                                    <small class="form-text">Casino operation day (admin only)</small>
                                </div>
                            </div>
                            <div class="col">
                                <!-- Empty column for layout balance -->
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Show operation date as read-only for non-admins -->
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="operation_date_display">Operation Date</label>
                                    <input type="text" id="operation_date_display" class="form-control" 
                                           value="<?php echo format_date($transaction['operation_date'] ?? date('Y-m-d')); ?>" readonly>
                                    <small class="form-text">Casino operation day (admin only can modify)</small>
                                </div>
                            </div>
                            <div class="col">
                                <!-- Empty column for layout balance -->
                            </div>
                        </div>
                    <?php endif; ?>
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
                    <a href="index.php?page=transactions&<?php echo $redirect_query_string; ?>" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="url-cleaner-data" 
     data-display-message="<?= !empty($display_message) ? 'true' : 'false' ?>" 
     data-display-error="<?= !empty($display_error) ? 'true' : 'false' ?>">
</div>
<script type="module" src="assets/js/url_cleaner.js"></script>
<script type="module" src="assets/js/transactions_edit.js"></script>
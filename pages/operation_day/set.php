<?php
/**
 * Set New Operation Day
 */

$error = '';
$success = '';
$operation_date = date('Y-m-d'); // Default to today

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operation_date = sanitize_input($_POST['operation_date'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Validate required fields
    if (empty($operation_date)) {
        $error = "Operation date is required.";
    } else {
        try {
            // Get current user info
            $user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $user_stmt->execute([$_SESSION['user_id']]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Insert new operation day
            $stmt = $conn->prepare("
                INSERT INTO operation_day (operation_date, set_by_user_id, set_by_username, notes, created_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $operation_date,
                $_SESSION['user_id'],
                $user['username'],
                $notes ?: null,
                date('Y-m-d H:i:s')
            ]);
            
            // Log action
            log_action('set_operation_day', "Set operation day to: $operation_date");
            
            $success = "Operation day has been set successfully!";
            
            // Redirect after 2 seconds
            header("refresh:2;url=index.php?page=operation_day");
            
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get current operation day for reference
try {
    $current_stmt = $conn->prepare("SELECT operation_date FROM operation_day ORDER BY id DESC LIMIT 1");
    $current_stmt->execute();
    $current = $current_stmt->fetch(PDO::FETCH_ASSOC);
    $current_operation_date = $current ? $current['operation_date'] : date('Y-m-d');
} catch (PDOException $e) {
    $current_operation_date = date('Y-m-d');
}
?>

<div class="operation-day-set fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Set Operation Day</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br><small>Redirecting to operation day overview...</small>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <strong>ℹ️ Current Operation Day:</strong> <?php echo format_date($current_operation_date); ?>
                <br><small>Setting a new operation day will affect all new transactions created after this change.</small>
            </div>

            <form method="POST" class="operation-day-form">
                <!-- Operation Day Information -->
                <div class="form-section">
                    <h4>Operation Day Settings</h4>
                    
                    <div class="form-group">
                        <label for="operation_date">Operation Date *</label>
                        <input type="date" id="operation_date" name="operation_date" class="form-control" 
                               value="<?php echo htmlspecialchars($operation_date); ?>" required>
                        <small class="form-text">
                            This date will be used for all new transactions as the operation day, 
                            separate from the actual timestamp when the transaction was recorded.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" 
                                  placeholder="Optional notes about this operation day change..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Warning Section -->
                <div class="form-section">
                    <div class="alert alert-warning">
                        <h5>⚠️ Important Notice</h5>
                        <ul>
                            <li>This operation day will be applied to all <strong>new transactions</strong> created after this change</li>
                            <li>Existing transactions will <strong>not</strong> be affected</li>
                            <li>All users will see this operation day when creating new transactions</li>
                            <li>Reports and statistics will use the operation date for grouping and filtering</li>
                        </ul>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Set Operation Day</button>
                    <a href="index.php?page=operation_day" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focus on the date input
    document.getElementById('operation_date').focus();
    
    // Add confirmation for setting operation day
    document.querySelector('.operation-day-form').addEventListener('submit', function(e) {
        const selectedDate = document.getElementById('operation_date').value;
        const currentDate = '<?php echo $current_operation_date; ?>';
        
        if (selectedDate !== currentDate) {
            const confirmMessage = `Are you sure you want to set the operation day to ${selectedDate}?\n\nThis will affect all new transactions created after this change.`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
<?php
/**
 * Edit Daily Tracking Entry
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

// Check if an ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=daily_tracking&error=" . urlencode("Invalid daily tracking ID"));
    exit;
}

$tracking_id = $_GET['id'];
$error = ''; // This variable will no longer be used for display, but might be for internal logic
$success = ''; // This variable will no longer be used for display, but might be for internal logic

// Get current tracking data
try {
    $stmt = $conn->prepare("SELECT * FROM daily_tracking WHERE id = ?");
    $stmt->execute([$tracking_id]);
    $tracking_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tracking_data) {
        header("Location: index.php?page=daily_tracking&error=" . urlencode("Daily tracking entry not found"));
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php?page=daily_tracking&error=" . urlencode("Database error"));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking_date = sanitize_input($_POST['tracking_date'] ?? '');
    $slots_drop = sanitize_input($_POST['slots_drop'] ?? '');
    $slots_out = sanitize_input($_POST['slots_out'] ?? '');
    $gambee_drop = sanitize_input($_POST['gambee_drop'] ?? '');
    $gambee_out = sanitize_input($_POST['gambee_out'] ?? '');
    $coins_drop = sanitize_input($_POST['coins_drop'] ?? '');
    $coins_out = sanitize_input($_POST['coins_out'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($tracking_date)) {
        $error = "Tracking date is required.";
    } else {
        try {
            // Check if another entry exists for this date (excluding current entry)
            $check_stmt = $conn->prepare("SELECT id FROM daily_tracking WHERE tracking_date = ? AND id != ?");
            $check_stmt->execute([$tracking_date, $tracking_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Another daily tracking entry already exists for this date.";
            } else {
                // Convert empty strings to 0 for numeric fields
                $slots_drop = empty($slots_drop) ? 0 : floatval($slots_drop);
                $slots_out = empty($slots_out) ? 0 : floatval($slots_out);
                $gambee_drop = empty($gambee_drop) ? 0 : floatval($gambee_drop);
                $gambee_out = empty($gambee_out) ? 0 : floatval($gambee_out);
                $coins_drop = empty($coins_drop) ? 0 : floatval($coins_drop);
                $coins_out = empty($coins_out) ? 0 : floatval($coins_out);

                // Calculate results and percentages
                $slots_result = $slots_drop - $slots_out;
                $gambee_result = $gambee_drop - $gambee_out;
                $coins_result = $coins_drop - $coins_out;
                
                $slots_percentage = $slots_drop > 0 ? (($slots_result / $slots_drop) * 100) : 0;
                $gambee_percentage = $gambee_drop > 0 ? (($gambee_result / $gambee_drop) * 100) : 0;
                $coins_percentage = $coins_drop > 0 ? (($coins_result / $coins_drop) * 100) : 0;
                
                $total_drop = $slots_drop + $gambee_drop + $coins_drop;
                $total_out = $slots_out + $gambee_out + $coins_out;
                $total_result = $total_drop - $total_out;
                $total_result_percentage = $total_drop > 0 ? (($total_result / $total_drop) * 100) : 0;

                // Update daily tracking entry
                $stmt = $conn->prepare("
                    UPDATE daily_tracking SET
                        tracking_date = ?, slots_drop = ?, slots_out = ?, gambee_drop = ?, gambee_out = ?, coins_drop = ?, coins_out = ?, notes = ?, updated_by = ?, updated_at = ?
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $tracking_date,
                    $slots_drop, $slots_out, $gambee_drop, $gambee_out, $coins_drop, $coins_out, $notes ?: null,
                    $_SESSION['user_id'],
                    date('Y-m-d H:i:s'),
                    $tracking_id
                ]);

                if ($result) {
                    // Log action
                    log_action('update_daily_tracking', "Updated daily tracking entry for: {$tracking_date}");
                    header("Location: index.php?page=daily_tracking&message=" . urlencode("Daily tracking entry updated successfully"));
                    exit;
                } else {
                    $error = "Failed to update daily tracking entry.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="daily-tracking-edit fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Edit Daily Tracking Entry</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($display_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($display_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($display_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($display_message); ?></div>
            <?php endif; ?>

            <div class="alert alert-info">
                <strong>📊 Daily Tracking:</strong> Edit the daily performance data for each machine type. Results and percentages will be calculated automatically.
            </div>

            <form method="POST" class="daily-tracking-form" id="dailyTrackingEditForm">
                <!-- Date Section -->
                <div class="form-section">
                    <h4>Tracking Information</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="tracking_date">Tracking Date *</label>
                                <input type="date" id="tracking_date" name="tracking_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($tracking_data['tracking_date']); ?>" required>
                            </div>
                        </div>
                        <div class="col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                </div>

                <!-- Slots Section -->
                <div class="form-section">
                    <h4>Slots Performance</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="slots_drop">Slots Drop</label>
                                <input type="number" id="slots_drop" name="slots_drop" class="form-control" 
                                       value="<?php echo htmlspecialchars($tracking_data['slots_drop']); ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="slots_out">Slots Out</label>
                                <input type="number" id="slots_out" name="slots_out" class="form-control" 
                                       value="<?php echo htmlspecialchars($tracking_data['slots_out']); ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gambee Section -->
                <div class="form-section">
                    <h4>Gambee Performance</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="gambee_drop">Gambee Drop</label>
                                <input type="number" id="gambee_drop" name="gambee_drop" class="form-control" 
                                       value="<?php echo htmlspecialchars($tracking_data['gambee_drop']); ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="gambee_out">Gambee Out</label>
                                <input type="number" id="gambee_out" name="gambee_out" class="form-control" 
                                       value="<?php echo htmlspecialchars($tracking_data['gambee_out']); ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coins Section -->
                <div class="form-section">
                    <h4>Coins Performance</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="coins_drop">Coins Drop</label>
                                <input type="number" id="coins_drop" name="coins_drop" class="form-control" 
                                       value="<?php echo htmlspecialchars($tracking_data['coins_drop']); ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="coins_out">Coins Out</label>
                                <input type="number" id="coins_out" name="coins_out" class="form-control" 
                                       value="<?php echo htmlspecialchars($tracking_data['coins_out']); ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="form-section">
                    <h4>Additional Information</h4>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" 
                                  placeholder="Optional notes about this day's performance..."><?php echo htmlspecialchars($tracking_data['notes']); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Daily Tracking</button>
                    <a href="index.php?page=daily_tracking" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/daily_tracking_edit.js"></script>
<?php
// JavaScript to clear URL parameters
if (!empty($display_message) || !empty($display_error)) {
    echo "<script type='text/javascript'>
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/&?(message|error)=[^&]*/g, ''));
    </script>";
}
?>

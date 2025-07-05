<?php
/**
 * Create Daily Tracking Entry
 */

// Get current operation day for default date
try {
    $op_stmt = $conn->prepare("SELECT operation_date FROM operation_day ORDER BY id DESC LIMIT 1");
    $op_stmt->execute();
    $current_operation_day = $op_stmt->fetch(PDO::FETCH_ASSOC);
    $default_date = $current_operation_day ? $current_operation_day['operation_date'] : date('Y-m-d');
} catch (PDOException $e) {
    $default_date = date('Y-m-d');
}

$tracking_data = [
    'tracking_date' => $default_date,
    'slots_drop' => '',
    'slots_out' => '',
    'gambee_drop' => '',
    'gambee_out' => '',
    'coins_drop' => '',
    'coins_out' => '',
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $tracking_data['tracking_date'] = sanitize_input($_POST['tracking_date'] ?? '');
    $tracking_data['slots_drop'] = sanitize_input($_POST['slots_drop'] ?? '');
    $tracking_data['slots_out'] = sanitize_input($_POST['slots_out'] ?? '');
    $tracking_data['gambee_drop'] = sanitize_input($_POST['gambee_drop'] ?? '');
    $tracking_data['gambee_out'] = sanitize_input($_POST['gambee_out'] ?? '');
    $tracking_data['coins_drop'] = sanitize_input($_POST['coins_drop'] ?? '');
    $tracking_data['coins_out'] = sanitize_input($_POST['coins_out'] ?? '');
    $tracking_data['notes'] = sanitize_input($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($tracking_data['tracking_date'])) {
        set_flash_message('danger', "Tracking date is required.");
        header("Location: index.php?page=daily_tracking&action=create");
        exit;
    } else {
        try {
            // Check if entry already exists for this date
            $check_stmt = $conn->prepare("SELECT id FROM daily_tracking WHERE tracking_date = ?");
            $check_stmt->execute([$tracking_data['tracking_date']]);
            
            if ($check_stmt->rowCount() > 0) {
                set_flash_message('danger', "Daily tracking entry already exists for this date. Please edit the existing entry or choose a different date.");
                header("Location: index.php?page=daily_tracking&action=create");
                exit;
            } else {
                // Convert empty strings to 0 for numeric fields
                $slots_drop = empty($tracking_data['slots_drop']) ? 0 : floatval($tracking_data['slots_drop']);
                $slots_out = empty($tracking_data['slots_out']) ? 0 : floatval($tracking_data['slots_out']);
                $gambee_drop = empty($tracking_data['gambee_drop']) ? 0 : floatval($tracking_data['gambee_drop']);
                $gambee_out = empty($tracking_data['gambee_out']) ? 0 : floatval($tracking_data['gambee_out']);
                $coins_drop = empty($tracking_data['coins_drop']) ? 0 : floatval($tracking_data['coins_drop']);
                $coins_out = empty($tracking_data['coins_out']) ? 0 : floatval($tracking_data['coins_out']);

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

                // Insert new daily tracking entry
                $stmt = $conn->prepare("
                    INSERT INTO daily_tracking (
                        tracking_date, slots_drop, slots_out, gambee_drop, gambee_out, coins_drop, coins_out, 
                        notes, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $tracking_data['tracking_date'],
                    $slots_drop, $slots_out, $gambee_drop, $gambee_out, $coins_drop, $coins_out, $tracking_data['notes'] ?: null,
                    $_SESSION['user_id'],
                    date('Y-m-d H:i:s')
                ]);

                // Log action
                log_action('create_daily_tracking', "Created daily tracking entry for: {$tracking_data['tracking_date']}");

                set_flash_message('success', "Daily tracking entry created successfully!");
                header("Location: index.php?page=daily_tracking");
                exit;
            }
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
            header("Location: index.php?page=daily_tracking&action=create");
            exit;
        }
    }
}
?>

<div class="daily-tracking-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add Daily Tracking Entry</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>📊 Daily Tracking:</strong> Enter the daily performance data for each machine type. Results and percentages will be calculated automatically.
            </div>

            <form method="POST" class="daily-tracking-form" id="dailyTrackingCreateForm">
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
                    <button type="submit" class="btn btn-primary">Save Daily Tracking</button>
                    <a href="index.php?page=daily_tracking" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/daily_tracking_create.js"></script>

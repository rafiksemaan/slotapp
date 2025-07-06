<?php
/**
 * Edit Meter Entry
 */

// Check if an ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', "Invalid meter ID.");
    header("Location: index.php?page=meters");
    exit;
}

$meter_id = $_GET['id'];

// Get current meter data
try {
    $stmt = $conn->prepare("
        SELECT me.*, m.machine_number, m.credit_value, m.system_comp, mt.name AS machine_type_name
        FROM meters me
        JOIN machines m ON me.machine_id = m.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        WHERE me.id = ?
    ");
    $stmt->execute([$meter_id]);
    $meter_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meter_data) {
        set_flash_message('danger', "Meter entry not found.");
        header("Location: index.php?page=meters");
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . $e->getMessage());
    header("Location: index.php?page=meters");
    exit;
}

// Get machines for dropdown with brand information
try {
    $stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name, m.system_comp, mt.name AS machine_type
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        ORDER BY CAST(m.machine_number AS UNSIGNED) ASC
    ");
    $raw_machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group machines by type for optgroup
    $grouped_machines = [];
    foreach ($raw_machines as $machine_option) {
        $type = $machine_option['machine_type'];
        if (!isset($grouped_machines[$type])) {
            $grouped_machines[$type] = [];
        }
        $grouped_machines[$type][] = $machine_option;
    }
} catch (PDOException $e) {
    $grouped_machines = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $machine_id = sanitize_input($_POST['machine_id'] ?? '');
    $operation_date = sanitize_input($_POST['operation_date'] ?? '');
    $manual_reading_notes = sanitize_input($_POST['manual_reading_notes'] ?? ''); // Re-added
    $notes = sanitize_input($_POST['notes'] ?? '');
    $is_initial_reading = isset($_POST['is_initial_reading']) ? 1 : 0; // Capture checkbox value

    // Fetch machine details to determine meter type and expected fields for submission
    $machine_details_for_submit = null;
    if (!empty($machine_id)) {
        $stmt = $conn->prepare("SELECT m.system_comp, mt.name AS machine_type FROM machines m JOIN machine_types mt ON m.type_id = mt.id WHERE m.id = ?");
        $stmt->execute([$machine_id]);
        $machine_details_for_submit = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (empty($machine_id) || empty($operation_date) || !$machine_details_for_submit) {
        set_flash_message('danger', "Machine and operation date are required.");
    } else {
        $system_comp = $machine_details_for_submit['system_comp'];
        $machine_type_name = $machine_details_for_submit['machine_type'];
        $meter_type = '';

        if ($system_comp === 'online') {
            $meter_type = 'online';
        } elseif ($system_comp === 'offline') {
            if ($machine_type_name === 'COINS') {
                $meter_type = 'coins';
            } else { // CASH or GAMBEE
                $meter_type = 'offline';
            }
        }

        // Initialize all fields to null for the UPDATE statement
        $total_in = $total_out = $bills_in = $ticket_in = $ticket_out = $jp = $bets = $handpay = $coins_in = $coins_out = $coins_drop = null;

        // Populate fields based on determined meter_type from submission
        if ($meter_type === 'coins') {
            $coins_in = empty($_POST['coins_in']) ? null : floatval($_POST['coins_in']);
            $coins_out = empty($_POST['coins_out']) ? null : floatval($_POST['coins_out']);
            $coins_drop = empty($_POST['coins_drop']) ? null : floatval($_POST['coins_drop']);
            $bets = empty($_POST['bets_coins']) ? null : floatval($_POST['bets_coins']);
            $handpay = empty($_POST['handpay_coins']) ? null : floatval($_POST['handpay_coins']);
        } elseif ($meter_type === 'offline') { // For offline CASH/GAMBEE
            $total_in = empty($_POST['total_in']) ? null : floatval($_POST['total_in']);
            $total_out = empty($_POST['total_out']) ? null : floatval($_POST['total_out']);
            $bills_in = empty($_POST['bills_in']) ? null : floatval($_POST['bills_in']);
            $handpay = empty($_POST['handpay_cash_gambee']) ? null : floatval($_POST['handpay_cash_gambee']);
            $jp = empty($_POST['jp']) ? null : floatval($_POST['jp']);
        }
        // Note: 'online' meter_type is handled by upload, not manual form.

        try {
            $stmt = $conn->prepare("
                UPDATE meters SET
                    machine_id = ?, operation_date = ?, meter_type = ?, 
                    total_in = ?, total_out = ?, bills_in = ?, ticket_in = ?, ticket_out = ?, jp = ?, bets = ?, handpay = ?, 
                    coins_in = ?, coins_out = ?, coins_drop = ?, 
                    manual_reading_notes = ?, notes = ?, is_initial_reading = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $machine_id,
                $operation_date,
                $meter_type,
                $total_in,
                $total_out,
                $bills_in,
                $ticket_in,
                $ticket_out,
                $jp,
                $bets,
                $handpay,
                $coins_in,
                $coins_out,
                $coins_drop,
                $manual_reading_notes ?: null, // Re-added
                $notes ?: null,
                $is_initial_reading, // New field
                $_SESSION['user_id'],
                $meter_id
            ]);

            if ($result) {
                log_action('update_meter', "Updated meter entry ID: {$meter_id} for machine {$machine_id} on {$operation_date}");
                set_flash_message('success', "Meter entry updated successfully.");
                header("Location: index.php?page=meters");
                exit;
            } else {
                set_flash_message('danger', "Failed to update meter entry.");
            }
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
        }
    }
}
?>

<div class="meter-edit fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Edit Meter Entry</h3>
        </div>
        <div class="card-body">
            <form method="POST" class="meter-form" id="meterEditForm">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h4>Basic Information</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="machine_id">Machine *</label>
                                <select id="machine_id" name="machine_id" class="form-control" required>
                                    <option value="">Select Machine</option>
                                    <?php foreach ($grouped_machines as $type_name => $machines_in_group): ?>
                                        <optgroup label="<?= htmlspecialchars($type_name) ?> Machines">
                                            <?php foreach ($machines_in_group as $machine_option): ?>
                                                <option value="<?php echo $machine_option['id']; ?>"
                                                        data-system-comp="<?php echo htmlspecialchars($machine_option['system_comp']); ?>"
                                                        data-machine-type="<?php echo htmlspecialchars($machine_option['machine_type']); ?>"
                                                        <?php echo $meter_data['machine_id'] == $machine_option['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($machine_option['machine_number']); ?>
                                                    <?php if ($machine_option['brand_name']): ?>
                                                        (<?php echo htmlspecialchars($machine_option['brand_name']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="operation_date">Operation Date *</label>
                                <input type="date" id="operation_date" name="operation_date" class="form-control"
                                       value="<?php echo htmlspecialchars($meter_data['operation_date']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="is_initial_reading" name="is_initial_reading" class="form-check-input" <?php echo $meter_data['is_initial_reading'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_initial_reading">This is the initial meter reading for this machine</label>
                    </div>
                </div>

                <!-- Dynamic Meter Fields Section -->
                <div class="form-section" id="dynamicMeterFields">
                    <h4>Meter Readings</h4>
				<div id="cashGambeeMeterFields" style="display: none;">
					<div class="row">
						<div class="col">
							<div class="form-group">
								<label for="total_in">Total In</label>
								<input type="number" id="total_in" name="total_in" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['total_in'] ?? ''); ?>">
							</div>
						</div>
						<div class="col">
							<div class="form-group">
								<label for="total_out">Total Out</label>
								<input type="number" id="total_out" name="total_out" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['total_out'] ?? ''); ?>">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<div class="form-group">
								<label for="bills_in">Bills In</label>
								<input type="number" id="bills_in" name="bills_in" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['bills_in'] ?? ''); ?>">
							</div>
						</div>
						<div class="col">
							<div class="form-group">
								<label for="handpay_cash_gambee">Handpay</label>
								<input type="number" id="handpay_cash_gambee" name="handpay_cash_gambee" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['handpay'] ?? ''); ?>">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<div class="form-group">
								<label for="jp">JP</label>
								<input type="number" id="jp" name="jp" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['jp'] ?? ''); ?>">
							</div>
						</div>
						<div class="col">
							<!-- Empty for layout balance -->
						</div>
					</div>
				</div>

				<div id="coinsMachineMeterFields" style="display: none;">
					<div class="row">
						<div class="col">
							<div class="form-group">
								<label for="coins_in">Coins In</label>
								<input type="number" id="coins_in" name="coins_in" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['coins_in'] ?? ''); ?>">
							</div>
						</div>
						<div class="col">
							<div class="form-group">
								<label for="coins_out">Coins Out</label>
								<input type="number" id="coins_out" name="coins_out" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['coins_out'] ?? ''); ?>">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<div class="form-group">
								<label for="coins_drop">Coins Drop</label>
								<input type="number" id="coins_drop" name="coins_drop" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['coins_drop'] ?? ''); ?>">
							</div>
						</div>
						<div class="col">
							<div class="form-group">
								<label for="bets_coins">Bets</label>
								<input type="number" id="bets_coins" name="bets_coins" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['bets'] ?? ''); ?>">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<div class="form-group">
								<label for="handpay_coins">Handpay</label>
								<input type="number" id="handpay_coins" name="handpay_coins" class="form-control" step="1" min="0"
									   value="<?php echo htmlspecialchars($meter_data['handpay'] ?? ''); ?>">
							</div>
						</div>
						<div class="col">
							<!-- Empty for layout balance -->
						</div>
					</div>
				</div>
                </div>

                <!-- Manual Reading Notes Section (applies to all offline machines) -->
                <div class="form-section" id="offlineMachineStatusSection" style="display: none;">
                    <h4>Offline Machine Details</h4>
                    <div class="form-group">
                        <label for="manual_reading_notes">Manual Reading Notes</label>
                        <textarea id="manual_reading_notes" name="manual_reading_notes" class="form-control" rows="3"
                                  placeholder="Notes for manual meter readings on offline machines..."><?php echo htmlspecialchars($meter_data['manual_reading_notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="form-section">
                    <h4>Additional Information</h4>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional notes about this meter entry..."><?php echo htmlspecialchars($meter_data['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Meter</button>
                    <a href="index.php?page=meters" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/meters_edit.js"></script>

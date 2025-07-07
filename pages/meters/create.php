<?php
/**
 * Create new meter entry
 */

// Get current operation day
try {
    $op_stmt = $conn->prepare("SELECT operation_date FROM operation_day ORDER BY id DESC LIMIT 1");
    $op_stmt->execute();
    $current_operation_day = $op_stmt->fetch(PDO::FETCH_ASSOC);
    $operation_date = $current_operation_day ? $current_operation_day['operation_date'] : date('Y-m-d');
} catch (PDOException $e) {
    $operation_date = date('Y-m-d');
}

// Initialize meter data
$meter = [
    'machine_id' => '',
    'operation_date' => $operation_date,
    'total_in' => '',
    'total_out' => '',
    'bills_in' => '',
    'coins_in' => '',
    'coins_out' => '',
    'coins_drop' => '',
    'bets' => '', // Generic bets field
    'handpay' => '', // Generic handpay field
    'jp' => '',
    'notes' => '',
    'is_initial_reading' => false // Initialize new field
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input using get_input
    $meter['machine_id'] = get_input(INPUT_POST, 'machine_id', 'int');
    $meter['operation_date'] = get_input(INPUT_POST, 'operation_date', 'string', $operation_date);
    $meter['notes'] = get_input(INPUT_POST, 'notes', 'string');
    $meter['is_initial_reading'] = get_input(INPUT_POST, 'is_initial_reading', 'bool', false);

    // Fetch machine details to determine meter type and expected fields
    $machine_details = null;
    if (!empty($meter['machine_id'])) {
        $stmt = $conn->prepare("SELECT m.system_comp, mt.name AS machine_type FROM machines m JOIN machine_types mt ON m.type_id = mt.id WHERE m.id = ?");
        $stmt->execute([$meter['machine_id']]);
        $machine_details = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (empty($meter['machine_id']) || empty($meter['operation_date']) || !$machine_details) {
        set_flash_message('danger', "Machine and operation date are required.");
        // No redirect here, allow form to re-render with error
    } else {
        $system_comp = $machine_details['system_comp'];
        $machine_type_name = $machine_details['machine_type'];
        $meter_type = '';

        // Determine meter_type for database
        if ($system_comp === 'online') {
            $meter_type = 'online'; // This branch should ideally not be reached if dropdown is filtered
        } elseif ($system_comp === 'offline') {
            if ($machine_type_name === 'COINS') {
                $meter_type = 'coins';
            } else { // CASH or GAMBEE
                $meter_type = 'offline';
            }
        }

        // Initialize all fields to null for the INSERT statement
        $total_in = $total_out = $bills_in = $ticket_in = $ticket_out = $jp = $bets = $handpay = $coins_in = $coins_out = $coins_drop = null;

        // Populate fields based on determined meter_type
        if ($meter_type === 'coins') {
            $coins_in = get_input(INPUT_POST, 'coins_in', 'int');
            $coins_out = get_input(INPUT_POST, 'coins_out', 'int');
            $coins_drop = get_input(INPUT_POST, 'coins_drop', 'int');
            $bets = get_input(INPUT_POST, 'bets_coins', 'int');
            $handpay = get_input(INPUT_POST, 'handpay_coins', 'int');
        } elseif ($meter_type === 'offline') { // For offline CASH/GAMBEE
            $total_in = get_input(INPUT_POST, 'total_in', 'int');
            $total_out = get_input(INPUT_POST, 'total_out', 'int');
            $bills_in = get_input(INPUT_POST, 'bills_in', 'int');
            $handpay = get_input(INPUT_POST, 'handpay_cash_gambee', 'int');
            $jp = get_input(INPUT_POST, 'jp', 'int');
        }
        // Note: 'online' meter_type is handled by upload, not manual.

        try {
            // Insert new meter entry
            $stmt = $conn->prepare("
                INSERT INTO meters (
                    machine_id, operation_date, meter_type, 
                    total_in, total_out, bills_in, ticket_in, ticket_out, jp, bets, handpay, 
                    coins_in, coins_out, coins_drop, 
                    notes, is_initial_reading, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $meter['machine_id'],
                $meter['operation_date'],
                $meter_type,
                $total_in,
                $total_out,
                $bills_in,
                $ticket_in, // Always null for manual offline entries
                $ticket_out, // Always null for manual offline entries
                $jp,
                $bets,
                $handpay,
                $coins_in,
                $coins_out,
                $coins_drop,
                $meter['notes'] ?: null,
                $meter['is_initial_reading'], // New field
                $_SESSION['user_id']
            ]);
            
            // Log action
            log_action('create_meter', "Created meter entry for machine ID: {$meter['machine_id']}, Type: {$meter_type}");
            
            set_flash_message('success', "Meter entry created successfully!");
            
            // Clear machine_id to deselect the machine in the dropdown
            $meter['machine_id'] = '';
            // The page will naturally re-render with the updated $meter array and flash message.
            
        } catch (PDOException $e) {
            // Check for duplicate entry error (SQLSTATE 23000 is for integrity constraint violation)
            if ($e->getCode() === '23000') {
                set_flash_message('danger', "A meter entry for this machine on this date already exists. Please edit the existing entry.");
            } else {
                set_flash_message('danger', "Database error: " . $e->getMessage());
            }
            // No redirect here, allow form to re-render with error
        }
    }
}

// Get machines for dropdown with brand, system_comp, machine_type, and credit_value information
// Filter to only show 'offline' machines for manual entry
try {
    // Fetch latest meter readings for each machine
    $stmt = $conn->query("
        SELECT
            m.id,
            m.machine_number,
            b.name as brand_name,
            m.system_comp,
            mt.name AS machine_type,
            m.credit_value,
            (SELECT bills_in FROM meters WHERE machine_id = m.id AND meter_type = 'offline' ORDER BY operation_date DESC, created_at DESC LIMIT 1) AS latest_bills_in,
            (SELECT coins_drop FROM meters WHERE machine_id = m.id AND meter_type = 'coins' ORDER BY operation_date DESC, created_at DESC LIMIT 1) AS latest_coins_drop,
            (SELECT handpay FROM meters WHERE machine_id = m.id AND (meter_type = 'offline' OR meter_type = 'coins') ORDER BY operation_date DESC, created_at DESC LIMIT 1) AS latest_handpay
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        WHERE m.system_comp = 'offline' AND m.status IN ('Active', 'Maintenance')
        ORDER BY mt.name ASC, CAST(m.machine_number AS UNSIGNED) ASC
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
?>

<div class="meter-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add New Meter Entry</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>📅 Current Operation Day:</strong> <?php echo escape_html_output(format_date($operation_date)); ?>
                <br><small>This meter entry will be recorded for the above operation day.</small>
            </div>
            
            <form action="index.php?page=meters&action=create" method="POST" id="meterCreateForm">
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
                                        <optgroup label="<?= escape_html_output($type_name) ?> Machines">
                                            <?php foreach ($machines_in_group as $machine_option): ?>
                                                <option value="<?php echo escape_html_output($machine_option['id']); ?>"
                                                        data-system-comp="<?php echo escape_html_output($machine_option['system_comp']); ?>"
                                                        data-machine-type="<?php echo escape_html_output($machine_option['machine_type']); ?>"
                                                        data-latest-bills-in="<?php echo escape_html_output($machine_option['latest_bills_in'] ?? 'N/A'); ?>"
                                                        data-latest-coins-drop="<?php echo escape_html_output($machine_option['latest_coins_drop'] ?? 'N/A'); ?>"
                                                        data-latest-handpay="<?php echo escape_html_output($machine_option['latest_handpay'] ?? 'N/A'); ?>"
                                                        <?php echo $meter['machine_id'] == $machine_option['id'] ? 'selected' : ''; ?>>
                                                    <?php echo escape_html_output($machine_option['machine_number']); ?>
                                                    <?php if ($machine_option['brand_name']): ?>
                                                        (<?php echo escape_html_output($machine_option['brand_name']); ?>)
                                                    <?php endif; ?>
                                                    - <?php echo format_currency($machine_option['credit_value']); ?>
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
                                       value="<?php echo escape_html_output($meter['operation_date']); ?>" required readonly>
                                <small class="form-text">Casino operation day (set by administrator)</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="is_initial_reading" name="is_initial_reading" class="form-check-input" <?php echo $meter['is_initial_reading'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_initial_reading">This is the initial meter reading for this machine</label>
                    </div>
                </div>

                <!-- Dynamic Meter Fields Section -->
                <div class="form-section" id="dynamicMeterFields">
                    <h4>Meter Readings</h4>
				<div id="cashGambeeMeterFields" style="display: none;">
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="total_in">Total In</label>
							<input type="number" id="total_in" name="total_in" class="form-control"
								   value="<?php echo escape_html_output($meter['total_in']); ?>" step="1" min="0">
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="total_out">Total Out</label>
							<input type="number" id="total_out" name="total_out" class="form-control"
								   value="<?php echo escape_html_output($meter['total_out']); ?>" step="1" min="0">
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="bills_in">Bills In</label>
							<input type="number" id="bills_in" name="bills_in" class="form-control"
								   value="<?php echo escape_html_output($meter['bills_in']); ?>" step="1" min="0">
						</div>
						<div class="latest-reading-display">
							<span class="latest-reading-label">Latest:</span>
							<span class="latest-reading-value" id="latest_bills_in">N/A</span>
						</div>
						<div class="variance-display">
							<span class="variance-label">Variance:</span>
							<span class="variance-value" id="variance_bills_in">N/A</span>
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="handpay_cash_gambee">Handpay</label>
							<input type="number" id="handpay_cash_gambee" name="handpay_cash_gambee" class="form-control"
								   value="<?php echo escape_html_output($meter['handpay']); ?>" step="1" min="0">
						</div>
						<div class="latest-reading-display">
							<span class="latest-reading-label">Latest:</span>
							<span class="latest-reading-value" id="latest_handpay_cash_gambee">N/A</span>
						</div>
						<div class="variance-display">
							<span class="variance-label">Variance:</span>
							<span class="variance-value" id="variance_handpay_cash_gambee">N/A</span>
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="jp">JP</label>
							<input type="number" id="jp" name="jp" class="form-control"
								   value="<?php echo escape_html_output($meter['jp']); ?>" step="1" min="0">
						</div>
					</div>
				</div>

				<div id="coinsMachineMeterFields" style="display: none;">
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="coins_in">Coins In</label>
							<input type="number" id="coins_in" name="coins_in" class="form-control"
								   value="<?php echo escape_html_output($meter['coins_in']); ?>" step="1" min="0">
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="coins_out">Coins Out</label>
							<input type="number" id="coins_out" name="coins_out" class="form-control"
								   value="<?php echo escape_html_output($meter['coins_out']); ?>" step="1" min="0">
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="coins_drop">Coins Drop</label>
							<input type="number" id="coins_drop" name="coins_drop" class="form-control"
								   value="<?php echo escape_html_output($meter['coins_drop']); ?>" step="1" min="0">
						</div>
						<div class="latest-reading-display">
							<span class="latest-reading-label">Latest:</span>
							<span class="latest-reading-value" id="latest_coins_drop">N/A</span>
						</div>
						<div class="variance-display">
							<span class="variance-label">Variance:</span>
							<span class="variance-value" id="variance_coins_drop">N/A</span>
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="bets_coins">Bets</label>
							<input type="number" id="bets_coins" name="bets_coins" class="form-control"
								   value="<?php echo escape_html_output($meter['bets']); ?>" step="1" min="0">
						</div>
					</div>
					<div class="meter-input-group">
						<div class="form-group current-reading-input">
							<label for="handpay_coins">Handpay</label>
							<input type="number" id="handpay_coins" name="handpay_coins" class="form-control"
								   value="<?php echo escape_html_output($meter['handpay']); ?>" step="1" min="0">
						</div>
						<div class="latest-reading-display">
							<span class="latest-reading-label">Latest:</span>
							<span class="latest-reading-value" id="latest_handpay_coins">N/A</span>
						</div>
						<div class="variance-display">
							<span class="variance-label">Variance:</span>
							<span class="variance-value" id="variance_handpay_coins">N/A</span>
						</div>
					</div>
				</div>
                </div>

                <!-- Additional Information Section -->
                <div class="form-section">
                    <h4>Additional Information</h4>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional notes about this meter entry..."><?php echo escape_html_output($meter['notes']); ?></textarea>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Meter Entry</button>
                    <a href="index.php?page=meters" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/meters_create.js"></script>


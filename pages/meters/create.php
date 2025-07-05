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
    'meter_type' => '',
    'total_in' => '',
    'total_out' => '',
    'bills_in' => '',
    'coins_in' => '',
    'coins_out' => '',
    'coins_drop' => '', // New field
    'bets_handpay' => '', // New field
    'handpay' => '', // New field
    'jp' => '', // New field
    'manual_reading_notes' => '',
    'notes' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $meter['machine_id'] = sanitize_input($_POST['machine_id'] ?? '');
    $meter['operation_date'] = sanitize_input($_POST['operation_date'] ?? $operation_date);
    $meter['meter_type'] = sanitize_input($_POST['meter_type'] ?? '');
    $meter['total_in'] = sanitize_input($_POST['total_in'] ?? '');
    $meter['total_out'] = sanitize_input($_POST['total_out'] ?? '');
    $meter['bills_in'] = sanitize_input($_POST['bills_in'] ?? '');
    $meter['coins_in'] = sanitize_input($_POST['coins_in'] ?? '');
    $meter['coins_out'] = sanitize_input($_POST['coins_out'] ?? '');
    $meter['coins_drop'] = sanitize_input($_POST['coins_drop'] ?? ''); // New field
    $meter['bets_handpay'] = sanitize_input($_POST['bets_handpay'] ?? ''); // New field
    $meter['handpay'] = sanitize_input($_POST['handpay'] ?? ''); // New field
    $meter['jp'] = sanitize_input($_POST['jp'] ?? ''); // New field
    $meter['manual_reading_notes'] = sanitize_input($_POST['manual_reading_notes'] ?? '');
    $meter['notes'] = sanitize_input($_POST['notes'] ?? '');

    // Validate required fields (basic validation, more detailed in JS)
    if (empty($meter['machine_id']) || empty($meter['operation_date']) || empty($meter['meter_type'])) {
        set_flash_message('danger', "Machine, operation date, and meter type are required.");
        header("Location: index.php?page=meters&action=create");
        exit;
    } else {
        try {
            // Convert empty strings to 0 for numeric fields
            $total_in = empty($meter['total_in']) ? 0 : floatval($meter['total_in']);
            $total_out = empty($meter['total_out']) ? 0 : floatval($meter['total_out']);
            $bills_in = empty($meter['bills_in']) ? 0 : floatval($meter['bills_in']);
            $coins_in = empty($meter['coins_in']) ? 0 : floatval($meter['coins_in']);
            $coins_out = empty($meter['coins_out']) ? 0 : floatval($meter['coins_out']);
            $coins_drop = empty($meter['coins_drop']) ? 0 : floatval($meter['coins_drop']); // New field
            $bets_handpay = empty($meter['bets_handpay']) ? 0 : floatval($meter['bets_handpay']); // New field
            $handpay = empty($meter['handpay']) ? 0 : floatval($meter['handpay']); // New field
            $jp = empty($meter['jp']) ? 0 : floatval($meter['jp']); // New field

            // Insert new meter entry
            $stmt = $conn->prepare("
                INSERT INTO meters (machine_id, operation_date, meter_type, total_in, total_out, bills_in, coins_in, coins_out, coins_drop, bets_handpay, handpay, jp, manual_reading_notes, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $meter['machine_id'],
                $meter['operation_date'],
                $meter['meter_type'],
                $total_in,
                $total_out,
                $bills_in,
                $coins_in,
                $coins_out,
                $coins_drop, // New field
                $bets_handpay, // New field
                $handpay, // New field
                $jp, // New field
                $meter['manual_reading_notes'] ?: null,
                $meter['notes'] ?: null,
                $_SESSION['user_id']
            ]);
            
            // Log action
            log_action('create_meter', "Created meter entry for machine ID: {$meter['machine_id']}, Type: {$meter['meter_type']}");
            
            set_flash_message('success', "Meter entry created successfully!");
            header("Location: index.php?page=meters");
            exit;
            
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
            header("Location: index.php?page=meters&action=create");
            exit;
        }
    }
}

// Get machines for dropdown with brand, system_comp, and machine_type information
try {
    $stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name, m.system_comp, mt.name AS machine_type
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        ORDER BY CAST(m.machine_number AS UNSIGNED) ASC
    ");
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $machines = [];
}

// Define meter types (assuming these are fixed or from a lookup table)
$meter_types = [
    'total_in' => 'Total In',
    'total_out' => 'Total Out',
    'bills_in' => 'Bills In',
    'coins_in' => 'Coins In',
    'coins_out' => 'Coins Out',
    'coins_drop' => 'Coins Drop', // New field
    'bets_handpay' => 'Bets Handpay', // New field
    'handpay' => 'Handpay', // New field
    'jp' => 'JP' // New field
];
?>

<div class="meter-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add New Meter Entry</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>📅 Current Operation Day:</strong> <?php echo format_date($operation_date); ?>
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
                                    <?php foreach ($machines as $machine_option): ?>
                                        <option value="<?php echo $machine_option['id']; ?>"
                                                data-system-comp="<?php echo htmlspecialchars($machine_option['system_comp']); ?>"
                                                data-machine-type="<?php echo htmlspecialchars($machine_option['machine_type']); ?>"
                                                <?php echo $meter['machine_id'] == $machine_option['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($machine_option['machine_number']); ?>
                                            <?php if ($machine_option['brand_name']): ?>
                                                (<?php echo htmlspecialchars($machine_option['brand_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="operation_date">Operation Date *</label>
                                <input type="date" id="operation_date" name="operation_date" class="form-control"
                                       value="<?php echo htmlspecialchars($meter['operation_date']); ?>" required readonly>
                                <small class="form-text">Casino operation day (set by administrator)</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="meter_type">Meter Type *</label>
                                <select id="meter_type" name="meter_type" class="form-control" required>
                                    <option value="">Select Meter Type</option>
                                    <?php foreach ($meter_types as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $meter['meter_type'] == $key ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <!-- Empty for layout balance -->
                        </div>
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
                                    <input type="number" id="total_in" name="total_in" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['total_in']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="total_out">Total Out</label>
                                    <input type="number" id="total_out" name="total_out" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['total_out']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="bills_in">Bills In</label>
                                    <input type="number" id="bills_in" name="bills_in" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['bills_in']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="handpay">Handpay</label>
                                    <input type="number" id="handpay" name="handpay" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['handpay']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="jp">JP</label>
                                    <input type="number" id="jp" name="jp" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['jp']); ?>" step="0.01" min="0">
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
                                    <input type="number" id="coins_in" name="coins_in" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['coins_in']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="coins_out">Coins Out</label>
                                    <input type="number" id="coins_out" name="coins_out" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['coins_out']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="coins_drop">Coins Drop</label>
                                    <input type="number" id="coins_drop" name="coins_drop" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['coins_drop']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="bets_handpay">Bets Handpay</label>
                                    <input type="number" id="bets_handpay" name="bets_handpay" class="form-control"
                                           value="<?php echo htmlspecialchars($meter['bets_handpay']); ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Offline Machine Status Section -->
                <div class="form-section" id="offlineMachineStatusSection" style="display: none;">
                    <h4>Offline Machine Details</h4>
                    <div class="form-group">
                        <label for="manual_reading_notes">Manual Reading Notes</label>
                        <textarea id="manual_reading_notes" name="manual_reading_notes" class="form-control" rows="3"
                                  placeholder="Notes for manual meter readings on offline machines..."><?php echo htmlspecialchars($meter['manual_reading_notes']); ?></textarea>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="form-section">
                    <h4>Additional Information</h4>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional notes about this meter entry..."><?php echo htmlspecialchars($meter['notes']); ?></textarea>
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


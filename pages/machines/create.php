<?php
/**
 * Create new slot machine
 */

// Initialize transaction data
$machine = [
    'machine_number' => '',
    'brand_id' => '',
    'model' => '',
    'game' => '',
    'type_id' => '',
    'credit_value' => '',
    'manufacturing_year' => '',
    'ip_address' => '',
    'mac_address' => '',
    'serial_number' => '',
    'status' => 'Active',
    'ticket_printer' => 'N/A',
    'system_comp' => 'offline'
];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
	$machine['machine_number'] = get_input(INPUT_POST, 'machine_number', 'string');
	$machine['brand_id'] = get_input(INPUT_POST, 'brand_id', 'int');
	$machine['model'] = get_input(INPUT_POST, 'model', 'string');
	$machine['game'] = get_input(INPUT_POST, 'game', 'string');
	$machine['type_id'] = get_input(INPUT_POST, 'type_id', 'int');
	$machine['credit_value'] = get_input(INPUT_POST, 'credit_value', 'float');
	$machine['manufacturing_year'] = get_input(INPUT_POST, 'manufacturing_year', 'int');
	$machine['ip_address'] = get_input(INPUT_POST, 'ip_address', 'ip');
	$machine['mac_address'] = get_input(INPUT_POST, 'mac_address', 'mac');
	$machine['serial_number'] = get_input(INPUT_POST, 'serial_number', 'string');
	$machine['status'] = get_input(INPUT_POST, 'status', 'string', 'Active');
	$machine['ticket_printer'] = get_input(INPUT_POST, 'ticket_printer', 'string', 'N/A');
	$machine['system_comp'] = get_input(INPUT_POST, 'system_comp', 'string', 'offline');

    // Validate required fields
    if (empty($machine['machine_number']) || empty($machine['brand_id']) || empty($machine['game']) ||
        empty($machine['type_id']) || empty($machine['credit_value']) || empty($machine['status']) ||
        empty($machine['ticket_printer']) || empty($machine['system_comp'])) {
        set_flash_message('danger', "Please fill out all required fields.");
        header("Location: index.php?page=machines&action=create");
        exit;
    }
    // Validate IP address format if provided
    else if (!empty($machine['ip_address']) && !is_valid_ip($machine['ip_address'])) {
        set_flash_message('danger', "Please enter a valid IP address.");
        header("Location: index.php?page=machines&action=create");
        exit;
    }
    // Validate MAC address format if provided
    else if (!empty($machine['mac_address']) && !is_valid_mac($machine['mac_address'])) {
        set_flash_message('danger', "Please enter a valid MAC address (e.g., 00:1A:2B:3C:4D:5E).");
        header("Location: index.php?page=machines&action=create");
        exit;
    }
    else {
        try {
            // Check if machine number already exists
            $stmt = $conn->prepare("SELECT id FROM machines WHERE machine_number = ?");
            $stmt->execute([$machine['machine_number']]);

            if ($stmt->rowCount() > 0) {
                set_flash_message('danger', "A machine with this number already exists.");
                header("Location: index.php?page=machines&action=create");
                exit;
            } else {
                // Check if serial number already exists (if provided)
                if (!empty($machine['serial_number'])) {
                    $stmt = $conn->prepare("SELECT id FROM machines WHERE serial_number = ?");
                    $stmt->execute([$machine['serial_number']]);

                    if ($stmt->rowCount() > 0) {
                        set_flash_message('danger', "A machine with this serial number already exists.");
                        header("Location: index.php?page=machines&action=create");
                        exit;
                    }
                }

                // Insert new machine
                $stmt = $conn->prepare("
                    INSERT INTO machines (machine_number, brand_id, model, game, type_id, credit_value,
                    manufacturing_year, ip_address, mac_address, serial_number, status, ticket_printer, system_comp)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

                ");

                // Handle empty brand_id
                if (empty($machine['brand_id'])) {
                    $machine['brand_id'] = null;
                }

                $stmt->execute([
                    $machine['machine_number'],
                    $machine['brand_id'],
                    $machine['model'] ?: null,
                    $machine['game'] ?: null,
                    $machine['type_id'],
                    $machine['credit_value'],
                    $machine['manufacturing_year'] ?: null,
                    $machine['ip_address'] ?: null,
                    $machine['mac_address'] ?: null,
                    $machine['serial_number'] ?: null,
                    $machine['status'],
                    $machine['ticket_printer'],
                    $machine['system_comp']
                ]);

                 // Log action
                log_action('create_machine', "Created machine: {$machine['machine_number']} - {$machine['game']}");

                // Redirect to machine list
                set_flash_message('success', "Machine created successfully.");
                header("Location: index.php?page=machines");
                exit;
            }
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
            header("Location: index.php?page=machines&action=create");
            exit;
        }
    }
}

// Get brands for dropdown
try {
    $stmt = $conn->query("SELECT id, name FROM brands ORDER BY name");
    $brands = $stmt->fetchAll();
} catch (PDOException $e) {
    // No need to set $error here, as flash messages handle display
    $brands = [];
}

// Get machine types for dropdown
try {
    $stmt = $conn->query("SELECT id, name FROM machine_types ORDER BY name");
    $machine_types = $stmt->fetchAll();
} catch (PDOException $e) {
    // No need to set $error here, as flash messages handle display
    $machine_types = [];
}
?>

<div class="machine-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add New Machine</h3>
        </div>
        <div class="card-body">
            <form action="index.php?page=machines&action=create" method="POST" id="machineCreateForm">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h4>Basic Information</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="machine_number">Machine Number *</label>
                                <input type="text" id="machine_number" name="machine_number" class="form-control" value="<?php echo htmlspecialchars($machine['machine_number']); ?>" required>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="brand_id">Brand *</label>
                                <select id="brand_id" name="brand_id" class="form-control" required>
                                    <option value="">Select Brand</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>" <?php echo $machine['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" id="model" name="model" class="form-control" value="<?php echo htmlspecialchars($machine['model']); ?>">
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="game">Game *</label>
                                <input type="text" id="game" name="game" class="form-control" value="<?php echo htmlspecialchars($machine['game']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="type_id">Type *</label>
                                <select id="type_id" name="type_id" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($machine_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $machine['type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="status">Status *</label>
                                <select id="status" name="status" class="form-control" required>
                                    <?php foreach ($machine_statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $machine['status'] == $status ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
						<div class="row">
						<div class="col">
                        <div class="form-group">
                            <label for="ticket_printer">Ticket Printer</label>
                            <select id="ticket_printer" name="ticket_printer" class="form-control" required>
                                <option value="yes" <?php echo $machine['ticket_printer'] == 'yes' ? 'selected' : ''; ?>>Yes</option>
                                <option value="N/A" <?php echo $machine['ticket_printer'] == 'N/A' ? 'selected' : ''; ?>>N/A</option>
                            </select>
                        </div>
                    </div>
					<div class="col">
                        <div class="form-group">
                            <label for="system_comp">System Compatibility</label>
                            <select id="system_comp" name="system_comp" class="form-control" required>
                                <option value="offline" <?php echo $machine['system_comp'] == 'offline' ? 'selected' : ''; ?>>Offline</option>
                                <option value="online" <?php echo $machine['system_comp'] == 'online' ? 'selected' : ''; ?>>Online</option>
                            </select>
                        </div>
                    </div>

                    </div>

                </div>

                <!-- Technical Details Section -->
                <div class="form-section">
                    <h4>Technical Details</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="credit_value">Credit Value *</label>
                                <input type="number" id="credit_value" name="credit_value" class="form-control" value="<?php echo htmlspecialchars($machine['credit_value']); ?>" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="manufacturing_year">Manufacturing Year</label>
                                <input type="number" id="manufacturing_year" name="manufacturing_year" class="form-control" value="<?php echo htmlspecialchars($machine['manufacturing_year']); ?>" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="serial_number">Serial Number</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($machine['serial_number']); ?>">
                            </div>
                        </div>

                        <div class="col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                </div>

                <!-- Network Configuration Section -->
                <div class="form-section">
                    <h4>Network Configuration</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="ip_address">IP Address</label>
                                <input type="text" id="ip_address" name="ip_address" class="form-control ip-address" value="<?php echo htmlspecialchars($machine['ip_address']); ?>">
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="mac_address">MAC Address</label>
                                <input type="text" id="mac_address" name="mac_address" class="form-control mac-address" value="<?php echo htmlspecialchars($machine['mac_address']); ?>" placeholder="00:1A:2B:3C:4D:5E">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Machine</button>
                    <a href="index.php?page=machines" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/machines_create.js"></script>

<?php
/**
 * Edit existing slot machine
 */

// Ensure we have an ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', "Invalid machine ID.");
    header("Location: index.php?page=machines");
    exit;
}

$machine_id = $_GET['id'];

// Get machine data
try {
    $stmt = $conn->prepare("
        SELECT m.*, b.name AS brand_name, mt.name AS type_name
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        WHERE m.id = ?
    ");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        set_flash_message('danger', "Machine not found.");
        header("Location: index.php?page=machines");
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . $e->getMessage());
    header("Location: index.php?page=machines");
    exit;
}

// Get brands for dropdown
try {
    $brands_stmt = $conn->query("SELECT id, name FROM brands ORDER BY name");
    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . $e->getMessage());
    $brands = [];
}

// Get machine types for dropdown
try {
    $types_stmt = $conn->query("SELECT id, name FROM machine_types ORDER BY name");
    $machine_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . $e->getMessage());
    $machine_types = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $machine_number = sanitize_input($_POST['machine_number'] ?? '');
    $brand_id = sanitize_input($_POST['brand_id'] ?? '');
    $model = sanitize_input($_POST['model'] ?? '');
    $game = sanitize_input($_POST['game'] ?? '');
    $type_id = sanitize_input($_POST['type_id'] ?? '');
    $credit_value = sanitize_input($_POST['credit_value'] ?? '');
    $manufacturing_year = sanitize_input($_POST['manufacturing_year'] ?? '');
    $ip_address = sanitize_input($_POST['ip_address'] ?? '');
    $mac_address = sanitize_input($_POST['mac_address'] ?? '');
    $serial_number = sanitize_input($_POST['serial_number'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Active');
    $ticket_printer = sanitize_input($_POST['ticket_printer'] ?? 'N/A');
    $system_comp = sanitize_input($_POST['system_comp'] ?? 'offline');

    // Validate required fields
    if (empty($machine_number) || empty($brand_id) || empty($game) ||
        empty($type_id) || empty($credit_value) || empty($status) ||
        empty($ticket_printer) || empty($system_comp)) {
        set_flash_message('danger', "Please fill out all required fields.");
    }
    else if (!empty($ip_address) && !is_valid_ip($ip_address)) {
        set_flash_message('danger', "Please enter a valid IP address.");
    }
    else if (!empty($mac_address) && !is_valid_mac($mac_address)) {
        set_flash_message('danger', "Please enter a valid MAC address (e.g., 00:1A:2B:3C:4D:5E).");
    }
    else {
        try {
            // Check for duplicate machine number (excluding current one)
            $stmt = $conn->prepare("SELECT id FROM machines WHERE machine_number = ? AND id != ?");
            $stmt->execute([$machine_number, $machine_id]);
            if ($stmt->rowCount() > 0) {
                set_flash_message('danger', "A machine with this number already exists.");
            }

            // Check for duplicate serial number (if provided)
            if (!empty($serial_number)) {
                $stmt = $conn->prepare("SELECT id FROM machines WHERE serial_number = ? AND id != ?");
                $stmt->execute([$serial_number, $machine_id]);
                if ($stmt->rowCount() > 0) {
                    set_flash_message('danger', "A machine with this serial number already exists.");
                }
            }

            // Update machine if no errors
            if (empty($error)) {
                $stmt = $conn->prepare("
                    UPDATE machines SET
                        machine_number = ?,
                        brand_id = ?,
                        model = ?,
                        game = ?,
                        type_id = ?,
                        credit_value = ?,
                        manufacturing_year = ?,
                        ip_address = ?,
                        mac_address = ?,
                        serial_number = ?,
                        status = ?,
                        ticket_printer = ?,
                        system_comp = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $machine_number,
                    $brand_id ?: null,
                    $model ?: null, // Model is now optional
                    $game,
                    $type_id,
                    $credit_value,
                    $manufacturing_year ?: null,
                    $ip_address ?: null,
                    $mac_address ?: null,
                    $serial_number ?: null,
                    $status,
                    $ticket_printer,
                    $system_comp,
                    $machine_id
                ]);

                log_action('update_machine', "Updated machine: {$machine_number} - {$game}");
                set_flash_message('success', "Machine updated successfully.");

                // Redirect after successful update
                if (!headers_sent()) {
                    header("Location: index.php?page=machines");
                    exit;
                }
            }
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
        }
    }
}
?>

<div class="machine-edit fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Edit Machine</h3>
        </div>
        <div class="card-body">
            <form action="index.php?page=machines&action=edit&id=<?php echo $machine_id; ?>" method="POST" id="machineEditForm">
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
                                <input type="text" id="game" name="game" class="form-control" value="<?php echo htmlspecialchars($machine['game'] ?? ''); ?>" placeholder="e.g., Buffalo Gold, Lightning Link" required>
                                <small class="form-text">Name of the game installed on this machine</small>
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
                                    <?php foreach ($machine_statuses as $status_opt): ?>
                                        <option value="<?php echo $status_opt; ?>" <?php echo $machine['status'] == $status_opt ? 'selected' : ''; ?>>
                                            <?php echo $status_opt; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="ticket_printer">Ticket Printer *</label>
                                <select id="ticket_printer" name="ticket_printer" class="form-control" required>
                                    <option value="yes" <?php echo $machine['ticket_printer'] == 'yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="N/A" <?php echo $machine['ticket_printer'] == 'N/A' ? 'selected' : ''; ?>>N/A</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="system_comp">System Compatibility *</label>
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
                                <input type="number" id="credit_value" name="credit_value" class="form-control" value="<?php echo htmlspecialchars(number_format((float)$machine['credit_value'], 2)); ?>" step="0.01" min="0" required>
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
                    <button type="submit" class="btn btn-primary">Update Machine</button>
                    <a href="index.php?page=machines" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/machines_edit.js"></script>
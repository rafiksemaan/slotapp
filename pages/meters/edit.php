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
    $stmt = $conn->prepare("SELECT * FROM meters WHERE id = ?");
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
        SELECT m.id, m.machine_number, b.name as brand_name
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        ORDER BY m.machine_number
    ");
    $machines = $stmt->fetchAll();
} catch (PDOException $e) {
    $machines = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $machine_id = sanitize_input($_POST['machine_id'] ?? '');
    $operation_date = sanitize_input($_POST['operation_date'] ?? '');
    $meter_type = sanitize_input($_POST['meter_type'] ?? '');

    // Online machine fields
    $total_in = sanitize_input($_POST['total_in'] ?? '');
    $total_out = sanitize_input($_POST['total_out'] ?? '');
    $bills_in = sanitize_input($_POST['bills_in'] ?? '');
    $ticket_in = sanitize_input($_POST['ticket_in'] ?? '');
    $ticket_out = sanitize_input($_POST['ticket_out'] ?? '');
    $jp = sanitize_input($_POST['jp'] ?? '');
    $bets_online = sanitize_input($_POST['bets_online'] ?? ''); // Renamed to avoid conflict
    $handpay_online = sanitize_input($_POST['handpay_online'] ?? ''); // Renamed to avoid conflict

    // Coins machine fields
    $coins_in = sanitize_input($_POST['coins_in'] ?? '');
    $coins_out = sanitize_input($_POST['coins_out'] ?? '');
    $coins_drop = sanitize_input($_POST['coins_drop'] ?? '');
    $bets_coins = sanitize_input($_POST['bets_coins'] ?? ''); // Renamed to avoid conflict
    $handpay_coins = sanitize_input($_POST['handpay_coins'] ?? ''); // Renamed to avoid conflict

    // Offline machine fields
    $total_in_offline = sanitize_input($_POST['total_in_offline'] ?? ''); // Renamed
    $total_out_offline = sanitize_input($_POST['total_out_offline'] ?? ''); // Renamed
    $handpay_offline = sanitize_input($_POST['handpay_offline'] ?? ''); // Renamed
    $jp_offline = sanitize_input($_POST['jp_offline'] ?? ''); // Renamed
    $bills_in_offline = sanitize_input($_POST['bills_in_offline'] ?? ''); // Renamed

    // Validate required fields
    if (empty($machine_id) || empty($operation_date) || empty($meter_type)) {
        set_flash_message('danger', "Machine, operation date, and meter type are required.");
    } else {
        try {
            // Update meter entry based on meter_type
            $sql = "UPDATE meters SET
                        machine_id = ?,
                        operation_date = ?,
                        meter_type = ?,
                        total_in = ?, total_out = ?, bills_in = ?, ticket_in = ?, ticket_out = ?, jp = ?, bets = ?, handpay = ?,
                        coins_in = ?, coins_out = ?, coins_drop = ?,
                        updated_by = ?, updated_at = NOW()
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);

            // Set all fields to null initially, then populate based on meter_type
            $params = [
                $machine_id, $operation_date, $meter_type,
                null, null, null, null, null, null, null, null, // Online fields
                null, null, null, // Coins fields
                $_SESSION['user_id'], $meter_id
            ];

            switch ($meter_type) {
                case 'online':
                    $params[3] = empty($total_in) ? null : floatval($total_in);
                    $params[4] = empty($total_out) ? null : floatval($total_out);
                    $params[5] = empty($bills_in) ? null : floatval($bills_in);
                    $params[6] = empty($ticket_in) ? null : floatval($ticket_in);
                    $params[7] = empty($ticket_out) ? null : floatval($ticket_out);
                    $params[8] = empty($jp) ? null : floatval($jp);
                    $params[9] = empty($bets_online) ? null : floatval($bets_online);
                    $params[10] = empty($handpay_online) ? null : floatval($handpay_online);
                    break;
                case 'coins':
                    $params[11] = empty($coins_in) ? null : floatval($coins_in);
                    $params[12] = empty($coins_out) ? null : floatval($coins_out);
                    $params[13] = empty($coins_drop) ? null : floatval($coins_drop);
                    $params[9] = empty($bets_coins) ? null : floatval($bets_coins); // Bets field is shared
                    $params[10] = empty($handpay_coins) ? null : floatval($handpay_coins); // Handpay field is shared
                    break;
                case 'offline':
                    $params[3] = empty($total_in_offline) ? null : floatval($total_in_offline);
                    $params[4] = empty($total_out_offline) ? null : floatval($total_out_offline);
                    $params[10] = empty($handpay_offline) ? null : floatval($handpay_offline); // Handpay field is shared
                    $params[8] = empty($jp_offline) ? null : floatval($jp_offline); // JP field is shared
                    $params[5] = empty($bills_in_offline) ? null : floatval($bills_in_offline); // Bills_in field is shared
                    break;
            }

            $result = $stmt->execute($params);

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
                                    <?php foreach ($machines as $machine): ?>
                                        <option value="<?php echo $machine['id']; ?>"
                                            <?php echo $meter_data['machine_id'] == $machine['id'] ? 'selected' : ''; ?>>
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
                                <label for="operation_date">Operation Date *</label>
                                <input type="date" id="operation_date" name="operation_date" class="form-control"
                                       value="<?php echo htmlspecialchars($meter_data['operation_date']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="meter_type">Meter Type *</label>
                                <select id="meter_type" name="meter_type" class="form-control" required>
                                    <option value="online" <?php echo $meter_data['meter_type'] == 'online' ? 'selected' : ''; ?>>Online Machine</option>
                                    <option value="coins" <?php echo $meter_data['meter_type'] == 'coins' ? 'selected' : ''; ?>>Coins Machine</option>
                                    <option value="offline" <?php echo $meter_data['meter_type'] == 'offline' ? 'selected' : ''; ?>>Offline Machine</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <!-- Empty for layout -->
                        </div>
                    </div>
                </div>

                <!-- Online Machine Fields -->
                <div class="form-section" id="online-fields">
                    <h4>Online Machine Meters</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="total_in">Total In</label>
                                <input type="number" id="total_in" name="total_in" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['total_in'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="total_out">Total Out</label>
                                <input type="number" id="total_out" name="total_out" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['total_out'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="bills_in">Bills In</label>
                                <input type="number" id="bills_in" name="bills_in" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['bills_in'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="ticket_in">Ticket In</label>
                                <input type="number" id="ticket_in" name="ticket_in" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['ticket_in'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="ticket_out">Ticket Out</label>
                                <input type="number" id="ticket_out" name="ticket_out" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['ticket_out'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="jp">JP</label>
                                <input type="number" id="jp" name="jp" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['jp'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="bets_online">Bets</label>
                                <input type="number" id="bets_online" name="bets_online" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['bets'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="handpay_online">Handpay</label>
                                <input type="number" id="handpay_online" name="handpay_online" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['handpay'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coins Machine Fields -->
                <div class="form-section" id="coins-fields">
                    <h4>Coins Machine Meters</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="coins_in">Coins In</label>
                                <input type="number" id="coins_in" name="coins_in" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['coins_in'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="coins_out">Coins Out</label>
                                <input type="number" id="coins_out" name="coins_out" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['coins_out'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="coins_drop">Coins Drop</label>
                                <input type="number" id="coins_drop" name="coins_drop" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['coins_drop'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="bets_coins">Bets</label>
                                <input type="number" id="bets_coins" name="bets_coins" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['bets'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="handpay_coins">Handpay</label>
                                <input type="number" id="handpay_coins" name="handpay_coins" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['handpay'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <!-- Empty for layout -->
                        </div>
                    </div>
                </div>

                <!-- Offline Machine Fields -->
                <div class="form-section" id="offline-fields">
                    <h4>Offline Machine Meters</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="total_in_offline">Total In</label>
                                <input type="number" id="total_in_offline" name="total_in_offline" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['total_in'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="total_out_offline">Total Out</label>
                                <input type="number" id="total_out_offline" name="total_out_offline" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['total_out'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="handpay_offline">Handpay</label>
                                <input type="number" id="handpay_offline" name="handpay_offline" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['handpay'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="jp_offline">JP</label>
                                <input type="number" id="jp_offline" name="jp_offline" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['jp'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="bills_in_offline">Bills In</label>
                                <input type="number" id="bills_in_offline" name="bills_in_offline" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($meter_data['bills_in'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <!-- Empty for layout -->
                        </div>
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
<script type="module">
    document.addEventListener('DOMContentLoaded', function() {
        const meterTypeSelect = document.getElementById('meter_type');
        const onlineFields = document.getElementById('online-fields');
        const coinsFields = document.getElementById('coins-fields');
        const offlineFields = document.getElementById('offline-fields');

        function toggleMeterFields() {
            const selectedType = meterTypeSelect.value;

            onlineFields.style.display = 'none';
            coinsFields.style.display = 'none';
            offlineFields.style.display = 'none';

            if (selectedType === 'online') {
                onlineFields.style.display = 'block';
            } else if (selectedType === 'coins') {
                coinsFields.style.display = 'block';
            } else if (selectedType === 'offline') {
                offlineFields.style.display = 'block';
            }
        }

        meterTypeSelect.addEventListener('change', toggleMeterFields);

        // Initial call to set correct fields on page load
        toggleMeterFields();
    });
</script>

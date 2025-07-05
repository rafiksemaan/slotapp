<?php
/**
 * Create new machine group
 */

// Process form submission
$group = [
    'name' => '',
    'description' => '',
    'machine_ids' => []
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $group['name'] = sanitize_input($_POST['name'] ?? '');
    $group['description'] = sanitize_input($_POST['description'] ?? '');
    $group['machine_ids'] = $_POST['machine_ids'] ?? [];
    
    // Validate required fields
    if (empty($group['name'])) {
        set_flash_message('danger', "Group name is required.");
        header("Location: index.php?page=machine_groups&action=create");
        exit;
    } elseif (count($group['machine_ids']) < 2) {
        set_flash_message('danger', "A group must contain at least 2 machines.");
        header("Location: index.php?page=machine_groups&action=create");
        exit;
    } else {
        try {
            // Check if group name already exists
            $stmt = $conn->prepare("SELECT id FROM machine_groups WHERE name = ?");
            $stmt->execute([$group['name']]);
            
            if ($stmt->rowCount() > 0) {
                set_flash_message('danger', "A group with this name already exists.");
                header("Location: index.php?page=machine_groups&action=create");
                exit;
            } else {
                // Start transaction
                $conn->beginTransaction();
                
                // Insert new group
                $stmt = $conn->prepare("INSERT INTO machine_groups (name, description) VALUES (?, ?)");
                $stmt->execute([$group['name'], $group['description'] ?: null]);
                
                $group_id = $conn->lastInsertId();
                
                // Insert group members
                $stmt = $conn->prepare("INSERT INTO machine_group_members (group_id, machine_id) VALUES (?, ?)");
                foreach ($group['machine_ids'] as $machine_id) {
                    $stmt->execute([$group_id, $machine_id]);
                }
                
                // Commit transaction
                $conn->commit();
                
                // Log action
                log_action('create_machine_group', "Created machine group: {$group['name']} with " . count($group['machine_ids']) . " machines");
                
                // Redirect to group list
                set_flash_message('success', "Machine group created successfully.");
                header("Location: index.php?page=machine_groups");
                exit;
            }
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollback();
            set_flash_message('danger', "Database error: " . $e->getMessage());
            header("Location: index.php?page=machine_groups&action=create");
            exit;
        }
    }
}

// Get all machines for selection
try {
    $stmt = $conn->query("
        SELECT m.id, m.machine_number, b.name as brand_name, mt.name as type_name
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        ORDER BY m.machine_number
    ");
    $machines = $stmt->fetchAll();
} catch (PDOException $e) {
    // No need to set $error here, as flash messages handle display
    $machines = [];
}
?>

<div class="machine-group-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Create New Machine Group</h3>
        </div>
        <div class="card-body">
            <form action="index.php?page=machine_groups&action=create" method="POST" id="machineGroupCreateForm">
                <!-- Group Information Section -->
                <div class="form-section">
                    <h4>Group Information</h4>
                    <div class="form-group">
                        <label for="name">Group Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($group['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Optional description of the group..."><?php echo htmlspecialchars($group['description']); ?></textarea>
                    </div>
                </div>
                
                <!-- Machine Selection Section -->
                <div class="form-section">
                    <h4>Machine Selection</h4>
                    <p class="form-description">Select at least 2 machines for this group (minimum required).</p>
                    
                    <div class="form-group">
                        <label>Select Machines * (minimum 2 required)</label>
                        <div class="machine-selection">
                            <?php if (empty($machines)): ?>
                                <p class="text-muted">No machines available</p>
                            <?php else: ?>
                                <div class="machine-grid">
                                    <?php foreach ($machines as $machine): ?>
                                        <label class="machine-checkbox">
                                            <input type="checkbox" name="machine_ids[]" value="<?php echo $machine['id']; ?>" 
                                                   <?php echo in_array($machine['id'], $group['machine_ids']) ? 'checked' : ''; ?>>
                                            <div class="machine-info">
                                                <strong><?php echo htmlspecialchars($machine['machine_number']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($machine['brand_name'] ?? 'No Brand'); ?> - <?php echo htmlspecialchars($machine['type_name'] ?? 'No Type'); ?></small>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div id="selection-count" class="selection-info">
                            Selected: <span id="count">0</span> machines
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Group</button>
                    <a href="index.php?page=machine_groups" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/machine_groups_create.js"></script>

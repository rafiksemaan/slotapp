<?php
/**
 * Create new machine type
 */

// Process form submission
$machine_type = [
    'name' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $machine_type['name'] = sanitize_input($_POST['name'] ?? '');
    $machine_type['description'] = sanitize_input($_POST['description'] ?? '');
    
    // Validate required fields
    if (empty($machine_type['name'])) {
        set_flash_message('danger', "Machine type name is required.");
        header("Location: index.php?page=machine_types&action=create");
        exit;
    } else {
        try {
            // Check if machine type already exists
            $stmt = $conn->prepare("SELECT id FROM machine_types WHERE name = ?");
            $stmt->execute([$machine_type['name']]);
            
            if ($stmt->rowCount() > 0) {
                set_flash_message('danger', "A machine type with this name already exists.");
                header("Location: index.php?page=machine_types&action=create");
                exit;
            } else {
                // Insert new machine type
                $stmt = $conn->prepare("INSERT INTO machine_types (name, description) VALUES (?, ?)");
                $stmt->execute([$machine_type['name'], $machine_type['description'] ?: null]);
                
                // Log action
                log_action('create_machine_type', "Created machine type: {$machine_type['name']}");
                
                // Redirect to machine type list
                set_flash_message('success', "Machine type created successfully.");
                header("Location: index.php?page=machine_types");
                exit;
            }
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
            header("Location: index.php?page=machine_types&action=create");
            exit;
        }
    }
}
?>

<div class="machine-type-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add New Machine Type</h3>
        </div>
        <div class="card-body">
            <form action="index.php?page=machine_types&action=create" method="POST" id="machineTypeCreateForm">
                <!-- Machine Type Information Section -->
                <div class="form-section">
                    <h4>Machine Type Information</h4>
                    <div class="form-group">
                        <label for="name">Type Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($machine_type['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Optional description of the machine type..."><?php echo htmlspecialchars($machine_type['description']); ?></textarea>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Machine Type</button>
                    <a href="index.php?page=machine_types" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/machine_types_create.js"></script>

<?php
/**
 * Create new machine type
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

// Process form submission
$message = '';
$error = '';
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
        header("Location: index.php?page=machine_types&action=create&error=" . urlencode("Machine type name is required."));
        exit;
    } else {
        try {
            // Check if machine type already exists
            $stmt = $conn->prepare("SELECT id FROM machine_types WHERE name = ?");
            $stmt->execute([$machine_type['name']]);
            
            if ($stmt->rowCount() > 0) {
                header("Location: index.php?page=machine_types&action=create&error=" . urlencode("A machine type with this name already exists."));
                exit;
            } else {
                // Insert new machine type
                $stmt = $conn->prepare("INSERT INTO machine_types (name, description) VALUES (?, ?)");
                $stmt->execute([$machine_type['name'], $machine_type['description'] ?: null]);
                
                // Log action
                log_action('create_machine_type', "Created machine type: {$machine_type['name']}");
                
                // Redirect to machine type list
                header("Location: index.php?page=machine_types&message=" . urlencode("Machine type created successfully"));
                exit;
            }
        } catch (PDOException $e) {
            header("Location: index.php?page=machine_types&action=create&error=" . urlencode("Database error: " . $e->getMessage()));
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
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($display_error)): ?>
                <div class="alert alert-danger"><?php echo $display_error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($display_message)): ?>
                <div class="alert alert-success"><?php echo $display_message; ?></div>
            <?php endif; ?>
            
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
<div id="url-cleaner-data" 
     data-display-message="<?= !empty($display_message) ? 'true' : 'false' ?>" 
     data-display-error="<?= !empty($display_error) ? 'true' : 'false' ?>">
</div>
<script type="module" src="assets/js/url_cleaner.js"></script>
<script type="module" src="assets/js/machine_types_create.js"></script>
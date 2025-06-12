<?php
/**
 * Create new machine type
 */

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
        $error = "Machine type name is required.";
    } else {
        try {
            // Check if machine type already exists
            $stmt = $conn->prepare("SELECT id FROM machine_types WHERE name = ?");
            $stmt->execute([$machine_type['name']]);
            
            if ($stmt->rowCount() > 0) {
                $error = "A machine type with this name already exists.";
            } else {
                // Insert new machine type
                $stmt = $conn->prepare("INSERT INTO machine_types (name, description) VALUES (?, ?)");
                $stmt->execute([$machine_type['name'], $machine_type['description'] ?: null]);
                
                // Log action
                log_action('create_machine_type', "Created machine type: {$machine_type['name']}");
                
                // Redirect to machine type list
                header("Location: index.php?page=machine_types&message=Machine type created successfully");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
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
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form action="index.php?page=machine_types&action=create" method="POST" onsubmit="return validateForm(this)">
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

<style>
.form-section {
    margin-bottom: 2rem;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
}

.form-section h4 {
    margin-bottom: 1rem;
    color: var(--secondary-color);
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 0.5rem;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
}
</style>
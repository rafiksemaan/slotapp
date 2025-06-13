<?php
/**
 * Create new brand
 */

// Process form submission
$message = '';
$error = '';
$brand = [
    'name' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $brand['name'] = sanitize_input($_POST['name'] ?? '');
    $brand['description'] = sanitize_input($_POST['description'] ?? '');
    
    // Validate required fields
    if (empty($brand['name'])) {
        $error = "Brand name is required.";
    } else {
        try {
            // Check if brand already exists
            $stmt = $conn->prepare("SELECT id FROM brands WHERE name = ?");
            $stmt->execute([$brand['name']]);
            
            if ($stmt->rowCount() > 0) {
                $error = "A brand with this name already exists.";
            } else {
                // Insert new brand
                $stmt = $conn->prepare("INSERT INTO brands (name, description) VALUES (?, ?)");
                $stmt->execute([$brand['name'], $brand['description'] ?: null]);
                
                // Log action
                log_action('create_brand', "Created brand: {$brand['name']}");
                
                // Redirect to brand list
                header("Location: index.php?page=brands&message=Brand created successfully");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="brand-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Add New Brand</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form action="index.php?page=brands&action=create" method="POST" onsubmit="return validateForm(this)">
                <!-- Brand Information Section -->
                <div class="form-section">
                    <h4>Brand Information</h4>
                    <div class="form-group">
                        <label for="name">Brand Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($brand['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Optional description of the brand..."><?php echo htmlspecialchars($brand['description']); ?></textarea>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Brand</button>
                    <a href="index.php?page=brands" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
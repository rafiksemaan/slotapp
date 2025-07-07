<?php
/**
 * Create new brand
 */

// Process form submission
$brand = [
    'name' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $brand['name'] = get_input(INPUT_POST, 'name', 'string');
    $brand['description'] = get_input(INPUT_POST, 'description', 'string');
    
    // Validate required fields
    if (empty($brand['name'])) {
        set_flash_message('danger', "Brand name is required.");
        header("Location: index.php?page=brands&action=create");
        exit;
    } else {
        try {
            // Check if brand already exists
            $stmt = $conn->prepare("SELECT id FROM brands WHERE name = ?");
            $stmt->execute([$brand['name']]);
            
            if ($stmt->rowCount() > 0) {
                set_flash_message('danger', "A brand with this name already exists.");
                header("Location: index.php?page=brands&action=create");
                exit;
            } else {
                // Insert new brand
                $stmt = $conn->prepare("INSERT INTO brands (name, description) VALUES (?, ?)");
                $stmt->execute([$brand['name'], $brand['description'] ?: null]);
                
                // Log action
                log_action('create_brand', "Created brand: {$brand['name']}");
                
                // Redirect to brand list
                set_flash_message('success', "Brand created successfully.");
                header("Location: index.php?page=brands");
                exit;
            }
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
            header("Location: index.php?page=brands&action=create");
            exit;
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
            <form action="index.php?page=brands&action=create" method="POST" id="brandCreateForm">
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
<script type="module" src="assets/js/brands_create.js"></script>

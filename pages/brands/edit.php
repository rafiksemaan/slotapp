<?php
/**
 * Edit Brand
 */

// Ensure user has edit permissions
if (!$can_edit) {
    set_flash_message('danger', "Access denied.");
    header("Location: index.php?page=brands");
    exit;
}

// Check if an ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', "Invalid brand ID.");
    header("Location: index.php?page=brands");
    exit;
}

$brand_id = $_GET['id'];

// Get current brand data
try {
    $stmt = $conn->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([$brand_id]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$brand) {
        set_flash_message('danger', "Brand not found.");
        header("Location: index.php?page=brands");
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', "Database error: " . $e->getMessage());
    header("Location: index.php?page=brands");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand['name'] = sanitize_input($_POST['name'] ?? '');
    $brand['description'] = sanitize_input($_POST['description'] ?? '');

    // Validate required fields
    if (empty($brand['name'])) {
        set_flash_message('danger', "Brand name is required.");
    } else {
        try {
            // Check if brand exists with the same name
            $stmt = $conn->prepare("SELECT id FROM brands WHERE name = ? AND id != ?");
            $stmt->execute([$brand['name'], $brand_id]);
            if ($stmt->rowCount() > 0) {
                set_flash_message('danger', "A brand with this name already exists.");
            } else {
                // Update the brand
                $stmt = $conn->prepare("
                    UPDATE brands 
                    SET name = ?, description = ?
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $brand['name'],
                    $brand['description'],
                    $brand_id
                ]);

                if ($result) {
                    log_action('update_brand', "Updated brand: {$brand['name']}");
                    set_flash_message('success', "Brand updated successfully.");
                    header("Location: index.php?page=brands");
                    exit;
                } else {
                    set_flash_message('danger', "Failed to update brand.");
                }
            }
        } catch (PDOException $e) {
            set_flash_message('danger', "Database error: " . $e->getMessage());
        }
    }
}
?>

<div class="brand-edit fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Edit Brand</h3>
        </div>
        <div class="card-body">
            <form method="POST" class="brand-form" id="brandEditForm">
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
                    <button type="submit" class="btn btn-primary">Update Brand</button>
                    <a href="index.php?page=brands" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="module" src="assets/js/brands_edit.js"></script>

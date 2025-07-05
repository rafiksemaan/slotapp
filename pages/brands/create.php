<?php
/**
 * Create new brand
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
$message = ''; // This variable will no longer be used for display, but might be for internal logic
$error = '';   // This variable will no longer be used for display, but might be for internal logic
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
        header("Location: index.php?page=brands&action=create&error=" . urlencode("Brand name is required."));
        exit;
    } else {
        try {
            // Check if brand already exists
            $stmt = $conn->prepare("SELECT id FROM brands WHERE name = ?");
            $stmt->execute([$brand['name']]);
            
            if ($stmt->rowCount() > 0) {
                header("Location: index.php?page=brands&action=create&error=" . urlencode("A brand with this name already exists."));
                exit;
            } else {
                // Insert new brand
                $stmt = $conn->prepare("INSERT INTO brands (name, description) VALUES (?, ?)");
                $stmt->execute([$brand['name'], $brand['description'] ?: null]);
                
                // Log action
                log_action('create_brand', "Created brand: {$brand['name']}");
                
                // Redirect to brand list
                header("Location: index.php?page=brands&message=" . urlencode("Brand created successfully"));
                exit;
            }
        } catch (PDOException $e) {
            header("Location: index.php?page=brands&action=create&error=" . urlencode("Database error: " . $e->getMessage()));
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
            <?php if (!empty($display_error)): ?>
                <div class="alert alert-danger"><?php echo $display_error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($display_message)): ?>
                <div class="alert alert-success"><?php echo $display_message; ?></div>
            <?php endif; ?>
            
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
<script src="assets/js/brands_create.js"></script>
<?php
// JavaScript to clear URL parameters
if (!empty($display_message) || !empty($display_error)) {
    echo "<script type='text/javascript'>
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/&?(message|error)=[^&]*/g, ''));
    </script>";
}
?>

<?php
/**
 * Edit Machine Type
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

// Ensure user has edit permissions
if (!$can_edit) {
    header("Location: index.php?page=machine_types&error=" . urlencode("Access denied"));
    exit;
}

// Check if an ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=machine_types");
    exit;
}

$type_id = $_GET['id'];
$error = '';
$success = false;

// Get current machine type data
try {
    $stmt = $conn->prepare("SELECT * FROM machine_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $machine_type = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine_type) {
        header("Location: index.php?page=machine_types&error=" . urlencode("Machine type not found"));
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php?page=machine_types&error=" . urlencode("Database error"));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');

    // Validate required fields
    if (empty($name)) {
        $error = "Machine type name is required.";
    } else {
        try {
            // Check for duplicate name
            $stmt = $conn->prepare("SELECT id FROM machine_types WHERE name = ? AND id != ?");
            $stmt->execute([$name, $type_id]);
            if ($stmt->rowCount() > 0) {
                $error = "A machine type with this name already exists.";
            } else {
                // Update machine type
                $stmt = $conn->prepare("
                    UPDATE machine_types SET
                        name = ?,
                        description = ?
                    WHERE id = ?
                ");

                $result = $stmt->execute([
                    $name,
                    $description ?: null,
                    $type_id
                ]);

                if ($result) {
                    log_action('update_machine_type', "Updated machine type: {$name}");
                    header("Location: index.php?page=machine_types&message=" . urlencode("Machine type updated successfully"));
                    exit;
                } else {
                    $error = "Failed to update machine type.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="machine-type-edit fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Edit Machine Type</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($display_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($display_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($display_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($display_message); ?></div>
            <?php endif; ?>

            <form method="POST" class="machine-type-form" id="machineTypeEditForm">
                <!-- Machine Type Information Section -->
                <div class="form-section">
                    <h4>Machine Type Information</h4>
                    <div class="form-group">
                        <label for="name">Type Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($machine_type['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="4" placeholder="Optional description of the machine type..."><?php echo htmlspecialchars($machine_type['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Machine Type</button>
                    <a href="index.php?page=machine_types" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="assets/js/machine_types_edit.js"></script>
<?php
// JavaScript to clear URL parameters
if (!empty($display_message) || !empty($display_error)) {
    echo "<script type='text/javascript'>
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/&?(message|error)=[^&]*/g, ''));
    </script>";
}
?>

<?php
/**
 * Create new machine group
 */

// Process form submission
$message = '';
$error = '';
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
        $error = "Group name is required.";
    } elseif (count($group['machine_ids']) < 2) {
        $error = "A group must contain at least 2 machines.";
    } else {
        try {
            // Check if group name already exists
            $stmt = $conn->prepare("SELECT id FROM machine_groups WHERE name = ?");
            $stmt->execute([$group['name']]);
            
            if ($stmt->rowCount() > 0) {
                $error = "A group with this name already exists.";
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
                header("Location: index.php?page=machine_groups&message=Machine group created successfully");
                exit;
            }
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Database error: " . $e->getMessage();
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
    $error = "Database error: " . $e->getMessage();
    $machines = [];
}
?>

<div class="machine-group-create fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Create New Machine Group</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form action="index.php?page=machine_groups&action=create" method="POST" onsubmit="return validateGroupForm(this)">
                <div class="form-group">
                    <label for="name">Group Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($group['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($group['description']); ?></textarea>
                </div>
                
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
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Create Group</button>
                    <a href="index.php?page=machine_groups" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateGroupForm(form) {
    const checkboxes = form.querySelectorAll('input[name="machine_ids[]"]:checked');
    if (checkboxes.length < 2) {
        alert('Please select at least 2 machines for the group.');
        return false;
    }
    return true;
}

// Update selection count
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="machine_ids[]"]');
    const countElement = document.getElementById('count');
    
    function updateCount() {
        const checked = document.querySelectorAll('input[name="machine_ids[]"]:checked');
        countElement.textContent = checked.length;
        
        // Change color based on count
        if (checked.length < 2) {
            countElement.style.color = 'var(--danger-color)';
        } else {
            countElement.style.color = 'var(--success-color)';
        }
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCount);
    });
    
    // Initial count
    updateCount();
});
</script>
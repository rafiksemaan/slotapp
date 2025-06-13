<?php
/**
 * View machine group details
 */

// Ensure we have an ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=machine_groups");
    exit;
}

$group_id = $_GET['id'];

// Initialize variables
$error = '';
$group = null;
$machines = [];

// Get group data
try {
    $stmt = $conn->prepare("SELECT * FROM machine_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        header("Location: index.php?page=machine_groups&error=Group not found");
        exit;
    }
    
    // Get machines in this group
    $stmt = $conn->prepare("
        SELECT m.*, b.name as brand_name, mt.name as type_name
        FROM machines m
        JOIN machine_group_members mgm ON m.id = mgm.machine_id
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        WHERE mgm.group_id = ?
        ORDER BY m.machine_number
    ");
    $stmt->execute([$group_id]);
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="machine-group-view fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Machine Group Details</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <div class="group-details">
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Group Name:</strong>
                                <span><?php echo htmlspecialchars($group['name']); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Created:</strong>
                                <span><?php echo format_datetime($group['created_at'], 'd M Y H:i'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Description:</strong>
                                <span><?php echo htmlspecialchars($group['description'] ?: 'No description'); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Last Updated:</strong>
                                <span><?php echo format_datetime($group['updated_at'], 'd M Y H:i'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Machines in Group -->
                    <div class="machines-section" style="margin-top: 2rem;">
                        <h4>Machines in Group (<?php echo count($machines); ?>)</h4>
                        
                        <?php if (empty($machines)): ?>
                            <p class="text-muted">No machines in this group.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Machine #</th>
                                            <th>Brand</th>
                                            <th>Model</th>
                                            <th>Type</th>
                                            <th>Credit Value</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($machines as $machine): ?>
                                            <tr>
                                                <td>
                                                    <a href="index.php?page=machines&action=view&id=<?php echo $machine['id']; ?>">
                                                        <?php echo htmlspecialchars($machine['machine_number']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($machine['brand_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($machine['model']); ?></td>
                                                <td><?php echo htmlspecialchars($machine['type_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo format_currency($machine['credit_value']); ?></td>
                                                <td>
                                                    <span class="status status-<?php echo strtolower($machine['status']); ?>">
                                                        <?php echo htmlspecialchars($machine['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div class="form-group" style="margin-top: 2rem;">
                        <a href="index.php?page=machine_groups&action=edit&id=<?php echo $group_id; ?>" class="btn btn-primary">Edit Group</a>
                        <a href="index.php?page=machine_groups" class="btn btn-danger">Back to Groups</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
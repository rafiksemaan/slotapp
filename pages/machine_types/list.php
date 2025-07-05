<?php
/**
 * List all machine types
 */

// Get sorting parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column
$allowed_columns = ['name', 'machine_count'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'name';
}

// Validate sort order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Toggle sort order for links
$toggle_order = $sort_order == 'ASC' ? 'DESC' : 'ASC';

// Get machine types with machine count
try {
    $query = "
        SELECT mt.*, COUNT(m.id) as machine_count 
        FROM machine_types mt
        LEFT JOIN machines m ON mt.id = m.type_id
        GROUP BY mt.id
    ";
    
    if ($sort_column == 'name') {
        $query .= " ORDER BY mt.name $sort_order";
    } else if ($sort_column == 'machine_count') {
        $query .= " ORDER BY machine_count $sort_order";
    }
    
    $stmt = $conn->query($query);
    $types = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    $types = [];
}
?>

<div class="machine-types-list fade-in">
    <!-- Action Buttons -->
    <?php if ($can_edit): ?>
    <div class="action-buttons">
        <a href="index.php?page=machine_types&action=create" class="btn btn-primary">Add New Machine Type</a>
    </div>
    <?php endif; ?>
    
    <!-- Machine Types Table -->
    <div class="card">
        <div class="card-header">
            <h3>Machine Types</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="index.php?page=machine_types&sort=name&order=<?php echo $sort_column == 'name' ? $toggle_order : 'ASC'; ?>">
                                    Type Name
                                    <?php if ($sort_column == 'name'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Description</th>
                            <th>
                                <a href="index.php?page=machine_types&sort=machine_count&order=<?php echo $sort_column == 'machine_count' ? $toggle_order : 'ASC'; ?>">
                                    Machines
                                    <?php if ($sort_column == 'machine_count'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($types)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No machine types found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($types as $type): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['name']); ?></td>
                                    <td><?php echo htmlspecialchars($type['description'] ?? 'N/A'); ?></td>
                                    <td><?php echo $type['machine_count']; ?></td>
                                    <td>
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=machine_types&action=edit&id=<?php echo $type['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                                            <a href="index.php?page=machine_types&action=delete&id=<?php echo $type['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this machine type?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
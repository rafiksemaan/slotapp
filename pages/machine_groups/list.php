<?php
/**
 * List all machine groups
 */

// Get sorting parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column
$allowed_columns = ['name', 'machine_count', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'name';
}

// Validate sort order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Toggle sort order for links
$toggle_order = $sort_order == 'ASC' ? 'DESC' : 'ASC';

// Get machine groups with machine count
try {
    $query = "
        SELECT mg.*, COUNT(mgm.machine_id) as machine_count 
        FROM machine_groups mg
        LEFT JOIN machine_group_members mgm ON mg.id = mgm.group_id
        GROUP BY mg.id
    ";
    
    if ($sort_column == 'name') {
        $query .= " ORDER BY mg.name $sort_order";
    } else if ($sort_column == 'machine_count') {
        $query .= " ORDER BY machine_count $sort_order";
    } else if ($sort_column == 'created_at') {
        $query .= " ORDER BY mg.created_at $sort_order";
    }
    
    $stmt = $conn->query($query);
    $groups = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    $groups = [];
}
?>

<div class="machine-groups-list fade-in">
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="index.php?page=machine_groups&action=create" class="btn btn-primary">Create New Group</a>
    </div>
    
    <!-- Machine Groups Table -->
    <div class="card">
        <div class="card-header">
            <h3>Machine Groups</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="index.php?page=machine_groups&sort=name&order=<?php echo $sort_column == 'name' ? $toggle_order : 'ASC'; ?>">
                                    Group Name
                                    <?php if ($sort_column == 'name'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Description</th>
                            <th>
                                <a href="index.php?page=machine_groups&sort=machine_count&order=<?php echo $sort_column == 'machine_count' ? $toggle_order : 'ASC'; ?>">
                                    Machines
                                    <?php if ($sort_column == 'machine_count'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="index.php?page=machine_groups&sort=created_at&order=<?php echo $sort_column == 'created_at' ? $toggle_order : 'ASC'; ?>">
                                    Created
                                    <?php if ($sort_column == 'created_at'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($groups)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No machine groups found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($groups as $group): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($group['name']); ?></td>
                                    <td><?php echo htmlspecialchars($group['description'] ?? 'N/A'); ?></td>
                                    <td><?php echo $group['machine_count']; ?></td>
                                    <td><?php echo format_datetime($group['created_at'], 'd M Y'); ?></td>
                                    <td>
                                        <a href="index.php?page=machine_groups&action=view&id=<?php echo $group['id']; ?>" class="action-btn view-btn" data-tooltip="View Details"><span class="menu-icon"><img src="<?= icon('view2') ?>"/></span></a>
                                        <a href="index.php?page=machine_groups&action=edit&id=<?php echo $group['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                                        <a href="index.php?page=machine_groups&action=delete&id=<?php echo $group['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this group?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
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

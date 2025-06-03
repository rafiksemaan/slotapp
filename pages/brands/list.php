<?php
/**
 * List all brands
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

// Get brands with machine count
try {
    $query = "
        SELECT b.*, COUNT(m.id) as machine_count 
        FROM brands b
        LEFT JOIN machines m ON b.id = m.brand_id
        GROUP BY b.id
    ";
    
    if ($sort_column == 'name') {
        $query .= " ORDER BY b.name $sort_order";
    } else if ($sort_column == 'machine_count') {
        $query .= " ORDER BY machine_count $sort_order";
    }
    
    $stmt = $conn->query($query);
    $brands = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    $brands = [];
}

// Process message
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<div class="brands-list fade-in">
    <!-- Action Buttons -->
    <?php if ($can_edit): ?>
    <div class="action-buttons">
        <a href="index.php?page=brands&action=create" class="btn btn-primary">Add New Brand</a>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <!-- Brands Table -->
    <div class="card">
        <div class="card-header">
            <h3>Slot Machine Brands</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="index.php?page=brands&sort=name&order=<?php echo $sort_column == 'name' ? $toggle_order : 'ASC'; ?>">
                                    Brand Name
                                    <?php if ($sort_column == 'name'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Description</th>
                            <th>
                                <a href="index.php?page=brands&sort=machine_count&order=<?php echo $sort_column == 'machine_count' ? $toggle_order : 'ASC'; ?>">
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
                        <?php if (empty($brands)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No brands found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($brands as $brand): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($brand['name']); ?></td>
                                    <td><?php echo htmlspecialchars($brand['description'] ?? 'N/A'); ?></td>
                                    <td><?php echo $brand['machine_count']; ?></td>
                                    <td>
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=brands&action=edit&id=<?php echo $brand['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit">✏️</a>
                                            <a href="index.php?page=brands&action=delete&id=<?php echo $brand['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this brand?">🗑️</a>
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
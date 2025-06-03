<?php
/**
 * List all slot machines
 */


// Get sorting parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'machine_number';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column
$allowed_columns = ['machine_number', 'brand_id', 'type', 'credit_value', 'status'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'machine_number';
}

// Validate sort order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Toggle sort order for links
$toggle_order = $sort_order == 'ASC' ? 'DESC' : 'ASC';

// Get filter parameters
$filter_brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "
    SELECT m.*, b.name as brand_name 
    FROM machines m
    LEFT JOIN brands b ON m.brand_id = b.id
    WHERE 1=1
";

$params = [];

// Add filters to query
if (!empty($filter_brand)) {
    $query .= " AND m.brand_id = ?";
    $params[] = $filter_brand;
}

if (!empty($filter_type)) {
    $query .= " AND m.type = ?";
    $params[] = $filter_type;
}

if (!empty($filter_status)) {
    $query .= " AND m.status = ?";
    $params[] = $filter_status;
}

// Add sorting
$query .= " ORDER BY " . ($sort_column == 'brand_id' ? 'b.name' : "m.$sort_column") . " $sort_order";

// Get machines
try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $machines = $stmt->fetchAll();
    
    // Get brands for filter dropdown
    $brands_stmt = $conn->query("SELECT id, name FROM brands ORDER BY name");
    $brands = $brands_stmt->fetchAll();
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    $machines = [];
    $brands = [];
}
?>

<div class="machines-list fade-in">
    <!-- Filters -->
    <div class="filters-container">
        <form action="index.php" method="GET" class="filters-form">
            <input type="hidden" name="page" value="machines">
            <input type="hidden" name="sort" value="<?php echo $sort_column; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
            
            <div class="filter-group">
                <label for="brand">Brand</label>
                <select name="brand" id="brand" class="form-control">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>" <?php echo $filter_brand == $brand['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="type">Type</label>
                <select name="type" id="type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($machine_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $filter_type == $type ? 'selected' : ''; ?>>
                            <?php echo $type; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach ($machine_statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo $filter_status == $status ? 'selected' : ''; ?>>
                            <?php echo $status; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="index.php?page=machines" class="btn btn-danger">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Action Buttons -->
    <?php if ($can_edit): ?>
    <div class="action-buttons">
        <a href="index.php?page=machines&action=create" class="btn btn-primary">Add New Machine</a>
    </div>
    <?php endif; ?>
    
    <!-- Machines Table -->
    <div class="card">
        <div class="card-header">
            <h3>Slot Machines</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="index.php?page=machines&sort=machine_number&order=<?php echo $sort_column == 'machine_number' ? $toggle_order : 'ASC'; ?><?php echo !empty($filter_brand) ? '&brand=' . $filter_brand : ''; ?><?php echo !empty($filter_type) ? '&type=' . $filter_type : ''; ?><?php echo !empty($filter_status) ? '&status=' . $filter_status : ''; ?>">
                                    Machine #
                                    <?php if ($sort_column == 'machine_number'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="index.php?page=machines&sort=brand_id&order=<?php echo $sort_column == 'brand_id' ? $toggle_order : 'ASC'; ?><?php echo !empty($filter_brand) ? '&brand=' . $filter_brand : ''; ?><?php echo !empty($filter_type) ? '&type=' . $filter_type : ''; ?><?php echo !empty($filter_status) ? '&status=' . $filter_status : ''; ?>">
                                    Brand
                                    <?php if ($sort_column == 'brand_id'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Model</th>
                            <th>
                                <a href="index.php?page=machines&sort=type&order=<?php echo $sort_column == 'type' ? $toggle_order : 'ASC'; ?><?php echo !empty($filter_brand) ? '&brand=' . $filter_brand : ''; ?><?php echo !empty($filter_type) ? '&type=' . $filter_type : ''; ?><?php echo !empty($filter_status) ? '&status=' . $filter_status : ''; ?>">
                                    Type
                                    <?php if ($sort_column == 'type'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="index.php?page=machines&sort=credit_value&order=<?php echo $sort_column == 'credit_value' ? $toggle_order : 'ASC'; ?><?php echo !empty($filter_brand) ? '&brand=' . $filter_brand : ''; ?><?php echo !empty($filter_type) ? '&type=' . $filter_type : ''; ?><?php echo !empty($filter_status) ? '&status=' . $filter_status : ''; ?>">
                                    Credit Value
                                    <?php if ($sort_column == 'credit_value'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="index.php?page=machines&sort=status&order=<?php echo $sort_column == 'status' ? $toggle_order : 'ASC'; ?><?php echo !empty($filter_brand) ? '&brand=' . $filter_brand : ''; ?><?php echo !empty($filter_type) ? '&type=' . $filter_type : ''; ?><?php echo !empty($filter_status) ? '&status=' . $filter_status : ''; ?>">
                                    Status
                                    <?php if ($sort_column == 'status'): ?>
                                        <span class="sort-indicator"><?php echo $sort_order == 'ASC' ? '▲' : '▼'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($machines)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No machines found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($machines as $machine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($machine['machine_number']); ?></td>
                                    <td><?php echo htmlspecialchars($machine['brand_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($machine['model']); ?></td>
                                    <td><?php echo htmlspecialchars($machine['type']); ?></td>
                                    <td><?php echo format_currency($machine['credit_value']); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($machine['status']); ?>">
                                            <?php echo htmlspecialchars($machine['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="index.php?page=machines&action=view&id=<?php echo $machine['id']; ?>" class="action-btn view-btn" data-tooltip="View Details">👁️</a>
                                        
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=machines&action=edit&id=<?php echo $machine['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit">✏️</a>
                                            <a href="index.php?page=machines&action=delete&id=<?php echo $machine['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this machine?">🗑️</a>
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
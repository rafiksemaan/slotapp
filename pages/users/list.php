<?php
/**
 * Users List Page
 * Shows list of users with actions
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

// Check permissions
$can_edit = true; // Replace with actual permission check if available

// Get sorting parameters
$sort_column = $_GET['sort'] ?? 'username';
$sort_order = $_GET['order'] ?? 'ASC';
$toggle_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

// Get filter values
$filter_role = $_GET['role'] ?? '';
$filter_status = $_GET['status'] ?? '';

try {
    $query = "SELECT * FROM users WHERE 1=1";

    $params = [];

    if ($filter_role !== '') {
        $query .= " AND role = ?";
        $params[] = $filter_role;
    }

    if ($filter_status !== '') {
        $query .= " AND status = ?";
        $params[] = $filter_status;
    }

    $query .= " ORDER BY $sort_column $sort_order";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $users = [];
}
?>

<div class="users-page fade-in">
    <!-- Action Buttons -->
    <?php if ($can_edit): ?>
        <div class="action-buttons mb-6 flex justify-end">
            <a href="index.php?page=users&action=create" class="btn btn-primary">Add New User</a>
			<?php if (!empty($display_error)): ?>
    <div class="alert alert-danger"><?php echo $display_error; ?></div>
<?php endif; ?>

<?php if (!empty($display_message)): ?>
    <div class="alert alert-success"><?php echo $display_message; ?></div>
<?php endif; ?>
	
        </div>
    <?php endif; ?>

    <!-- Users Table -->
	<!-- DEBUG: Display Message Value: '<?php echo $display_message; ?>' -->
			<!-- DEBUG: Display Error Value: '<?php echo $display_error; ?>' -->
	
    <div class="card overflow-hidden">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Users</h3>
        </div>
        <div class="card-body p-6">
            <div class="table-container overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=users&sort=username&order=<?php echo $toggle_order; ?>">
                                    Username <?php if ($sort_column == 'username') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=users&sort=name&order=<?php echo $toggle_order; ?>">
                                    Name <?php if ($sort_column == 'name') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">
                                <a href="index.php?page=users&sort=email&order=<?php echo $toggle_order; ?>">
                                    Email <?php if ($sort_column == 'email') echo $sort_order == 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th class="px-4 py-2 text-left">Role</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center px-4 py-6">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-800 transition duration-150">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-4 py-2"><?php echo ucfirst($user['role']); ?></td>
                                    <td class="px-4 py-2"><?php echo ucfirst($user['status']); ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <?php if ($can_edit): ?>
                                            <a href="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" class="action-btn edit-btn" data-tooltip="Edit"><span class="menu-icon"><img src="<?= icon('edit') ?>"/></span></a>
                                            <a href="index.php?page=users&action=delete&id=<?php echo $user['id']; ?>" class="action-btn delete-btn" data-tooltip="Delete" data-confirm="Are you sure you want to delete this user?"><span class="menu-icon"><img src="<?= icon('delete') ?>"/></span></a>
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
<div id="url-cleaner-data" 
     data-display-message="<?= !empty($display_message) ? 'true' : 'false' ?>" 
     data-display-error="<?= !empty($display_error) ? 'true' : 'false' ?>">
</div>
<script type="module" src="assets/js/url_cleaner.js"></script>
<?php
/**
 * Edit User Page
 */

$can_edit = true; // Replace with real permission check if available
$message = '';
$error = '';
$user_id = $_GET['id'] ?? 0;

// Load current user
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        include('404.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $user['name'] = trim($_POST['name'] ?? '');
    $user['email'] = trim($_POST['email'] ?? '');
    $user['role'] = trim($_POST['role'] ?? '');
    $user['status'] = trim($_POST['status'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($user['name']) || empty($user['email'])) {
        $error = "Name and email are required.";
    } else {
        try {
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, status = ?";
            $params = [$user['name'], $user['email'], $user['role'], $user['status']];

            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            header("Location: index.php?page=users&message=User+updated+successfully");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<div class="users-edit fade-in">
    <div class="card">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Edit User</h3>
        </div>
        <div class="card-body p-6">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" class="form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password" class="form-control">
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role" class="form-control">
                        <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="editor" <?php echo ($user['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                        <option value="viewer" <?php echo ($user['role'] === 'viewer') ? 'selected' : ''; ?>>Viewer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Active" <?php echo ($user['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($user['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="index.php?page=users" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
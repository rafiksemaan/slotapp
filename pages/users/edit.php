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
        <div class="card-header">
            <h3>Edit User</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" class="user-form">
                <!-- User Information Section -->
                <div class="form-section">
                    <h4>User Information</h4>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small class="form-text">Username cannot be changed</small>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="form-section">
                    <h4>Change Password</h4>
                    <p class="form-description">Leave password field empty if you don't want to change the password.</p>
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control">
                        <small class="form-text">Leave blank to keep current password</small>
                    </div>
                </div>

                <!-- Access & Status Section -->
                <div class="form-section">
                    <h4>Access & Status</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select name="role" id="role" class="form-control" required>
                                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="editor" <?php echo ($user['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                                    <option value="viewer" <?php echo ($user['role'] === 'viewer') ? 'selected' : ''; ?>>Viewer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="status">Status *</label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="Active" <?php echo ($user['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($user['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="index.php?page=users" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/users_edit.js"></script>
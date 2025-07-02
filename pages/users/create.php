<?php
/**
 * Create New User
 */

$can_edit = true; // Replace with real permission check if available

$message = '';
$error = '';
$user = [
    'username' => '',
    'name' => '',
    'email' => '',
    'role' => 'viewer',
    'status' => 'Active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user['username'] = trim($_POST['username'] ?? '');
    $user['name'] = trim($_POST['name'] ?? '');
    $user['email'] = trim($_POST['email'] ?? '');
    $user['role'] = trim($_POST['role'] ?? '');
    $user['status'] = trim($_POST['status'] ?? '');

    if (empty($user['username']) || empty($user['name']) || empty($user['email'])) {
        $error = "All required fields must be filled out.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['username'],
                password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
                $user['name'],
                $user['email'],
                $user['role'],
                $user['status']
            ]);

            header("Location: index.php?page=users&message=User+created+successfully");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<div class="users-create fade-in">
    <div class="card">
        <div class="card-header bg-gray-800 text-white px-6 py-3 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Add New User</h3>
        </div>
        <div class="card-body p-6">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="index.php?page=users&action=create" class="form">
                <!-- User Information Section -->
                <div class="form-section">
                    <h4>User Information</h4>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                        </div>
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
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                    <option value="viewer" <?php echo $user['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="status">Status *</label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo $user['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save User</button>
                    <a href="index.php?page=users" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/users_create.js"></script>
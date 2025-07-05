<?php
/**
 * Edit User Profile
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

$error = '';
$success = '';

// Get current user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: logout.php");
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($name) || empty($email)) {
        $error = "Name and email are required.";
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    // If changing password, validate current password
    elseif (!empty($new_password) && !password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    }
    // Validate new password confirmation
    elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    }
    // Validate password strength
    elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    }
    else {
        try {
            // Check if email is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $error = "This email address is already in use by another user.";
            } else {
                // Update user profile
                if (!empty($new_password)) {
                    // Update with new password
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, password = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $name,
                        $email,
                        password_hash($new_password, PASSWORD_DEFAULT),
                        $_SESSION['user_id']
                    ]);
                } else {
                    // Update without changing password
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $name,
                        $email,
                        $_SESSION['user_id']
                    ]);
                }

                // Update session username if it was changed
                $_SESSION['username'] = $user['username'];

                // Log action
                log_action('update_profile', "Updated profile information");

                $success = "Profile updated successfully!";
                header("Location: index.php?page=profile&message=" . urlencode("Profile updated successfully!"));
                exit;
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="profile-edit fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Edit Profile</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($display_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($display_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($display_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($display_message); ?></div>
            <?php endif; ?>

            <form method="POST" class="profile-form" id="profileEditForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <h4>Basic Information</h4>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small class="form-text">Username cannot be changed</small>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" name="role" class="form-control" 
                               value="<?php echo ucfirst($user['role']); ?>" disabled>
                        <small class="form-text">Role is managed by administrators</small>
                    </div>
                </div>

                <!-- Password Change -->
                <div class="form-section">
                    <h4>Change Password</h4>
                    <p class="form-description">Leave password fields empty if you don't want to change your password.</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control">
                        <small class="form-text">Required only if changing password</small>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" minlength="6">
                                <small class="form-text">Minimum 6 characters</small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="index.php?page=profile" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/profile_edit.js"></script>
<?php
// JavaScript to clear URL parameters
if (!empty($display_message) || !empty($display_error)) {
    echo "<script type='text/javascript'>
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/&?(message|error)=[^&]*/g, ''));
    </script>";
}
?>

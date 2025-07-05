<?php
/**
 * Delete User
 */

// Ensure user has edit permissions
if (!$can_edit) {
    set_flash_message('danger', "Access denied.");
    header("Location: index.php?page=users");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', "Invalid user ID.");
    header("Location: index.php?page=users");
    exit;
}

$user_id = $_GET['id'];

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        set_flash_message('danger', "User not found.");
        header("Location: index.php?page=users");
        exit;
    }
    
    // Prevent deletion of current user
    if ($user_id == $_SESSION['user_id']) {
        set_flash_message('danger', "Cannot delete your own account.");
        header("Location: index.php?page=users");
        exit;
    }
    
    // Check if user has associated transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $transaction_count = $stmt->fetch()['count'];
    
    if ($transaction_count > 0) {
        set_flash_message('danger', "Cannot delete user: User has associated transactions.");
        header("Location: index.php?page=users");
        exit;
    }
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Log action
    log_action('delete_user', "Deleted user: {$user['username']}");
    
    set_flash_message('success', "User deleted successfully.");
    header("Location: index.php?page=users");
    exit;
    
} catch (PDOException $e) {
    set_flash_message('danger', "Error deleting user: " . $e->getMessage());
    header("Location: index.php?page=users");
    exit;
}
?>

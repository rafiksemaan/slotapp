<?php
/**
 * Delete User
 */

// Ensure user has edit permissions
if (!$can_edit) {
    header("Location: index.php?page=users&error=Access denied");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=users&error=Invalid user ID");
    exit;
}

$user_id = $_GET['id'];

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: index.php?page=users&error=User not found");
        exit;
    }
    
    // Prevent deletion of current user
    if ($user_id == $_SESSION['user_id']) {
        header("Location: index.php?page=users&error=Cannot delete your own account");
        exit;
    }
    
    // Check if user has associated transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $transaction_count = $stmt->fetch()['count'];
    
    if ($transaction_count > 0) {
        header("Location: index.php?page=users&error=Cannot delete user: User has associated transactions");
        exit;
    }
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Log action
    log_action('delete_user', "Deleted user: {$user['username']}");
    
    header("Location: index.php?page=users&message=User deleted successfully");
    exit;
    
} catch (PDOException $e) {
    header("Location: index.php?page=users&error=Error deleting user: " . urlencode($e->getMessage()));
    exit;
}
?>
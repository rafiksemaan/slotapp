<?php
/**
 * Delete machine group
 */

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?page=machine_groups");
    exit;
}

$group_id = $_GET['id'];

try {
    // Check if group exists
    $stmt = $conn->prepare("SELECT id, name FROM machine_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();
    
    if (!$group) {
        header("Location: index.php?page=machine_groups&error=Group not found");
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Delete group members first (due to foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM machine_group_members WHERE group_id = ?");
    $stmt->execute([$group_id]);
    
    // Delete group
    $stmt = $conn->prepare("DELETE FROM machine_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    
    // Commit transaction
    $conn->commit();
    
    // Log action
    log_action('delete_machine_group', "Deleted machine group: {$group['name']}");
    
    header("Location: index.php?page=machine_groups&message=Machine group deleted successfully");
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollback();
    header("Location: index.php?page=machine_groups&error=Error deleting group: " . urlencode($e->getMessage()));
    exit;
}
?>
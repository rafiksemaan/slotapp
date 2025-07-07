<?php
/**
 * Delete machine group
 */

// Check if ID is provided
$group_id = get_input(INPUT_GET, 'id', 'int');
if (empty($group_id)) {
    set_flash_message('danger', "Group ID not provided.");
    header("Location: index.php?page=machine_groups");
    exit;
}

try {
    // Check if group exists
    $stmt = $conn->prepare("SELECT id, name FROM machine_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();
    
    if (!$group) {
        set_flash_message('danger', "Group not found.");
        header("Location: index.php?page=machine_groups");
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
    
    set_flash_message('success', "Machine group deleted successfully.");
    header("Location: index.php?page=machine_groups");
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollback();
    set_flash_message('danger', "Error deleting group: " . $e->getMessage());
    header("Location: index.php?page=machine_groups");
    exit;
}
?>

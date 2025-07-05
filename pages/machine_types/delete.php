<?php
/**
 * Delete machine type
 */

// Check if ID is provided
if (!isset($_GET['id'])) {
    set_flash_message('danger', "Machine type ID not provided.");
    header("Location: index.php?page=machine_types");
    exit;
}

$type_id = $_GET['id'];

try {
    // Check if machine type exists
    $stmt = $conn->prepare("SELECT id, name FROM machine_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $type = $stmt->fetch();
    
    if (!$type) {
        set_flash_message('danger', "Machine type not found.");
        header("Location: index.php?page=machine_types");
        exit;
    }
    
    // Check if type has associated machines
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM machines WHERE type_id = ?");
    $stmt->execute([$type_id]);
    $machine_count = $stmt->fetch()['count'];
    
    if ($machine_count > 0) {
        set_flash_message('danger', "Cannot delete machine type: It has associated machines.");
        header("Location: index.php?page=machine_types");
        exit;
    }
    
    // Delete machine type
    $stmt = $conn->prepare("DELETE FROM machine_types WHERE id = ?");
    $stmt->execute([$type_id]);
    
    // Log action
    log_action('delete_machine_type', "Deleted machine type: {$type['name']}");
    
    set_flash_message('success', "Machine type deleted successfully.");
    header("Location: index.php?page=machine_types");
    exit;
    
} catch (PDOException $e) {
    set_flash_message('danger', "Error deleting machine type: " . $e->getMessage());
    header("Location: index.php?page=machine_types");
    exit;
}
?>

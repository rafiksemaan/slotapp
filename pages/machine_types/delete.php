<?php
/**
 * Delete machine type
 */

// Check if ID is provided
if (!isset($_GET['id'])) {
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
        header("Location: index.php?page=machine_types&message=Machine type not found");
        exit;
    }
    
    // Check if type has associated machines
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM machines WHERE type_id = ?");
    $stmt->execute([$type_id]);
    $machine_count = $stmt->fetch()['count'];
    
    if ($machine_count > 0) {
        header("Location: index.php?page=machine_types&message=Cannot delete machine type: It has associated machines");
        exit;
    }
    
    // Delete machine type
    $stmt = $conn->prepare("DELETE FROM machine_types WHERE id = ?");
    $stmt->execute([$type_id]);
    
    // Log action
    log_action('delete_machine_type', "Deleted machine type: {$type['name']}");
    
    header("Location: index.php?page=machine_types&message=Machine type deleted successfully");
    exit;
    
} catch (PDOException $e) {
    header("Location: index.php?page=machine_types&message=Error deleting machine type: " . urlencode($e->getMessage()));
    exit;
}
?>
<?php
/**
 * Delete Meter Entry
 */

// Check if ID is provided
$meter_id = get_input(INPUT_GET, 'id', 'int');
if (empty($meter_id)) {
    set_flash_message('danger', "Meter ID not provided.");
    header("Location: index.php?page=meters");
    exit;
}

try {
    // Check if meter entry exists
    $stmt = $conn->prepare("SELECT id, machine_id, operation_date FROM meters WHERE id = ?");
    $stmt->execute([$meter_id]);
    $meter = $stmt->fetch();
    
    if (!$meter) {
        set_flash_message('danger', "Meter entry not found.");
        header("Location: index.php?page=meters");
        exit;
    }
    
    // Delete meter entry
    $stmt = $conn->prepare("DELETE FROM meters WHERE id = ?");
    $stmt->execute([$meter_id]);
    
    // Log action
    log_action('delete_meter', "Deleted meter entry ID: {$meter_id} for machine {$meter['machine_id']} on {$meter['operation_date']}");
    
    set_flash_message('success', "Meter entry deleted successfully.");
    header("Location: index.php?page=meters");
    exit;
    
} catch (PDOException $e) {
    set_flash_message('danger', "Error deleting meter entry: " . $e->getMessage());
    header("Location: index.php?page=meters");
    exit;
}
?>

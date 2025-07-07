<?php
/**
 * Delete Guest Upload and Associated Data
 */

// Ensure user has edit permissions
if (!$can_edit) {
    set_flash_message('danger', "Access denied.");
    header("Location: index.php?page=guest_tracking");
    exit;
}

// Check if upload date is provided
$upload_date = get_input(INPUT_GET, 'upload_date', 'string');
if (empty($upload_date)) {
    set_flash_message('danger', "Upload date is required.");
    header("Location: index.php?page=guest_tracking");
    exit;
}

try {
    // Check if upload exists
    $check_stmt = $conn->prepare("SELECT upload_filename FROM guest_uploads WHERE upload_date = ?");
    $check_stmt->execute([$upload_date]);
    $upload = $check_stmt->fetch();
    
    if (!$upload) {
        set_flash_message('danger', "Upload not found.");
        header("Location: index.php?page=guest_tracking");
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Delete guest data for this upload date
    $data_stmt = $conn->prepare("DELETE FROM guest_data WHERE upload_date = ?");
    $data_stmt->execute([$upload_date]);
    $deleted_records = $data_stmt->rowCount();
    
    // Delete upload record
    $upload_stmt = $conn->prepare("DELETE FROM guest_uploads WHERE upload_date = ?");
    $upload_stmt->execute([$upload_date]);
    
    // Clean up guests that no longer have any data
    $cleanup_stmt = $conn->prepare("
        DELETE FROM guests 
        WHERE guest_code_id NOT IN (SELECT DISTINCT guest_code_id FROM guest_data)
    ");
    $cleanup_stmt->execute();
    $cleaned_guests = $cleanup_stmt->rowCount();
    
    // Commit transaction
    $conn->commit();
    
    // Log action
    log_action('delete_guest_upload', "Deleted guest upload for date: $upload_date, Records: $deleted_records, Cleaned guests: $cleaned_guests");
    
    set_flash_message('success', "Upload deleted successfully. Removed $deleted_records records and cleaned up $cleaned_guests guests.");
    header("Location: index.php?page=guest_tracking");
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollback();
    set_flash_message('danger', "Error deleting upload: " . $e->getMessage());
    header("Location: index.php?page=guest_tracking");
    exit;
}
?>

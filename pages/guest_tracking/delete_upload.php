<?php
/**
 * Delete Guest Upload and Associated Data
 */

// Ensure user has edit permissions
if (!$can_edit) {
    header("Location: index.php?page=guest_tracking&error=Access denied");
    exit;
}

// Check if upload date is provided
if (!isset($_GET['upload_date'])) {
    header("Location: index.php?page=guest_tracking&error=Upload date is required");
    exit;
}

$upload_date = $_GET['upload_date'];

try {
    // Check if upload exists
    $check_stmt = $conn->prepare("SELECT upload_filename FROM guest_uploads WHERE upload_date = ?");
    $check_stmt->execute([$upload_date]);
    $upload = $check_stmt->fetch();
    
    if (!$upload) {
        header("Location: index.php?page=guest_tracking&error=Upload not found");
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
    
    header("Location: index.php?page=guest_tracking&message=Upload deleted successfully. Removed $deleted_records records and cleaned up $cleaned_guests guests.");
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollback();
    header("Location: index.php?page=guest_tracking&error=Error deleting upload: " . urlencode($e->getMessage()));
    exit;
}
?>
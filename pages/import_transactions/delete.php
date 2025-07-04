<?php
/**
 * Delete Transaction Upload and Associated Transactions
 */

// Start session and include required files
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check permissions: Only editors and administrators can access this page
if (!has_permission('editor')) {
    header("Location: ../../index.php?page=import_transactions&error=Access denied");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../index.php?page=import_transactions&error=Upload ID is required");
    exit;
}

$upload_id = $_GET['id'];

try {
    // Start transaction
    $conn->beginTransaction();

    // Get upload details to find the operation_date
    $upload_stmt = $conn->prepare("SELECT upload_date, upload_filename FROM transaction_uploads WHERE id = ?");
    $upload_stmt->execute([$upload_id]);
    $upload = $upload_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$upload) {
        $conn->rollBack();
        header("Location: ../../index.php?page=import_transactions&error=Upload record not found");
        exit;
    }

    $operation_date_to_delete = $upload['upload_date'];
    $upload_filename = $upload['upload_filename'];

    // Delete associated transactions for this operation_date and filename
    // This assumes transactions imported from a specific file for a specific date can be uniquely identified.
    // If multiple files can be imported for the same operation_date, you might need a more robust linking.
    // For this implementation, we'll rely on the operation_date and the notes field (which contains the filename).
    $delete_transactions_stmt = $conn->prepare("
        DELETE FROM transactions 
        WHERE operation_date = ? AND notes LIKE ?
    ");
    $delete_transactions_stmt->execute([$operation_date_to_delete, "%Imported from {$upload_filename}%"]);
    $deleted_transactions_count = $delete_transactions_stmt->rowCount();

    // Delete the upload record itself
    $delete_upload_stmt = $conn->prepare("DELETE FROM transaction_uploads WHERE id = ?");
    $delete_upload_stmt->execute([$upload_id]);

    // Commit transaction
    $conn->commit();

    // Log action
    log_action('delete_transaction_upload', "Deleted transaction upload ID: {$upload_id} (File: {$upload_filename}, Date: {$operation_date_to_delete}). Removed {$deleted_transactions_count} associated transactions.");

    header("Location: ../../index.php?page=import_transactions&message=Upload and {$deleted_transactions_count} associated transactions deleted successfully.");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    header("Location: ../../index.php?page=import_transactions&error=Error deleting upload: " . urlencode($e->getMessage()));
    exit;
}
?>

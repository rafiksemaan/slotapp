<?php
// Remove temporary error display for debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Remove session_start() as it's already handled by index.php
// session_start();

// Use absolute paths for includes to avoid issues when included from other files
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Check permissions: Only editors and administrators can access this page
if (!has_permission('editor')) {
    header("Location: ../../index.php?page=import_transactions&error=Access denied");
    exit;
}

// Check if ID is provided and is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../index.php?page=import_transactions&error=Invalid upload ID provided.");
    exit;
}

$upload_id = (int)$_GET['id'];

try {
    // Start transaction
    $conn->beginTransaction();

    // Get upload details to find the operation_date and filename
    $upload_stmt = $conn->prepare("SELECT upload_date, upload_filename FROM transaction_uploads WHERE id = ?");
    $upload_stmt->execute([$upload_id]);
    $upload = $upload_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$upload) {
        $conn->rollBack(); // If transaction was started
        error_log("Delete failed: Upload record not found for ID: {$upload_id}"); // Log this
        header("Location: ../../index.php?page=import_transactions&error=Upload record not found for ID: {$upload_id}");
        exit;
    }

    $operation_date_to_delete = $upload['upload_date'];
    $upload_filename = $upload['upload_filename'];

    // Log the values being used for deletion
    // error_log("Attempting to delete for upload_id: {$upload_id}");
    // error_log("Operation Date to Delete: {$operation_date_to_delete}");
    // error_log("Upload Filename: {$upload_filename}");

    // Escape '%' and '_' characters in the filename for use in LIKE clause
    $escaped_filename = str_replace(['%', '_'], ['\\%', '\\_'], $upload_filename);
    // error_log("Escaped Filename for LIKE: {$escaped_filename}");

    // Delete associated transactions for this operation_date and filename
    $delete_transactions_sql = "DELETE FROM transactions WHERE operation_date = ? AND notes LIKE ? ESCAPE '\\'";
    // error_log("Delete Transactions SQL: {$delete_transactions_sql}");
    // error_log("Delete Transactions Params: [{$operation_date_to_delete}, %Imported from {$escaped_filename}%]");

    $delete_transactions_stmt = $conn->prepare($delete_transactions_sql);
    $delete_transactions_stmt->execute([$operation_date_to_delete, "%Imported from {$escaped_filename}%"]);
    $deleted_transactions_count = $delete_transactions_stmt->rowCount();
    // error_log("Number of transactions deleted: {$deleted_transactions_count}");

    // Delete the upload record itself
    $delete_upload_sql = "DELETE FROM transaction_uploads WHERE id = ?";
    // error_log("Delete Upload SQL: {$delete_upload_sql}");
    // error_log("Delete Upload Params: [{$upload_id}]");

    $delete_upload_stmt = $conn->prepare($delete_upload_sql);
    $delete_upload_stmt->execute([$upload_id]);
    $deleted_upload_count = $delete_upload_stmt->rowCount();
    // error_log("Number of upload records deleted: {$deleted_upload_count}");


    // Commit transaction
    $conn->commit();

    // Log action
    log_action('delete_transaction_upload', "Deleted transaction upload ID: {$upload_id} (File: {$upload_filename}, Date: {$operation_date_to_delete}). Removed {$deleted_transactions_count} associated transactions.");

    header("Location: ../../index.php?page=import_transactions&message=Upload and {$deleted_transactions_count} associated transactions deleted successfully.");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("PDOException during delete: " . $e->getMessage()); // Log the exception
    header("Location: ../../index.php?page=import_transactions&error=Error deleting upload: " . urlencode($e->getMessage()));
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    error_log("General Exception during delete: " . $e->getMessage()); // Log general exceptions
    header("Location: ../../index.php?page=import_transactions&error=Error deleting upload: " . urlencode($e->getMessage()));
    exit;
}
?>

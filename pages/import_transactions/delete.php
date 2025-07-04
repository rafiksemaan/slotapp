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

    // Get upload details to find the operation_date and filename
    $upload_stmt = $conn->prepare("SELECT upload_date, upload_filename FROM transaction_uploads WHERE id = ?");
    $upload_stmt->execute([$upload_id]);
    $upload = $upload_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$upload) {
        $conn->rollBack(); // If transaction was started
        header("Location: ../../index.php?page=import_transactions&error=Upload record not found for ID: {$upload_id}");
        exit;
    }

    $operation_date_to_delete = $upload['upload_date'];
    $upload_filename = $upload['upload_filename'];

    // Escape '%' and '_' characters in the filename for use in LIKE clause
    // The ESCAPE '\' clause tells SQL to treat '\' as the escape character.
    $escaped_filename = str_replace(['%', '_'], ['\\%', '\\_'], $upload_filename);
	
	$like_pattern = "%Imported from {$escaped_filename}%";

		// --- DEBUGGING START ---
		// Fetch a sample transaction that should be deleted to inspect its notes field
		$debug_stmt = $conn->prepare("
			SELECT id, notes, operation_date
			FROM transactions
			WHERE operation_date = ? AND notes LIKE ? ESCAPE '\\'
			LIMIT 1
		");
		$debug_stmt->execute([$operation_date_to_delete, $like_pattern]);
		$sample_transaction = $debug_stmt->fetch(PDO::FETCH_ASSOC);

		echo "DEBUG INFO:<br>";
		echo "Upload ID: " . htmlspecialchars($upload_id) . "<br>";
		echo "Operation Date to Delete: " . htmlspecialchars($operation_date_to_delete) . "<br>";
		echo "Upload Filename: " . htmlspecialchars($upload_filename) . "<br>";
		echo "Escaped Filename: " . htmlspecialchars($escaped_filename) . "<br>";
		echo "LIKE Pattern (used in query): '" . htmlspecialchars($like_pattern) . "'<br>";

		if ($sample_transaction) {
			echo "Sample Transaction Found:<br>";
			echo "  ID: " . htmlspecialchars($sample_transaction['id']) . "<br>";
			echo "  Notes (from DB): '" . htmlspecialchars($sample_transaction['notes']) . "'<br>";
			echo "  Operation Date (from DB): " . htmlspecialchars($sample_transaction['operation_date']) . "<br>";
		} else {
			echo "No sample transaction found matching criteria. This means the LIKE pattern or operation_date might be incorrect.<br>";
		}
		echo "<br>Stopping execution for debug. No deletion performed.<br>";
		$conn->rollBack(); // Ensure no changes are committed during debug
		exit;
		// --- DEBUGGING END ---


    // Delete associated transactions for this operation_date and filename
    // The notes field is set as "Imported from {filename}" during import.
    $delete_transactions_stmt = $conn->prepare("
    DELETE FROM transactions 
    WHERE operation_date = ? AND notes LIKE ? ESCAPE '\\'
	");
	$delete_transactions_stmt->execute([$operation_date_to_delete, "%Imported from {$escaped_filename}%"]);
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

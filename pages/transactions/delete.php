<?php
/**
 * Delete a transaction
 */

// Ensure user has edit permissions
if (!$can_edit) {
    header("Location: index.php?page=transactions&error=Access denied");
    exit;
}

// Check if ID was provided and is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=transactions&error=Invalid transaction ID");
    exit;
}

$transaction_id = $_GET['id'];

try {
    // Check if transaction exists before deleting
    $stmt = $conn->prepare("SELECT id FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);
    
    if ($stmt->rowCount() === 0) {
        header("Location: index.php?page=transactions&error=Transaction not found");
        exit;
    }

    // Delete the transaction
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);

    // Log action
    log_action('delete_transaction', "Deleted transaction ID: {$transaction_id}");

    // Redirect with success message
    header("Location: index.php?page=transactions&message=Transaction deleted successfully");
    exit;

} catch (PDOException $e) {
    // Handle database error
    header("Location: index.php?page=transactions&error=Database error: " . $e->getMessage());
    exit;
}
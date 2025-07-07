<?php
/**
 * Delete a transaction
 */

// Ensure user has edit permissions
if (!$can_edit) {
    set_flash_message('danger', "Access denied");
    header("Location: index.php?page=transactions");
    exit;
}

// Check if ID was provided and is valid
$transaction_id = get_input(INPUT_GET, 'id', 'int');
if (empty($transaction_id)) {
    set_flash_message('danger', "Invalid transaction ID");
    header("Location: index.php?page=transactions");
    exit;
}

try {
    // Check if transaction exists before deleting
    $stmt = $conn->prepare("SELECT id FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);
    
    if ($stmt->rowCount() === 0) {
        set_flash_message('danger', "Transaction not found");
        header("Location: index.php?page=transactions");
        exit;
    }

    // Delete the transaction
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);

    // Log action
    log_action('delete_transaction', "Deleted transaction ID: {$transaction_id}");

    // Redirect with success message
    set_flash_message('success', "Transaction deleted successfully");
    header("Location: index.php?page=transactions");
    exit;

} catch (PDOException $e) {
    // Handle database error
    set_flash_message('danger', "Database error: " . $e->getMessage());
    header("Location: index.php?page=transactions");
    exit;
}

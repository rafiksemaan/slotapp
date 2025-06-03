<?php
/**
 * Delete a slot machine
 */

// Ensure user has edit permissions
if (!$can_edit) {
    header("Location: index.php?page=machines&error=Access denied");
    exit;
}

// Validate machine ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=machines&error=Invalid machine ID");
    exit;
}

$machine_id = $_GET['id'];

try {
    // Check if machine exists
    $stmt = $conn->prepare("SELECT id, machine_number FROM machines WHERE id = ?");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        header("Location: index.php?page=machines&error=Machine not found");
        exit;
    }

    // Delete the machine
    $stmt = $conn->prepare("DELETE FROM machines WHERE id = ?");
    $stmt->execute([$machine_id]);

    // Log action
    log_action('delete_machine', "Deleted machine: {$machine['machine_number']}");

    // Redirect with success message
    header("Location: index.php?page=machines&message=Machine deleted successfully");
    exit;

} catch (PDOException $e) {
    // Handle error
    header("Location: index.php?page=machines&error=Database error: " . $e->getMessage());
    exit;
}
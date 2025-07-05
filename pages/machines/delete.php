<?php
/**
 * Delete a slot machine
 */

// Ensure user has edit permissions
if (!$can_edit) {
    set_flash_message('danger', "Access denied.");
    header("Location: index.php?page=machines");
    exit;
}

// Validate machine ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', "Invalid machine ID.");
    header("Location: index.php?page=machines");
    exit;
}

$machine_id = $_GET['id'];

try {
    // Check if machine exists
    $stmt = $conn->prepare("SELECT id, machine_number FROM machines WHERE id = ?");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        set_flash_message('danger', "Machine not found.");
        header("Location: index.php?page=machines");
        exit;
    }

    // Delete the machine
    $stmt = $conn->prepare("DELETE FROM machines WHERE id = ?");
    $stmt->execute([$machine_id]);

    // Log action
    log_action('delete_machine', "Deleted machine: {$machine['machine_number']}");

    // Redirect with success message
    set_flash_message('success', "Machine deleted successfully.");
    header("Location: index.php?page=machines");
    exit;

} catch (PDOException $e) {
    // Handle error
    set_flash_message('danger', "Database error: " . $e->getMessage());
    header("Location: index.php?page=machines");
    exit;
}

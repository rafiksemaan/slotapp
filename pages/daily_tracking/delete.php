<?php
/**
 * Delete Daily Tracking Entry
 */

// Ensure user has edit permissions
if (!has_permission('editor')) {
    set_flash_message('danger', "Access denied.");
    header("Location: index.php?page=daily_tracking");
    exit;
}

// Check if ID was provided and is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', "Invalid daily tracking ID.");
    header("Location: index.php?page=daily_tracking");
    exit;
}

$tracking_id = $_GET['id'];

try {
    // Fetch the tracking date before deleting for logging purposes
    $stmt = $conn->prepare("SELECT tracking_date FROM daily_tracking WHERE id = ?");
    $stmt->execute([$tracking_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        set_flash_message('danger', "Daily tracking entry not found.");
        header("Location: index.php?page=daily_tracking");
        exit;
    }

    // Delete the daily tracking entry
    $stmt = $conn->prepare("DELETE FROM daily_tracking WHERE id = ?");
    $stmt->execute([$tracking_id]);

    // Log action
    log_action('delete_daily_tracking', "Deleted daily tracking entry for date: {$entry['tracking_date']}");

    // Redirect with success message
    set_flash_message('success', "Daily tracking entry deleted successfully.");
    header("Location: index.php?page=daily_tracking");
    exit;

} catch (PDOException $e) {
    // Handle database error
    set_flash_message('danger', "Database error: " . $e->getMessage());
    header("Location: index.php?page=daily_tracking");
    exit;
}
?>

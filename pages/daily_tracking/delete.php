<?php
/**
 * Delete Daily Tracking Entry
 */

// Ensure user has edit permissions
if (!has_permission('editor')) {
    header("Location: index.php?page=daily_tracking&error=Access denied");
    exit;
}

// Check if ID was provided and is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=daily_tracking&error=Invalid daily tracking ID");
    exit;
}

$tracking_id = $_GET['id'];

try {
    // Fetch the tracking date before deleting for logging purposes
    $stmt = $conn->prepare("SELECT tracking_date FROM daily_tracking WHERE id = ?");
    $stmt->execute([$tracking_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        header("Location: index.php?page=daily_tracking&error=Daily tracking entry not found");
        exit;
    }

    // Delete the daily tracking entry
    $stmt = $conn->prepare("DELETE FROM daily_tracking WHERE id = ?");
    $stmt->execute([$tracking_id]);

    // Log action
    log_action('delete_daily_tracking', "Deleted daily tracking entry for date: {$entry['tracking_date']}");

    // Redirect with success message
    header("Location: index.php?page=daily_tracking&message=Daily tracking entry deleted successfully");
    exit;

} catch (PDOException $e) {
    // Handle database error
    header("Location: index.php?page=daily_tracking&error=Database error: " . $e->getMessage());
    exit;
}
?>

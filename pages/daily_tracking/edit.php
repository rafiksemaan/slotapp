<?php
/**
 * Edit Daily Tracking Entry
 */

// Check if an ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=daily_tracking");
    exit;
}

$tracking_id = $_GET['id'];
$error = '';
$success = '';

// Get current tracking data
try {
    $stmt = $conn->prepare("SELECT * FROM daily_tracking WHERE id = ?");
    $stmt->execute([$tracking_id]);
    $tracking_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tracking_data) {
        header("Location: index.php?page=daily_tracking&error=Daily tracking entry not found");
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php?page=daily_tracking&error=Database error");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking_date = sanitize_input($_POST['tracking_date'] ?? '');
    $slots_drop = sanitize_input($_POST['slots_drop'] ?? '');
    $slots_out = sanitize_input($_POST['slots_out'] ?? '');
    $gambee_drop = sanitize_input($_POST['gambee_drop'] ?? '');
    $gambee_out = sanitize_input($_POST['gambee_out'] ?? '');
    $coins_drop = sanitize_input($_POST['coins_drop'] ?? '');
    $coins_out = sanitize_input($_POST['coins_out'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($tracking_date)) {
        $error = "Tracking date is required.";
    } else {
        try {
            // Check if another entry exists for this date (excluding current entry)
            $check_stmt = $conn->prepare("SELECT id FROM daily_tracking WHERE tracking_date = ? AND id != ?");
            $check_stmt->execute([$tracking_date, $tracking_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Another daily tracking entry already exists for this date.";
            } else {
                // Convert empty strings to 0 for numeric fields
                $slots_drop = empty($slots_drop) ? 0 : floatval($slots_drop);
                $slots_out = empty($slots_out) ? 0 : floatval($slots_out);
                $gambee_drop = empty($gambee_drop) ? 0 : floatval($gambee_drop);
                $gambee_out = empty($gambee_out) ? 0 : floatval($gambee_out);
                $coins_drop = empty($coins_drop) ? 0 : floatval($coins_drop);
                $coins_out = empty($coins_out) ? 0 : floatval($coins_out);

                // Calculate results and percentages
                $slots_result = $slots_drop - $slots_out;
                $gambee_result = $gambee_drop - $gambee_out;
                $coins_result = $coins_drop - $coins_out;
                
                $slots_percentage = $slots_drop > 0 ? (($slots_result / $slots_drop) * 100) : 0;
                $gambee_percentage = $gambee_drop > 0 ? (($gambee_result / $gambee_drop) * 100) : 0;
                $coins_percentage = $coins_drop > 0 ? (($coins_result / $coins_drop) * 100) : 0;
                
                $total_drop = $slots_drop + $gambee_drop + $coins_drop;
                $total_out = $slots_out + $gambee_out + $coins_out;
                $total_result
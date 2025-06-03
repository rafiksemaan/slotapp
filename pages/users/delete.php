<?php
/**
 * Delete User
 */

$can_edit = true; // Replace with real permission check if available
$user_id = $_GET['id'] ?? 0;

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        include('404.php');
        exit;
    }

    if ($can_edit) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        header("Location: index.php?page=users&message=User+deleted+successfully");
        exit;
    }
} catch (PDOException $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage());
    exit;
}
<?php
/**
 * Delete brand
 */

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?page=brands");
    exit;
}

$brand_id = $_GET['id'];

try {
    // Check if brand exists
    $stmt = $conn->prepare("SELECT id, name FROM brands WHERE id = ?");
    $stmt->execute([$brand_id]);
    $brand = $stmt->fetch();
    
    if (!$brand) {
        header("Location: index.php?page=brands&message=Brand not found");
        exit;
    }
    
    // Check if brand has associated machines
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM machines WHERE brand_id = ?");
    $stmt->execute([$brand_id]);
    $machine_count = $stmt->fetch()['count'];
    
    if ($machine_count > 0) {
        header("Location: index.php?page=brands&message=Cannot delete brand: It has associated machines");
        exit;
    }
    
    // Delete brand
    $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->execute([$brand_id]);
    
    // Log action
    log_action('delete_brand', "Deleted brand: {$brand['name']}");
    
    header("Location: index.php?page=brands&message=Brand deleted successfully");
    exit;
    
} catch (PDOException $e) {
    header("Location: index.php?page=brands&message=Error deleting brand: " . urlencode($e->getMessage()));
    exit;
}
?>
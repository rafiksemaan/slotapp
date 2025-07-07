<?php
/**
 * Delete brand
 */

// Check if ID is provided
$brand_id = get_input(INPUT_GET, 'id', 'int');
if (empty($brand_id)) {
    set_flash_message('danger', "Brand ID not provided or invalid.");
    header("Location: index.php?page=brands");
    exit;
}


try {
    // Check if brand exists
    $stmt = $conn->prepare("SELECT id, name FROM brands WHERE id = ?");
    $stmt->execute([$brand_id]);
    $brand = $stmt->fetch();
    
    if (!$brand) {
        set_flash_message('danger', "Brand not found.");
        header("Location: index.php?page=brands");
        exit;
    }
    
    // Check if brand has associated machines
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM machines WHERE brand_id = ?");
    $stmt->execute([$brand_id]);
    $machine_count = $stmt->fetch()['count'];
    
    if ($machine_count > 0) {
        set_flash_message('danger', "Cannot delete brand: It has associated machines.");
        header("Location: index.php?page=brands");
        exit;
    }
    
    // Delete brand
    $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->execute([$brand_id]);
    
    // Log action
    log_action('delete_brand', "Deleted brand: {$brand['name']}");
    
    set_flash_message('success', "Brand deleted successfully.");
    header("Location: index.php?page=brands");
    exit;
    
} catch (PDOException $e) {
    set_flash_message('danger', "Error deleting brand: " . $e->getMessage());
    header("Location: index.php?page=brands");
    exit;
}
?>

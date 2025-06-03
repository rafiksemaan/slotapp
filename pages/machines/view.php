<?php
/**
 * View slot machine details
 */

// Ensure we have an ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=machines");
    exit;
}

$machine_id = $_GET['id'];

// Initialize variables
$error = '';
$machine = null;

// Get machine data
try {
    $stmt = $conn->prepare("
        SELECT m.*, b.name AS brand_name 
        FROM machines m
        LEFT JOIN brands b ON m.brand_id = b.id
        WHERE m.id = ?
    ");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        header("Location: index.php?page=machines&error=Machine not found");
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="machine-view fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Machine Details</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <div class="machine-details">
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Machine Number:</strong>
                                <span><?php echo htmlspecialchars($machine['machine_number']); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Brand:</strong>
                                <span><?php echo htmlspecialchars($machine['brand_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Model:</strong>
                                <span><?php echo htmlspecialchars($machine['model']); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Type:</strong>
                                <span><?php echo htmlspecialchars($machine['type']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Credit Value:</strong>
                                <span><?php echo format_currency($machine['credit_value']); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Manufacturing Year:</strong>
                                <span><?php echo htmlspecialchars($machine['manufacturing_year'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>IP Address:</strong>
                                <span><?php echo htmlspecialchars($machine['ip_address'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>MAC Address:</strong>
                                <span><?php echo htmlspecialchars($machine['mac_address'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Serial Number:</strong>
                                <span><?php echo htmlspecialchars($machine['serial_number'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Status:</strong>
                                <span class="status status-<?php echo strtolower(htmlspecialchars($machine['status'])); ?>">
                                    <?php echo htmlspecialchars($machine['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="form-group" style="margin-top: 2rem;">
                        <?php if ($can_edit): ?>
                            <a href="index.php?page=machines&action=edit&id=<?php echo $machine_id; ?>" class="btn btn-primary">Edit Machine</a>
                        <?php endif; ?>
                        <a href="index.php?page=machines" class="btn btn-danger">Back to List</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
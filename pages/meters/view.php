<?php
/**
 * View Meter Entry Details
 */

// Ensure we have an ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=meters");
    exit;
}

$meter_id = $_GET['id'];

// Initialize variables
$error = '';
$meter = null;

// Get meter data with machine details and credit_value
try {
    $stmt = $conn->prepare("
        SELECT 
            me.*, 
            m.machine_number, 
            m.credit_value, -- Select credit_value
            m.system_comp,
            mt.name AS machine_type_name,
            u.username AS created_by_username,
            eu.username AS updated_by_username
        FROM meters me
        JOIN machines m ON me.machine_id = m.id
        LEFT JOIN machine_types mt ON m.type_id = mt.id
        LEFT JOIN users u ON me.created_by = u.id
        LEFT JOIN users eu ON me.updated_by = eu.id
        WHERE me.id = ?
    ");
    $stmt->execute([$meter_id]);
    $meter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meter) {
        header("Location: index.php?page=meters&error=Meter entry not found");
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="meter-view fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Meter Entry Details</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <div class="meter-details">
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Machine Number:</strong>
                                <span><?php echo htmlspecialchars($meter['machine_number']); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Operation Date:</strong>
                                <span><?php echo htmlspecialchars(format_date($meter['operation_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Meter Type:</strong>
                                <span><?php echo htmlspecialchars(ucfirst($meter['meter_type'])); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Machine Type:</strong>
                                <span><?php echo htmlspecialchars($meter['machine_type_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>System Compatibility:</strong>
                                <span><?php echo htmlspecialchars(ucfirst($meter['system_comp'])); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Credit Value:</strong>
                                <span><?php echo format_currency($meter['credit_value']); ?></span>
                            </div>
                        </div>
                    </div>

                    <h4 class="section-title" style="margin-top: 2rem;">Meter Readings (Multiplied by Credit Value)</h4>
                    <div class="row">
                        <?php if ($meter['meter_type'] === 'online' || $meter['meter_type'] === 'offline'): ?>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Total In:</strong>
                                    <span><?php echo format_currency(($meter['total_in'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Total Out:</strong>
                                    <span><?php echo format_currency(($meter['total_out'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Bills In:</strong>
                                    <span><?php echo format_currency(($meter['bills_in'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Handpay:</strong>
                                    <span><?php echo format_currency(($meter['handpay'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>JP:</strong>
                                    <span><?php echo format_currency(($meter['jp'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Bets:</strong>
                                    <span><?php echo format_currency(($meter['bets'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($meter['meter_type'] === 'coins'): ?>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Coins In:</strong>
                                    <span><?php echo format_currency(($meter['coins_in'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Coins Out:</strong>
                                    <span><?php echo format_currency(($meter['coins_out'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Coins Drop:</strong>
                                    <span><?php echo format_currency(($meter['coins_drop'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Bets:</strong>
                                    <span><?php echo format_currency(($meter['bets'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Handpay:</strong>
                                    <span><?php echo format_currency(($meter['handpay'] ?? 0) * $meter['credit_value']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h4 class="section-title" style="margin-top: 2rem;">Audit Information</h4>
                    <div class="row">
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Created By:</strong>
                                <span><?php echo htmlspecialchars($meter['created_by_username'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-group">
                                <strong>Created At:</strong>
                                <span><?php echo htmlspecialchars(format_datetime($meter['created_at'], 'd M Y H:i:s')); ?></span>
                            </div>
                        </div>
                        <?php if ($meter['updated_by']): ?>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Last Updated By:</strong>
                                    <span><?php echo htmlspecialchars($meter['updated_by_username'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-group">
                                    <strong>Last Updated At:</strong>
                                    <span><?php echo htmlspecialchars(format_datetime($meter['updated_at'], 'd M Y H:i:s')); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <div class="detail-group">
                                <strong>Manual Reading Notes:</strong>
                                <span><?php echo nl2br(htmlspecialchars($meter['manual_reading_notes'] ?? 'N/A')); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-group">
                                <strong>General Notes:</strong>
                                <span><?php echo nl2br(htmlspecialchars($meter['notes'] ?? 'N/A')); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-group" style="margin-top: 2rem;">
                        <a href="index.php?page=meters" class="btn btn-primary">Back to Meter List</a>
                        <?php if ($can_edit): ?>
                            <a href="index.php?page=meters&action=edit&id=<?php echo $meter['id']; ?>" class="btn btn-primary">Edit Meter Entry</a>
                            <a href="index.php?page=meters&action=delete&id=<?php echo $meter['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this meter entry?')">Delete Meter Entry</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

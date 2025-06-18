<?php
/**
 * View Guest Details
 */

// Get guest code ID from URL
$guest_code_id = $_GET['guest_code_id'] ?? '';

if (empty($guest_code_id)) {
    header("Location: index.php?page=guest_tracking&error=Guest Code ID is required");
    exit;
}

try {
    // Get guest information
    $guest_stmt = $conn->prepare("SELECT * FROM guests WHERE guest_code_id = ?");
    $guest_stmt->execute([$guest_code_id]);
    $guest = $guest_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$guest) {
        header("Location: index.php?page=guest_tracking&error=Guest not found");
        exit;
    }
    
    // Get guest data history
    $data_stmt = $conn->prepare("
        SELECT gd.*, gu.upload_filename 
        FROM guest_data gd
        LEFT JOIN guest_uploads gu ON gd.upload_date = gu.upload_date
        WHERE gd.guest_code_id = ?
        ORDER BY gd.upload_date DESC
    ");
    $data_stmt->execute([$guest_code_id]);
    $guest_data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_drop = array_sum(array_column($guest_data, 'drop_amount'));
    $total_result = array_sum(array_column($guest_data, 'result_amount'));
    $total_visits = array_sum(array_column($guest_data, 'visits'));
    $total_uploads = count($guest_data);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $guest = null;
    $guest_data = [];
    $total_drop = $total_result = $total_visits = $total_uploads = 0;
}
?>

<div class="guest-view-page fade-in">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
        <!-- Guest Information Card -->
        <div class="card mb-6">
            <div class="card-header">
                <h3>Guest Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="detail-group">
                            <strong>Guest Code ID:</strong>
                            <span><?php echo htmlspecialchars($guest['guest_code_id']); ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-group">
                            <strong>Guest Name:</strong>
                            <span><?php echo htmlspecialchars($guest['guest_name']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="detail-group">
                            <strong>First Recorded:</strong>
                            <span><?php echo format_datetime($guest['created_at'], 'd M Y H:i'); ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-group">
                            <strong>Last Updated:</strong>
                            <span><?php echo format_datetime($guest['updated_at'], 'd M Y H:i'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-container grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <div class="stat-card in p-4 rounded bg-opacity-10 bg-success-color text-center">
                <div class="stat-title uppercase text-sm text-muted">Total Drop</div>
                <div class="stat-value text-lg font-bold"><?php echo format_currency($total_drop); ?></div>
            </div>
            <div class="stat-card <?php echo $total_result >= 0 ? 'in' : 'out'; ?> p-4 rounded text-center bg-opacity-10 <?php echo $total_result >= 0 ? 'bg-success-color' : 'bg-danger-color'; ?>">
                <div class="stat-title uppercase text-sm text-muted">Total Result</div>
                <div class="stat-value text-lg font-bold"><?php echo format_currency($total_result); ?></div>
            </div>
            <div class="stat-card p-4 rounded bg-opacity-10 bg-warning-color text-center">
                <div class="stat-title uppercase text-sm text-muted">Total Visits</div>
                <div class="stat-value text-lg font-bold"><?php echo number_format($total_visits); ?></div>
            </div>
            <div class="stat-card p-4 rounded bg-opacity-10 bg-primary-color text-center">
                <div class="stat-title uppercase text-sm text-muted">Data Uploads</div>
                <div class="stat-value text-lg font-bold"><?php echo number_format($total_uploads); ?></div>
            </div>
        </div>

        <!-- Guest Data History -->
        <div class="card">
            <div class="card-header">
                <h3>Data History</h3>
            </div>
            <div class="card-body">
                <?php if (empty($guest_data)): ?>
                    <div class="text-center py-6">
                        <p class="text-muted">No data history found for this guest.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-800 text-white">
                                <tr>
                                    <th class="px-4 py-2 text-left">Upload Date</th>
                                    <th class="px-4 py-2 text-right">Drop Amount</th>
                                    <th class="px-4 py-2 text-right">Result Amount</th>
                                    <th class="px-4 py-2 text-right">Visits</th>
                                    <th class="px-4 py-2 text-left">Source File</th>
                                    <th class="px-4 py-2 text-left">Recorded At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($guest_data as $data): ?>
                                    <tr class="hover:bg-gray-800 transition duration-150">
                                        <td class="px-4 py-2 font-medium"><?php echo format_date($data['upload_date']); ?></td>
                                        <td class="px-4 py-2 text-right font-bold text-success-color"><?php echo format_currency($data['drop_amount']); ?></td>
                                        <td class="px-4 py-2 text-right font-bold <?php echo $data['result_amount'] >= 0 ? 'text-success-color' : 'text-danger-color'; ?>">
                                            <?php echo format_currency($data['result_amount']); ?>
                                        </td>
                                        <td class="px-4 py-2 text-right"><?php echo number_format($data['visits']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($data['upload_filename'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-2"><?php echo format_datetime($data['created_at'], 'd M Y H:i'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="form-group mt-6">
            <a href="index.php?page=guest_tracking" class="btn btn-primary">Back to Guest List</a>
        </div>
    <?php endif; ?>
</div>
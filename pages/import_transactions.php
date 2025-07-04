<?php
/**
 * Import Historical Transactions from CSV
 */

// Check permissions: Only editors and administrators can access this page
if (!has_permission('editor')) {
    include 'access_denied.php';
    exit;
}

$message = '';
$error = '';
$import_stats = [
    'total_files_processed' => 0,
    'total_transactions_imported' => 0,
    'files_with_errors' => [],
    'skipped_transactions' => 0,
    'skipped_files' => 0
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_files'])) {
    // Fetch machine mappings (machine_number => id) once
    $machine_map = [];
    try {
        $stmt = $conn->query("SELECT id, machine_number FROM machines");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $machine_map[$row['machine_number']] = $row['id'];
        }
    } catch (PDOException $e) {
        $error = "Database error fetching machines: " . $e->getMessage();
    }

    // Fetch transaction type mappings (name => id) once
    $transaction_type_map = [];
    try {
        $stmt = $conn->query("SELECT id, name FROM transaction_types");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transaction_type_map[$row['name']] = $row['id'];
        }
    } catch (PDOException $e) {
        $error = "Database error fetching transaction types: " . $e->getMessage();
    }

    if (empty($error)) {
        $uploaded_files = $_FILES['csv_files'];

        foreach ($uploaded_files['name'] as $key => $filename) {
            if ($uploaded_files['error'][$key] !== UPLOAD_ERR_OK) {
                $error_msg = "File '{$filename}' upload failed with error code: {$uploaded_files['error'][$key]}.";
                $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => $error_msg];
                $import_stats['skipped_files']++;
                continue;
            }

            $temp_file = $uploaded_files['tmp_name'][$key];
            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($file_extension !== 'csv') {
                $error_msg = "File '{$filename}' is not a CSV file. Skipping.";
                $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => $error_msg];
                $import_stats['skipped_files']++;
                continue;
            }

			// Validate new filename format: transactions_YYYY-MM-DD_daily_upload.csv
            if (!preg_match('/transactions_(\d{4}-\d{2}-\d{2})_daily_upload\.csv$/i', $filename, $matches)) {
                $error_msg = "Filename '{$filename}' does not match the expected format (e.g., transactions_YYYY-MM-DD_daily_upload.csv). Cannot determine operation date. Skipping.";
                $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => $error_msg];
                $import_stats['skipped_files']++;
                continue;
            }
            $operation_date_str = $matches[1]; // Extract YYYY-MM-DD directly


            $file_transactions_imported = 0;
            $file_skipped_transactions = 0;
            $file_errors = [];

                        try {
                $conn->beginTransaction();

                // Insert into transaction_uploads table first
                $upload_stmt = $conn->prepare("
                    INSERT INTO transaction_uploads (upload_date, upload_filename, uploaded_by, uploaded_at)
                    VALUES (?, ?, ?, ?)
                ");
                $upload_stmt->execute([$operation_date_str, $filename, $_SESSION['user_id'], date('Y-m-d H:i:s')]);
                $upload_id = $conn->lastInsertId(); // Get the ID of the new upload record

                if (($handle = fopen($temp_file, "r")) !== FALSE) {
                    $header = fgetcsv($handle); // Read header row
                    if ($header === FALSE) {
                        $file_errors[] = "Could not read header from '{$filename}'.";
                        fclose($handle);
                        $conn->rollBack();
                        // Delete the incomplete upload record if transaction was rolled back
                        $delete_upload_stmt = $conn->prepare("DELETE FROM transaction_uploads WHERE id = ?");
                        $delete_upload_stmt->execute([$upload_id]);
                        $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => implode(', ', $file_errors)];
                        $import_stats['skipped_files']++;
                        continue;
                    }

                    $header_map = array_flip(array_map('trim', array_map('strtolower', $header)));

                    // Expected columns in CSV
                    $expected_columns = ['machine_id', 'total_handpay', 'total_refill', 'total_ticket', 'cash_drop', 'coins_drop'];
                    $missing_columns = array_diff($expected_columns, array_keys($header_map));
                    if (!empty($missing_columns)) {
                        $file_errors[] = "Missing required columns in '{$filename}': " . implode(', ', $missing_columns);
                        fclose($handle);
                        $conn->rollBack();
                        // Delete the incomplete upload record if transaction was rolled back
                        $delete_upload_stmt = $conn->prepare("DELETE FROM transaction_uploads WHERE id = ?");
                        $delete_upload_stmt->execute([$upload_id]);
                        $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => implode(', ', $file_errors)];
                        $import_stats['skipped_files']++;
                        continue;
                    }

                    $insert_stmt = $conn->prepare("
                        INSERT INTO transactions (machine_id, transaction_type_id, amount, timestamp, operation_date, user_id, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    while (($row_data = fgetcsv($handle)) !== FALSE) {
                        if (count($row_data) < count($header)) {
                            $file_errors[] = "Skipping malformed row in '{$filename}': " . implode(',', $row_data);
                            $file_skipped_transactions++;
                            continue;
                        }

                        $machine_number_from_csv = trim($row_data[$header_map['machine_id']] ?? '');
                        $db_machine_id = $machine_map[$machine_number_from_csv] ?? null;

                        if (!$db_machine_id) {
                            $file_errors[] = "Unknown machine number '{$machine_number_from_csv}' in '{$filename}'. Skipping transactions for this machine in this row.";
                            $file_skipped_transactions++;
                            continue;
                        }

                        $transaction_types_to_import = [
                            'Handpay' => floatval($row_data[$header_map['total_handpay']] ?? 0),
                            'Refill' => floatval($row_data[$header_map['total_refill']] ?? 0),
                            'Ticket' => floatval($row_data[$header_map['total_ticket']] ?? 0),
                            'Cash Drop' => floatval($row_data[$header_map['cash_drop']] ?? 0),
                            'Coins Drop' => floatval($row_data[$header_map['coins_drop']] ?? 0)
                        ];

                        foreach ($transaction_types_to_import as $type_name => $amount) {
                            if ($amount > 0) {
                                $db_transaction_type_id = $transaction_type_map[$type_name] ?? null;
                                if ($db_transaction_type_id) {
                                    $insert_stmt->execute([
                                        $db_machine_id,
                                        $db_transaction_type_id,
                                        $amount,
                                        $operation_date_str . ' 23:59:59', // Use extracted operation_date for timestamp
                                        $operation_date_str, // Use extracted operation_date
                                        $_SESSION['user_id'],
                                        "Imported from {$filename}"
                                    ]);
                                    $file_transactions_imported++;
                                } else {
                                    $file_errors[] = "Unknown transaction type '{$type_name}' in database. Skipping.";
                                    $file_skipped_transactions++;
                                }
                            }
                        }
                    }
                    fclose($handle);
                } else {
                    $file_errors[] = "Could not open file '{$filename}'.";
                }

                $conn->commit();
                $import_stats['total_files_processed']++;
                $import_stats['total_transactions_imported'] += $file_transactions_imported;
                $import_stats['skipped_transactions'] += $file_skipped_transactions;

                if (!empty($file_errors)) {
                    $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => implode(', ', $file_errors)];
                }

            } catch (PDOException $e) {
                $conn->rollBack();
                // Delete the incomplete upload record if transaction was rolled back
                if (isset($upload_id)) {
                    $delete_upload_stmt = $conn->prepare("DELETE FROM transaction_uploads WHERE id = ?");
                    $delete_upload_stmt->execute([$upload_id]);
                }
                $error_msg = "Database error processing '{$filename}': " . $e->getMessage();
                $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => $error_msg];
                $import_stats['skipped_files']++;
            } catch (Exception $e) {
                $conn->rollBack();
                // Delete the incomplete upload record if transaction was rolled back
                if (isset($upload_id)) {
                    $delete_upload_stmt = $conn->prepare("DELETE FROM transaction_uploads WHERE id = ?");
                    $delete_upload_stmt->execute([$upload_id]);
                }
                $error_msg = "Error processing '{$filename}': " . $e->getMessage();
                $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => $error_msg];
                $import_stats['skipped_files']++;
            }
        }

        if (empty($import_stats['files_with_errors']) && $import_stats['total_transactions_imported'] > 0) {
            $message = "All selected files processed successfully! Imported {$import_stats['total_transactions_imported']} transactions.";
        } elseif ($import_stats['total_transactions_imported'] > 0) {
            $message = "Processed some files. Imported {$import_stats['total_transactions_imported']} transactions. See errors below for details.";
        } else {
            $error = "No transactions were imported. Please check the file format and content.";
        }
    }
}
?>

<div class="import-transactions-page fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Import Historical Transactions</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($import_stats['files_with_errors'])): ?>
                <div class="alert alert-warning">
                    <h4>⚠️ Issues Encountered During Import:</h4>
                    <ul>
                        <?php foreach ($import_stats['files_with_errors'] as $file_error): ?>
                            <li><strong><?php echo htmlspecialchars($file_error['filename']); ?>:</strong> <?php echo htmlspecialchars($file_error['error']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <h5>📋 File Format Requirements:</h5>
                <p>Upload one or more CSV files containing daily transaction data. Each file should represent a single day's data.</p>
                <ul>
                    <li>**Filename Format**: `transactions_YYYY-MM-DD_daily_upload.csv` (e.g., `transactions_2023-10-26_daily_upload.csv`). This is used to determine the `operation_date`.</li>
                    <li>**Required Columns (case-insensitive)**:
                        <ul>
                            <li>`machine_id` (This should be the machine number, e.g., "M001")</li>
                            <li>`total_handpay`</li>
                            <li>`total_refill`</li>
                            <li>`total_ticket`</li>
                            <li>`cash_drop`</li>
                            <li>`coins_drop`</li>
                        </ul>
                    </li>
                    <li>**Data**: Each row should contain the aggregated amounts for a specific machine for that day. Only non-zero amounts will be imported.</li>
                </ul>
                <p>Transactions will be recorded with the `operation_date` derived from the filename, and the `timestamp` set to `23:59:59` on that day.</p>
            </div>

            <form action="index.php?page=import_transactions" method="POST" enctype="multipart/form-data" id="importTransactionsForm">
                <div class="form-section">
                    <h4>Select CSV Files</h4>
                    <div class="form-group">
                        <label for="csv_files">Upload CSV Files *</label>
                        <input type="file" id="csv_files" name="csv_files[]" class="form-control" accept=".csv" multiple required>
                        <small class="form-text">Select one or more CSV files (e.g., `transactions_2023-10-26_daily_upload.csv`, `transactions_2023-10-27_daily_upload.csv`)</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Import Data</button>
                    <a href="index.php?page=import_transactions" class="btn btn-danger">Reset Form</a>
                </div>
            </form>

            <?php if ($import_stats['total_files_processed'] > 0 || $import_stats['skipped_files'] > 0): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Import Summary</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Files Processed Successfully:</strong> <?php echo $import_stats['total_files_processed']; ?></p>
                        <p><strong>Total Transactions Imported:</strong> <?php echo $import_stats['total_transactions_imported']; ?></p>
                        <p><strong>Files Skipped:</strong> <?php echo $import_stats['skipped_files']; ?></p>
                        <p><strong>Transactions Skipped (due to unknown machines/types or errors within files):</strong> <?php echo $import_stats['skipped_transactions']; ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Fetch upload history for display
$upload_history = [];
try {
    $history_stmt = $conn->query("
        SELECT tu.*, u.username as uploaded_by_username
        FROM transaction_uploads tu
        LEFT JOIN users u ON tu.uploaded_by = u.id
        ORDER BY tu.uploaded_at DESC
    ");
    $upload_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $history_error = "Error fetching upload history: " . $e->getMessage();
}
?>

<?php if (!empty($upload_history)): ?>
    <div class="card mt-6">
        <div class="card-header">
            <h3>Transaction Upload History</h3>
        </div>
        <div class="card-body">
            <?php if (isset($history_error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($history_error) ?></div>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Upload Date</th>
                            <th>Filename</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upload_history as $upload): ?>
                            <tr>
                                <td><?= htmlspecialchars(format_date($upload['upload_date'])) ?></td>
                                <td><?= htmlspecialchars($upload['upload_filename']) ?></td>
                                <td><?= htmlspecialchars($upload['uploaded_by_username'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(format_datetime($upload['uploaded_at'])) ?></td>
                                <td>
                                    <a href="index.php?page=import_transactions&action=delete_upload&id=<?= $upload['id'] ?>" 
                                       class="action-btn delete-btn" data-tooltip="Delete Upload" 
                                       data-confirm="Are you sure you want to delete this upload and all associated transactions?">
                                        <span class="menu-icon"><img src="<?= icon('delete') ?>"/></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>


<script src="assets/js/import_transactions.js"></script>

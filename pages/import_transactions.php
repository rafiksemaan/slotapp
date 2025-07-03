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

            // Extract month and year from filename (e.g., transactions_YYYY-MM.csv)
            if (!preg_match('/(\d{4}-\d{2})\.csv$/', $filename, $matches)) {
                $error_msg = "Filename '{$filename}' does not match expected format (e.g., transactions_YYYY-MM.csv). Cannot determine month/year. Skipping.";
                $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => $error_msg];
                $import_stats['skipped_files']++;
                continue;
            }
            $year_month = $matches[1];
            $operation_date_str = date('Y-m-t', strtotime($year_month . '-01')); // Last day of the month

            $file_transactions_imported = 0;
            $file_skipped_transactions = 0;
            $file_errors = [];

            try {
                $conn->beginTransaction();

                if (($handle = fopen($temp_file, "r")) !== FALSE) {
                    $header = fgetcsv($handle); // Read header row
                    if ($header === FALSE) {
                        $file_errors[] = "Could not read header from '{$filename}'.";
                        fclose($handle);
                        $conn->rollBack();
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
                                        $operation_date_str . ' 23:59:59', // Timestamp at end of operation day
                                        $operation_date_str,
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
                $error_msg = "Database error processing '{$filename}': " . $e->getMessage();
                $import_stats['files_with_errors'][] = ['filename' => $filename, 'error' => $error_msg];
                $import_stats['skipped_files']++;
            } catch (Exception $e) {
                $conn->rollBack();
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
                <p>Your Excel/CSV file must contain the following columns (in any order):</p>
                <ul>
                    <li>**Filename Format**: `transactions_YYYY-MM.csv` (e.g., `transactions_2023-10.csv`). This is used to determine the `operation_date`.</li>
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
                    <li>**Data**: Each row should contain the aggregated amounts for a specific machine for that month. Only non-zero amounts will be imported.</li>
                </ul>
                <p>Transactions will be recorded with the `operation_date` set to the last day of the month derived from the filename, and the `timestamp` set to `23:59:59` on that day.</p>
            </div>

            <form action="index.php?page=import_transactions" method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <h4>Select CSV Files</h4>
                    <div class="form-group">
                        <label for="csv_files">Upload CSV Files *</label>
                        <input type="file" id="csv_files" name="csv_files[]" class="form-control" accept=".csv" multiple required>
                        <small class="form-text">Select one or more CSV files (e.g., `transactions_2023-01.csv`, `transactions_2023-02.csv`)</small>
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

<script src="assets/js/import_transactions.js"></script>

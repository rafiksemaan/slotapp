<?php
/**
 * Upload Meter Data for Online Machines
 */

$message = '';
$error = '';
$upload_stats = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $upload_date = sanitize_input($_POST['upload_date'] ?? date('Y-m-d'));
    
    // Validate file upload
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('danger', "File upload failed. Please try again.");
    } elseif ($_FILES['csv_file']['size'] > 10 * 1024 * 1024) { // 10MB limit
        set_flash_message('danger', "File size too large. Maximum size is 10MB.");
    } else {
        $file_info = pathinfo($_FILES['csv_file']['name']);
        $allowed_extensions = ['csv'];
        
        if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
            set_flash_message('danger', "Invalid file type. Please upload a CSV file.");
        } else {
            try {
                // Process the uploaded file
                $upload_result = processMeterCSVFile($_FILES['csv_file'], $upload_date, $conn);
                
                if ($upload_result['success']) {
                    $upload_stats = $upload_result['stats'];
                    $message_text = "CSV file uploaded and processed successfully!";
                    $is_html_message = false;

                    if (!empty($upload_stats['errors'])) {
                        $message_text .= "<br>Some entries were skipped. Please review:<br><ul>";
                        foreach ($upload_stats['errors'] as $error_item) {
                            $message_text .= "<li>" . htmlspecialchars($error_item) . "</li>";
                        }
                        $message_text .= "</ul>";
                        $is_html_message = true;
                        set_flash_message('warning', $message_text, $is_html_message); // Change to warning if there are skipped entries
                    } else {
                        set_flash_message('success', $message_text, $is_html_message);
                    }
                    
                    // Log action
                    log_action('upload_meter_data', "Uploaded meter data for date: $upload_date, Records: {$upload_stats['total_records']}");
                } else {
                    set_flash_message('danger', $upload_result['error']); // This error is plain text
                }
            } catch (Exception $e) {
                set_flash_message('danger', "Error processing file: " . $e->getMessage());
            }
        }
    }
    header("Location: index.php?page=meters&action=upload");
    exit;
}

/**
 * Process uploaded CSV file for meter data
 */
function processMeterCSVFile($file, $operation_date, $conn) {
    $temp_file = $file['tmp_name'];
    $original_filename = $file['name'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        $data = [];
        if (($handle = fopen($temp_file, "r")) !== FALSE) {
            // Check for and remove UTF-8 BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xef\xbb\xbf") {
                fseek($handle, 0); // No BOM, rewind to the beginning
            }
            
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data found in the uploaded file.'];
        }
        
        // Expected headers for online machines
        // Updated to include all possible meter fields.
        $expected_headers = ['machine_id', 'operation_date', 'total_in', 'total_out', 'bills_in', 'ticket_in', 'ticket_out', 'jp', 'bets', 'handpay', 'coins_in', 'coins_out', 'coins_drop'];
        $headers = array_map('strtolower', array_map('trim', $data[0]));
        
        // Create header mapping
        $header_map = [];
        foreach ($headers as $index => $header_name) {
            $header_map[$header_name] = $index;
        }

        // Flexible Header Validation: Only machine_id and operation_date are strictly required.
        if (!isset($header_map['machine_id']) || !isset($header_map['operation_date'])) {
            return ['success' => false, 'error' => 'Missing required columns: machine_id and operation_date.'];
        }
        
        // Fetch machine mappings (machine_number => id, system_comp, machine_type) for ALL machines
        $machine_map = [];
        $stmt = $conn->query("SELECT m.machine_number, m.id, m.system_comp, mt.name AS machine_type FROM machines m JOIN machine_types mt ON m.type_id = mt.id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $machine_map[$row['machine_number']] = ['id' => $row['id'], 'system_comp' => $row['system_comp'], 'machine_type' => $row['machine_type']];
        }

        $processed_count = 0;
        $errors = [];
        
        // Prepare insert statement for meters table
        // This statement now includes all possible meter columns.
        $insert_stmt = $conn->prepare("
            INSERT INTO meters (
                machine_id, operation_date, meter_type,
                total_in, total_out, bills_in, ticket_in, ticket_out, jp, bets, handpay,
                coins_in, coins_out, coins_drop,
                created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            
            // Skip empty or incomplete rows
            if (empty($row) || count($row) < 2) { // Minimum 2 columns (machine_id, operation_date)
                $errors[] = "Row " . ($i + 1) . ": Skipping empty or incomplete row.";
                continue;
            }
            
            $machine_number_from_csv = trim($row[$header_map['machine_id']] ?? '');
            
            if (!isset($machine_map[$machine_number_from_csv])) {
                $errors[] = "Row " . ($i + 1) . ": Unknown machine number '" . $machine_number_from_csv . "'. Skipping entry.";
                continue;
            }

            $machine_info = $machine_map[$machine_number_from_csv];
            $db_machine_id = $machine_info['id'];
            $system_comp = $machine_info['system_comp'];
            $machine_type_name = $machine_info['machine_type'];

            $row_operation_date = trim($row[$header_map['operation_date']] ?? '');
            if (!DateTime::createFromFormat('Y-m-d', $row_operation_date)) {
                $errors[] = "Row " . ($i + 1) . ": Invalid operation_date format '" . $row_operation_date . "'. Expected YYYY-MM-DD. Skipping entry.";
                continue;
            }

            // Determine meter_type for database based on machine properties
            $meter_type = '';
            if ($system_comp === 'online') {
                $meter_type = 'online';
            } elseif ($system_comp === 'offline') {
                if ($machine_type_name === 'COINS') {
                    $meter_type = 'coins';
                } else { // CASH or GAMBEE (offline)
                    $meter_type = 'offline';
                }
            } else {
                $errors[] = "Row " . ($i + 1) . ": Machine '" . $machine_number_from_csv . "' has an unsupported system compatibility '" . $system_comp . "'. Skipping entry.";
                continue;
            }

            // Initialize all meter fields to null
            $total_in = $total_out = $bills_in = $ticket_in = $ticket_out = $jp = $bets = $handpay = $coins_in = $coins_out = $coins_drop = null;

            // Conditionally assign values based on meter_type and header presence
            if ($meter_type === 'online' || $meter_type === 'offline') {
                // These fields are common for online and offline (cash/gambee) machines
                $total_in = isset($header_map['total_in']) ? (int)($row[$header_map['total_in']] ?? 0) : null;
                $total_out = isset($header_map['total_out']) ? (int)($row[$header_map['total_out']] ?? 0) : null;
                $bills_in = isset($header_map['bills_in']) ? (int)($row[$header_map['bills_in']] ?? 0) : null;
                $handpay = isset($header_map['handpay']) ? (int)($row[$header_map['handpay']] ?? 0) : null;
                $jp = isset($header_map['jp']) ? (int)($row[$header_map['jp']] ?? 0) : null;
                $bets = isset($header_map['bets']) ? (int)($row[$header_map['bets']] ?? 0) : null;
                
                // Ticket fields are typically for online, but included in comprehensive headers
                $ticket_in = isset($header_map['ticket_in']) ? (int)($row[$header_map['ticket_in']] ?? 0) : null;
                $ticket_out = isset($header_map['ticket_out']) ? (int)($row[$header_map['ticket_out']] ?? 0) : null;

            } elseif ($meter_type === 'coins') {
                // These fields are specific to coins machines
                $coins_in = isset($header_map['coins_in']) ? (int)($row[$header_map['coins_in']] ?? 0) : null;
                $coins_out = isset($header_map['coins_out']) ? (int)($row[$header_map['coins_out']] ?? 0) : null;
                $coins_drop = isset($header_map['coins_drop']) ? (int)($row[$header_map['coins_drop']] ?? 0) : null;
                $bets = isset($header_map['bets']) ? (int)($row[$header_map['bets']] ?? 0) : null;
                $handpay = isset($header_map['handpay']) ? (int)($row[$header_map['handpay']] ?? 0) : null;
            }

            // Execute insert statement
            $insert_stmt->execute([
                $db_machine_id,
                $row_operation_date,
                $meter_type,
                $total_in,
                $total_out,
                $bills_in,
                $ticket_in,
                $ticket_out,
                $jp,
                $bets,
                $handpay,
                $coins_in,
                $coins_out,
                $coins_drop,
                $_SESSION['user_id']
            ]);
            
            $processed_count++;
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'stats' => [
                'total_records' => $processed_count,
                'upload_date' => $operation_date,
                'filename' => $original_filename,
                'errors' => $errors
            ]
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>

<div class="meter-upload-page fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Upload Meter Data (Online Machines)</h3>
        </div>
        <div class="card-body">
            <?php if ($upload_stats): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars("CSV file uploaded and processed successfully!"); ?>
                    
                    <div class="upload-stats mt-3">
                        <h5>Upload Statistics:</h5>
                        <ul>
                            <li><strong>Records Processed:</strong> <?php echo $upload_stats['total_records']; ?></li>
                            <li><strong>Upload Date:</strong> <?php echo format_date($upload_stats['upload_date']); ?></li>
                            <li><strong>Filename:</strong> <?php echo htmlspecialchars($upload_stats['filename']); ?></li>
                        </ul>
                        
                        <?php if (!empty($upload_stats['errors'])): ?>
                            <div class="upload-errors mt-2">
                                <h6>Errors encountered:</h6>
                                <ul>
                                    <?php foreach ($upload_stats['errors'] as $error_item): ?>
                                        <li><?php echo htmlspecialchars($error_item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- File Format Instructions -->
            <div class="alert alert-info">
                <h5>📋 File Format Requirements:</h5>
                <p>Upload a CSV file containing daily meter data for <strong>online machines only</strong>. The file must contain the following columns:</p>
                <ul>
                    <li><strong>machine_id</strong> - The machine number (e.g., "M001")</li>
                    <li><strong>operation_date</strong> - Date in YYYY-MM-DD format</li>
                    <li><strong>total_in</strong> - Total amount put into the machine</li>
                    <li><strong>total_out</strong> - Total amount paid out by the machine</li>
                    <li><strong>bills_in</strong> - Value of bills inserted</li>
                    <li><strong>ticket_in</strong> - Value of tickets inserted</li>
                    <li><strong>ticket_out</strong> - Value of tickets paid out</li>
                    <li><strong>jp</strong> - Jackpot amount</li>
                    <li><strong>bets</strong> - Total bets placed</li>
                    <li><strong>handpay</strong> - Handpay amount</li>
                </ul>
                <p>All numeric fields should be valid numbers. Empty numeric fields will be treated as 0.</p>
            </div>
            
            <form action="index.php?page=meters&action=upload" method="POST" enctype="multipart/form-data" id="meterUploadForm">
                <!-- Upload Details Section -->
                <div class="form-section">
                    <h4>Upload File</h4>
                    
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="upload_date">Data Operation Date *</label>
                                <input type="date" id="upload_date" name="upload_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                                <small class="form-text">This date will be used as the default operation_date for entries if not provided in CSV.</small>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="csv_file">CSV File *</label>
                                <input type="file" id="csv_file" name="csv_file" class="form-control" 
                                       accept=".csv" required>
                                <small class="form-text">Maximum file size: 10MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload and Process</button>
                    <a href="index.php?page=meters" class="btn btn-danger">Cancel</a>
                </div>
            </form>
            
            <!-- Sample Data Format -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>📄 Sample CSV Format</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-gray-100 p-3 rounded">machine_id,operation_date,total_in,total_out,bills_in,ticket_in,ticket_out,jp,bets,handpay
M001,2023-01-01,1500,1200,1000,50,20,0,10000,0
M002,2023-01-01,2200,1800,1500,75,30,500,15000,100
M003,2023-01-01,800,700,600,25,10,0,5000,0</pre>
                </div>
            </div>
        </div>
    </div>
</div>


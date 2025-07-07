<?php
/**
 * Guest Tracking Excel Upload Page
 */

$message = '';
$error = '';
$upload_stats = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
	$upload_date = get_input(INPUT_POST, 'upload_date', 'string', date('Y-m-d'));
    
    // Validate file upload
    if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('danger', "File upload failed. Please try again.");
    } elseif ($_FILES['excel_file']['size'] > 10 * 1024 * 1024) { // 10MB limit
        set_flash_message('danger', "File size too large. Maximum size is 10MB.");
    } else {
        $file_info = pathinfo($_FILES['excel_file']['name']);
        $allowed_extensions = ['xlsx', 'xls', 'csv'];
        
        if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
            set_flash_message('danger', "Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file.");
        } else {
            try {
                // Check if upload for this date already exists
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM guest_uploads WHERE upload_date = ?");
                $check_stmt->execute([$upload_date]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    set_flash_message('danger', "Data for this date already exists. Please delete the existing upload first or choose a different date.");
                } else {
                    // Process the uploaded file
                    $upload_result = processGuestExcelFile($_FILES['excel_file'], $upload_date, $conn);
                    
                    if ($upload_result['success']) {
                        $upload_stats = $upload_result['stats'];
                        set_flash_message('success', "Excel file uploaded and processed successfully!");
                        
                        // Log action
                        log_action('upload_guest_data', "Uploaded guest data for date: $upload_date, Records: {$upload_stats['total_records']}");
                    } else {
                        set_flash_message('danger', $upload_result['error']);
                    }
                }
            } catch (Exception $e) {
                set_flash_message('danger', "Error processing file: " . $e->getMessage());
            }
        }
    }
    header("Location: index.php?page=guest_tracking&action=upload");
    exit;
}

/**
 * Process uploaded Excel/CSV file
 */
function processGuestExcelFile($file, $upload_date, $conn) {
    $temp_file = $file['tmp_name'];
    $original_filename = $file['name'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Read file content based on extension
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        
        if ($file_extension === 'csv') {
            $data = readCSVFile($temp_file);
        } else {
            // For Excel files, we'll use a simple CSV conversion approach
            // In a real implementation, you might want to use PhpSpreadsheet
            $error = "Excel files (.xlsx, .xls) are not fully supported yet. Please convert to CSV format.";
            return ['success' => false, 'error' => $error];
        }
        
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data found in the uploaded file.'];
        }
        
        // Validate headers
        $expected_headers = ['guest_code_id', 'guest_name', 'drop', 'result', 'visits'];
        $headers = array_map('strtolower', array_map('trim', $data[0]));
        
        $missing_headers = array_diff($expected_headers, $headers);
        if (!empty($missing_headers)) {
            return ['success' => false, 'error' => 'Missing required columns: ' . implode(', ', $missing_headers)];
        }
        
        // Create header mapping
        $header_map = array_flip($headers);
        
        // Process data rows
        $processed_count = 0;
        $updated_count = 0;
        $new_count = 0;
        $errors = [];
        
        // Record upload
        $upload_stmt = $conn->prepare("
            INSERT INTO guest_uploads (upload_date, upload_filename, uploaded_by, uploaded_at) 
            VALUES (?, ?, ?, ?)
        ");
        $upload_stmt->execute([$upload_date, $original_filename, $_SESSION['user_id'], date('Y-m-d H:i:s')]);
        
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            
            if (empty($row) || count($row) < count($expected_headers)) {
                continue; // Skip empty or incomplete rows
            }
            
            $guest_code_id = trim($row[$header_map['guest_code_id']] ?? '');
            $guest_name = trim($row[$header_map['guest_name']] ?? '');
            $drop = floatval($row[$header_map['drop']] ?? 0);
            $result = floatval($row[$header_map['result']] ?? 0);
            $visits = intval($row[$header_map['visits']] ?? 0);
            
            if (empty($guest_code_id)) {
                $errors[] = "Row " . ($i + 1) . ": Guest Code ID is required";
                continue;
            }
            
            // Insert or update guest
            $guest_stmt = $conn->prepare("
                INSERT INTO guests (guest_code_id, guest_name, created_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE guest_name = VALUES(guest_name), updated_at = CURRENT_TIMESTAMP
            ");
            $guest_stmt->execute([$guest_code_id, $guest_name, date('Y-m-d H:i:s')]);
            
            // Insert guest data
            $data_stmt = $conn->prepare("
                INSERT INTO guest_data (guest_code_id, upload_date, drop_amount, result_amount, visits, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $data_stmt->execute([$guest_code_id, $upload_date, $drop, $result, $visits, date('Y-m-d H:i:s')]);
            
            $processed_count++;
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'stats' => [
                'total_records' => $processed_count,
                'upload_date' => $upload_date,
                'filename' => $original_filename,
                'errors' => $errors
            ]
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Read CSV file
 */
function readCSVFile($filename) {
    $data = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}
?>

<div class="guest-upload-page fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Upload Guest Tracking Data</h3>
        </div>
        <div class="card-body">
            <?php if ($upload_stats): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars("Excel file uploaded and processed successfully!"); ?>
                    
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
                <p>Your Excel/CSV file must contain the following columns (in any order):</p>
                <ul>
                    <li><strong>guest_code_id</strong> - Unique identifier for the guest</li>
                    <li><strong>guest_name</strong> - Guest's full name</li>
                    <li><strong>drop</strong> - Drop amount (numeric)</li>
                    <li><strong>result</strong> - Result amount (numeric, can be negative)</li>
                    <li><strong>visits</strong> - Number of visits (numeric)</li>
                </ul>
                <p><strong>Note:</strong> Currently only CSV files are supported. Please convert Excel files to CSV format before uploading.</p>
            </div>
            
            <form action="index.php?page=guest_tracking&action=upload" method="POST" enctype="multipart/form-data" id="guestTrackingUploadForm">
                <!-- Upload Details Section -->
                <div class="form-section">
                    <h4>Upload Details</h4>
                    
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="upload_date">Upload Date *</label>
                                <input type="date" id="upload_date" name="upload_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                                <small class="form-text">This represents the period/month this data is for</small>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="form-group">
                                <label for="excel_file">Excel/CSV File *</label>
                                <input type="file" id="excel_file" name="excel_file" class="form-control" 
                                       accept=".xlsx,.xls,.csv" required>
                                <small class="form-text">Maximum file size: 10MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload and Process</button>
                    <a href="index.php?page=guest_tracking" class="btn btn-danger">Cancel</a>
                </div>
            </form>
            
            <!-- Sample Data Format -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>📄 Sample CSV Format</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-gray-100 p-3 rounded">guest_code_id,guest_name,drop,result,visits
G001,John Smith,1500.75,250.50,3
G002,Jane Doe,2200.00,-150.25,2
G003,Bob Johnson,800.50,75.00,1</pre>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="module" src="assets/js/guest_tracking_upload.js"></script>

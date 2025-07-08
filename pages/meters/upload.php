<?php
/**
 * Upload Meter Data for Online Machines
 */

// Start output buffering and set JSON header immediately for POST requests
// This block executes first, before any HTML output can occur for POST requests.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start(); // Start output buffering
    header('Content-Type: application/json'); // Set JSON header
    // Include necessary files directly for the AJAX endpoint
    require_once '../../config/config.php';
    require_once '../../includes/functions.php';
}

/**
 * Process received meter data (from XLSX/CSV)
 * This function now expects an array of associative arrays (JSON objects)
 */
function processMeterData($meter_data, $operation_date, $original_filename, $conn) {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Fetch machine mappings (machine_number => id, system_comp, machine_type) for ALL machines
        $machine_map = [];
        $stmt = $conn->query("SELECT m.machine_number, m.id, m.system_comp, mt.name AS machine_type FROM machines m JOIN machine_types mt ON m.type_id = mt.id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $machine_map[$row['machine_number']] = ['id' => $row['id'], 'system_comp' => $row['system_comp'], 'machine_type' => $row['machine_type']];
        }

        $processed_count = 0;
        $errors = [];

        // Prepare insert statement for meters table
        $insert_stmt = $conn->prepare("
            INSERT INTO meters (
                machine_id, operation_date, meter_type,
                total_in, total_out, bills_in, ticket_in, ticket_out, jp, bets, handpay,
                coins_in, coins_out, coins_drop,
                created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($meter_data as $i => $row_data) {
            // Convert all keys to lowercase for consistent access
            $row_data_lower = array_change_key_case($row_data, CASE_LOWER);

            $machine_number_from_file = trim($row_data_lower['machine'] ?? $row_data_lower['machine_id'] ?? ''); // Assuming 'machine' or 'machine_id' column in XLSX
            $row_operation_date_str = trim($row_data_lower['operation date'] ?? $row_data_lower['operation_date'] ?? $operation_date); // Assuming 'operation date' or 'operation_date' or use default

            if (empty($machine_number_from_file)) {
                $errors[] = "Row " . ($i + 2) . ": Missing machine number. Skipping entry."; // +2 for 1-based index and header row
                continue;
            }

            if (!isset($machine_map[$machine_number_from_file])) {
                $errors[] = "Row " . ($i + 2) . ": Unknown machine number '" . $machine_number_from_file . "'. Skipping entry.";
                continue;
            }

            $machine_info = $machine_map[$machine_number_from_file];
            $db_machine_id = $machine_info['id'];
            $system_comp = $machine_info['system_comp'];
            $machine_type_name = $machine_info['machine_type'];

            // Validate operation_date format
            if (!DateTime::createFromFormat('Y-m-d', $row_operation_date_str)) {
                $errors[] = "Row " . ($i + 2) . ": Invalid operation_date format '" . $row_operation_date_str . "'. Expected YYYY-MM-DD. Skipping entry.";
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
                $errors[] = "Row " . ($i + 2) . ": Machine '" . $machine_number_from_file . "' has an unsupported system compatibility '" . $system_comp . "'. Skipping entry.";
                continue;
            }

            // Initialize all meter fields to null
            $total_in = $total_out = $bills_in = $ticket_in = $ticket_out = $jp = $bets = $handpay = $coins_in = $coins_out = $coins_drop = null;

            // Helper function to get numeric value, handling commas and empty strings
            $get_numeric_value = function($key) use ($row_data_lower) {
                $value = $row_data_lower[$key] ?? null;
                if (is_string($value)) {
                    $value = str_replace(',', '', $value); // Remove commas
                }
                return is_numeric($value) ? (float)$value : null;
            };

            // Populate fields based on determined meter_type and available columns
            if ($meter_type === 'online' || $meter_type === 'offline') {
                $total_in = $get_numeric_value('total in');
                $total_out = $get_numeric_value('total out');
                $bills_in = $get_numeric_value('bills in');
                $handpay = $get_numeric_value('hand pay'); // Assuming 'hand pay' column
                $jp = $get_numeric_value('jp');
                $bets = $get_numeric_value('bets');
                $ticket_in = $get_numeric_value('ticket in');
                $ticket_out = $get_numeric_value('ticket out');
            }

            if ($meter_type === 'coins') {
                $coins_in = $get_numeric_value('coins in');
                $coins_out = $get_numeric_value('coins out');
                $coins_drop = $get_numeric_value('coins drop');
                $bets = $get_numeric_value('bets');
                $handpay = $get_numeric_value('hand pay');
            }

            // Execute insert statement
            $insert_stmt->execute([
                $db_machine_id,
                $row_operation_date_str,
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
                $_SESSION['user_id'] ?? null // Use null if user_id is not set
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
        return ['success' => false, 'error' => $e->getMessage(), 'errors' => [$e->getMessage()]];
    }
}

// Handle POST request for file upload processing (AJAX endpoint logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'An unknown error occurred.', 'errors' => []];

    try {
        // Read the raw POST data
        $json_data = file_get_contents('php://input');
        $request_data = json_decode($json_data, true); // Decode as associative array

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
        }

        $upload_date = $request_data['upload_date'] ?? date('Y-m-d');
        $filename = $request_data['filename'] ?? 'uploaded_meter_data.xlsx';
        $meter_data = $request_data['meter_data'] ?? [];

        if (empty($meter_data)) {
            throw new Exception('No meter data found in the uploaded file.');
        }

        // Process the received data
        $upload_result = processMeterData($meter_data, $upload_date, $filename, $conn);

        if ($upload_result['success']) {
            $upload_stats = $upload_result['stats'];
            $response['success'] = true;
            $response['message'] = "Excel file uploaded and processed successfully! Imported {$upload_stats['total_records']} entries.";
            $response['stats'] = $upload_stats;
            if (!empty($upload_stats['errors'])) {
                $response['message'] .= " Some entries were skipped.";
                $response['errors'] = $upload_stats['errors'];
            }
            // Log action
            log_action('upload_meter_data', "Uploaded meter data for date: $upload_date, Records: {$upload_stats['total_records']}");
        } else {
            $response['message'] = $upload_result['error'];
            $response['errors'] = $upload_result['errors'] ?? [];
        }

    } catch (Exception $e) {
        $response['message'] = "Error processing file: " . $e->getMessage();
        $response['errors'][] = $e->getMessage();
    } finally {
        // Clear any buffered output and send the JSON response
        ob_end_clean(); // Ensure all output is cleared
        echo json_encode($response);
        exit;
    }
}

// If it's not a POST request, continue to render the HTML form below
// Initial variable declarations for GET request
$message = '';
$error = '';
$upload_stats = null; // This will be populated if there was a previous successful upload and the page reloads.

// Check for flash messages from previous redirects
if (isset($_SESSION['flash_messages'])) {
    foreach ($_SESSION['flash_messages'] as $msg) {
        if ($msg['type'] === 'success') {
            $message = $msg['message'];
        } elseif ($msg['type'] === 'danger') {
            $error = $msg['message'];
        }
    }
    unset($_SESSION['flash_messages']); // Clear messages after displaying
}
?>

<div class="meter-upload-page fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Upload Meter Data (Online Machines)</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- File Format Instructions -->
            <div class="alert alert-info">
                <h5>📋 File Format Requirements:</h5>
                <p>Upload an Excel file (.xlsx, .xls) containing daily meter data for <strong>online machines only</strong>. The file must contain the following columns:</p>
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
                                <label for="csv_file">Excel File *</label>
                                <input type="file" id="csv_file" name="csv_file" class="form-control" 
                                       accept=".xlsx,.xls" required>
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
                    <h5>📄 Sample Excel Format</h5>
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

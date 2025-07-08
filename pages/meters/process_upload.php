<?php
/**
 * AJAX endpoint for processing uploaded meter data.
 * This file is solely responsible for handling the POST request from the frontend.
 */

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Set JSON header and start output buffering immediately
ob_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'errors' => []];

try {
    // Check if the request is an AJAX POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        throw new Exception('Invalid request method or not an AJAX request.');
    }

    // Read the raw POST data (JSON body)
    $json_data = file_get_contents('php://input');
    $request_data = json_decode($json_data, true); // Decode as associative array

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }

    $upload_date = $request_data['upload_date'] ?? date('Y-m-d');
    $filename = $request_data['filename'] ?? 'uploaded_meter_data.xlsx';
    $meter_data_array = $request_data['meter_data'] ?? []; // This is the array of objects from XLSX.utils.sheet_to_json

    if (empty($meter_data_array)) {
        throw new Exception('No meter data found in the uploaded file.');
    }

    // --- Start of processMeterData function (adapted from processMeterCSVFile) ---
    // This function will process the array of meter data objects
    function processMeterData($meter_data_array, $operation_date, $original_filename, $conn) {
        try {
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

            foreach ($meter_data_array as $row_data) {
                // Ensure keys are lowercase for consistent access
                $row_data_lc = array_change_key_case($row_data, CASE_LOWER);

                $machine_number_from_excel = trim($row_data_lc['machine_id'] ?? '');

                if (!isset($machine_map[$machine_number_from_excel])) {
                    $errors[] = "Machine ID '" . $machine_number_from_excel . "' not found. Skipping entry.";
                    continue;
                }

                $machine_info = $machine_map[$machine_number_from_excel];
                $db_machine_id = $machine_info['id'];
                $system_comp = $machine_info['system_comp'];
                $machine_type_name = $machine_info['machine_type'];

                $row_operation_date = trim($row_data_lc['operation_date'] ?? $operation_date); // Use provided upload_date as fallback
                // Convert Excel date number to YYYY-MM-DD if it's a number
                if (is_numeric($row_operation_date) && $row_operation_date > 0) {
                    // Excel date (number of days since 1900-01-01, with 1900-02-29 bug)
                    // PHP DateTime::createFromFormat('U', ...) expects Unix timestamp
                    // Excel's epoch is 1900-01-01, Unix epoch is 1970-01-01
                    // Difference is 25569 days (for 1900-01-01 to 1970-01-01, plus 1 for Excel's 1-based day count)
                    // Adjust for Excel's leap year bug (Excel thinks 1900 was a leap year)
                    $unix_date = ($row_operation_date - 25569) * 86400; // Convert days to seconds
                    $row_operation_date = date('Y-m-d', $unix_date);
                } else if (!DateTime::createFromFormat('Y-m-d', $row_operation_date)) {
                    $errors[] = "Invalid operation_date format '" . $row_operation_date . "' for machine '" . $machine_number_from_excel . "'. Expected YYYY-MM-DD or Excel date number. Skipping entry.";
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
                    $errors[] = "Machine '" . $machine_number_from_excel . "' has an unsupported system compatibility '" . $system_comp . "'. Skipping entry.";
                    continue;
                }

                // Initialize all meter fields to null
                $total_in = $total_out = $bills_in = $ticket_in = $ticket_out = $jp = $bets = $handpay = $coins_in = $coins_out = $coins_drop = null;

                // Conditionally assign values based on meter_type and header presence
                // Use null coalescing operator (??) to handle missing keys gracefully
                // Use (int) or (float) to ensure numeric type, or null if empty/not set
                if ($meter_type === 'online' || $meter_type === 'offline') {
                    $total_in = isset($row_data_lc['total_in']) && is_numeric($row_data_lc['total_in']) ? (int)$row_data_lc['total_in'] : null;
                    $total_out = isset($row_data_lc['total_out']) && is_numeric($row_data_lc['total_out']) ? (int)$row_data_lc['total_out'] : null;
                    $bills_in = isset($row_data_lc['bills_in']) && is_numeric($row_data_lc['bills_in']) ? (int)$row_data_lc['bills_in'] : null;
                    $handpay = isset($row_data_lc['handpay']) && is_numeric($row_data_lc['handpay']) ? (int)$row_data_lc['handpay'] : null;
                    $jp = isset($row_data_lc['jp']) && is_numeric($row_data_lc['jp']) ? (int)$row_data_lc['jp'] : null;
                    $bets = isset($row_data_lc['bets']) && is_numeric($row_data_lc['bets']) ? (int)$row_data_lc['bets'] : null;
                    $ticket_in = isset($row_data_lc['ticket_in']) && is_numeric($row_data_lc['ticket_in']) ? (int)$row_data_lc['ticket_in'] : null;
                    $ticket_out = isset($row_data_lc['ticket_out']) && is_numeric($row_data_lc['ticket_out']) ? (int)$row_data_lc['ticket_out'] : null;

                } elseif ($meter_type === 'coins') {
                    $coins_in = isset($row_data_lc['coins_in']) && is_numeric($row_data_lc['coins_in']) ? (int)$row_data_lc['coins_in'] : null;
                    $coins_out = isset($row_data_lc['coins_out']) && is_numeric($row_data_lc['coins_out']) ? (int)$row_data_lc['coins_out'] : null;
                    $coins_drop = isset($row_data_lc['coins_drop']) && is_numeric($row_data_lc['coins_drop']) ? (int)$row_data_lc['coins_drop'] : null;
                    $bets = isset($row_data_lc['bets']) && is_numeric($row_data_lc['bets']) ? (int)$row_data_lc['bets'] : null;
                    $handpay = isset($row_data_lc['handpay']) && is_numeric($row_data_lc['handpay']) ? (int)$row_data_lc['handpay'] : null;
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

        } catch (PDOException $e) {
            $conn->rollback();
            // Check for duplicate entry error (SQLSTATE 23000 is for integrity constraint violation)
            if ($e->getCode() === '23000') {
                return ['success' => false, 'error' => "Duplicate entry detected. A meter entry for a machine on a specific date already exists. Please ensure your file contains unique machine-date combinations or edit existing entries.", 'errors' => [$e->getMessage()]];
            }
            return ['success' => false, 'error' => "Database error during processing: " . $e->getMessage(), 'errors' => [$e->getMessage()]];
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'error' => "Processing error: " . $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }
    // --- End of processMeterData function ---

    // Call the processing function
    $upload_result = processMeterData($meter_data_array, $upload_date, $filename, $conn);

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
?>

<?php
/**
 * Upload Meter Data for Online Machines
 */

// Include necessary files for rendering the form.
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

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

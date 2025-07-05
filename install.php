<?php
/**
 * Installation script for Slot Management System
 * Run this script once to set up the database and create admin user
 */

// Include configuration
require_once 'config/config.php';

// Function to create database tables
function createTables($conn) {
    try {
        // Create users table
        $conn->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
            `password` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL,
            `name` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
            `email` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
            `role` ENUM('admin', 'editor', 'viewer') COLLATE utf8mb4_general_ci NOT NULL,
            `status` ENUM('Active','Inactive','Maintenance','Reserved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create brands table
        $conn->exec("CREATE TABLE IF NOT EXISTS `brands` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
            `description` TEXT COLLATE utf8mb4_general_ci,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create machine_types table
        $conn->exec("CREATE TABLE IF NOT EXISTS `machine_types` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create machines table
        $conn->exec("CREATE TABLE IF NOT EXISTS `machines` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `machine_number` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
            `brand_id` INT DEFAULT NULL,
            `model` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
            `game` VARCHAR(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `type_id` INT DEFAULT NULL,
            `credit_value` DECIMAL(10,2) NOT NULL,
            `manufacturing_year` INT DEFAULT NULL,
            `ip_address` VARCHAR(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `mac_address` VARCHAR(17) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `serial_number` VARCHAR(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `status` ENUM('Active','Inactive','Maintenance','Reserved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
            `ticket_printer` ENUM('yes','N/A') COLLATE utf8mb4_general_ci DEFAULT NULL,
            `system_comp` ENUM('offline','online') COLLATE utf8mb4_general_ci DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `machine_number` (`machine_number`),
            UNIQUE KEY `serial_number` (`serial_number`),
            KEY `brand_id` (`brand_id`),
            KEY `type_id` (`type_id`),
            KEY `idx_machines_game` (`game`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create machine_groups table
        $conn->exec("CREATE TABLE IF NOT EXISTS `machine_groups` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
            `description` TEXT COLLATE utf8mb4_general_ci,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`),
            KEY `idx_machine_groups_name` (`name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create machine_group_members table
        $conn->exec("CREATE TABLE IF NOT EXISTS `machine_group_members` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `group_id` INT NOT NULL,
            `machine_id` INT NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_group_machine` (`group_id`,`machine_id`),
            KEY `idx_machine_group_members_group_id` (`group_id`),
            KEY `idx_machine_group_members_machine_id` (`machine_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create transaction_types table
        $conn->exec("CREATE TABLE IF NOT EXISTS `transaction_types` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
            `category` ENUM('OUT', 'DROP') COLLATE utf8mb4_general_ci NOT NULL,
            `description` TEXT COLLATE utf8mb4_general_ci,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create transactions table
        $conn->exec("CREATE TABLE IF NOT EXISTS `transactions` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `machine_id` INT NOT NULL,
            `transaction_type_id` INT NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `operation_date` DATE NOT NULL DEFAULT (CURDATE()),
            `user_id` INT NOT NULL,
            `edited_by` INT DEFAULT NULL,
            `notes` TEXT COLLATE utf8mb4_general_ci,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `machine_id` (`machine_id`),
            KEY `transaction_type_id` (`transaction_type_id`),
            KEY `user_id` (`user_id`),
            KEY `edited_by` (`edited_by`),
            KEY `idx_operation_date` (`operation_date`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Create daily_tracking table
        $conn->exec("CREATE TABLE IF NOT EXISTS `daily_tracking` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `tracking_date` DATE NOT NULL,
            `slots_drop` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `slots_out` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `slots_result` DECIMAL(10,2) GENERATED ALWAYS AS ((`slots_drop` - `slots_out`)) STORED,
            `gambee_drop` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `gambee_out` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `gambee_result` DECIMAL(10,2) GENERATED ALWAYS AS ((`gambee_drop` - `gambee_out`)) STORED,
            `coins_drop` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `coins_out` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `coins_result` DECIMAL(10,2) GENERATED ALWAYS AS ((`coins_drop` - `coins_out`)) STORED,
            `created_by` INT NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_by` INT DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `notes` TEXT COLLATE utf8mb4_general_ci,
            PRIMARY KEY (`id`),
            UNIQUE KEY `tracking_date` (`tracking_date`),
            KEY `updated_by` (`updated_by`),
            KEY `idx_tracking_date` (`tracking_date`),
            KEY `idx_created_by` (`created_by`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create logs table
        $conn->exec("CREATE TABLE IF NOT EXISTS `logs` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `user_id` INT DEFAULT NULL,
            `action` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL,
            `details` TEXT COLLATE utf8mb4_general_ci,
            `ip_address` VARCHAR(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create login_attempts table for security
        $conn->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
            `ip_address` VARCHAR(45) COLLATE utf8mb4_general_ci NOT NULL,
            `attempt_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_username_time` (`username`,`attempt_time`),
            KEY `idx_ip_time` (`ip_address`,`attempt_time`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Create security_logs table
        $conn->exec("CREATE TABLE IF NOT EXISTS `security_logs` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `event_type` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
            `details` TEXT COLLATE utf8mb4_general_ci,
            `severity` ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'INFO',
            `ip_address` VARCHAR(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `user_agent` TEXT COLLATE utf8mb4_general_ci,
            `user_id` INT DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `idx_event_type` (`event_type`),
            KEY `idx_severity` (`severity`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Create guest_uploads table
        $conn->exec("CREATE TABLE IF NOT EXISTS `guest_uploads` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `upload_date` DATE NOT NULL,
            `upload_filename` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL,
            `uploaded_by` INT NOT NULL,
            `uploaded_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `upload_date` (`upload_date`),
            KEY `uploaded_by` (`uploaded_by`),
            KEY `idx_upload_date` (`upload_date`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Create guests table
        $conn->exec("CREATE TABLE IF NOT EXISTS `guests` (
            `guest_code_id` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
            `guest_name` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`guest_code_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Create guest_data table
        $conn->exec("CREATE TABLE IF NOT EXISTS `guest_data` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `guest_code_id` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
            `upload_date` DATE NOT NULL,
            `drop_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `result_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `visits` INT NOT NULL DEFAULT '0',
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_guest_upload` (`guest_code_id`,`upload_date`),
            KEY `idx_upload_date` (`upload_date`),
            KEY `idx_guest_code` (`guest_code_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Create operation_day table
        $conn->exec("CREATE TABLE IF NOT EXISTS `operation_day` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `operation_date` DATE NOT NULL,
            `set_by_user_id` INT NOT NULL,
            `set_by_username` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
            `notes` TEXT COLLATE utf8mb4_general_ci,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `set_by_user_id` (`set_by_user_id`),
            KEY `idx_operation_date` (`operation_date`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Create transaction_uploads table
        $conn->exec("CREATE TABLE IF NOT EXISTS `transaction_uploads` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `upload_date` DATE NOT NULL,
            `upload_filename` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL,
            `uploaded_by` INT DEFAULT NULL,
            `uploaded_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_upload_date_filename` (`upload_date`,`upload_filename`(191)),
            KEY `uploaded_by` (`uploaded_by`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        return true;
    } catch (PDOException $e) {
        return "Error creating tables: " . $e->getMessage();
    }
}

// Function to insert default data
function insertDefaultData($conn) {
    try {
        // Insert default machine types
        $machine_types = [
            ['name' => 'CASH', 'description' => 'Cash-based slot machines'],
            ['name' => 'COINS', 'description' => 'Coin-based slot machines'],
            ['name' => 'GAMBEE', 'description' => 'Gambee slot machines']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO machine_types (name, description) VALUES (?, ?)");
        foreach ($machine_types as $type) {
            $stmt->execute([$type['name'], $type['description']]);
        }
        
        // Insert default transaction types
        $transaction_types = [
            ['name' => 'Handpay', 'category' => 'OUT', 'description' => 'Manual payment to player'],
            ['name' => 'Ticket', 'category' => 'OUT', 'description' => 'Ticket out payment'],
            ['name' => 'Refill', 'category' => 'OUT', 'description' => 'Machine refill'],
            ['name' => 'Coins Drop', 'category' => 'DROP', 'description' => 'Coins inserted by players'],
            ['name' => 'Cash Drop', 'category' => 'DROP', 'description' => 'Cash inserted by players']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO transaction_types (name, category, description) VALUES (?, ?, ?)");
        foreach ($transaction_types as $type) {
            $stmt->execute([$type['name'], $type['category'], $type['description']]);
        }
        
        // Insert some sample brands
        $brands = [
            ['name' => 'IGT', 'description' => 'International Game Technology'],
            ['name' => 'Aristocrat', 'description' => 'Aristocrat Leisure Limited'],
            ['name' => 'Scientific Games', 'description' => 'Scientific Games Corporation'],
            ['name' => 'Konami', 'description' => 'Konami Gaming, Inc.'],
            ['name' => 'Novomatic', 'description' => 'Novomatic AG']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO brands (name, description) VALUES (?, ?)");
        foreach ($brands as $brand) {
            $stmt->execute([$brand['name'], $brand['description']]);
        }
        
        return true;
    } catch (PDOException $e) {
        return "Error inserting default data: " . $e->getMessage();
    }
}

// Function to create admin user
function createAdminUser($conn, $username, $password, $name, $email) {
    try {
        // Check if user already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->rowCount() > 0) {
            return "Admin user already exists";
        }
        
        // Validate password strength
        $password_errors = validatePasswordStrength($password);
        if (!empty($password_errors)) {
            return "Password does not meet security requirements: " . implode(", ", $password_errors);
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert admin user with 'Active' status, which is part of the new ENUM
        $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, 'admin', 'Active')");
        $stmt->execute([$username, $hashed_password, $name, $email]);
        
        return true;
    } catch (PDOException $e) {
        return "Error creating admin user: " . $e->getMessage();
    }
}

// Process installation
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create tables
    $result = createTables($conn);
    
    if ($result === true) {
        // Insert default data
        $result = insertDefaultData($conn);
        
        if ($result === true) {
            // Create admin user
            $admin_username = validateInput($_POST['admin_username'] ?? '', 'alphanumeric');
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_name = validateInput($_POST['admin_name'] ?? '', 'string');
            $admin_email = validateInput($_POST['admin_email'] ?? '', 'email');
            
            if (empty($admin_username) || empty($admin_password) || empty($admin_name) || !$admin_email) {
                $error = "All fields are required and must be valid";
            } else {
                $result = createAdminUser($conn, $admin_username, $admin_password, $admin_name, $admin_email);
                
                if ($result === true) {
                    $success = "Installation completed successfully! <a href='login.php'>Go to login page</a>";
                    
                    // Log installation
                    logSecurityEvent('SYSTEM_INSTALLED', "Admin user created: $admin_username", 'INFO');
                } else {
                    $error = $result;
                }
            }
        } else {
            $error = $result;
        }
    } else {
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Slot Management System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Install Slot Management System</h2>
                <p style="font-size: 0.9rem; opacity: 0.8;">🔒 Secure Installation</p>
            </div>
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                    <form method="POST" action="install.php" id="installForm">
                        <div class="form-group">
                            <label for="admin_username">Admin Username *</label>
                            <input type="text" id="admin_username" name="admin_username" class="form-control" 
                                   pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed" 
                                   maxlength="50" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Admin Password *</label>
                            <input type="password" id="admin_password" name="admin_password" class="form-control" 
                                   minlength="8" maxlength="255" required>
                            <div class="password-requirements">
                                <strong>Password Requirements:</strong>
                                <ul>
                                    <li>At least 8 characters long</li>
                                    <li>At least one uppercase letter (A-Z)</li>
                                    <li>At least one lowercase letter (a-z)</li>
                                    <li>At least one number (0-9)</li>
                                    <li>At least one special character (!@#$%^&*)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_name">Admin Full Name *</label>
                            <input type="text" id="admin_name" name="admin_name" class="form-control" 
                                   maxlength="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Admin Email *</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-control" 
                                   maxlength="100" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                🚀 Install System
                            </button>
                        </div>
                    </form>
                    
                    <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-muted); text-align: center;">
                        <p>⚠️ Run this installer only once</p>
                        <p>🛡️ All data will be secured with enterprise-grade encryption</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/install_form.js"></script>
	
</body>
</html>

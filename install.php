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
        $conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            role ENUM('admin', 'editor', 'viewer') NOT NULL,
            status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create brands table
        $conn->exec("CREATE TABLE IF NOT EXISTS brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create machine_types table
        $conn->exec("CREATE TABLE IF NOT EXISTS machine_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create machines table
        $conn->exec("CREATE TABLE IF NOT EXISTS machines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            machine_number VARCHAR(50) NOT NULL UNIQUE,
            brand_id INT,
            model VARCHAR(100) NOT NULL,
            type_id INT,
            credit_value DECIMAL(10,2) NOT NULL,
            manufacturing_year INT,
            ip_address VARCHAR(15),
            mac_address VARCHAR(17),
            serial_number VARCHAR(100) UNIQUE,
            status ENUM('Active', 'Inactive', 'Maintenance', 'Reserved') NOT NULL DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
            FOREIGN KEY (type_id) REFERENCES machine_types(id) ON DELETE SET NULL
        )");
        
        // Create machine_groups table
        $conn->exec("CREATE TABLE IF NOT EXISTS machine_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create machine_group_members table
        $conn->exec("CREATE TABLE IF NOT EXISTS machine_group_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            machine_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES machine_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
            UNIQUE KEY unique_group_machine (group_id, machine_id)
        )");
        
        // Create transaction_types table
        $conn->exec("CREATE TABLE IF NOT EXISTS transaction_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            category ENUM('OUT', 'DROP') NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create transactions table
        $conn->exec("CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            machine_id INT NOT NULL,
            transaction_type_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id INT NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
            FOREIGN KEY (transaction_type_id) REFERENCES transaction_types(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_timestamp (timestamp),
            INDEX idx_machine_timestamp (machine_id, timestamp)
        )");

        // Create daily_tracking table
        $conn->exec("CREATE TABLE IF NOT EXISTS daily_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tracking_date DATE NOT NULL UNIQUE,
            slots_drop DECIMAL(10,2) DEFAULT 0.00,
            slots_out DECIMAL(10,2) DEFAULT 0.00,
            slots_result DECIMAL(10,2) DEFAULT 0.00,
            slots_percentage DECIMAL(5,2) DEFAULT 0.00,
            gambee_drop DECIMAL(10,2) DEFAULT 0.00,
            gambee_out DECIMAL(10,2) DEFAULT 0.00,
            gambee_result DECIMAL(10,2) DEFAULT 0.00,
            gambee_percentage DECIMAL(5,2) DEFAULT 0.00,
            coins_drop DECIMAL(10,2) DEFAULT 0.00,
            coins_out DECIMAL(10,2) DEFAULT 0.00,
            coins_result DECIMAL(10,2) DEFAULT 0.00,
            coins_percentage DECIMAL(5,2) DEFAULT 0.00,
            total_drop DECIMAL(10,2) DEFAULT 0.00,
            total_out DECIMAL(10,2) DEFAULT 0.00,
            total_result DECIMAL(10,2) DEFAULT 0.00,
            total_result_percentage DECIMAL(5,2) DEFAULT 0.00,
            notes TEXT,
            created_by INT,
            updated_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        )");
        
        // Create logs table
        $conn->exec("CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_created_at (created_at),
            INDEX idx_user_id (user_id)
        )");
        
        // Create login_attempts table for security
        $conn->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username_time (username, attempt_time),
            INDEX idx_ip_time (ip_address, attempt_time)
        )");
        
        // Create security_logs table
        $conn->exec("CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(100) NOT NULL,
            details TEXT,
            severity ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL DEFAULT 'INFO',
            ip_address VARCHAR(45),
            user_agent TEXT,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_event_type (event_type),
            INDEX idx_severity (severity),
            INDEX idx_created_at (created_at)
        )");

        // Create guest_uploads table
        $conn->exec("CREATE TABLE IF NOT EXISTS guest_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            upload_date DATE NOT NULL UNIQUE,
            upload_filename VARCHAR(255) NOT NULL,
            uploaded_by INT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        )");

        // Create guests table
        $conn->exec("CREATE TABLE IF NOT EXISTS guests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guest_code_id VARCHAR(50) NOT NULL UNIQUE,
            guest_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Create guest_data table
        $conn->exec("CREATE TABLE IF NOT EXISTS guest_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guest_code_id VARCHAR(50) NOT NULL,
            upload_date DATE NOT NULL,
            drop_amount DECIMAL(10,2) NOT NULL,
            result_amount DECIMAL(10,2) NOT NULL,
            visits INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (guest_code_id) REFERENCES guests(guest_code_id) ON DELETE CASCADE,
            UNIQUE KEY unique_guest_data (guest_code_id, upload_date)
        )");

        // Create operation_day table
        $conn->exec("CREATE TABLE IF NOT EXISTS operation_day (
            id INT AUTO_INCREMENT PRIMARY KEY,
            operation_date DATE NOT NULL UNIQUE,
            set_by_user_id INT,
            set_by_username VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (set_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
        
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
        
        // Insert admin user
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('installForm');
            const installBtn = document.getElementById('installBtn');
            
            if (form) {
                form.addEventListener('submit', function() {
                    installBtn.disabled = true;
                    installBtn.textContent = '⏳ Installing...';
                });
            }
        });
    </script>
</body>
</html>

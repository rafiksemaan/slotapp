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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create brands table
        $conn->exec("CREATE TABLE IF NOT EXISTS brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
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
            type ENUM('CASH', 'COINS', 'GAMBEE') NOT NULL,
            credit_value DECIMAL(10,2) NOT NULL,
            manufacturing_year INT,
            ip_address VARCHAR(15),
            mac_address VARCHAR(17),
            serial_number VARCHAR(100) UNIQUE,
            status ENUM('Active', 'Inactive', 'Maintenance', 'Reserved') NOT NULL DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Create logs table
        $conn->exec("CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
        
        return true;
    } catch (PDOException $e) {
        return "Error creating tables: " . $e->getMessage();
    }
}

// Function to insert default data
function insertDefaultData($conn) {
    try {
        // Insert default transaction types
        $transaction_types = [
            ['name' => 'Handpay', 'category' => 'OUT', 'description' => 'Manual payment to player'],
            ['name' => 'Ticket', 'category' => 'OUT', 'description' => 'Ticket out payment'],
            ['name' => 'Refill', 'category' => 'OUT', 'description' => 'Machine refill'],
            ['name' => 'Coins Drop', 'category' => 'DROP', 'description' => 'Coins inserted by players'],
            ['name' => 'Cash Drop', 'category' => 'DROP', 'description' => 'Cash inserted by players']
        ];
        
        $stmt = $conn->prepare("INSERT INTO transaction_types (name, category, description) VALUES (?, ?, ?)");
        
        foreach ($transaction_types as $type) {
            // Check if type already exists
            $check = $conn->prepare("SELECT id FROM transaction_types WHERE name = ?");
            $check->execute([$type['name']]);
            
            if ($check->rowCount() == 0) {
                $stmt->execute([$type['name'], $type['category'], $type['description']]);
            }
        }
        
        // Insert some sample brands
        $brands = [
            ['name' => 'IGT', 'description' => 'International Game Technology'],
            ['name' => 'Aristocrat', 'description' => 'Aristocrat Leisure Limited'],
            ['name' => 'Scientific Games', 'description' => 'Scientific Games Corporation'],
            ['name' => 'Konami', 'description' => 'Konami Gaming, Inc.'],
            ['name' => 'Novomatic', 'description' => 'Novomatic AG']
        ];
        
        $stmt = $conn->prepare("INSERT INTO brands (name, description) VALUES (?, ?)");
        
        foreach ($brands as $brand) {
            // Check if brand already exists
            $check = $conn->prepare("SELECT id FROM brands WHERE name = ?");
            $check->execute([$brand['name']]);
            
            if ($check->rowCount() == 0) {
                $stmt->execute([$brand['name'], $brand['description']]);
            }
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
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, role) VALUES (?, ?, ?, ?, 'admin')");
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
            $admin_username = $_POST['admin_username'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_name = $_POST['admin_name'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            
            if (empty($admin_username) || empty($admin_password) || empty($admin_name) || empty($admin_email)) {
                $error = "All fields are required";
            } else {
                $result = createAdminUser($conn, $admin_username, $admin_password, $admin_name, $admin_email);
                
                if ($result === true) {
                    $success = "Installation completed successfully! <a href='login.php'>Go to login page</a>";
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
            </div>
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                    <form method="POST" action="install.php">
                        <div class="form-group">
                            <label for="admin_username">Admin Username</label>
                            <input type="text" id="admin_username" name="admin_username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_password">Admin Password</label>
                            <input type="password" id="admin_password" name="admin_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_name">Admin Name</label>
                            <input type="text" id="admin_name" name="admin_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_email">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">Install</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
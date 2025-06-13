<?php
/**
 * Security Configuration and Functions
 * Implements various security measures for the Slot Management System
 */

// Security constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_EXPIRY', 1800); // 30 minutes

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting for login attempts
 */
function checkLoginAttempts($username, $ip_address) {
    global $conn;
    
    try {
        // Clean old attempts (older than lockout time)
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempt_time < ?");
        $stmt->execute([date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME)]);
        
        // Count recent attempts for this username/IP
        $stmt = $conn->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE (username = ? OR ip_address = ?) 
            AND attempt_time > ?
        ");
        $stmt->execute([
            $username, 
            $ip_address, 
            date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME)
        ]);
        
        $result = $stmt->fetch();
        return $result['attempts'] < MAX_LOGIN_ATTEMPTS;
        
    } catch (PDOException $e) {
        // If we can't check, allow the attempt but log the error
        error_log("Login attempt check failed: " . $e->getMessage());
        return true;
    }
}

/**
 * Record failed login attempt
 */
function recordFailedLogin($username, $ip_address) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO login_attempts (username, ip_address, attempt_time) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$username, $ip_address, date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {
        error_log("Failed to record login attempt: " . $e->getMessage());
    }
}

/**
 * Clear login attempts for successful login
 */
function clearLoginAttempts($username, $ip_address) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            DELETE FROM login_attempts 
            WHERE username = ? OR ip_address = ?
        ");
        $stmt->execute([$username, $ip_address]);
    } catch (PDOException $e) {
        error_log("Failed to clear login attempts: " . $e->getMessage());
    }
}

/**
 * Secure session management
 */
function initSecureSession() {
    // Prevent session fixation
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Input validation and sanitization
 */
function validateInput($input, $type = 'string', $options = []) {
    $input = trim($input);
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
            
        case 'int':
            $min = $options['min'] ?? null;
            $max = $options['max'] ?? null;
            $flags = 0;
            if ($min !== null || $max !== null) {
                $flags = FILTER_FLAG_ALLOW_RANGE;
                $options = ['min_range' => $min, 'max_range' => $max];
            }
            return filter_var($input, FILTER_VALIDATE_INT, $flags ? ['options' => $options] : 0);
            
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
            
        case 'ip':
            return filter_var($input, FILTER_VALIDATE_IP);
            
        case 'mac':
            return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $input) ? $input : false;
            
        case 'alphanumeric':
            return preg_match('/^[a-zA-Z0-9_]+$/', $input) ? $input : false;
            
        case 'string':
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Password strength validation
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = '', $severity = 'INFO') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO security_logs (event_type, details, severity, ip_address, user_agent, user_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $event,
            $details,
            $severity,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SESSION['user_id'] ?? null,
            date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log security event: " . $e->getMessage());
    }
}

/**
 * Check for suspicious activity
 */
function detectSuspiciousActivity() {
    // Check for rapid requests from same IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Simple rate limiting check
    if (!isset($_SESSION['request_count'])) {
        $_SESSION['request_count'] = 1;
        $_SESSION['request_start_time'] = time();
    } else {
        $_SESSION['request_count']++;
        
        // If more than 100 requests in 60 seconds, flag as suspicious
        if ($_SESSION['request_count'] > 100 && (time() - $_SESSION['request_start_time']) < 60) {
            logSecurityEvent('RATE_LIMIT_EXCEEDED', "IP: $ip, Requests: {$_SESSION['request_count']}", 'WARNING');
            
            // Reset counter
            $_SESSION['request_count'] = 1;
            $_SESSION['request_start_time'] = time();
            
            return true;
        }
        
        // Reset counter every minute
        if ((time() - $_SESSION['request_start_time']) > 60) {
            $_SESSION['request_count'] = 1;
            $_SESSION['request_start_time'] = time();
        }
    }
    
    return false;
}

/**
 * Generate secure random password
 */
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Sanitize file upload
 */
function sanitizeFileUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['error' => 'File type not allowed'];
    }
    
    if ($file['size'] > $max_size) {
        return ['error' => 'File too large'];
    }
    
    // Generate secure filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    
    return ['filename' => $filename, 'original' => $file['name']];
}
?>
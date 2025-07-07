<?php
/**
 * Common functions used throughout the application
 */

if (!function_exists('format_datetime')) {
    function format_datetime($timestamp, $format = 'd M Y H:i:s') {
        try {
            // Parse as UTC
            $dt = new DateTime($timestamp, new DateTimeZone('UTC'));
            
            // Convert to Cairo time
            $dt->setTimezone(new DateTimeZone('Africa/Cairo'));
            
            return $dt->format($format);
        } catch (Exception $e) {
            return 'Invalid date';
        }
    }
}

function cairo_time($format = 'd M Y – H:i') {
    $tz = new DateTimeZone('Africa/Cairo');
    $dt = new DateTime('now', $tz);

    // Force add 1 hour to simulate EEST (UTC+3)
    $dt->modify('+1 hour');

    return $dt->format($format);
}

/**
 * Check if user has required permission
 * 
 * @param string $required_role Minimum role required
 * @return bool True if user has permission, false otherwise
 */
function has_permission($required_role) {
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Admin has all permissions
    if ($user_role === 'admin') {
        return true;
    }
    
    // Editor has permissions for everything except settings
    if ($user_role === 'editor' && $required_role !== 'admin') {
        return true;
    }
    
    // Viewer has view-only permissions
    if ($user_role === 'viewer' && $required_role === 'viewer') {
        return true;
    }
    
    return false;
}

/**
 * Format currency amount with dollar sign
 * 
 * @param float $amount Amount to format
 * @return string Formatted amount with dollar sign
 */
function format_currency($amount) {
    return '$' . number_format((float)$amount, 2);
}

/**
 * Format date/time
 * 
 * @param string $datetime Date/time to format
 * @param string $format Format string
 * @return string Formatted date/time
 */
function format_date($dateString) {
    return date('d M Y', strtotime($dateString));
}

/**
 * Get transaction category (IN/OUT) based on type
 * 
 * @param string $type Transaction type
 * @return string Category (IN/OUT)
 */
function get_transaction_category($type) {
    global $transaction_types;
    return $transaction_types[$type]['category'] ?? 'Unknown';
}

/**
 * Calculate date range based on period
 * 
 * @param string $period Period (day, week, month)
 * @param string $date Reference date (YYYY-MM-DD)
 * @return array Start and end dates
 */
function calculate_date_range($period, $date = null) {
    // If date not provided, use current date
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $start_date = '';
    $end_date = '';
    
    switch ($period) {
        case 'day':
            $start_date = $date . ' 00:00:00';
            $end_date = $date . ' 23:59:59';
            break;
            
        case 'week':
            // Get start of week (Monday) and end of week (Sunday)
            $dt = new DateTime($date);
            $dt->modify('this week monday');
            $start_date = $dt->format('Y-m-d') . ' 00:00:00';
            
            $dt->modify('+6 days');
            $end_date = $dt->format('Y-m-d') . ' 23:59:59';
            break;
            
        case 'month':
            // Get start and end of month
            $dt = new DateTime($date);
            $start_date = $dt->format('Y-m-01') . ' 00:00:00';
            $end_date = $dt->format('Y-m-t') . ' 23:59:59';
            break;
            
        default:
            // Default to current day
            $start_date = date('Y-m-d') . ' 00:00:00';
            $end_date = date('Y-m-d') . ' 23:59:59';
    }
    
    return [
        'start' => $start_date,
        'end' => $end_date
    ];
}

/**
 * Create a log entry
 * 
 * @param string $action Action performed
 * @param string $details Additional details
 * @return bool Success/failure
 */
function log_action($action, $details = '') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $user_id = $_SESSION['user_id'] ?? 0;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        return $stmt->execute([$user_id, $action, $details, $ip_address]);
    } catch (PDOException $e) {
        // Log to error file instead
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

/**
 * Escapes data for HTML output to prevent XSS.
 * Should be used when displaying any user-provided or dynamic data in HTML.
 *
 * @param mixed $data Data to escape.
 * @return mixed Escaped data.
 */
function escape_html_output($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = escape_html_output($value);
        }
    } else {
        // Only perform HTML escaping here.
        // Input sanitization (trim, stripslashes, specific XSS pattern removal)
        // should happen when receiving input, not when outputting.
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Safely retrieves and sanitizes input from $_GET or $_POST.
 *
 * @param int $source INPUT_GET or INPUT_POST.
 * @param string $name The name of the input variable.
 * @param string $type Expected data type ('string', 'int', 'float', 'email', 'url', 'ip', 'mac', 'alphanumeric', 'bool').
 * @param mixed $default Default value if input is not found or invalid.
 * @return mixed Sanitized and validated input, or default value.
 */
function get_input($source, $name, $type = 'string', $default = null) {
    $filter = FILTER_UNSAFE_RAW;
    $options = [];

    switch ($type) {
        case 'int':
            $filter = FILTER_VALIDATE_INT;
            break;
        case 'float':
            $filter = FILTER_VALIDATE_FLOAT;
            break;
        case 'email':
            $filter = FILTER_VALIDATE_EMAIL;
            break;
        case 'url':
            $filter = FILTER_VALIDATE_URL;
            break;
        case 'ip':
            $filter = FILTER_VALIDATE_IP;
            break;
        case 'mac':
            // Custom validation for MAC address
            $value = filter_input($source, $name, FILTER_UNSAFE_RAW);
            if ($value !== null && preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value)) {
                return $value;
            }
            return $default;
        case 'alphanumeric':
            // Custom validation for alphanumeric (letters, numbers, underscore)
            $value = filter_input($source, $name, FILTER_UNSAFE_RAW);
            if ($value !== null && preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                return $value;
            }
            return $default;
        case 'bool':
            $filter = FILTER_VALIDATE_BOOLEAN;
            $options = FILTER_NULL_ON_FAILURE; // Returns null if not true/false/1/0/on/off/yes/no
            break;
        case 'string':
        default:
            $filter = FILTER_UNSAFE_RAW; // Raw string, then trim and strip slashes manually if needed
            break;
    }

    $value = filter_input($source, $name, $filter, $options);

    // For string types, apply trim and stripslashes after filter_input
    if ($type === 'string' && is_string($value)) {
        $value = trim($value);
        // stripslashes is generally not needed unless magic_quotes_gpc is enabled (which is deprecated)
        // If you encounter issues with backslashes, you might need to add:
        // $value = stripslashes($value);
    }

    // Return default if validation failed or input was not set
    return ($value === null || $value === false) ? $default : $value;
}

/**
 * Validate MAC address format
 * 
 * @param string $mac MAC address to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_mac($mac) {
    return (bool) preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
}

/**
 * Validate IP address format
 * 
 * @param string $ip IP address to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Check if current request is from a secure connection
 */
function is_secure_connection() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           $_SERVER['SERVER_PORT'] == 443 ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * Get client IP address (considering proxies)
 */
function get_client_ip() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Generate secure token for various purposes
 */
function generate_secure_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if user session is valid and not expired
 */
function is_valid_session() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        return false;
    }
    
    return true;
}

/**
 * Secure redirect function
 */
function secure_redirect($url) {
    // Validate URL to prevent open redirects
    $parsed_url = parse_url($url);
    
    // Only allow relative URLs or URLs to the same domain
    if (isset($parsed_url['host']) && $parsed_url['host'] !== $_SERVER['HTTP_HOST']) {
        $url = 'index.php'; // Default safe redirect
    }
    
    header("Location: $url");
    exit;
}

function icon($name) {
    return ICON_PATH . '/' . $name . '.png';
}

/**
 * Calculates the Monday and Sunday of the week for a given date,
 * and provides a consistent week label, handling year-spanning weeks.
 *
 * @param string $date_string A date string in 'YYYY-MM-DD' format.
 * @return array An associative array with 'start_date', 'end_date', 'week_label', 'week_year', 'week_number'.
 */
function get_monday_sunday_week_info($date_string) {
    $dt = new DateTime($date_string);

    // Set to Monday of the current week
    $dt->modify('this week monday');
    $start_date = $dt->format('Y-m-d');
    $week_year = $dt->format('Y'); // Year of the Monday
    $week_number = $dt->format('W'); // ISO week number based on Monday

    // Set to Sunday of the current week
    $dt->modify('this week sunday');
    $end_date = $dt->format('Y-m-d');

    $week_label = "{$week_year} Week {$week_number}";

    // Determine if the week spans across two different years
    $is_cross_year = (date('Y', strtotime($start_date)) !== date('Y', strtotime($end_date)));

    return [
        'start_date' => $start_date,
        'end_date' => $end_date,
        'week_label' => $week_label,
        'week_year' => $week_year,
        'week_number' => $week_number,
        'is_cross_year' => $is_cross_year // Add this new flag
    ];
}

/**
 * Sets a flash message to be displayed on the next page load.
 *
 * @param string $type The type of message (e.g., 'success', 'error', 'info', 'warning').
 * @param string $message The message content.
 * @param bool $is_html Whether the message content is HTML and should not be escaped.
 */
function set_flash_message($type, $message, $is_html = false) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message, 'is_html' => $is_html];
}

/**
 * Retrieves and clears all flash messages.
 *
 * @return array An array of flash messages, each with 'type', 'message', and 'is_html' keys.
 */
function get_flash_messages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    $_SESSION['flash_messages'] = []; // Clear messages after retrieval
    return $messages;
}

?>

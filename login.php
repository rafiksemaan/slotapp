<?php
// Login page with enhanced security
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$lockout_message = '';

// Check if there's a timeout message
if (isset($_GET['timeout'])) {
    $error = "Your session has expired. Please log in again.";
    logSecurityEvent('SESSION_TIMEOUT', 'User session expired');
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Use get_input for all POST data
    $username = get_input(INPUT_POST, 'username', 'alphanumeric');
    $password = get_input(INPUT_POST, 'password', 'string');
    $csrf_token_post = get_input(INPUT_POST, 'csrf_token', 'string');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Verify CSRF token
    if (!isset($csrf_token_post) || !verifyCSRFToken($csrf_token_post)) {
        $error = "Invalid request. Please try again.";
        logSecurityEvent('CSRF_TOKEN_INVALID', 'Invalid CSRF token on login', 'WARNING');
    } else {
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } elseif (!checkLoginAttempts($username, $ip_address)) {
            $lockout_message = "Too many failed login attempts. Please try again in " . (LOGIN_LOCKOUT_TIME / 60) . " minutes.";
            logSecurityEvent('LOGIN_LOCKOUT', "Username: {$username}, IP: {$ip_address}", 'WARNING');
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? AND status = 'Active'");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Check password strength and age
                    $stmt = $conn->prepare("SELECT updated_at FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $user_data = $stmt->fetch();
                    
                    // Login successful
                    session_regenerate_id(true); // Prevent session fixation
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Clear failed login attempts
                    clearLoginAttempts($username, $ip_address);
                    
                    // Log successful login
                    logSecurityEvent('LOGIN_SUCCESS', "Username: {$username}");
                    log_action('login', 'User logged in successfully');
                    
                    // Check if password is old (older than 90 days)
                    if ($user_data && strtotime($user_data['updated_at']) < (time() - (90 * 24 * 60 * 60))) {
                        $_SESSION['password_warning'] = true;
                    }
                    
                    // Redirect to dashboard
                    header("Location: index.php");
                    exit;
                } else {
                    // Record failed login attempt
                    recordFailedLogin($username, $ip_address);
                    logSecurityEvent('LOGIN_FAILED', "Username: {$username}, IP: {$ip_address}", 'WARNING');
                    
                    $error = "Invalid username or password";
                    
                    // Add delay to slow down brute force attacks
                    sleep(2);
                }
            } catch (PDOException $e) {
                logSecurityEvent('LOGIN_ERROR', "Database error: " . $e->getMessage(), 'ERROR');
                $error = "Login system temporarily unavailable. Please try again later.";
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Slot Management System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Slot Management System</h2>
                <p style="font-size: 0.9rem; opacity: 0.8;">Secure Access Portal</p>
            </div>
            <div class="login-body">
                <?php if (!empty($lockout_message)): ?>
                    <div class="lockout-message"><?php echo escape_html_output($lockout_message); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo escape_html_output($error); ?></div>
                <?php endif; ?>
                
                <div class="security-notice">
                    <strong>🔒 Security Notice:</strong> This system is protected by advanced security measures. 
                    All login attempts are monitored and logged.
                </div>
                
                <form method="POST" action="login.php" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html_output($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo escape_html_output(get_input(INPUT_POST, 'username', 'string', '')); ?>" 
                               required autocomplete="username" maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               required autocomplete="current-password" maxlength="255">
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                            🔐 Secure Login
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-muted); text-align: center;">
                    <p>🛡️ Protected by enterprise-grade security</p>
                    <p>Maximum <?php echo MAX_LOGIN_ATTEMPTS; ?> login attempts allowed</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/login_form.js"></script>
	
</body>
</html>

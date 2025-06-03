<?php
// Logout script
session_start();

// Log the logout action
require_once 'config/config.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    log_action('logout', 'User logged out');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
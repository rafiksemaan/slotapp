<?php
// Main entry point for the application
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// Get user role for permission checks
$user_role = $_SESSION['user_role'];

// Include header
include 'includes/header.php';

// Default page is dashboard
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Validate page access based on user role
$allowed = true;
$admin_pages = ['machines', 'brands', 'machine_types', 'machine_groups', 'users', 'import_transactions', 'action_logs', 'security_logs'];

if (in_array($page, $admin_pages) && $user_role != 'admin') {
    $allowed = false;
}

// Add weekly_tracking to allowed pages
if ($page == 'weekly_tracking' && !$allowed) { // This condition might need adjustment based on your existing logic
    // If you want to restrict weekly_tracking to certain roles, add that logic here.
    // For now, assuming it's generally allowed if not explicitly denied by 'settings' rule.
}


if ($allowed) {
    // Load the requested page
    $file_path = 'pages/' . $page . '.php';
    if (file_exists($file_path)) {
        include $file_path;
    } else {
        include 'pages/404.php';
    }
} else {
    // Show access denied page
    include 'pages/access_denied.php';
}

// Include footer
include 'includes/footer.php';
?>

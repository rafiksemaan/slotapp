<?php
// header.php

date_default_timezone_set('Africa/Cairo');
$page = $_GET['page'] ?? 'dashboard';

// Get current operation day for header display
try {
    $op_stmt = $conn->prepare("SELECT operation_date FROM operation_day ORDER BY id DESC LIMIT 1");
    $op_stmt->execute();
    $current_operation_day = $op_stmt->fetch(PDO::FETCH_ASSOC);
    $header_operation_date = $current_operation_day ? $current_operation_day['operation_date'] : date('Y-m-d');
} catch (PDOException $e) {
    $header_operation_date = date('Y-m-d');
}

// Buffer output to prevent headers sent error
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app_name; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/common_utils.js"></script> <!-- Added common_utils.js -->
	<!-- Favicon -->
	<link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96" />
	<link rel="icon" type="image/svg+xml" href="favicon.svg" />
	<link rel="shortcut icon" href="favicon.ico" />
	<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png" />
	
	<?php
	// Add Chart.js from CDN
	echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
	// Define base URL or path to icons
//	define('ICON_PATH', 'assets/icons'); // Make sure this matches your actual folder name
	?>
    <!-- Add SheetJS library for XLSX parsing -->
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js" defer></script>
</head>

<body>
    <div class="app-container">
        <header class="app-header">
            <div class="logo">
                <h1><?php echo $app_name; ?></h1>
			</div>
			
			<div class="logopic">
				<img src="<?= icon('sgc') ?>" alt="Logo" />
			</div>
			
			<div class="user-info">
                <div class="operation-day-display">
                    <span class="operation-day-label">Operation Day:</span>
                    <span class="operation-day-date"><?php echo format_date($header_operation_date); ?></span>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="index.php?page=operation_day" class="operation-day-link" title="Manage Operation Day">
                            <span class="menu-icon"><img src="<?= icon('calendar') ?>" alt="Calendar" /></span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <span class="username"><?php echo $_SESSION['username']; ?></span>
                    <span class="role"><?php echo $user_roles[$_SESSION['user_role']]; ?></span>
                </div>
				<div class="user-actions">
                    <a href="index.php?page=profile" class="profile-btn" title="My Profile">
                        <span class="menu-icon"><img src="<?= icon('profile') ?>" alt="Profile" /></span>
                    </a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </header>
        
        <div class="main-container">
            <aside class="sidebar" id="sidebar">
    <nav class="main-nav">
        <ul>
            <!-- Regular menu items -->
            <li><a href="index.php?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('home') ?>" alt="Dashboard" /></span>
                <span class="menu-text">Dashboard</span>
            </a></li>
			<?php if (has_permission('editor')): // This condition allows both admin and editor ?>
			<li><a href="index.php?page=operation_day" class="<?= $page == 'operation_day' ? 'active' : '' ?>">
				<span class="menu-icon"><img src="<?= icon('calendar') ?>" alt="operation_day" /></span>
				<span class="menu-text">Operation Day</span>
			</a></li>
			<?php endif; ?>
            <li><a href="index.php?page=transactions" class="<?= $page == 'transactions' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('dollar-sign') ?>" alt="transactions" /></span>
                <span class="menu-text">Transactions</span>
            </a></li>
            <li><a href="index.php?page=daily_tracking" class="<?= $page == 'daily_tracking' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('calendar') ?>" alt="daily_tracking" /></span>
                <span class="menu-text">Daily Tracking</span>
            </a></li>
            <li><a href="index.php?page=weekly_tracking" class="<?= $page == 'weekly_tracking' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('calendar') ?>" alt="weekly_tracking" /></span>
                <span class="menu-text">Weekly Tracking</span>
            </a></li>
            <li><a href="index.php?page=meters" class="<?= $page == 'meters' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('meters') ?>" alt="meters" /></span>
                <span class="menu-text">Meters</span>
            </a></li>
            <li><a href="index.php?page=general_report" class="<?= $page == 'general_report' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('report') ?>" alt="general_report" /></span>
                <span class="menu-text">General Report</span>
            </a></li>
            <li><a href="index.php?page=reports" class="<?= $page == 'reports' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('report') ?>" alt="reports" /></span>
                <span class="menu-text">Reports</span>
            </a></li>
			<li><a href="index.php?page=custom_report" class="<?= $page == 'custom_report' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('report-chart') ?>" alt="custom_report" /></span>
                <span class="menu-text">Custom Report</span>
            </a></li>
            <li><a href="index.php?page=guest_tracking" class="<?= $page == 'guest_tracking' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('users') ?>" alt="guest_tracking" /></span>
                <span class="menu-text">Guest Tracking</span>
            </a></li>
            <li><a href="index.php?page=profile" class="<?= $page == 'profile' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('profile') ?>" alt="profile" /></span>
                <span class="menu-text">My Profile</span>
            </a></li>

			<!-- Admin Only: Settings Section -->
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
			<li class="settings-menu <?= in_array($page, ['machines', 'brands', 'machine_types', 'machine_groups', 'users', 'operation_day', 'import_transactions', 'action_logs', 'security_logs']) ? 'open active' : '' ?>">
                <a href="#" class="settings-toggle">
                    <span class="menu-icon"><img src="<?= icon('settings') ?>" alt="Settings" /></span>
                    <span class="menu-text">Settings</span>
                    <span class="submenu-arrow">▾</span>
                </a>
                <ul class="submenu">
                    <li><a href="index.php?page=machines" class="<?= $page == 'machines' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('machines') ?>" alt="machines" /></span>
                        <span class="menu-text">Machines</span>
                    </a></li>
                    <li><a href="index.php?page=brands" class="<?= $page == 'brands' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('brands') ?>" alt="brands" /></span>
                        <span class="menu-text">Brands</span>
                    </a></li>
                    <li><a href="index.php?page=machine_types" class="<?= $page == 'machine_types' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('types') ?>" alt="types" /></span>
                        <span class="menu-text">Machine Types</span>
                    </a></li>
                    <li><a href="index.php?page=machine_groups" class="<?= $page == 'machine_groups' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('groups') ?>" alt="groups" /></span>
                        <span class="menu-text">Machine Groups</span>
                    </a></li>
                    <li><a href="index.php?page=users" class="<?= $page == 'users' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('users') ?>" alt="users" /></span>
                        <span class="menu-text">Users</span>
                    </a></li>
                    <li><a href="index.php?page=import_transactions" class="<?= $page == 'import_transactions' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('transaction') ?>" alt="Import Transactions" /></span>
                        <span class="menu-text">Import Transactions</span>
                    </a></li>
                    <!-- NEW: Action Logs Link -->
                    <li><a href="index.php?page=action_logs" class="<?= $page == 'action_logs' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('report') ?>" alt="Action Logs" /></span>
                        <span class="menu-text">Action Logs</span>
                    </a></li>
                    <!-- NEW: Security Logs Link -->
                    <li><a href="index.php?page=security_logs" class="<?= $page == 'security_logs' ? 'active' : '' ?>">
                        <span class="submenu-icon"><img src="<?= icon('settings') ?>" alt="Security Logs" /></span>
                        <span class="menu-text">Security Logs</span>
                    </a></li>
                </ul>
            </li>
            <?php endif; ?>

        </ul>
    </nav>
</aside>
            
            <main class="content">
                <div class="page-header">
                    <h2><?php echo ucfirst($page == 'custom_report' ? 'Custom Report' : ($page == 'general_report' ? 'General Report' : ($page == 'guest_tracking' ? 'Guest Tracking' : ($page == 'operation_day' ? 'Operation Day' : ($page == 'daily_tracking' ? 'Daily Tracking' : ($page == 'weekly_tracking' ? 'Weekly Tracking' : ($page == 'action_logs' ? 'Action Logs' : ($page == 'security_logs' ? 'Security Logs' : $page)))))))); ?></h2>
                </div>
                <div class="page-content">
				
		<script src="assets/js/sidebar.js"></script>
        <?php
        // Display flash messages
        $flash_messages = get_flash_messages();
        if (!empty($flash_messages)) {
            foreach ($flash_messages as $msg) {
                echo '<div class="alert alert-' . htmlspecialchars($msg['type']) . '">';
                if ($msg['is_html']) {
                    echo $msg['message'];
                } else {
                    echo htmlspecialchars($msg['message']);
                }
                echo '</div>';
            }
        }
        ?>

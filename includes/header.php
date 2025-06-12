<?php
// header.php

date_default_timezone_set('Africa/Cairo');
$page = $_GET['page'] ?? 'dashboard';

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
	
	<?php
// Define base URL or path to icons
define('ICON_PATH', 'assets/icons'); // Make sure this matches your actual folder name
?>
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
                <div class="user-details">
                    <span class="username"><?php echo $_SESSION['username']; ?></span>
                    <span class="role"><?php echo $user_roles[$_SESSION['user_role']]; ?></span>
                </div>
                <div class="user-actions">
                    <a href="index.php?page=profile" class="profile-btn" title="My Profile">
                        <span class="menu-icon"><img src="<?= icon('users') ?>" alt="Profile" /></span>
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
            <li><a href="index.php?page=transactions" class="<?= $page == 'transactions' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('dollar-sign') ?>" alt="transactions" /></span>
                <span class="menu-text">Transactions</span>
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
            <li><a href="index.php?page=profile" class="<?= $page == 'profile' ? 'active' : '' ?>">
                <span class="menu-icon"><img src="<?= icon('profile') ?>" alt="profile" /></span>
                <span class="menu-text">My Profile</span>
            </a></li>

            <!-- Admin Only: Settings Section -->
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
			<li class="settings-menu <?= in_array($page, ['machines', 'brands', 'machine_types', 'machine_groups', 'users']) ? 'open active' : '' ?>">
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
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
            
            <main class="content">
                <div class="page-header">
                    <h2><?php echo ucfirst($page == 'custom_report' ? 'Custom Report' : ($page == 'general_report' ? 'General Report' : $page)); ?></h2>
                </div>
                <div class="page-content">
				
				<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const settingsToggle = document.querySelector('.settings-toggle');
    const settingsMenu = document.querySelector('.settings-menu');
    
    let sidebarTimer;
    let isHovering = false;

    // Auto-hide sidebar after 3 seconds
    function startSidebarTimer() {
        clearTimeout(sidebarTimer);
        sidebarTimer = setTimeout(() => {
            if (!isHovering) {
                sidebar.classList.add('collapsed');
            }
        }, 1000);
    }

    // Show sidebar on hover
    function showSidebar() {
        isHovering = true;
        sidebar.classList.remove('collapsed');
        clearTimeout(sidebarTimer);
    }

    // Hide sidebar when not hovering
    function hideSidebar() {
        isHovering = false;
        startSidebarTimer();
    }

    // Event listeners for sidebar auto-hide
    sidebar.addEventListener('mouseenter', showSidebar);
    sidebar.addEventListener('mouseleave', hideSidebar);

    // Start the initial timer
    startSidebarTimer();

    // Settings menu functionality
    if (settingsToggle && settingsMenu) {
        // Load saved state
        const savedState = localStorage.getItem('settingsMenuOpen');
        if (savedState === 'true') {
            settingsMenu.classList.add('open');
        }

        // Toggle Settings menu
        settingsToggle.addEventListener('click', function (e) {
            e.preventDefault();
            const isOpen = settingsMenu.classList.toggle('open');
            localStorage.setItem('settingsMenuOpen', isOpen);
        });
    }

    // Auto-collapse Settings menu when clicking outside
    const links = document.querySelectorAll('.main-nav a');
    if (links.length > 0) {
        links.forEach(link => {
            // Skip the settings toggle itself
            if (link === settingsToggle) return;

            link.addEventListener('click', function () {
                const href = link.getAttribute('href');
                if (!href) return;

                // Parse the href to get page param
                const url = new URL(href, window.location.href);
                const targetPage = url.searchParams.get('page');

                // Collapse if not a settings subpage
                if (!['machines', 'brands', 'machine_types', 'machine_groups', 'users'].includes(targetPage)) {
                    if (settingsMenu) {
                        settingsMenu.classList.remove('open');
                        localStorage.setItem('settingsMenuOpen', 'false');
                    }
                }
            });
        });
    }
});
</script>
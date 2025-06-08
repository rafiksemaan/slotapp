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
</head>

<body>
    <div class="app-container">
        <header class="app-header">
            <div class="logo">
                <h1><?php echo $app_name; ?></h1>
            </div>
            <div class="user-info">
                <span class="username"><?php echo $_SESSION['username']; ?></span>
                <span class="role"><?php echo $user_roles[$_SESSION['user_role']]; ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>
        
        <div class="main-container">
            <aside class="sidebar">
    <nav class="main-nav">
        <ul>
            <!-- Regular menu items -->
            <li><a href="index.php?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>"><span class="menu-icon">🏠</span> Dashboard</a></li>
            <li><a href="index.php?page=transactions" class="<?= $page == 'transactions' ? 'active' : '' ?>"><span class="menu-icon">💰</span> Transactions</a></li>
            <li><a href="index.php?page=reports" class="<?= $page == 'reports' ? 'active' : '' ?>"><span class="menu-icon">📊</span> Reports</a></li>
			<li><a href="index.php?page=custom_report" class="<?= $page == 'custom_report' ? 'active' : '' ?>">📋 Custom Report</a></li>

            <!-- Admin Only: Settings Section -->
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <li class="settings-menu <?= in_array($page, ['machines', 'brands', 'machine_types', 'machine_groups', 'users']) ? 'open active' : '' ?>">
    <a href="#" class="settings-toggle">
        <span class="menu-icon">⚙️</span>
        Settings <span class="submenu-arrow">▾</span>
    </a>
    <ul class="submenu">
        <li><a href="index.php?page=machines" class="<?= $page == 'machines' ? 'active' : '' ?>">
            <span class="submenu-icon">🧪</span> Machines</a></li>
        <li><a href="index.php?page=brands" class="<?= $page == 'brands' ? 'active' : '' ?>">
            <span class="submenu-icon">🏷️</span> Brands</a></li>
        <li><a href="index.php?page=machine_types" class="<?= $page == 'machine_types' ? 'active' : '' ?>">
            <span class="submenu-icon">🎰</span> Machine Types</a></li>
        <li><a href="index.php?page=machine_groups" class="<?= $page == 'machine_groups' ? 'active' : '' ?>">
            <span class="submenu-icon">👥</span> Machine Groups</a></li>
        <li><a href="index.php?page=users" class="<?= $page == 'users' ? 'active' : '' ?>">
            <span class="submenu-icon">👥</span> Users</a></li>
    </ul>
</li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
            
            <main class="content">
                <div class="page-header">
                    <h2><?php echo ucfirst($page); ?></h2>
                </div>
                <div class="page-content">
				
				<script>
document.addEventListener('DOMContentLoaded', function () {
    const settingsToggle = document.querySelector('.settings-toggle');
    const settingsMenu = document.querySelector('.settings-menu');

    // Load saved state
    const savedState = localStorage.getItem('settingsMenuOpen');
    if (savedState === 'true') {
        settingsMenu.classList.add('open');
    }

    // Toggle Settings menu
    if (settingsToggle && settingsMenu) {
        settingsToggle.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent page jump
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
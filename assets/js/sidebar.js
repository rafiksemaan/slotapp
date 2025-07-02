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
        }, 500);
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

                // Collapse if not a settings subpage or weekly_tracking
                if (!['machines', 'brands', 'machine_types', 'machine_groups', 'users', 'operation_day', 'weekly_tracking', 'import_transactions', 'action_logs', 'security_logs'].includes(targetPage)) {
                    if (settingsMenu) {
                        settingsMenu.classList.remove('open');
                        localStorage.setItem('settingsMenuOpen', 'false');
                    }
                }
            });
        });
    }
});

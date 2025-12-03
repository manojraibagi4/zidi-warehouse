(function () {
    // Function to apply collapsed state
    function applyCollapsedState(isCollapsed) {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        // If sidebar doesn't exist (on public pages), exit early
        if (!sidebar || !mainContent || !toggleBtn) return;
        
        const sidebarTexts = sidebar.querySelectorAll('.sidebar-text');
        const sidebarHeadings = sidebar.querySelectorAll('.sidebar-heading');
        const adminIcons = sidebar.querySelectorAll('.admin-icon');

        if (isCollapsed) {
            // Collapse sidebar - show only icons
            sidebar.classList.remove('col-md-3', 'col-lg-2');
            sidebar.classList.add('col-md-1'); // Reduced width when collapsed

            mainContent.classList.remove('col-md-9', 'ms-sm-auto', 'col-lg-10');
            mainContent.classList.add('col-md-11', 'ms-sm-auto'); // Adjusted main content width

            // Hide sidebar text
            sidebarTexts.forEach(el => {
                el.style.display = 'none';
            });
            
            // Hide sidebar headings but keep admin icons visible
            sidebarHeadings.forEach(el => {
                // Hide the entire heading but we'll show the admin icons separately
                el.style.display = 'none';
            });
            
            // Make sure admin icons are always visible
            adminIcons.forEach(el => {
                el.style.display = 'inline';
                el.style.marginRight = '0'; // Remove margin when collapsed
            });
            
            // Center the icons and toggle button when collapsed
            const navItems = sidebar.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.style.textAlign = 'center';
            });
            
            // Update toggle button icon and center it
            toggleBtn.innerHTML = '<i class="bi bi-chevron-right fs-5"></i>';
            toggleBtn.setAttribute('title', 'Expand sidebar');
            toggleBtn.style.marginLeft = 'auto';
            toggleBtn.style.marginRight = 'auto';
            
        } else {
            // Expand sidebar - show icons and text
            sidebar.classList.remove('col-md-1');
            sidebar.classList.add('col-md-3', 'col-lg-2');

            mainContent.classList.remove('col-md-11');
            mainContent.classList.add('col-md-9', 'ms-sm-auto', 'col-lg-10');

            // Show sidebar text
            sidebarTexts.forEach(el => {
                el.style.display = 'inline';
            });
            
            // Show sidebar headings
            sidebarHeadings.forEach(el => {
                el.style.display = 'block';
            });
            
            // Reset admin icons to normal
            adminIcons.forEach(el => {
                el.style.display = 'inline';
                el.style.marginRight = '0.5rem'; // Restore margin when expanded
            });
            
            // Reset text alignment
            const navItems = sidebar.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.style.textAlign = '';
            });
            
            // Update toggle button icon and left-align it
            toggleBtn.innerHTML = '<i class="bi bi-list fs-5"></i>';
            toggleBtn.setAttribute('title', 'Collapse sidebar');
            toggleBtn.style.marginLeft = '0';
            toggleBtn.style.marginRight = '';
        }
    }

    // Initialize when DOM is ready
    function init() {
        const toggleBtn = document.getElementById('sidebarToggle');
        
        // If toggle button doesn't exist (on public pages), exit early
        if (!toggleBtn) return;

        // Get initial state from sessionStorage (default to false - expanded)
        const collapsed = sessionStorage.getItem('sidebarCollapsed') === 'true';
        
        // Apply initial state
        applyCollapsedState(collapsed);

        // Add click event listener to toggle button
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isCurrentlyCollapsed = sessionStorage.getItem('sidebarCollapsed') === 'true';
            const newState = !isCurrentlyCollapsed;
            
            // Save state to sessionStorage
            sessionStorage.setItem('sidebarCollapsed', newState);
            
            // Apply new state
            applyCollapsedState(newState);
        });

        console.log('Sidebar toggle initialized successfully');
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();


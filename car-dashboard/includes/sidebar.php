<?php defined('BASE_URL') or exit('No direct script access allowed'); ?>
<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-car"></i>
        <span>QUOLYWHEELS</span>
    </div>
    <nav class="menu">
        <ul>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">
                <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'vehicles.php' ? 'active' : '' ?>">
                <a href="vehicles.php"><i class="fas fa-car"></i> <span>Inventory</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'sales.php' ? 'active' : '' ?>">
                <a href="sales.php"><i class="fas fa-receipt"></i> <span>Sales</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'rentals.php' ? 'active' : '' ?>">
                <a href="rentals.php"><i class="fas fa-car-side"></i> <span>Rentals</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'customers.php' ? 'active' : '' ?>">
                <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'bug-reports.php' ? 'active' : '' ?>">
                <a href="bug-reports.php"><i class="fas fa-flag"></i> <span>Reports</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'callbacks.php' ? 'active' : '' ?>">
                <a href="callbacks.php"><i class="fas fa-phone-alt"></i> <span>Callbacks</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'testimonials.php' ? 'active' : '' ?>">
                <a href="testimonials.php"><i class="fa-solid fa-user-tie"></i> <span>Testimonials</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'calendar.php' ? 'active' : '' ?>">
                <a href="calendar.php"><i class="fas fa-calendar"></i> <span>Calendar</span></a>
            </li>
            <li class="<?= basename($_SERVER['SCRIPT_NAME']) === 'settings.php' ? 'active' : '' ?>">
                <a href="settings.php"><i class="fas fa-user"></i> <span>Profile</span></a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <div class="logout">
            <a href="includes/logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
        <!-- Desktop Sidebar Toggle Button -->
        <div class="sidebar-collapse">
            <button id="collapse-sidebar" title="Collapse Sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
    </div>
</aside>

<!-- Mobile overlay - positioned correctly -->
<div class="sidebar-overlay"></div>

<!-- Include the same CSS and JavaScript from your original sidebar file -->
<style>
/* All the existing sidebar styles from your original file */
/* Responsive Sidebar Styles - FIXED WIDTH AND OVERLAY ISSUES */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    background-color: #1f2937;
    color: #f9fafb;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    z-index: 1050; /* Bootstrap modal z-index is 1055, so we stay below */
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

/* Logo styling */
.sidebar .logo {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    min-height: 80px;
}

.sidebar .logo i {
    color: #e11d48;
    font-size: 1.5rem;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.sidebar .logo span {
    color: #e11d48;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

/* Menu styling */
.sidebar .menu {
    flex: 1;
    padding: 1rem 0;
}

.sidebar .menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar .menu li {
    margin-bottom: 0.25rem;
}

.sidebar .menu li a {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: #d1d5db;
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.sidebar .menu li a:hover {
    background-color: rgba(255, 255, 255, 0.05);
    color: #ffffff;
}

.sidebar .menu li.active a {
    background-color: rgba(225, 29, 72, 0.1);
    color: #e11d48;
    border-left: 3px solid #e11d48;
}

.sidebar .menu li a i {
    font-size: 1.1rem;
    min-width: 1.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sidebar .menu li a span {
    margin-left: 0.5rem;
    white-space: nowrap;
    opacity: 1;
    transition: opacity 0.3s ease;
}

/* Footer styling */
.sidebar-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 70px;
}

.sidebar-footer .logout a {
    display: flex;
    align-items: center;
    color: #d1d5db;
    text-decoration: none;
    transition: all 0.2s ease;
    padding: 0.5rem 0;
}

.sidebar-footer .logout a:hover {
    color: #e11d48;
}

.sidebar-footer .logout a i {
    font-size: 1.1rem;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.sidebar-footer .logout a span {
    white-space: nowrap;
    opacity: 1;
    transition: opacity 0.3s ease;
}

/* Desktop Sidebar Toggle Button */
.sidebar-collapse {
    display: none;
}

.sidebar-collapse button {
    background: none;
    border: none;
    color: #d1d5db;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.sidebar-collapse button:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

.sidebar-collapse button i {
    font-size: 0.875rem;
    transition: transform 0.3s ease;
}

/* Collapsed sidebar styles */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .logo span,
.sidebar.collapsed .menu li a span,
.sidebar.collapsed .sidebar-footer .logout a span {
    opacity: 0;
    visibility: hidden;
    width: 0;
    margin: 0;
}

.sidebar.collapsed .logo {
    justify-content: center;
    padding: 1.5rem 0.5rem;
}

.sidebar.collapsed .logo i {
    margin-right: 0;
}

.sidebar.collapsed .menu li a {
    justify-content: center;
    padding: 0.75rem;
}

.sidebar.collapsed .sidebar-footer {
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem 0.5rem;
}

.sidebar.collapsed .sidebar-footer .logout a {
    justify-content: center;
    padding: 0.5rem;
}

.sidebar.collapsed .sidebar-footer .logout a i {
    margin-right: 0;
}

.sidebar.collapsed .sidebar-collapse button i {
    transform: rotate(180deg);
}

/* Main content adjustment */
.main-content {
    margin-left: 250px;
    transition: all 0.3s ease;
    width: calc(100% - 250px);
}

.main-content.expanded {
    margin-left: 70px;
    width: calc(100% - 70px);
}

/* Mobile sidebar toggle button */
.toggle-sidebar {
    background: transparent;
    border: none;
    color: #e11d48;
    font-size: 1.25rem;
    cursor: pointer;
    display: none;
    padding: 0.5rem;
    margin-right: 0.5rem;
}

/* FIXED: Overlay for mobile - proper z-index and click handling */
.sidebar-overlay {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

/* Responsive breakpoints */
@media (min-width: 768px) {
    .sidebar-collapse {
        display: block;
    }
    
    /* Hide overlay on desktop */
    .sidebar-overlay {
        display: none;
    }
}

@media (max-width: 991.98px) {
    .sidebar {
        width: 220px;
    }
    
    .sidebar.collapsed {
        width: 65px;
    }
    
    .main-content {
        margin-left: 220px;
        width: calc(100% - 220px);
    }
    
    .main-content.expanded {
        margin-left: 65px;
        width: calc(100% - 65px);
    }
    
    .sidebar .logo {
        padding: 1.25rem;
    }
    
    .sidebar.collapsed .logo {
        padding: 1.25rem 0.5rem;
    }
    
    .sidebar .menu li a {
        padding: 0.65rem 1.25rem;
    }
    
    .sidebar.collapsed .menu li a {
        padding: 0.65rem;
    }
}

/* FIXED: Mobile styles with proper width */
@media (max-width: 767.98px) {
    .sidebar {
        transform: translateX(-100%);
        box-shadow: none;
        width: 70% !important;
        max-width: 180px !important;
        z-index: 1050;
    }
    
    .sidebar.collapsed {
        width: 70% !important;
        max-width: 180px !important;
    }
    
    .sidebar.collapsed .logo span,
    .sidebar.collapsed .menu li a span,
    .sidebar.collapsed .sidebar-footer .logout a span {
        opacity: 1;
        visibility: visible;
        width: auto;
        margin-left: 0.5rem;
    }
    
    .sidebar.collapsed .logo {
        justify-content: flex-start;
        padding: 1rem;
    }
    
    .sidebar.collapsed .logo i {
        margin-right: 0.75rem;
    }
    
    .sidebar.collapsed .menu li a {
        justify-content: flex-start;
        padding: 0.6rem 1rem;
    }
    
    .sidebar.collapsed .sidebar-footer {
        flex-direction: row;
        gap: 0;
        padding: 1rem;
    }
    
    .sidebar.collapsed .sidebar-footer .logout a {
        justify-content: flex-start;
        padding: 0.5rem 0;
    }
    
    .sidebar.collapsed .sidebar-footer .logout a i {
        margin-right: 0.75rem;
    }
    
    .sidebar-collapse {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .main-content.expanded {
        margin-left: 0;
        width: 100%;
    }
    
    .toggle-sidebar {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sidebar-overlay {
        display: block;
    }

    .sidebar .logo {
        padding: 0.75rem;
        min-height: 60px;
    }

    .sidebar .logo span {
        font-size: 1rem;
        letter-spacing: 0.25px;
    }

    .sidebar .menu li a {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .sidebar .menu li a span {
        margin-left: 0.5rem;
        white-space: nowrap;
        overflow: visible;
        text-overflow: clip;
    }

    .sidebar-footer {
        padding: 0.75rem;
        min-height: 50px;
    }

    .sidebar-footer .logout a {
        font-size: 0.875rem;
        padding: 0.25rem 0;
    }

    .sidebar-footer .logout a span {
        white-space: nowrap;
        overflow: visible;
    }

    .sidebar .menu li a i {
        min-width: 1.5rem;
        font-size: 1rem;
    }

    .sidebar .logo i {
        font-size: 1.25rem;
        margin-right: 0.5rem;
    }
}

@media (max-width: 575.98px) {
    .sidebar {
        width: 160px;
    }
    
    .sidebar .logo {
        padding: 1rem;
    }
    
    .sidebar .menu li a {
        padding: 0.6rem 1rem;
    }
}

/* Prevent body scroll when sidebar is open on mobile */
body.sidebar-open {
    overflow: hidden;
}

@media (min-width: 768px) {
    body.sidebar-open {
        overflow: auto;
    }
}

/* Add this to ensure the sidebar doesn't block content when open */
.sidebar.active {
    transform: translateX(0);
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
}

/* Ensure main content is accessible */
.main-content {
    position: relative;
    z-index: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get sidebar elements
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleSidebarBtns = document.querySelectorAll('.toggle-sidebar');
    const collapseSidebarBtn = document.getElementById('collapse-sidebar');
    
    // Remove the overlay element completely
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    if (sidebarOverlay) {
        sidebarOverlay.parentNode.removeChild(sidebarOverlay);
    }
    
    // Function to toggle sidebar on mobile
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        
        // Toggle body scroll lock
        if (sidebar.classList.contains('active')) {
            document.body.classList.add('sidebar-open');
        } else {
            document.body.classList.remove('sidebar-open');
        }
    }
    
    // Function to collapse sidebar on desktop
    function collapseSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        
        // Save state to localStorage
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        
        // Update button title
        if (collapseSidebarBtn) {
            const isCollapsed = sidebar.classList.contains('collapsed');
            collapseSidebarBtn.title = isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar';
        }
    }
    
    // Add click event to all mobile toggle buttons
    toggleSidebarBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
        });
    });
    
    // Add click event to desktop collapse button
    if (collapseSidebarBtn) {
        collapseSidebarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            collapseSidebar();
        });
    }
    
    // Close sidebar when clicking outside of it (mobile only)
    document.addEventListener('click', function(e) {
        // Only on mobile
        if (window.innerWidth <= 767.98) {
            // Check if sidebar is open and click is outside sidebar
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !Array.from(toggleSidebarBtns).some(btn => btn.contains(e.target))) {
                toggleSidebar();
            }
        }
    });
    
    // Close sidebar when pressing escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 767.98 && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });
    
    // Check localStorage for saved sidebar state (desktop only)
    if (window.innerWidth >= 768) {
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            if (collapseSidebarBtn) {
                collapseSidebarBtn.title = 'Expand Sidebar';
            }
        }
    }
    
    // Keyboard shortcut for sidebar collapse (Ctrl/Cmd + B) - desktop only
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'b' && window.innerWidth >= 768) {
            e.preventDefault();
            collapseSidebar();
        }
    });
});
</script>
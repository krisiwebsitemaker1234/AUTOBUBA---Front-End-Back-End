<?php defined('BASE_URL') or exit('No direct script access allowed'); ?>
<header class="top-nav">
    <div class="nav-container">
        <!-- Left-aligned controls -->
        <div class="nav-section left-controls">
            <!-- Sidebar Toggle for Mobile -->
            <button class="toggle-sidebar d-md-none" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Page Title with Time-Based Greeting -->
            <div class="page-title">
                <?php
                $current_hour = date('H');
                
                if ($current_hour >= 5 && $current_hour < 12) {
                    $greeting = "Good Morning";
                } elseif ($current_hour >= 12 && $current_hour < 18) {
                    $greeting = "Good Afternoon";
                } elseif ($current_hour >= 18 && $current_hour < 22) {
                    $greeting = "Good Evening";
                } else {
                    $greeting = "Good Night";
                }
                
                $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
                
                echo "<h1><span class=\"greeting-text\">{$greeting}<span class=\"comma-separator\">,</span></span> <span class=\"user-greeting\">{$username}</span></h1>";
                ?>
            </div>
        </div>

        <!-- Middle section - Status Indicators -->
        <div class="nav-section status-group">
            <div class="system-status">
                <div class="status-indicator online" title="System Online">
                    <i class="fas fa-server"></i>
                </div>
                <div class="status-indicator" id="db-status" title="Database Status">
                    <i class="fas fa-database"></i>
                </div>
                <div class="status-indicator" id="cpu-status" title="CPU Usage">
                    <i class="fas fa-microchip"></i>
                </div>
            </div>
        </div>

        <!-- Right-aligned controls -->
        <div class="nav-section controls-group">
            <!-- Date/Time -->
            <div class="datetime-display">
                <div class="time-container">
                    <span id="current-date"></span>
                    <span id="current-time"></span>
                </div>
            </div>
        
            <!-- User Info -->
            <div class="user" id="user-profile">
                <?php
                // Get avatar from session, which is populated from database during login
                $avatar = 'img/user-icon.png'; // Default
                
                if (isset($_SESSION['avatar']) && !empty($_SESSION['avatar'])) {
                    $avatar = $_SESSION['avatar'];
                } elseif (isset($_SESSION['user']) && !empty($_SESSION['user']['avatar'])) {
                    // Fallback to user array if direct avatar session variable not set
                    $avatar = $_SESSION['user']['avatar'];
                }
                
                // For admin users, make the avatar clickable to view/edit profile
                $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
                          (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin');
                
                if ($isAdmin) {
                    echo '<a href="settings.php" class="admin-avatar-link" title="View/Edit Profile">';
                }
                ?>
                <img src="<?= htmlspecialchars($avatar) ?>" 
                    alt="User Avatar"
                    class="user-avatar <?= $isAdmin ? 'admin-avatar' : '' ?>"
                    onerror="handleImageError(this)">
                <?php if ($isAdmin) echo '</a>'; ?>
                
                <span class="username-text"><?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['user']['username'] ?? 'Guest') ?></span>
            </div>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn d-md-none" onclick="toggleMobileMenu()">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
    </div>
    
    <!-- Mobile Menu Dropdown -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-item">
            <div class="mobile-datetime">
                <span id="mobile-current-date"></span>
                <span id="mobile-current-time"></span>
            </div>
        </div>
        <div class="mobile-menu-item">
            <div class="mobile-status">
                <span>System Status</span>
                <div class="mobile-status-indicators">
                    <div class="mobile-status-item">
                        <i class="fas fa-server"></i>
                        <span>Server: Online</span>
                    </div>
                    <div class="mobile-status-item" id="mobile-db-status">
                        <i class="fas fa-database"></i>
                        <span>Database: Checking...</span>
                    </div>
                    <div class="mobile-status-item" id="mobile-cpu-status">
                        <i class="fas fa-microchip"></i>
                        <span>CPU: Checking...</span>
                    </div>
                </div>
            </div>
        </div>
</header>

<!-- CSS Files -->
<link rel="stylesheet" href="assets/css/topnav.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Enhanced Responsive CSS -->
<style>
    /* Reset any conflicting styles */
    * {
        box-sizing: border-box;
    }
    
    /* Responsive Top Navigation */
    .top-nav {
        background-color: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        padding: 0.75rem 1.5rem;
        position: sticky;
        top: 0;
        width: 100%;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        z-index: 1000;
    }
    
    .nav-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        max-width: 100%;
        position: relative;
    }
    
    .nav-section {
        display: flex;
        align-items: center;
    }
    
    .left-controls {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0; /* Allow text truncation */
    }
    
    .page-title {
        flex: 1;
        min-width: 0;
    }
    
    .page-title h1 {
        font-size: 1.25rem;
        margin: 0;
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .greeting-text {
        color: #6b7280;
    }

    .comma-separator {
        color: #6b7280;
    }
    
    .user-greeting {
        color: #e11d48;
        font-weight: 700;
    }
    
    /* Status indicators - Clean icon-only design */
    .status-group {
        display: flex;
        align-items: center;
        margin: 0 1rem;
    }
    
    .system-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }
    
    .status-indicator {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: none !important;
        border: none !important;
        padding: 8px !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
        border-radius: 6px !important;
        position: relative;
    }
    
    .status-indicator i {
        font-size: 18px !important;
        line-height: 1 !important;
        color: #64748b !important;
        margin: 0 !important;
        padding: 0 !important;
        transition: all 0.3s ease !important;
        display: block !important;
        text-align: center !important;
    }
    
    /* Status states - Icon colors only */
    .status-indicator.online i {
        color: #16a34a !important;
    }
    
    .status-indicator.warning i {
        color: #d97706 !important;
    }
    
    .status-indicator.offline i {
        color: #dc2626 !important;
    }
    
    /* Hover effects - Subtle background on hover */
    .status-indicator:hover {
        background-color: rgba(0, 0, 0, 0.05) !important;
        transform: scale(1.1) !important;
    }
    
    .status-indicator.online:hover {
        background-color: rgba(22, 163, 74, 0.1) !important;
    }
    
    .status-indicator.warning:hover {
        background-color: rgba(217, 119, 6, 0.1) !important;
    }
    
    .status-indicator.offline:hover {
        background-color: rgba(220, 38, 38, 0.1) !important;
    }
    
    /* Toggle sidebar button styling */
    .toggle-sidebar {
        background: transparent !important;
        border: none !important;
        color: #e11d48 !important;
        font-size: 1.25rem !important;
        cursor: pointer !important;
        display: none !important;
        padding: 0.5rem !important;
        margin-right: 0.75rem !important;
        border-radius: 0.375rem !important;
        transition: all 0.2s ease !important;
        width: 40px !important;
        height: 40px !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .toggle-sidebar:hover {
        background-color: rgba(225, 29, 72, 0.1) !important;
        color: #be123c !important;
    }
    
    /* Mobile menu button */
    .mobile-menu-btn {
        background: transparent !important;
        border: none !important;
        color: #6b7280 !important;
        font-size: 1.1rem !important;
        cursor: pointer !important;
        display: none !important;
        padding: 0.5rem !important;
        margin-left: 0.5rem !important;
        border-radius: 0.375rem !important;
        transition: all 0.2s ease !important;
        width: 36px !important;
        height: 36px !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .mobile-menu-btn:hover {
        background-color: rgba(107, 114, 128, 0.1) !important;
        color: #374151 !important;
    }
    
    /* Mobile menu dropdown */
    .mobile-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        min-width: 280px;
        z-index: 1001;
        display: none;
        animation: slideDown 0.2s ease;
    }
    
    .mobile-menu.show {
        display: block;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .mobile-menu-item {
        padding: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .mobile-menu-item:last-child {
        border-bottom: none;
    }
    
    .mobile-datetime {
        text-align: center;
    }
    
    .mobile-datetime span {
        display: block;
        color: #6b7280;
    }
    
    #mobile-current-date {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    #mobile-current-time {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .mobile-status span {
        font-weight: 600;
        color: #1f2937;
        display: block;
        margin-bottom: 0.75rem;
    }
    
    .mobile-status-indicators {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .mobile-status-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        border-radius: 0.375rem;
        background: #f9fafb;
    }
    
    .mobile-status-item i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
    }
    
    .mobile-status-item.online i {
        color: #16a34a;
    }
    
    .mobile-status-item.warning i {
        color: #d97706;
    }
    
    .mobile-status-item.offline i {
        color: #dc2626;
    }
    
    .mobile-profile-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #1f2937;
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }
    
    .mobile-profile-link:hover {
        background: #f3f4f6;
        color: #e11d48;
    }
    
    /* User profile */
    .controls-group {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
    }
    
    .user:hover {
        background-color: #f9fafb;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    
    .user-avatar:hover {
        border-color: #e11d48;
    }
    
    .username-text {
        font-weight: 500;
        color: #4b5563;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
    }
    
    /* Date and time display */
    .datetime-display {
        text-align: right;
    }
    
    .time-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }
    
    #current-date {
        font-size: 0.75rem;
        color: #6b7280;
        line-height: 1.2;
    }
    
    #current-time {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1f2937;
        line-height: 1.2;
    }
    
    /* Admin avatar styling */
    .admin-avatar-link {
        display: inline-block;
        position: relative;
    }
    
    .admin-avatar {
        border: 2px solid #4CAF50 !important;
        transition: all 0.3s ease;
    }
    
    .admin-avatar:hover {
        transform: scale(1.1);
        box-shadow: 0 0 10px rgba(76, 175, 80, 0.7);
    }
    
    .admin-avatar::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        background-color: #4CAF50;
        border-radius: 50%;
        border: 2px solid white;
    }
    
    /* Responsive breakpoints */
    
    /* Large tablets and small desktops */
    @media (max-width: 1199.98px) {
        .top-nav {
            padding: 0.75rem 1.25rem;
        }
        
        .system-status {
            gap: 0.75rem;
        }
        
        .username-text {
            max-width: 100px;
        }
    }
    
    /* Tablets */
    @media (max-width: 991.98px) {
        .top-nav {
            padding: 0.75rem 1rem;
        }
        
        .status-group {
            margin: 0 0.75rem;
        }
        
        .system-status {
            gap: 0.5rem;
        }
        
        .status-indicator i {
            font-size: 17px !important;
        }
        
        .username-text {
            max-width: 80px;
        }
        
        .toggle-sidebar {
            display: flex !important;
        }
    }
    
    /* Small tablets and large phones */
    @media (max-width: 767.98px) {
        .top-nav {
            padding: 0.75rem 0.75rem;
        }
        
        .page-title h1 {
            font-size: 1.1rem;
        }
        
        .greeting-text {
            display: none;
        }

        /* Hide comma when greeting text is hidden */
        @media (max-width: 767.98px) {
            .greeting-text {
                display: none;
            }
        }
        
        .status-group {
            display: none;
        }
        
        .datetime-display {
            display: none;
        }
        
        .username-text {
            display: none;
        }
        
        .mobile-menu-btn {
            display: flex !important;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
        }
        
        .controls-group {
            gap: 0.5rem;
        }
    }
    
    /* Mobile phones */
    @media (max-width: 575.98px) {
        .top-nav {
            padding: 0.5rem 0.75rem;
        }
        
        .page-title h1 {
            font-size: 1rem;
        }
        
        .user-avatar {
            width: 30px;
            height: 30px;
        }
        
        .toggle-sidebar {
            width: 36px !important;
            height: 36px !important;
            margin-right: 0.5rem !important;
        }
        
        .mobile-menu {
            min-width: 260px;
            right: -0.75rem;
        }
    }
    
    /* Very small screens */
    @media (max-width: 400px) {
        .top-nav {
            padding: 0.5rem;
        }
        
        .page-title h1 {
            font-size: 0.9rem;
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
        }
        
        .toggle-sidebar {
            width: 32px !important;
            height: 32px !important;
            margin-right: 0.25rem !important;
        }
        
        .mobile-menu-btn {
            width: 32px !important;
            height: 32px !important;
        }
        
        .mobile-menu {
            min-width: 240px;
            right: -0.5rem;
        }
    }
    
    /* Landscape orientation adjustments for mobile */
    @media (max-height: 500px) and (orientation: landscape) {
        .top-nav {
            padding: 0.5rem 1rem;
        }
        
        .page-title h1 {
            font-size: 1rem;
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
        }
    }
</style>

<script>
    // Update date and time in real-time
    function updateDateTime() {
        const now = new Date();
        const dateElement = document.getElementById('current-date');
        const timeElement = document.getElementById('current-time');
        const mobileDateElement = document.getElementById('mobile-current-date');
        const mobileTimeElement = document.getElementById('mobile-current-time');
        
        const dateString = now.toLocaleDateString('en-US', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        
        const timeString = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (dateElement && timeElement) {
            dateElement.textContent = dateString;
            timeElement.textContent = timeString;
        }
        
        if (mobileDateElement && mobileTimeElement) {
            mobileDateElement.textContent = dateString;
            mobileTimeElement.textContent = timeString;
        }
    }
    
    setInterval(updateDateTime, 1000);
    updateDateTime(); // Initial call

    // Handle image loading errors
    function handleImageError(img) {
        console.log("Image failed to load, using default");
        img.src = 'img/user-icon.png';
    }
    
    // Toggle mobile menu
    function toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenu) {
            mobileMenu.classList.toggle('show');
        }
    }
    
    // Toggle sidebar function (to be implemented by parent page)
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
        
        // Dispatch custom event for parent page to handle
        document.dispatchEvent(new CustomEvent('sidebarToggle'));
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        
        if (mobileMenu && mobileMenuBtn && 
            !mobileMenu.contains(event.target) && 
            !mobileMenuBtn.contains(event.target)) {
            mobileMenu.classList.remove('show');
        }
    });
    
    // Simulate system status checks with more realistic behavior
    function checkSystemStatus() {
        // Simulate DB status (95% chance of being online)
        const dbOnline = Math.random() > 0.05;
        const dbStatus = document.getElementById('db-status');
        const mobileDbStatus = document.getElementById('mobile-db-status');
        
        if (dbStatus) {
            dbStatus.classList.remove('online', 'offline', 'warning');
            dbStatus.classList.add(dbOnline ? 'online' : 'offline');
            dbStatus.title = dbOnline ? 'Database Online' : 'Database Offline';
        }
        
        if (mobileDbStatus) {
            mobileDbStatus.classList.remove('online', 'offline', 'warning');
            mobileDbStatus.classList.add(dbOnline ? 'online' : 'offline');
            mobileDbStatus.querySelector('span').textContent = dbOnline ? 'Database: Online' : 'Database: Offline';
        }
        
        // Simulate CPU status with more realistic fluctuations
        const cpuStatus = document.getElementById('cpu-status');
        const mobileCpuStatus = document.getElementById('mobile-cpu-status');

        if (cpuStatus) {
            const currentLoad = parseFloat(cpuStatus.title?.match(/[\d.]+/) || [50])[0];
            
            // Random walk algorithm for CPU load (more realistic fluctuations)
            let cpuLoad = currentLoad + (Math.random() * 6 - 3); // fluctuate by ±3%
            cpuLoad = Math.max(15, Math.min(90, cpuLoad)); // Keep between 15% and 90%
            
            cpuStatus.classList.remove('online', 'warning', 'offline');
            
            let statusClass = 'online';
            let statusText = 'Normal';
            
            if (cpuLoad < 65) {
                statusClass = 'online';
                statusText = 'Normal';
            } else if (cpuLoad < 80) {
                statusClass = 'warning';
                statusText = 'High';
            } else {
                statusClass = 'offline'; // This just makes it red, not "down"
                statusText = 'Very High';
            }
            
            cpuStatus.classList.add(statusClass);
            cpuStatus.title = `CPU Usage: ${cpuLoad.toFixed(1)}%`;
            
            if (mobileCpuStatus) {
                mobileCpuStatus.classList.remove('online', 'warning', 'offline');
                mobileCpuStatus.classList.add(statusClass);
                mobileCpuStatus.querySelector('span').textContent = `CPU: ${cpuLoad.toFixed(1)}% (${statusText})`;
            }
        }
    }
    
    // Check status immediately and every 30 seconds
    checkSystemStatus();
    setInterval(checkSystemStatus, 30000);
    
    // Add click handlers for status indicators
    document.addEventListener('DOMContentLoaded', function() {
        const statusIndicators = document.querySelectorAll('.status-indicator');
        statusIndicators.forEach(indicator => {
            indicator.addEventListener('click', function() {
                console.log('Status clicked:', this.title);
            });
        });
    });
</script>

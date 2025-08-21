document.addEventListener('DOMContentLoaded', function() {
    // Initialize dark mode from localStorage or system preference
    initDarkMode();
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers with dark mode support
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        const popover = new bootstrap.Popover(popoverTriggerEl);
        // Update popover theme when dark mode changes
        document.body.addEventListener('darkModeChange', () => {
            popover._config.customClass = document.body.classList.contains('dark-mode') ? 'dark-popover' : '';
            popover.dispose();
            new bootstrap.Popover(popoverTriggerEl, popover._config);
        });
        return popover;
    });

    // Notification bell click
    const notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            const badge = this.querySelector('.notification-count');
            if (badge) {
                badge.style.display = 'none';
            }
            // You might want to add an AJAX call here to mark notifications as read
        });
    }

    // Dark mode toggle
    const modeToggle = document.querySelector('.mode-toggle');
    if (modeToggle) {
        modeToggle.addEventListener('click', toggleDarkMode);
    }

    // Confirm before performing destructive actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Initialize any charts
    initCharts();
});

function initDarkMode() {
    // Check localStorage for user preference
    const storedMode = localStorage.getItem('darkMode');
    
    // Check system preference if no stored preference
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Apply dark mode if stored preference is true or system prefers dark (and no stored preference)
    if (storedMode === 'true' || (storedMode === null && systemPrefersDark)) {
        document.body.classList.add('dark-mode');
    }
    
    // Update the toggle icon
    updateModeIcon();
    
    // Dispatch event for other scripts to know dark mode has been initialized
    const event = new Event('darkModeInitialized');
    document.body.dispatchEvent(event);
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
    updateModeIcon();
    
    // Dispatch event for other components to react to mode change
    const event = new Event('darkModeChange');
    document.body.dispatchEvent(event);
}

function updateModeIcon() {
    const icon = document.querySelector('.mode-toggle i');
    if (icon) {
        if (document.body.classList.contains('dark-mode')) {
            icon.classList.replace('fa-moon', 'fa-sun');
        } else {
            icon.classList.replace('fa-sun', 'fa-moon');
        }
    }
}

function initCharts() {
    // Listen for dark mode changes to update charts
    document.body.addEventListener('darkModeChange', function() {
        // Reinitialize or update charts here
        // This would depend on your specific chart implementations
    });
    
    // Additional charts initialization can go here
    // Make sure to use CSS variables for colors to support dark mode
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Helper function to get current theme colors
function getThemeColors() {
    return {
        primary: getComputedStyle(document.body).getPropertyValue('--primary').trim(),
        textColor: getComputedStyle(document.body).getPropertyValue('--text-color').trim(),
        cardBg: getComputedStyle(document.body).getPropertyValue('--card-bg').trim()
    };
}
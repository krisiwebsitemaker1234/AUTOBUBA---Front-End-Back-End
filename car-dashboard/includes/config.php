<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base configuration
define('BASE_URL', 'http://localhost/car-dashboard');
define('SITE_NAME', 'QUOLYWHEELS');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'quolywheels_db'); 
define('DB_USER', 'root');
define('DB_PASS', '');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// File paths
define('ASSETS_PATH', BASE_URL . '/assets');
define('UPLOADS_PATH', __DIR__ . '/uploads');

// Security
define('CSRF_TOKEN_SECRET', 'your-secret-key-here'); // Change this to a random string in production
define('PASSWORD_PEPPER', 'your-password-pepper');   // Change this to a random string in production

// Timezone default
define('DEFAULT_TIMEZONE', 'UTC');

// File upload settings
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
<?php
// includes/auth.php
require_once 'config.php';
require_once 'db.php';

function login($username, $password) {
    $user = query("SELECT * FROM users WHERE username = ?", [$username])->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Store all user data in session
        $_SESSION['user'] = $user;  // Store entire user array
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Set avatar from database
        $_SESSION['avatar'] = !empty($user['avatar']) ? $user['avatar'] : 'img/user-icon.png';
        
        return true;
    }
    return false;
}

// Rest of the auth.php file remains unchanged
function currentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // Get full user data from database
    $user = getSingle("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    
    if (!$user) {
        // User ID in session doesn't exist in database
        session_destroy();
        return null;
    }
    
    return $user;
}


function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect if already logged in (for login/register pages)
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Log in user
 *
 * @param int $userId User ID
 * @param string $username Username
 * @param bool $remember Remember me option
 */
function loginUser($userId, $username, $remember = false) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    
    // Get user data including avatar
    $user = getSingle("SELECT * FROM users WHERE id = ?", [$userId]);
    if ($user) {
        $_SESSION['avatar'] = !empty($user['avatar']) ? $user['avatar'] : 'img/user-icon.png';
        $_SESSION['role'] = $user['role'];
    }
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (86400 * 30); // 30 days
        
        // Store token in database
        global $db;
        $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $userId, $token, $expires);
        $stmt->execute();
        
        // Set cookie
        setcookie('remember_token', $token, $expires, '/');
    }
    
    // Redirect to intended page if set, otherwise to dashboard
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header("Location: $redirect");
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

/**
 * Log out user
 */
function logoutUser() {
    // Clear remember token if exists
    if (isset($_COOKIE['remember_token'])) {
        // Make sure we have a database connection
        if (!isset($GLOBALS['db'])) {
            // Try to establish connection
            try {
                $GLOBALS['db'] = DB::getInstance()->getConnection();
            } catch (Exception $e) {
                // If connection fails, continue with logout process
                error_log("Database connection failed in logout: " . $e->getMessage());
            }
        }
        
        if (isset($GLOBALS['db'])) {
            $token = $_COOKIE['remember_token'];
            
            // Delete token from database
            try {
                $stmt = $GLOBALS['db']->prepare("DELETE FROM remember_tokens WHERE token = ?");
                $stmt->bindParam(1, $token, PDO::PARAM_STR);
                $stmt->execute();
            } catch (PDOException $e) {
                error_log("Error deleting remember token: " . $e->getMessage());
            }
        }
        
        // Clear cookie regardless of database status
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Clear dark mode cookie
    if (isset($_COOKIE['dark_mode'])) {
        setcookie('dark_mode', '', time() - 3600, '/');
    }
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Destroy session
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header('Location: ../../login.php');
    exit;
}

/**
 * Check for remember me cookie and log in user if valid
 */
function checkRememberMe() {
    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        global $db;
        $token = $_COOKIE['remember_token'];
        
        // Find valid token
        $stmt = $db->prepare("SELECT t.user_id, u.username, u.avatar, u.role FROM remember_tokens t 
                             JOIN users u ON t.user_id = u.id 
                             WHERE t.token = ? AND t.expires > ?");
        $now = time();
        $stmt->bind_param('si', $token, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Log user in
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['avatar'] = !empty($row['avatar']) ? $row['avatar'] : 'img/user-icon.png';
            $_SESSION['role'] = $row['role'];
            $_SESSION['last_activity'] = time();
            
            // Refresh token
            $expires = time() + (86400 * 30); // 30 days
            $stmt = $db->prepare("UPDATE remember_tokens SET expires = ? WHERE token = ?");
            $stmt->bind_param('is', $expires, $token);
            $stmt->execute();
            
            // Refresh cookie
            setcookie('remember_token', $token, $expires, '/');
        }
    }
}

// Check for "remember me" when file is included
checkRememberMe();

/**
 * Check session timeout and logout if inactive
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        $timeout = 30 * 60; // 30 minutes
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            // Session has expired
            logoutUser();
        } else {
            // Update last activity time
            $_SESSION['last_activity'] = time();
        }
    }
}

// Check session timeout when file is included
checkSessionTimeout();
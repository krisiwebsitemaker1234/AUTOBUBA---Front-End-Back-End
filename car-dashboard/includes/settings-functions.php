<?php
/**
 * Settings-specific functions that work with the PDO database connection
 */
    function getUserSettings() {
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
    return currentUser();
}

/**
 * Update user profile information
 */
function updateUserProfile($data) {
    global $pdo;
    
    $user = currentUser();
    if (!$user) return ['success' => false, 'message' => 'Authentication required'];

    // Validate inputs
    $required = ['first_name', 'last_name', 'email'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field $field is required"];
        }
    }

    // Email validation
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }

    // Check email uniqueness
    $emailCheck = getSingle("SELECT id FROM users WHERE email = ? AND id != ?", [$data['email'], $user['id']]);
    if ($emailCheck) {
        return ['success' => false, 'message' => 'Email already in use'];
    }

    try {
        // Update profile
        $stmt = $pdo->prepare("UPDATE users SET 
            first_name = ?,
            last_name = ?,
            email = ?,
            phone = ?
            WHERE id = ?");
        
        $result = $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? '',
            $user['id']
        ]);
        
        return $result 
            ? ['success' => true, 'message' => 'Profile updated'] 
            : ['success' => false, 'message' => 'Database error'];
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Change user password
 */
function changeUserPassword($userId, $currentPassword, $newPassword) {
    global $pdo;

    try {
        // Get current hash
        $user = getSingle("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password incorrect'];
        }

        // Validate new password
        if (strlen($newPassword) < 8 || 
           !preg_match('/[A-Z]/', $newPassword) || 
           !preg_match('/[0-9]/', $newPassword)) {
            return ['success' => false, 'message' => 'Password must be 8+ chars with uppercase and number'];
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$newHash, $userId]);
        
        return $result
            ? ['success' => true, 'message' => 'Password updated']
            : ['success' => false, 'message' => 'Database error'];
    } catch (PDOException $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Update user preferences
 */
function updateUserPreferences($userId, $darkMode, $timezone) {
    global $pdo;
    
    // Validate timezone
    if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
        return ['success' => false, 'message' => 'Invalid timezone'];
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET 
            dark_mode = ?,
            timezone = ?
            WHERE id = ?");
        
        $result = $stmt->execute([$darkMode, $timezone, $userId]);
        
        return $result
            ? ['success' => true, 'message' => 'Preferences updated']
            : ['success' => false, 'message' => 'Database error'];
    } catch (PDOException $e) {
        error_log("Preferences update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Update user avatar
 */
function updateUserAvatar($userId, $file) {
    global $pdo;

    // Validate file
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large (max 2MB)'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Upload directory
    $uploadDir = 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "avatar_{$userId}_" . time() . ".$ext";
    $targetPath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'File upload failed'];
    }

    try {
        // Get old avatar
        $oldAvatar = getSingle("SELECT avatar FROM users WHERE id = ?", [$userId]);
        
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $result = $stmt->execute([$targetPath, $userId]);
        
        if ($result) {
            // Delete old avatar if exists
            if ($oldAvatar && !empty($oldAvatar['avatar']) && file_exists($oldAvatar['avatar'])) {
                unlink($oldAvatar['avatar']);
            }
            return ['success' => true, 'message' => 'Avatar updated'];
        }
        
        unlink($targetPath); // Cleanup if DB failed
        return ['success' => false, 'message' => 'Database error'];
    } catch (PDOException $e) {
        error_log("Avatar update error: " . $e->getMessage());
        unlink($targetPath); // Cleanup if exception
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Remove user avatar
 */
function removeUserAvatar($userId) {
    global $pdo;

    try {
        // Get current avatar
        $user = getSingle("SELECT avatar FROM users WHERE id = ?", [$userId]);

        // Delete file
        if ($user && !empty($user['avatar']) && file_exists($user['avatar'])) {
            unlink($user['avatar']);
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        $result = $stmt->execute([$userId]);
        
        return $result
            ? ['success' => true, 'message' => 'Avatar removed']
            : ['success' => false, 'message' => 'Database error'];
    } catch (PDOException $e) {
        error_log("Avatar removal error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Create a timezone helper file
 */
function createTimezoneHelper() {
    $content = '<?php
    if (isset($_GET["timezone"])) {
        $timezone = $_GET["timezone"];
        
        // Validate timezone
        if (in_array($timezone, DateTimeZone::listIdentifiers())) {
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone($timezone));
            echo $date->format("F j, Y - g:i A");
        } else {
            echo "Invalid timezone";
        }
    } else {
        echo "No timezone specified";
    }
    ?>';
    
    $file = 'includes/get_timezone_time.php';
    file_put_contents($file, $content);
}

// Create the timezone helper file if it doesn't exist
if (!file_exists('includes/get_timezone_time.php')) {
    createTimezoneHelper();
}
?>
<?php
session_start();
require_once 'includes/config.php'; 
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/settings-functions.php';
requireAuth();

$user = currentUser();

// Check if user is admin
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
          (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') ||
          (isset($user['role']) && $user['role'] === 'admin');

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information (full name and timezone)
        $fullName = trim($_POST['full_name']);
        $timezone = trim($_POST['timezone']);
        
        // Validate timezone
        $europeTimezones = DateTimeZone::listIdentifiers(DateTimeZone::EUROPE);
        if (!in_array($timezone, $europeTimezones)) {
            $error_message = "Invalid timezone selected";
        } else {
            try {
                $result = query(
                    "UPDATE users SET full_name = ?, timezone = ? WHERE id = ?",
                    [$fullName, $timezone, $user['id']]
                );
                
                if ($result) {
                    $success_message = "Profile updated successfully!";
                    $user = currentUser(); // Refresh user data
                } else {
                    $error_message = "Failed to update profile";
                }
            } catch (Exception $e) {
                $error_message = 'Error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_avatar_url']) && $isAdmin) {
        // Update avatar URL (for admin users)
        $avatar_url = trim($_POST['avatar_url']);
        
        // Validate URL format
        if (!empty($avatar_url) && !filter_var($avatar_url, FILTER_VALIDATE_URL)) {
            $error_message = "Invalid avatar URL format";
        } else {
            // Update user avatar URL in database
            $result = updateUserAvatarUrl($user['id'], $avatar_url);
            if ($result['success']) {
                // Update session variables
                $_SESSION['avatar'] = $avatar_url;
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['avatar'] = $avatar_url;
                }
                
                $success_message = "Avatar URL updated successfully!";
                $user = currentUser();
            } else {
                $error_message = $result['message'] ?? "Failed to update avatar URL";
            }
        }
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        // Handle avatar upload
        $result = updateUserAvatar($user['id'], $_FILES['avatar']);
        if ($result['success']) {
            $success_message = "Avatar updated successfully!";
            $user = currentUser();
        } else {
            $error_message = $result['message'];
        }
    } elseif (isset($_POST['remove_avatar'])) {
        // Remove avatar
        $result = removeUserAvatar($user['id']);
        if ($result['success']) {
            $success_message = "Avatar removed successfully!";
            $user = currentUser();
        } else {
            $error_message = $result['message'];
        }
    }
}

// Function to update user avatar URL (for admin users)
function updateUserAvatarUrl($userId, $avatarUrl) {
    try {
        $result = query(
            "UPDATE users SET avatar = ? WHERE id = ?",
            [$avatarUrl, $userId]
        );
        
        if ($result) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Database update failed'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | <?= SITE_NAME ?></title>
    <link rel="icon" href="img/autobuba logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e11d48;
            --primary-dark: #be123c;
            --primary-light: #fecdd3;
            --primary-lighter: #ffe4e6;
            --secondary-color: #6b7280;
            --text-color: #1f2937;
            --light-text: #6b7280;
            --border-color: #e5e7eb;
            --background: #f9fafb;
            --white: #ffffff;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        body {
            background-color: var(--background);
            color: var(--text-color);
        }
        
        .profile-container {
            max-width: 100%;
            margin-bottom: 2rem;
        }
        
        .profile-card {
            background-color: var(--white);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            border-top: 4px solid var(--primary-color);
        }
        
        .profile-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .profile-card-header h4 {
            margin: 0;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .profile-card-header i {
            margin-right: 0.75rem;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        .avatar-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            position: relative;
            margin-bottom: 1rem;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 3rem;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 3px solid var(--white);
        }
        
        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            padding: 0.35rem;
            font-size: 0.8rem;
            color: var(--white);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .avatar-overlay:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        /* Admin badge and styling */
        .admin-badge {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
        
        .admin-avatar-container {
            border: 3px solid #4CAF50;
            position: relative;
        }
        
        .admin-avatar-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 20px;
            height: 20px;
            background-color: #4CAF50;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 1;
        }
        
        /* Admin-specific styles */
        .admin-section {
            background-color: rgba(76, 175, 80, 0.1);
            border-left: 3px solid #4CAF50;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 0.25rem;
        }
        
        .admin-section h5 {
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        
        .avatar-preview {
            margin-top: 1rem;
            text-align: center;
        }
        
        .avatar-preview img {
            max-width: 120px;
            max-height: 120px;
            border-radius: 50%;
            border: 2px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Predefined avatar options */
        .avatar-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .avatar-option {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .avatar-option:hover {
            transform: scale(1.1);
            border-color: var(--primary-color);
        }
        
        .avatar-option.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .user-info-item {
            display: flex;
            margin-bottom: 0.75rem;
            align-items: center;
        }
        
        .user-info-label {
            font-weight: 600;
            min-width: 120px;
            color: var(--secondary-color);
        }
        
        .user-info-value {
            flex: 1;
        }
        
        .info-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .info-badge-primary {
            background-color: var(--primary-lighter);
            color: var(--primary-dark);
        }
        
        .info-badge-success {
            background-color: rgba(76, 175, 80, 0.2);
            color: #2e7d32;
        }
        
        .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 1.5rem 0;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .account-info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .account-info-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .account-info-list li:last-child {
            border-bottom: none;
        }
        
        .account-info-list .info-label {
            color: var(--secondary-color);
        }
        
        .account-info-list .info-value {
            font-weight: 500;
        }
        
        .timezone-display {
            background-color: var(--primary-lighter);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .timezone-display i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <?php include 'includes/topnav.php'; ?>

            <!-- Content -->
            <div class="content">
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <div>
                        <h1 class="mb-1">My Profile</h1>
                        <p class="text-muted mb-0">
                            View and manage your account information
                            <?php if ($isAdmin): ?>
                                <span class="admin-badge">Admin</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Profile Layout -->
                <div class="profile-container">
                    <div class="row">
                        <!-- Left Column - Profile Information -->
                        <div class="col-lg-8">
                            <!-- Basic Profile Information -->
                            <div class="profile-card">
                                <div class="profile-card-header">
                                    <i class="fas fa-user-circle"></i>
                                    <h4>Profile Information</h4>
                                </div>
                                
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-4 mb-4 mb-md-0">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="avatar-container <?= $isAdmin ? 'admin-avatar-container' : '' ?>">
                                                    <?php if (!empty($user['avatar'])): ?>
                                                        <img src="<?= $user['avatar'] ?>" alt="Profile">
                                                    <?php else: ?>
                                                        <?= substr($user['full_name'] ?? 'U', 0, 1) ?>
                                                    <?php endif; ?>
                                                    <div class="avatar-overlay">
                                                        <i class="fas fa-camera"></i> Change
                                                    </div>
                                                </div>
                                                <input type="file" id="avatar-upload" name="avatar" class="d-none" accept="image/*">
                                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="remove-avatar-btn">
                                                    <i class="fas fa-trash-alt me-1"></i> Remove
                                                </button>
                                                <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
                                                
                                                <?php if ($isAdmin): ?>
                                                <div class="mt-3 text-center">
                                                    <span class="info-badge info-badge-success">
                                                        <i class="fas fa-user-shield"></i> Admin account
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                                            </div>
                                            
                                            <div class="user-info-item">
                                                <div class="user-info-label">Username:</div>
                                                <div class="user-info-value"><?= htmlspecialchars($user['username'] ?? '') ?></div>
                                            </div>
                                            
                                            <div class="user-info-item">
                                                <div class="user-info-label">Role:</div>
                                                <div class="user-info-value">
                                                    <span class="info-badge info-badge-primary">
                                                        <?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone" required>
                                        <?php
                                        $europeTimezones = DateTimeZone::listIdentifiers(DateTimeZone::EUROPE);
                                        foreach ($europeTimezones as $tz) {
                                            $selected = ($tz === ($user['timezone'] ?? 'UTC')) ? 'selected' : '';
                                            echo "<option value=\"$tz\" $selected>$tz</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                            
                                            <div class="divider"></div>
                                            
                                            <div class="text-end">
                                                <button type="submit" name="update_profile" class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Account Information -->
                            <div class="profile-card">
                                <div class="profile-card-header">
                                    <i class="fas fa-info-circle"></i>
                                    <h4>Account Information</h4>
                                </div>
                                
                                <ul class="account-info-list">
                                    <li>
                                        <span class="info-label">Account Created</span>
                                        <span class="info-value">
                                            <?php 
                                            if (isset($user['created_at'])) {
                                                $created = new DateTime($user['created_at']);
                                                echo $created->format('F j, Y');
                                            } else {
                                                echo 'Not available';
                                            }
                                            ?>
                                        </span>
                                    </li>
                                    <li>
                                        <span class="info-label">Last Login</span>
                                        <span class="info-value">
                                            <?php 
                                            if (isset($user['last_login'])) {
                                                $lastLogin = new DateTime($user['last_login']);
                                                echo $lastLogin->format('F j, Y - g:i A');
                                            } else {
                                                echo 'Not available';
                                            }
                                            ?>
                                        </span>
                                    </li>
                                </ul>
                                
                                <div class="timezone-display mt-3">
                                    <i class="fas fa-clock"></i> Current time in your timezone: 
                                    <strong>
                                        <?php 
                                        $date = new DateTime();
                                        $date->setTimezone(new DateTimeZone($user['timezone'] ?? 'UTC'));
                                        echo $date->format('F j, Y - g:i A');
                                        ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Admin Settings -->
                        <div class="col-lg-4">
                            <?php if ($isAdmin): ?>
                            <!-- Admin Settings Card -->
                            <div class="profile-card">
                                <div class="profile-card-header">
                                    <i class="fas fa-user-shield"></i>
                                    <h4>Admin Settings</h4>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> These settings are only available to administrators.
                                </div>
                                
                                <!-- Admin Avatar URL Form -->
                                <form action="" method="POST">
                                    <div class="mb-4">
                                        <h5><i class="fas fa-image me-2"></i>Avatar URL Settings</h5>
                                        <p class="text-muted small">As an admin, you can directly set your avatar from a URL instead of uploading a file.</p>
                                        
                                        <div class="mb-3">
                                            <label for="avatar_url" class="form-label">Avatar URL</label>
                                            <input type="url" class="form-control" id="avatar_url" name="avatar_url" 
                                                   value="<?= htmlspecialchars($user['avatar'] ?? '') ?>"
                                                   placeholder="https://example.com/avatar.png"
                                                   oninput="previewAvatar(this.value)">
                                            <small class="text-muted">Enter a valid URL to an image</small>
                                        </div>
                                        
                                        <div class="avatar-preview">
                                            <img id="avatar-preview-img" 
                                                 src="<?= htmlspecialchars($user['avatar'] ?? 'img/user-icon.png') ?>" 
                                                 alt="Avatar Preview" 
                                                 onerror="this.src='img/user-icon.png'">
                                        </div>
                                        
                                        <!-- Predefined avatar options -->
                                        <div class="mt-3">
                                            <label class="form-label">Quick Select Avatars</label>
                                            <div class="avatar-options">
                                                <img src="https://cdn-icons-png.flaticon.com/512/2206/2206368.png" class="avatar-option" onclick="selectAvatar(this.src)" alt="Avatar 1">
                                                <img src="https://cdn-icons-png.flaticon.com/512/219/219969.png" class="avatar-option" onclick="selectAvatar(this.src)" alt="Avatar 2">
                                                <img src="https://cdn-icons-png.flaticon.com/512/219/219986.png" class="avatar-option" onclick="selectAvatar(this.src)" alt="Avatar 3">
                                                <img src="https://cdn-icons-png.flaticon.com/512/147/147144.png" class="avatar-option" onclick="selectAvatar(this.src)" alt="Avatar 4">
                                                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="avatar-option" onclick="selectAvatar(this.src)" alt="Avatar 5">
                                                <img src="https://cdn-icons-png.flaticon.com/512/4333/4333609.png" class="avatar-option" onclick="selectAvatar(this.src)" alt="Avatar 6">
                                            </div>
                                        </div>
                                        
                                        <div class="text-end mt-3">
                                            <button type="submit" name="update_avatar_url" class="btn btn-success">
                                                <i class="fas fa-save me-1"></i> Update Avatar URL
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="admin-section">
                                    <h5><i class="fas fa-users-cog me-2"></i>Admin Privileges</h5>
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            User Management
                                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            System Settings
                                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Reports Access
                                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Financial Data
                                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- Regular User Card -->
                            <div class="profile-card">
                                <div class="profile-card-header">
                                    <i class="fas fa-info-circle"></i>
                                    <h4>Account Status</h4>
                                </div>
                                
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="fas fa-user-check text-success" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5>Your account is active</h5>
                                    <p class="text-muted">You have access to all standard user features</p>
                                </div>
                                
                                <div class="divider"></div>
                                
                                <div class="user-info-item">
                                    <div class="user-info-label">Account Type:</div>
                                    <div class="user-info-value">
                                        <span class="info-badge info-badge-primary">
                                            <?= htmlspecialchars(ucfirst($user['role'] ?? 'User')) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="user-info-item">
                                    <div class="user-info-label">Status:</div>
                                    <div class="user-info-value">
                                        <span class="info-badge info-badge-success">Active</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Help Card -->
                            <div class="profile-card">
                                <div class="profile-card-header">
                                    <i class="fas fa-question-circle"></i>
                                    <h4>Need Help?</h4>
                                </div>
                                
                                <p>If you need assistance with your account or have questions, please contact your administrator.</p>
                                
                                <div class="d-grid gap-2">
                                    <a href="mailto:admin@example.com" class="btn btn-outline-primary">
                                        <i class="fas fa-envelope me-2"></i> Contact Admin
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle avatar upload
        const avatarContainer = document.querySelector('.avatar-container');
        const avatarOverlay = document.querySelector('.avatar-overlay');
        const avatarUpload = document.getElementById('avatar-upload');
        const removeAvatarBtn = document.getElementById('remove-avatar-btn');
        const removeAvatarInput = document.getElementById('remove_avatar');
        
        if (avatarContainer && avatarOverlay && avatarUpload) {
            avatarOverlay.addEventListener('click', function() {
                avatarUpload.click();
            });
            
            avatarUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let img = avatarContainer.querySelector('img');
                        if (!img) {
                            img = document.createElement('img');
                            avatarContainer.innerHTML = '';
                            avatarContainer.appendChild(img);
                            avatarContainer.appendChild(avatarOverlay);
                        }
                        img.src = e.target.result;
                        
                        // Reset remove avatar flag if user uploads new avatar
                        removeAvatarInput.value = '0';
                    }
                    reader.readAsDataURL(this.files[0]);
                    
                    // Automatically submit the form when file is selected
                    const form = avatarUpload.closest('form');
                    form.submit();
                }
            });
        }
        
        // Remove avatar
        if (removeAvatarBtn) {
            removeAvatarBtn.addEventListener('click', function() {
                const avatarContainer = document.querySelector('.avatar-container');
                const img = avatarContainer.querySelector('img');
                if (img) {
                    img.remove();
                    const firstInitial = document.getElementById('full_name').value.charAt(0) || 'U';
                    avatarContainer.innerHTML = firstInitial;
                    avatarContainer.appendChild(document.querySelector('.avatar-overlay'));
                    
                    // Set the flag to remove avatar in the database
                    removeAvatarInput.value = '1';
                    
                    // Submit the form
                    const form = removeAvatarBtn.closest('form');
                    form.submit();
                }
            });
        }
        
        // Preview avatar from URL (for admin users)
        window.previewAvatar = function(url) {
            const previewImg = document.getElementById('avatar-preview-img');
            if (previewImg) {
                if (url) {
                    previewImg.src = url;
                } else {
                    previewImg.src = 'img/user-icon.png';
                }
            }
        };
        
        // Select predefined avatar
        window.selectAvatar = function(src) {
            const avatarUrlInput = document.getElementById('avatar_url');
            const previewImg = document.getElementById('avatar-preview-img');
            
            if (avatarUrlInput && previewImg) {
                avatarUrlInput.value = src;
                previewImg.src = src;
                
                // Update selected state
                document.querySelectorAll('.avatar-option').forEach(option => {
                    option.classList.remove('selected');
                    if (option.src === src) {
                        option.classList.add('selected');
                    }
                });
            }
        };
    });
    </script>
</body>
</html>
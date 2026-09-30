<?php
include_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=settings.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user data
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = !empty(trim($_POST['phone'] ?? '')) ? trim($_POST['phone']) : null;
    $address = !empty(trim($_POST['address'] ?? '')) ? trim($_POST['address']) : null;
    $timezone = $_POST['timezone'] ?? 'UTC';
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error_message = "Full name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email is already taken by another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Email address is already taken.";
        } else {
            // Update user profile
            $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, timezone = ? WHERE id = ?");
            $update_stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $timezone, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                $_SESSION['email'] = $email;
                $_SESSION['full_name'] = $full_name;
                
                // Refresh user data
                $user_data['full_name'] = $full_name;
                $user_data['email'] = $email;
                $user_data['phone'] = $phone;
                $user_data['address'] = $address;
                $user_data['timezone'] = $timezone;
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        if (password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password. Please try again.";
            }
            $update_stmt->close();
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/avatars/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Delete old avatar if exists (with error suppression)
                if (!empty($user_data['profile_image']) && file_exists($user_data['profile_image'])) {
                    @unlink($user_data['profile_image']);
                }
                
                // Update database
                $update_stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $update_stmt->bind_param("si", $upload_path, $user_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Profile image updated successfully!";
                    $user_data['profile_image'] = $upload_path;
                } else {
                    $error_message = "Error updating profile image.";
                }
                $update_stmt->close();
            } else {
                $error_message = "Error uploading file.";
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        $error_message = "Please select a valid image file.";
    }
}

// Helper function to safely display values, handling null inputs
function safe_value($value, $default = '') {
    return $value !== null ? htmlspecialchars($value) : $default;
}

// Helper function for selected options
function is_selected($value, $current, $default = 'UTC') {
    $current = $current ?? $default;
    return $value === $current ? 'selected' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QUOLYWHEELS - Settings</title>
    <link rel="icon" href="img/quolywheels logo.png" type="image/png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .header-section {
            background: linear-gradient(135deg, #d90429, #c82333);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #d90429;
            box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #d90429, #c82333);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 4, 41, 0.4);
        }

        .avatar-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .current-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #d90429;
            margin-bottom: 1rem;
        }

        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #dee2e6;
            border: 4px solid #d90429;
            margin: 0 auto 1rem;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            background: #d90429;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            background: #c82333;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: 0;
            border-bottom: 3px solid transparent;
        }

        .nav-tabs .nav-link.active {
            color: #d90429;
            background: none;
            border-bottom-color: #d90429;
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #dc3545; width: 25%; }
        .strength-fair { background: #ffc107; width: 50%; }
        .strength-good { background: #17a2b8; width: 75%; }
        .strength-strong { background: #28a745; width: 100%; }

        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Account Settings</h2>
                    <p class="mb-0 opacity-75">Manage your account preferences and security</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Navigation -->
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                    <i class="fas fa-user me-2"></i>Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                    <i class="fas fa-shield-alt me-2"></i>Security
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="avatar-tab" data-bs-toggle="tab" data-bs-target="#avatar" type="button" role="tab">
                    <i class="fas fa-image me-2"></i>Avatar
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabContent">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-user me-2"></i>Profile Information
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?= safe_value($user_data['full_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= safe_value($user_data['email']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?= safe_value($user_data['phone']) ?>" 
                                               placeholder="Enter your phone number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="timezone" class="form-label">Timezone</label>
                                         <select class="form-control" id="timezone" name="timezone" required>
                                            <option value="UTC" <?= is_selected('UTC', $user_data['timezone']) ?>>UTC</option>
                                            <option value="America/New_York" <?= is_selected('America/New_York', $user_data['timezone']) ?>>Eastern Time</option>
                                            <option value="America/Chicago" <?= is_selected('America/Chicago', $user_data['timezone']) ?>>Central Time</option>
                                            <option value="America/Denver" <?= is_selected('America/Denver', $user_data['timezone']) ?>>Mountain Time</option>
                                            <option value="America/Los_Angeles" <?= is_selected('America/Los_Angeles', $user_data['timezone']) ?>>Pacific Time</option>
                                            <option value="Europe/London" <?= is_selected('Europe/London', $user_data['timezone']) ?>>London</option>
                                            <option value="Europe/Paris" <?= is_selected('Europe/Paris', $user_data['timezone']) ?>>Paris</option>
                                            <option value="Europe/Tirane" <?= is_selected('Europe/Tirane', $user_data['timezone']) ?>>Tirana</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" 
                                          placeholder="Enter your address"><?= safe_value($user_data['address']) ?></textarea>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-shield-alt me-2"></i>Change Password
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" 
                                       placeholder="Enter your current password" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       placeholder="Enter your new password" required>
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <small class="text-muted" id="strengthText">Password strength will appear here</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm your new password" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Avatar Tab -->
            <div class="tab-pane fade" id="avatar" role="tabpanel">
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-image me-2"></i>Profile Picture
                    </div>
                    <div class="card-body">
                        <div class="avatar-section">
                            <?php if (!empty($user_data['profile_image']) && file_exists($user_data['profile_image'])): ?>
                                <img src="<?= safe_value($user_data['profile_image']) ?>" alt="Current Avatar" class="current-avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="file-input-wrapper">
                                    <input type="file" class="file-input" id="profile_image" name="profile_image" accept="image/*">
                                    <label for="profile_image" class="file-input-label">
                                        <i class="fas fa-camera me-2"></i>Choose New Photo
                                    </label>
                                </div>
                                <br><br>
                                <button type="submit" name="update_avatar" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Upload Photo
                                </button>
                            </form>
                            
                            <p class="text-muted mt-3">
                                <small>Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let text = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthFill.className = 'strength-fill';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthFill.classList.add('strength-weak');
                    text = 'Weak';
                    break;
                case 2:
                    strengthFill.classList.add('strength-fair');
                    text = 'Fair';
                    break;
                case 3:
                case 4:
                    strengthFill.classList.add('strength-good');
                    text = 'Good';
                    break;
                case 5:
                    strengthFill.classList.add('strength-strong');
                    text = 'Strong';
                    break;
            }
            
            strengthText.textContent = text;
        });

        // Password confirmation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.style.borderColor = '#dc3545';
            } else if (confirm) {
                this.style.borderColor = '#28a745';
            }
        });

        // File input preview
        document.getElementById('profile_image').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.querySelector('.current-avatar, .avatar-placeholder');
                    if (avatar.tagName === 'IMG') {
                        avatar.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'current-avatar';
                        avatar.parentNode.replaceChild(img, avatar);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

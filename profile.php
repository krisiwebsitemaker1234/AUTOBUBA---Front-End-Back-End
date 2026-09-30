<?php
include_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: login.php");
    exit();
}

// Handle profile update
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validate input
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Check if email is already taken by another user
    $email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $email_check->bind_param("si", $email, $_SESSION['user_id']);
    $email_check->execute();
    if ($email_check->get_result()->num_rows > 0) {
        $errors[] = "Email is already taken";
    }
    $email_check->close();
    
    if (empty($errors)) {
        // Update user profile
        $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
            $message_type = "success";
            // Refresh user data
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
        } else {
            $message = "Error updating profile. Please try again.";
            $message_type = "error";
        }
        $update_stmt->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}

// Prepare data for display (sanitize all outputs)
$username = htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');
$fullName = htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$address = htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($user['role'] ?? 'Member', ENT_QUOTES, 'UTF-8');
$memberSince = htmlspecialchars($user['created_at'] ?? 'Not available', ENT_QUOTES, 'UTF-8');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QUOLYWHEELS - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #6c757d;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), #c82333);
            color: white;
            padding: 2rem 0;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: -50px;
            position: relative;
            z-index: 1;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar d-flex align-items-center justify-content-center bg-white bg-opacity-25">
                            <i class="fa-solid fa-user fa-3x text-white"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h1 class="mb-1"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h1>
                    <p class="mb-0 opacity-75"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="badge bg-light text-dark mt-2"><?= ucfirst($user['role']) ?></span>
                </div>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-light">
                        <i class="fa-solid fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card profile-card">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4">
                        <i class="fa-solid fa-user-edit me-2 text-primary"></i>Edit Profile
                    </h3>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $message_type == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                            <i class="fa-solid fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" value="<?= htmlspecialchars(ucfirst($user['role'] ?? 'Member')) ?>" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="created_at" class="form-label">Member Since</label>
                                <input type="text" class="form-control" id="created_at" 
                                       value="<?= htmlspecialchars(date('F j, Y', strtotime($user['created_at'] ?? 'Not available'))) ?>" disabled>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
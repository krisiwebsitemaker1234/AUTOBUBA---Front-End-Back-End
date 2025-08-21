<?php
include_once 'config.php';

// Redirect logged in users
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';
$success_message = '';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid request. Please try again.";
    } else {
        $username_email = trim($_POST['username_email']);
        $password = $_POST['password'];

        if (empty($username_email) || empty($password)) {
            $error_message = "Please fill in all fields.";
        } else {
            // Query without email verification check
            $stmt = $conn->prepare("SELECT id, username, email, password, full_name, status, role FROM users WHERE (username = ? OR email = ?)");
            $stmt->bind_param("ss", $username_email, $username_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Allow admins to login regardless of status, check status for regular users
                if ($user['role'] !== 'admin' && $user['status'] !== 'active') {
                    $error_message = "Your account is not active. Please contact support.";
                } elseif (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];

                    // Handle remember me
                    if (isset($_POST['remember_me'])) {
                        $remember_token = bin2hex(random_bytes(32));
                        $remember_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $remember_stmt->bind_param("si", $remember_token, $user['id']);
                        $remember_stmt->execute();
                        $remember_stmt->close();
                        
                        // Set cookie for 30 days
                        setcookie('remember_token', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    }

                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();

                    header("Location: index.php");
                    exit();
                } else {
                    sleep(1);
                    $error_message = "Invalid username/email or password.";
                }
            } else {
                sleep(1);
                $error_message = "Invalid username/email or password.";
            }
            $stmt->close();
        }
    }
}

// Handle register POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid request. Please try again.";
    } else {
        $username = trim($_POST['reg_username']);
        $email = trim($_POST['reg_email']);
        $full_name = trim($_POST['reg_full_name']);
        $password = $_POST['reg_password'];
        $confirm_password = $_POST['reg_confirm_password'];
        $phone = trim($_POST['reg_phone']);

        if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
            $error_message = "Please fill in all required fields.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error_message = "Password must be at least 8 characters and contain a capital letter and a number.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } elseif (!preg_match('/@gmail\.com$/i', $email)) {
            $error_message = "Only Gmail addresses (@gmail.com) are allowed for registration.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $error_message = "Username must be 3-20 characters and contain only letters, numbers, and underscores.";
        } else {
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error_message = "Username or email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user without email verification requirements
                $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'customer', 'active', NOW())");
                $insert_stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);

                if ($insert_stmt->execute()) {
                    $success_message = "Registration successful! You can now login with your credentials.";
                } else {
                    $error_message = "Registration failed. Please try again.";
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUTOBUBA - Login</title>
    <link rel="icon" href="/placeholder.svg?height=32&width=32" type="image/svg+xml">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 35%, #ffffff 65%, #f8f9fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .login-container {
            background: white;
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(220, 53, 69, 0.15),
                0 10px 25px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            overflow: hidden;
            max-width: 950px;
            width: 100%;
            min-height: 500px;
            display: flex;
            border: 2px solid rgba(220, 53, 69, 0.1);
        }

        .login-left {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 50%, #b02a37 100%);
            color: white;
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-left::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px),
                radial-gradient(circle, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 50px 50px, 25px 25px;
            animation: float 25s infinite linear;
            z-index: 1;
        }

        @keyframes float {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-50px) translateY(-50px); }
        }

        .login-left .content {
            position: relative;
            z-index: 2;
        }

        .login-left h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .login-left p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            line-height: 1.6;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .car-icon {
            font-size: 4.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .features {
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .feature-item i {
            color: #ffffff;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .login-right {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(to bottom, #ffffff 0%, #fefefe 100%);
        }

        .form-container {
            max-width: 420px;
            margin: 0 auto;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo img {
            max-height: 70px;
            filter: drop-shadow(0 2px 4px rgba(220, 53, 69, 0.2));
        }

        .form-tabs {
            display: flex;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 6px;
            border: 2px solid rgba(220, 53, 69, 0.1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .tab-btn {
            flex: 1;
            padding: 14px;
            background: none;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            font-size: 1rem;
            color: #6c757d;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 
                0 4px 15px rgba(220, 53, 69, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .form-group {
            margin-bottom: 1.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus {
            outline: none;
            border-color: #dc3545;
            background: white;
            box-shadow: 
                0 0 0 4px rgba(220, 53, 69, 0.1),
                inset 0 1px 3px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            padding-left: 48px;
        }

        .input-group .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #dc3545;
            font-size: 1.1rem;
            z-index: 2;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 50%, #b02a37 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 1rem;
            box-shadow: 
                0 4px 15px rgba(220, 53, 69, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 8px 25px rgba(220, 53, 69, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .form-check-input {
            margin-right: 0.7rem;
            accent-color: #dc3545;
            transform: scale(1.1);
        }

        .form-check-label {
            font-size: 0.95rem;
            color: #555;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: none;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee 0%, #fdd 100%);
            color: #c82333;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: linear-gradient(135deg, #efe 0%, #dfd 100%);
            color: #28a745;
            border-left: 4px solid #28a745;
        }

        .back-home {
            position: absolute;
            top: 25px;
            left: 25px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 30px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 500;
            z-index: 3;
        }

        .back-home:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-tab {
            display: none;
        }

        .form-tab.active {
            display: block;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 10px;
                border-radius: 20px;
            }

            .login-left {
                padding: 2.5rem 2rem;
                min-height: 250px;
            }

            .login-left h2 {
                font-size: 2.2rem;
            }

            .login-right {
                padding: 2.5rem 2rem;
            }

            .car-icon {
                font-size: 3.5rem;
                margin-bottom: 1.5rem;
            }

            .form-container {
                max-width: 100%;
            }

            .back-home {
                top: 15px;
                left: 15px;
                padding: 10px 16px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side -->
        <div class="login-left">
            <a href="index.php" class="back-home">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
            
            <div class="content">
                <div class="car-icon">
                    <img src="img/image.jpg" alt="Car Icon" class="img-fluid">
                </div>
                <h2>Welcome to AUTOBUBA</h2>
                <p>Your trusted partner in finding the perfect vehicle. Join thousands of satisfied customers who have found their dream cars with us.</p>
                
                <div class="features">
                    <div class="feature-item mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        Premium Vehicle Selection
                    </div>
                    <div class="feature-item mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        Trusted by Thousands
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle me-2"></i>
                        24/7 Customer Support
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side -->
        <div class="login-right">
            <div class="form-container">
                <div class="logo">
                    <img src="img/logo zoom.png" alt="AUTOBUBA" class="img-fluid">
                </div>

                <!-- Form Tabs -->
                <div class="form-tabs">
                    <button type="button" class="tab-btn active" onclick="switchTab('login')">Login</button>
                    <button type="button" class="tab-btn" onclick="switchTab('register')">Register</button>
                </div>

                <!-- Error/Success Messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <div id="login-form" class="form-tab active">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label for="username_email">Username or Email</label>
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" class="form-control" id="username_email" name="username_email" 
                                       placeholder="Enter your username or email" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Remember me for 30 days
                            </label>
                        </div>

                        <button type="submit" name="login" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login to Your Account
                        </button>
                    </form>
                </div>

                <!-- Register Form -->
                <div id="register-form" class="form-tab">
                    <form method="POST" action="" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label for="reg_full_name">Full Name *</label>
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" class="form-control" id="reg_full_name" name="reg_full_name" 
                                       placeholder="Enter your full name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg_username">Username *</label>
                            <div class="input-group">
                                <i class="fas fa-at input-icon"></i>
                                <input type="text" class="form-control" id="reg_username" name="reg_username" 
                                       placeholder="Choose a username" required pattern="[a-zA-Z0-9_]{3,20}">
                            </div>
                            <small class="form-text text-muted">3-20 characters, letters, numbers, and underscores only.</small>
                        </div>

                        <div class="form-group">
                            <label for="reg_email">Gmail Address *</label>
                            <div class="input-group">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="form-control" id="reg_email" name="reg_email" 
                                       placeholder="yourname@gmail.com" required pattern=".*@gmail\.com$">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg_phone">Phone Number</label>
                            <div class="input-group">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" class="form-control" id="reg_phone" name="reg_phone" 
                                       placeholder="Enter your phone number">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg_password">Password *</label>
                            <div class="input-group">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control" id="reg_password" name="reg_password" 
                                       placeholder="Create a password" required minlength="8">
                                <button type="button" class="password-toggle" onclick="togglePassword('reg_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">8+ characters with uppercase letter and number</small>
                        </div>

                        <div class="form-group">
                            <label for="reg_confirm_password">Confirm Password *</label>
                            <div class="input-group">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control" id="reg_confirm_password" name="reg_confirm_password" 
                                       placeholder="Confirm your password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('reg_confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="register" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>
                            Create Your Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function switchTab(tab) {
            // Remove active class from all tabs and forms
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-tab').forEach(form => form.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding form
            if (tab === 'login') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
            }
            document.getElementById(tab + '-form').classList.add('active');
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Enhanced form validation
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.getElementById('registerForm');
            const password = document.getElementById('reg_password');
            const confirmPassword = document.getElementById('reg_confirm_password');
            const email = document.getElementById('reg_email');

            // Gmail validation
            email.addEventListener('input', function() {
                if (this.value && !this.value.endsWith('@gmail.com')) {
                    this.setCustomValidity('Please enter a Gmail address (@gmail.com)');
                    this.style.borderColor = '#dc3545';
                } else {
                    this.setCustomValidity('');
                    this.style.borderColor = '#28a745';
                }
            });

            // Password strength validation
            password.addEventListener('input', function() {
                const value = this.value;
                const hasUpper = /[A-Z]/.test(value);
                const hasNumber = /[0-9]/.test(value);
                const hasLength = value.length >= 8;

                if (hasUpper && hasNumber && hasLength) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#dc3545';
                }
            });

            // Password confirmation
            confirmPassword.addEventListener('input', function() {
                if (this.value !== password.value) {
                    this.setCustomValidity('Passwords do not match');
                    this.style.borderColor = '#dc3545';
                } else {
                    this.setCustomValidity('');
                    this.style.borderColor = '#28a745';
                }
            });

            // Form submission validation
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    if (password.value !== confirmPassword.value) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        confirmPassword.focus();
                        return false;
                    }
                    
                    if (!email.value.endsWith('@gmail.com')) {
                        e.preventDefault();
                        alert('Please enter a valid Gmail address!');
                        email.focus();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
include_once 'config.php';

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $about_yourself = trim($_POST['about_yourself'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($position)) {
        $errors[] = "Position is required";
    }
    
    if (empty($about_yourself)) {
        $errors[] = "Please tell us about yourself";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_email_stmt = $conn->prepare("SELECT id FROM job_applications WHERE email = ?");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $result = $check_email_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "An application with this email already exists";
        }
        $check_email_stmt->close();
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO job_applications (full_name, email, position, about_yourself) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $position, $about_yourself);
        
        if ($stmt->execute()) {
            $success_message = "Your application has been submitted successfully! We will contact you soon.";
            // Clear form data
            $full_name = $email = $position = $about_yourself = '';
        } else {
            $error_message = "Error submitting application. Please try again.";
        }
        $stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get user data if logged in
$user_data = null;
$notifications = ['orders' => 0, 'bookings' => 0, 'messages' => 0];

if (isset($_SESSION['user_id'])) {
    $user_stmt = $conn->prepare("SELECT full_name, email, profile_image, role FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_result) {
        $user_data = $user_result->fetch_assoc();
    }
    $user_stmt->close();

    // Get notification counts
    $orders_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status != 'cancelled'");
    $orders_stmt->bind_param("i", $_SESSION['user_id']);
    $orders_stmt->execute();
    $orders_count = $orders_stmt->get_result()->fetch_assoc()['count'];
    $orders_stmt->close();

    $bookings_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status != 'cancelled'");
    $bookings_stmt->bind_param("i", $_SESSION['user_id']);
    $bookings_stmt->execute();
    $bookings_count = $bookings_stmt->get_result()->fetch_assoc()['count'];
    $bookings_stmt->close();

    $notifications = ['orders' => $orders_count, 'bookings' => $bookings_count, 'messages' => 0];
}
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <link rel="stylesheet" href="css/cart.css">
    <title>AUTOBUBA - Work With Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Your existing CSS styles here */
        /* Global Styles */
:root {
    --primary-color: #dc3545;
    --secondary-color: #2d3436;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --gray-color: #6c757d;
    --white-color: #ffffff;
    --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

body {
    font-family: 'Poppins', sans-serif;
    color: var(--dark-color);
    background-color: #f5f5f5;
}

/* Alert Styles */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 8px;
    font-weight: 500;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-dismissible .close {
    position: relative;
    top: -2px;
    right: -21px;
    color: inherit;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Form Validation Styles */
.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control.is-valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* Loading Button State */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    margin: auto;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: button-loading-spinner 1s ease infinite;
}

@keyframes button-loading-spinner {
    from {
        transform: rotate(0turn);
    }
    to {
        transform: rotate(1turn);
    }
}

/* Rest of your existing CSS... */
a {
    text-decoration: none;
    color: inherit;
    transition: var(--transition);
}

a:hover {
    color: var(--primary-color);
}

.btn {
    transition: var(--transition);
    font-weight: 500;
    border-radius: 4px;
}

.btn-danger {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
}

.btn-outline-danger {
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-danger:hover {
    background-color: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
}

/* Header Styles */
.header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background-color: var(--white-color);
    box-shadow: var(--shadow);
}

.navbar {
    padding: 15px 0;
}

.navbar-brand img {
    max-height: 60px;
}

.nav-link {
    position: relative;
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    color: var(--dark-color) !important;
}

.nav-link:hover {
    color: var(--primary-color) !important;
}

.nav-link.underline::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 1rem;
    right: 1rem;
    height: 3px;
    background-color: var(--primary-color);
}

.badge {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
    border-radius: 50%;
}

/* Language Dropdown Styles */
.header .language-dropdown {
    margin-right: 15px;
}

.header .language-dropdown .btn-outline-danger {
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-width: 2px;
    display: flex;
    align-items: center;
    background: linear-gradient(to right, rgba(220, 53, 69, 0.05), rgba(220, 53, 69, 0));
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.header .language-dropdown .btn-outline-danger:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 12px rgba(220, 53, 69, 0.2);
    background: linear-gradient(to right, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.02));
}

.header .language-dropdown .btn-outline-danger .fa-globe {
    font-size: 1.1rem;
    margin-right: 8px;
    color: var(--primary-color);
    transition: transform 0.3s ease;
}

.header .language-dropdown .btn-outline-danger:hover .fa-globe {
    transform: rotate(15deg);
}

.header .language-dropdown .dropdown-toggle::after {
    margin-left: 8px;
    vertical-align: middle;
    transition: transform 0.3s ease;
}


.header .language-dropdown .dropdown-menu {
    min-width: auto;
    width: auto;
    border-radius: 10px;
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    padding: 0.5rem;
    margin-top: 8px;
    overflow: hidden;
    background: white;
}
.breadcrumb-option{
    background-image: url(img/counter-bg.jpg);
}


.header .language-dropdown .dropdown-menu::before {
    content: '';
    position: absolute;
    top: -6px;
    left: 20px;
    width: 12px;
    height: 12px;
    background: white;
    box-shadow: -3px -3px 5px rgba(0, 0, 0, 0.04);
}

.header .language-dropdown .dropdown-item {
    padding: 0.5rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
    margin-bottom: 0.25rem;
}

.header .language-dropdown .dropdown-item:last-child {
    margin-bottom: 0;
}

.header .language-dropdown .dropdown-item:hover {
    background-color: #f8f9fa;
}

.header .language-dropdown .dropdown-item.active {
    background-color: rgba(220, 53, 69, 0.1);
}

/* Header Button Styles */
.header-buttons {
    display: flex;
    align-items: center;
}

.header-buttons .nav-link {
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 40px;
    height: 40px;
}

#cart-count {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
    color: white;
}

        /* Enhanced User Dropdown Styles */
        .user-dropdown {
            position: relative;
        }

        .user-dropdown .dropdown-menu {
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border-radius: 15px;
            padding: 0;
            min-width: 320px;
            overflow: hidden;
            margin-top: 10px;
        }

        .user-dropdown .dropdown-header {
            background: linear-gradient(135deg, #d90429, #c82333);
            color: white;
            padding: 1.5rem;
            border-radius: 0;
            margin: 0;
            border: none;
        }

        .user-dropdown .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-dropdown .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .user-dropdown .user-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .user-dropdown .user-details h6 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .user-dropdown .user-details small {
            opacity: 0.8;
            font-size: 0.85rem;
        }

        .user-dropdown .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #333;
            text-decoration: none;
        }

        .user-dropdown .dropdown-item:hover {
            background-color: #f8f9fa;
            border-left-color: #d90429;
            transform: translateX(5px);
            color: #d90429;
        }

        .user-dropdown .dropdown-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .user-dropdown .dropdown-item.text-danger:hover {
            background-color: #fff5f5;
            color: #dc3545;
        }

        .user-dropdown .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .user-dropdown .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e9ecef;
        }

        /* User Button Styles */
        .user-btn {
            background: linear-gradient(135deg, #d90429, #c82333);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
        }

        .user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 4, 41, 0.3);
            color: white;
        }

        .user-btn img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

/* Contact Section Styles */
.contact {
    padding: 80px 0;
}

.contact__text .section-title h2 {
    color: var(--dark-color);
    margin-bottom: 1.5rem;
    font-weight: 700;
}

.contact__text .section-title p {
    color: var(--gray-color);
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.contact__text ul {
    list-style: none;
    padding: 0;
}

.contact__text ul li {
    padding: 0.5rem 0;
    color: var(--gray-color);
    font-weight: 500;
}

.contact__text ul li span {
    color: var(--primary-color);
    font-weight: 600;
}

.contact__form {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.contact__form .form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    transition: var(--transition);
}

.contact__form .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    outline: none;
}

.contact__form textarea {
    min-height: 120px;
    resize: vertical;
}

.site-btn {
    background: linear-gradient(135deg, #d90429, #c82333);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: 600;
    transition: var(--transition);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.site-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(217, 4, 41, 0.3);
    color: white;
}

/* Breadcrumb Styles */
.breadcrumb-option {
    padding: 120px 0;
    background-size: cover;
    background-position: center;
    position: relative;
    z-index: 1;
}

.breadcrumb-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: -1;
}

.breadcrumb__text h2 {
    font-size: 48px;
    color: var(--white-color);
    font-weight: 700;
    margin-bottom: 15px;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.breadcrumb__links {
    display: flex;
    justify-content: center;
}

.breadcrumb__links a,
.breadcrumb__links span {
    font-size: 16px;
    color: var(--white-color);
    margin-right: 26px;
    position: relative;
    display: inline-block;
}

.breadcrumb__links a:after {
    position: absolute;
    right: -16px;
    top: 0;
    content: "|";
    color: var(--white-color);
}

/* Footer Styles */
.footer {
    background-size: cover;
    background-position: center;
    padding: 60px 0 20px;
    position: relative;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: -1;
}

.footer__about .footer__logo img {
    max-height: 60px;
    margin-bottom: 1.5rem;
}

.footer__contact-box p {
    color: white;
    margin-bottom: 0.5rem;
}

.footer__contact-box i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.footer__social {
    margin-top: 1rem;
}

.social-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 0.5rem;
    transition: var(--transition);
    text-decoration: none;
}

.social-btn.tiktok {
    background: #000;
    color: white;
}

.social-btn.instagram {
    background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
    color: white;
}

.social-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.footer__widget h5 {
    color: white;
    margin-bottom: 1rem;
    font-weight: 600;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: #ccc;
    transition: var(--transition);
}

.footer-links a:hover {
    color: var(--primary-color);
    transform: translateX(5px);
}

.footer-links i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.footer__bottom {
    border-top: 1px solid #444;
    margin-top: 2rem;
    padding-top: 1rem;
}

.copyright {
    color: #ccc;
    margin: 0;
    font-size: 0.9rem;
}

/* Cart Styles */
.cart-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: 350px;
    max-width: 100%;
    height: 100%;
    background-color: white;
    z-index: 1001;
    box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
    padding: 20px;
    overflow-y: auto;
    display: none;
    animation: slideIn 0.3s ease;
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e1e1e1;
}

.close-cart {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--gray-color);
    transition: color 0.3s ease;
}

.close-cart:hover {
    color: var(--primary-color);
}

.cart-body {
    margin-top: 20px;
}

.selected-car-item {
    display: flex;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e1e1;
}

.selected-car-img {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    margin-right: 15px;
}

.selected-car-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.selected-car-info {
    flex-grow: 1;
}

.selected-car-name {
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 16px;
}

.selected-car-price {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 18px;
}

.selected-car-remove {
    background: none;
    border: none;
    color: var(--gray-color);
    cursor: pointer;
    transition: color 0.3s ease;
}

.selected-car-remove:hover {
    color: var(--primary-color);
}

.empty-cart {
    text-align: center;
    padding: 40px 0;
}

.empty-cart i {
    font-size: 50px;
    color: #e1e1e1;
    margin-bottom: 15px;
}

.empty-cart p {
    color: var(--gray-color);
}

.cart-sidebar.show {
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .breadcrumb-option {
        padding: 80px 0;
    }
    
    .breadcrumb__text h2 {
        font-size: 36px;
    }
    
    .contact {
        padding: 60px 0;
    }
    
    .contact__form {
        margin-top: 2rem;
    }
}

@media (max-width: 575px) {
    .breadcrumb__text h2 {
        font-size: 28px;
    }
    
    .contact__form {
        padding: 1.5rem;
    }
}
    </style>
    
    <meta charset="UTF-8">
    <meta name="description" content="HVAC Template">
    <meta name="keywords" content="HVAC, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://kit.fontawesome.com/dfba5e2447.js" crossorigin="anonymous"></script>
    <title>AUTOBUBA - Work</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="css/magnific-popup.css" type="text/css">
    <link rel="stylesheet" href="css/jquery-ui.min.css" type="text/css">
    <link rel="stylesheet" href="css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="css/style.css" type="text/css">
    <link rel="stylesheet" href="loading.css">
    <script src="https://kit.fontawesome.com/dfba5e2447.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/navbar.css" type="text/css">
    <!-- Flag Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">

    
</head>

<body>

  
    <!-- Loading Screen -->
  <div id="loading-screen">
    <div class="loading-container">
      <!-- Speedometer -->
      <div class="speedometer-container">
        <div class="speedometer">
          <div class="speedometer-scale">
            <div class="scale-mark mark-0"><span>0</span></div>
            <div class="scale-mark mark-20"><span>20</span></div>
            <div class="scale-mark mark-40"><span>40</span></div>
            <div class="scale-mark mark-60"><span>60</span></div>
            <div class="scale-mark mark-80"><span>80</span></div>
            <div class="scale-mark mark-100"><span>100</span></div>
          </div>
          <div class="needle-container">
            <div class="needle"></div>
            <div class="needle-center"></div>
          </div>
          <div class="speed-display">
            <span id="speed-value">0</span>
            <span class="speed-unit">km/h</span>
          </div>
        </div>
      </div>
      
      <!-- Loading Text -->
      <div class="loading-text">
        <span>L</span>
        <span>O</span>
        <span>A</span>
        <span>D</span>
        <span>I</span>
        <span>N</span>
        <span>G</span>
        <span>.</span>
        <span>.</span>
        <span>.</span>
      </div>
      
      <!-- Progress Bar -->
      <div class="progress-container">
        <div id="progress-bar" class="progress-bar"></div>
      </div>
      
      <!-- AUTOBUBA Text -->
      <div class="autobuba-text">AUTOBUBA</div>
    </div>
  </div>

    <!-- Header Section Begin -->
<header class="header">
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="./index.php">
                <img src="img/logo zoom.png" alt="AUTOBUBA" class="img-fluid" style="max-height: 60px;">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link text-black text-decoration-none mx-4" href="car.php"
                            data-translate="Cars">Cars</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-black text-decoration-none mx-4" href="blog.php"
                            data-translate="Blog">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-black text-decoration-none mx-4" href="about.php"
                            data-translate="About">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-black text-decoration-none mx-4 underline" href="work.php"
                            data-translate="Work">Work</a>
                    </li>
                </ul>

                <div class="language-dropdown mr-4">
                    <div class="dropdown">
                        <button class="btn btn-outline-danger dropdown-toggle" type="button" id="languageDropdown" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="flag-icon flag-icon-gb"></span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item language-option active" href="#" data-lang="en">
                                <span class="flag-icon flag-icon-gb"></span>
                            </a></li>
                            <li><a class="dropdown-item language-option" href="#" data-lang="sq">
                                <span class="flag-icon flag-icon-al"></span>
                            </a></li>
                        </ul>
                    </div>
                </div>

                <div class="header-buttons d-flex align-items-center">
                    <a href="book-test-drive.php" class="nav-link mr-2 d-flex align-items-center">
                        <i class="fa-solid fa-car-side"></i>
                    </a>
                    <a id="cart" href="#" class="nav-link mr-2 position-relative d-flex align-items-center">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="badge bg-danger position-absolute" id="cart-count"></span>
                    </a>
                    <a href="report.php" class="nav-link mr-3 d-flex align-items-center" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" title="Report a problem">
                        <i class="fa-solid fa-flag"></i>
                    </a>
                    
                    <!-- Enhanced User Dropdown -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            $user_name = $user_data['full_name'] ?: $_SESSION['username'];
                            $user_email = $user_data['email'];
                            $user_avatar = $user_data['profile_image'];
                            $user_role = $user_data['role'];
                            ?>
                            
                            <div class="user-dropdown">
                                <div class="dropdown">
                                    <button class="user-btn dropdown-toggle" type="button" id="userDropdown" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <?php if ($user_avatar): ?>
                                            <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar">
                                        <?php else: ?>
                                            <i class="fa-solid fa-user-circle fs-5"></i>
                                        <?php endif; ?>
                                        <span class="d-none d-md-inline"><?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span>
                                    </button>
                                    
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                        <!-- User Header -->
                                        <li class="dropdown-header">
                                            <div class="user-info">
                                                <?php if ($user_avatar): ?>
                                                    <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" class="user-avatar">
                                                <?php else: ?>
                                                    <div class="user-avatar-placeholder">
                                                        <i class="fa-solid fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="user-details">
                                                    <h6><?= htmlspecialchars($user_name) ?></h6>
                                                    <small><?= htmlspecialchars($user_email) ?></small>
                                                    <br>
                                                    <small class="badge rounded-4 bg-light text-dark mt-2 mr-2"><?= ucfirst($user_role) ?></small>
                                                </div>
                                            </div>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- User Menu Items -->
                                        <li>
                                            <a class="dropdown-item" href="profile.php">
                                                <i class="fa-solid fa-user"></i>
                                                <span>My Profile</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="bookings.php">
                                                <i class="fa-solid fa-calendar-check"></i>
                                                <span>My Bookings</span>
                                                <?php if ($notifications['bookings'] > 0): ?>
                                                    <span class="notification-badge"><?= $notifications['bookings'] ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                        
                                        <?php if (in_array($user_role, ['admin', 'manager', 'sales'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="car-dashboard/index.php">
                                                    <i class="fa-solid fa-tachometer-alt"></i>
                                                    <span>Admin Dashboard</span>
                                                </a>
                                                <a class="dropdown-item" href="view_applications.php">
                                                    <i class="fas fa-briefcase"></i>
                                                    <span>View Job Applications</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="settings.php">
                                                <i class="fa-solid fa-cog"></i>
                                                <span>Settings</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="logout.php">
                                                <i class="fa-solid fa-sign-out-alt"></i>
                                                <span>Logout</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <button onclick="window.open ('login.php')" class="user-btn">
                                <i class="fa-solid fa-user"></i>
                                <span data-translate="Log In">Log In</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Header Section End -->

<!-- Breadcrumb Begin -->
<div class="breadcrumb-option set-bg" data-setbg="img/breadcrumb-about_us.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb__text">
                    <h2 data-translate="Work With Us">Work With Us</h2>
                    <div class="breadcrumb__links">
                        <a href="./index.html" data-translate="Home"><i class="fa fa-home"></i> Home</a>
                        <span data-translate="Work With Us">Work With Us</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Breadcrumb End -->

<!-- Contact Section Begin -->
<section class="contact spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-md-6">
                <div class="contact__text">
                    <div class="section-title">
                        <h2 data-translate="Join Our Team">Join Our Team</h2>
                        <p data-translate="We're always looking for talented individuals to join our automotive family. Submit your application and let's drive forward together.">We're always looking for talented individuals to join our automotive family. Submit your application and let's drive forward together.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="contact__form">
                    <!-- Success/Error Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            <?= $error_message ?>
                            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="jobApplicationForm">
                        <div class="row">
                            <div class="col-lg-6">
                                <input type="text" 
                                       name="full_name" 
                                       placeholder="Full Name" 
                                       data-translate="Full Name"
                                       value="<?= htmlspecialchars($full_name ?? '') ?>"
                                       required>
                            </div>
                            <div class="col-lg-6">
                                <input type="email" 
                                       name="email" 
                                       placeholder="Email" 
                                       data-translate="Email"
                                       value="<?= htmlspecialchars($email ?? '') ?>"
                                       required>
                            </div>
                        </div>
                        <input type="text" 
                               name="position" 
                               placeholder="Position Applying For" 
                               data-translate="Position Applying For"
                               value="<?= htmlspecialchars($position ?? '') ?>"
                               required>
                        <textarea name="about_yourself" 
                                  placeholder="Tell Us About Yourself" 
                                  data-translate="Tell Us About Yourself"
                                  required><?= htmlspecialchars($about_yourself ?? '') ?></textarea>
                        <button type="submit" class="site-btn" id="submitBtn" data-translate="Apply Now">
                            <span class="btn-text">Apply Now</span>
                            <span class="btn-loading" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Submitting...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Contact Section End -->

<!-- Footer Section Begin -->
<footer class="footer set-bg" style="background: url(img/footer-bg.jpg);">
    <div class="container">
        <div class="row pt-5">
            <!-- Logo and Contact Section -->
            <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-0">
                <div class="footer__about">
                    <div class="footer__logo">
                        <img src="img/logo zoom.png" alt="Autobuba Logo" class="img-fluid">
                    </div>
                    <div class="footer__contact-box">
                        <p><i class="fa fa-envelope"></i> autobuba2025@gmail.com</p>
                        <p><i class="fa fa-phone"></i> +355 69 311 3209</p>
                    </div>
                    <div class="footer__social">
                        <a href="https://www.tiktok.com/@autobuba1" class="social-btn tiktok" target="_blank">
                            <i class="fa-brands fa-tiktok"></i>
                        </a>
                        <a href="https://www.instagram.com/auto_buba?igsh=MWg1b213Y3oxeG1ydg=="
                            class="social-btn instagram" target="_blank">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Navigation and Learn Sections - Side by Side on Mobile -->
            <div class="col-lg-8 col-md-6 col-12">
                <div class="row">
                    <!-- Navigation Section -->
                    <div class="col-6">
                        <div class="footer__widget">
                            <h5 data-translate="Navigation">Navigation</h5>
                            <ul class="list-unstyled footer-links">
                                <li><a href="contact.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="Contact Us">Contact Us</span>
                                </a></li>
                                <li><a href="car.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Cars">Cars</span>
                                </a></li>
                                <li><a href="blog.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Blog">Blog</span>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Learn Section -->
                    <div class="col-6">
                        <div class="footer__brand">
                            <h5 data-translate="Learn">Learn</h5>
                            <ul class="list-unstyled footer-links">
                                <li><a href="about.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="About Us">About Us</span>
                                </a></li>
                                <li><a href="#chooseUs" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Why Us?">Why Us?</span>
                                </a></li>
                                <li><a href="#trustedName" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="Who are we?">Who are we?</span>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer__bottom">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="copyright" data-translate="Copyright">
                        © Copyright 2025 QUOLYTECH. All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Footer Section End -->

<!-- Simple Phone Button -->
<div class="phone-container">
    <div class="phone-logo" id="phoneLogo">
      <div class="phone-icon">
        <i class="fa-solid fa-phone"></i>
      </div>
    </div>
</div>
  
<!-- Contact Modal -->
<div class="modal" id="contactModal">
    <div class="modal-content">
      <h2 class="modal-title" data-translate="Contact Information">Contact Information</h2>
      <p data-translate="Please call us at">Please call us at:</p>
      <div class="modal-number-container">
        <a href="tel:+69 69 690 6969" class="modal-number" id="phoneNumber" data-translate="Phone Number">+355 69 311 3209</a>
        <button class="modal-copy-button" id="modalCopyButton">
          <svg class="modal-copy-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
          </svg>
        </button>
        <span class="copy-tooltip" id="modalTooltip" data-translate="Copied to clipboard">Copied to clipboard!</span>
      </div>
      <button class="close-button" id="closeModal" data-translate="Close">Close</button>
    </div>
</div>


 <!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartCars">
    <div class="cart-header">
        <h3 data-translate="Your Cart">Your Cart</h3>
        <button id="closeCartdisplay" class="close-cart">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <div class="cart-body" id="selectedCars">
        <!-- Cart items will be inserted here dynamically -->
    </div>
   
</div>

    <!-- Js Plugins -->
    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="js/jquery-3.3.1.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.nice-select.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="js/mixitup.min.js"></script>
<script src="js/jquery.slicknav.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/main.js"></script>
<script src="loading.js"></script>
<script src="js/hero.js"></script>
<script src="js/index-language-selector.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/cart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Initialize language settings
            let currentLang = localStorage.getItem('language') || 'en';
            document.documentElement.setAttribute('lang', currentLang);
        
            // 2. Define all translations
            const translations = {
        en: {
            // Navigation
            "Cars": "Cars",
            "Blog": "Blog",
            "Car Details": "Car Details",
            "About": "About",
            "Contact": "Contact",
            "Log In": "Log In",
            "Work":"Work",
            // Breadcrumb
            "Contact Us": "Contact Us",
            "Home": "Home",
            // Contact Content
            "Let's Work Together": "Let's Work Together",
            "To make requests for further information, contact us via our social channels.": "To make requests for further information, contact us via our social channels.",
            "Weekday": "Weekday",
            "08:00 am to 18:00 pm": "08:00 am to 18:00 pm",
            "Saturday:": "Saturday:",
            "10:00 am to 16:00 pm": "10:00 am to 16:00 pm",
            "Sunday:": "Sunday:",
            "Closed": "Closed",
            "Name": "Name",
            "Email": "Email",
            "Subject": "Subject",
            "Your Question": "Your Question",
            "Submit Now": "Submit Now",
            "Our Office": "Our Office",
            "Address": "Address",
            "Phone": "Phone",
            "Send Message": "Send Message",
            "Your message has been sent. Thank you!": "Your message has been sent. Thank you!",
            "Please fill out this field": "Please fill out this field",
            "Join Our Team": "Join Our Team",
            "Work With Us": "Work With Us",
            "We're always looking for talented individuals to join our automotive family. Submit your application and let's drive forward together.": "We're always looking for talented individuals to join our automotive family. Submit your application and let's drive forward together.",
            "Positions":"Positions",
            "Schedule":"Schedule",
            "Benefits":"Benefits",
            "Apply Now":"Apply Now",
            "Contact Us":"Contact Us",
            "Cars":"Cars",
            "Blog":"Blog",
            "Position Applying For":"Position Applying For",
            "Tell Us About Yourself":"Tell Us About Yourself",
            "Full Name":"Full Name",
            // Footer
            "Contact Us Now!": "Contact Us Now!",
            "Any questions? Let us know on +69 69 690 6969": "Any questions? Let us know on +69 69 690 6969",
            "Navigation": "Navigation",
            "Hatchback": "Hatchback",
            "Sedan": "Sedan",
            "SUV": "SUV",
            "Learn": "Learn",
            "About Us": "About Us",
            "Why Us?": "Why Us?",
            "Who are we?": "Who are we?",
            "Search here.....": "Search here.....",
            
        },
        sq: {
            // Navigation
            "Cars": "Makinat",
            "Blog": "Blogu",
            "Car Details": "Detajet e Makinës",
            "About": "Rreth Nesh",
            "Contact": "Kontakti",
            "Log In": "Hyr",
            "Work":"Puna",
            // Breadcrumb
            "Contact Us": "Na Kontaktoni",
            "Home": "Kryefaqja",
            // Contact Content
            "Let's Work Together": "Le të Punojmë Së Bashku",
            "To make requests for further information, contact us via our social channels.": "Për të bërë kërkesa për informacion të mëtejshëm, na kontaktoni përmes kanaleve tona sociale.",
            "Weekday": "Ditë Jave",
            "08:00 am to 18:00 pm": "08:00 deri në 18:00",
            "Saturday:": "E Shtunë:",
            "10:00 am to 16:00 pm": "10:00 deri në 16:00",
            "Sunday:": "E Diel:",
            "Closed": "Mbyllur",
            "Name": "Emri",
            "Email": "Email",
            "Subject": "Subjekti",
            "Your Question": "Pyetja Juaj",
            "Submit Now": "Dërgo Tani",
            "Our Office": "Zyra Jonë",
            "Address": "Adresa",
            "Phone": "Telefoni",
            "Send Message": "Dërgo Mesazh",
            "Your message has been sent. Thank you!": "Mesazhi juaj është dërguar. Faleminderit!",
            "Please fill out this field": "Ju lutemi plotësoni këtë fushë",
            "Join Our Team": "Bashkohu me Ne",
            "Work With Us": "Puno me Ne",
            "We're always looking for talented individuals to join our automotive family. Submit your application and let's drive forward together.": "Jemi gjithmonë në kërkim të individëve të talentuar për t'u bashkuar me familjen tonë automobilistike. Dorëzoni aplikacionin tuaj dhe të ecim përpara së bashku!",
            "Positions":"Pozicione",
            "Schedule":"Programi",
            "Benefits":"Përfitime",
            "Apply Now":"Apliko Tani",
            "Contact Us":"Na Kontaktoni",
            "Cars":"Makinat",
            "Blog":"Blogu",
            "Position Applying For":"Pozicionet për të aplikuar",
            "Tell Us About Yourself":"Na Trego për Veten",
            "Full Name":"Emri i Plotë",
            // Footer
            "Contact Us Now!": "Na Kontaktoni Tani!",
            "Any questions? Let us know on +69 69 690 6969": "Keni pyetje? Na kontaktoni në +69 69 690 6969",
            "Navigation": "Navigimi",
            "Hatchback": "Hatchback",
            "Sedan": "Sedan",
            "SUV": "SUV",
            "Learn": "Mëso",
            "About Us": "Rreth Nesh",
            "Why Us?": "Pse Ne?",
            "Who are we?": "Kush Jemi Ne?",
            "Search here.....": "Kërko këtu.....",
        }
    };
        
            // 3. Core translation function
           $(document).ready(function() {
            // Initialize owl carousel without autoplay
            $(".car__item__pic__slider").owlCarousel({
                loop: true,
                margin: 0,
                items: 1,
                dots: true,
                nav: true,
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                smartSpeed: 1200,
                autoHeight: false,
                autoplay: false // Removed autoplay
            });
            
            // Set initial language
            let currentLanguage = localStorage.getItem('language') || 'en';
            applyLanguage(currentLanguage);
            
            // Language selector functionality
            $('.language-option').click(function(e) {
                e.preventDefault();
                const lang = $(this).data('lang');
                currentLanguage = lang;
                localStorage.setItem('language', lang);
                applyLanguage(lang);
                
                // Update active state
                $('.language-option').removeClass('active');
                $(this).addClass('active');
                
                // Update flag icon
                $('#languageDropdown').html($(this).html());
            });
            
            // Apply language translations
            function applyLanguage(lang) {
                $('[data-translate]').each(function() {
                    const key = $(this).data('translate');
                    if (translations[lang] && translations[lang][key]) {
                        $(this).text(translations[lang][key]);
                    }
                });
                
                // Handle placeholders
                $('[data-translate-placeholder]').each(function() {
                    const key = $(this).data('translate-placeholder');
                    if (translations[lang] && translations[lang][key]) {
                        $(this).attr('placeholder', translations[lang][key]);
                    }
                });
            }

            // Form submission with loading state
            $('#jobApplicationForm').on('submit', function() {
                const submitBtn = $('#submitBtn');
                const btnText = submitBtn.find('.btn-text');
                const btnLoading = submitBtn.find('.btn-loading');
                
                // Show loading state
                btnText.hide();
                btnLoading.show();
                submitBtn.prop('disabled', true);
            });
        });
    });
        
    </script>

<script src="js/cart.js"></script>
<script src="js/language-icon.js"></script>

</body>

</html>